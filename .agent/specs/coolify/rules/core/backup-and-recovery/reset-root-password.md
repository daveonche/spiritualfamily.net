# Core › Backup and Recovery › Reset Root Password

Source: coollabsio/coolify-docs `content/docs/core/backup-and-recovery/reset-root-password.mdx` @ main `3b7c416`.

## When to use the terminal

- Reset the root password from the server terminal when you cannot sign in to the root account and cannot use an emailed password-reset link.
- Self-hosted instances only — Coolify Cloud users cannot access the container that runs this command; contact the Coolify team instead.
- Least disruptive first: signed in and knows the current password → **Profile > General** > **Change Password** > **Save**; instance can send transactional email → **Forgot Password** on the login page; otherwise the terminal procedure — it requires neither the current password nor a working email configuration.

## Before you reset the password

- SSH access to the server that runs the instance and permission to run Docker commands (add `sudo` before the Docker commands if the server requires it).
- The root account's current login email, or a plan to change the root email after the reset.
- A strong, unique password ready to enter twice — the command checks only that both entries match, not the dashboard password rules. Use a long, randomly generated password stored in a password manager.
- WARNING: after the change, Coolify signs the root account out on every device and revokes every API token owned by that account. Sessions and API tokens of other users are not affected.

## Reset the password

- Connect over SSH to the machine that runs the Coolify control plane: `ssh root@<server-ip>`.
- Confirm the control plane container is running — the command should print `running`; if not, update or repair the installation first:

```sh
docker inspect --format '{{.State.Status}}' coolify
```

- Run the interactive command and enter the same new password at both prompts (the terminal hides input):

```sh
docker exec -it coolify php artisan root:reset-password
```

- Success prints `Root password updated successfully.`
- Only the root account password changes — Coolify does not restart, and deployed applications, databases, and services are unaffected.

## Verify root access

- Open the login page in a private browser window and sign in with the current email, the new password, and the 2FA code if enabled — a private window verifies a fresh login without an existing session.
- Existing root sessions should no longer open the dashboard.
- If an integration used an API token owned by the root account, create a replacement after signing in and update the integration — the revoked token cannot be restored.

## Troubleshoot

- Passwords do not match → Coolify did not change anything; rerun the command and enter the same value at both prompts.
- `Root user not found` → the instance database lacks the root account at user ID `0`; do not create or edit the record manually. Confirm the correct server, then restore the instance database from a known backup or contact support.
- `Failed to update root password` → confirm `coolify-db` is running (same `docker inspect` check against `coolify-db`); if running and the reset still fails, keep the command output and contact support.
- Command succeeded but login still fails → confirm the exact current email and the password entered in the terminal; if the email is unusable, follow the change-root-email rule; if 2FA blocks the login, follow the reset-2fa rule.
