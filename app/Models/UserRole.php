<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;


#[Guarded([""])]
class UserRole extends Model
{
    public $timestamps = false;
        protected $keyType = 'string';
    public $incrementing = false;
    
     protected static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            if (!$user->id) {
                $user->id = Str::uuid()->toString();
            }
        });
    }
}
