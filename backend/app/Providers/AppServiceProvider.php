<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Every model in this codebase declares its writable columns via
        // #[Fillable(...)] (never $guarded = []) — Eloquent's DEFAULT
        // behavior when a mass-assigned key isn't fillable is to silently
        // drop it, not throw. That default almost bit us for real: Phase 4's
        // Bundle/PieceSerial models were briefly built with no #[Fillable]
        // at all (client-facing writes are intentionally blocked — see
        // Modules/Production/README.md), which would have made
        // create()/fill() calls from their own Services silently produce
        // empty rows instead of erroring. Caught before it shipped, but the
        // failure mode is exactly what failed_doc.md warns about, so: make
        // it loud everywhere, permanently, not just where we happened to
        // notice.
        Model::preventSilentlyDiscardingAttributes(true);

        // The Password Grant is opt-in as of Passport 13 — without this,
        // Modules/Auth's AuthController would get a valid-looking 400
        // from /oauth/token no matter how correct the credentials are.
        Passport::enablePasswordGrant();

        // sdd.md §4: short-lived access tokens, longer-lived refresh
        // tokens. failed_doc.md §1: this is the line that keeps token
        // lifetime from being "no expiry" / excessively long.
        Passport::tokensExpireIn(now()->addHour());
        Passport::refreshTokensExpireIn(now()->addDays(14));
        Passport::personalAccessTokensExpireIn(now()->addMonths(6));

        // failed_doc.md §1 & §10: throttle login attempts and, more
        // generally, cap every unauthenticated/authenticated request rate
        // so a single client can't credential-stuff or hammer write
        // endpoints. Per-module routes opt into 'throttle:login' or
        // 'throttle:api' as appropriate (see Modules/Auth/routes/api.php).
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip().'|'.$request->input('email', ''));
        });

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(120)->by($request->user()?->id ?: $request->ip());
        });
    }
}
