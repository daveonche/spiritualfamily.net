# Core › Security › Authentication › OAuth › Google

Source: coollabsio/coolify-docs `content/docs/core/security/authentication/oauth/google.mdx` @ main `3b7c416`.

## Select a project

- [Google Auth Platform](https://console.cloud.google.com/auth/overview): select or create the Google Cloud project owning the OAuth client.
- Consent screen when asked: complete **Branding**, choose the **Audience**, add contact information. External app in testing: add every Coolify tester as a test user.

## Create the web client

- **Clients** > **Create client** > **Web application**; name e.g. `Coolify`.
- **Authorized redirect URIs**: `https://<coolify-domain>/auth/google/callback` → create.
- Copy **Client ID** + **Client secret** (client JSON download optional; Coolify needs only the two values).

## Configure in Coolify

- **Settings > Authentication > Google**:

| Coolify field | Google value |
| :--- | :--- |
| **Client ID** | The Google web client ID |
| **Client Secret** | The Google client secret |
| **Redirect URI** | `https://<coolify-domain>/auth/google/callback` |
| **Hosted domain** | Optional Workspace domain, e.g. `example.com`; `*` = any Workspace domain |

- **Save** → **Enabled**.
- Hosted domain restricts sign-in: `*` allows any Google Workspace account but REJECTS personal Google accounts; leave the field empty when no Workspace restriction is needed.

## Verify

- Login page in a private browser window → **Login with Google** → complete consent if requested → the matching account opens.

## Troubleshoot

- `redirect_uri_mismatch` → web client **Authorized redirect URIs** vs Coolify **Redirect URI**: exact match required.
- App not available to the user → consent screen in testing: add the account under **Audience > Test users**; internal app: the account belongs to the selected Workspace organization.
