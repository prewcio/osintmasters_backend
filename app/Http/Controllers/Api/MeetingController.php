<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Meeting;
use Illuminate\Http\Request;

class MeetingController extends Controller
{
    public function index()
    {
        return Meeting::with('creator')
            ->orderBy('date')
            ->get();
    }

    public function store(Request $request)
    {
        $this->authorize('create', Meeting::class);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'date' => 'required|date',
            'location' => 'required|string|max:255',
            'link' => 'nullable|url|max:255',
        ]);

        $meeting = Meeting::create([
            'title' => $validated['title'],
            'date' => $validated['date'],
            'location' => $validated['location'],
            'link' => $validated['link'] ?? null,
            'created_by' => $request->user()->id,
        ]);

        return response()->json($meeting->load('creator'), 201);
    }

    public function show(Meeting $meeting)
    {
        return response()->json($meeting->load('creator'));
    }

    public function update(Request $request, Meeting $meeting)
    {
        $this->authorize('update', $meeting);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'date' => 'required|date',
            'location' => 'required|string|max:255',
            'link' => 'nullable|url|max:255',
        ]);

        $meeting->update($validated);

        return response()->json($meeting->load('creator'));
    }

    public function destroy(Meeting $meeting)
    {
        $this->authorize('delete', $meeting);
        
        $meeting->delete();

        return response()->json(null, 204);
    }

    public function upcoming()
    {
        $meeting = Meeting::with(['creator:id,name'])
            ->where('date', '>', now())
            ->orderBy('date')
            ->first();

        if (!$meeting) {
            return response()->json(['message' => 'No upcoming meetings found'], 404);
        }

        return response()->json([
            'id' => $meeting->id,
            'title' => $meeting->title,
            'date' => $meeting->date,
            'location' => $meeting->location,
            'link' => $meeting->link,
            'creator' => $meeting->creator
        ]);
    }
} 