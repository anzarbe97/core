<?php

return [

    /*
    | Version of the biigle/core build.
    */
    'version' => env('BIIGLE_VERSION'),

    /*
    | Show the logfiles in the admin area.
    */
    'admin_logs' => true,

    /*
    | Email address of the admins of this BIIGLE instance.
    */
    'admin_email' => env('ADMIN_EMAIL', ''),

    /*
    | Disable all features that require a working internet connection.
    */
    'offline_mode' => false,

    /*
    | Enable user registration. This allows everybody to create a new user account.
    */
    'user_registration' => false,

    /*
    | Enable user registration confirmation by admins. Whenever a new user is registered,
    | they are created with the global "guest" role and an email notification is sent to
    | the admin_email. If admins approve the registration, the global role of the new user
    | is changed to "editor". If they reject the registration, the new user is deleted.
    | If this is disabled, all new users immediately get the global "editor" role.
    |
    | This feature cannot be enabled in offline mode as it relies on emails.
    */
    'user_registration_confirmation' => false,

];
