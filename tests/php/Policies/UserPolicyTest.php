<?php

namespace Biigle\Tests\Policies;

use TestCase;
use Biigle\Role;
use Biigle\User;
use Biigle\Tests\UserTest;

class UserPolicyTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->guest = UserTest::create(['role_id' => Role::$guest->id]);
        $this->editor = UserTest::create(['role_id' => Role::$editor->id]);
        $this->admin = UserTest::create(['role_id' => Role::$admin->id]);
    }

    public function testCreate()
    {
        $this->assertFalse($this->guest->can('create', User::class));
        $this->assertFalse($this->editor->can('create', User::class));
        $this->assertTrue($this->admin->can('create', User::class));
    }

    public function testUpdate()
    {
        $this->assertFalse($this->guest->can('update', $this->admin));
        $this->assertTrue($this->guest->can('update', $this->guest));

        $this->assertFalse($this->editor->can('update', $this->admin));
        $this->assertTrue($this->editor->can('update', $this->editor));

        $this->assertTrue($this->admin->can('update', $this->guest));
        $this->assertTrue($this->admin->can('update', $this->editor));
        $this->assertTrue($this->admin->can('update', $this->admin));
    }

    public function testDestroy()
    {
        $this->assertFalse($this->guest->can('destroy', $this->admin));
        $this->assertTrue($this->guest->can('destroy', $this->guest));

        $this->assertFalse($this->editor->can('destroy', $this->admin));
        $this->assertTrue($this->editor->can('destroy', $this->editor));

        $this->assertTrue($this->admin->can('destroy', $this->guest));
        $this->assertTrue($this->admin->can('destroy', $this->editor));
        $this->assertTrue($this->admin->can('destroy', $this->admin));
    }
}
