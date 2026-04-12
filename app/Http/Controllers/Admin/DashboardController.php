<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\AuditLog;
use App\Models\Event;
use App\Models\User;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_volunteers' => User::where('role', 'volunteer')->count(),
            'total_events' => Event::count(),
            'active_events' => Event::whereIn('status', ['open', 'closed'])->count(),
            'pending_applications' => Application::whereIn('status', ['submitted', 'under_review'])->count(),
        ];

        $recentEvents = Event::withCount(['applications'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        $recentApplications = Application::with(['user', 'event'])
            ->whereIn('status', ['submitted', 'under_review'])
            ->orderByDesc('submitted_at')
            ->limit(10)
            ->get();

        $recentAuditLogs = AuditLog::with('user')
            ->orderByDesc('created_at')
            ->limit(15)
            ->get();

        return Inertia::render('Admin/Dashboard', [
            'stats' => $stats,
            'recentEvents' => $recentEvents,
            'recentApplications' => $recentApplications,
            'recentAuditLogs' => $recentAuditLogs,
        ]);
    }
}
