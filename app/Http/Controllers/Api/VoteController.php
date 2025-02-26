<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Poll;
use App\Models\PollResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VoteController extends Controller
{
    public function active()
    {
        $user = request()->user();
        
        $polls = Poll::with(['questions', 'responses' => function($query) use ($user) {
                $query->where('user_id', $user->id);
            }])
            ->active()
            ->get()
            ->map(function ($poll) {
                return [
                    'id' => $poll->id,
                    'title' => $poll->title,
                    'description' => $poll->description,
                    'questions' => $poll->questions->map(function ($question) {
                        return [
                            'question' => $question->question,
                            'type' => $question->type
                        ];
                    }),
                    'active' => $poll->is_active,
                    'created_at' => $poll->created_at,
                    'expires_at' => $poll->expires_at,
                    'is_system_post' => $poll->is_system_post,
                    'total_votes' => $poll->total_votes,
                    'has_voted' => $poll->responses->isNotEmpty()
                ];
            });

        return response()->json(['polls' => $polls]);
    }

    public function show(Poll $poll)
    {
        $user = request()->user();
        
        $poll->load(['questions', 'responses' => function($query) use ($user) {
            $query->where('user_id', $user->id);
        }]);

        return response()->json([
            'id' => $poll->id,
            'title' => $poll->title,
            'description' => $poll->description,
            'questions' => $poll->questions,
            'active' => $poll->is_active,
            'created_at' => $poll->created_at,
            'expires_at' => $poll->expires_at,
            'is_system_post' => $poll->is_system_post,
            'has_voted' => $poll->responses->isNotEmpty()
        ]);
    }

    public function store(Request $request, Poll $poll)
    {
        // Validate the request
        $validated = $request->validate([
            'responses' => 'required|array|min:1',
            'responses.*.question_index' => 'required|integer',
            'responses.*.response' => 'required|array',
            'responses.*.response.type' => 'required|in:single,multiple,text,scale'
        ]);

        // Get all questions for validation
        $questions = $poll->questions()->orderBy('question_order')->get();

        // Validate each response
        foreach ($validated['responses'] as $response) {
            if (!isset($questions[$response['question_index']])) {
                return response()->json([
                    'message' => "Question at index {$response['question_index']} not found"
                ], 422);
            }

            $question = $questions[$response['question_index']];

            // Validate response type matches question type
            if ($response['response']['type'] !== $question->type) {
                return response()->json([
                    'message' => "Response type does not match question type for question {$question->id}"
                ], 422);
            }

            // Validate response format based on type
            switch ($question->type) {
                case 'single':
                    if (!isset($response['response']['selected_option']) || 
                        !is_numeric($response['response']['selected_option']) ||
                        $response['response']['selected_option'] >= count($question->options)) {
                        return response()->json([
                            'message' => "Invalid option selected for single choice question {$question->id}"
                        ], 422);
                    }
                    break;

                case 'multiple':
                    if (!isset($response['response']['selected_options']) || 
                        !is_array($response['response']['selected_options'])) {
                        return response()->json([
                            'message' => "Invalid options selected for multiple choice question {$question->id}"
                        ], 422);
                    }
                    foreach ($response['response']['selected_options'] as $option) {
                        if (!is_numeric($option) || $option >= count($question->options)) {
                            return response()->json([
                                'message' => "Invalid option selected for multiple choice question {$question->id}"
                            ], 422);
                        }
                    }
                    break;

                case 'text':
                    if (!isset($response['response']['text']) || 
                        !is_string($response['response']['text'])) {
                        return response()->json([
                            'message' => "Invalid text response for question {$question->id}"
                        ], 422);
                    }
                    break;

                case 'scale':
                    if (!isset($response['response']['value']) || 
                        !is_numeric($response['response']['value']) ||
                        $response['response']['value'] < $question->scale_config['min'] ||
                        $response['response']['value'] > $question->scale_config['max']) {
                        return response()->json([
                            'message' => "Invalid scale value for question {$question->id}"
                        ], 422);
                    }
                    break;
            }
        }

        try {
            DB::beginTransaction();

            // Delete any existing responses from this user for this poll
            PollResponse::where('poll_id', $poll->id)
                ->where('user_id', $request->user()->id)
                ->delete();

            // Create new responses
            foreach ($validated['responses'] as $response) {
                $question = $questions[$response['question_index']];
                PollResponse::create([
                    'poll_id' => $poll->id,
                    'question_id' => $question->id,
                    'user_id' => $request->user()->id,
                    'response_data' => $response['response']
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Vote submitted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function activeCount()
    {
        $activePollVotes = PollResponse::select('poll_id', DB::raw('count(distinct user_id) as vote_count'))
            ->whereIn('poll_id', function($query) {
                $query->select('id')
                    ->from('polls')
                    ->where('is_active', true)
                    ->where(function ($q) {
                        $q->whereNull('expires_at')
                            ->orWhere('expires_at', '>', now());
                    });
            })
            ->groupBy('poll_id')
            ->get();

        return response()->json([
            'active_votes' => $activePollVotes,
            'total_count' => $activePollVotes->sum('vote_count')
        ]);
    }

    public function userResponses(Poll $poll)
    {
        $user = request()->user();
        
        $responses = $poll->responses()
            ->where('user_id', $user->id)
            ->with('question')
            ->get()
            ->map(function ($response) {
                return [
                    'question_id' => $response->question->id,
                    'question' => $response->question->question,
                    'type' => $response->question->type,
                    'response_data' => $response->response_data
                ];
            });

        return response()->json([
            'poll_id' => $poll->id,
            'poll_title' => $poll->title,
            'user_responses' => $responses
        ]);
    }
} 