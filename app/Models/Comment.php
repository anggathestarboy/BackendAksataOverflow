<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;


#[Guarded([""])]
class Comment extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;

    public function post()
    {
        return $this->belongsTo(Post::class, "post_id");
    }

    public function user()
    {
        return $this->belongsTo(User::class, "user_id");
    }

    public function replies()
    {
        return $this->hasMany(Comment::class, "parent_id");
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($comment) {
            if (!$comment->id) {
                $comment->id = (string) Str::uuid();
            }
        });
    }
}
