# Core › Automation › Scheduled Tasks › Execution History

Source: coollabsio/coolify-docs `content/docs/core/automation/scheduled-tasks/execution-history.mdx` @ main `3b7c416`.

## Open execution history

- Coolify records each scheduled and manual task run under **Recent executions** — use the records to confirm a command finished and to inspect its output when it fails.
- Open the application or service > **Scheduled Tasks** > select the task > scroll to **Recent executions**.
- Coolify shows the 20 most recent execution records, newest first.

## Read an execution

| Status | Meaning | What to do |
| --- | --- | --- |
| In Progress | The command is still running. | Keep the task page open while Coolify refreshes the record. |
| Success | The command exited without a reported error. | Open the output and confirm it produced the intended result. |
| Failed | The command failed, timed out, or could not run in the selected container. | Open the output and start with the first reported error. |

- Completed records also show start time, end time, and duration, displayed in the timezone configured for the server that runs the resource.

## Inspect and download output

- Seeing scheduled commands and output requires permission to update the resource; API access to task output requires `read:sensitive`.
- Click an execution record to expand its captured output.
- **Load More** reveals another group of lines; **Load All** shows the complete captured output; **Download Logs** saves non-empty output as a `.log` file.
- An execution can succeed without printing output — verify the actual result of the command when output alone is not enough.

## Troubleshoot

- Execution stays In Progress → executions exceeding their timeout are marked failed with the timeout error; lost queued scheduled tasks are recorded as failed and the team is notified. Refresh the task page to review the result; if the command regularly needs more time, increase **Timeout** (up to `36000` seconds) and run it again.
- No output was recorded → the command may not write to standard output or standard error; check the effect the command was meant to produce, and update the command or script to print a concise success or failure message if a visible record is needed.
- Failed because no valid container was found → confirm the resource is running and **Container name** or **Service name** matches the intended component; save the task, then click **Execute Now**.
