# Core › Security › Authentication › OAuth › GitHub

Source: coollabsio/coolify-docs `content/docs/core/security/authentication/oauth/github.mdx` @ main `3b7c416`.

## Open OAuth Apps

- [GitHub Developer settings](https://github.com/settings/developers) > **OAuth Apps** > **New OAuth App**.
- Organization-owned credential: create the OAuth App in that organization's settings instead; requires permission to manage OAuth Apps for the account/org.

## Register the application

| GitHub field | Value |
| :--- | :--- |
| **Application name** | Recognizable name, e.g. `Coolify` |
| **Homepage URL** | The public Coolify URL |
| **Application description** | Optional |
| **Authorization callback URL** | `https://<coolify-domain>/auth/github/callback` |

- **Register application**.
- One callback URL per OAuth App: create another OAuth App when a second Coolify instance uses a different public domain.

## Create the client secret

- Copy **Client ID** → **Generate a new client secret** → copy and store securely (Coolify needs the complete secret).

## Configure in Coolify

- **Settings > Authentication > GitHub**: **Client ID**, **Client Secret**, **Redirect URI** = `https://<coolify-domain>/auth/github/callback`.
- **Save** → **Enabled**.

## Verify

- Login page in a private browser window → **Login with GitHub** → authorize the OAuth App if asked → the account with the same email as the VERIFIED primary GitHub email opens.

## Troubleshoot

- Redirect URI mismatch → scheme, domain, `/auth/github/callback` path, and trailing slash must match between GitHub and Coolify.
- Returns to the login page → GitHub must return a verified primary email: confirm it under GitHub **Settings > Emails** and that the authorization grants `user:email`.
