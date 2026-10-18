# Core › Security › Authentication › SSO › OpenID Connect

Source: coollabsio/coolify-docs `content/docs/core/security/authentication/sso/oidc.mdx` @ main `3b7c416`.

## Configure the provider (root team administrator or owner)

1. Create a confidential web application at your identity provider.
2. Copy the **Redirect URI** displayed by Coolify into the provider's allowed redirect URIs — exact match including scheme and path.
3. Enter **Issuer URL**, **Client ID**, **Client secret** in Coolify; Coolify discovers the authorization, token, userinfo, and JWKS endpoints from the issuer.
4. **Scopes**: include `openid`; add `email` and `profile` when supported; request any additional scopes your provider requires.
5. Configure **Use PKCE**, **Require verified email**, **Clock skew (seconds)**; customize the **Login button label**.
6. Save the settings and enable the provider.
- Configure the instance URL BEFORE setup so Coolify generates the correct default callback URL.

## Registration and team membership

- **Allow OIDC user creation**: a successful OIDC login creates a user when password registration is disabled; disable when only existing users should sign in.
- **Auto-join new users to Root team**: newly created users become members without a personal team.
- Password registration can be disabled while OAuth is enabled under **Settings > Authentication**.
- Existing-account linking requires a verified email address; disabling the OIDC verification setting does NOT bypass that requirement.

## Microsoft Entra ID

- Issuer URL: `https://login.microsoftonline.com/<tenant-ID>/v2.0`.
- App registration: add the optional ID token claims `email` and `xms_edov`; Coolify accepts `xms_edov` as evidence of email verification.
- Register the OIDC callback displayed by Coolify — NOT the callback for the separate Microsoft OAuth integration.

## Verify

- Private browser window, approved non-root user: expected account and team open; new account creation follows policy; Coolify requests a 2FA code when enabled.
- Linked identities appear on **Profile**; provider-managed email addresses cannot be edited there.
- Login fails → read the reason on the login page; check issuer, callback, scopes, email-verification claims. Keep an administrator recovery sign-in available while testing.
