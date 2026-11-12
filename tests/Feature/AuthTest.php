<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected Role $userRole;
    protected Role $adminRole;
    protected Role $superAdminRole;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles into md_role for testing
        $this->superAdminRole = Role::create([
            'role_code'   => 'super_admin',
            'role_name'   => 'Super Admin',
            'description' => 'Super Administrator',
            'is_active'   => true,
        ]);

        $this->adminRole = Role::create([
            'role_code'   => 'admin',
            'role_name'   => 'Admin',
            'description' => 'Administrator',
            'is_active'   => true,
        ]);

        $this->userRole = Role::create([
            'role_code'   => 'user',
            'role_name'   => 'User',
            'description' => 'Standard User',
            'is_active'   => true,
        ]);
    }

    // ==========================================
    // 1. REGISTRATION TESTS
    // ==========================================

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get(route('register'));

        $response->assertStatus(200);
        $response->assertSee('Create your Account');
    }

    public function test_new_user_can_register_successfully(): void
    {
        $response = $this->post(route('register'), [
            'name'                  => 'Aulia Almara',
            'email'                 => 'aulia@example.com',
            'password'              => 'securepassword123',
            'password_confirmation' => 'securepassword123',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'name'  => 'Aulia Almara',
            'email' => 'aulia@example.com',
        ]);

        $user = User::where('email', 'aulia@example.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue(Hash::check('securepassword123', $user->password));
        $this->assertFalse($user->password === 'securepassword123'); // Must NOT be plaintext
    }

    public function test_user_automatically_assigned_default_role_user(): void
    {
        $this->post(route('register'), [
            'name'                  => 'Regular User',
            'email'                 => 'reguser@example.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $user = User::where('email', 'reguser@example.com')->first();
        $this->assertNotNull($user);

        // Verify assignment via user_roles table
        $this->assertDatabaseHas('user_roles', [
            'user_id' => $user->id,
            'role_id' => $this->userRole->id,
        ]);

        $this->assertTrue($user->hasRole('user'));
        $this->assertFalse($user->hasRole('admin'));
        $this->assertFalse($user->hasRole('super_admin'));
    }

    public function test_registration_prevents_privilege_escalation(): void
    {
        // Attacker attempts to inject admin/super_admin role in request payload
        $this->post(route('register'), [
            'name'                  => 'Hacker Attempt',
            'email'                 => 'hacker@example.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'role'                  => 'super_admin',
            'role_id'               => $this->superAdminRole->id,
            'is_admin'              => 1,
            'role_code'             => 'admin',
        ]);

        $user = User::where('email', 'hacker@example.com')->first();
        $this->assertNotNull($user);

        // Must still ONLY have the 'user' role
        $this->assertTrue($user->hasRole('user'));
        $this->assertFalse($user->hasRole('admin'));
        $this->assertFalse($user->hasRole('super_admin'));
        $this->assertEquals(1, $user->roles()->count());
    }

    public function test_registration_fails_with_existing_email(): void
    {
        User::create([
            'name'      => 'Existing User',
            'email'     => 'existing@example.com',
            'password'  => Hash::make('password123'),
            'is_active' => true,
        ]);

        $response = $this->post(route('register'), [
            'name'                  => 'New Person',
            'email'                 => 'existing@example.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    public function test_registration_fails_with_invalid_email_format(): void
    {
        $response = $this->post(route('register'), [
            'name'                  => 'Invalid Email Person',
            'email'                 => 'not-an-email',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    public function test_registration_fails_with_short_password(): void
    {
        $response = $this->post(route('register'), [
            'name'                  => 'Short Pass Person',
            'email'                 => 'shortpass@example.com',
            'password'              => '1234567', // 7 chars, minimum is 8
            'password_confirmation' => '1234567',
        ]);

        $response->assertSessionHasErrors(['password']);
    }

    public function test_registration_fails_when_password_confirmation_mismatches(): void
    {
        $response = $this->post(route('register'), [
            'name'                  => 'Mismatch Person',
            'email'                 => 'mismatch@example.com',
            'password'              => 'password123',
            'password_confirmation' => 'different456',
        ]);

        $response->assertSessionHasErrors(['password']);
    }

    public function test_registration_fails_safely_when_user_role_not_found(): void
    {
        // Deactivate or delete the 'user' role
        $this->userRole->delete();

        $response = $this->post(route('register'), [
            'name'                  => 'No Role User',
            'email'                 => 'norole@example.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors(['general']);
        // Verify user was NOT created (transaction rolled back)
        $this->assertDatabaseMissing('users', [
            'email' => 'norole@example.com',
        ]);
    }

    // ==========================================
    // 2. FORGOT PASSWORD & RESET TESTS
    // ==========================================

    public function test_forgot_password_screen_can_be_rendered(): void
    {
        $response = $this->get(route('password.request'));

        $response->assertStatus(200);
        $response->assertSee('Forgot Password?');
    }

    public function test_reset_link_can_be_requested_for_registered_user(): void
    {
        $user = User::create([
            'name'      => 'Reset User',
            'email'     => 'resetuser@example.com',
            'password'  => Hash::make('password123'),
            'is_active' => true,
        ]);

        $response = $this->post(route('password.email'), [
            'email' => 'resetuser@example.com',
        ]);

        $response->assertSessionHas('status');
        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => 'resetuser@example.com',
        ]);
    }

    public function test_reset_link_fails_for_nonexistent_email(): void
    {
        $response = $this->post(route('password.email'), [
            'email' => 'doesnotexist@example.com',
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    public function test_reset_password_screen_can_be_rendered(): void
    {
        $response = $this->get(route('password.reset', ['token' => 'dummy-token', 'email' => 'test@example.com']));

        $response->assertStatus(200);
        $response->assertSee('Set New Password');
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        $user = User::create([
            'name'      => 'Password Reset Person',
            'email'     => 'pwdreset@example.com',
            'password'  => Hash::make('oldpassword123'),
            'is_active' => true,
        ]);

        $token = Password::createToken($user);

        $response = $this->post(route('password.update'), [
            'token'                 => $token,
            'email'                 => 'pwdreset@example.com',
            'password'              => 'brandnewpassword123',
            'password_confirmation' => 'brandnewpassword123',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('success');

        $user->refresh();
        $this->assertTrue(Hash::check('brandnewpassword123', $user->password));
        $this->assertFalse(Hash::check('oldpassword123', $user->password));

        // Test login with old vs new password
        $this->assertFalse(Auth::attempt(['email' => 'pwdreset@example.com', 'password' => 'oldpassword123']));
        $this->assertTrue(Auth::attempt(['email' => 'pwdreset@example.com', 'password' => 'brandnewpassword123']));
    }

    public function test_password_cannot_be_reset_with_invalid_token(): void
    {
        $user = User::create([
            'name'      => 'Invalid Token Person',
            'email'     => 'invalidtoken@example.com',
            'password'  => Hash::make('originalpass123'),
            'is_active' => true,
        ]);

        $response = $this->post(route('password.update'), [
            'token'                 => 'invalid-wrong-token-value',
            'email'                 => 'invalidtoken@example.com',
            'password'              => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertSessionHasErrors(['email']);

        $user->refresh();
        $this->assertTrue(Hash::check('originalpass123', $user->password));
    }

    public function test_password_cannot_be_reset_when_confirmation_mismatches(): void
    {
        $user = User::create([
            'name'      => 'Mismatch Reset Person',
            'email'     => 'mismatchreset@example.com',
            'password'  => Hash::make('originalpass123'),
            'is_active' => true,
        ]);

        $token = Password::createToken($user);

        $response = $this->post(route('password.update'), [
            'token'                 => $token,
            'email'                 => 'mismatchreset@example.com',
            'password'              => 'newpassword123',
            'password_confirmation' => 'doesnotmatch123',
        ]);

        $response->assertSessionHasErrors(['password']);

        $user->refresh();
        $this->assertTrue(Hash::check('originalpass123', $user->password));
    }

    // ==========================================
    // 3. LOGOUT TESTS
    // ==========================================

    public function test_user_can_logout_successfully(): void
    {
        $user = User::create([
            'name'      => 'Logout Test User',
            'email'     => 'logoutuser@example.com',
            'password'  => Hash::make('password123'),
            'is_active' => true,
        ]);

        $this->actingAs($user);
        $this->assertTrue(Auth::check());

        $response = $this->post(route('logout'));

        $response->assertRedirect(route('login'));
        $this->assertFalse(Auth::check());
    }
}

