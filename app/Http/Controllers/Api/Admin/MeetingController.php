<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Meeting;
use Illuminate\Http\Request;

class MeetingController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', User::class);
        return Meeting::with('creator')->orderBy('date')->get();
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

    public function update(Request $request, Meeting $meeting)
    {
        $this->authorize('update', $meeting);

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'date' => 'sometimes|date',
            'location' => 'sometimes|string|max:255',
            'link' => 'nullable|url|max:255',
        ]);

        $meeting->update($validated);

        return response()->json($meeting->load('creator'));
    }

    public function destroy(Meeting $meeting)
    {
        $this->authorize('delete', $meeting);
        
        $meeting->delete();

        return response()->json(['message' => 'Meeting deleted successfully']);
    }
} 