# Core › Automation › Tags › Manage Tags

Source: coollabsio/coolify-docs `content/docs/core/automation/tags/manage-tags.mdx` @ main `3b7c416`.

## Create and assign

- Manage tags from the application or service that should belong to the group.
- Coolify creates a new team tag when the name does not exist and reuses an existing tag when it does.
- Open the resource > **Tags** > enter a name in **Create new or assign existing tags** > click **Add**; new and reused tags appear under **Assigned Tags**.
- Multiple names at once: separate with spaces, e.g. `production storefront eu` — not commas.
- Each name must contain at least two characters; Coolify removes HTML and stores names in lowercase.

## Assign an existing tag

- Team tags not assigned to the resource appear under **Existing Tags**; click a name to assign it without re-entering.
- The same tag can be assigned to applications and services in different projects or environments within the team.

## Remove

- Under **Assigned Tags**, click the remove icon beside the tag name — removed from this resource immediately.
- Removing a tag does not stop, restart, or redeploy the resource.

## Tag lifecycle

- Coolify removes a tag from the team after it is detached from its last application or service; there is no separate delete action on the main **Tags** page.
- To keep a grouping, leave the tag assigned to at least one application or service.

## Troubleshoot

- Invalid tag → use a name with at least two characters; separate multiple names with spaces, not commas.
- Duplicate tag → the resource already has that tag; check **Assigned Tags** instead of adding it again.
- Tags page is read-only → permission to update the resource is required; ask a team administrator to update the role or make the change.
