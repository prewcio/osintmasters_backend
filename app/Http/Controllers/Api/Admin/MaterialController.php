<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Material;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MaterialController extends Controller
{
    private $chunkPath;
    private $filesPath;
    private $videosPath;
    
    public function __construct()
    {
        $this->middleware('auth:sanctum')->except(['stream']);
        $this->chunkPath = storage_path('app/chunks');
        $this->filesPath = public_path('files');
        $this->videosPath = public_path('videos');
        
        // Ensure directories exist
        foreach ([$this->chunkPath, $this->filesPath, $this->videosPath] as $path) {
            if (!file_exists($path)) {
                mkdir($path, 0777, true);
            }
        }
    }

    public function index()
    {
        $materials = Material::with('creator:id,name')
            ->latest()
            ->get()
            ->map(function ($material) {
                return [
                    'id' => $material->id,
                    'title' => $material->title,
                    'type' => $material->type,
                    'src' => $material->type === 'video' 
                        ? asset('videos/' . $material->src)
                        : asset('files/' . $material->src),
                    'file_type' => $material->file_type,
                    'created_at' => $material->created_at,
                    'creator' => $material->creator
                ];
            });

        return response()->json($materials);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|in:video,file',
            'file' => 'required|file',
            'chunk' => 'required|integer',
            'chunks' => 'required|integer',
            'chunk_size' => 'required|integer'
        ]);

        $file = $request->file('file');
        $chunk = (int)$request->input('chunk');
        $chunks = (int)$request->input('chunks');
        $originalName = $request->input('title');
        
        // Only validate file type on the first chunk
        if ($chunk === 0) {
            $mimeType = $file->getMimeType();
            $extension = $this->getExtensionFromMimeType($mimeType);
            
            if (empty($extension)) {
                $extension = $file->getClientOriginalExtension();
            }
            
            if (empty($extension) && strpos($originalName, '.') !== false) {
                $extension = pathinfo($originalName, PATHINFO_EXTENSION);
            }

            // For videos, if we still don't have an extension, default to mp4
            if ($request->type === 'video' && empty($extension)) {
                $extension = 'mp4';
            }
            
            // Generate unique filename for the complete file
            $fileName = Str::slug(pathinfo($originalName, PATHINFO_FILENAME)) . '-' . time() . '.' . $extension;
            
            // Validate file type
            if ($request->type === 'video') {
                $allowedTypes = ['mp4', 'webm', 'ogg'];
                if (!in_array(strtolower($extension), $allowedTypes) && 
                    !str_starts_with($mimeType, 'video/') &&
                    $mimeType !== 'application/octet-stream') {
                    return response()->json([
                        'message' => 'Invalid video format. Allowed formats: mp4, webm, ogg',
                        'detected_extension' => $extension,
                        'mime_type' => $mimeType
                    ], 422);
                }
            } else {
                $allowedTypes = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt'];
                if (!in_array(strtolower($extension), $allowedTypes) && 
                    !in_array($mimeType, [
                        'application/pdf',
                        'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'application/vnd.ms-excel',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'text/plain'
                    ])) {
                    return response()->json([
                        'message' => 'Invalid file format. Allowed formats: pdf, doc, docx, xls, xlsx, txt',
                        'detected_extension' => $extension,
                        'mime_type' => $mimeType
                    ], 422);
                }
            }

            // Create material record with the determined extension
            $material = Material::create([
                'title' => $originalName,
                'type' => $request->type,
                'src' => $fileName,
                'file_type' => $extension,
                'created_by' => $request->user()->id,
            ]);
        } else {
            // For subsequent chunks, get the filename from the existing material record
            $material = Material::where('title', $originalName)
                ->where('type', $request->type)
                ->where('created_by', $request->user()->id)
                ->latest()
                ->first();

            if (!$material) {
                return response()->json(['message' => 'Material record not found'], 404);
            }

            $fileName = $material->src;
        }

        // Create directory for this file's chunks if it doesn't exist
        $fileChunkPath = "{$this->chunkPath}/" . pathinfo($fileName, PATHINFO_FILENAME);
        if (!file_exists($fileChunkPath)) {
            mkdir($fileChunkPath, 0777, true);
        }

        // Save this chunk with proper naming to ensure correct order
        $chunkFile = $fileChunkPath . "/chunk_{$chunk}";
        file_put_contents($chunkFile, file_get_contents($file->getRealPath()));

        // Check if all chunks have been uploaded
        $uploadedChunks = glob("{$fileChunkPath}/chunk_*");
        $isComplete = count($uploadedChunks) === $chunks;

        // If this is the last chunk, assemble the file
        if ($isComplete) {
            $targetPath = $request->type === 'video' ? $this->videosPath : $this->filesPath;
            $targetFile = "{$targetPath}/{$fileName}";
            
            // Sort chunks to ensure correct order
            sort($uploadedChunks, SORT_NATURAL);
            
            // Create/open target file
            $out = fopen($targetFile, 'wb');
            
            // Concatenate all chunks
            foreach ($uploadedChunks as $chunk) {
                $in = fopen($chunk, 'rb');
                while (!feof($in)) {
                    $buffer = fread($in, 8192); // Read in smaller chunks
                    fwrite($out, $buffer);
                }
                fclose($in);
                unlink($chunk); // Delete chunk after use
            }
            
            fclose($out);
            rmdir($fileChunkPath); // Remove chunks directory

            // Verify file integrity
            clearstatcache(true, $targetFile);
            $finalSize = filesize($targetFile);
            $expectedSize = $request->input('chunk_size') * ($chunks - 1) + $file->getSize();
            
            if ($finalSize !== $expectedSize) {
                unlink($targetFile);
                $material->delete();
                return response()->json([
                    'message' => 'File assembly failed. Size mismatch.',
                    'expected' => $expectedSize,
                    'actual' => $finalSize
                ], 500);
            }
        }

        return response()->json([
            'uploaded' => $isComplete,
            'progress' => (count($uploadedChunks) / $chunks) * 100
        ]);
    }

    private function getExtensionFromMimeType($mimeType)
    {
        $mimeToExt = [
            'application/pdf' => 'pdf',
            'application/msword' => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'application/vnd.ms-excel' => 'xls',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
            'text/plain' => 'txt',
            'video/mp4' => 'mp4',
            'video/webm' => 'webm',
            'video/ogg' => 'ogg',
            'application/octet-stream' => '' // Allow octet-stream and handle extension separately
        ];

        return $mimeToExt[$mimeType] ?? '';
    }

    public function stream(Material $material)
    {
        $filePath = $material->type === 'video'
            ? "{$this->videosPath}/{$material->src}"
            : "{$this->filesPath}/{$material->src}";

        if (!file_exists($filePath)) {
            return response()->json(['message' => 'File not found'], 404);
        }

        $fileSize = filesize($filePath);
        $contentType = $this->getContentType($material->file_type);

        // Handle range request
        $start = 0;
        $end = $fileSize - 1;
        $statusCode = 200;

        if (isset($_SERVER['HTTP_RANGE'])) {
            $statusCode = 206; // Partial Content
            
            // Parse the range header
            if (preg_match('/bytes=\h*(\d+)-(\d*)/i', $_SERVER['HTTP_RANGE'], $matches)) {
                $start = intval($matches[1]);
                
                // If the end byte is specified
                if (!empty($matches[2])) {
                    $end = intval($matches[2]);
                }
            }
        }

        // Calculate length
        $length = $end - $start + 1;

        // Prepare headers
        $headers = [
            'Content-Type' => $contentType,
            'Content-Length' => $length,
            'Accept-Ranges' => 'bytes',
            'Content-Range' => "bytes $start-$end/$fileSize",
            'Content-Disposition' => 'inline; filename="' . $material->title . '"'
        ];

        // Create streamed response
        return new StreamedResponse(function () use ($filePath, $start, $length) {
            $handle = fopen($filePath, 'rb');
            fseek($handle, $start);
            $remaining = $length;
            $chunkSize = 1024 * 1024; // 1MB chunks

            while (!feof($handle) && $remaining > 0) {
                $readSize = min($chunkSize, $remaining);
                $data = fread($handle, $readSize);
                echo $data;
                flush();
                $remaining -= strlen($data);
            }

            fclose($handle);
        }, $statusCode, $headers);
    }

    private function getContentType($extension)
    {
        $mimeTypes = [
            'mp4' => 'video/mp4',
            'webm' => 'video/webm',
            'ogg' => 'video/ogg',
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'txt' => 'text/plain'
        ];

        return $mimeTypes[strtolower($extension)] ?? 'application/octet-stream';
    }

    public function destroy(Material $material)
    {
        // Delete the actual file
        $filePath = $material->type === 'video'
            ? "{$this->videosPath}/{$material->src}"
            : "{$this->filesPath}/{$material->src}";

        if (file_exists($filePath)) {
            unlink($filePath);
        }

        // Delete any leftover chunks
        $fileChunkPath = "{$this->chunkPath}/" . pathinfo($material->src, PATHINFO_FILENAME);
        if (file_exists($fileChunkPath)) {
            $chunks = glob("{$fileChunkPath}/chunk_*");
            foreach ($chunks as $chunk) {
                unlink($chunk);
            }
            rmdir($fileChunkPath);
        }

        $material->delete();
        return response()->json(['message' => 'Material deleted successfully']);
    }
}