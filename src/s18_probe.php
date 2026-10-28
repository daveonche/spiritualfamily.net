<?php
/**
 * Temporary S1.8 manual-verification probe (Story S1.8 Step 1).
 * Delete after verification — never commit, never bake into a production image.
 */

$autoload_path = __DIR__ . '/vendor/autoload.php';
$autoload_available = include_once($autoload_path);
if (!$autoload_available) {
	die("Couldn't include '$autoload_path'. Did you run `composer install`?");
}

// Boot the core without serving a page: ::start() is the static boot
// (reflection-verified against the vendored 2.3 core). ::upgrade() would
// run DB upgrades; ::index() would serve the front page.
\Elgg\Application::start();

// One event per level, all tagged for grep.
elgg_log('S1.8 NOTICE probe', 'NOTICE');
elgg_log('S1.8 WARNING probe', 'WARNING');
elgg_deprecated_notice('S1.8 deprecation probe', '2.3');

echo 'S1.8 probe complete';
