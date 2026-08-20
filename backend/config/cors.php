<?php

// Needed once the SPA and API are deployed on two different domains
// (Vercel + Railway, see ../RAILWAY.md) — the framework's built-in
// fallback CORS defaults are broader than we want, so this is
// published explicitly rather than relied on implicitly. Auth is
// Bearer-token-based (see Modules/Auth — no cookies), so
// supports_credentials stays false and allowed_origins can safely be a
// specific list rather than '*'.

return [

    'paths' => ['api/*'],

    'allowed_methods' => ['*'],

    // CORS_ALLOWED_ORIGINS: comma-separated exact origins, e.g.
    // "https://erp.vercel.app,https://vishesh-textiles.example". Falls
    // back to FRONTEND_URL (single origin) if that's all that's set.
    'allowed_origins' => array_values(array_filter(array_map(
        'trim',
        explode(',', env('CORS_ALLOWED_ORIGINS', env('FRONTEND_URL', '')))
    ))),

    // Vercel preview deployments get a unique *.vercel.app subdomain
    // per branch/PR — allow the whole project's preview subdomain
    // pattern via CORS_ALLOWED_ORIGIN_PATTERN (e.g.
    // "^https://erp-frontend-[a-z0-9-]+\.vercel\.app$") so previews
    // aren't blocked without having to add each one by hand.
    'allowed_origins_patterns' => array_values(array_filter([
        env('CORS_ALLOWED_ORIGIN_PATTERN'),
    ])),

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
