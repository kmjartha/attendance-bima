<?php

namespace App\Middleware;

class HrdOnly extends RoleMiddleware
{
    protected array $allowed = ['HRD'];
}
