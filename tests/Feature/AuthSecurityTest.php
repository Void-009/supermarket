<?php

namespace Tests\Feature;

use App\Mail\WelcomeMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AuthSecurityTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'superadmin', 'is_active' => true]);
    }

    public function test_registration_creates_lowercased_unverified_customer_and_sends_welcome_email(): void
    {
        Mail::fake();

        $this->post('/register', [
            'name' => 'Test Customer',
            'email' => 'Customer@Example.com',
            'password' => 'StrongPass1',
            'password_confirmation' => 'StrongPass1',
        ])->assertRedirect(route('verification.notice'));

        $user = User::where('email', 'customer@example.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals('customer', $user->role);
        $this->assertTrue($user->is_active);
        $this->assertNull($user->email_verified_at);

        Mail::assertSent(WelcomeMail::class, fn ($mail) => $mail->hasTo('customer@example.com'));
    }

    public function test_unverified_customer_is_kept_out_of_the_store_on_login(): void
    {
        $user = User::factory()->create([
            'email' => 'unverified@example.com',
            'password' => bcrypt('StrongPass1'),
            'role' => 'customer',
            'is_active' => true,
            'email_verified_at' => null,
        ]);

        $this->post('/login', [
            'email' => 'unverified@example.com',
            'password' => 'StrongPass1',
        ])->assertRedirect(route('verification.notice'));
    }

    public function test_verified_customer_goes_to_the_store_after_login(): void
    {
        $user = User::factory()->create([
            'email' => 'verified@example.com',
            'password' => bcrypt('StrongPass1'),
            'role' => 'customer',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        $this->post('/login', [
            'email' => 'verified@example.com',
            'password' => 'StrongPass1',
        ])->assertRedirect(route('home'));
    }

    public function test_verification_link_marks_the_account_verified(): void
    {
        $user = User::factory()->create([
            'email' => 'pending@example.com',
            'role' => 'customer',
            'email_verified_at' => null,
        ]);

        $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
            'id' => $user->id,
            'hash' => sha1($user->getEmailForVerification()),
        ]);

        $this->actingAs($user)->get($url)->assertRedirect('/');

        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_unverified_user_cannot_start_online_payment(): void
    {
        $user = User::factory()->create([
            'role' => 'customer',
            'email_verified_at' => null,
        ]);

        $order = \App\Models\Order::create([
            'user_id' => $user->id,
            'customer_name' => 'Test Customer',
            'customer_phone' => '08000000000',
            'delivery_method' => 'pickup',
            'payment_method' => 'bank_transfer',
            'status' => 'new',
            'payment_status' => 'pending',
            'payment_reference' => 'TEST-PAY-123',
            'total_amount' => 100,
            'delivery_fee' => 0,
        ]);

        $this->actingAs($user)
            ->get("/payment/paystack/{$order->id}")
            ->assertRedirect(route('verification.notice'));
    }

    public function test_registration_rejects_weak_password(): void
    {
        $this->post('/register', [
            'name' => 'Test Customer',
            'email' => 'weak@example.com',
            'password' => 'short',
            'password_confirmation' => 'short',
        ])->assertSessionHasErrors('password');

        $this->assertDatabaseMissing('users', ['email' => 'weak@example.com']);
    }

    public function test_registration_rejects_password_without_number(): void
    {
        $this->post('/register', [
            'name' => 'Test Customer',
            'email' => 'no-number@example.com',
            'password' => 'onlyletters',
            'password_confirmation' => 'onlyletters',
        ])->assertSessionHasErrors('password');

        $this->assertDatabaseMissing('users', ['email' => 'no-number@example.com']);
    }

    public function test_registration_rejects_duplicate_email_case_insensitively(): void
    {
        User::factory()->create(['email' => 'existing@example.com', 'role' => 'customer']);

        $this->post('/register', [
            'name' => 'Another',
            'email' => 'EXISTING@example.com',
            'password' => 'StrongPass1',
            'password_confirmation' => 'StrongPass1',
        ])->assertSessionHasErrors('email');
    }

    public function test_forgot_password_does_not_leak_account_existence(): void
    {
        $this->get('/forgot-password')->assertStatus(200);

        // Unknown account still receives the same generic "link sent" message.
        $this->post('/forgot-password', ['email' => 'nobody@example.com'])
            ->assertSessionHas('status')
            ->assertSessionMissing('errors');

        // Known account behaves identically.
        $user = User::factory()->create(['email' => 'known@example.com', 'role' => 'customer']);
        $this->post('/forgot-password', ['email' => $user->email])
            ->assertSessionHas('status')
            ->assertSessionMissing('errors');
    }

    public function test_reset_password_rejects_weak_password(): void
    {
        $this->post('/reset-password', [
            'token' => 'invalid-token',
            'email' => 'someone@example.com',
            'password' => 'weak',
            'password_confirmation' => 'weak',
        ])->assertSessionHasErrors('password');
    }

    public function test_admin_cannot_create_staff_with_weak_password(): void
    {
        $this->actingAs($this->admin());

        $this->post('/admin/users', [
            'name' => 'New Staff',
            'email' => 'staff@example.com',
            'password' => 'weak',
            'role' => 'admin',
            'is_active' => '1',
        ])->assertSessionHasErrors('password');

        $this->assertDatabaseMissing('users', ['email' => 'staff@example.com']);
    }

    public function test_login_blocks_deactivated_account(): void
    {
        User::factory()->create([
            'email' => 'disabled@example.com',
            'password' => bcrypt('StrongPass1'),
            'role' => 'customer',
            'is_active' => false,
        ]);

        $this->post('/login', [
            'email' => 'disabled@example.com',
            'password' => 'StrongPass1',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }
}
