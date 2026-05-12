<?php

use App\Http\Controllers\Auth\DiscordController;
use App\Http\Controllers\CalendarFeedController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin;
use App\Http\Controllers\Volunteer;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Calendar feed (public, token-gated)
Route::get('/cal/{token}.ics', [CalendarFeedController::class, 'show'])
    ->where('token', '[a-f0-9]{48}')
    ->middleware('throttle:60,1')
    ->name('calendar.feed');

// Auth
Route::get('/login', fn() => Inertia::render('Auth/Login'))->name('login')->middleware('guest');
Route::get('/auth/discord', [DiscordController::class, 'redirect'])->name('auth.discord');
Route::get('/auth/discord/callback', [DiscordController::class, 'callback'])->name('auth.discord.callback');
Route::post('/logout', [DiscordController::class, 'logout'])->name('logout')->middleware('auth');

// Authenticated routes
Route::middleware(['auth'])->group(function () {

    // Root redirect
    Route::get('/', function () {
        if (auth()->user()->isStaff()) {
            return redirect()->route('admin.dashboard');
        }
        return redirect()->route('volunteer.dashboard');
    });

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');

    // Volunteer routes
    Route::prefix('portal')->name('volunteer.')->group(function () {
        Route::get('/', [Volunteer\DashboardController::class, 'index'])->name('dashboard');

        // Applications
        Route::get('/events/{event}/apply', [Volunteer\ApplicationController::class, 'create'])->name('applications.create');
        Route::post('/events/{event}/apply', [Volunteer\ApplicationController::class, 'store'])->name('applications.store');
        Route::get('/applications/{application}', [Volunteer\ApplicationController::class, 'show'])->name('applications.show');
        Route::patch('/applications/{application}', [Volunteer\ApplicationController::class, 'update'])->name('applications.update');
        Route::post('/applications/{application}/submit', [Volunteer\ApplicationController::class, 'submit'])->name('applications.submit');
        Route::get('/applications/{application}/files/{file}/download', [Volunteer\ApplicationController::class, 'downloadFile'])->name('applications.files.download');

        // Rota
        Route::get('/events/{event}/rota', [Volunteer\RotaController::class, 'show'])->name('rota.show');

        // Documents
        Route::get('/events/{event}/documents', [Volunteer\DocumentController::class, 'index'])->name('documents.index');
        Route::get('/events/{event}/documents/{document}/download', [Volunteer\DocumentController::class, 'download'])->name('documents.download');

        // Feedback
        Route::get('/events/{event}/feedback', [Volunteer\FeedbackController::class, 'show'])->name('feedback.show');
        Route::post('/events/{event}/feedback', [Volunteer\FeedbackController::class, 'store'])->name('feedback.store');
    });

    // Staff routes — accessible to admin + organiser
    Route::prefix('admin')->name('admin.')->middleware('staff')->group(function () {
        Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');

        // Events
        Route::resource('events', Admin\EventController::class);

        // Form Builder
        Route::get('/events/{event}/forms/{stage}', [Admin\FormBuilderController::class, 'show'])->name('events.forms.show');
        Route::put('/events/{event}/forms/{stage}', [Admin\FormBuilderController::class, 'save'])->name('events.forms.save');
        Route::post('/events/{event}/forms/{stage}/clone', [Admin\FormBuilderController::class, 'clone'])->name('events.forms.clone');

        // Applications
        Route::get('/events/{event}/applications', [Admin\ApplicationController::class, 'index'])->name('events.applications.index');
        Route::get('/events/{event}/applications/{application}', [Admin\ApplicationController::class, 'show'])->name('events.applications.show');
        Route::patch('/events/{event}/applications/{application}/status', [Admin\ApplicationController::class, 'updateStatus'])->name('events.applications.status');
        Route::patch('/events/{event}/applications/{application}/notes', [Admin\ApplicationController::class, 'updateNotes'])->name('events.applications.notes');
        Route::post('/events/{event}/applications/{application}/tags', [Admin\ApplicationController::class, 'syncTags'])->name('events.applications.tags');
        Route::post('/events/{event}/applications/bulk-status', [Admin\ApplicationController::class, 'bulkUpdateStatus'])->name('events.applications.bulk-status');
        Route::get('/events/{event}/applications/export/csv', [Admin\ApplicationController::class, 'export'])->name('events.applications.export');
        Route::get('/events/{event}/applications/export/full-data/csv', [Admin\ApplicationController::class, 'exportFullData'])->middleware('admin')->name('events.applications.export-full');

        // Teams
        Route::get('/events/{event}/teams', [Admin\TeamController::class, 'index'])->name('events.teams.index');
        Route::post('/events/{event}/teams', [Admin\TeamController::class, 'store'])->name('events.teams.store');
        Route::patch('/events/{event}/teams/{team}', [Admin\TeamController::class, 'update'])->name('events.teams.update');
        Route::delete('/events/{event}/teams/{team}', [Admin\TeamController::class, 'destroy'])->name('events.teams.destroy');
        Route::post('/events/{event}/teams/{team}/members', [Admin\TeamController::class, 'syncMembers'])->name('events.teams.members');
        Route::post('/events/{event}/teams/reorder', [Admin\TeamController::class, 'reorder'])->name('events.teams.reorder');

        // Rota
        Route::get('/events/{event}/rota', [Admin\RotaController::class, 'index'])->name('events.rota.index');
        Route::post('/events/{event}/rota/rules', [Admin\RotaController::class, 'saveRules'])->name('events.rota.rules');
        Route::post('/events/{event}/rota/shifts', [Admin\RotaController::class, 'storeShift'])->name('events.rota.shifts.store');
        Route::patch('/events/{event}/rota/shifts/{shift}', [Admin\RotaController::class, 'updateShift'])->name('events.rota.shifts.update');
        Route::delete('/events/{event}/rota/shifts/{shift}', [Admin\RotaController::class, 'destroyShift'])->name('events.rota.shifts.destroy');
        Route::post('/events/{event}/rota/generate', [Admin\RotaController::class, 'generate'])->name('events.rota.generate');
        Route::post('/events/{event}/rota/assign', [Admin\RotaController::class, 'assignVolunteer'])->name('events.rota.assign');
        Route::delete('/events/{event}/rota/shifts/{shift}/volunteers/{userId}', [Admin\RotaController::class, 'removeAssignment'])->name('events.rota.unassign');
        Route::post('/events/{event}/rota/publish', [Admin\RotaController::class, 'publish'])->name('events.rota.publish');
        Route::get('/events/{event}/rota/export', [Admin\RotaController::class, 'exportCsv'])->name('events.rota.export');

        // Documents
        Route::get('/events/{event}/documents', [Admin\DocumentController::class, 'index'])->name('events.documents.index');
        Route::post('/events/{event}/documents', [Admin\DocumentController::class, 'store'])->name('events.documents.store');
        Route::patch('/events/{event}/documents/{document}', [Admin\DocumentController::class, 'update'])->name('events.documents.update');
        Route::get('/events/{event}/documents/{document}/download', [Admin\DocumentController::class, 'download'])->name('events.documents.download');
        Route::delete('/events/{event}/documents/{document}', [Admin\DocumentController::class, 'destroy'])->name('events.documents.destroy');

        // Notifications
        Route::get('/events/{event}/notifications', [Admin\NotificationController::class, 'index'])->name('events.notifications.index');
        Route::post('/events/{event}/notifications/bulk', [Admin\NotificationController::class, 'sendBulk'])->name('events.notifications.bulk');
        Route::post('/message-templates', [Admin\NotificationController::class, 'storeTemplate'])->name('message-templates.store');
        Route::delete('/message-templates/{template}', [Admin\NotificationController::class, 'destroyTemplate'])->name('message-templates.destroy');

        // Audit Log
        Route::get('/audit-log', fn() => Inertia::render('Admin/AuditLog', [
            'logs' => \App\Models\AuditLog::with('user')->orderByDesc('created_at')->paginate(50),
        ]))->name('audit-log');

        Route::get('/changelog', [Admin\ChangelogController::class, 'index'])->name('changelog');

        // Tags
        Route::apiResource('tags', \App\Http\Controllers\Admin\TagController::class)->only(['index', 'store', 'destroy']);

        // Volunteers list
        Route::get('/volunteers', fn() => Inertia::render('Admin/Volunteers', [
            'volunteers' => \App\Models\User::where('role', 'volunteer')
                ->withCount('applications')
                ->orderBy('name')
                ->paginate(50),
        ]))->name('volunteers.index');

        // Settings — admin only (nested additional middleware)
        Route::middleware('admin')->group(function () {
            Route::get('/settings', [Admin\SettingsController::class, 'index'])->name('settings');
            Route::patch('/settings/users/{user}/role', [Admin\SettingsController::class, 'updateUserRole'])->name('settings.user-role');
            Route::post('/settings/app', [Admin\SettingsController::class, 'updateSettings'])->name('settings.app');
        });
    });
});
