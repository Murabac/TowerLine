<?php

namespace App\Support;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Audits
{
    public static function log(string $action, Model $model, array $changes = []): void
    {
        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'model_type' => $model::class,
            'model_id' => $model->getKey(),
            'changes' => $changes ?: null,
            'created_at' => now(),
        ]);
    }
}
