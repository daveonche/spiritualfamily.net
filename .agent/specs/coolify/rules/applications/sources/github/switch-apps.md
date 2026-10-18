# Applications › Sources › GitHub › Switch apps

Source: coollabsio/coolify-docs `content/docs/applications/sources/github/switch-apps.mdx` @ main `3b7c416`.

- Switch an application's GitHub App when the repository moves to another account/org or the access policy changes. The replacement app must have repository access BEFORE Coolify can clone.

## Order of operations

1. **Move the repository** on GitHub only when it is changing account/org; skip when the location is unchanged and only app access changes.
2. **Add the replacement app** (see `app.md`) and install it for the required repository. Keep the existing app installed until the application has deployed successfully through the replacement.
3. **Confirm access**: **Sources > [replacement app] > General > Repositories**; under **Repository access** on GitHub confirm the repo is selected; return to Coolify and refresh the repository list.
4. **Change the source**: application > **Git Source** → select the replacement app → confirm.
5. **Update the Repository field** from the old `owner/repository` to the new location — only when the repo moved; skip when owner and name are unchanged.
6. **Save changes** → **Redeploy** → confirm the expected repo + commit in **Deployment Logs** → push a test commit → confirm the replacement app delivers the webhook and Coolify starts a webhook deployment.

## Cleanup

- Remove the previous GitHub App only after EVERY application using it has been switched and verified.
