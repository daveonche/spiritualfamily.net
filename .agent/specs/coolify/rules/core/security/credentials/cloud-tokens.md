# Core › Security › Credentials › Cloud Tokens

Source: coollabsio/coolify-docs `content/docs/core/security/credentials/cloud-tokens.mdx` @ main `3b7c416`.

## Scope

- Coolify uses cloud tokens to connect to supported cloud providers. A cloud token belongs to the active Coolify team and can be used when creating or managing a server in that provider account.
- Only team administrators and owners can manage cloud tokens. Coolify encrypts the token value in its database and does not display the saved value again.
- WARNING: treat cloud tokens as passwords — create a dedicated provider token for Coolify, grant only the access it needs, and store the original value in a secret manager. Revoke and replace the token immediately if it is exposed.

## Create a provider token

### Hetzner

- Hetzner tokens belong to one project — create the token in the project where Coolify should manage servers.
- Open the [Hetzner Cloud Console](https://console.hetzner.cloud/projects) > select the project > **Security** in the project sidebar > **API Tokens** > **Generate API Token**.
- Enter a description such as `coolify-shadowarcanist`, then select **Read & Write** — Coolify needs write access to create and delete servers, upload SSH keys, control server power, and optionally enable Hetzner backups. A **Read** token may pass the initial validation but cannot perform these actions.
- **Generate API Token** > copy the complete value > store it securely — Hetzner does not show the token again after you close the dialog.

### DigitalOcean

- Create a personal access token for the team where Coolify should manage Droplets.
- Open [Applications & API](https://cloud.digitalocean.com/account/api/tokens) > **Personal access tokens** > **Generate New Token**.
- Enter a name such as `coolify-shadowarcanist`; choose an expiration period that gives you enough time to rotate the token before it expires.
- Choose **Custom Scopes**, then grant: `account:read`, `regions:read`, `sizes:read`, `image:read`, `ssh_key:read`, `ssh_key:create`, `droplet:read`, `droplet:create`, `droplet:update`, `droplet:delete` — these let Coolify validate the account, list the options required by the server form, manage SSH keys, and create, inspect, start, or delete Droplets. **Full Access** also works but grants broader access than Coolify needs.
- **Generate Token** > copy the complete value > store it securely — DigitalOcean shows the token secret only once.

### Vultr

- Create a Vultr API key for the account where Coolify should manage instances.
- Open the [Vultr API page](https://console.vultr.com/user/apiaccess/) — depending on the Vultr Console version it appears as **Vultr API** under **Orchestration** or as **API Access** under **Account**. Enable API access for the Vultr user if it is disabled.
- Check API access control: if the Vultr account restricts API access by IP address, allow the public outbound IP address of the Coolify instance — the API requests come from Coolify, not from your browser or the server that Coolify will create.
- Under **User Access Tokens**, enter a name such as `coolify-shadowarcanist`, choose an expiration option, and select **Add Key** — the Vultr user must be allowed to list plans, regions, operating systems, and SSH keys, and to create, inspect, start, and delete instances.
- Copy the complete key > store it securely — Vultr does not let you recover the value after leaving the page.

### Hostinger

- Create a Hostinger API token for the account where Coolify should buy and manage VPS servers.
- If you do not have a Hostinger account, you can [create one with the Coolify link](https://www.hostinger.com/vps/coolify-hosting?ref=coolify.io) — Coolify shows the same link in the token form.
- Log in to Hostinger and open the [API page in hPanel](https://hpanel.hostinger.com/profile/api) > create a new API token > give it a name that identifies Coolify, such as `coolify-shadowarcanist`.
- Copy the complete token value > store it in a secure location.
- WARNING: a Hostinger token can buy servers — Coolify uses this token to order VPS servers, and Hostinger charges each order to the default payment method of the account. Keep the token secret.

## Add the token to Coolify

- **Keys & Tokens** in the Coolify sidebar > **Cloud Tokens** > **New token** > choose the **Provider**: **Hetzner**, **DigitalOcean**, **Vultr**, or **Hostinger**.
- Complete the form: **Token name** identifies the provider account or environment (e.g. `shadowarcanist-production`); **Description** optionally records the project, team, account, or intended use; **API token** receives the complete token or API key copied from the provider.
- Select **Validate and add** — after validation succeeds, Coolify closes the form and displays the new token on the **Cloud Provider Tokens** page.
- INFO: validation checks the credential, not every permission — Coolify validates a Hetzner token by listing servers, DigitalOcean and Vultr tokens through their account endpoints, and a Hostinger token with `GET https://developers.hostinger.com/api/vps/v1/virtual-machines`. A successful result confirms that the provider accepts the credential, but it does not prove that every permission required to create or manage a server is available.

## Manage a cloud token

- **Keys & Tokens > Cloud Tokens** > select the token's **Edit** icon.
- Change **Name** or **Description** > **Save changes**; **Validate** checks whether the provider still accepts the saved credential; **Delete** removes an unused token.
- The provider and creation date are read-only. Coolify does not show or replace the saved secret on this page — to rotate a credential, create a replacement at the provider, add it as a new cloud token, and move every dependent server to the replacement before revoking the previous credential.
- Coolify blocks deletion while one or more servers still use the token. Do not revoke the credential at the provider until those servers have been moved to another token or are no longer managed through that provider connection.

## Troubleshoot

- Invalid API token → confirm you selected the matching provider and pasted the complete token without surrounding spaces; check that the credential has not expired or been revoked. Hetzner: confirm the project still exists. DigitalOcean: confirm the token includes `account:read`. Vultr: confirm API access is enabled and Vultr's access-control list permits requests from the Coolify instance.
- The token validates but server actions fail → initial validation does not test every write operation. Hetzner: use a **Read & Write** token. DigitalOcean: compare the token's custom scopes with the list above. Vultr: confirm the Vultr user can manage instances and SSH keys and that API access control permits the Coolify instance. Hostinger: confirm the account has a valid default payment method — if Coolify cannot list post-install scripts, the token does not have permission for them, and Coolify shows the message from Hostinger. After correcting provider permissions, return to the saved cloud token and select **Validate**.
- Vultr rejects requests from Coolify → check **Access Control** on the Vultr API page; if an IP allowlist is enabled, it must contain the public outbound IP address of the Coolify instance. Adding your browser's IP address or the address of the server you intend to create does not authorize requests sent by Coolify.
- Hetzner resources are missing → Hetzner tokens are project-specific; confirm the token was created in the project containing the servers, networks, firewalls, and SSH keys you expect Coolify to use. Create and add a separate token when Coolify must work with another Hetzner project.
- Coolify will not delete the token → the token is still assigned to one or more servers; move those servers to another valid provider token or remove the provider relationship before trying to delete it again. Do not revoke the provider credential first — Coolify needs the working credential for provider-backed server actions until the dependency has been removed.
