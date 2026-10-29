# Secrets Handling Rules

How credentials reach the stack, what must never be committed, and where
secrets live per environment. Enforced by the guard suite:
`src/tests/ProductionSecretHygieneTest.php` keeps the committed template
placeholder-only; `src/tests/SecretsHandlingRulesTest.php` keeps this
document in agreement with that enforcement.

## Injection-only rule

Secrets enter the stack exclusively through environment mechanisms — never
baked into images, hard-coded in committed files, or passed on command
lines:

- Development: the git-ignored `.env` file (copied from `.env.example`),
  consumed by Compose interpolation and `env_file`
- Production: the Coolify environment settings (Story S1.6), consumed by
  the same Compose mechanisms in the deployed stack

## Never commit

- Real credential values in any committed file — no exceptions
- The `.env` file itself: it stays git-ignored; only `.env.example` is
  committed
- Any `.env.example` value except `changeme` placeholders and the
  documented non-secret defaults

## Where secrets live

| Environment | Location |
| :--- | :--- |
| Development | local `.env` (git-ignored), filled from `.env.example` |
| Production | Coolify environment settings (Story S1.6) |

## Credential variables

Credential-material variables (supplied only via the mechanisms above,
never committed with real values):

```text
MYSQL_ROOT_PASSWORD
MYSQL_DATABASE
MYSQL_USER
MYSQL_PASSWORD
ELGG_DB_PREFIX
APP_CRON_MINUTE
APP_CRON_FIVEMINUTE
APP_CRON_FIFTEENMIN
APP_CRON_HALFHOUR
APP_CRON_HOURLY
APP_CRON_DAILY
APP_CRON_WEEKLY
APP_CRON_MONTHLY
APP_CRON_YEARLY
MAIL_SMTP_USER
MAIL_SMTP_PASSWORD
```

Every other `.env.example` variable carries a documented non-secret default
and keeps its committed value.
