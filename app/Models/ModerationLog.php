<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

#[Guarded([])]
class ModerationLog extends Model
{
    public $timestamps = true;
    protected $keyType = 'string';
    public $incrementing = false;

    public function moderator()
    {
        return $this->belongsTo(User::class, 'moderator_id');
    }

    public function post()
    {
        return $this->belongsTo(Post::class, 'post_id');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($moderationLog) {
            if (!$moderationLog->id) {
                $moderationLog->id = (string) Str::uuid();
            }
        });
    }
}
