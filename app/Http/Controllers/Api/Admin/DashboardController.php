<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Poll;
use App\Models\ChatMessage;
use App\Models\News;
use App\Models\Meeting;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function statistics()
    {
        $this->authorize('viewAny', User::class);

        return response()->json([
            'userCount' => User::count(),
            'activePolls' => Poll::where('is_active', true)->count(),
            'newMessages' => ChatMessage::where('created_at', '>=', Carbon::now()->subDay())->count(),
            'upcomingMeetings' => Meeting::where('date', '>=', Carbon::now())->count(),
            'totalNews' => News::count(),
        ]);
    }

    public function recentActivities()
    {
        $this->authorize('viewAny', User::class);

        $activities = collect();

        // Get recent users
        $recentUsers = User::latest()->take(5)->get()->map(function ($user) {
            return [
                'type' => 'user_created',
                'description' => "New user registered: {$user->name}",
                'timestamp' => $user->created_at,
            ];
        });

        // Get recent polls
        $recentPolls = Poll::latest()->take(5)->get()->map(function ($poll) {
            return [
                'type' => 'poll_created',
                'description' => "New poll created: {$poll->title}",
                'timestamp' => $poll->created_at,
            ];
        });

        // Get recent news
        $recentNews = News::latest()->take(5)->get()->map(function ($news) {
            return [
                'type' => 'news_created',
                'description' => "New news item posted",
                'timestamp' => $news->created_at,
            ];
        });

        // Merge all activities and sort by timestamp
        $activities = $activities->concat($recentUsers)
            ->concat($recentPolls)
            ->concat($recentNews)
            ->sortByDesc('timestamp')
            ->take(10)
            ->values();

        return response()->json($activities);
    }
} 