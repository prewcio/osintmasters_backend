<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Poll extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'is_active',
        'is_system_post',
        'expires_at',
        'created_by'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_system_post' => 'boolean',
        'expires_at' => 'datetime'
    ];

    protected $with = ['questions'];

    public function questions()
    {
        return $this->hasMany(PollQuestion::class)->orderBy('question_order');
    }

    public function responses()
    {
        return $this->hasMany(PollResponse::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    public function getTotalVotesAttribute()
    {
        return PollResponse::where('poll_id', $this->id)->count();
    }
} 