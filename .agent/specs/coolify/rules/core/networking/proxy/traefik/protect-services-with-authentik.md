# Core › Networking › Proxy › Traefik › Protect with Authentik

Source: coollabsio/coolify-docs `content/docs/core/networking/proxy/traefik/protect-services-with-authentik.mdx` @ main `3b7c416`.

## How it works

- Traefik's `forwardAuth` middleware sends each request to an Authentik proxy provider before it reaches the application; Authentik handles sign-in and returns identity headers to Traefik after successful authentication.
- Use when Authentik and the protected resource communicate over a shared Docker network on the same Coolify server.

## Configure Authentik

- Deploy the [Authentik service](/services/authentik), then follow Authentik's forward authentication documentation to: create a proxy provider using forward authentication; create an Authentik application and attach the provider; set the provider's cookie domain to the parent domain shared by the protected services for SSO across subdomains; add the protected domain to the application/provider configuration.
- The Authentik server container and the protected resource must share a Docker network Traefik can use to reach Authentik.

## Create the middleware

- Find the Authentik server container/service name on the shared network (example: `authentik-server`, port `9000`).
- **Servers > your server > Proxy > Dynamic Configurations** → `authentik-forward-auth.yaml`:

```yaml
http:
  middlewares:
    authentik-forward-auth:
      forwardAuth:
        address: 'http://authentik-server:9000/outpost.goauthentik.io/auth/traefik'
        trustForwardHeader: true
        authResponseHeaders:
          - X-authentik-username
          - X-authentik-groups
          - X-authentik-entitlements
          - X-authentik-email
          - X-authentik-name
          - X-authentik-uid
          - X-authentik-jwt
          - X-authentik-meta-jwks
          - X-authentik-meta-outpost
          - X-authentik-meta-provider
          - X-authentik-meta-app
          - X-authentik-meta-version
```

- Replace `authentik-server` with the actual internal hostname. Do NOT use the public Authentik URL unless the proxy cannot reach Authentik privately and you intentionally want the external traffic path.

## Attach the middleware

- Standard application: **General > Container labels** → **Label management** → **Managed manually (edit labels yourself)** → append to the existing HTTPS router:
  - `traefik.http.routers.https-0-<resource-id>.middlewares=gzip` → `gzip,authentik-forward-auth@file`
  - Save and redeploy.
- Docker Compose or service: add the Coolify shorthand to the service receiving the domain: `coolify.traefik.middlewares=authentik-forward-auth@file`. Save and redeploy.

## Verify

- Open the protected domain in a private browser window: Traefik redirects to Authentik, then returns to the application after sign-in.

## Troubleshoot

- Gateway error / 500 → confirm the Authentik internal hostname + port from the shared Docker network; inspect Authentik logs and **Proxy > Logs** for connection errors.
- No redirect to Authentik → confirm the proxy provider is assigned to the correct Authentik application and `authentik-forward-auth@file` is attached to the resource's HTTPS router.
- Loops back to the sign-in page → confirm the provider's cookie domain covers the protected hostname and the protected domain is assigned to the expected application.

## Recovery path

- A broken forward-auth route can lock every user out of the protected application. Keep Coolify dashboard access on a separate domain and know how to remove the middleware label before protecting administrative services.
