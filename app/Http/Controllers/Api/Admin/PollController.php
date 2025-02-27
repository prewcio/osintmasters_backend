<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Poll;
use App\Models\PollQuestion;
use App\Models\PollOption;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Message;
use App\Models\ChatMessage;
use App\Events\NewChatMessage;
use App\Models\User;

class PollController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', User::class);
        return Poll::with(['creator', 'questions.options'])->latest()->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'active' => 'required|boolean',
            'expires_at' => 'nullable|date|after:now',
            'is_system_post' => 'boolean',
            'questions' => 'required|array|min:1',
            'questions.*.question' => 'required|string|max:255',
            'questions.*.type' => 'required|in:single,multiple,text,scale',
            'questions.*.required' => 'boolean',
            'questions.*.options' => 'required_if:questions.*.type,single,multiple|array',
            'questions.*.options.*' => 'required|string|max:255',
            'questions.*.scale_config' => 'required_if:questions.*.type,scale|array',
            'questions.*.scale_config.min' => 'required_if:questions.*.type,scale|numeric',
            'questions.*.scale_config.max' => 'required_if:questions.*.type,scale|numeric|gt:questions.*.scale_config.min',
            'questions.*.scale_config.step' => 'required_if:questions.*.type,scale|numeric|gt:0'
        ]);

        try {
            DB::beginTransaction();

            $poll = Poll::create([
                'title' => $validated['title'],
                'description' => $validated['description'],
                'is_active' => $validated['active'],
                'expires_at' => $validated['expires_at'] ?? null,
                'is_system_post' => $validated['is_system_post'] ?? false,
                'created_by' => $request->user()->id
            ]);

            foreach ($validated['questions'] as $index => $questionData) {
                $poll->questions()->create([
                    'question' => $questionData['question'],
                    'type' => $questionData['type'],
                    'options' => in_array($questionData['type'], ['single', 'multiple']) ? $questionData['options'] : null,
                    'scale_config' => $questionData['type'] === 'scale' ? $questionData['scale_config'] : null,
                    'question_order' => $index,
                    'required' => $questionData['required'] ?? true
                ]);
            }

            DB::commit();

            return response()->json(
                $poll->load(['questions', 'creator']),
                201
            );

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function show(Poll $poll)
    {
        $poll->load('questions');

        $questions = $poll->questions->map(function ($question) {
            $data = [
                'type' => $question->type,
                'question' => $question->question,
            ];

            if (in_array($question->type, ['single', 'multiple'])) {
                $data['options'] = $question->options;
                if ($question->type === 'multiple') {
                    $data['maxChoices'] = count($question->options);
                }
            } elseif ($question->type === 'scale') {
                $data['min'] = $question->scale_config['min'];
                $data['max'] = $question->scale_config['max'];
                $data['step'] = $question->scale_config['step'];
                if (isset($question->scale_config['labels'])) {
                    $data['labels'] = $question->scale_config['labels'];
                }
            } elseif ($question->type === 'text') {
                $data['maxLength'] = $question->text_config['maxLength'] ?? null;
                $data['placeholder'] = $question->text_config['placeholder'] ?? null;
            }

            return $data;
        });

        return response()->json([
            'id' => $poll->id,
            'title' => $poll->title,
            'description' => $poll->description,
            'questions' => $questions,
            'active' => $poll->is_active,
            'created_at' => $poll->created_at,
            'expires_at' => $poll->expires_at,
            'is_system_post' => $poll->is_system_post
        ]);
    }

    public function update(Request $request, Poll $poll)
    {
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'active' => 'sometimes|boolean',
            'expires_at' => 'nullable|date|after:now',
            'is_system_post' => 'sometimes|boolean',
            'questions' => 'sometimes|array|min:1',
            'questions.*.question' => 'required|string|max:255',
            'questions.*.type' => 'required|in:single,multiple,text,scale',
            'questions.*.required' => 'boolean',
            'questions.*.options' => 'required_if:questions.*.type,single,multiple|array',
            'questions.*.options.*' => 'required|string|max:255',
            'questions.*.scale_config' => 'required_if:questions.*.type,scale|array',
            'questions.*.scale_config.min' => 'required_if:questions.*.type,scale|numeric',
            'questions.*.scale_config.max' => 'required_if:questions.*.type,scale|numeric|gt:questions.*.scale_config.min',
            'questions.*.scale_config.step' => 'required_if:questions.*.type,scale|numeric|gt:0'
        ]);

        try {
            DB::beginTransaction();

            $poll->update([
                'title' => $validated['title'] ?? $poll->title,
                'description' => $validated['description'] ?? $poll->description,
                'is_active' => $validated['active'] ?? $poll->is_active,
                'expires_at' => $validated['expires_at'] ?? $poll->expires_at,
                'is_system_post' => $validated['is_system_post'] ?? $poll->is_system_post
            ]);

            if (isset($validated['questions'])) {
                // Delete existing questions and their responses
                $poll->questions()->delete();

                // Create new questions
                foreach ($validated['questions'] as $index => $questionData) {
                    $poll->questions()->create([
                        'question' => $questionData['question'],
                        'type' => $questionData['type'],
                        'options' => in_array($questionData['type'], ['single', 'multiple']) ? $questionData['options'] : null,
                        'scale_config' => $questionData['type'] === 'scale' ? $questionData['scale_config'] : null,
                        'question_order' => $index,
                        'required' => $questionData['required'] ?? true
                    ]);
                }
            }

            DB::commit();

            return response()->json($poll->load(['questions', 'creator']));

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function destroy(Poll $poll)
    {
        $poll->delete();
        return response()->json(null, 204);
    }

    public function results(Poll $poll)
    {
        $results = $poll->questions->map(function ($question) {
            return [
                'question' => $question->question,
                'type' => $question->type,
                ...$question->getResults()
            ];
        });

        return response()->json([
            'id' => $poll->id,
            'results' => $results,
            'total_votes' => $poll->total_votes
        ]);
    }

    public function toggleActive(Poll $poll)
    {
        $poll->update([
            'is_active' => !$poll->is_active
        ]);

        return response()->json([
            'message' => 'Poll status updated successfully',
            'is_active' => $poll->is_active
        ]);
    }

    public function handleMessages(Request $request)
    {
        \Log::info('Request Method: ' . $request->method());
        // Validate the incoming request
        $validated = $request->validate([
            'content' => 'required|string|max:255',
        ]);

        // Create a new chat message in the database
        $message = ChatMessage::create([
            'content' => $validated['content'],
            'user_id' => $request->user()->id, // Assuming you have user authentication
        ]);

        // Broadcast the new message to all users
        broadcast(new NewChatMessage($message))->toOthers();

        // Return a response
        return response()->json(['message' => 'Message received!', 'data' => $message], 201);
    }

    public function getMessages()
    {
        // Retrieve all chat messages with user information
        $messages = ChatMessage::with('user')->latest()->get();

        return response()->json($messages);
    }
}