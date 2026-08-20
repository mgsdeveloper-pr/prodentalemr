<?php

return [
    /*
    | Public tenant registration is intentionally closed until the complete
    | plan, agreement, verification, and approval workflow is released.
    */
    'public_registration' => (bool) env('PRODENTAL_PUBLIC_REGISTRATION', false),
];
