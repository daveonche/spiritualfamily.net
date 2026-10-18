# Core › Backup and Recovery › Reset 2FA

Source: coollabsio/coolify-docs `content/docs/core/backup-and-recovery/reset-2fa.mdx` @ main `3b7c416`.

## When to use the terminal

- Reset two-factor authentication from the server terminal when an account cannot complete the 2FA challenge and has no usable recovery codes. The reset removes the account's authenticator setup and recovery codes; it does not change the password.
- Self-hosted instances only — Coolify Cloud users cannot access the container required for this procedure; contact the Coolify team instead.
- Try first: a current code from the authenticator app → a saved recovery code → if correct codes are rejected, check 2FA time synchronization (upstream: `/troubleshoot/server/two-factor-stopped-working`) → then the terminal procedure.

## Before you reset 2FA

- SSH access to the server that runs the instance and permission to run Docker commands (add `sudo` before the Docker commands if the server requires it).
- The affected account's email address (unless resetting the root account) and the account password for the post-reset login check. Root account with unknown password → reset the root password first.
- WARNING: the procedure clears the selected account's authenticator secret and recovery codes — the existing authenticator entry and every recovery code for that account stop working. Configure 2FA again after access is restored.

## Reset 2FA from the server

- Connect over SSH: `ssh root@<server-ip>`; confirm `coolify` is running (`docker inspect --format '{{.State.Status}}' coolify` → `running`).
- Open Tinker inside the `coolify` container and wait for the `>` prompt:

```sh
docker exec -it coolify php artisan tinker
```

- Select the affected account:

```php
// Root account — by user ID 0:
$user = App\Models\User::find(0);
// Another account — replace the address:
$user = App\Models\User::where('email', strtolower('user@example.com'))->first();
```

- Verify the selection before changing anything — Tinker must print the expected user ID and email; `two_factor_confirmed_at` normally holds a timestamp when 2FA is enabled. If it prints `null` or a different account, stop and reselect:

```php
$user?->only(['id', 'email', 'two_factor_confirmed_at']);
```

- Clear the setup — the `save()` call must return `true`:

```php
$user->two_factor_secret = null;
$user->two_factor_recovery_codes = null;
$user->two_factor_confirmed_at = null;
$user->save();
```

- Verify the database update — all three fields must report `null` — then exit Tinker:

```php
$user->refresh()->only(['two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at']);
exit;
```

- Coolify does not need to restart; deployed applications, databases, and services are not affected.

## Verify and reconfigure

- Private browser window: sign in with the account email and password — no 2FA challenge should appear. Root account with unavailable email → follow the change-root-email rule.
- Reconfigure: **Profile > General** > **Two-factor Authentication** > **Configure** > add the new setup to the authenticator and validate it with a current code > store the new recovery codes in a secure location outside the Coolify server > confirm the new code works in a fresh private-window login.
- The cleared authenticator secret and recovery codes cannot be reused.

## Troubleshoot

- Account lookup returns `null` → the root account requires `App\Models\User::find(0)`; other accounts need the complete email address with the lowercase lookup. Never clear fields until the verification prints the account you intend to reset.
- Saving the reset fails → leave Tinker, confirm `coolify-db` is running, reopen Tinker, select the account again, and repeat the update; if saving still fails, keep the error output and contact support.
- Login page still asks for a 2FA code → re-verify in Tinker that all three fields report `null`, then open a new private browser window — a page already displaying the challenge may replay the previous login attempt.
- Password rejected after the reset → resetting 2FA does not change passwords: root → reset-root-password rule; other accounts → **Forgot Password** when the instance can send transactional email.
