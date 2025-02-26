<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class News extends Model
{
    use HasFactory;

    protected $fillable = [
        'content',
        'author',
        'is_system_post',
    ];

    protected $casts = [
        'is_system_post' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'author');
    }
} 