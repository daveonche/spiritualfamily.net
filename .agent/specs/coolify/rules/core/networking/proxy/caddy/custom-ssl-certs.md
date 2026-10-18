# Core › Networking › Proxy › Caddy › Custom SSL certificates

Source: coollabsio/coolify-docs `content/docs/core/networking/proxy/caddy/custom-ssl-certs.mdx` @ main `3b7c416`.

## When

- Caddy normally obtains certificates automatically; use custom certificates when your organization or another certificate provider manages TLS. Caddy supports `tls <certificate-file> <key-file>`; Coolify uses caddy-docker-proxy, so add the equivalent label to the resource's existing Caddy route.

## Prepare the files (every server whose Caddy serves the domain)

- PEM-encoded certificate including required intermediates, SANs covering the domain; matching UNENCRYPTED private key.
- `sudo install -d -m 700 /data/coolify/proxy/caddy/certs`; copy files up (e.g. `scp example.com.crt root@<server>:/data/coolify/proxy/caddy/certs/`); `sudo chmod 600 /data/coolify/proxy/caddy/certs/example.com.key`.
- Transfer the key over an encrypted connection; never commit it to Git or paste it into the dashboard; a non-root Caddy user must read the files without making the key publicly readable.

## Mount into Caddy

- **Servers > server > Proxy > Configuration** → add to the existing `volumes` of the `caddy` service: `- /data/coolify/proxy/caddy/certs:/certs:ro` → **Save changes** → **Restart Proxy**; Caddy reads `/certs/example.com.crt` + `/certs/example.com.key` inside the container.

## Configure the resource

- Configure the HTTPS domain (e.g. `https://example.com`) BEFORE adding the certificate label.
- Standard app: **General > Container labels** → **Managed manually** → keep the generated routing labels and add `caddy_0.tls=/certs/example.com.crt /certs/example.com.key`. Use the prefix of the EXISTING HTTPS domain label (`caddy_1.tls` when `caddy_1=https://example.com`); REPLACE an existing `tls` value for that route — never add a second one.
- Docker Compose: add the same label to the service receiving traffic, keeping its routing labels.

## Verify and renew

- Open the HTTPS domain; inspect issuer, hostname coverage, expiration; check **Proxy > Logs** when Caddy cannot read the files or keeps serving another certificate.
- Caddy does NOT auto-renew supplied certificates: replace cert + key before expiration, then restart the proxy; private-CA certificates also require clients to trust that CA.
