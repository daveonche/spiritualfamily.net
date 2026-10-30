# Workflow State
Last updated: 2026-10-30

## Active Workflow
- Command: (none — no active workflow; side-task complete)
- Hazard: the aider shell exports an empty ELGG_LOG_FILE (shadows .env) — recreate cron only with the ELGG_LOG_FILE=/var/www/dataroot/logs/elgg.log prefix until aider is restarted from a fresh shell
- Last completed: side-task done — root-exec footgun note folded into AGENTS.md ("Dataroot writes from exec sessions": run dataroot-writing one-offs as --user 33:33, chown repair via the container's ELGG_LOG_FILE, root-owned rotation archives expected)
- Reload to resume: (none pending — request the next task's files when the next task is chosen)
