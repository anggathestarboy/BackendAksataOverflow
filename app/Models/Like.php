<?php

namespace App\Models;

use App\Models\Comment;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;


#[Guarded([""])]

class Like extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;



  




     public function user()
    {
        return $this->belongsTo(User::class);
    }


    public function post()
    {
        return $this->belongsTo(Post::class, 'target_id');
    }

    public function comment()
    {
        return $this->belongsTo(Comment::class, 'target_id');
    }
   

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($like) {
            if (!$like->id) {
                $like->id = (string) Str::uuid();
            }
        });
    }
}
