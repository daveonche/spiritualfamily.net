# Core › Security › Credentials › Cloud-Init Scripts

Source: coollabsio/coolify-docs `content/docs/core/security/credentials/cloud-init-scripts.mdx` @ main `3b7c416`.

## Scope

- A cloud-init script configures a new cloud server during its first startup. Coolify can send one as provider user data when creating a Hetzner, DigitalOcean, or Vultr server.
- Use a script for repeatable initialization — creating files, installing packages, or applying operating-system settings — before Coolify validates the server.
- WARNING: a broken cloud-init script can leave a new server incomplete or unreachable. Test with a temporary server and avoid changing the SSH access Coolify requires.
- Only team administrators and owners can create, update, or delete cloud-init scripts. Scripts belong to the active team.

## Create a reusable script

- **Keys & Tokens** in the Coolify sidebar > **Cloud-Init Scripts**.
- **New script** > enter a descriptive **Script name** > enter the cloud-config YAML under **Script content** > **Create script**.
- Name and script content are required; Coolify validates that the content is valid cloud-init YAML before saving it.

For example:

```yaml
#cloud-config
package_update: true
packages:
  - curl

write_files:
  - path: /etc/motd
    content: |
      Managed by Coolify
```

- Cloud-init features vary by Linux image and provider — check the image's cloud-init support before relying on a module.

## Use a script while provisioning

- **Servers** > **New server** > choose **Hetzner**, **DigitalOcean**, or **Vultr**.
- Complete the provider's server settings > open the advanced options > select the saved cloud-init script.
- Review the remaining options, then select **Buy & Create Server**.
- You can also enter a script directly in the provisioning form — enable **Save this script for later use** to add it to the team's reusable scripts.
- The script is sent only when the provider creates the server; it does not run when you validate a server or add an existing server manually.
- Hostinger does not use cloud-init scripts — for a Hostinger VPS, select a Hostinger post-installation script in the advanced options (upstream: `/core/infrastructure/servers/provision-cloud-server`).
- INFO: saved scripts are templates — editing a saved script changes future provisioning requests that use it; it does not rerun cloud-init or modify servers that were already created.

## Edit or delete a script

- **Keys & Tokens > Cloud-Init Scripts** > select the script's **Edit** icon.
- Change **Script name** or **Script content**, then select **Save changes**.
- **Delete** > enter the requested confirmation > **Delete script** to remove it permanently.
- Deleting a saved script does not affect servers that were already provisioned with it.

## Troubleshoot

If a new server is not configured as expected:
- Check the provider console to confirm the server was created and is running.
- Open the server console and inspect the cloud-init status:

```sh
cloud-init status --long
```

- Review the cloud-init logs:

```sh
sudo less /var/log/cloud-init.log
sudo less /var/log/cloud-init-output.log
```

- Correct the saved script and provision a temporary server to test it again.
- Cloud-init normally runs during the machine's first boot — changing the saved template does not repair an existing machine.
