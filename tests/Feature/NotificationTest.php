<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Budget;
use App\Models\Category;
use App\Models\Debt;
use App\Models\Installment;
use App\Models\Notification;
use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed default role
        $role = Role::create([
            'role_code' => 'user',
            'role_name' => 'User',
            'is_active' => true,
        ]);

        $this->user = User::factory()->create();

        UserRole::create([
            'user_id' => $this->user->id,
            'role_id' => $role->id,
        ]);
    }

    public function test_poll_requires_authentication(): void
    {
        $response = $this->getJson('/notifications/poll');
        $response->assertStatus(401);
    }

    public function test_poll_returns_empty_notifications_for_new_user(): void
    {
        $response = $this->actingAs($this->user)->getJson('/notifications/poll');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'unread_count' => 0,
                'notifications' => [],
            ]);
    }

    public function test_automated_notification_for_upcoming_debt_installment(): void
    {
        $debt = Debt::create([
            'user_id' => $this->user->id,
            'creditor_name' => 'Bank BCA',
            'debt_type' => 'bank_loan',
            'direction' => 'payable',
            'total_amount' => 5000000,
            'remaining_amount' => 5000000,
            'start_date' => now(),
            'due_date' => now()->addDays(2),
            'tenor_months' => 5,
            'status' => 'active',
        ]);

        Installment::create([
            'debt_id' => $debt->id,
            'installment_number' => 1,
            'due_date' => now()->addDays(2),
            'amount' => 1000000,
            'status' => 'unpaid',
        ]);

        $response = $this->actingAs($this->user)->getJson('/notifications/poll');

        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('unread_count'));
        $this->assertEquals('debt_due', $response->json('notifications.0.type'));
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->user->id,
            'type' => 'debt_due',
            'is_read' => false,
        ]);
    }

    public function test_automated_notification_for_low_account_balance(): void
    {
        Account::create([
            'user_id' => $this->user->id,
            'account_name' => 'Dompet Cash',
            'account_type' => 'cash',
            'account_number' => null,
            'current_balance' => 50000, // below 200.000 default threshold
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)->getJson('/notifications/poll');

        $response->assertStatus(200);
        $this->assertGreaterThanOrEqual(1, $response->json('unread_count'));
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->user->id,
            'type' => 'low_balance',
        ]);
    }

    public function test_mark_single_notification_as_read(): void
    {
        $notif = Notification::create([
            'user_id' => $this->user->id,
            'type' => 'system',
            'title' => 'Test Notice',
            'message' => 'Hello test',
            'variant' => 'info',
            'is_read' => false,
        ]);

        $response = $this->actingAs($this->user)->postJson("/notifications/{$notif->id}/read");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'unread_count' => 0,
            ]);

        $this->assertTrue($notif->fresh()->is_read);
    }

    public function test_mark_all_notifications_as_read(): void
    {
        Notification::create([
            'user_id' => $this->user->id,
            'type' => 'system',
            'title' => 'Notice 1',
            'message' => 'Hello 1',
            'is_read' => false,
        ]);
        Notification::create([
            'user_id' => $this->user->id,
            'type' => 'system',
            'title' => 'Notice 2',
            'message' => 'Hello 2',
            'is_read' => false,
        ]);

        $response = $this->actingAs($this->user)->postJson('/notifications/read-all');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'unread_count' => 0,
            ]);

        $this->assertEquals(0, Notification::where('user_id', $this->user->id)->unread()->count());
    }

    public function test_notifications_are_not_duplicated_on_repeated_polls(): void
    {
        Account::create([
            'user_id' => $this->user->id,
            'account_name' => 'E-Wallet OVO',
            'account_type' => 'ewallet',
            'current_balance' => 20000,
            'is_active' => true,
        ]);

        // First poll
        $this->actingAs($this->user)->getJson('/notifications/poll');
        $countFirst = Notification::where('user_id', $this->user->id)->count();

        // Second poll
        $this->actingAs($this->user)->getJson('/notifications/poll');
        $countSecond = Notification::where('user_id', $this->user->id)->count();

        $this->assertEquals($countFirst, $countSecond);
    }
}
