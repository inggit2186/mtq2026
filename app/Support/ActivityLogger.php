<?php

namespace App\Support;

use App\Models\ApplicationLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class ActivityLogger
{
    public static function log(
        string $action,
        string $description,
        ?Model $subject = null,
        array $properties = [],
        ?Request $request = null,
    ): ?ApplicationLog {
        try {
            if (! Schema::hasTable('application_logs')) {
                return null;
            }

            $user = Auth::user();
            $request ??= app()->bound('request') ? request() : null;

            return ApplicationLog::query()->create([
                'user_id' => $user?->id,
                'user_name' => $user?->name,
                'user_role' => $user?->role,
                'action' => Str::limit($action, 120, ''),
                'description' => $description,
                'subject_type' => $subject?->getMorphClass(),
                'subject_id' => $subject?->getKey(),
                'subject_name' => $subject ? self::subjectName($subject) : null,
                'ip_address' => $request?->ip(),
                'user_agent' => Str::limit((string) ($request?->userAgent() ?? ''), 512, ''),
                'properties' => $properties !== [] ? $properties : null,
            ]);
        } catch (Throwable) {
            return null;
        }
    }

    protected static function subjectName(Model $subject): string
    {
        $name = trim((string) ($subject->getAttribute('name') ?? $subject->getAttribute('title') ?? $subject->getAttribute('caption') ?? ''));
        $registrationNumber = trim((string) ($subject->getAttribute('registration_number') ?? ''));
        $maqraCode = trim((string) ($subject->getAttribute('maqra_code') ?? ''));
        $email = trim((string) ($subject->getAttribute('email') ?? ''));

        if ($name !== '' && $registrationNumber !== '') {
            return $name.' ('.$registrationNumber.')';
        }

        if ($name !== '') {
            return $name;
        }

        if ($registrationNumber !== '') {
            return $registrationNumber;
        }

        if ($maqraCode !== '') {
            return $maqraCode;
        }

        if ($email !== '') {
            return $email;
        }

        return class_basename($subject).' #'.$subject->getKey();
    }
}
