<?php

namespace Dias\Console\Commands;

use Illuminate\Console\Command;
use Dias\User;
use Dias\Role;

class NewUser extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'user:new';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new user';

    /**
     * Handle the command
     *
     * @return void
     */
    public function handle()
    {
        $u = new User;
        $u->firstname = $this->ask('What is the user\'s first name?');
        $u->lastname = $this->ask('What is the user\'s last name?');
        $u->email = $this->ask('What is the user\'s email address?');

        if ($this->confirm('Should the user be global admin? [y|N]')) {
            $u->role_id = Role::$admin->id;
        }

        if ($this->confirm('Do you wish to auto-generate a password? [y|N]')) {
            $password = str_random(10);
            $this->info("The password is <comment>{$password}</comment>");
        } else {
            $password = $this->secret('Please enter the password (min. 8 characters)');
            while (strlen($password) < 8) {
                $password = $this->secret('Please choose a password with at least 8 characters');
            }
        }

        $u->password = bcrypt($password);
        $u->save();
        $this->info("<comment>{$u->firstname} {$u->lastname}</comment> was successfully created!");
    }
}
