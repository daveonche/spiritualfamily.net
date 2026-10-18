# Core › Notifications › Channels › Webhook › Setup

Source: coollabsio/coolify-docs `content/docs/core/notifications/channels/webhook/setup.mdx` @ main `3b7c416`.

## Scope

- Webhook notifications send a JSON `POST` request to an endpoint you control — use this channel to connect Coolify to an automation workflow, incident system, or custom application.

## Before you start

- Prepare an endpoint that: accepts `POST` requests over HTTP or HTTPS; accepts a JSON request body; is reachable from the Coolify instance; records requests or provides another way to verify delivery; returns a successful HTTP response promptly after accepting a request; can safely receive the same event more than once if a delivery is retried.
- For an initial test, use a development endpoint or request inspector where you can view the method, headers, and JSON body — move to the production endpoint only after the test succeeds.

## Configure the webhook

- **Notifications > Webhook**: enter the endpoint in **Webhook URL** > **Save changes**.
- Select **Enable** — Coolify requires a valid webhook URL before it enables the channel.
- Under **Notification events**, select the events to send to the endpoint — see the notification-events rule for each trigger.
- Select **Send test** and inspect the request received by your endpoint. Confirm all of the following: the request method is `POST`; the body is valid JSON; the body contains `"event": "test"`; your endpoint returns a successful response without waiting on long-running work.
- A received test confirms that Coolify can reach the current URL and submit a request — it does not verify that every event-specific field is handled by your application.

## Endpoint requirements

- Coolify accepts URLs with the `http` or `https` scheme; the URL must contain a valid host.
- For server-side request protection, Coolify rejects: `localhost`, `0.0.0.0`, and IPv6 loopback addresses; IPv4 addresses in `127.0.0.0/8`; link-local IPv4 addresses in `169.254.0.0/16`, including common cloud metadata endpoints; hostnames ending in `.internal`.
- Private-network IP addresses are accepted, but the Coolify instance must still have a network route to the endpoint.

## Delivery behavior

- Coolify queues webhook deliveries and sends the JSON request body with Laravel's HTTP client — a delivery job allows up to five total attempts, with a 10-second backoff after an unhandled exception.
- Coolify currently does not fail the job only because the endpoint returns a non-2xx HTTP status — return a 2xx response when your application accepts the event, and use the receiver's logs or monitoring to detect rejected requests.
- Your endpoint should respond promptly and handle duplicate event payloads safely.
- WARNING: Coolify does not add a signature or shared-secret header — use an unguessable HTTPS URL or an authenticated gateway when the endpoint is exposed to the internet.
- Event identifiers and fields: see the upstream webhook payload reference (`/core/notifications/channels/webhook/payload-reference`).

## Troubleshoot webhook delivery

If the test does not reach your endpoint:
1. Confirm that the URL starts with `http://` or `https://` and includes the complete path expected by the receiver.
2. Confirm that the URL is not in one of the blocked loopback, link-local, or `.internal` ranges above.
3. From the Coolify server's network, confirm that DNS resolves the hostname and that firewalls allow the connection.
4. Check the receiving service's access and application logs — a request can reach a reverse proxy but fail before the application records it.
5. Confirm that the endpoint accepts `POST` with a JSON body and responds promptly.
6. If a private IP is used, confirm that the Coolify instance has a route to that private network.

- If the test succeeds but a production event does not arrive, confirm that the event is enabled under **Notification events** and compare the receiver's parser with the payload for that event (upstream: `/core/notifications/channels/webhook/payload-reference`).
