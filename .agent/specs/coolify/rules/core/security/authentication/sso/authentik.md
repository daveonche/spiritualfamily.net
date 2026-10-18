# Core › Security › Authentication › SSO › Authentik

Source: coollabsio/coolify-docs `content/docs/core/security/authentication/sso/authentik.mdx` @ main `3b7c416`.

## Requirements

- Coolify v4.3.0+.
- Example values used by this guide: Coolify callback `https://coolify.shadowarcanist.com/auth/authentik/callback`; Authentik base URL `https://auth.shadowarcanist.com`; application name `Coolify`.

## Create the provider and application

- Authentik admin interface > **Applications > Applications** > **Create with Provider**: name `Coolify`, choose a slug, provider type **OAuth2/OpenID Connect**.
- Older Authentik versions creating providers separately: **Applications > Providers** > create an **OAuth2/OpenID Provider**, then attach it to a new application.

## Configure the provider (confidential authorization-code client)

| Authentik setting | Value |
| :--- | :--- |
| **Client type** | **Confidential** |
| **Redirect URIs/Origins** | Strict URI `https://coolify.shadowarcanist.com/auth/authentik/callback` |
| **Scopes** | `openid`, `profile`, `email`; Coolify also requests Authentik's `goauthentik.io/api` scope |
| **Authorization flow** | The flow your users should complete |
| **Signing key** | The signing key required by your Authentik policy |

- Authentik 2025.10+: linking an existing Coolify account requires the email scope to return `email_verified: true`. Under **Customization > Property Mappings**, add a scope mapping for `email` with the expression `return {"email": request.user.email, "email_verified": True}` and use it on the provider instead of the default email mapping. Only assert verification for users whose email addresses your organization trusts.
- Save the provider and application.
- Restrict who may access the Coolify application with Authentik policies, groups, or application bindings.

## Save the credentials

- Copy the provider **Client ID** + **Client Secret**; store the secret securely.
- **Base URL** = the Authentik instance origin (`https://auth.shadowarcanist.com`). Do NOT append `/application/o/authorize/` or the application slug.

## Configure in Coolify

- **Settings > Authentication > Authentik**: **Client ID** = provider client ID; **Client Secret** = provider secret; **Redirect URI** = `https://coolify.shadowarcanist.com/auth/authentik/callback`; **Base URL** = Authentik origin.
- **Save** → **Enabled**.

## Verify

- Coolify login page in a private browser window → **Login with Authentik** → complete the assigned flow → the account matching the Authentik email opens.
- Also test an Authentik user NOT allowed by the application policy and confirm Authentik blocks access.

## Troubleshoot

- Authentik reports an invalid redirect URI → compare the provider's strict redirect URI with the Coolify **Redirect URI**; no regex unless your Authentik policy requires one.
- Coolify cannot reach Authentik → **Base URL** contains only the public Authentik origin and is reachable from the Coolify server; authorization, token, and userinfo live below `/application/o/`.
- Coolify returns to the login page → the provider includes the `email` scope and returns an email address; the application policy permits that user.
