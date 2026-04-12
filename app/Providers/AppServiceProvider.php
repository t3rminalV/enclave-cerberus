<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Socialite\Contracts\Factory as SocialiteFactory;
use SocialiteProviders\Discord\Provider as DiscordProvider;
use SocialiteProviders\Manager\SocialiteWasCalled;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Register Discord Socialite provider
        $socialite = $this->app->make(SocialiteFactory::class);
        $socialite->extend('discord', function () use ($socialite) {
            $config = config('services.discord');
            return $socialite->buildProvider(DiscordProvider::class, $config);
        });
    }
}
