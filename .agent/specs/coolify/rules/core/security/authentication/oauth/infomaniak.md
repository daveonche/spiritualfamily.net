# Core › Security › Authentication › OAuth › Infomaniak

Source: coollabsio/coolify-docs `content/docs/core/security/authentication/oauth/infomaniak.mdx` @ main `3b7c416`.

## Open applications

- Sign in to Infomaniak → [Applications](https://manager.infomaniak.com/v3/ng/profile/user/applications/list) in the user profile settings → create an application credential.

## Create the application

- Type **Application**; name e.g. `Coolify`; redirect URL `https://<coolify-domain>/auth/infomaniak/callback` → create.

## Save the credentials

- Copy the application client ID + client secret; store the client secret securely BEFORE leaving the credential page.

## Configure in Coolify

- **Settings > Authentication > Infomaniak**: **Client ID**, **Client Secret**, **Redirect URI** = `https://<coolify-domain>/auth/infomaniak/callback`.
- **Save** → **Enabled**.

## Verify

- Login page in a private browser window → **Login with Infomaniak** → complete authorization → the account matching the Infomaniak email opens.

## Troubleshoot

- Infomaniak rejects the redirect URL → the application's redirect URL exactly matches the public Coolify domain + `/auth/infomaniak/callback` path.
- Returns to the login page → the client ID and secret belong to the SAME Infomaniak application; the Infomaniak account returns an email address; create a new secret if the original was not saved completely.
