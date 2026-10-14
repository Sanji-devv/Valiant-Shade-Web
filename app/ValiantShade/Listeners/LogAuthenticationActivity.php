<?php

namespace App\ValiantShade\Listeners;

use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;

class LogAuthenticationActivity
{
    public function handleLogin(Login $event): void
    {
        activity('auth')
            ->causedBy($event->user)
            ->performedOn($event->user)
            ->event('auth.login')
            ->withProperties([
                'guard' => $event->guard,
                'ip' => request()?->ip(),
                'user_agent' => substr((string) (request()?->userAgent() ?? ''), 0, 255),
            ])
            ->log(__('User signed in.'));
    }

    public function handleLogout(Logout $event): void
    {
        if (! $event->user) {
            return;
        }

        activity('auth')
            ->causedBy($event->user)
            ->performedOn($event->user)
            ->event('auth.logout')
            ->withProperties([
                'guard' => $event->guard,
                'ip' => request()?->ip(),
            ])
            ->log(__('User signed out.'));
    }

    public function handleFailed(Failed $event): void
    {
        activity('auth')
            ->event('auth.failed')
            ->withProperties([
                'guard' => $event->guard,
                'email' => $event->credentials['email'] ?? null,
                'ip' => request()?->ip(),
                'user_agent' => substr((string) (request()?->userAgent() ?? ''), 0, 255),
            ])
            ->log(__('Failed sign-in attempt.'));
    }
}
