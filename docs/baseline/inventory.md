# Production Inventory — Elgg 2.3 Baseline

Read-only production inventory captured for Story S1.1 Step 4. It is the stored
migration starting point for the upgrade journey: S1.3 restores a production
database dump from this state, and S1.4 cross-checks its plugin drop list against
the active plugin set recorded here. Production was not modified during capture.

## Capture Details

- Capture date: 2026-09-18
- Database access: phpMyAdmin, read-only `SELECT` queries against the shared
  production MySQL server
- Active plugins resolved via the `active_plugin` site relationship (Elgg 2.x
  stores plugin entities with `enabled='no'`; activation lives in relationships)
- Dataroot measurement: temporary read-only PHP probe script uploaded to the
  webroot, executed once, then deleted
- Secrets (database credentials, salts, keys) are intentionally excluded

## Database Inventory

### Size

| Schema | Size (MB) | Scope |
| :--- | ---: | :--- |
| `spirihf6_elgg114` | 1121.1 | Elgg production database — in scope |
| `spirihf6_phpBB5lce7nh4ok` | 117.0 | phpBB forum — out of scope |
| `spirihf6_elgg433` | 18.7 | Legacy Elgg install — out of scope |
| `spirihf6_fusf` | 2.8 | Unrelated — out of scope |
| `spirihf6_phpb452` | 2.8 | phpBB — out of scope |

The Elgg production database is 1121.1 MB. Neighbor schemas on the shared server
are recorded as context only.

### Schema and Install Metadata

| Item | Value |
| :--- | :--- |
| Datalists `version` | 2016092300 (Elgg 2.3-line schema code; consistent with pinned core 2.3.17) |
| Datalists `installed` | 1483761119 (2017-01-07 UTC) |
| Site GUID | 1 |
| Site name | SpiritualFamily.Net |
| Site URL | `https://spiritualfamily.net/` |

### Content Volume

| Entity type | Count |
| :--- | ---: |
| object | 87152 |
| user | 1136 |
| group | 289 |
| site | 1 |

## Active Plugin Set

77 plugins active at capture, in activation order (Elgg resolves plugin priority
in this order). Dates are UTC activation dates.

