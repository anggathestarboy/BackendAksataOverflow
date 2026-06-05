<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;


#[Guarded([""])]
class Vote extends Model
{
    public $timestamps = false;
    protected $keyType = 'string';
    public $incrementing = false;


    protected static function boot()
    {
        parent::boot();

        static::creating(function ($vote) {
            if (!$vote->id) {
                $vote->id = (string) Str::uuid();
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class, "user_id");
    }

    public function post()
    {
        return $this->belongsTo(Post::class, "post_id");
    }

    
}
