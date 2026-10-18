# Services › Configuration › Networking

Source: coollabsio/coolify-docs `content/docs/services/configuration/networking.mdx` @ main `3b7c416`.

## The two networks

- **Service network**: private, per-Service, name based on the Service UUID; components join automatically.
- **Coolify network**: shared attachable network named `coolify` per server; used by Applications, standalone Databases, the Coolify Proxy, and Services with **Connect To Predefined Network**. Same-server private communication without host ports; never cross-server.
- **Connect To Predefined Network** attaches exactly ONE network: the predefined destination selected for the Service (default `coolify`) — not every network on the server.

## Same Service

- Use the Compose `services:` key as hostname + the internal listening port (`database:5432`). Never `localhost` (that is the calling container itself), a public domain, or a published host port. No `ports:`/domain needed. `depends_on` orders startup only — the caller must still tolerate slow readiness.

## Coolify network (cross-resource, same server)

1. Both resources on the same server → 2. other resource attached to `coolify` → 3. Service's predefined destination is `coolify` → 4. **Configuration > General** → 5. enable **Connect To Predefined Network** → 6. restart/deploy → 7. use the other resource's internal hostname + port → 8. test from the calling component.

- Coolify adds an alias of Compose service name + Service UUID; use the deployed container name/alias shown by Coolify — never assume the unsuffixed service name is unique outside the stack. Standalone Database: use its **Internal URL**.
- Domains do NOT require this setting: the proxy joins the Service network when the stack starts. Enable it for private cross-resource communication only.

## Other Docker network

- Separate networks cannot resolve each other by container name. Options: (1) one shared network — clone the Service to the required destination and migrate data separately; (2) a domain/routed endpoint — keeps isolation, good for HTTP; (3) an external Compose network — `external: true` + `name:`; Coolify does not create or manage it, it must exist on the server, deleting/renaming breaks the connection, and it does not change the predefined destination in the dashboard.

## Other server

- Docker bridge networks and container DNS are host-local. Use a routable path: HTTP(S) domain via the proxy, provider private addresses, VPN/overlay (WireGuard, Tailscale), secured TCP endpoint, or an app-level API/tunnel/broker. The receiver must listen on an address reachable through that path; restrict firewalls to required source/destination/port; add TLS + auth when traffic leaves a trusted network. NEVER use a container name as a cross-server hostname.

## Public access

- **Domains**: set on the application component; include the internal port when ≠ 80 (`https://api.example.com:3000`) — visitors still use standard HTTPS, the suffix selects the container port for the proxy. No Compose `ports:` needed; DNS must point at the server and the proxy must run.
- **Host ports**: `ports: ["8080:3000"]` publishes on all server interfaces and bypasses proxy routing — only for external direct TCP/UDP clients; restrict with firewalls; loopback-only: `"127.0.0.1:8080:3000"` (reachable only from the server itself).

## Verify and boundaries

- After changes: save + restart/deploy → review **Show Deployable Compose** for networks/ports → resolve the hostname from the calling component's **Terminal** → connect and verify an application-level response → for domains, external HTTPS request; for ports, confirm blocked from networks that should not reach them.
- Boundaries: project/environment is organization, not networking; a separate `coolify` network exists per server; Service names resolve only on shared networks; domains route traffic but do not merge networks; published ports expose server interfaces and need firewall + protocol security.
