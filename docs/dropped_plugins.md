# Dropped Plugins Disposition List

Recorded: 2026-09-26 — Story S1.4, Step 1 (`docs/analysis/S1.4-story-steps.md`)

Scope: every plugin directory present in `src/mod` (85 total, active and inactive), cross-checked against `src/composer.lock` and the recorded decisions in `docs/tech_stack.md`. Each plugin carries exactly one disposition. Plugins in removal categories are dropped in Story S1.4 Steps 2–4; deferred plugins stay until their recorded trigger; kept plugins require no action.

## Dispositions

### Composer replacement at Elgg 7 (12)

Removed in Step 4. The Elgg 7 replacement arrives via Composer in Story S1.14.

| Plugin | Removed in | Replacement package | Notes |
| --- | --- | --- | --- |
| analytics | Step 4 | `coldtrick/analytics v5.0` | |
| blog_tools | Step 4 | `coldtrick/blog_tools v17.0` | |
| data_views | Step 4 | `elgg/data_views 1.0.0` | package already present in `src/composer.lock` |
| event_manager | Step 4 | `coldtrick/event_manager v21.2` | |
| group_tools | Step 4 | `coldtrick/group_tools v23.2` | |
| htmlawed | Step 4 | `htmlawed/htmlawed` | runtime dependency of elgg/elgg 7.1.0 itself |
| newsletter | Step 4 | `coldtrick/newsletter v13.0` | |
| profile_manager | Step 4 | `coldtrick/profile_manager v23.0` | |
| questions | Step 4 | `coldtrick/questions v15.0` | |
| site_announcements | Step 4 | `coldtrick/site_announcements v13.0` | |
| widget_manager | Step 4 | `coldtrick/widget_manager v18.0.1` | |
| widget_pack | Step 4 | not yet recorded | disposition assigned 2026-09-26; package to be identified at S1.14 provisioning |

### Manual reinstall in a later sprint (6)

Removed in Step 4; reinstalled manually per `docs/tech_stack.md` Manual Installs (versions recorded at install time).

| Plugin | Removed in | Notes |
| --- | --- | --- |
| captcha | Step 4 | required feature (PS-4) |
| faq | Step 4 | required feature (CMF-10) |
| sbw_campaigns | Step 4 | required feature (CMF-8) |
| tidypics | Step 4 | required feature (CF-8) |
| videos | Step 4 | required feature (CF-12) |
| views_counter | Step 4 | required feature (CF-13) |

### Manual-upgrade candidates (19)

Removed in Step 4 (elgg_file_viewer with the core drops in Step 3); candidates for manual upgrade in a later sprint.

| Plugin | Removed in | Notes |
| --- | --- | --- |
| ambercal_settings_transfer | Step 4 | |
| countries | Step 4 | |
| elgg_file_viewer | Step 3 | Elgg 2.3 core plugin; added to manual-upgrade candidates 2026-09-26 |
| hypeAttachments | Step 4 | |
| hypeDiscussions | Step 4 | |
| hypeDropzone | Step 4 | |
| hypeEmbed | Step 4 | |
| hypeDiscovery | Step 4 | |
| hypeInteractions | Step 4 | |
| hypeLists | Step 4 | |
| hypeMapsOpen | Step 4 | |
| hypeNotifications | Step 4 | added to manual-upgrade candidates 2026-09-26 |
| hypeScraper | Step 4 | |
| hypeWall | Step 4 | |
| payments | Step 4 | |
| payments_paypal_api | Step 4 | |
| payments_sofort | Step 4 | |
| payments_stripe | Step 4 | |
| payments_wire | Step 4 | |

### Permanently dropped — no replacement (14)

Removed in Steps 2 and 4; never reintroduced.

| Plugin | Removed in | Notes |
| --- | --- | --- |
| aalborg_theme | Step 2 | legacy theme stack (REQ-FR-TR-1) |
| access_grant | Step 4 | |
| ckeditor_addons | Step 4 | |
| code_review | Step 4 | also present as Composer package `srokap/code_review` in `src/composer.lock`; remove both entries |
| elgg-moment | Step 4 | |
| elgg_ajax_register | Step 4 | |
| elgg_hybridauth | Step 4 | |
| elgg_tokeninput | Step 4 | |
| elggchat | Step 4 | |
| freichat | Step 4 | |
| html_email_handler | Step 4 | |
| l1 | Step 2 | aalborg extras/L1, legacy theme stack (REQ-FR-TR-1) |
| mrdave_aalborg | Step 2 | legacy theme stack (REQ-FR-TR-1) |
| paul_override | Step 2 | legacy theme stack (REQ-FR-TR-1) |

