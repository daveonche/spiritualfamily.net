# Core › Networking › Proxy › Traefik › TLS certificates

Source: coollabsio/coolify-docs `content/docs/core/networking/proxy/traefik/tls-certificates.mdx` @ main `3b7c416`.

## Scope

- **Servers > [server] > Proxy > TLS Certificates** views certificates stored in Traefik's `acme.json` — this page manages Traefik ACME storage only. For certificates you provide yourself, see `custom-ssl-certs.md`.

## Delete a certificate

- Review the domains a certificate covers before deleting. Deleting the entry removes it from local ACME storage; it does NOT revoke the certificate at the certificate authority.
- After a change Coolify indicates the proxy needs a restart — restart to apply the updated storage. Traefik may need to obtain a replacement certificate for domains that still require one.

## Restore ACME storage

- Coolify backs up `acme.json` before every change and retains the last 10 backups; on the same page choose a backup to restore or delete.
- Restoring replaces the ACME storage file and can affect several certificates — review the backup before confirming, and restart the proxy when Coolify indicates it is required.