| # | Plugin ID | GUID | Activated |
| ---: | :--- | ---: | :--- |
| 1 | blog | 3 | 2017-01-07 |
| 2 | thewire | 32 | 2017-01-07 |
| 3 | search | 29 | 2017-01-07 |
| 4 | reportedcontent | 28 | 2017-01-07 |
| 5 | profile | 27 | 2017-01-07 |
| 6 | notifications | 25 | 2017-01-07 |
| 7 | messages | 24 | 2017-01-07 |
| 8 | messageboard | 23 | 2017-01-07 |
| 9 | members | 22 | 2017-01-07 |
| 10 | logrotate | 21 | 2017-01-07 |
| 11 | logbrowser | 20 | 2017-01-07 |
| 12 | likes | 19 | 2017-01-07 |
| 13 | invitefriends | 17 | 2017-01-07 |
| 14 | groups | 15 | 2017-01-07 |
| 15 | garbagecollector | 14 | 2017-01-07 |
| 16 | file | 13 | 2017-01-07 |
| 17 | discussions | 10 | 2017-01-07 |
| 18 | ckeditor | 5 | 2017-01-07 |
| 19 | uservalidationbyemail | 34 | 2017-01-07 |
| 20 | externalpages | 12 | 2017-01-18 |
| 21 | login_as | 43 | 2017-01-18 |
| 22 | embed | 11 | 2017-01-19 |
| 23 | tagcloud | 31 | 2017-01-24 |
| 24 | developers | 8 | 2017-01-24 |
| 25 | web_services | 35 | 2017-01-24 |
| 26 | data_views | 42 | 2017-01-24 |
| 27 | profile_manager | 88 | 2017-02-01 |
| 28 | aalborg_theme | 2 | 2017-02-01 |
| 29 | pages | 26 | 2017-02-01 |
| 30 | captcha | 1049 | 2017-03-03 |
| 31 | elgg_ajax_register | 1050 | 2017-03-03 |
| 32 | diagnostics | 9 | 2017-03-03 |
| 33 | legacy_urls | 18 | 2017-03-03 |
| 34 | countries | 1081 | 2017-03-06 |
| 35 | l1 | 1082 | 2017-03-06 |
| 36 | site_announcements | 1090 | 2017-03-06 |
| 37 | faq | 1395 | 2017-03-22 |
| 38 | friend_request | 1565 | 2017-03-30 |
| 39 | mrdave_aalborg | 1083 | 2017-06-14 |
| 40 | access_grant | 4993 | 2017-10-03 |
| 41 | paul_override | 3795 | 2017-10-03 |
| 42 | elgg_tokeninput | 4999 | 2017-10-03 |
| 43 | hypeAttachments | 5001 | 2017-10-03 |
| 44 | hypeDiscovery | 5002 | 2017-10-03 |
| 45 | hypeDropzone | 5004 | 2017-10-03 |
| 46 | hypeMapsOpen | 5008 | 2017-10-03 |
| 47 | hypeScraper | 5010 | 2017-10-03 |
| 48 | payments | 1084 | 2017-10-03 |
| 49 | payments_paypal_api | 1085 | 2017-10-03 |
| 50 | elgg_hybridauth | 4998 | 2017-10-03 |
| 51 | views_counter | 164 | 2017-10-20 |
| 52 | bookmarks | 4 | 2017-11-25 |
| 53 | tidypics | 90 | 2019-03-23 |
| 54 | htmlawed | 16 | 2021-02-15 |
| 55 | hypeInteractions | 5006 | 2021-03-12 |
| 56 | ckeditor_addons | 1394 | 2021-03-12 |
| 57 | group_tools | 85 | 2021-03-14 |
| 58 | hypeLists | 5007 | 2021-03-14 |
| 59 | hypeDiscussions | 5003 | 2021-03-14 |
| 60 | hypeNotifications | 5009 | 2021-03-14 |
| 61 | hypeEmbed | 5005 | 2021-03-28 |
| 62 | blog_tools | 4995 | 2021-03-28 |
| 63 | ambercal_settings_transfer | 4994 | 2023-05-24 |
| 64 | html_email_handler | 62110 | 2023-05-24 |
| 65 | newsletter | 52212 | 2023-05-24 |
| 66 | event_manager | 5000 | 2023-09-11 |
| 67 | amap_coverphoto | 2111 | 2023-09-20 |
| 68 | elgg_file_viewer | 2113 | 2023-09-20 |
| 69 | thewire_tools | 2588 | 2023-09-20 |
| 70 | freichat | 28267 | 2023-09-20 |
| 71 | questions | 89 | 2023-09-20 |
| 72 | sbw_campaigns | 1089 | 2023-09-20 |
| 73 | payments_wire | 1088 | 2023-09-20 |
| 74 | videos | 163 | 2025-08-09 |
| 75 | hypeWall | 5011 | 2025-08-09 |
| 76 | widget_manager | 5012 | 2025-10-26 |
| 77 | widget_pack | 5013 | 2025-10-26 |

### Inactive and Stale Plugin Entities

25 additional plugin entities exist but are inactive: `actions`,
`advanced_comments`, `classes`, `content_subscriptions`, `crontrigger`,
`cropper`, `demo`, `disabled`, `elggx_fivestar`, `hypeEmbed_3.0.2`,
`hypeEmbed_3.0.3`, `hypeNotification`, `install`, `izap-videos`,
`izap-videos-5.1.1`, `izap_videos`, `languages`, `lib`, `notification_tools`,
`notifier`, `screenshots`, `timeline_avatar`, `time_theme_pro`,
`uservalidationbyadmin`, `vendor`

Observations for S1.4:

- Entities named after directories (`actions`, `classes`, `install`, `languages`,
  `lib`, `screenshots`, `vendor`, `disabled`) are scan artifacts, not real
  plugins; they can never activate.
