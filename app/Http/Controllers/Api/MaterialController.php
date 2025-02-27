<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Material;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Str;

class MaterialController extends Controller
{
    private $filesPath;
    private $videosPath;
    
    public function __construct()
    {
        $this->middleware('auth:sanctum')->except(['stream']);
        $this->filesPath = public_path('files');
        $this->videosPath = public_path('videos');
    }

    public function index()
    {
        return Material::with('creator')->latest()->get();
    }

    public function store(Request $request)
    {
        $this->authorize('create', Material::class);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|in:video,file',
            'file' => 'required|file|max:102400', // 100MB max
            'file_type' => 'required_if:type,file|string|max:255',
        ]);

        $file = $request->file('file');
        $extension = $file->getClientOriginalExtension();
        $fileName = Str::slug(pathinfo($validated['title'], PATHINFO_FILENAME)) . '-' . time() . '.' . $extension;
        
        // Move file to the appropriate directory
        if ($validated['type'] === 'video') {
            $file->move(public_path('videos'), $fileName);
            $path = 'videos/' . $fileName;
        } else {
            $file->move(public_path('files'), $fileName);
            $path = 'files/' . $fileName;
        }

        $material = Material::create([
            'title' => $validated['title'],
            'type' => $validated['type'],
            'src' => $path,
            'file_type' => $extension,
            'created_by' => $request->user()->id,
        ]);

        return response()->json($material->load('creator'), 201);
    }

    public function show(Material $material)
    {
        return response()->json($material->load('creator'));
    }

    public function update(Request $request, Material $material)
    {
        $this->authorize('update', $material);

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'type' => 'sometimes|in:video,file',
            'file' => 'sometimes|file|max:102400', // 100MB max
            'file_type' => 'required_if:type,file|string|max:255',
        ]);

        if ($request->hasFile('file')) {
            // Delete old file
            Storage::disk('public')->delete($material->src);

            // Store new file
            $path = $request->file('file')->store('materials/' . $validated['type'], 'public');
            $validated['src'] = $path;
        }

        $material->update($validated);

        return response()->json($material->load('creator'));
    }

    public function destroy(Material $material)
    {
        $this->authorize('delete', $material);

        // Delete the file
        Storage::disk('public')->delete($material->src);
        
        $material->delete();

        return response()->json(null, 204);
    }

    public function stream(Material $material)
    {
        // Get the correct file path from storage
        $filePath = public_path($material->src);

        if (!file_exists($filePath)) {
            // Fallback to direct videos directory for older files
            $fallbackPath = $material->type === 'video'
                ? public_path('videos/' . basename($material->src))
                : public_path('files/' . basename($material->src));

            if (!file_exists($fallbackPath)) {
                return response()->json([
                    'message' => 'File not found',
                    'path' => $filePath,
                    'fallback' => $fallbackPath
                ], 404);
            }
            $filePath = $fallbackPath;
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
} 