-- utf8mb4 conversion (S1.9 Step 5 remediation, 2026-11-03)
-- Reproduces Elgg\Upgrades\AlterDatabaseToMultiByteCharset::run() statement-for-statement,
-- minus the obsolete `SET GLOBAL innodb_large_prefix = 'ON'`: the variable was removed
-- in MariaDB 10.3 and its effect is always-on in 10.11; the vendor probe's empty-row
-- path aborted the upgrade before any ALTER ran. Once every table below reports
-- utf8mb4_general_ci, the vendor upgrade's shouldBeSkipped() returns true and the
-- pending upgrade shows complete.
-- SCRATCH FILE: uncommitted, delete after the conversion is verified.

ALTER DATABASE CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

ALTER TABLE elggjr_access_collection_membership ROW_FORMAT=DYNAMIC;
ALTER TABLE elggjr_access_collection_membership CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

ALTER TABLE elggjr_access_collections ROW_FORMAT=DYNAMIC;
ALTER TABLE elggjr_access_collections CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

ALTER TABLE elggjr_annotations ROW_FORMAT=DYNAMIC;
ALTER TABLE elggjr_annotations CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

ALTER TABLE elggjr_api_users ROW_FORMAT=DYNAMIC;
ALTER TABLE elggjr_api_users CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

ALTER TABLE elggjr_config DROP PRIMARY KEY;
ALTER TABLE elggjr_config ROW_FORMAT=DYNAMIC;
ALTER TABLE elggjr_config CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE elggjr_config MODIFY name VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci;
ALTER TABLE elggjr_config ADD PRIMARY KEY (name);

ALTER TABLE elggjr_entities DROP KEY subtype;
ALTER TABLE elggjr_entities ROW_FORMAT=DYNAMIC;
ALTER TABLE elggjr_entities CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE elggjr_entities MODIFY subtype VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci;
ALTER TABLE elggjr_entities ADD KEY subtype (subtype);

ALTER TABLE elggjr_entity_relationships ROW_FORMAT=DYNAMIC;
ALTER TABLE elggjr_entity_relationships CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

ALTER TABLE elggjr_metadata ROW_FORMAT=DYNAMIC;
ALTER TABLE elggjr_metadata CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

ALTER TABLE elggjr_private_settings ROW_FORMAT=DYNAMIC;
ALTER TABLE elggjr_private_settings CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

ALTER TABLE elggjr_queue DROP KEY name;
ALTER TABLE elggjr_queue ROW_FORMAT=DYNAMIC;
ALTER TABLE elggjr_queue CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE elggjr_queue MODIFY name VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci;
ALTER TABLE elggjr_queue ADD KEY name (name);

ALTER TABLE elggjr_river ROW_FORMAT=DYNAMIC;
ALTER TABLE elggjr_river CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

ALTER TABLE elggjr_system_log DROP KEY object_class;
ALTER TABLE elggjr_system_log DROP KEY object_type;
ALTER TABLE elggjr_system_log DROP KEY object_subtype;
ALTER TABLE elggjr_system_log DROP KEY event;
ALTER TABLE elggjr_system_log DROP KEY river_key;
ALTER TABLE elggjr_system_log ROW_FORMAT=DYNAMIC;
ALTER TABLE elggjr_system_log CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE elggjr_system_log MODIFY object_class VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci;
ALTER TABLE elggjr_system_log MODIFY object_type VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci;
ALTER TABLE elggjr_system_log MODIFY object_subtype VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci;
ALTER TABLE elggjr_system_log MODIFY event VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci;
ALTER TABLE elggjr_system_log ADD KEY object_class (object_class);
ALTER TABLE elggjr_system_log ADD KEY object_type (object_type);
ALTER TABLE elggjr_system_log ADD KEY object_subtype (object_subtype);
ALTER TABLE elggjr_system_log ADD KEY event (event);
ALTER TABLE elggjr_system_log ADD KEY river_key (object_type, object_subtype, event);

ALTER TABLE elggjr_users_remember_me_cookies ROW_FORMAT=DYNAMIC;
ALTER TABLE elggjr_users_remember_me_cookies CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

ALTER TABLE elggjr_users_sessions DROP PRIMARY KEY;
ALTER TABLE elggjr_users_sessions ROW_FORMAT=DYNAMIC;
ALTER TABLE elggjr_users_sessions CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE elggjr_users_sessions MODIFY session VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci;
ALTER TABLE elggjr_users_sessions ADD PRIMARY KEY (session);

ALTER TABLE elggjr_hmac_cache DROP PRIMARY KEY;
ALTER TABLE elggjr_hmac_cache ROW_FORMAT=DYNAMIC;
ALTER TABLE elggjr_hmac_cache CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
ALTER TABLE elggjr_hmac_cache MODIFY hmac VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci;
ALTER TABLE elggjr_hmac_cache ADD PRIMARY KEY (hmac);

ALTER TABLE elggjr_users_apisessions ROW_FORMAT=DYNAMIC;
ALTER TABLE elggjr_users_apisessions CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
