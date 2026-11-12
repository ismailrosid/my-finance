<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

class AuthController extends Controller
{
    /**
     * Show login form.
     */
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    /**
     * Process login authentication.
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $user = Auth::user();

            if (!$user->is_active) {
                Auth::logout();
                return back()->withErrors([
                    'email' => 'Your account has been deactivated. Please contact an administrator.',
                ]);
            }

            $request->session()->regenerate();

            AuditLog::record($user->id, 'LOGIN', 'users', $user->id, null, [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return redirect()->intended(route('dashboard'));
        }

        return back()->withInput($request->only('email', 'remember'))->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ]);
    }

    /**
     * Show registration form.
     */
    public function showRegister(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.register');
    }

    /**
     * Process new user registration.
     * Enforces default role 'user' strictly on backend.
     */
    public function register(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'email:filter,rfc', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        try {
            DB::transaction(function () use ($validated, $request) {
                // 1. Fetch default 'user' role safely from md_role
                $defaultRole = Role::where('role_code', 'user')
                    ->where('is_active', true)
                    ->first();

                if (!$defaultRole) {
                    throw new \RuntimeException('Default User role is not available in the system.');
                }

                // 2. Create new user with hashed password (no role mass-assignment allowed)
                $user = User::create([
                    'name'      => trim($validated['name']),
                    'email'     => strtolower(trim($validated['email'])),
                    'password'  => Hash::make($validated['password']),
                    'is_active' => true,
                ]);

                // 3. Assign strictly default role 'user' through user_roles pivot
                UserRole::create([
                    'user_id' => $user->id,
                    'role_id' => $defaultRole->id,
                ]);

                // 4. Record audit log
                AuditLog::record($user->id, 'REGISTER', 'users', $user->id, null, [
                    'ip'         => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'role'       => 'user',
                ]);
            });
        } catch (\RuntimeException $e) {
            return back()
                ->withInput($request->except(['password', 'password_confirmation']))
                ->withErrors(['general' => $e->getMessage()]);
        } catch (\Throwable $e) {
            return back()
                ->withInput($request->except(['password', 'password_confirmation']))
                ->withErrors(['general' => 'Registration failed due to an unexpected system error. Please try again.']);
        }

        return redirect()->route('login')->with('success', 'Account created successfully! You can now sign in.');
    }

    /**
     * Show forgot password request form.
     */
    public function showForgotPassword(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.forgot-password');
    }

    /**
     * Send password reset link to user email via Laravel Password broker.
     */
    public function forgotPassword(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('status', __($status));
        }

        return back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => __($status)]);
    }

    /**
     * Show password reset form with token.
     */
    public function showResetPassword(Request $request, string $token): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email', old('email', '')),
        ]);
    }

    /**
     * Reset the user password via Laravel Password broker.
     */
    public function resetPassword(Request $request): RedirectResponse
    {
        $request->validate([
            'token'    => ['required'],
            'email'    => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, string $password) use ($request) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));

                AuditLog::record($user->id, 'PASSWORD_RESET', 'users', $user->id, null, [
                    'ip'         => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('success', __($status));
        }

        return back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => __($status)]);
    }

    /**
     * Process logout.
     */
    public function logout(Request $request): RedirectResponse
    {
        $userId = Auth::id();

        if ($userId) {
            AuditLog::record($userId, 'LOGOUT', 'users', $userId, null, [
                'ip'         => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'You have been signed out successfully.');
    }
}
