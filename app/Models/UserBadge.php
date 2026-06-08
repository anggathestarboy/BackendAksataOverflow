<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;



#[Guarded([])]
class UserBadge extends Model
{
    public $timestamps = true;
    protected $keyType = 'string';
    public $incrementing = false;

    public function badge()
    {
        return $this->belongsTo(Badge::class, 'badge_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($userBadge) {
            if (!$userBadge->id) {
                $userBadge->id = (string) Str::uuid();
            }
        });
    }
}
