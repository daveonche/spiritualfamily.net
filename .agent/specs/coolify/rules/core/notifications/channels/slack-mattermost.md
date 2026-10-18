# Core › Notifications › Channels › Slack & Mattermost

Source: coollabsio/coolify-docs `content/docs/core/notifications/channels/slack-mattermost.mdx` @ main `3b7c416`.

## Provider model

- Coolify's **Slack** notification channel accepts a Slack incoming webhook URL.
- Mattermost incoming webhooks use a compatible message format, so Mattermost is configured on the same Coolify **Slack** page.

## Slack: create the incoming webhook

- Create the app: [Slack API apps page](https://api.slack.com/apps) > **Create New App > From scratch** > name e.g. `Coolify Notifications` + select the workspace that owns the destination channel > **Create App**. You need permission to install or authorize apps in the workspace; if approval is required, ask a workspace administrator.
- Create the webhook: **Incoming Webhooks** in the app settings > turn on **Activate Incoming Webhooks** > **Add New Webhook to Workspace** > choose the channel that should receive Coolify notifications and authorize the app > copy the URL under **Webhook URLs for Your Workspace**.
- Add to Coolify: **Notifications > Slack** > paste the URL into **Webhook URL** > **Save changes** > **Enable**.

## Mattermost: create the incoming webhook

- Prerequisites: the Mattermost administrator must allow incoming webhooks, and the account needs permission to create one. If **Integrations** or **Incoming Webhooks** is missing, contact the administrator.
- **Product Menu > Integrations > Incoming Webhooks** > **Add Incoming Webhook** > enter a recognizable title and description > choose the destination channel > save the webhook and copy the generated URL (normally resembles `https://mattermost.example.com/hooks/...`).
- Mattermost displays the complete URL only after creation — copy it then; it grants permission to post through the integration.
- Add to Coolify: **Notifications > Slack** > paste the Mattermost URL into **Webhook URL** > **Save changes** > **Enable**.

- WARNING: the webhook URL authorizes posts to its destination channel — store it as a secret and replace it in the provider and Coolify if it is exposed.

## Choose events and verify delivery

- Under **Notification events**, select the events to send through this channel — see the notification-events rule for each trigger.
- Select **Send test** and confirm the message arrives in the Slack or Mattermost channel. If it does not, verify the incoming webhook remains enabled and still targets a channel the integration can access.

## Troubleshoot Slack or Mattermost delivery

- Confirm the saved URL belongs to the provider and workspace or server you are checking.
- Confirm the webhook still exists and still targets an accessible channel.
- If the app or webhook was reinstalled or recreated, copy the new URL into Coolify and save it.
- Slack: confirm the app remains authorized for the workspace and channel.
- Mattermost: confirm incoming webhooks remain enabled in the server's integration settings.
- Send another test before enabling production events.
