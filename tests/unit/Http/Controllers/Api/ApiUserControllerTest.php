<?php

use Dias\User;
use Dias\Role;

class ApiUserControllerTest extends ApiTestCase
{
    private function callToken($verb, $route, $user)
    {
        $token = ApiTokenTest::create([
            'hash' => bcrypt('test_token'),
            'owner_id' => $user->id,
        ]);

        return $this->call($verb, $route, [], [], [], [
            'PHP_AUTH_USER' => $user->email,
            'PHP_AUTH_PW' => 'test_token',
        ]);
    }

    public function testIndex()
    {
        $this->doTestApiRoute('GET', '/api/v1/users');

        // everybody can do this
        $this->beGuest();
        $this->get('/api/v1/users');
        $content = $this->response->getContent();
        $this->assertResponseOk();
        $this->assertStringStartsWith('[', $content);
        $this->assertStringEndsWith(']', $content);
    }

    public function testShow()
    {
        $this->doTestApiRoute('GET', '/api/v1/users/'.$this->guest()->id);

        $this->beGuest();
        $this->get('/api/v1/users/'.$this->guest()->id);
        $this->assertResponseOk();

        $this->beGlobalAdmin();
        $this->get('/api/v1/users/'.$this->guest()->id);
        $content = $this->response->getContent();
        $this->assertResponseOk();
        $this->assertStringStartsWith('{', $content);
        $this->assertStringEndsWith('}', $content);
    }

    public function testShowOwn()
    {
        $this->doTestApiRoute('GET', '/api/v1/users/my');

        $this->beGuest();
        $this->get('/api/v1/users/my');
        $this->assertResponseOk();

        $this->beGlobalAdmin();
        $this->get('/api/v1/users/my');
        $content = $this->response->getContent();
        $this->assertResponseOk();
        $this->assertStringStartsWith('{', $content);
        $this->assertStringEndsWith('}', $content);
    }

