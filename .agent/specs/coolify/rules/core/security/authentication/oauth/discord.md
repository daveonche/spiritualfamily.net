# Core › Security › Authentication › OAuth › Discord

Source: coollabsio/coolify-docs `content/docs/core/security/authentication/oauth/discord.mdx` @ main `3b7c416`.

## Create the application

- [Discord Developer Portal](https://discord.com/developers/applications) > **New Application**; name e.g. `Coolify`; create. No bot needed for Coolify sign-in.

## Register the redirect

- Application **OAuth2** > **Redirects** > **Add Redirect**: `https://<coolify-domain>/auth/discord/callback` → save.
- Coolify supplies the `identify` + `email` scopes when it starts authorization; do not generate or copy an OAuth URL from Discord.

## Copy the credentials

- **Client ID** from **OAuth2** or **General Information**.
- Copy or reset the **Client Secret**; store the complete value securely — resetting invalidates the previous value.

## Configure in Coolify

- **Settings > Authentication > Discord**: **Client ID**, **Client Secret**, **Redirect URI** = `https://<coolify-domain>/auth/discord/callback`.
- **Save** → **Enabled**.

## Verify

- Coolify identifies an existing user by email address: the Discord account email must match the Coolify account email, case-insensitive.
- Login page in a private browser window → **Login with Discord** → authorize → the account with the matching email opens.
- An existing account is linked only when Discord returns a VERIFIED email address; new accounts follow the registration settings.

## Troubleshoot

- Invalid redirect URI → the exact Coolify callback is under the application's **OAuth2 > Redirects** list and has been saved.
- Returns to the login page → the Discord account has an email matching the Coolify account; the authorization screen includes `identify` + `email`; if the client secret was reset, update **Client Secret** in Coolify.
