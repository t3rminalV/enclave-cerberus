# Cerberus — Volunteer Management Portal

## Stack
- **Laravel 13** (PHP 8.5), **Inertia.js + Vue 3 + TypeScript**, **Tailwind CSS v4**
- **PostgreSQL** (via Sail in dev, Laravel Cloud in prod)
- **Redis** for cache (Sail in dev)
- Standard database queue driver (no Horizon)
- Discord OAuth via `socialiteproviders/discord` + `laravel/socialite`
- Discord bot notifications via HTTP API (jobs in `app/Jobs/SendDiscordNotification.php`)

## Dev Setup
```bash
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate
npm run dev
```

## Making yourself an admin
1. Log in once via Discord SSO to create your user record
2. Add `ADMIN_DISCORD_ID=your_discord_id` to `.env`
3. Run: `./vendor/bin/sail artisan db:seed --class=AdminSeeder`

## Key env vars to set
```
DISCORD_CLIENT_ID=
DISCORD_CLIENT_SECRET=
DISCORD_REDIRECT_URI=http://localhost/auth/discord/callback
DISCORD_BOT_TOKEN=
```

## Architecture notes
- All pages under `resources/js/pages/` are Inertia page components
- Admin routes: `/admin/*` — requires `is_admin = true`
- Volunteer routes: `/portal/*` — requires auth
- CSS uses pure CSS in `@layer components` — Tailwind v4 does NOT support `@apply` of custom component classes
- `AppLayout.vue` handles both admin and volunteer navigation based on `auth.user.is_admin`
- Application stage 2 auto-unlocks when status transitions to `accepted` (see `Application::transitionTo()`)
- Rota generation in `app/Services/RotaGeneratorService.php` — respects team membership, shift duration rules, rest periods, max shifts per volunteer
- Discord notifications are queued jobs — run `./vendor/bin/sail artisan queue:work` to process them

## Conventions
- Controllers return `Inertia::render('Path/To/Page', [...])` — page path matches `resources/js/pages/`
- Flash messages via `with('success', ...)` / `with('error', ...)` — displayed by `FlashMessages.vue`
- Audit logging: `AuditLog::record('action.name', $model, $old, $new)` for all significant admin actions
- Form builder saves as JSON field config; rendered by `FormFieldInput.vue` (volunteer) and `FieldPreview.vue` (admin preview)
