# Core › Notifications › Channels › Webhook › Payload reference

Source: coollabsio/coolify-docs `content/docs/core/notifications/channels/webhook/payload-reference.mdx` @ main `3b7c416`.

## Common fields (every payload)

| Field | Type | Meaning |
| --- | --- | --- |
| `success` | boolean | `true` success events; `false` failures and alerts |
| `event` | string | Machine-readable event identifier |
| `message` | string | Human-readable summary |

- Requests: JSON `POST`, `Content-Type: application/json`.

## Application deployments

- `deployment_success` / `deployment_failed`: + `application_name`, `application_uuid`, `deployment_uuid`, `deployment_url`, `project`, `environment`, `fqdn` (only when the app has a domain; may be absent from either type).
- PR preview deployments add `pull_request_id` + `preview_fqdn`.
- `status_changed`: application stopped unexpectedly (not manual stop) — no `deployment_uuid`; includes `url`.
- `restart_limit_reached`: Coolify stopped the app at the automatic restart limit — + `restart_count`, `max_restart_count`, `url`.

## Database backups

- `backup_success` / `backup_failed`: + `database_name`, `database_uuid`, `database_type`, `frequency`, `url`; failed adds `error_output`.
- `backup_success_with_s3_warning`: local backup succeeded, S3 upload failed — `success: true` + `s3_error`, `s3_storage_url` (only when available), `url`.

## Scheduled tasks

- `task_success` / `task_failed`: + `task_name`, `task_uuid`, `output`, `url`; `application_uuid` for application-level tasks, `service_uuid` for service-level tasks.

## Docker cleanup

- `docker_cleanup_success` / `docker_cleanup_failed`: + `server_name`, `server_uuid`, `url`; success adds `cleanup_message`, failure adds `error_message`.

## Server events

- `server_reachable` / `server_unreachable`: + `server_name`, `server_uuid`, `url`.
- `high_disk_usage`: + `disk_usage`, `threshold` (integer percentages), `url`.
- `server_patch_check`: + `total_updates`, `os_id`, `package_manager`, `updates` (array), `critical_packages_count`, `url`.
- `server_patch_check_error`: + `os_id`, `package_manager`, `error`, `url`.
- `traefik_version_outdated`: + `affected_servers_count`, `servers[]` — each: `name`, `uuid`, `current_version`, `latest_version`, `update_type` (`patch_update` | `minor_upgrade`), `upgrade_target` (minor_upgrade only), `newer_branch_target`/`newer_branch_latest` (when a newer major/minor branch exists).

## Container events

- `container_stopped` (unexpected, not manual) / `container_restarted` (automatic): + `container_name`, `server_name`, `server_uuid`, `url` (only when available).

## Test

- `test`: sent by **Send test** in the dashboard — `success: true`, `event: "test"`, `message`, `url`.
