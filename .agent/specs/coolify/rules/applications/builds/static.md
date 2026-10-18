# Applications › Builds › Static

Source: coollabsio/coolify-docs `content/docs/applications/builds/static.mdx` @ main `3b7c416`.

- The **Static** build pack packages already-committed files into an Nginx image; it runs no build command. If `npm run build` (or similar) is needed first → Nixpacks/Railpack with **Static** site type + **Publish Directory**, or a Dockerfile.
- **Base Directory** = directory containing the finished files (`/` for repo root, e.g. `/dist` for a subdirectory).
- Web server is Nginx with a Coolify-generated default config; edit under **General** only for SPA fallback, custom headers, or caching rules.
- The Nginx config is copied into the image at build time: save + **redeploy**; a restart reusing the existing image cannot apply config changes.
- Verify after deploy: a document, an asset, and a client-side route.
