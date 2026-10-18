# Core › Security › Authentication › OAuth › GitLab

Source: coollabsio/coolify-docs `content/docs/core/security/authentication/oauth/gitlab.mdx` @ main `3b7c416`.

## Open applications

- Sign in → avatar menu > **Edit profile** > **Access > Applications**.
- Self-managed instance: an administrator can create an instance-wide application when organizational ownership is required.

## Create the application

| GitLab field | Value |
| :--- | :--- |
| **Name** | Recognizable name, e.g. `Coolify` |
| **Redirect URI** | `https://<coolify-domain>/auth/gitlab/callback` |
| **Confidential** | Enabled — Coolify stores and uses a client secret |
| **Scopes** | `read_user` |

- **Add new application** → save.

## Save the credentials

- Copy **Application ID** + **Secret**; store securely. If GitLab no longer shows the complete value, create or rotate the secret before continuing.

## Configure in Coolify

- **Settings > Authentication > GitLab**:

| Coolify field | GitLab value |
| :--- | :--- |
| **Client ID** | The GitLab **Application ID** |
| **Client Secret** | The GitLab application **Secret** |
| **Redirect URI** | `https://<coolify-domain>/auth/gitlab/callback` |
| **Base URL** | Empty for GitLab.com; self-managed: origin only, e.g. `https://gitlab.example.com` |

- **Save** → **Enabled**.

## Verify

- Login page in a private browser window → **Login with GitLab** → approve the application → the account matching the GitLab email opens.

## Troubleshoot

- A self-managed GitLab redirects to GitLab.com → **Base URL** = the self-managed origin; do NOT include `/oauth/authorize`, `/api`, or a trailing application path.
- GitLab rejects the callback → the exact `https://<coolify-domain>/auth/gitlab/callback` value in both GitLab and Coolify; application confidential with the `read_user` scope.
