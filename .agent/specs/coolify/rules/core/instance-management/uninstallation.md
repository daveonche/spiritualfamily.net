# Core › Instance Management › Uninstallation

Source: coollabsio/coolify-docs `content/docs/core/instance-management/uninstallation.mdx` @ main `3b7c416`.

## Scope

- Self-hosted instance: the steps remove Coolify's containers and its data.
- Coolify Cloud: the steps remove Coolify Proxy and Sentinel from connected servers.
- Deployed applications and services may keep running after removal. Removing Coolify Proxy stops the domains and TLS routing it provides.

## Before you uninstall (self-hosted)

- Create an instance backup (see the instance-backup rule) and keep a copy outside this server.
- Save `/data/coolify/source/.env` separately — it contains the encryption key needed to restore the instance backup.
- Save or migrate the application configuration, deployment files, local backups, and proxy routing you need after removal — they are stored under `/data/coolify`.
- Plan a replacement proxy before continuing if your resources use Coolify-managed domains or TLS certificates.
- Confirm there are no active deployments and you are connected to the Coolify server over SSH.
- DANGER: deleting `/data/coolify` permanently removes Coolify's configuration, SSH keys, deployment files, proxy configuration, and locally stored instance backups. An instance backup contains the Coolify database; it does not back up the applications, databases, services, or volumes you deploy with Coolify.

## Remove the self-hosted instance

- Connect to the server that runs the instance over SSH: `ssh root@<server-ip>` (non-root sign-in: run `sudo -i` first to open a root shell).
- Remove the control-plane containers:

```sh
docker rm -f coolify coolify-db coolify-redis coolify-sentinel
```

- Installations from before the Reverb migration also have a `coolify-realtime` container (see the reverb-migration rule) — remove it as well if it exists:

```sh
docker rm -f coolify-realtime
```

- Application, database, and service containers keep running.
- Remove Coolify Proxy according to the Docker mode used by the server — standalone Docker: `docker rm -f coolify-proxy`; Docker Swarm (run on a manager node): `docker stack rm coolify-proxy`.
- Remove the volumes that store the Coolify database and Redis data:

```sh
docker volume rm coolify-db coolify-redis
```

- Do NOT run a broad `docker volume prune` as part of this process — it can remove unused volumes that belong to other workloads on the server.
- If application or service containers still use the `coolify` network, connect them to another network before removing it — or leave the network in place if they should keep using it. When no workloads need the network, remove it:

```sh
docker network rm coolify
```

- After confirming that your backup and any application or service files you still need are stored elsewhere, delete the Coolify data directory — the final destructive step, permanently removing the instance `.env` file, local instance backups, and the deployment and configuration files Coolify stored under `/data/coolify`:

```sh
rm -rf /data/coolify
```

## Remove the self-hosted images

- Image tags vary depending on when you last updated Coolify — run `docker images` to see the images and tags on your server, then use those tags in the commands.
- Remove the default Coolify images to free disk space:

```sh
docker rmi ghcr.io/coollabsio/coolify:latest
docker rmi ghcr.io/coollabsio/coolify-helper:latest
docker rmi ghcr.io/coollabsio/sentinel:1.0.1
docker rmi postgres:15-alpine
docker rmi redis:7-alpine
```

- If you removed a `coolify-realtime` container, remove its image too: `docker rmi ghcr.io/coollabsio/coolify-realtime:1.0.16`.
- If you used the default Traefik proxy, remove its image too: `docker rmi traefik:v3.7`; for Caddy instead: `docker rmi lucaslorentz/caddy-docker-proxy`.

## Verify the self-hosted removal

- Confirm no Coolify control-plane containers remain — the command should return no container names:

```sh
docker ps -a --format '{{.Names}}' | grep -E '^(coolify|coolify-db|coolify-redis|coolify-realtime|coolify-sentinel|coolify-proxy)(_|$)' || true
```

- Confirm Docker reports the `coolify-db` and `coolify-redis` volumes as missing and `/data/coolify` no longer exists:

```sh
docker volume inspect coolify-db coolify-redis
test ! -d /data/coolify && echo "Coolify data directory removed"
```

- `docker volume inspect` should report that the two volumes do not exist.
- If applications or services remain on the server, manage or migrate them directly with Docker and replace Coolify Proxy routing before exposing them publicly again.

## Remove the Coolify Cloud services

- Before you uninstall: cancel the subscription from the Billing page (`https://app.coolify.io/subscription/`) and plan a replacement proxy if resources use Coolify-managed domains or TLS certificates.
- Connect to the server connected to Coolify Cloud over SSH: `ssh root@<server-ip>` (non-root sign-in: `sudo -i` first to open a root shell).
- Remove the proxy according to the Docker mode used by the server — standalone Docker: `docker rm -f coolify-proxy`; Docker Swarm (run on a manager node): `docker stack rm coolify-proxy`.
- Remove Sentinel if it is running on the server:

```sh
if docker container inspect coolify-sentinel >/dev/null 2>&1; then
  docker rm -f coolify-sentinel
fi
```

- If application or service containers still use the `coolify` network, connect them to another network first or leave the network in place; when no workloads need it, remove it: `docker network rm coolify`.
- Back up any application or service files you still need from this server, then remove the Coolify data directory — this permanently deletes the deployment and configuration files Coolify stored under `/data/coolify` on this server:

```sh
rm -rf /data/coolify
```

- Remove the default images (same tag caveat — run `docker images` first): `docker rmi ghcr.io/coollabsio/coolify-helper:latest` and `docker rmi ghcr.io/coollabsio/sentinel:1.0.1`; plus `docker rmi traefik:v3.7` for the default Traefik proxy or `docker rmi lucaslorentz/caddy-docker-proxy` for Caddy.

## Verify the Cloud removal

- Confirm no Coolify control-plane containers remain — the command should return no container names:

```sh
docker ps -a --format '{{.Names}}' | grep -E '^coolify-(proxy|sentinel)(_|$)' || true
```

- If applications or services remain on the server, manage or migrate them directly with Docker and replace Coolify Proxy routing before exposing them publicly again.
