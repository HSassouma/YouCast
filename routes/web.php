<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\Admin\ActivityController as AdminActivityController;
use App\Http\Controllers\Admin\ChallengeIdeaController as AdminChallengeIdeaController;
use App\Http\Controllers\Admin\ConversationController as AdminConversationController;
use App\Http\Controllers\ChallengeIdeaController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\TeamController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::view('/a-propos', 'about')->name('about');

Route::middleware(['auth'])->group(function () {
    // The catalogue and every activity detail are members-only: guests are
    // redirected to the login screen with the intended URL kept in session.
    Route::get('/activities', [ActivityController::class, 'index'])->name('activities.index');
    Route::get('/activities/{activity}', [ActivityController::class, 'show'])->name('activities.show');

    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->middleware('throttle:password-change')->name('profile.destroy');

    Route::post('/activities/{activity}/registrations', [RegistrationController::class, 'store'])
        ->middleware('throttle:registration')
        ->name('activities.registrations.store');
    Route::delete('/activities/{activity}/registrations', [RegistrationController::class, 'destroy'])
        ->middleware('throttle:registration')
        ->name('activities.registrations.destroy');

    Route::post('/activities/{activity}/teams', [TeamController::class, 'store'])
        ->middleware('throttle:teams')
        ->name('activities.teams.store');
    Route::post('/activities/{activity}/teams/join', [TeamController::class, 'join'])
        ->middleware('throttle:teams')
        ->name('activities.teams.join');
    Route::delete('/activities/{activity}/teams/{team}/leave', [TeamController::class, 'leave'])
        ->middleware('throttle:teams')
        ->name('activities.teams.leave');
    Route::delete('/activities/{activity}/teams/{team}', [TeamController::class, 'destroy'])
        ->middleware('throttle:teams')
        ->name('activities.teams.destroy');
    Route::post('/activities/{activity}/teams/{team}/register', [TeamController::class, 'register'])
        ->middleware('throttle:teams')
        ->name('activities.teams.register');
    Route::post('/activities/{activity}/teams/{team}/unregister', [TeamController::class, 'unregister'])
        ->middleware('throttle:teams')
        ->name('activities.teams.unregister');

    Route::get('/ideas', [ChallengeIdeaController::class, 'index'])->name('ideas.index');
    Route::get('/ideas/create', [ChallengeIdeaController::class, 'create'])->name('ideas.create');
    Route::post('/ideas', [ChallengeIdeaController::class, 'store'])
        ->middleware('throttle:ideas')
        ->name('ideas.store');

    Route::get('/messages', [ConversationController::class, 'index'])->name('messages.index');
    Route::get('/messages/create', [ConversationController::class, 'create'])->name('messages.create');
    Route::post('/messages', [ConversationController::class, 'store'])
        ->middleware('throttle:message-create')
        ->name('messages.store');
    Route::get('/messages/{conversation}', [ConversationController::class, 'show'])->name('messages.show');
    Route::post('/messages/{conversation}/replies', [MessageController::class, 'store'])
        ->middleware('throttle:message-reply')
        ->name('messages.replies.store');
});

Route::middleware(['auth', 'admin', 'throttle:admin-actions'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/activities', [AdminActivityController::class, 'index'])->name('activities.index');
        Route::get('/activities/create', [AdminActivityController::class, 'create'])->name('activities.create');
        Route::post('/activities', [AdminActivityController::class, 'store'])->name('activities.store');
        Route::get('/activities/{activity}/edit', [AdminActivityController::class, 'edit'])->name('activities.edit');
        Route::put('/activities/{activity}', [AdminActivityController::class, 'update'])->name('activities.update');
        Route::get('/activities/{activity}', [AdminActivityController::class, 'show'])->name('activities.show');
        Route::post('/activities/{activity}/publish', [AdminActivityController::class, 'publish'])->name('activities.publish');
        Route::post('/activities/{activity}/unpublish', [AdminActivityController::class, 'unpublish'])->name('activities.unpublish');
        Route::post('/activities/{activity}/close', [AdminActivityController::class, 'close'])->name('activities.close');
        Route::post('/activities/{activity}/cancel', [AdminActivityController::class, 'cancel'])->name('activities.cancel');
        Route::post('/activities/{activity}/archive', [AdminActivityController::class, 'archive'])->name('activities.archive');

        Route::get('/ideas', [AdminChallengeIdeaController::class, 'index'])->name('ideas.index');
        Route::get('/ideas/{idea}', [AdminChallengeIdeaController::class, 'show'])->name('ideas.show');
        Route::post('/ideas/{idea}/review', [AdminChallengeIdeaController::class, 'review'])->name('ideas.review');
        Route::post('/ideas/{idea}/convert', [AdminChallengeIdeaController::class, 'convert'])->name('ideas.convert');

        Route::get('/messages', [AdminConversationController::class, 'index'])->name('messages.index');
        Route::get('/messages/{conversation}', [AdminConversationController::class, 'show'])->name('messages.show');
        Route::post('/messages/{conversation}/close', [AdminConversationController::class, 'close'])->name('messages.close');
        Route::post('/messages/{conversation}/reopen', [AdminConversationController::class, 'reopen'])->name('messages.reopen');
    });
