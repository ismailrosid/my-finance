<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

abstract class Controller
{
    /**
     * Get the active user ID, falling back to the first available user in local environment.
     */
    protected function getUserId(): int
    {
        if (Auth::check()) {
            return Auth::id();
        }

        // Fallback for demonstration / local testing
        $user = User::first();
        if ($user) {
            return $user->id;
        }

        return 1;
    }

    /**
     * Get the active user instance.
     */
    protected function getUser(): ?User
    {
        if (Auth::check()) {
            return Auth::user();
        }

        return User::first();
    }
}
