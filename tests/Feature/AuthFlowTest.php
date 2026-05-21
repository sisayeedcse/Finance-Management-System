<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\PendingRegistration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_log_in_with_a_hashed_password(): void
    {
        Member::create([
            'id' => 'SIPR26-JH-6729',
            'name' => 'Jahed Aziz',
            'email' => 'jaziro@sipr.com',
            'role' => 'admin',
            'status' => 'active',
            'password' => Hash::make('secret123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'jaziro@sipr.com',
            'password' => 'secret123',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'token',
                'member' => [
                    'id',
                    'name',
                    'email',
                    'role',
                    'title',
                ],
            ]);
    }

    public function test_registration_request_stores_a_hashed_password_and_invite_code(): void
    {
        $response = $this->postJson('/api/register-request', [
            'name' => 'New Member',
            'email' => 'new.member@example.com',
            'phone' => '01700000000',
            'invite_code' => 'INV-12345',
            'password' => 'secret123',
        ]);

        $response->assertOk();

        $pending = PendingRegistration::where('email', 'new.member@example.com')->first();

        $this->assertNotNull($pending);
        $this->assertSame('INV-12345', $pending->invite_code);
        $this->assertTrue(Hash::check('secret123', $pending->password));
    }

    public function test_admin_approval_creates_a_member_with_the_selected_role(): void
    {
        $admin = Member::create([
            'id' => 'SIPR26-AD-0001',
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'role' => 'admin',
            'status' => 'active',
            'password' => Hash::make('admin-secret'),
        ]);

        $pending = PendingRegistration::create([
            'name' => 'Approved User',
            'email' => 'approved@example.com',
            'phone' => '01700000001',
            'invite_code' => 'INV-54321',
            'password' => Hash::make('member-secret'),
            'status' => 'pending',
        ]);

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/registrations/' . $pending->id . '/approve', [
            'role' => 'finance',
        ]);

        $response->assertOk()->assertJson([
            'message' => 'Approved',
        ]);

        $created = Member::where('email', 'approved@example.com')->first();

        $this->assertNotNull($created);
        $this->assertSame('finance', $created->role);
        $this->assertTrue(Hash::check('member-secret', $created->password));

        $pending->refresh();
        $this->assertSame('approved', $pending->status);
        $this->assertSame('finance', $pending->approved_role);
        $this->assertSame($created->id, $pending->member_id);
    }

    public function test_google_sign_in_is_disabled(): void
    {
        $response = $this->get('/auth/google/redirect');

        $response->assertRedirect('/?error=google_signin_disabled');
    }
}