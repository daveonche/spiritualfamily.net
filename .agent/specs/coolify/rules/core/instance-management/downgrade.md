# Core › Instance Management › Downgrade

Source: coollabsio/coolify-docs `content/docs/core/instance-management/downgrade.mdx` @ main `3b7c416`.

## Scope

- Downgrading replaces the version of the self-hosted Coolify control plane. It does not roll back your applications, databases, services, managed servers, or their data.
- Self-hosted only — the Coolify team manages instance versions for Coolify Cloud; if the latest version causes a serious issue, contact the Coolify team.
- The dashboard and automatic updates only install newer releases — downgrading requires the terminal.

## Before you downgrade

- Create an instance backup (see the instance-backup rule).
- Review the release notes (`https://github.com/coollabsio/coolify/releases`) for the current and target versions.
- Choose a maintenance window: the dashboard will be unavailable while Coolify restarts, but your apps keep running.
- Check for active deployments and let them finish before continuing.
- DANGER: an older Coolify release may not work with database changes made by a newer release. Changing the version does not restore the earlier database state. If the downgrade breaks your Coolify instance, restore the known-good instance backup instead of downgrading through multiple older releases.

## Perform the downgrade

### Disable automatic updates

- Open **Settings** in the left sidebar, then select **Updates**; under **Automatic updates**, choose **Disabled** — this setting saves immediately and prevents Coolify from returning to the newest release after the downgrade.
- If you cannot change **Automatic updates**, check whether `/data/coolify/source/.env` contains the `AUTOUPDATE` environment variable; if it does, set it to `false`.
- The upgrade script saves a timestamped backup of `.env` before changing it — save your own copy of `/data/coolify/source/.env` before downgrading in case anything goes wrong.

### Install the target version

- Connect to the Coolify server over SSH: `ssh root@<server-ip>` (non-root sign-in: run `sudo -i` first to open a root shell).
- Run the upgrade script with the exact target version — replace `4.3.21` with the version from the release you selected:

```sh
curl -fsSL https://cdn.coollabs.io/coolify/upgrade.sh | bash -s 4.3.21
```

- The upgrade script downloads the Coolify configuration files, pulls the target Coolify image, then restarts the Coolify containers in the background — wait for the restart confirmation before closing the SSH session.

### Verify the downgrade

- After the script completes, wait for the dashboard to be available again, then confirm: the dashboard opens and shows the expected Coolify version; you can see your projects and resources; managed servers still connect.
- The script writes a timestamped log such as `/data/coolify/source/upgrade-2026-07-11-12-00-00.log` — review the newest `upgrade-*.log` file if the dashboard is still unavailable or a container fails to start.

## Troubleshoot

- Coolify upgrades again after the downgrade → return to **Settings > Updates** and confirm **Automatic updates** is **Disabled**; if the control is unavailable because `AUTOUPDATE` is set in `/data/coolify/source/.env`, change it to `AUTOUPDATE=false` and rerun the target-version command.
- The Coolify dashboard stays unavailable → review the newest `/data/coolify/source/upgrade-*.log` file first; check that the `coolify`, `coolify-db`, and `coolify-redis` containers are running and healthy; make sure your firewall still allows access to the dashboard. Releases before the Reverb migration also start a `coolify-realtime` container — a downgrade to one of those releases brings that container back, and ports `6001` and `6002` move to it again.
- The target version does not work with the existing data → stop if the target version cannot use the existing database or the dashboard remains unavailable; do not keep downgrading to older releases. Use the backup created before the change and follow the instance-restore rule — restore uses the backup database together with the matching `.env` file so Coolify retains the encryption key required to read encrypted values.
