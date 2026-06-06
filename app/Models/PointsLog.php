<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Guarded;

#[Guarded([])]
class PointsLog extends Model
{
    protected $keyType    = 'string';
    public    $incrementing = false;

    // The table only has created_at, no updated_at
    const UPDATED_AT = null;

    /**
     * Relationship: the user who earned/lost the points.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
