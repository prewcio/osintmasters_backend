<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MessageController extends Controller
{
    public function index()
    {
        return DB::table('chat_messages')
            ->join('users', 'chat_messages.user_id', '=', 'users.id')
            ->select([
                'chat_messages.id',
                'chat_messages.user_id',
                'users.name',
                'chat_messages.content',
                'chat_messages.created_at',
                'chat_messages.updated_at'
            ])
            ->orderBy('chat_messages.created_at', 'asc')
            ->latest('chat_messages.created_at')
            ->limit(50)
            ->get();
    }

    public function store(Request $request)
    {
        $request->validate([
            'content' => 'required|string|max:1000',
        ]);

        $message = DB::table('chat_messages')->insert([
            'user_id' => Auth::id(),
            'content' => $request->content,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $inserted = DB::table('chat_messages')
            ->join('users', 'chat_messages.user_id', '=', 'users.id')
            ->select([
                'chat_messages.id',
                'chat_messages.user_id',
                'users.name',
                'chat_messages.content',
                'chat_messages.created_at',
                'chat_messages.updated_at'
            ])
            ->orderBy('chat_messages.created_at', 'desc')
            ->first();

        return response()->json($inserted, 201);
    }
}
