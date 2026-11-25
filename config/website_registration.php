<?php

return [
    'enabled' => (bool) env('WEBSITE_REGISTRATION_ENABLED', false),
    'bridge_secret' => env('WEBSITE_REGISTRATION_SECRET', ''),
    'resume_url' => env('WEBSITE_REGISTRATION_RESUME_URL', 'https://denentiq.com/registration'),
];
