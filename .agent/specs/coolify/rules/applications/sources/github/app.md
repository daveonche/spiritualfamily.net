# Applications › Sources › GitHub › App

Source: coollabsio/coolify-docs `content/docs/applications/sources/github/app.mdx` @ main `3b7c416`.

## Method choice

- GitHub App = repository selection + automatic deployments + PR events through one integration. Deploy key when an app cannot be installed or read-only single-repo access suffices.
- **Automated installation** (GitHub manifest flow) is recommended: fewer credentials transferred manually. **Manual installation** for GitHub Enterprise or custom permission setups.

## Automated installation

1. **Sources > New source > GitHub App**: name; **Organization** empty for a personal account; **Use this App for > Pull request previews** when previews are needed. Self-hosted GHE: expand **Self-hosted / Enterprise GitHub** (HTML URL, API URL, Git user, Git port).
2. **System wide** (self-hosted only) exposes the app to every team on the instance — leave disabled for team-only use; Coolify Cloud does not offer it.
3. **Webhook endpoint**: instance endpoint or custom endpoint (e.g., a tunnel domain). Enter the BASE URL only — never append `/webhooks`. GitHub must reach it: repo access can work while automatic and preview deployments fail when it cannot.
4. **Preview deployment access > Read and update pull requests** grants read-write PR access. Mandatory permissions: contents, metadata, email addresses (read).
5. **Register with GitHub** → unique app name → **Create GitHub App** → GitHub returns to Coolify, which stores App ID, client credentials, webhook secret, and private key.
6. **Install repositories**: All repositories or Only select repositories; confirm the source shows installation + repository-management actions.
7. Create the application: **+ New > Private Repository (with GitHub App)**; server; the GitHub App source; repo + **Load Repository**; branch, Build pack, base directory, internal port; deploy; confirm clone + commit in **Deployment Logs**.

## Manual installation

- Transfer: App ID, Installation ID, Client ID, Client Secret, Webhook Secret, private key.
- Create the app on GitHub (personal: Account settings > Developer settings > GitHub Apps > New; org: Org settings > Developer settings > GitHub Apps > New).
- Configure: unique **GitHub App name**; **Homepage URL** = public Coolify URL; webhook URL = `https://<coolify.example.com>/webhooks/source/github/events`; long random **Webhook secret** (store like a password); SSL verification enabled.
- Repository permissions: Contents / Metadata / Email addresses = Read-only; Pull requests = Read and write ONLY when previews are required.
- Subscribe to events: **Push**; **Pull request** when previews are required; limit installation to the current account unless others must install it.
- Collect: **App ID** + **Client ID**; **Generate a new client secret**; **Generate a private key** (`.pem` download). Keep all three confidential.
- Install: **Install App** > account/org > All or selected repositories; open installation settings; copy the numeric **Installation ID** from the URL after `/installations/`.
- Coolify: **Keys & Tokens > Private Keys > New private key > Add manually** — paste the COMPLETE `.pem` contents. Then source **General**: App ID, Installation ID, Client ID, Client Secret, the SAME Webhook Secret configured on GitHub; select the private key; **Save changes**; **Sync name** — a successful sync confirms Coolify can authenticate as the app.

## Change repository access

- **Sources > [app] > General > Repositories** → change the installation's repository access on GitHub → return and refresh the repository list before creating another application.
- Removing repository access prevents new clones AND webhook deployments for applications using that repository.
