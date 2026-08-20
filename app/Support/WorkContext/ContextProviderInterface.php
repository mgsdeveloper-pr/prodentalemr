<?php

namespace App\Support\WorkContext;

interface ContextProviderInterface
{
    public function context(): WorkContext;
}
