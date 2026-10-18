# Core › Backup and Recovery › Change Root Email

Source: coollabsio/coolify-docs `content/docs/core/backup-and-recovery/change-root-email.mdx` @ main `3b7c416`.

## When to use the terminal

- Change the root email from the server terminal when the address is incorrect, inaccessible, or cannot receive Coolify's verification email.
- Self-hosted instances only — Coolify Cloud users cannot access the container that runs this command; contact the Coolify team instead.
- Dashboard method (signed in and the instance can send transactional email): **Profile > General** > **Change Email** > enter the new address > **Send Verification Code** > enter the six-digit code > **Verify & Update Email**.
- The terminal command changes the login email immediately and does not send a verification message.

## Before you change the email

- SSH access to the server that runs the instance and permission to run Docker commands (add `sudo` before the Docker commands if the server requires it).
- The root account's current password, or a plan to reset the root password after changing the email.
- A correctly spelled email address that no other account on the same instance uses.
- WARNING: the command does not validate the format or whether the mailbox can receive messages — Coolify saves the address in lowercase and the database rejects an address already used by another account. Check the complete address before submitting it.

## Change the email

- Connect over SSH to the machine that runs the Coolify control plane: `ssh root@<server-ip>`.
- Confirm the control plane container is running — the command should print `running`; if not, update or repair the installation first:

```sh
docker inspect --format '{{.State.Status}}' coolify
```

- Run the interactive command and enter the complete new email address at the prompt:

```sh
docker exec -it coolify php artisan root:change-email
```

- Success prints `Root user's email updated successfully.`
- Only the root account's active login email changes: the current password, 2FA configuration, browser sessions, and API tokens remain unchanged. Coolify does not restart, and deployed applications, databases, and services continue running.

## Verify the new login email

- Keep the current dashboard session open if one is available; open the login page in a private browser window.
- Sign in with the new email address, the existing root password, and the 2FA code if enabled.
- The old email address must no longer sign in.

## Troubleshoot

- Wrong address saved → do not edit the Coolify database directly; run the same command again, enter the correct address, wait for `Root user's email updated successfully.`, and repeat the private-window login check.
- `Failed to update root user's email` → first confirm no other Coolify account already uses the address (the `users` table requires unique emails; the command reports a generic failure on duplicates), then confirm `coolify-db` is running with the same `docker inspect` check against `coolify-db`. If the database is running and the address is unique, keep the command output and contact support.
- Command succeeded but login still fails → enter the new address in lowercase with the existing password; if the password is also unknown, follow the reset-root-password rule; if 2FA blocks the login, follow the reset-2fa rule.
