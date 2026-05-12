<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\TicketTailorService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SettingsController extends Controller
{
    public function index(TicketTailorService $tickets)
    {
        return Inertia::render('Admin/Settings/Index', [
            'users' => User::orderBy('name')->get()->map(fn(User $u) => [
                'id'               => $u->id,
                'name'             => $u->name,
                'discord_username' => $u->discord_username,
                'avatar_url'       => $u->avatar_url,
                'role'             => $u->role,
                'email'            => $u->email,
                'created_at'       => $u->created_at,
            ]),
            'settings' => AppSetting::allWithDefaults(),
            'integrations' => [
                'tickettailor' => [
                    'configured' => $tickets->isConfigured(),
                ],
            ],
        ]);
    }

    public function updateUserRole(Request $request, User $user)
    {
        $validated = $request->validate([
            'role' => 'required|in:volunteer,organiser,admin',
        ]);

        if ($user->id === auth()->id() && $validated['role'] !== 'admin') {
            return back()->withErrors(['role' => 'You cannot remove your own admin role.']);
        }

        $old = $user->role;
        $user->update(['role' => $validated['role']]);

        AuditLog::record('user.role_changed', $user, ['role' => $old], ['role' => $validated['role']]);

        return back()->with('success', "{$user->name}'s role updated to {$validated['role']}.");
    }

    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'settings'       => 'required|array',
            'settings.*.key'   => 'required|string',
            'settings.*.value' => 'present',
        ]);

        $old = AppSetting::allWithDefaults();

        foreach ($validated['settings'] as $item) {
            AppSetting::set($item['key'], $item['value']);
        }

        AuditLog::record('settings.updated', null, ['settings' => $old], ['settings' => $validated['settings']]);

        return back()->with('success', 'Settings saved.');
    }
}
