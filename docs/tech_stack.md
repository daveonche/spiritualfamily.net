# Technology Stack Documentation

## Core Technology

- elgg/elgg 7.1.0
- PHP 8.3 (platform pin)
- Composer with asset-packagist.org repository

## Required Dependencies

### Events (REQ-FR-CMF-9)

- coldtrick/event_manager v21.2
  - Purpose: Event management with registrations and day programs
  - Chosen because: verified Elgg 7 compatible (`conflict: elgg/elgg <7.0`); replaces `src/mod/event_manager`
  - Transitive (resolved by Composer): dompdf/dompdf ~3.1.0, npm-asset/fullcalendar ~6.1.0, kigkonsult/icalcreator ^2.41

### Newsletters (REQ-FR-CMF-6)

- coldtrick/newsletter v13.0
  - Purpose: Newsletter creation and management for site/group members
  - Chosen because: verified Elgg 7 compatible; replaces `src/mod/newsletter`

### Questions (REQ-FR-CMF-7)

- coldtrick/questions v15.0
  - Purpose: Q&A functionality
  - Chosen because: verified Elgg 7 compatible; replaces `src/mod/questions`

### Announcements (REQ-FR-CMF-5)

- coldtrick/site_announcements v13.0
  - Purpose: Admin announcements displayed to users
  - Chosen because: verified Elgg 7 compatible; replaces `src/mod/site_announcements`

### Group Tools

- coldtrick/group_tools v23.2
  - Purpose: Group enhancements
  - Chosen because: verified Elgg 7 compatible; replaces `src/mod/group_tools`

### Blog Tools

- coldtrick/blog_tools v17.0
  - Purpose: Blog enhancements
  - Chosen because: verified Elgg 7 compatible; replaces `src/mod/blog_tools`

### Profile Manager

- coldtrick/profile_manager v23.0
  - Purpose: Custom profile fields and categories
  - Chosen because: verified Elgg 7 compatible; replaces `src/mod/profile_manager`

### Widget Manager

- coldtrick/widget_manager v18.0.1
  - Purpose: Widget layout management
  - Chosen because: verified Elgg 7 compatible; replaces `src/mod/widget_manager`

### Analytics

- coldtrick/analytics v5.0
  - Purpose: Analytics tracking
  - Chosen because: verified compatible (requires Elgg >= 5.1); replaces `src/mod/analytics`

### Data Views (REQ-FR-PS-8)

- elgg/data_views 1.0.0
  - Purpose: Views for web services
  - Chosen because: same version already locked in the current composer.lock

### Input Filtering (REQ-FR-PS-9)

- htmlawed/htmlawed ~1.2.15
  - Purpose: HTML input filtering
  - Chosen because: runtime dependency of elgg/elgg 7.1.0 itself; not pinned in the project file

## Bundled Elgg 7.1.0 Plugins (no Composer packages required)

blog (CF-1), bookmarks (CF-2), ckeditor (CF-6), discussions (CF-3), externalpages (CF-4),
thewire (CF-5), tagcloud (CF-9), search (CF-10), likes (CF-11), groups (CMF-1),
members (CMF-2), invitefriends (CMF-3), messages (CMF-4), site_notifications (PS-2),
uservalidationbyemail (PS-3), reportedcontent (PS-6), web_services (PS-7), developers (DAT-1),
custom_index (deactivate or override per REQ-FR-TR-1), activity, dashboard, file, friends,
friends_collections, garbagecollector, messageboard, pages, profile, system_log, theme_sandbox,
data_views (PS-8; Composer-managed, elgg/data_views 1.0.0 — re-dispositioned to kept 2026-10-08).
Core notifications service covers PS-1.

## Manual Installs (no Packagist package; versions recorded at install time)

- Required features: sbw_campaigns (CMF-8), videos (CF-12), faq (CMF-10), tidypics (CF-8),
  views_counter (CF-13), captcha (PS-4)
- Manual-upgrade candidates: ambercal_settings_transfer, payments, payments_paypal_api,
  payments_sofort, payments_stripe, payments_wire, countries, hypeAttachments, hypeDropzone,
  hypeInteractions, hypeWall, hypeScraper, hypeMapsOpen, hypeLists, hypeDiscussions,
  hypeEmbed, hypeDiscovery
- Content migration then drop: amap_coverphoto

## Permanently Dropped

Third-party: aalborg_theme, access_grant, freichat, paul_override, mrdave_aalborg,
html_email_handler, ckeditor_addons, elggchat, elgg_tokeninput, elgg_ajax_register,
elgg_hybridauth, elgg-moment, l1, code_review.
Elgg 2.3 core per REQ-FR-PU-7: embed, legacy_urls, diagnostics, logbrowser, logrotate, login_as.

## Compatibility Matrix

| Dependency | elgg/elgg 7.1.0 | PHP 8.3 | Evidence |
| --- | :---: | :---: | --- |
| coldtrick/event_manager v21.2 | ✓ | ✓ | conflict `elgg/elgg <7.0`; dompdf ~3.1.0 supports PHP 8.3 |
| coldtrick/newsletter v13.0 | ✓ | ✓ | conflict `elgg/elgg <7.0` |
| coldtrick/questions v15.0 | ✓ | ✓ | conflict `elgg/elgg <7.0` |
| coldtrick/site_announcements v13.0 | ✓ | ✓ | conflict `elgg/elgg <7.0` |
| coldtrick/group_tools v23.2 | ✓ | ✓ | conflict `elgg/elgg <7.0` |
| coldtrick/blog_tools v17.0 | ✓ | ✓ | conflict `elgg/elgg <7.0` |
| coldtrick/profile_manager v23.0 | ✓ | ✓ | conflict `elgg/elgg <7.0` |
| coldtrick/widget_manager v18.0.1 | ✓ | ✓ | conflict `elgg/elgg <7.0` |
| coldtrick/analytics v5.0 | ✓ | ✓ | requires `elgg/elgg >= 5.1` |
| elgg/data_views 1.0.0 | ✓ | ✓ | no Elgg constraint; already in production lock file |
| elgg/elgg 7.1.0 | — | ✓ | requires `php >= 8.3` |

## Version Lock Rationale

All versions are exact (e.g., "v21.2" not "^21.2") to ensure:

- Consistent behavior across environments
- Predictable dependency resolution
- Reproducible builds
