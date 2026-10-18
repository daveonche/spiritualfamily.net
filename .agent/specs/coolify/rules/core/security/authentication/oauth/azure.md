# Core › Security › Authentication › OAuth › Azure

Source: coollabsio/coolify-docs `content/docs/core/security/authentication/oauth/azure.mdx` @ main `3b7c416`.

## Register the app

- [Microsoft Entra admin center](https://entra.microsoft.com/) > **Identity > Applications > App registrations** > **New registration**; name e.g. `Coolify`; choose the supported account type matching who may sign in.
- **Redirect URI**: **Web** + `https://<coolify-domain>/auth/azure/callback` → **Register**.
- Coolify uses Microsoft Graph `User.Read` to obtain the signed-in user's name and user principal name.

## Copy the IDs

- App registration **Overview**: **Application (client) ID** + **Directory (tenant) ID**.
- Single-tenant organization: use the Directory tenant ID in Coolify. Tenant selectors `organizations`, `common`, `consumers` are supported when the app registration is configured for those account types.

## Create the client secret

- **Certificates & secrets > Client secrets** > **New client secret**; description + expiration → **Add**.
- Copy the secret **Value** immediately — NOT the **Secret ID**; Coolify needs the value.

## Confirm Graph access

- **API permissions**: delegated Microsoft Graph **User.Read** present; add if missing. Apply administrator consent when Entra policies require it.

## Configure in Coolify

- **Settings > Authentication > Azure**:

| Coolify field | Microsoft Entra value |
| :--- | :--- |
| **Client ID** | **Application (client) ID** |
| **Client Secret** | Client secret **Value** |
| **Redirect URI** | `https://<coolify-domain>/auth/azure/callback` |
| **Tenant** | **Directory (tenant) ID**, or the allowed tenant selector |

- **Save** → **Enabled**.

## Verify

- Login page in a private browser window → **Login with Azure** → sign in with an allowed account → the matching Coolify account opens.

## Troubleshoot

- Microsoft reports a reply URL mismatch → app registration **Authentication**: the **Web** redirect URI exactly matches `/auth/azure/callback` on the public Coolify domain.
- Returns to the login page after Microsoft sign-in → **User.Read** granted; Microsoft Graph returns a user principal name usable as the account email; client secret not expired.