    public function testUpdate()
    {
        $this->doTestApiRoute('PUT', '/api/v1/users/'.$this->guest()->id);

        // api key authentication is not allowed for this route
        $this->callToken('PUT', '/api/v1/users/'.$this->guest()->id, $this->globalAdmin());
        $this->assertResponseStatus(401);

        $this->beGuest();
        $this->put('/api/v1/users/'.$this->guest()->id);
        $this->assertResponseStatus(401);

        $this->beEditor();
        $this->put('/api/v1/users/'.$this->guest()->id);
        $this->assertResponseStatus(401);

        $this->globalAdmin()->password = bcrypt('adminpassword');
        $this->globalAdmin()->save();
        $this->beGlobalAdmin();

        $this->put('/api/v1/users/'.$this->globalAdmin()->id);
        // the own user cannot be updated via this route
        $this->assertResponseStatus(400);

        // ajax call to get the correct response status
        $this->json('PUT', '/api/v1/users/'.$this->guest()->id, [
            'password' => 'hacked!!',
        ]);
        // no password confirmation
        $this->assertResponseStatus(422);

        $this->json('PUT', '/api/v1/users/'.$this->guest()->id, [
            'password' => '',
            'password_confirmation' => '',
        ]);
        // password must not be empty if it is present
        $this->assertResponseStatus(422);

        $this->json('PUT', '/api/v1/users/'.$this->guest()->id, [
            'password' => 'newpassword',
            'password_confirmation' => 'newpassword',
        ]);
        // changing the email requires the admin password
        $this->assertResponseStatus(422);

        $this->json('PUT', '/api/v1/users/'.$this->guest()->id, [
            'password' => 'newpassword',
            'password_confirmation' => 'newpassword',
            'auth_password' => 'wrongpassword',
        ]);
        // wrong password
        $this->assertResponseStatus(422);

        $this->assertFalse(Hash::check('newpassword', $this->guest()->fresh()->password));

        $this->json('PUT', '/api/v1/users/'.$this->guest()->id, [
            'password' => 'newpassword',
            'password_confirmation' => 'newpassword',
            'auth_password' => 'adminpassword',
        ]);
        $this->assertResponseOk();
        $this->assertTrue(Hash::check('newpassword', $this->guest()->fresh()->password));

        // ajax call to get the correct response status
        $this->json('PUT', '/api/v1/users/'.$this->guest()->id, [
            'email' => 'no-mail',
        ]);
        // invalid email format
        $this->assertResponseStatus(422);

        $this->json('PUT', '/api/v1/users/'.$this->guest()->id, [
            'email' => '',
        ]);
        // email must not be empty if it is present
        $this->assertResponseStatus(422);

        $this->json('PUT', '/api/v1/users/'.$this->guest()->id, [
            'email' => 'new@email.me',
        ]);
        // changing the email requires the admin password
        $this->assertResponseStatus(422);

        $this->json('PUT', '/api/v1/users/'.$this->guest()->id, [
            'email' => 'new@email.me',
            'auth_password' => 'wrongpassword',
        ]);
        // wrong password
        $this->assertResponseStatus(422);

        $this->json('PUT', '/api/v1/users/'.$this->guest()->id, [
            'email' => 'new@email.me',
            'auth_password' => 'adminpassword',
        ]);
        $this->assertResponseOk();
        $this->assertEquals('new@email.me', $this->guest()->fresh()->email);

        $this->json('PUT', '/api/v1/users/'.$this->guest()->id, [
            'role_id' => 999,
            'auth_password' => 'adminpassword',
        ]);
        // role does not exist
        $this->assertResponseStatus(422);

        $this->json('PUT', '/api/v1/users/'.$this->guest()->id, [
            'role_id' => Role::$admin->id,
        ]);
        // changing the role requires the admin password
        $this->assertResponseStatus(422);

        $this->json('PUT', '/api/v1/users/'.$this->guest()->id, [
            'role_id' => Role::$admin->id,
            'auth_password' => 'wrongpassword',
        ]);
        // wrong password
        $this->assertResponseStatus(422);

        $this->assertEquals(Role::$editor->id, $this->guest()->fresh()->role_id);

        $this->json('PUT', '/api/v1/users/'.$this->guest()->id, [
            'role_id' => Role::$admin->id,
            'auth_password' => 'adminpassword',
        ]);
        $this->assertResponseOk();
        $this->assertEquals(Role::$admin->id, $this->guest()->fresh()->role_id);

        $this->put('/api/v1/users/'.$this->guest()->id, [
            'firstname' => 'jack',
            'lastname' => 'jackson',
        ]);
        $this->assertResponseOk();

        $this->assertEquals('jack', $this->guest()->fresh()->firstname);
        $this->assertEquals('jackson', $this->guest()->fresh()->lastname);
    }

    public function testUpdateOwn()
    {
        $this->guest()->password = bcrypt('guest-password');
        $this->guest()->save();

        $this->doTestApiRoute('PUT', '/api/v1/users/my');

        // api key authentication is not allowed for this route
        $this->callToken('PUT', '/api/v1/users/my', $this->guest());
        $this->assertResponseStatus(401);

        $this->beGuest();
        $this->json('PUT', '/api/v1/users/my', [
            'password' => 'hacked!!',
            '_origin' => 'password',
        ]);
        // no password confirmation
        $this->assertResponseStatus(422);
        $this->assertSessionHas('origin', 'password');

        // ajax call to get the correct response status
        $this->json('PUT', '/api/v1/users/my', [
            'email' => 'no-mail',
        ]);
        // invalid email format
        $this->assertResponseStatus(422);

        $this->json('PUT', '/api/v1/users/my', [
            'password' => 'newpassword',
            'password_confirmation' => 'newpassword',
        ]);
        // no auth password provided
        $this->assertResponseStatus(422);

        $this->json('PUT', '/api/v1/users/my', [
            'email' => 'new@email.me',
        ]);
        // no auth password provided either
        $this->assertResponseStatus(422);

        // ajax call to get the correct response status
        $this->json('PUT', '/api/v1/users/my', [
            'password' => 'newpassword',
            'password_confirmation' => 'newpassword',
            'auth_password' => 'guest-password',
            'firstname' => 'jack',
            'lastname' => 'jackson',
            'email' => 'new@email.me',
            '_origin' => 'email'
        ]);
        $this->assertResponseOk();
        $this->assertSessionHas('origin', 'email');

        $user = $this->guest()->fresh();
        $this->assertTrue(Hash::check('newpassword', $user->password));
        $this->assertEquals('jack', $user->firstname);
        $this->assertEquals('jackson', $user->lastname);
        $this->assertEquals('new@email.me', $user->email);
    }

