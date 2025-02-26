<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\News;
use App\Models\Meeting;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $news = News::with('user')
            ->latest()
            ->take(5)
            ->get();

        $upcomingMeeting = Meeting::where('date', '>=', Carbon::now())
            ->orderBy('date')
            ->with('creator')
            ->first();

        return response()->json([
            'news' => $news,
            'upcomingMeeting' => $upcomingMeeting,
        ]);
    }
} 