<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;


#[Guarded([""])]
class Bookmark extends Model
{
    public $timestamps = false;
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

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($bookmark) {
            if (!$bookmark->id) {
                $bookmark->id = (string) Str::uuid();
            }
        });
    }
}
