<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;


#[Guarded([""])]
class CommentEditHistory extends Model
{
    public $timestamps = false;
    protected $keyType = 'string';
    public $incrementing = false;


    public function user() {
        return $this->belongsTo(User::class, "edited_by");
    }

    public function comment() {
        return $this->belongsTo(Comment::class);
    }

        protected static function boot()
        {
            parent::boot();
    
            static::creating(function ($history) {
                if (!$history->id) {
                    $history->id = (string) Str::uuid();
                }
            });
        }
}
