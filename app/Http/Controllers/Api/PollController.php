<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Poll;
use App\Models\PollQuestion;
use App\Models\PollResponse;
use App\Models\User;
use App\Models\ChatMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PollController extends Controller
{
    public function index()
    {
        return Poll::with(['creator', 'questions.options'])->latest()->paginate(10);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'questions' => 'required|array|min:1',
            'questions.*.question' => 'required|string|max:255',
            'questions.*.type' => 'required|in:text,multiple_choice',
            'questions.*.options' => 'required_if:questions.*.type,multiple_choice|array|min:2',
            'questions.*.options.*' => 'required_if:questions.*.type,multiple_choice|string|max:255',
        ]);

        $poll = DB::transaction(function () use ($validated, $request) {
            $poll = Poll::create([
                'title' => $validated['title'],
                'created_by' => $request->user()->id,
                'is_active' => true,
            ]);

            foreach ($validated['questions'] as $questionData) {
                $question = $poll->questions()->create([
                    'question' => $questionData['question'],
                    'type' => $questionData['type'],
                ]);

                if ($questionData['type'] === 'multiple_choice') {
                    foreach ($questionData['options'] as $optionText) {
                        $question->options()->create(['option_text' => $optionText]);
                    }
                }
            }

            return $poll;
        });

        return response()->json($poll->load(['creator', 'questions.options']), 201);
    }

    public function show(Poll $poll)
    {
        return response()->json($poll->load(['creator', 'questions.options']));
    }

    public function update(Request $request, Poll $poll)
    {
        $this->authorize('update', $poll);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'is_active' => 'required|boolean',
            'questions' => 'required|array|min:1',
            'questions.*.id' => 'sometimes|exists:poll_questions,id',
            'questions.*.question' => 'required|string|max:255',
            'questions.*.type' => 'required|in:text,multiple_choice',
            'questions.*.options' => 'required_if:questions.*.type,multiple_choice|array|min:2',
            'questions.*.options.*' => 'required_if:questions.*.type,multiple_choice|string|max:255',
        ]);

        $poll = DB::transaction(function () use ($validated, $poll) {
            $poll->update([
                'title' => $validated['title'],
                'is_active' => $validated['is_active'],
            ]);

            // Delete existing questions and options
            $poll->questions()->delete();

            foreach ($validated['questions'] as $questionData) {
                $question = $poll->questions()->create([
                    'question' => $questionData['question'],
                    'type' => $questionData['type'],
                ]);

                if ($questionData['type'] === 'multiple_choice') {
                    foreach ($questionData['options'] as $optionText) {
                        $question->options()->create(['option_text' => $optionText]);
                    }
                }
            }

            return $poll;
        });

        return response()->json($poll->load(['creator', 'questions.options']));
    }

    public function destroy(Poll $poll)
    {
        $this->authorize('delete', $poll);
        
        $poll->delete();

        return response()->json(null, 204);
    }

    public function respond(Request $request, Poll $poll)
    {
        if (!$poll->is_active) {
            return response()->json(['message' => 'This poll is no longer active.'], 403);
        }

        $validated = $request->validate([
            'responses' => 'required|array|min:1',
            'responses.*.question_id' => 'required|exists:poll_questions,id',
            'responses.*.response' => 'required',
        ]);

        DB::transaction(function () use ($validated, $request) {
            foreach ($validated['responses'] as $response) {
                $question = PollQuestion::findOrFail($response['question_id']);
                
                // Delete any existing responses by this user for this question
                PollResponse::where('poll_question_id', $question->id)
                    ->where('user_id', $request->user()->id)
                    ->delete();

                if ($question->type === 'text') {
                    PollResponse::create([
                        'poll_question_id' => $question->id,
                        'user_id' => $request->user()->id,
                        'response_text' => $response['response'],
                    ]);
                } else {
                    PollResponse::create([
                        'poll_question_id' => $question->id,
                        'user_id' => $request->user()->id,
                        'poll_option_id' => $response['response'],
                    ]);
                }
            }
        });

        return response()->json(['message' => 'Responses recorded successfully.']);
    }

    public function results(Poll $poll)
    {
        $this->authorize('viewAny', User::class);

        $poll->load(['questions.options', 'questions.responses']);

        $results = $poll->questions->map(function ($question) {
            $data = [
                'id' => $question->id,
                'question' => $question->question,
                'type' => $question->type,
                'total_responses' => $question->responses->count(),
            ];

            if ($question->type === 'multiple_choice') {
                $data['options'] = $question->options->map(function ($option) use ($question) {
                    return [
                        'option_text' => $option->option_text,
                        'count' => $question->responses->where('poll_option_id', $option->id)->count(),
                    ];
                });
            } else {
                $data['responses'] = $question->responses->pluck('response_text');
            }

            return $data;
        });

        return response()->json([
            'id' => $poll->id,
            'title' => $poll->title,
            'is_active' => $poll->is_active,
            'total_participants' => PollResponse::where('poll_question_id', $poll->questions->pluck('id')->first())->distinct('user_id')->count(),
            'questions' => $results,
        ]);
    }

    public function getMessages()
    {
        // Retrieve all chat messages with user information
        $messages = ChatMessage::with('user')->latest()->get();

        return response()->json($messages);
    }
} 