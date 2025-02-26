<?php

namespace App\Http\Controllers;

use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class VideoStreamController extends Controller
{
    private $path;
    private $stream;
    private $buffer = 102400;
    private $start = -1;
    private $end = -1;
    private $size = 0;

    /**
     * Stream the video file
     *
     * @param string $path
     * @return StreamedResponse|Response
     */
    public function stream(string $path)
    {
        try {
            Log::info('Video stream request', ['path' => $path]);
            
            // Use public_path() to get the correct path to public directory
            $this->path = public_path($path);
            Log::info('Full file path', ['full_path' => $this->path]);
            
            if (!file_exists($this->path)) {
                Log::error('Video file not found', ['path' => $this->path]);
                return response()->json(['error' => 'Video not found'], 404)
                    ->header('Access-Control-Allow-Origin', '*')
                    ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
                    ->header('Access-Control-Allow-Headers', 'Content-Type, Range');
            }

            // Handle OPTIONS request for CORS
            if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
                return response('', 200)
                    ->header('Access-Control-Allow-Origin', '*')
                    ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
                    ->header('Access-Control-Allow-Headers', 'Content-Type, Range');
            }

            $this->start = 0;
            $this->size = filesize($this->path);
            $this->end = $this->size - 1;

            Log::info('Video file info', [
                'size' => $this->size,
                'mime' => mime_content_type($this->path)
            ]);

            if (isset($_SERVER['HTTP_RANGE'])) {
                $this->start = $this->getStartFromRange();
                $this->end = $this->getEndFromRange();
                Log::info('Range request', [
                    'range' => $_SERVER['HTTP_RANGE'],
                    'start' => $this->start,
                    'end' => $this->end
                ]);
                return $this->streamPartialContent();
            }

            return $this->streamFullContent();
        } catch (\Exception $e) {
            Log::error('Video streaming error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Internal server error'], 500)
                ->header('Access-Control-Allow-Origin', '*')
                ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
                ->header('Access-Control-Allow-Headers', 'Content-Type, Range');
        }
    }

    private function streamPartialContent()
    {
        $response = new StreamedResponse(function() {
            $this->streamVideo();
        });

        $response->headers->set('Content-Type', 'video/mp4');
        $response->headers->set('Content-Length', $this->end - $this->start + 1);
        $response->headers->set('Content-Range', sprintf('bytes %d-%d/%d', $this->start, $this->end, $this->size));
        $response->headers->set('Accept-Ranges', 'bytes');
        $response->headers->set('Cache-Control', 'public, max-age=2592000');
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Range');
        $response->setStatusCode(206);

        return $response;
    }

    private function streamFullContent()
    {
        $response = new StreamedResponse(function() {
            $this->streamVideo();
        });

        $response->headers->set('Content-Type', 'video/mp4');
        $response->headers->set('Content-Length', $this->size);
        $response->headers->set('Accept-Ranges', 'bytes');
        $response->headers->set('Cache-Control', 'public, max-age=2592000');
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Range');

        return $response;
    }

    /**
     * Stream video in chunks
     */
    private function streamVideo()
    {
        try {
            $this->stream = fopen($this->path, 'rb');
            if ($this->stream === false) {
                Log::error('Could not open video file for streaming', ['path' => $this->path]);
                throw new \Exception('Could not open video file');
            }

            if ($this->start > 0) {
                fseek($this->stream, $this->start);
            }

            $i = $this->start;
            set_time_limit(0);
            while(!feof($this->stream) && $i <= $this->end) {
                $bytesToRead = $this->buffer;
                if(($i + $bytesToRead) > $this->end) {
                    $bytesToRead = $this->end - $i + 1;
                }
                $data = fread($this->stream, $bytesToRead);
                if ($data === false) {
                    Log::error('Error reading from stream', ['position' => $i]);
                    break;
                }
                echo $data;
                flush();
                $i += $bytesToRead;
            }
        } catch (\Exception $e) {
            Log::error('Error in streamVideo', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        } finally {
            if ($this->stream) {
                fclose($this->stream);
            }
        }
    }

    private function getStartFromRange()
    {
        $range = explode('-', substr($_SERVER['HTTP_RANGE'], 6));
        return intval($range[0]);
    }

    private function getEndFromRange()
    {
        $range = explode('-', substr($_SERVER['HTTP_RANGE'], 6));
        return (isset($range[1]) && is_numeric($range[1])) 
            ? intval($range[1]) 
            : $this->size - 1;
    }
} 