# Core › Automation › Scheduled Tasks › Create a Task

Source: coollabsio/coolify-docs `content/docs/core/automation/scheduled-tasks/create-a-task.mdx` @ main `3b7c416`.

## Before you create a task

- Know: the exact command that works inside the resource container; how often it should run; which container or service component should receive it; the longest time it may run.
- If the command changes or deletes data, test it inside the intended container before scheduling it.

## Create the task

- Open the application or service > **Scheduled Tasks** in the resource sidebar > **+ Add** → **New Scheduled Task**.

## Task fields

- **Name**: short description of the command's purpose, e.g. `Run Laravel scheduler` or `Clear expired sessions`.
- **Command**: exactly as it should run inside the container, e.g. `php artisan schedule:run`. Do NOT include `docker exec` — Coolify enters the selected container before running the command.
- **Frequency**: five-field cron expression such as `0 0 * * *`, or a named schedule such as `daily`. The schedule uses the timezone configured for the server that runs the resource — see the cron-syntax rule.
- **Timeout**: maximum execution time in seconds; the dashboard accepts `60`–`36000`, default `300`. Choose a value longer than the task's normal runtime but short enough to stop a stuck command.
- **Container**: leave empty for a single-container application. Multiple containers: select or enter the container that contains the command. Service: select the service component; if entered manually later, use the Docker Compose component name (e.g. `mysql`) without Coolify's generated resource suffix.

## Save and verify

- Click **Add task** — the task is created enabled and added to the **Scheduled Tasks** list.
- Open the new task; if the resource is running, click **Execute now**.
- Under **Recent executions**, open the new execution and confirm: status changes to **Success**; start and end times are shown; the command output matches the expected result.
- Scheduled runs use the same command, container, and timeout; their results appear in the same execution history.

## Troubleshoot

- Invalid cron expression → use a valid five-field expression or a supported named schedule; `daily` is valid, `@every_minute` is not — compare with the cron-syntax rule.
- "More than one container exists" → open the task and set **Container** to the application container or service component that should run the command.
- "No containers are running" → start the application or service, confirm its target container is running, then click **Execute now** again.
