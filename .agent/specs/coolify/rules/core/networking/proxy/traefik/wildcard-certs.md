# Core › Networking › Proxy › Traefik › Wildcard certificates

Source: coollabsio/coolify-docs `content/docs/core/networking/proxy/traefik/wildcard-certs.mdx` @ main `3b7c416`.

## Coverage

- `*.example.com` covers first-level subdomains (`app.example.com`, `api.example.com`); NOT the apex `example.com` or deeper names (`www.app.example.com`) unless included separately.
- Traefik can request wildcard certificates only through the DNS challenge (see `dns-challenge.md`).

## What you need

- Traefik DNS Challenge configured and working; an apex DNS record for `example.com` when the apex should resolve; a wildcard DNS record `*.example.com` pointing at the server or upstream traffic endpoint.

## Request the certificate

- **Servers > your server > Proxy > Dynamic Configurations** → `wildcard-example-com.yaml`:

```yaml
http:
  routers:
    wildcard-example-com:
      rule: 'HostRegexp(`[a-z0-9-]+\.example\.com`)'
      entryPoints:
        - https
      priority: 1
      service: wildcard-placeholder
      tls:
        certResolver: letsencrypt
        domains:
          - main: example.com
            sans:
              - '*.example.com'

  services:
    wildcard-placeholder:
      loadBalancer:
        servers: []
```

- Replace `example.com`, then save. The low-priority router requests the certificate; resource-specific routers keep handling matching application traffic.
- Restarting Traefik is normally not required for this dynamic file; inspect **Proxy > Logs** until the ACME request succeeds.

## Use the certificate

- **Individual resource domains**: set a normal HTTPS domain (`https://app.example.com`) on each application/service — Coolify generates the resource router and Traefik selects the matching wildcard certificate from its store. Redeploy each changed resource; verify the presented certificate.
- **One application for every subdomain** (only when one application handles arbitrary tenant subdomains): keep the wildcard DNS + certificate configuration; set **Label management** → **Managed manually**; match the Traefik image version shown in **Proxy > Configuration**.
  - Traefik v3: `tenant-http` rule `HostRegexp(`[a-z0-9-]+\.example\.com`)` + entryPoints `http` + middlewares `redirect-to-https`; `tenant-https` same rule + entryPoints `https` + `tls=true` + `tls.certresolver=letsencrypt` + `service=tenant-service`; `traefik.http.services.tenant-service.loadbalancer.server.port=<application-port>`.
  - Traefik v2: rule `HostRegexp(`{subdomain:[a-zA-Z0-9-]+}.example.com`)` instead.
- `letsencrypt` must match the certificate resolver name in the proxy configuration; `<application-port>` = the port the application listens on inside its container.
- Redeploy the application and test at least two subdomains.

## Manual labels are your responsibility

- After switching to manual label management Coolify no longer repairs the application labels automatically; use **Reset to defaults** to recover the generated route if the application becomes unreachable.
