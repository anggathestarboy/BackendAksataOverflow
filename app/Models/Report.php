<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;


#[Guarded([""])]
class Report extends Model
{
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;
    public function reporter()
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function resolvedBy()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }



    public function post()
    {
        return $this->belongsTo(Post::class, 'target_id');
    }

    public function comment()
    {
        return $this->belongsTo(Comment::class, 'target_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'target_id');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($report) {
            if (!$report->id) {
                $report->id = (string) Str::uuid();
            }
        });
    }
}
