# Applications › Builds › Custom Docker options

Source: coollabsio/coolify-docs `content/docs/applications/builds/custom-docker-options.mdx` @ main `3b7c416`.

- **General > Custom Docker Options** adds container-runtime args only; image construction belongs in a Dockerfile; use dedicated settings for CPU/memory/GPU/ports/storage/env when they exist.
- Supported allow-list (anything else is not applied): `--ip --ip6 --shm-size --dns --cap-add --cap-drop --security-opt --sysctl --device --ulimit --init --privileged --gpus --hostname --entrypoint`.
- Example: `--shm-size 1g --init`.
- `--entrypoint` accepted forms: `--entrypoint /bin/sh`, `--entrypoint="/app/start-worker.sh"`, `--entrypoint "sh -c 'php artisan horizon'"` (quoted form for spaces/args); converted to the Compose `entrypoint` field.
- The executable must exist inside the image; if it exits, Docker treats the container as stopped.
- Use cases: run a worker from the same image (`--entrypoint "sh -c 'npm run worker'"`); run an image-owned init script (`--entrypoint /app/custom-init.sh`).
- Exactly one `--entrypoint` value; put extra arguments inside the quoted value.
- `--privileged`, `--device`, and added capabilities weaken isolation — grant only what the app requires.
