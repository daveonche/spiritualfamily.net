# Applications › Sources › Deploy keys

Source: coollabsio/coolify-docs `content/docs/applications/sources/deploy-keys.mdx` @ main `3b7c416`.

## What a deploy key is

- One SSH key → one private repository, read-only (clone + fetch only). Use when a Git App cannot be installed or repo-specific access is preferred.

## Create and attach

1. **Keys & Tokens > Private Keys** > **New private key** > **Generate ED25519** or **Generate RSA** (saved immediately); rename via the edit icon to identify the repo; copy **Public key**.
2. Add the public key in the repo's deploy-key/access-key settings; keep write access disabled.
3. Copy the provider's SSH clone URL (`git@github.com:coollabsio/coolify-examples.git`) — never the HTTPS URL for **Private Repository (with deploy key)**.
4. Project > **+ New** > **Private Repository (with deploy key)**; choose server, select the stored key, paste the SSH URL, configure.
5. **Deploy**; confirm clone + requested branch succeed in the deployment log.

## Automatic deployment

- The SSH key provides repository access only. Either a manual Git webhook + **Advanced > Deployment > Auto deploy** → **Deploy on push (webhooks)**, or the authenticated **Deploy Webhook** called from CI after checks pass.
- GitHub: prefer a GitHub App for repo selection + webhook configuration through one integration.

## Key scope and rotation

- Prefer a separate deploy key per repository. If exposed: remove from repo and Coolify, create a replacement, attach under **Git Source**, redeploy.
