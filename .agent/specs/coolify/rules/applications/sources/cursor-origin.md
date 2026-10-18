# Applications › Sources › Cursor Origin

Source: coollabsio/coolify-docs `content/docs/applications/sources/cursor-origin.mdx` @ main `3b7c416`.

- Deploys a private [Cursor Origin](https://cursor.com/origin) repository over SSH: private key stored in Coolify, matching SSH key added to Origin, app created with the deploy-key source.

## Setup

1. **Keys & Tokens > Private Keys** > **New private key**: **Generate ED25519**/**Generate RSA** (saved immediately), or **Add manually** + paste the complete private key + **Continue**. Edit icon inspects the public key / renames; **Save changes** after editing.
2. From the repository directory: `origin ssh-key add` — follow the CLI prompts; add the SSH key corresponding to the Coolify-stored private key. Origin must use the SAME key.
3. Project > **+ New** > **Private Repository (with deploy key)**; choose the server; select the key from step 1; enter the SSH clone URL; configure; deploy.

## Gotcha

- The deploy key authenticates an SSH connection; an HTTPS repository URL will not use it.
