# Core › Notifications › Channels › Email

Source: coollabsio/coolify-docs `content/docs/core/notifications/channels/email.mdx` @ main `3b7c416`.

## Provider paths

Configure at **Notifications > Email**. A team can use only one email path at a time:

| Provider | Availability | What you need |
| --- | --- | --- |
| Use hosted email service | Coolify Cloud | No provider credentials. |
| Use system-wide settings | Self-hosted only | A working email provider under **Settings > Email**. |
| SMTP server | Cloud and self-hosted | SMTP sender, host, port, encryption, optional authentication details. |
| Resend | Cloud and self-hosted | A sender name, sender address, and Resend API key. |

- The hosted and instance-wide options disable team-specific SMTP and Resend; enabling SMTP disables Resend, and enabling Resend disables SMTP.
- **Use system-wide settings** appears only on self-hosted Coolify and reuses the sender and SMTP/Resend provider configured under **Settings > Email** for the instance. Coolify Cloud shows **Use hosted email service** instead; Cloud users do not configure the instance-wide provider.

## Before you start

- SMTP: copy host, port, encryption method, username, and password from the provider; confirm it permits the sender address you want to use.
- Resend: verify the sending domain or sender in Resend and create an API key.
- System-wide: configure and test the instance provider first — requires access to the self-hosted instance settings.

## Use the hosted or system-wide provider

- The dashboard shows the option for the Coolify deployment type; it does not show both together.
- Self-hosted: as instance administrator, configure **Settings > Email** (shared sender details, SMTP or Resend, enable, save) and use **Send test** there — continue only after that test arrives. Then for the team set **Email service** to **Use system-wide settings**: it uses the instance sender and credentials, and team members cannot change them from the Notifications page. Choose events, then **Send test** to an accessible recipient. Changing the instance-wide provider affects every team that reuses it.
- Cloud: set **Email service** to **Use hosted email service** (no SMTP host, password, or Resend key), select events, **Send test** to an accessible recipient, and confirm arrival before relying on the channel for operational alerts.

## Configure team SMTP

- Set **Email service** to **Use team email settings**; enter **From name** and a valid **From address**.
- Under **SMTP server**: **Host** (e.g. `smtp.example.com`); **Port** — common values `587`, `465`, `25`, accepted range `1`–`65535`; **Encryption** — `StartTLS`, `TLS/SSL`, or `None` (port 587 commonly StartTLS, port 465 commonly TLS/SSL, but follow the provider's instructions); **SMTP username** / **SMTP password** when required; **Timeout** optional, in seconds.
- Check the values against the provider's documentation — a mismatched port and encryption option prevents Coolify from connecting.
- **Save changes** in the **SMTP server** section, set **SMTP delivery** to **Enabled**, and **Save changes** — Coolify disables Resend when SMTP is enabled.
- WARNING: choose **None** only when the provider explicitly requires it and the connection stays on a trusted network; prefer an encrypted option supported by the provider.

## Configure Resend

- Create an API key in the Resend dashboard and verify the domain or sender address Coolify will use.
- Set **Email service** to **Use team email settings**; enter **From name** and the verified **From address**.
- Under **Resend**, enter the **API key** > **Save changes**; set **Resend delivery** to **Enabled** > **Save changes** — Coolify disables SMTP when Resend is enabled.

## Choose events and verify delivery

- Under **Notification events**, select the events to send by email — see the notification-events rule for each trigger.
- **Send test**, enter a recipient address you can access, and send; confirm the message arrives. If not, check the provider's activity logs, sender verification, SMTP port access, and spam folder.

## Troubleshoot email delivery

- Confirm the intended provider is enabled — SMTP, Resend, and the hosted/system-wide option are mutually exclusive.
- For SMTP, compare host, port, and encryption with the provider's current settings; confirm the Coolify server can make outbound connections on that port.
- Check whether the provider requires the **From address** or its domain to be verified.
- Check the provider's delivery or activity logs — an accepted request can still be rejected later because of sender policy or recipient filtering.
- Check the recipient's spam or junk folder.
- When using the self-hosted system-wide provider, send a test from **Settings > Email**; if that test also fails, fix the instance provider before troubleshooting the team channel.
