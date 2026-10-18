# Services › Configuration › General

Source: coollabsio/coolify-docs `content/docs/services/configuration/general.mdx` @ main `3b7c416`.

## Service Stack tabs

- **Details**: **Service Name** identifies the resource in project/environment — changing it does NOT rename Compose services or running containers. **Description** is documentation only.
- **Network**: **Connect To Predefined Network** attaches components to the predefined destination network (default `coolify`) with an alias of Compose service name + Service UUID. In-stack traffic keeps using Compose service name + internal port. Do NOT enable it merely to expose an HTTP component — configure a domain instead.
- **Template values**: **Service Specific Configuration** holds one-click-template generated credentials/app values; review helper text + the service guide before changing; saving stores the value in the Service environment and reparses Compose.
- **Compose**: **Edit Compose File** opens the stored definition; **Show Source Compose** (editable) vs **Show Deployable Compose** (generated, read-only); **Validate** for user-defined Services; **Escape special characters in labels?** controls `$` escaping.

## Component cards

- One card per Compose `services:` entry: name, image, status, domain. Actions: **Settings** (component General/Advanced), **Restart** (that container only), **Backups** (recognized database when supported).
- Restart one component only when its dependencies tolerate the interruption; deploy/restart the whole Service when a Compose or shared env change affects several components.

## Application component

- **General**: **Name** (human-readable, no Compose rename), **Description**, **Domains** (add the internal port when ≠ 80, e.g. `https://app.example.com:3000` — visitors still use normal HTTPS), **Image** (written to the generated Compose; review the upstream upgrade path first).
- **Advanced**: **Enable Gzip Compression**, **Strip Prefixes**, **Exclude from service status**, **Drain Logs** (when server log drain is enabled).

## Database component

- **General**: name, description, image; **Make it publicly available** + **Public Port** (TCP proxy). Keep private by default: expose only for outside clients, restrict via firewall, use engine encryption, never reuse generated credentials across environments.
- **Advanced/data**: exclude from aggregate status, log drain; **Backups** + **Import Backup** when the engine is supported.
- Changing a database image tag is NOT an upgrade procedure — create and verify a compatible backup before changing a production image.

## Classification

- **Convert to Database** / **Convert to Application** only when Coolify misclassified the Compose service. Conversion preserves the Compose entry + image and changes only the controls Coolify shows; review domains, public ports, storage, backups, health status, log settings before redeploying.

## Save and verify

1. Save → 2. review **Show Deployable Compose** when generated Compose is affected → 3. restart or deploy → 4. follow deployment output → 5. confirm every required component running + healthy → 6. open domains, test internal dependencies. On failure: inspect the component's **Logs**, compare source vs deployable Compose.
