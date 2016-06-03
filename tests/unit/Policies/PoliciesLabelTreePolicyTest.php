<?php

use Dias\Role;

class PoliciesLabelTreePolicyTest extends TestCase
{
    private $tree;
    private $user;
    private $editor;
    private $admin;
    private $globalAdmin;


    public function setUp()
    {
        parent::setUp();
        $this->tree = LabelTreeTest::create();
        $this->user = UserTest::create();
        $this->editor = UserTest::create();
        $this->admin = UserTest::create();
        $this->globalAdmin = UserTest::create(['role_id' => Role::$admin->id]);
        $this->tree->addMember($this->editor, Role::$editor);
        $this->tree->addMember($this->admin, Role::$admin);
    }

    public function testAccess()
    {
        $this->assertFalse($this->user->can('access', $this->tree));
        $this->assertTrue($this->editor->can('access', $this->tree));
        $this->assertTrue($this->admin->can('access', $this->tree));
        $this->assertTrue($this->globalAdmin->can('access', $this->tree));
    }

    public function testAddLabel()
    {
        $this->assertFalse($this->user->can('add-label', $this->tree));
        $this->assertTrue($this->editor->can('add-label', $this->tree));
        $this->assertTrue($this->admin->can('add-label', $this->tree));
        $this->assertTrue($this->globalAdmin->can('add-label', $this->tree));
    }

    public function testRemoveLabel()
    {
        $this->assertFalse($this->user->can('remove-label', $this->tree));
        $this->assertTrue($this->editor->can('remove-label', $this->tree));
        $this->assertTrue($this->admin->can('remove-label', $this->tree));
        $this->assertTrue($this->globalAdmin->can('remove-label', $this->tree));
    }

    public function testUpdate()
    {
        $this->assertFalse($this->user->can('update', $this->tree));
        $this->assertFalse($this->editor->can('update', $this->tree));
        $this->assertTrue($this->admin->can('update', $this->tree));
        $this->assertTrue($this->globalAdmin->can('update', $this->tree));
    }

    public function testDestroy()
    {
        $this->assertFalse($this->user->can('destroy', $this->tree));
        $this->assertFalse($this->editor->can('destroy', $this->tree));
        $this->assertTrue($this->admin->can('destroy', $this->tree));
        $this->assertTrue($this->globalAdmin->can('destroy', $this->tree));
    }

    public function testAddMember()
    {
        $this->assertFalse($this->user->can('add-member', $this->tree));
        $this->assertFalse($this->editor->can('add-member', $this->tree));
        $this->assertTrue($this->admin->can('add-member', $this->tree));
        $this->assertTrue($this->globalAdmin->can('add-member', $this->tree));
    }

    public function testUpdateMember()
    {
        $this->assertFalse($this->user->can('update-member', [$this->tree, $this->user]));

        $this->assertFalse($this->editor->can('update-member', [$this->tree, $this->user]));

        $this->assertTrue($this->admin->can('update-member', [$this->tree, $this->user]));
        $this->assertFalse($this->admin->can('update-member', [$this->tree, $this->admin]));

        $this->assertTrue($this->globalAdmin->can('update-member', [$this->tree, $this->user]));
        $this->assertTrue($this->globalAdmin->can('update-member', [$this->tree, $this->admin]));
    }

    public function testRemoveMember()
    {
        // user is no member
        $this->assertFalse($this->user->can('remove-member', [$this->tree, $this->user]));
        $this->assertFalse($this->user->can('remove-member', [$this->tree, $this->editor]));
        $this->assertFalse($this->user->can('remove-member', [$this->tree, $this->admin]));

        $this->assertFalse($this->editor->can('remove-member', [$this->tree, $this->user]));
        $this->assertTrue($this->editor->can('remove-member', [$this->tree, $this->editor]));
        $this->assertFalse($this->editor->can('remove-member', [$this->tree, $this->admin]));

        $this->assertTrue($this->admin->can('remove-member', [$this->tree, $this->user]));
        $this->assertTrue($this->admin->can('remove-member', [$this->tree, $this->editor]));
        $this->assertTrue($this->admin->can('remove-member', [$this->tree, $this->admin]));

        $this->assertTrue($this->globalAdmin->can('remove-member', [$this->tree, $this->user]));
        $this->assertTrue($this->globalAdmin->can('remove-member', [$this->tree, $this->editor]));
        $this->assertTrue($this->globalAdmin->can('remove-member', [$this->tree, $this->admin]));
    }
}
