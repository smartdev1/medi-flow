<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Str;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    protected function initialsFor(string $name): string
    {
        return collect(explode(' ', $name))
            ->filter()
            ->map(fn (string $part) => Str::upper($part[0]))
            ->take(2)
            ->implode('');
    }
}
