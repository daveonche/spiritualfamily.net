# Applications › Sources › GitHub › Public repository

Source: coollabsio/coolify-docs `content/docs/applications/sources/github/public-repository.mdx` @ main `3b7c416`.

- Deploy any public GitHub repository without a GitHub App or deploy key.

1. Project > environment > **+ New** > **Public Repository**.
2. Choose the server (self-hosted starts with **localhost**; selected automatically when no other deployment server is available).
3. Paste the public repo URL — branch comes from the URL path: `.../coolify-examples/tree/next` selects `next`; a bare repo URL selects `main`.
4. **Check Repository** → choose **Build pack**, internal port, base directory, other settings.
5. Continue to application configuration; set the domain and required variables; deploy.
6. Confirm repository, branch, and commit in **Deployment Logs**.
