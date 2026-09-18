<?php

/**
 * Moment.js
 *
 * Moment.js AMD module for Elgg
 *
 * @author Ismayil Khayredinov <info@hypejunction.com>
 * @copyright Copyright (c) 2017, Ismayil Khayredinov
 */
require_once __DIR__ . '/autoloader.php';

elgg_register_event_handler('init', 'system', function () {

    elgg_define_js('moment', [
        'src' => elgg_get_simplecache_url('moment.js'),
        'exports' => 'window.moment',
    ]);

});