### Legacy core drops (8)

Elgg 2.3 core plugins not bundled in Elgg 7.1.0 (REQ-FR-PU-7). Removed in Step 3.

| Plugin | Removed in | Duty coverage after drop |
| --- | --- | --- |
| diagnostics | Step 3 | to be recorded in Step 3 |
| embed | Step 3 | to be recorded in Step 3 |
| legacy_urls | Step 3 | to be recorded in Step 3 |
| logbrowser | Step 3 | `system_log` + `garbagecollector` cover at Elgg 7 (S1.15 developer notes) |
| login_as | Step 3 | to be recorded in Step 3 |
| logrotate | Step 3 | container/cron log rotation (S1.7) + `system_log`/`garbagecollector` at Elgg 7 (S1.15 developer notes) |
| notifications | Step 3 | Elgg 7 core notifications service covers PS-1 |
| twitter_api | Step 3 | to be recorded in Step 3 |

### Deferred (2)

Not removed in Story S1.4; dropped later per recorded trigger.

| Plugin | Deferred until | Notes |
| --- | --- | --- |
| amap_coverphoto | after content migration | `docs/tech_stack.md`: "Content migration then drop" |
| custom_index | Story S1.16 | REQ-FR-TR-1; deactivate or override at Elgg 7 |

### Kept — bundled in Elgg 7.1.0 (24)

No action; activated at Elgg 7 in Story S1.15.

blog, bookmarks, ckeditor, dashboard, developers, discussions, externalpages, file, garbagecollector, groups, invitefriends, likes, members, messageboard, messages, pages, profile, reportedcontent, search, site_notifications, tagcloud, thewire, uservalidationbyemail, web_services

## Cross-Check Against docs/tech_stack.md

- Permanently Dropped (third-party, 14 recorded): all 14 appear under "Permanently dropped — no replacement" with matching disposition.
- Permanently Dropped (Elgg 2.3 core per REQ-FR-PU-7, 6 recorded): embed, legacy_urls, diagnostics, logbrowser, logrotate, login_as all appear under "Legacy core drops". notifications and twitter_api were added to this category on 2026-09-26 per user decision; both are Elgg 2.3 core plugins not bundled in Elgg 7.1.0, consistent with the REQ-FR-PU-7 category.
- Required Dependencies (Composer replacements): event_manager, newsletter, questions, site_announcements, group_tools, blog_tools, profile_manager, widget_manager, analytics, data_views, htmlawed all appear under "Composer replacement at Elgg 7" with the package recorded in `docs/tech_stack.md`. widget_pack was added to this category on 2026-09-26 per user decision; its replacement package is not recorded in `docs/tech_stack.md` and must be identified at Story S1.14 provisioning.
- Manual Installs (required features): sbw_campaigns, videos, faq, tidypics, views_counter, captcha all appear under "Manual reinstall in a later sprint".
- Manual Installs (manual-upgrade candidates, 17 recorded): all 17 appear under "Manual-upgrade candidates". elgg_file_viewer and hypeNotifications were added on 2026-09-26 per user decision, extending the recorded list; `docs/tech_stack.md` should be updated to stay in sync.
- Content migration then drop: amap_coverphoto appears under "Deferred".
- Bundled Elgg 7.1.0 plugins: the 24 kept plugins match the recorded bundled list; custom_index is excluded (deferred to S1.16 per REQ-FR-TR-1).
- Count reconciliation: 12 + 6 + 19 + 14 + 8 + 2 + 24 = 85 = plugin directories in `src/mod`.

## Open Items Carried Forward

- Identify the Composer replacement package for widget_pack before Story S1.14.
- Update `docs/tech_stack.md` (manual-upgrade candidates; widget_pack replacement) to mirror this list.
- Record duty coverage for diagnostics, embed, legacy_urls, login_as, twitter_api during Step 3.
