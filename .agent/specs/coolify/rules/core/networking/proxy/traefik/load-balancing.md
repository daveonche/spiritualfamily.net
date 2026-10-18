# Core › Networking › Proxy › Traefik › Load balancing

Source: coollabsio/coolify-docs `content/docs/core/networking/proxy/traefik/load-balancing.mdx` @ main `3b7c416`.

## What it does

- A Traefik load-balancer service distributes requests for one domain across multiple upstream URLs. Upstreams can be containers on the same server or services on other servers — the `coolify-proxy` container must reach every URL.

## Prepare the upstreams

- At least two healthy upstream instances, each exposed on an address + port reachable from the Traefik container.
- Public domain points at the server running this Traefik proxy; decide whether Traefik terminates HTTPS.
- Remove any other resource route using the same hostname and path.
- Upstream addressing: never `localhost` unless the service runs inside the Traefik container; host service → `host.docker.internal:<port>`; container on a shared Docker network → container/service name + internal port.

## Add the load-balancer route

- **Servers > your server > Proxy > Dynamic Configurations** → **Add** → `app-load-balancer.yaml`.

HTTPS:

```yaml
http:
  middlewares:
    load-balancer-https-redirect:
      redirectScheme:
        scheme: https

  routers:
    app-load-balancer-http:
      rule: 'Host(`app.example.com`)'
      entryPoints:
        - http
      middlewares:
        - load-balancer-https-redirect
      service: app-load-balancer

    app-load-balancer-https:
      rule: 'Host(`app.example.com`)'
      entryPoints:
        - https
      service: app-load-balancer
      tls:
        certResolver: letsencrypt

  services:
    app-load-balancer:
      loadBalancer:
        healthCheck:
          path: /health
          interval: 10s
          timeout: 3s
        servers:
          - url: 'http://192.168.1.23:3000'
          - url: 'http://192.168.1.65:3000'
```

- Replace the domain, health-check path, and upstream URLs. Remove `healthCheck` if the application has no endpoint that returns a successful status.

HTTP only:

```yaml
http:
  routers:
    app-load-balancer:
      rule: 'Host(`app.example.com`)'
      entryPoints:
        - http
      service: app-load-balancer

  services:
    app-load-balancer:
      loadBalancer:
        servers:
          - url: 'http://192.168.1.23:3000'
          - url: 'http://192.168.1.65:3000'
```

- HTTP-only public routing only when TLS terminates at another trusted proxy or load balancer before traffic reaches Traefik.

## Verify traffic distribution

1. Save the dynamic configuration → 2. open the public domain and confirm it responds → 3. check **Proxy > Logs** for connection or configuration errors → 4. stop one upstream and confirm requests continue through a healthy upstream → 5. restore it and confirm it returns to service.

- Every request returns a gateway error → test each upstream URL from the proxy network; confirm the service listens on the documented internal port.
