# Core › Notifications › Channels › Pushover

Source: coollabsio/coolify-docs `content/docs/core/notifications/channels/pushover.mdx` @ main `3b7c416`.

## Create a Pushover application

- Coolify sends Pushover notifications with your Pushover user key and an API token created for a Pushover application.
- Before configuring Coolify, sign in to Pushover and register at least one phone, tablet, or desktop client with the account — the test notification is delivered to devices registered to that account.
- User key: sign in to the [Pushover dashboard](https://pushover.net/) > copy **Your User Key**. It identifies the receiving account and is NOT the same as the application API token.
- Application token: open [Create an Application/API Token](https://pushover.net/apps/build) > enter an application name such as `Coolify` > accept the terms and create the application (description, URL, and icon are optional unless Pushover states otherwise) > open the created application > copy its **API Token/Key**. It identifies Coolify as the sender and tracks message usage separately.
- WARNING: store the Pushover API token as a secret — if exposed, replace the token in Pushover and update Coolify.

## Configure Coolify

- **Notifications > Pushover**: enter the **User key** and **API token** > **Save changes** > **Enable**.
- Under **Notification events**, select the events to send through Pushover.
- Select **Send test**; confirm the test arrives on a device connected to your Pushover account before selecting more events.

## Troubleshoot Pushover delivery

- Confirm **User key** came from the receiving account dashboard.
- Confirm **API token** came from the Pushover application created for Coolify — do not enter the user key in both fields.
- Confirm at least one device is registered and active on the receiving Pushover account.
- Open the Pushover application page and check its message usage and status.
- If you regenerated the application token, save the new value in Coolify and test again.
- See the notification-events rule for the trigger behind each event setting.
