<?php

namespace App\Broadcasting;

use Illuminate\Broadcasting\Broadcasters\Broadcaster;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SSEBroadcaster extends Broadcaster
{
    public function auth($request)
    {
        return true;
    }

    public function validAuthenticationResponse($request, $result)
    {
        return $result;
    }

    public function broadcast(array $channels, $event, array $payload = [])
    {
        $data = [
            'event' => $event,
            'data' => $payload,
            'time' => now()->timestamp
        ];

        // Store the event in cache for 1 minute
        foreach ($channels as $channel) {
            $key = "sse_events_{$channel}";
            $events = Cache::get($key, []);
            $events[] = $data;
            Cache::put($key, $events, now()->addMinute());
        }

        return true;
    }

    public function stream($channel)
    {
        return new StreamedResponse(function () use ($channel) {
            $lastEventId = 0;

            while (true) {
                $key = "sse_events_{$channel}";
                $events = Cache::get($key, []);

                foreach ($events as $event) {
                    if ($event['time'] > $lastEventId) {
                        echo "id: {$event['time']}\n";
                        echo "event: {$event['event']}\n";
                        echo "data: " . json_encode($event['data']) . "\n\n";
                        $lastEventId = $event['time'];
                    }
                }

                ob_flush();
                flush();
                sleep(1);
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no'
        ]);
    }
} 