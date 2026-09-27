<?php
// Copy to ../dwa-private/config.php, OUTSIDE public_html. Never commit credentials.
return [
    'enabled' => false, // Set true only after database + SMTP are ready.
    'app_key' => '', // Generate 64 random hex characters: php -r "echo bin2hex(random_bytes(32));"
    'db' => ['host' => 'localhost', 'name' => '', 'user' => '', 'password' => ''],
    'smtp' => [
        'host' => '', 'port' => 465, 'encryption' => 'ssl',
        'username' => '', 'password' => '',
        'from_email' => '', 'from_name' => 'DWA Bussines',
    ],
];
