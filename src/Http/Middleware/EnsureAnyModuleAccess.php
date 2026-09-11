<?php

namespace Trench94\Puppr\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Trench94\Puppr\Exceptions\ModuleAccessDeniedException;

/**
 * Require the authenticated user to have access to ANY of the listed modules.
 *
 *     Route::middleware('module.any:billing,reports')
 */
class EnsureAnyModuleAccess extends EnsureModuleAccess
{
    /**
     * @throws ModuleAccessDeniedException
     */
    public function handle(Request $request, Closure $next, string ...$modules): Response
    {
        $modules = $this->normalise($modules);

        $this->puppr->authorizeAny($request->user(), $modules);

        return $next($request);
    }
}
