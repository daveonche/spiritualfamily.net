# Core › Security › Authentication › SSO › Clerk

Source: coollabsio/coolify-docs `content/docs/core/security/authentication/sso/clerk.mdx` @ main `3b7c416`.

## Requirements

- Coolify v4.3.0+.
- Example values: Coolify callback `https://coolify.shadowarcanist.com/auth/clerk/callback`; Clerk base URL `https://shadowarcanist.clerk.accounts.dev`.

## Create the OAuth application

- Clerk Dashboard (for the Clerk instance authenticating Coolify users) > **OAuth applications** > **Add OAuth application**: name e.g. `Coolify`; include the `email` and `profile` scopes. Add `openid` only when another client needs OIDC — Coolify's Clerk integration requests `email` and `profile`.
- **Add**. See Clerk's OAuth application guide when dashboard navigation differs.

## Save the credentials

- Copy the **Client Secret** from the creation modal and store it securely — Clerk does not show the complete secret again.
- Copy **Client ID** from the OAuth application's settings page.

## Register the Coolify redirect

- Under **Redirect URIs** add `https://coolify.shadowarcanist.com/auth/clerk/callback` → save.
- Use Clerk's user and organization controls to decide who may authenticate through the OAuth application.

## Copy the Clerk Base URL

- **API Keys** in the Clerk Dashboard → copy the instance's **Frontend API URL**. Development instance: `https://shadowarcanist.clerk.accounts.dev`. Production may use a custom Clerk domain — use the origin shown by Clerk WITHOUT `/oauth/authorize`, `/oauth/token`, or another path.

## Configure in Coolify

- **Settings > Authentication > Clerk**: **Client ID** = Clerk OAuth application client ID; **Client Secret** = client secret; **Redirect URI** = `https://coolify.shadowarcanist.com/auth/clerk/callback`; **Base URL** = Clerk Frontend API URL.
- **Save** → **Enabled**.

## Verify

- Coolify login page in a private browser window → **Login with Clerk** → complete Clerk authentication → the account matching the primary Clerk email address opens.

## Troubleshoot

- Clerk rejects the redirect URI → the OAuth application lists the exact public Coolify callback under **Redirect URIs**.
- Coolify cannot start Clerk authentication → **Base URL** is the Clerk Frontend API origin — not `dashboard.clerk.com`, not a URL with `/oauth/authorize` appended.
- Coolify returns to the login page → the OAuth application grants `email` and `profile`; the Clerk user has a primary email address; the client secret in Coolify is current.
