# Core › Notifications › Channels › Discord

Source: coollabsio/coolify-docs `content/docs/core/notifications/channels/discord.mdx` @ main `3b7c416`.

## Create a Discord webhook

- Coolify sends Discord notifications through an incoming webhook created for a Discord text channel.
- Open the Discord server that should receive notifications; create a text channel (e.g. `coolify-notifications`) or select an existing one. You need permission to manage webhooks in that server.
- **Server Settings > Integrations > Webhooks** > **New Webhook** > give it a recognizable name (e.g. `Coolify`) > select the text channel that should receive its messages > **Copy Webhook URL**.
- See Discord's webhook documentation for provider-specific details.
- WARNING: anyone with the URL can post to the Discord channel — store it as a secret and replace the webhook if the URL is exposed.

## Configure Coolify

- **Notifications > Discord**: paste the URL into **Webhook URL** > **Save changes** > **Enable**.
- Optional: set **Critical event mention** to **Mention @here** to add `@here` to critical event notifications — Discord notifies only members who are online and can access the channel.
- Under **Notification events**, select the events to send to Discord.
- Select **Send test**; confirm the test appears in the selected Discord channel before selecting additional events.

## Troubleshoot Discord delivery

If the test does not appear:
- Return to **Server Settings > Integrations > Webhooks** and confirm the webhook still exists.
- Confirm it targets the channel you are checking — a webhook can be moved to another channel without changing the workflow in Coolify.
- Copy the URL again and replace the saved value in Coolify if the webhook was recreated or its token changed.
- Confirm the destination channel still exists and the webhook can post to it.
- Save the URL before selecting **Enable**, then send another test.
- See the notification-events rule for the trigger behind each event setting.
