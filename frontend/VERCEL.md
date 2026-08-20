# Deploying to Vercel

Static Vite build, deployed as-is — `vercel.json` in this repo sets the
build command, output directory, and an SPA rewrite (Vue Router runs in
`history` mode, so every path needs to fall back to `index.html`).

## 1. Import the project

New Project → Import Git Repository → this repo (`ERP-frontend`).
Vercel auto-detects the Vite framework preset; `vercel.json` pins the
same settings explicitly so it doesn't depend on that autodetection.

## 2. Required environment variable

```
VITE_API_BASE_URL=https://<your-backend>.up.railway.app/api/v1
```

This is read at *build* time (`import.meta.env.VITE_API_BASE_URL` in
`src/shared/api/axios.js`), not runtime — set it in Vercel's Project
Settings → Environment Variables for the Production (and Preview, if
you want previews to hit the same live API) environment before the
first deploy, and redeploy after changing it.

## 3. CORS

The backend must list this frontend's exact deployed origin in its own
`CORS_ALLOWED_ORIGINS` (or `CORS_ALLOWED_ORIGIN_PATTERN` for preview
URLs) — see `../backend/RAILWAY.md` §2. Without that, every API call
from this frontend will fail as a CORS error in the browser console,
even though the API itself is reachable.

## 4. Build/output

Already wired via `vercel.json`:

```json
{
  "buildCommand": "npm run build",
  "outputDirectory": "dist",
  "framework": "vite",
  "rewrites": [{ "source": "/(.*)", "destination": "/index.html" }]
}
```

`npm run build` runs `vite build` per `package.json` — no server-side
rendering, this is a pure static SPA served from Vercel's CDN.
