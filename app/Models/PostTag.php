<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;



#[Guarded([""])]
#[Table("post_tags")]
class PostTag extends Model
{
    public $timestamps = false;
    protected $keyType = 'string';
    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($postTag) {
            if (!$postTag->id) {
                $postTag->id = (string) Str::uuid();
            }
        });
    }
}
