# Coolify Deployment Guide

Deploy the production stack from the repository's `compose.yml` — the single
source of truth for the app, dbm, and cron services. The development-only
`pma` service is excluded from production.

## Scope

This guide covers creating the production application in Coolify from the
committed `compose.yml` and starting the app, dbm, and cron services as one
stack. The remaining deployment concerns are handled by their own steps in
`docs/analysis/S1.6-story-steps.md`: version-pin verification (Step 2),
environment/secret injection (Step 3), HTTPS entry routing (Step 4),
production email (Step 5), the deploy runbook (Step 6), the database
migration (Step 7), and re-deploy reproducibility (Step 8).

## Prerequisites

- A Coolify instance with a connected Git source for this repository
- The committed `compose.yml` at the project root
- The committed `docker/app/Dockerfile` build context — the app service is
  built from it during deployment

## Create the application from the Compose file

1. In Coolify, create a new resource and select the Docker Compose
   deployment type.
2. Select this repository and branch as the source.
3. Set the Compose file path to `compose.yml`.
4. Let Coolify parse the file: it lists the services the file defines —
   app, dbm, cron, and pma.

Exact menu labels vary between Coolify versions; choose the deployment type
that deploys a Compose file as a stack of services.

## Service inventory

| Service | Role | Image / build | In production |
| :--- | :--- | :--- | :--- |
| app | Elgg web server | Built from `docker/app/Dockerfile` via the `PHP_VERSION` build arg | Yes |
| dbm | MariaDB database | Pinned `mariadb:10.11.18` | Yes |
| cron | Scheduled tasks | Pinned `alpine:3.23.3` | Yes |
| pma | phpMyAdmin — development-only database administration | Pinned `phpmyadmin/phpmyadmin:5.2.3` | No — exclude |

The three production services form one startable stack through the
`depends_on` chain in `compose.yml`: app starts only once dbm passes its
healthcheck, and cron starts after app. All three restart automatically
(`restart: unless-stopped`) on the shared `appnet` network.

## Exclude the pma service

`pma` is a development-only database administration UI. It is a leaf
service: it depends on dbm, but nothing depends on it — so excluding it from
production breaks nothing.

1. In the parsed service list, locate `pma`.
2. Disable it so Coolify does not deploy it (per-service toggle in the
   Compose-based application).
3. Confirm the remaining deployed services are exactly app, dbm, and cron.

## Deploy the stack

1. Set the required environment variables in the application's environment
   settings in Coolify — the committed `.env.example` documents every
   required variable, and no real `.env` is committed. The full
   secrets-injection treatment is Step 3 of the story steps.
2. Trigger the deployment. Coolify clones the repository, builds the app
   image from `docker/app/Dockerfile`, and starts the services in dependency
   order: dbm first, then app, then cron.
3. Confirm all three services reach the running state.

## Verify

- [ ] The application in Coolify lists exactly the app, dbm, and cron
  services as one stack.
- [ ] `pma` is not deployed and not running in the production stack.
- [ ] The services started in dependency order (app after dbm healthy, cron
  after app).
