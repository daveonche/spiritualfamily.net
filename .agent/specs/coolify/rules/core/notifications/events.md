# Core › Notifications › Events

Source: coollabsio/coolify-docs `content/docs/core/notifications/events.mdx` @ main `3b7c416`.

## Selection model

- Events are chosen per channel (email, Discord, Telegram, Slack, Pushover, webhook): **Notifications** > select the channel > dropdowns under **Notification events**.
- Groups: **Deployments**, **Resources**, **Backups**, **Scheduled tasks**, **Servers** (**Server** on Email).
- Changing an event for one channel does not change the other channels.

## Deployments

| Setting | When Coolify sends the notification |
| --- | --- |
| Deployment Success | An application deployment completes successfully. |
| Deployment Failure | An application deployment fails. |
| Resource status changes | An application or resource container stops unexpectedly or restarts automatically. |
| Restart limit reached | A resource reaches its automatic restart limit and is stopped. |

- Pull request preview deployments use the same **Deployment Success** / **Deployment Failure** selections as regular application deployments.

## Backups

| Setting | When Coolify sends the notification |
| --- | --- |
| Backup Success | A scheduled database backup completes successfully. |
| Backup Failure | A scheduled database backup fails, or the local backup succeeds but its S3 upload fails. |

- S3 upload warnings use **Backup Failure**: when Coolify creates the local backup but cannot upload its S3 copy, it sends a warning through channels that have **Backup Failure** selected.

## Scheduled tasks

| Setting | When Coolify sends the notification |
| --- | --- |
| Scheduled Task Success | A scheduled application or service task completes successfully. |
| Scheduled Task Failure | A scheduled application or service task fails. |

## Server events

| Setting | When Coolify sends the notification |
| --- | --- |
| Docker Cleanup Success | An automatic Docker cleanup job completes successfully. |
| Docker Cleanup Failure | An automatic Docker cleanup job fails. |
| Disk usage warning (**Server disk usage** on Email) | Server disk usage exceeds the threshold configured for that server. |
| Server Reachable | A previously unreachable server becomes reachable again. |
| Server Unreachable | Coolify cannot reach a server after two consecutive checks. |
| Server Patching | Coolify finds operating system updates during a patch check, or the patch check fails. |
| Traefik Proxy Outdated | Coolify detects one or more servers running an outdated Traefik version. |

## Test the selection

- Email uses **Send test** and asks for a recipient address; Discord, Telegram, Slack, Pushover, and webhook channels use **Send test**.
- A test confirms the current channel credentials can deliver a message — it does not trigger or verify every selected event.
- Webhook event identifiers and fields: see the upstream webhook payload reference (`/core/notifications/channels/webhook/payload-reference`).

## Rate limits

- High-disk alerts default to a 24-hour repeat interval per server; configure the interval under the server's **Advanced** settings.
- A proxy stuck in a restart loop sends at most one container-restarted notification per hour.