- Duplicate/stale variants exist: `hypeEmbed_3.0.2` and `hypeEmbed_3.0.3` beside
  the active `hypeEmbed`; three inactive `izap-videos`/`izap_videos` variants.
- `custom_index` is not active in production (relevant to REQ-FR-TR-1 and S1.16).
- The legacy theme stack is active: `aalborg_theme`, `l1`, `mrdave_aalborg`,
  `paul_override` (REQ-FR-TR-1).
- Core plugins slated for drop in REQ-FR-PU-7 are active: `embed`, `legacy_urls`,
  `diagnostics`, `logbrowser`, `logrotate`, `login_as`.
- `htmlawed` and `data_views` are active and move to Composer packages in S1.14.
- `captcha`, `tidypics`, `views_counter`, `videos`, `faq`, `sbw_campaigns` are
  active and move to the manual-plugins directory in S1.14.

## Site Settings

| Setting | Value | Source |
| :--- | :--- | :--- |
| Language | `en` | config |
| Default access | `2` (public) | config |
| Allow registration | enabled | config |
| Walled garden | disabled | config |
| Simple cache | enabled | datalists |
| System cache | enabled | datalists |
| Dataroot | `/home/sites/42a/a/adf982fbe5/elggdata/` | datalists |
| Elgg install path | `/home/sites/42a/a/adf982fbe5/public_html/` | datalists |

## Data Directory Inventory

### Totals

| Metric | Value |
| :--- | ---: |
| Total size | 47,939.3 MB (~46.8 GiB) |
| Total files | 102,690 |

### File Types (Top Extensions)

| Extension | Files |
| :--- | ---: |
| jpg | 54823 |
| png | 31266 |
| gif | 10923 |
| jpeg | 4299 |
| mp4 | 341 |
| json | 306 |
| pdf | 219 |
| docx | 115 |
| js | 112 |
| svg | 76 |
| lang | 60 |
| mp3 | 37 |
| html | 16 |
| css | 16 |
| (no extension) | 15 |

Image files (jpg, png, gif, jpeg) account for 101,311 of 102,690 files (~98.7%).
Counts beyond the top 15 rows are omitted (66 files).

### Largest Content Buckets

| Bucket | Size (MB) |
| :--- | ---: |
| `1/36/file` | 12286.0 |
| `1/36/image` | 9192.6 |
| `1/1758/file` | 5090.7 |
| `10000/11743/file` | 2158.0 |
| `5000/7701/image` | 1313.2 |
| `1/1758/image` | 1058.7 |
| `1/71/file` | 629.6 |
| `25000/27563/file` | 540.3 |
| `1/773/image` | 489.7 |
| `50000/51498/image` | 406.7 |
| `35000/37999/file` | 395.7 |
| `20000/22895/file` | 373.6 |
| `50000/51498/file` | 341.0 |
| `35000/39161/file` | 287.8 |
| `1/1/scraper_cache` | 275.1 |

### Distribution and Observations

- The site-owned bucket `1/` holds 31,250.8 MB (65.2% of the dataroot); GUID 36
  alone accounts for ~21.5 GB across its `file` and `image` subdirectories.
- Remaining content spreads across user/group GUID buckets (`10000`–`95000`).
- Regenerable caches are negligible: `system_cache` 7 MB, `views_simplecache` 3.9 MB.
- Third-party plugin footprints sit at the dataroot root: `PayPal.live.log`
  (0.8 MB), `PayPal.sandbox.log` (0.1 MB), `izap_queue_db`, `paypal`,
  `river_id_cache.dat`, `scraper_cache.dat` — all tied to plugins dropped in S1.4.

## Migration Starting-Point Notes

- The production database dump required by the S1.3 restore test is exported via
  phpMyAdmin at that step (compressed); it is not part of this inventory.
- Treat this inventory as read-only reference data; never modify production while
  re-capturing or cross-checking.
- Cross-check: datalists `version` 2016092300 is consistent with the pinned
  Elgg 2.3.17 core in `docs/baseline/baseline.md`.
