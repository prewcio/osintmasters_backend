<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use Illuminate\Http\Request;
use App\Events\NewChatMessage;
use App\Models\Message;

class ChatController extends Controller
{
    public function index()
    {
        $messages = Message::with('user')->latest()->get(); // Assuming you have a relationship set up
        return response()->json($messages);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'content' => 'required|string|max:255',
        ]);

        $message = ChatMessage::create([
            'content' => $validated['content'],
            'user_id' => $request->user()->id,
        ]);

        broadcast(new NewChatMessage($message))->toOthers();

        return response()->json(['message' => 'Message received!', 'data' => $message], 201);
    }
} 