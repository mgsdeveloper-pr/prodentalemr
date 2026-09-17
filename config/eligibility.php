<?php

return [
    // Populate only from Zuub's partner documentation. These are not guessed API defaults.
    'zuub' => [
        'sandbox' => [
            'probe_url' => null,
            'approved_host' => null,
            'credential_header' => null,
            'credential_prefix' => '',
            'probe_documented_safe' => false,
        ],
        'production' => [
            'probe_url' => null,
            'approved_host' => null,
            'credential_header' => null,
            'credential_prefix' => '',
            'probe_documented_safe' => false,
        ],
    ],
];
