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
        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'discord_username' => $request->user()->discord_username,
                    'discord_id' => $request->user()->discord_id,
                    'avatar_url' => $request->user()->avatar_url,
                    'role' => $request->user()->role,
                    'is_admin' => $request->user()->is_admin,
                    'is_staff' => $request->user()->isStaff(),
                ] : null,
            ],
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
                'warnings' => $request->session()->get('warnings'),
            ],
        ]);
    }
}
