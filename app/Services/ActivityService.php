<?php

namespace App\Services;

use App\Models\Activity;

class ActivityService
{
    public function log($userId, $action, $subject = null, $details = null)
    {
        return Activity::create([
            'user_id' => $userId,
            'action' => $action,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id' => $subject ? $subject->id : null,
            'details' => $details,
            'ip_address' => request()->ip(),
        ]);
    }
}