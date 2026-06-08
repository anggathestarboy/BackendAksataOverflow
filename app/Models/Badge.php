<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;


#[Guarded([])]
class Badge extends Model
{
    public $timestamps = false;
    protected $keyType = 'string';
    public $incrementing = false;

   

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($badge) {
            if (!$badge->id) {
                $badge->id = (string) Str::uuid();
            }
        });
    }

    /**
     * Get users who have this badge
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_badges')
            ->withTimestamps();
    }
}
