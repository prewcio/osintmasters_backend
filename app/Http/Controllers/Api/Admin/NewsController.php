<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\News;
use App\Models\User;
use Illuminate\Http\Request;

class NewsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    private function formatNewsItem($item)
    {
        return [
            'id' => $item->id,
            'content' => $item->content,
            'is_system_post' => $item->is_system_post,
            'created_at' => $item->created_at,
            'updated_at' => $item->updated_at,
            'author' => [
                'id' => $item->is_system_post ? null : $item->user->id,
                'name' => $item->is_system_post ? 'SYSTEM' : $item->user->name
            ]
        ];
    }

    public function index()
    {
        $this->authorize('viewAny', News::class);
        
        $news = News::with('user')
            ->latest()
            ->get()
            ->map(function ($item) {
                return $this->formatNewsItem($item);
            });

        return response()->json($news);
    }

    public function store(Request $request)
    {
        $this->authorize('create', News::class);

        $validated = $request->validate([
            'content' => 'required|string',
            'is_system_post' => 'boolean',
        ]);

        $news = News::create([
            'content' => $validated['content'],
            'is_system_post' => $validated['is_system_post'] ?? false,
            'author' => $request->user()->id,
        ]);

        $news->load('user');

        return response()->json($this->formatNewsItem($news), 201);
    }

    public function update(Request $request, News $news)
    {
        $this->authorize('update', $news);

        $validated = $request->validate([
            'content' => 'required|string',
            'is_system_post' => 'boolean',
        ]);

        $news->update([
            'content' => $validated['content'],
            'is_system_post' => $validated['is_system_post'] ?? $news->is_system_post,
        ]);

        $news->load('user');

        return response()->json($this->formatNewsItem($news));
    }

    public function destroy(News $news)
    {
        $this->authorize('delete', $news);
        
        $news->delete();

        return response()->json(['message' => 'News item deleted successfully']);
    }
} 