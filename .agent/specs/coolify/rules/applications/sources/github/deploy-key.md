# Applications › Sources › GitHub › Deploy key

Source: coollabsio/coolify-docs `content/docs/applications/sources/github/deploy-key.mdx` @ main `3b7c416`.

## When

- Read-only SSH access to ONE private repository without a personal access token or account-level SSH key. Use when Coolify should access only that repo, a GitHub App cannot be installed in the org, or repo-specific read-only access is preferred. Use a GitHub App for integrated access to several repositories.

## Create the key

- **Keys & Tokens > Private Keys > New private key** → **Generate RSA** or **Generate ED25519** (saved immediately); Edit icon → copy **Public key**.
- Or generate outside Coolify: `ssh-keygen -t rsa -b 4096 -C "coolify-deploy-key"` — paste the private key into Coolify, use the matching `.pub` on GitHub.

## Attach and deploy

1. GitHub repo settings > **Deploy keys > Add deploy key**: descriptive title, paste the public key, keep **Allow write access** DISABLED.
2. Copy the SSH clone URL (repo > **Code > Local > SSH**, e.g. `git@github.com:OWNER/REPO.git`).
3. Project > **+ New > Private Repository (with Deploy Key)**; choose the server (self-hosted defaults to **localhost** when no other server exists); select the key; paste the SSH URL.
4. Configure branch, **Build pack**, internal port; deploy; confirm the private repo + requested commit clone in **Deployment Logs**.
5. Push-triggered deployments: follow the repository-webhook path in `auto-deploy.md`.
