# Core › Automation › Scheduled Tasks › Manage Tasks

Source: coollabsio/coolify-docs `content/docs/core/automation/scheduled-tasks/manage-tasks.mdx` @ main `3b7c416`.

## Open a task

- Each application and service keeps its own scheduled tasks.
- Open the application or service > **Scheduled Tasks** > select the task; the task page shows its configuration and recent executions.

## Update configuration

- Change **Name**, **Schedule**, **Timeout (seconds)**, **Container** or **Service**, and **Command** > click **Save changes**; Coolify validates the schedule before saving.
- After changing the command or target container, run the task manually and review its output before relying on the next scheduled execution.

## Enable or disable

- **Disable** stops future scheduled executions without deleting the configuration or its history; **Enable** restores scheduled runs.
- Disabling does not stop an execution that is already running.

## Run a task now

- When the application or service is running, click **Execute now** to dispatch the task immediately.
- Manual execution uses the saved command, container, and timeout but does not change the task's next scheduled time.
- Open the new record under **Recent executions** to verify the result; if **Execute now** is not shown, start the resource first.

## Delete

- WARNING: deletion is permanent — the configuration is removed from the resource and its execution history is no longer available from the dashboard. Disable the task instead if you may need it again.
- Open the task > **Delete** > enter the scheduled task name shown in the confirmation dialog > **Permanently Delete**.
- Coolify returns to the resource's **Scheduled Tasks** list; the deleted task no longer runs.

## Troubleshoot

- Saved task does not run on schedule → confirm the task is enabled, the application or service is running, and its server is validated and functional; compare **Schedule** with the cron-syntax rule and the server timezone.
- Execute Now runs the command in the wrong container → open the task and set **Container** or **Service** to the component that contains the command; save the change before running again.
