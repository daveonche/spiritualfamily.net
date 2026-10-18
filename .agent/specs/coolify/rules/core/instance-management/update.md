# Core › Instance Management › Update

Source: coollabsio/coolify-docs `content/docs/core/instance-management/update.mdx` @ main `3b7c416`.

## Scope

- Updating Coolify changes the self-hosted Coolify control plane. It does not update the applications, databases, services, or servers you manage with Coolify.
- Coolify Cloud: the Coolify team manages instance updates — you must wait for the Coolify team to update the instance.
- Self-hosted: open **Settings** in the left sidebar, then select **Updates**.

## Before you update

- Create an instance backup (see the instance-backup rule).
- Review the release notes (`https://github.com/coollabsio/coolify/releases`) for the version you plan to install.
- Check for active deployments; they can fail during the update process.
- Updating from a release that uses Soketi → read the reverb-migration rule before you update if you use custom Compose overrides, realtime ports, firewall rules, or Cloudflare Tunnels.

## Automatic updates

- Enable **Automatic updates** and set **Update frequency** — Coolify then checks for a new release and installs it automatically when one is available.
- **Enable**: choose **Enabled** under **Automatic updates** (saves immediately; **Update frequency** becomes available). If automatic updates cannot be configured in the dashboard, `AUTOUPDATE` is set in `/data/coolify/source/.env` — configure automatic updates in that file instead.
- **Update frequency**: how often Coolify checks for a new release and installs it if one is available — a cron expression or a preset (`every_minute`, `hourly`, `daily`, `weekly`, `monthly`, `yearly`); default `0 0 * * *` (daily at midnight in the instance timezone). Unavailable when `AUTOUPDATE` is set in `.env`.
- **Check frequency**: how often Coolify checks the versions file on the CDN (`https://cdn.coollabs.io/coolify/versions.json`) and updates the available-version status; Coolify installs an update only when a new version is available; default `0 * * * *` (hourly). **Check now** checks for a new version immediately — it does not install the update.
- During an update, Coolify restarts to apply the new version; the dashboard is unavailable briefly, usually for less than a minute.

## Manual update via dashboard

- Open **Settings > Updates**. When a new version is available, Coolify shows **Update available** beside the version number at the top left of the sidebar.
- Click **Upgrade Now** in the **Update Coolify** section, or click **Update available** beside the version number — both options open the upgrade modal.
- Review the current and new version in the modal; check that no deployments are pending or running (deployments during the update will fail), then click **Upgrade Now**.
- An update usually takes less than a minute (longer depending on network conditions and server resources); Coolify restarts while applying the update, so the dashboard may be unavailable briefly.
- If the progress modal remains on **Restart** for more than five minutes, refresh the page — Coolify may have finished updating but failed to reload the page automatically. On **Gateway Timeout** or **Bad Gateway**, wait two minutes before refreshing; if the error remains after refreshing, check the update logs under `/data/coolify/source/upgrade-*`.
- When all update stages are complete, Coolify confirms the new version and reloads the page; if the page does not reload automatically, click **Reload Now**.

## Manual update via terminal

- Use the Coolify upgrade script to update an existing instance.
- WARNING: do not use `install.sh` to update — on an existing instance it resets ownership and permissions under `/data/coolify`, including managed service data. Use `upgrade.sh` instead.
- Connect to the server that runs the Coolify instance over SSH: `ssh <username>@<server-ip>`.
- Run the upgrade script with the Coolify version you want to install, without a leading `v` — replace `4.3.23` with your target version:

```sh
curl -fsSL https://cdn.coollabs.io/coolify/upgrade.sh | bash -s 4.3.23
```

- The upgrade script updates configuration files from the versions file on the CDN as part of the process.
- Specify a version: without one, the script writes `COOLIFY_VERSION=latest` to `.env`, which prevents update checks from working correctly.
- Wait for the script to finish, then open the dashboard and confirm everything is working.

## Verify the update

- Confirm the dashboard is reachable, the expected Coolify version is shown, and your resources are running.
- If the dashboard does not return, review the update logs on the Coolify server — Coolify writes update logs under `/data/coolify/source/` with names such as `upgrade-2026-07-11-12-00-00.log`.
