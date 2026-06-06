<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\User;

class UserTest extends TestCase
{
    /** @test */
    public function it_has_fillable_attributes()
    {
        $user = new User();
        $fillable = $user->getFillable();

        $expected = ['name', 'email', 'password_hash', 'role', 'active', 'email_verified'];

        foreach ($expected as $attr) {
            $this->assertContains($attr, $fillable, "Missing fillable: {$attr}");
        }
    }

    /** @test */
    public function it_has_hidden_attributes()
    {
        $user = new User();
        $hidden = $user->getHidden();

        $this->assertContains('password_hash', $hidden);
        $this->assertContains('email_verify_token', $hidden);
    }

    /** @test */
    public function it_can_create_a_user()
    {
        $user = new User([
            'name'     => 'Admin',
            'email'    => 'admin@example.com',
            'role'     => 'admin',
            'active'   => 1,
        ]);

        $this->assertEquals('Admin', $user->name);
        $this->assertEquals('admin@example.com', $user->email);
    }

    /** @test */
    public function it_returns_active_status()
    {
        $activeUser   = new User(['active' => 1]);
        $inactiveUser = new User(['active' => 0]);

        $this->assertTrue($activeUser->isActive());
        $this->assertFalse($inactiveUser->isActive());
    }

    /** @test */
    public function is_active_returns_boolean()
    {
        $user = new User(['active' => 1]);
        $this->assertIsBool($user->isActive());
    }

    /** @test */
    public function it_accepts_role_admin_or_editor()
    {
        $admin  = new User(['role' => 'admin']);
        $editor = new User(['role' => 'editor']);

        $this->assertEquals('admin', $admin->role);
        $this->assertEquals('editor', $editor->role);
    }
}
