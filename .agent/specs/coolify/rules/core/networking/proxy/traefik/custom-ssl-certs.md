# Core › Networking › Proxy › Traefik › Custom SSL certificates

Source: coollabsio/coolify-docs `content/docs/core/networking/proxy/traefik/custom-ssl-certs.mdx` @ main `3b7c416`.

## When

- Traefik normally obtains certificates through the `letsencrypt` resolver; use a custom certificate when a private CA, an organization-managed certificate, or another workflow must terminate TLS at Traefik.
- The certificate + private key must exist on EVERY server whose Traefik serves the domain.

## Prepare the files

- PEM-encoded certificate including required intermediates; matching UNENCRYPTED private key.
- Copy over an encrypted connection, restrict permissions, never commit to a repository or paste into the Coolify dashboard.
- `sudo install -d -m 700 /data/coolify/proxy/certs`; copy both files (e.g. `scp /path/example.com.crt root@<server>:/data/coolify/proxy/certs/example.com.crt` + the `.key`); `sudo chmod 600 /data/coolify/proxy/certs/example.com.key`.
- Coolify mounts `/data/coolify/proxy/` at `/traefik/` inside the Traefik container.

## Add the dynamic TLS configuration

- **Servers > server > Proxy > Dynamic Configurations** → **Add** → e.g. `custom-certificates.yaml`:
  - `tls.certificates`: `- certFile: /traefik/certs/example.com.crt`, `keyFile: /traefik/certs/example.com.key`
- Save. Traefik watches the dynamic configuration directory and selects a matching certificate by subject alternative names when a request arrives.

## Verify

- Open the HTTPS domain; inspect issuer, SANs, expiration. Terminal check: `openssl s_client -connect example.com:443 -servername example.com </dev/null 2>/dev/null | openssl x509 -noout -subject -issuer -dates`.
- If Traefik serves another certificate: confirm the certificate covers the requested hostname; inspect **Proxy > Logs** for file/key parsing errors.
