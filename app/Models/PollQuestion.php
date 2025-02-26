<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PollQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'poll_id',
        'question',
        'type',
        'options',
        'scale_config',
        'question_order',
        'required'
    ];

    protected $casts = [
        'options' => 'array',
        'scale_config' => 'array',
        'required' => 'boolean'
    ];

    public function poll()
    {
        return $this->belongsTo(Poll::class);
    }

    public function options()
    {
        return $this->hasMany(PollOption::class);
    }

    public function responses()
    {
        return $this->hasMany(PollResponse::class, 'question_id');
    }

    public function getResults()
    {
        $responses = $this->responses;
        $totalResponses = $responses->count();

        switch ($this->type) {
            case 'single':
            case 'multiple':
                $results = [];
                foreach ($responses as $response) {
                    $data = $response->response_data;
                    if ($this->type === 'single') {
                        $results[$data['selected_option']] = ($results[$data['selected_option']] ?? 0) + 1;
                    } else {
                        foreach ($data['selected_options'] as $option) {
                            $results[$option] = ($results[$option] ?? 0) + 1;
                        }
                    }
                }
                return [
                    'type' => $this->type,
                    'options' => $this->options,
                    'results' => $results,
                    'total_responses' => $totalResponses
                ];

            case 'text':
                return [
                    'type' => 'text',
                    'responses' => $responses->map(function($response) {
                        return $response->response_data['text'];
                    }),
                    'total_responses' => $totalResponses
                ];

            case 'scale':
                $distribution = [];
                $sum = 0;
                foreach ($responses as $response) {
                    $value = $response->response_data['value'];
                    $distribution[$value] = ($distribution[$value] ?? 0) + 1;
                    $sum += $value;
                }
                return [
                    'type' => 'scale',
                    'scale_config' => $this->scale_config,
                    'distribution' => $distribution,
                    'average' => $totalResponses > 0 ? $sum / $totalResponses : 0,
                    'total_responses' => $totalResponses
                ];
        }
    }
} 