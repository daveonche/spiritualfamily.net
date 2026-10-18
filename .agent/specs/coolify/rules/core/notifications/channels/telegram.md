# Core › Notifications › Channels › Telegram

Source: coollabsio/coolify-docs `content/docs/core/notifications/channels/telegram.mdx` @ main `3b7c416`.

## Create the bot and destination

- Coolify sends Telegram notifications through a bot; you need the bot API token and the ID of the group, channel, or chat that should receive notifications.
- Bot: open [BotFather](https://t.me/botfather) and confirm the account is the official `@BotFather` > send `/newbot` > choose a display name and a unique username > copy the bot API token.
- Destination: create or choose a group, channel, or direct chat — a dedicated group keeps deployment and server alerts separate from general conversation. Add the bot by searching for its username. Channel: promote the bot to administrator with permission to post. Group: ensure its permissions allow the bot to send messages; promote it if restrictions require.
- Optional topics: for a group with **Topics** enabled, open the group's settings > edit > enable **Topics** > create topics such as `Deployments`, `Backups`, `Servers` > send a message inside each topic whose ID you need. Telegram shows the Topics option only for groups that support forum topics.
- Generate an update: send a new message in the destination (inside the relevant topic when using topics) — Telegram must deliver an update to the bot before its API response contains the chat or thread ID.
- WARNING: anyone with the bot token can use the bot API — store the token as a secret; if exposed, use BotFather to revoke it and create a replacement.

## Find the chat and topic IDs

- Open `https://api.telegram.org/bot<BOT_TOKEN>/getUpdates` in a browser, replacing the entire `<BOT_TOKEN>` placeholder with the token from BotFather.
- Find the newest `message` object in the JSON response.
- Copy `message.chat.id` for the **Chat ID** — group and channel IDs are often negative; include the leading `-`.
- If the message was sent inside a topic, copy `message.message_thread_id` for that topic.

For example, the relevant part of a response can look like this:

```json
{
  "message": {
    "message_thread_id": 42,
    "chat": {
      "id": -1001234567890
    }
  }
}
```

- If the response does not include the destination, send another message in the group or topic and request `getUpdates` again.

## Configure Coolify

- **Notifications > Telegram**: enter the **Bot API token** and **Chat ID** exactly as returned by Telegram, including a leading `-` in the chat ID > **Save changes** > **Enable**. If Coolify does not keep the channel enabled, recheck that both required values were saved.
- Under **Notification events**, select the events to send to Telegram.
- Topic routing: enable the event, then enter that topic's numeric **Topic ID** for it under **Forum topics** > **Save changes**. Leave it empty to send the event to the main chat.
- **Send test** and confirm it arrives in the configured chat — the general test uses the main chat configuration, not a custom thread ID.

- Custom thread ID limitations: currently apply to deployment success and failure, application and container stopped or restarted events, backup success and failure, scheduled tasks, Docker cleanup, disk usage, server reachability, and server patching. Restart-limit alerts, backup S3 upload warnings, Traefik proxy outdated alerts, and test notifications use the main chat without a custom thread ID.

## Troubleshoot Telegram delivery

- Confirm the BotFather token was copied without spaces and the full `<BOT_TOKEN>` placeholder was replaced in the `getUpdates` URL.
- Send a new message in the destination and load `getUpdates` again if the response is empty or does not show the chat.
- Include the leading minus sign in a negative group or channel ID.
- Confirm the bot is still a member of the destination and has permission to post — channels normally require the bot to be an administrator.
- If only a topic-routed event fails, confirm the thread ID belongs to the same group as the saved chat ID and the event supports custom thread IDs.
- If the token was revoked in BotFather, save the replacement token in Coolify and test again.
- See the notification-events rule for the trigger behind each event setting.
