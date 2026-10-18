# Core › Security › Authentication › OAuth › Bitbucket

Source: coollabsio/coolify-docs `content/docs/core/security/authentication/oauth/bitbucket.mdx` @ main `3b7c416`.

## Open OAuth consumers

- Bitbucket Cloud → the workspace owning the integration → **Workspace settings > OAuth consumers**; requires permission to manage workspace settings (see Atlassian's docs when navigation differs).

## Add the consumer

| Bitbucket field | Value |
| :--- | :--- |
| **Name** | Recognizable name, e.g. `Coolify` |
| **Website** | `https://<coolify-domain>` |
| **Callback URL** | `https://<coolify-domain>/auth/bitbucket/callback` |

- Permissions: read access to the account and email address. Coolify requests the `email` scope and uses the confirmed primary email returned by Bitbucket.
- Save the consumer.

## Save the credentials

- Expand the created consumer → copy **Key** + **Secret**. The **Key** is the OAuth client ID; store the secret securely.

## Configure in Coolify

- **Settings > Authentication > Bitbucket**: **Client ID** = consumer **Key**; **Client Secret** = consumer **Secret**; **Redirect URI** = `https://<coolify-domain>/auth/bitbucket/callback`.
- **Save** → **Enabled**.

## Verify

- Login page in a private browser window → **Login with Bitbucket** → approve the consumer → the account matching the confirmed primary Bitbucket email opens.

## Troubleshoot

- Bitbucket rejects the callback URL → consumer **Callback URL** vs Coolify **Redirect URI**: same HTTPS domain + `/auth/bitbucket/callback` path.
- Coolify does not receive an email address → the consumer has email read permission AND the Bitbucket account has a confirmed primary email address.
