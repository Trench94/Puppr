<?php

namespace Trench94\Puppr\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Trench94\Puppr\Exceptions\ModuleAccessDeniedException;
use Trench94\Puppr\Puppr;

/**
 * Require the authenticated user to have access to ALL of the listed modules.
 *
 *     Route::middleware('module:billing,reports')
 */
class EnsureModuleAccess
{
    public function __construct(protected Puppr $puppr)
    {
    }

    /**
     * @throws ModuleAccessDeniedException
     */
    public function handle(Request $request, Closure $next, string ...$modules): Response
    {
        $modules = $this->normalise($modules);

        $this->puppr->authorize($request->user(), $modules);

        return $next($request);
    }

    /**
     * Allow modules to be separated by commas or pipes.
     *
     * @param  array<int, string>  $modules
     * @return array<int, string>
     */
    protected function normalise(array $modules): array
    {
        $normalised = [];

        foreach ($modules as $module) {
            foreach (preg_split('/[,|]/', $module) ?: [] as $part) {
                if (trim($part) !== '') {
                    $normalised[] = trim($part);
                }
            }
        }

        return $normalised;
    }

    /**
     * Build the middleware string for route definitions.
     */
    public static function using(string ...$modules): string
    {
        return static::class.':'.implode(',', $modules);
    }
}
