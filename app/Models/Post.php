<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;


#[Guarded([""])]
class Post extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;


    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'post_tags', 'post_id', 'tag_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, "category_id");
    }

    public function user()
    {
        return $this->belongsTo(User::class, "user_id");
    }


    public function comments()
    {
        return $this->hasMany(Comment::class, "post_id");
    }


    public function likes() {
        return $this->hasMany(Like::class, "target_id")->where('target_type', 'post');
    }
    
    public function votes()
    {
        return $this->hasMany(Vote::class, "target_id")->where('target_type', 'post');
    }

    public function bookmarks()
    {
        return $this->hasMany(Bookmark::class, "post_id");
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($post) {
            if (!$post->id) {
                $post->id = (string) \Illuminate\Support\Str::uuid();
            }
        });
    }

    

    

    

}
