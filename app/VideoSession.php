<?php

namespace App;

use App\Traits\Tenantable;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class VideoSession extends Model
{
    use Tenantable;

    protected $fillable = [
        'user_id',
        'course_id',
        'lesson_id',
        'token',
        'expires_at',
        'used_at',
        'is_active',
        'last_position_seconds',
        'duration_seconds',
        'percent_watched',
        'client_identifier',
    ];

    protected $dates = [
        'expires_at',
        'used_at',
    ];

    /**
     * Determine if the session is currently valid.
     *
     * @return bool
     */
    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->expires_at instanceof Carbon && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }
}

