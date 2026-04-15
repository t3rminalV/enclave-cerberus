<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class DiscordController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('discord')
            ->scopes(['identify', 'email'])
            ->redirect();
    }

    public function callback()
    {
        try {
            $discordUser = Socialite::driver('discord')->user();
        } catch (\Throwable $e) {
            return redirect()->route('login')->withErrors(['discord' => 'Discord authentication failed. Please try again.']);
        }

        $user = User::updateOrCreate(
            ['discord_id' => $discordUser->getId()],
            [
                'name' => $discordUser->getName() ?? $discordUser->getNickname(),
                'email' => $discordUser->getEmail(),
                'discord_username' => $discordUser->getNickname(),
                'discord_avatar' => $discordUser->getAvatar() ? $this->extractAvatarHash($discordUser->getAvatar()) : null,
            ]
        );

        Auth::login($user, remember: true);

        AuditLog::record('auth.logged_in', $user, [], [
            'new_user' => $user->wasRecentlyCreated,
        ], $user);

        return redirect()->intended('/');
    }

    public function logout()
    {
        $user = auth()->user();

        if ($user) {
            AuditLog::record('auth.logged_out', $user, [], [], $user);
        }

        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
        return redirect()->route('login');
    }

    private function extractAvatarHash(string $avatarUrl): string
    {
        // Discord avatar URLs contain the hash; extract it
        preg_match('/avatars\/\d+\/([a-f0-9_]+)/', $avatarUrl, $matches);
        return $matches[1] ?? $avatarUrl;
    }
}
