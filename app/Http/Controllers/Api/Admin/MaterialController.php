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
        
        // Ensure directories exist with proper permissions
        foreach ([$this->chunkPath, $this->filesPath, $this->videosPath] as $path) {
            if (!file_exists($path)) {
                mkdir($path, 0777, true);
                chmod($path, 0777);
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
        try {
            $request->validate([
                'title' => 'required|string|max:255',
                'type' => 'required|in:video,file',
                'file' => 'required',
                'chunk' => 'required|integer|min:0',
                'chunks' => 'required|integer|min:1',
                'chunk_size' => 'required|integer|min:1'
            ]);

            $file = $request->file('file');
            if (!$file || !$file->isValid()) {
                return response()->json([
                    'message' => 'The file failed to upload.',
                    'error' => $file ? $file->getError() : 'No file received'
                ], 422)->header('Access-Control-Allow-Origin', '*')
                  ->header('Access-Control-Allow-Methods', 'POST, OPTIONS')
                  ->header('Access-Control-Allow-Headers', 'Content-Type, X-Requested-With');
            }

            // Log upload attempt
            \Log::info('File upload attempt', [
                'chunk' => $request->input('chunk'),
                'chunks' => $request->input('chunks'),
                'filename' => $request->input('title'),
                'type' => $request->input('type')
            ]);

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
                        $mimeType !== 'application/octet-stream') {
                        return response()->json([
                            'message' => 'Invalid video format. Allowed formats: mp4, webm, ogg',
                            'detected_extension' => $extension,
                            'mime_type' => $mimeType
                        ], 422)->header('Access-Control-Allow-Origin', '*')
                          ->header('Access-Control-Allow-Methods', 'POST, OPTIONS')
                          ->header('Access-Control-Allow-Headers', 'Content-Type, X-Requested-With');
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
                        ], 422)->header('Access-Control-Allow-Origin', '*')
                          ->header('Access-Control-Allow-Methods', 'POST, OPTIONS')
                          ->header('Access-Control-Allow-Headers', 'Content-Type, X-Requested-With');
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

                \Log::info('Created material record', ['material_id' => $material->id]);
            } else {
                // For subsequent chunks, get the filename from the existing material record
                $material = Material::where('title', $originalName)
                    ->where('type', $request->type)
                    ->where('created_by', $request->user()->id)
                    ->latest()
                    ->first();

                if (!$material) {
                    return response()->json(['message' => 'Material record not found'], 404)
                        ->header('Access-Control-Allow-Origin', '*')
                        ->header('Access-Control-Allow-Methods', 'POST, OPTIONS')
                        ->header('Access-Control-Allow-Headers', 'Content-Type, X-Requested-With');
                }

                $fileName = $material->src;
            }

            // Create directory for this file's chunks if it doesn't exist
            $fileChunkPath = "{$this->chunkPath}/" . pathinfo($fileName, PATHINFO_FILENAME);
            if (!file_exists($fileChunkPath)) {
                if (!mkdir($fileChunkPath, 0777, true)) {
                    throw new \Exception('Failed to create chunk directory');
                }
                chmod($fileChunkPath, 0777);
            }

            // Save this chunk with proper naming to ensure correct order
            $chunkFile = $fileChunkPath . "/chunk_{$chunk}";
            if (!move_uploaded_file($file->getRealPath(), $chunkFile)) {
                throw new \Exception('Failed to move uploaded chunk');
            }
            chmod($chunkFile, 0666);

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
                if (!$out) {
                    throw new \Exception('Failed to create target file');
                }
                
                // Concatenate all chunks
                foreach ($uploadedChunks as $chunk) {
                    $in = fopen($chunk, 'rb');
                    if ($in) {
                        while (!feof($in)) {
                            $buffer = fread($in, 8192);
                            fwrite($out, $buffer);
                        }
                        fclose($in);
                        unlink($chunk);
                    }
                }
                
                fclose($out);
                chmod($targetFile, 0666);
                rmdir($fileChunkPath);

                \Log::info('File upload completed', [
                    'material_id' => $material->id,
                    'filename' => $fileName
                ]);

                return response()->json([
                    'message' => 'File uploaded successfully',
                    'material' => $material->load('creator')
                ], 201)->header('Access-Control-Allow-Origin', '*')
                    ->header('Access-Control-Allow-Methods', 'POST, OPTIONS')
                    ->header('Access-Control-Allow-Headers', 'Content-Type, X-Requested-With');
            }

            return response()->json([
                'message' => 'Chunk uploaded successfully',
                'uploaded' => $isComplete,
                'progress' => (count($uploadedChunks) / $chunks) * 100
            ])->header('Access-Control-Allow-Origin', '*')
              ->header('Access-Control-Allow-Methods', 'POST, OPTIONS')
              ->header('Access-Control-Allow-Headers', 'Content-Type, X-Requested-With');

        } catch (\Exception $e) {
            \Log::error('File upload error: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            
            // Clean up any temporary files
            if (isset($fileChunkPath) && file_exists($fileChunkPath)) {
                $chunks = glob("{$fileChunkPath}/chunk_*");
                foreach ($chunks as $chunk) {
                    unlink($chunk);
                }
                rmdir($fileChunkPath);
            }

            return response()->json([
                'message' => 'File upload failed',
                'error' => $e->getMessage()
            ], 500)->header('Access-Control-Allow-Origin', '*')
                  ->header('Access-Control-Allow-Methods', 'POST, OPTIONS')
                  ->header('Access-Control-Allow-Headers', 'Content-Type, X-Requested-With');
        }
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