    public function testStore()
    {
        $this->doTestApiRoute('POST', '/api/v1/users');

        // api key authentication is not allowed for this route
        $this->callToken('POST', '/api/v1/users', $this->globalAdmin());
        $this->assertResponseStatus(401);

        $this->beAdmin();
        $this->post('/api/v1/users', [
            '_token' => Session::token(),
        ]);
        $this->assertResponseStatus(401);

        $this->beGlobalAdmin();
        // ajax call to get the correct response status
        $this->json('POST', '/api/v1/users', [
            'password' => 'newpassword',
            'firstname' => 'jack',
            'lastname' => 'jackson',
            'email' => 'new@email.me',
        ]);
        // no password confirmation
        $this->assertResponseStatus(422);

        $this->post('/api/v1/users', [
            'password' => 'newpassword',
            'password_confirmation' => 'newpassword',
            'firstname' => 'jack',
            'lastname' => 'jackson',
            'email' => 'new@email.me',
        ]);
        $this->assertResponseOk();

        $newUser = User::find(User::max('id'));
        $this->assertEquals('jack', $newUser->firstname);
        $this->assertEquals('jackson', $newUser->lastname);
        $this->assertEquals('new@email.me', $newUser->email);
        $this->assertEquals(Role::$editor->id, $newUser->role_id);

        $this->json('POST', '/api/v1/users', [
            'password' => 'newpassword',
            'password_confirmation' => 'newpassword',
            'firstname' => 'jack',
            'lastname' => 'jackson',
            'email' => 'new@email.me',
        ]);
        // email has already been taken
        $this->assertResponseStatus(422);

        $this->json('POST', '/api/v1/users', [
            'password' => 'newpassword',
            'password_confirmation' => 'newpassword',
            'firstname' => 'jack',
            'lastname' => 'jackson',
            'email' => 'new2@email.me',
            'role_id' => 999
        ]);
        // role does not exist
        $this->assertResponseStatus(422);

        $this->json('POST', '/api/v1/users', [
            'password' => 'newpassword',
            'password_confirmation' => 'newpassword',
            'firstname' => 'jack',
            'lastname' => 'jackson',
            'email' => 'new2@email.me',
            'role_id' => Role::$admin->id,
        ]);
        $this->assertResponseOk();

        $newUser = User::find(User::max('id'));
        $this->assertEquals(Role::$admin->id, $newUser->role_id);
    }

