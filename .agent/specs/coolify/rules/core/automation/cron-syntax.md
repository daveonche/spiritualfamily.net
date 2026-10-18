# Core › Automation › Cron Syntax

Source: coollabsio/coolify-docs `content/docs/core/automation/cron-syntax.mdx` @ main `3b7c416`.

## Five-field format

- Fields: minute (0-59), hour (0-23), day-of-month (1-31), month (1-12), day-of-week (0-7; Sunday is 0 or 7).

## Common operators

| Character | Meaning | Example |
| --- | --- | --- |
| `*` | Every valid value | `* * * * *` runs every minute. |
| `,` | A list of values | `0 8,16 * * *` runs at 08:00 and 16:00. |
| `-` | An inclusive range | `0 9 * * 1-5` runs 09:00 Monday–Friday. |
| `/` | A step interval | `*/15 * * * *` runs every 15 minutes. |

## Field-specific values

- `JAN`-`DEC` month names: `0 0 1 JAN *` runs on January 1.
- `SUN`-`SAT` weekday names: `0 9 * * MON-FRI` runs 09:00 on weekdays.
- `?` (day of month or day of week): no specific value — `0 9 ? * MON`.
- `L` (day of month): last day of the month — `0 0 L * *`.
- `W` (day of month): weekday nearest the date — `0 9 15W * *`.
- `L` (day of week): last specified weekday of the month — `0 9 * * 5L`.
- `#` (day of week): numbered occurrence of a weekday — `0 9 * * 5#2` = second Friday.

## Named schedules

| Named schedule | Equivalent | Runs |
| --- | --- | --- |
| `every_minute` | `* * * * *` | Every minute |
| `hourly` / `@hourly` | `0 * * * *` | Minute 0 of every hour |
| `daily` / `@daily` / `@midnight` | `0 0 * * *` | Every day at 00:00 |
| `weekly` / `@weekly` | `0 0 * * 0` | Sunday 00:00 |
| `monthly` / `@monthly` | `0 0 1 * *` | 1st of the month 00:00 |
| `yearly` / `@yearly` / `@annually` | `0 0 1 1 *` | January 1 00:00 |

- `@every_minute` is NOT supported — use `every_minute` or `* * * * *`.

## Timezone

- The schedule runs in the timezone of the feature that owns it: scheduled tasks use the timezone configured for the server that runs the resource; other features may use an instance or server timezone per their own settings page.
- Confirm the relevant timezone before saving when exact local time matters.

## Validate

- Enter the expression in the schedule's **Frequency** field and save; Coolify rejects values its cron parser cannot evaluate.
- For tasks, **Execute Now** verifies command and container separately — manual execution does not prove the expression means the intended time.
