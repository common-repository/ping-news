<?php
/**
 * @package Ping News
 */
/*
 Plugin Name: Ping News
 Description: The Ping! WordPress plugin allows hyperlocal journalists to submit their news stories to the Ping! system and receive approval/feedback on their stories.
 Version: 1.0.17
 License: GPLv2 or later
 */

define("PINGNEWS_PLUGIN_VERSION", "1.0.17");

// Include all functions related to pushing packages to ingest
include 'push.php';

add_action("init", "pingnews_pusher_role", 100);
add_action("admin_menu", "pingnews_menu_page", 101);
add_action("admin_init", "pingnews_update_auth_token", 102);
add_action("add_meta_boxes", "pingnews_add_metabox", 103);
add_action("wp_ajax_pingnews_post_package", "pingnews_post_package_ajax_handler", 104);
add_action("wp_ajax_nopriv_pingnews_post_package", "pingnews_post_package_ajax_handler", 105);
add_action("wp_ajax_pingnews_get_package", "pingnews_get_package_ajax_handler", 106);
add_action("wp_ajax_nopriv_pingnews_get_package", "pingnews_get_package_ajax_handler", 107);
?>
