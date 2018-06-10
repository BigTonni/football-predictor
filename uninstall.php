<?php

/**
 * Football Predictor Uninstall
 *
 * Uninstalling Football Predictor deletes user data.
 *
 * @class       Football
 * @version 1.0.2
 */
if( ! defined('WP_UNINSTALL_PLUGIN') ) exit;

global $wpdb;

$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}fp_stage");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}fp_team");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}fp_venue");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}fp_match");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}fp_prediction");

delete_option('fp_db_version');
delete_option('fp_promo_link');
delete_option('fp_group_stats');
delete_option('fp_scoring');
delete_option('fp_countdown_format');
delete_option('fp_browser_locale');

$roles = array("subscriber", "contributor", "author", "editor", "administrator");
foreach ($roles as $role) {
    $arole = get_role($role);
    $arole->remove_cap('fp_manager');
}
