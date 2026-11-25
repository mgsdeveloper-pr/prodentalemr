<?php

namespace App\Services;

use RuntimeException;

final class SystemUpdateBlocked extends RuntimeException
{
    public function __construct(public readonly string $staffMessage)
    {
        parent::__construct($staffMessage);
    }
}
