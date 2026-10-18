# Core › Networking › Proxy › Traefik › DNS challenge

Source: coollabsio/coolify-docs `content/docs/core/networking/proxy/traefik/dns-challenge.mdx` @ main `3b7c416`.

## When and requirements

- Default Traefik config uses the ACME HTTP challenge (public port 80 required). Switch to the DNS challenge for wildcard certificates or when port 80 cannot be made reachable during validation.
- Traefik's Lego integration creates a temporary `_acme-challenge` TXT record, waits for DNS validation, removes it after issuance.
- Needs: a domain hosted by a [Lego-supported DNS provider](https://go-acme.github.io/lego/dns/) + provider credentials that create/delete DNS records for the zone.
- Use a RESTRICTED credential: token limited to the DNS zone/record permissions Traefik needs; never reuse an account-wide key when scoped tokens are supported.

## Change the Traefik configuration

- **Servers > server > Proxy > Configuration** — edit the main configuration in Coolify: disk edits to `/data/coolify/proxy/docker-compose.yml` are not synced back once saved, and restarting the proxy through Coolify overwrites disk-only edits.
- Keep the existing Docker Compose configuration; inside the `traefik` service make two changes:
  1. Add the provider credentials under `environment`: Cloudflare `CF_DNS_API_TOKEN`; Hetzner `HETZNER_API_TOKEN`; Route 53 `AWS_ACCESS_KEY_ID` + `AWS_SECRET_ACCESS_KEY` + `AWS_REGION`; Hostinger `HOSTINGER_API_TOKEN`.
  2. Remove `--certificatesresolvers.letsencrypt.acme.httpchallenge=true` + `--certificatesresolvers.letsencrypt.acme.httpchallenge.entrypoint=http`; add `--certificatesresolvers.letsencrypt.acme.dnschallenge.provider=<cloudflare|hetzner|route53|hostinger>` + `--certificatesresolvers.letsencrypt.acme.dnschallenge.delaybeforecheck=0`; keep `--certificatesresolvers.letsencrypt.acme.storage=/traefik/acme.json`.
- Other providers: use the identifier + environment variables from the Lego DNS provider documentation.

## Restart and verify

- Save the proxy configuration → **Restart Proxy**. Redeploy one resource with an HTTPS domain to trigger certificate issuance.
- Verify the domain serves a valid certificate; inspect **Servers > server > Proxy > Logs** for ACME messages.

## Troubleshoot

- TXT record not visible in time → increase the propagation delay: `delaybeforecheck=30`.
- CNAME delegation fails → some integrations follow CNAME records for `_acme-challenge`; add environment `LEGO_DISABLE_CNAME_SUPPORT=true` when the delegated setup requires disabling that.
- Let's Encrypt rate limits block testing → temporarily add `--certificatesresolvers.letsencrypt.acme.caserver=https://acme-staging-v02.api.letsencrypt.org/directory`, restart, test; REMOVE after — staging certificates are not browser-trusted.
