<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;


#[Guarded([""])]
class PostEditHistory extends Model
{
    public $timestamps = false;
    protected $keyType = 'string';
    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($history) {
            if (!$history->id) {
                $history->id = (string) Str::uuid();
            }
        });
    }

    public function user() {
        return $this->belongsTo(User::class, "edited_by");
    }

    public function post() {
        return $this->belongsTo(Post::class, "post_id");
    }

}
