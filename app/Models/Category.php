<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;


#[Guarded([""])]
class Category extends Model
{
    public $timestamps = false;
    protected $keyType = 'string';
    public $incrementing = false;



    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }



    protected static function boot()
    {
        parent::boot();

        static::creating(function ($category) {
            if (!$category->id) {
                $category->id = Str::uuid()->toString();
            }
        });
    }
}
