<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', AuditLog::class);

        $query = AuditLog::query()->with('user')->latest('created_at');

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->integer('user_id'));
        }

        if ($request->filled('action')) {
            $query->where('action', $request->string('action'));
        }

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->date('from'));
        }

        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->date('to'));
        }

        return view('audit-logs.index', [
            'logs' => $query->paginate(20)->withQueryString(),
            'users' => User::query()->orderBy('name')->get(['id', 'name']),
            'actions' => ['created', 'updated', 'deleted'],
            'counts' => [
                'created' => AuditLog::query()->where('action', 'created')->count(),
                'updated' => AuditLog::query()->where('action', 'updated')->count(),
                'deleted' => AuditLog::query()->where('action', 'deleted')->count(),
            ],
        ]);
    }
}