    public function testDestroy()
    {
        $this->globalAdmin()->password = bcrypt('globalAdmin-password');
        $this->globalAdmin()->save();

        $id = $this->guest()->id;
        $this->doTestApiRoute('DELETE', '/api/v1/users/'.$id);

        // api key authentication is not allowed for this route
        $this->callToken('DELETE', '/api/v1/users/'.$id, $this->globalAdmin());
        $this->assertResponseStatus(401);

        $this->beAdmin();
        $this->delete('/api/v1/users/'.$id, [
            '_token' => Session::token(),
        ]);
        $this->assertResponseStatus(401);

        $this->beGlobalAdmin();

        $this->delete('/api/v1/users/'.$this->globalAdmin()->id, [
            '_token' => Session::token(),
        ]);
        // the own user cannot be deleted via this route
        $this->assertResponseStatus(400);

        $this->json('DELETE', '/api/v1/users/'.$id);
        // admin password is required
        $this->assertResponseStatus(422);

        $this->json('DELETE', '/api/v1/users/'.$id, [
            'password' => 'wrong-password',
        ]);
        // admin password is wrong
        $this->assertResponseStatus(422);

        $this->assertNotNull($this->guest()->fresh());
        $this->delete('/api/v1/users/'.$id, [
            'password' => 'globalAdmin-password',
        ]);
        $this->assertResponseOk();
        $this->assertNull($this->guest()->fresh());

        // remove creator, so admin is the last remaining admin of the project
        $this->project()->removeUserId($this->project()->creator->id);
        $this->delete('/api/v1/users/'.$this->admin()->id, [
            'password' => 'globalAdmin-password',
        ]);
        // last remaining admin of a project mustn't be deleted
        $this->assertResponseStatus(400);
    }

    public function testDestroyOwn()
    {
        $this->guest()->password = bcrypt('guest-password');
        $this->guest()->save();
        $this->editor()->password = bcrypt('editor-password');
        $this->guest()->save();

        $this->doTestApiRoute('DELETE', '/api/v1/users/my');

        // api key authentication is not allowed for this route
        $this->callToken('DELETE', '/api/v1/users/my', $this->guest());
        $this->assertResponseStatus(401);

        $this->beGuest();
        // ajax call to get the correct response status
        $this->json('DELETE', '/api/v1/users/my');
        // no password provided
        $this->assertResponseStatus(422);

        // ajax call to get the correct response status
        $this->json('DELETE', '/api/v1/users/my', [
            'password' => 'wrong-password'
        ]);
        // wrong password provided
        $this->assertResponseStatus(422);

        $this->assertNotNull($this->guest()->fresh());
        // ajax call to get the correct response status
        $this->json('DELETE', '/api/v1/users/my', [
            'password' => 'guest-password'
        ]);
        $this->assertResponseOk();
        $this->assertNull($this->guest()->fresh());

        $this->beEditor();
        $this->delete('/api/v1/users/my', [
            'password' => 'editor-password'
        ]);
        $this->assertRedirectedTo('auth/login');
        $this->assertNull(Auth::user());

        $this->delete('/api/v1/users/my');
        // deleted user doesn't have permission any more
        $this->assertResponseStatus(401);

        $this->beAdmin();
        // make admin the only admin
        $this->project()->creator->delete();
        $this->visit('settings/profile');
        $this->delete('/api/v1/users/my', [
            '_token' => Session::token(),
        ]);
        // couldn't be deleted, returns with error message
        $this->assertRedirectedTo('settings/profile');
        $this->assertNotNull(Auth::user());
        $this->assertSessionHas('errors');
    }

    public function testFind()
    {
        $user = UserTest::create(['firstname' => 'abc', 'lastname' => 'def']);
        UserTest::create(['firstname' => 'abc', 'lastname' => 'ghi']);

        $this->doTestApiRoute('GET', '/api/v1/users/find/a');

        $this->beGuest();
        $this->get('/api/v1/users/find/a');
        $content = $this->response->getContent();
        $this->assertResponseOk();

        $this->assertContains('"firstname":"abc"', $content);
        $this->assertContains('"lastname":"def"', $content);
        $this->assertContains('"lastname":"ghi"', $content);

        $this->get('/api/v1/users/find/d');
        $content = $this->response->getContent();
        $this->assertResponseOk();

        $this->assertContains('"firstname":"abc"', $content);
        $this->assertContains('"lastname":"def"', $content);
        $this->assertNotContains('"lastname":"ghi"', $content);
    }
}
