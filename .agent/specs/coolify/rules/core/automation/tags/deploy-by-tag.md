# Core › Automation › Tags › Deploy by Tag

Source: coollabsio/coolify-docs `content/docs/core/automation/tags/deploy-by-tag.mdx` @ main `3b7c416`.

## Scope

- Deploy by tag when several applications and services should be started or redeployed as one group.
- Coolify acts on every resource assigned to the selected tag in the active team.

## Before deploying

- **Tags** (main sidebar) > select the tag > review every application and service under **Tag Details**.
- Remove the tag from any resource that does not belong in the deployment group before continuing.
- WARNING: **Redeploy All** starts work for the full group — resources can be temporarily unavailable while their deployment or restart is in progress.

## Redeploy from the dashboard

- **Tags** > select the tag > review the group under **Tag Details** (select any resource card to inspect its configuration first).
- Click **Redeploy All** > enter the tag name in the confirmation dialog > click **Redeploy All** again.
- Coolify queues a deployment for each application and starts each service assigned to the tag.

## Verify

- Watch **Deployments** on the tag page for queued and in-progress application deployments; open each deployment to inspect its logs.
- Services: open each service page and confirm its components are running.

## Automate with a webhook

- The tag's **Deploy Webhook URL** calls the same team-scoped deployment API — use from CI or other automation.
- Follow the deploy-webhooks rule to create a token with `deploy` permission, call the URL, and verify the response.

## Troubleshoot

- Resource missing from the tag page → confirm the same tag is assigned on the resource's **Tags** page and that you are in the team where the tag was created.
- No deployments after Redeploy All → confirm the tag still has applications or services assigned; application deployments appear in the tag's **Deployments** list, services must be checked from their resource pages.
