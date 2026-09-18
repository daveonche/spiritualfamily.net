# Core Requirements for Spiritual Family Network

## Functional Requirements

### Platform Upgrade

- REQ-FR-PU-1: The platform shall be upgraded from Elgg 2.3 to Elgg 7 (latest stable Elgg version).
- REQ-FR-PU-2: All third-party plugins shall be dropped from the codebase as part of the upgrade.
- REQ-FR-PU-3: A list of all dropped third-party plugins shall be produced, recording for each plugin whether an upgraded Composer replacement is available or it must be installed manually.
- REQ-FR-PU-4: Features delivered by dropped third-party plugins shall be restored on Elgg 7 via upgraded Composer-installed versions where available, and via manually installed plugin versions where no Packagist package exists.
- REQ-FR-PU-5: The upgrade shall follow Elgg's supported major-version upgrade sequence (Elgg 2.3 to Elgg 3, Elgg 3 to Elgg 4, and so on through Elgg 7), one major version at a time.
- REQ-FR-PU-6: Dropped third-party plugins without an available replacement shall remain on the dropped-plugins list, with individual plugin upgrades planned in a later sprint.

### Theme Replacement

- REQ-FR-TR-1: The current theme stack (Aalborg theme, Aalborg Extras, L1, custom_index, paul_override) shall be replaced with a new theme, with custom index functionality delivered by the Elgg 7.1.0 bundled custom_index plugin.

### Content Features

- REQ-FR-CF-1: Blog functionality shall be preserved.
- REQ-FR-CF-2: Bookmarks functionality shall be preserved.
- REQ-FR-CF-3: Discussions functionality shall be preserved.
- REQ-FR-CF-4: Site Pages functionality shall be preserved.
- REQ-FR-CF-5: The Wire functionality shall be preserved.
- REQ-FR-CF-6: CKEditor (rich text editing) functionality shall be preserved.
- REQ-FR-CF-7: Embed functionality shall be preserved.
- REQ-FR-CF-8: Photos functionality shall be preserved.
- REQ-FR-CF-9: Tag Cloud functionality shall be preserved.
- REQ-FR-CF-10: Search functionality shall be preserved.
- REQ-FR-CF-11: Likes functionality shall be preserved.
- REQ-FR-CF-12: Video functionality shall be preserved.

### Community Features

- REQ-FR-CMF-1: Groups functionality shall be preserved.
- REQ-FR-CMF-2: Members directory functionality shall be preserved.
- REQ-FR-CMF-3: Invite Friends functionality shall be preserved.
- REQ-FR-CMF-4: Messages functionality shall be preserved.
- REQ-FR-CMF-5: Announcements functionality shall be preserved.
- REQ-FR-CMF-6: Newsletters functionality shall be preserved.
- REQ-FR-CMF-7: Questions functionality shall be preserved.
- REQ-FR-CMF-8: Campaigns (donation campaigns) functionality shall be preserved.
- REQ-FR-CMF-9: Events functionality shall be preserved.
- REQ-FR-CMF-10: FAQ functionality shall be preserved.

### Platform Services

- REQ-FR-PS-1: Notifications functionality shall be preserved.
- REQ-FR-PS-2: Site Notifications functionality shall be preserved.
- REQ-FR-PS-3: User Validation by Email functionality shall be preserved.
- REQ-FR-PS-4: Captcha functionality shall be preserved.
- REQ-FR-PS-5: Legacy URL Support shall be preserved.
- REQ-FR-PS-6: Reported Content functionality shall be preserved.
- REQ-FR-PS-7: Web services functionality shall be preserved.
- REQ-FR-PS-8: Data views for web services shall be preserved.
- REQ-FR-PS-9: HTMLawed (input filtering) functionality shall be preserved.

### Development and Administration Tools

- REQ-FR-DAT-1: Elgg Developer Tools functionality shall be preserved.
- REQ-FR-DAT-2: Diagnostics functionality shall be preserved.
- REQ-FR-DAT-3: Log Browser functionality shall be preserved.
- REQ-FR-DAT-4: Log Rotate functionality shall be preserved.
- REQ-FR-DAT-5: Login As functionality shall be preserved.

## Additional Requirements

### Deployment

- REQ-DEPL-1: The application shall be set up using Docker.
- REQ-DEPL-2: The application shall be orchestrated using Docker Compose.
- REQ-DEPL-3: The application shall be deployed to Coolify.
- REQ-DEPL-4: The application shall be deployed to production at every upgrade point of the Elgg upgrade sequence so the production database remains in sync with each upgrade step.
