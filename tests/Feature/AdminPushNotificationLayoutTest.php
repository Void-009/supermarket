<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPushNotificationLayoutTest extends TestCase
{
    use RefreshDatabase;

    private function makeAdmin(array $permissions = []): User
    {
        return User::create([
            'name' => 'Admin',
            'email' => 'admin.layout@test.com',
            'password' => bcrypt('Password123'),
            'role' => 'admin',
            'is_active' => true,
            'permissions' => $permissions,
        ]);
    }

    public function test_admin_layout_exposes_the_push_opt_in_control()
    {
        $admin = $this->makeAdmin(['process_orders' => true]);

        $html = $this->actingAs($admin)
            ->get(route('admin.orders.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('x-data="pushNotifyBtn()"', $html);
        $this->assertStringContainsString('window.VAPID_PUBLIC_KEY', $html);
        $this->assertStringContainsString('build/assets/pwa-', $html);
    }

    public function test_push_control_still_works_without_the_chat_permission()
    {
        // manage_chat only gates the chat widget, not notifications.
        $admin = $this->makeAdmin(['process_orders' => true]);

        $html = $this->actingAs($admin)
            ->get(route('admin.orders.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('x-data="pushNotifyBtn()"', $html);
        $this->assertStringNotContainsString('x-data="adminChatWidget()"', $html);
    }

    public function test_chat_widget_renders_for_staff_with_manage_chat()
    {
        $admin = $this->makeAdmin(['process_orders' => true, 'manage_chat' => true]);

        $this->actingAs($admin)
            ->get(route('admin.orders.index'))
            ->assertOk()
            ->assertSee('x-data="adminChatWidget()"', false);
    }
}
