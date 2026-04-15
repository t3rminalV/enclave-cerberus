<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $user = $request->user();

        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'discord_username' => $user->discord_username,
                    'discord_id' => $user->discord_id,
                    'avatar_url' => $user->avatar_url,
                    'role' => $user->role,
                    'is_admin' => $user->is_admin,
                    'is_staff' => $user->isStaff(),
                ] : null,
            ],
            'app' => [
                'version' => $user?->isStaff() ? config('app.version') : null,
            ],
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
                'warnings' => $request->session()->get('warnings'),
            ],
        ]);
    }
}
