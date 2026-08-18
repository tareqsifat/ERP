<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * failed_doc.md §1: "There is no server-side check that a Passport token
 * actually belongs to an active, non-deleted user before honoring it."
 *
 * Passport's TokenGuard already refuses to resolve a soft-deleted user
 * (Eloquent's default query excludes them). What it does NOT do on its
 * own is re-check `is_active` on every request — without this middleware,
 * an Admin deactivating a user mid-session would have no effect until
 * that user's access token happened to expire naturally (up to 1 hour,
 * per AppServiceProvider::boot()). This closes that gap globally instead
 * of relying on every module route to remember it individually.
 *
 * Runs on every request (registered in the global "api" middleware
 * group) and deliberately no-ops for unauthenticated requests — it only
 * ever blocks a request that resolved to a real, but deactivated, user.
 */
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->is_active) {
            abort(401, 'This account has been deactivated.');
        }

        return $next($request);
    }
}
