<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Broadcasting\SSEBroadcaster;

class ChatController extends Controller
{
    protected $broadcaster;

    public function __construct()
    {
        $this->middleware('auth:sanctum');
        $this->broadcaster = new SSEBroadcaster();
    }

    public function index()
    {
        $messages = ChatMessage::with('user')->latest()->get();
        return response()->json($messages);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'content' => 'required|string|max:1000',
        ]);

        $message = ChatMessage::create([
            'content' => $validated['content'],
            'user_id' => Auth::id(),
        ]);

        $this->broadcaster->broadcast(['chat'], 'new-message', ['message' => $message->load('user')]);

        return response()->json($message->load('user'), 201);
    }

    public function stream(Request $request)
    {
        $response = $this->broadcaster->stream('chat');
        
        return $response->header('Access-Control-Allow-Origin', config('cors.allowed_origins')[0])
                       ->header('Access-Control-Allow-Credentials', 'true')
                       ->header('Access-Control-Allow-Methods', 'GET, POST')
                       ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization')
                       ->header('Cache-Control', 'no-cache')
                       ->header('X-Accel-Buffering', 'no');
    }
} 