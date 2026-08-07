<?php

namespace App\Http\Middleware;

use App\Enums\Role;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Handle an incoming request.
     *
     * L'administrateur système a une visibilité de supervision sur tous les
     * écrans (voir gestion roles perimetre activite.md), il est donc toujours
     * autorisé sans avoir à être listé explicitement sur chaque route.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        abort_unless($user && ($user->role === Role::Admin || $user->hasRole(...$roles)), 403);

        return $next($request);
    }
}
