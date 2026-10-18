# Core › Security › Authentication › SSO › Zitadel

Source: coollabsio/coolify-docs `content/docs/core/security/authentication/sso/zitadel.mdx` @ main `3b7c416`.

## Requirements

- Coolify v4.3.0+.
- Example values: Coolify callback `https://coolify.shadowarcanist.com/auth/zitadel/callback`; Zitadel base URL `https://id.shadowarcanist.com`.

## Create or select the project

- Zitadel Console > the organization owning the integration > create or open a project for Coolify.
- Use project roles and user grants when only selected organization members should access Coolify. See Zitadel's OIDC login guide when Console navigation differs.

## Add the web application

- Project > **New Application**: name e.g. `Coolify`, type **Web**.
- Authorization code flow with a client secret; token endpoint authentication method **POST** (client ID + secret sent with the token request).

## Register the callback URL

- Redirect URI: `https://coolify.shadowarcanist.com/auth/zitadel/callback`.
- Keep development mode DISABLED for a production HTTPS domain → finish creating the application.
- Coolify requests the `openid`, `profile`, and `email` scopes.

## Save the credentials

- Copy the generated **Client ID** + **Client Secret**; store the secret securely.
- **Base URL** = the Zitadel instance origin (`https://id.shadowarcanist.com`). Do NOT append `/oauth/v2/authorize`, `/oauth/v2/token`, or `/oidc/v1/userinfo`.

## Configure in Coolify

- **Settings > Authentication > Zitadel**: **Client ID** = web application client ID; **Client Secret** = client secret; **Redirect URI** = `https://coolify.shadowarcanist.com/auth/zitadel/callback`; **Base URL** = Zitadel instance origin.
- **Save** → **Enabled**.
- WARNING: Coolify may allow the provider card to be enabled before **Base URL** is filled — Zitadel sign-in cannot work without it, because Coolify builds the authorization, token, and userinfo endpoints from this value.

## Verify

- Coolify login page in a private browser window → **Login with Zitadel** → complete authentication → the account matching the Zitadel email address opens.
- Test a user without the required project grant or provider access policy and confirm Zitadel blocks access.

## Troubleshoot

- Zitadel reports an invalid redirect URI → the web application's redirect URI exactly matches the public Coolify domain + `/auth/zitadel/callback` path.
- The token exchange fails → the web application uses a client secret + the **POST** token endpoint authentication method; the client secret in Coolify has not been rotated.
- Coolify cannot reach Zitadel → **Base URL** is the Zitadel instance origin and reachable from the Coolify server; no console page or OAuth endpoint path.
