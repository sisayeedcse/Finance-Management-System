<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\PendingRegistration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
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

    public function test_admin_can_delete_a_pending_registration(): void
    {
        $admin = Member::create([
            'id' => 'SIPR26-AD-0002',
            'name' => 'Admin User',
            'email' => 'admin2@example.com',
            'role' => 'admin',
            'status' => 'active',
            'password' => Hash::make('admin-secret'),
        ]);

        $pending = PendingRegistration::create([
            'name' => 'Delete Me',
            'email' => 'delete@example.com',
            'phone' => '01700000002',
            'invite_code' => 'INV-00002',
            'password' => Hash::make('member-secret'),
            'status' => 'pending',
        ]);

        Sanctum::actingAs($admin);

        $response = $this->deleteJson('/api/registrations/' . $pending->id);

        $response->assertOk()->assertJson([
            'message' => 'Deleted',
        ]);

        $this->assertDatabaseMissing('pending_registrations', [
            'email' => 'delete@example.com',
        ]);
    }

    public function test_google_sign_in_issues_a_token_and_links_the_member(): void
    {
        Member::create([
            'id' => 'SIPR26-GG-0001',
            'name' => 'Imported Member',
            'email' => 'member@example.com',
            'role' => 'member',
            'status' => 'active',
            'password' => null,
        ]);

        Http::fake([
            'https://oauth2.googleapis.com/tokeninfo*' => Http::response([
                'email' => 'member@example.com',
                'sub' => 'google-sub-123',
                'name' => 'Imported Member',
                'picture' => 'https://example.com/photo.jpg',
            ], 200),
        ]);

        $response = $this->postJson('/api/auth/google', [
            'id_token' => 'fake-token',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'token',
                'member' => [
                    'id',
                    'name',
                    'email',
                    'role',
                ],
            ]);

        $this->assertDatabaseHas('members', [
            'email' => 'member@example.com',
            'google_uid' => 'google-sub-123',
            'google_email' => 'member@example.com',
        ]);
    }

    public function test_unknown_google_sign_in_is_rejected_without_creating_a_member(): void
    {
        Http::fake([
            'https://oauth2.googleapis.com/tokeninfo*' => Http::response([
                'email' => 'new.person@example.com',
                'sub' => 'google-sub-999',
                'name' => 'New Person',
            ], 200),
        ]);

        $response = $this->postJson('/api/auth/google', [
            'id_token' => 'fake-token',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'error' => 'Google account is not linked to an approved member',
            ]);

        $this->assertDatabaseMissing('members', [
            'email' => 'new.person@example.com',
        ]);
    }
}