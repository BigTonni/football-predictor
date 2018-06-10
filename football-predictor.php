<?php
/**
 * Plugin Name: Football Predictor
 * Plugin URI: https://wordpress.org/plugins/football-predictor/
 * Description: To manage and perform a marvel football competition for the FIFA World Cup 2018.
 * Version: 1.0.2
 * Author: Anton Shulga
 * Author URI: https://github.com/BigTonni
 * Text Domain: football-predictor
 * Domain Path: /languages/
 * License: GPLv2 or later
 */
/*
  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation; either version 2 of the License, or
  (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

if (!defined('ABSPATH')){
    exit; // Exit if accessed directly
}

if (!defined('FP_PD')) {
    define('FP_PD', 'football-predictor');
}
if (!defined('FP_FILE')) {
    define('FP_FILE', __FILE__);
}
if (!defined('FP_ABSPATH')) {
    define('FP_ABSPATH', dirname(FP_FILE) . '/');
}
if (!defined('FP_VERSION')) {
    define('FP_VERSION', '1.0.2');
}
if (!defined('FP_PREFIX')) {
    define('FP_PREFIX', 'fp_');
}

if (!class_exists('Football_Start')) {

    class Football_Start {
	
	public $prefix = FP_PREFIX;

        private static $instance = null;

        private function __construct() {
            $this->includes();

            $this->init_hooks();
        }

        private function __clone() { }

        private function __wakeup() { }

        public static function instance() {
            if (is_null(self::$instance)) {
                self::$instance = new self();
            }

            return self::$instance;
        }

        private function init_hooks() {

            register_activation_hook(FP_FILE, array($this, 'activate'));

            add_action('plugins_loaded', array($this, 'load_textdomain'));

            register_deactivation_hook(FP_FILE, array($this, 'deactivate'));

            if (get_option('fp_adjust_knockout', 1)) {
                add_action('wp_enqueue_scripts', array($this, 'adjust_knockout'));
            }
        }

        private function includes() {
            require_once FP_ABSPATH . 'includes/football-helpers.php';
            require_once FP_ABSPATH . 'includes/class-football.php';
            new Football();

            require_once FP_ABSPATH . 'includes/class-fp-predict.php';
            $fpp = new FootballPredict();

            if (is_admin()) {
                require_once FP_ABSPATH . 'admin/class-fp-admin.php';
                require_once FP_ABSPATH . 'admin/class-fp-teams.php';
                require_once FP_ABSPATH . 'admin/class-fp-venues.php';
                require_once FP_ABSPATH . 'admin/class-fp-stages.php';
                require_once FP_ABSPATH . 'admin/class-fp-matches.php';
                require_once FP_ABSPATH . 'admin/class-fp-predictions.php';
                require_once FP_ABSPATH . 'admin/class-fp-scoring.php';
                require_once FP_ABSPATH . 'admin/class-fp-overview.php';
                require_once FP_ABSPATH . 'admin/class-fp-results.php';
                require_once FP_ABSPATH . 'includes/class-fp-reports.php';

                $fpadmin = new FootballAdmin();

                add_action('admin_menu', array($fpadmin, 'admin_menu'));
                add_action('admin_print_scripts', array($fpadmin, 'admin_print_scripts'));
                add_action('admin_print_styles', array($fpadmin, 'admin_print_styles'));
                add_action('admin_init', array($fpadmin, 'admin_init'));
            }
        }

        /**
         * Load the translation of the plugin.
         */
        public function load_textdomain() {
            load_plugin_textdomain('fp', false, plugin_basename(dirname(__FILE__)) . '/languages');
        }

        /**
         * Activation hook.
         * 
         * Create database structure
         */
        function activate() {

            require_once ABSPATH . 'wp-admin/includes/upgrade.php';

            global $wpdb;

            $charset_collate = '';
            if ($wpdb->has_cap('collation')) {
                if (!empty($wpdb->charset))
                    $charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
                if (!empty($wpdb->collate))
                    $charset_collate .= " COLLATE $wpdb->collate";
            }

            // Plugin database table version
            $db_version = "1.0";

            $sql = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}{$this->prefix}match` (
		  `match_id` int(11) NOT NULL AUTO_INCREMENT,
		  `match_no` int(11) NOT NULL,
		  `kickoff` datetime NOT NULL,
		  `home_team_id` int(11) NOT NULL,
		  `away_team_id` int(11) NOT NULL,
		  `home_goals` int(11) NOT NULL,
		  `away_goals` int(11) NOT NULL,
		  `home_penalties` int(11) NOT NULL,
		  `away_penalties` int(11) NOT NULL,
		  `venue_id` int(11) NOT NULL,
		  `is_result` BOOL NOT NULL DEFAULT '0', 
		  `extra_time` BOOL NOT NULL DEFAULT '0', 
		  `stage_id` int(11) NOT NULL,
		  `scored` BOOL NOT NULL DEFAULT '0', 
		  `wwhen` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		  PRIMARY KEY (`match_id`)
		) $charset_collate";
            $wpdb->query($sql);

            $sql = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}{$this->prefix}prediction` (
		  `prediction_id` int(11) NOT NULL AUTO_INCREMENT,
		  `user_id` bigint(20) NOT NULL,
		  `match_id` int(11) NOT NULL,
		  `home_goals` int(11) NOT NULL,
		  `away_goals` int(11) NOT NULL,
		  `home_penalties` int(11) NOT NULL,
		  `away_penalties` int(11) NOT NULL,
		  `points` int(11) NOT NULL,
		  `wwhen` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
		  PRIMARY KEY (`prediction_id`),
		  UNIQUE KEY `idx_pred_um` (`user_id`,`match_id`),
		  INDEX  `idx_pred_wwhen` (  `wwhen` )
		) $charset_collate";
            $wpdb->query($sql);

            $sql = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}{$this->prefix}stage` (
		  `stage_id` int(11) NOT NULL AUTO_INCREMENT,
		  `stage_name` varchar(32) NOT NULL,
		  `is_group` tinyint(1) NOT NULL,
		  `sort_order` int(11) NOT NULL,
		  `wwhen` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		  PRIMARY KEY (`stage_id`)
		) $charset_collate";
            $wpdb->query($sql);

            $sql = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}{$this->prefix}team` (
		  `team_id` int(20) NOT NULL AUTO_INCREMENT,
		  `name` varchar(64) NOT NULL,
		  `country` char(3) NOT NULL,
		  `team_url` varchar(255) NOT NULL,
		  `group_order` int(11) NOT NULL,
		  `wwhen` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		  PRIMARY KEY (`team_id`)
		) $charset_collate";
            $wpdb->query($sql);

            $sql = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}{$this->prefix}venue` (
		  `venue_id` int(11) NOT NULL AUTO_INCREMENT,
		  `venue_name` varchar(64) NOT NULL,
		  `venue_url` varchar(255) NOT NULL,
		  `stadium` varchar(64) NOT NULL,
		  `tz_offset` int(11) NOT NULL,
		  `wwhen` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		  PRIMARY KEY (`venue_id`)
		) $charset_collate";
            $wpdb->query($sql);
            
            update_option($this->prefix . 'db_version', $db_version);

            add_option($this->prefix . 'nag', 10);
            add_option($this->prefix . 'show_predictions', 0);
            add_option($this->prefix . 'promo_link', 0);
            add_option($this->prefix . 'countdown_format', __('Next prediction deadline in', FP_PD) . " %%D%%d, %%H%%h, %%M%%m, %%S%%s");
            add_option($this->prefix . 'browser_locale', 1);
            add_option($this->prefix . 'adjust_knockout', 1);
            add_option($this->prefix . 'match_predictions', '');
            add_option($this->prefix . 'user_predictions', '');
            delete_option($this->prefix . 'group_stats');  // Clear cache

            /**
             * Set Capabilities
             */
            $role = get_role('administrator');
            $role->add_cap($this->prefix . 'manager'); // Can manage players, teams etc.

            $role = get_role('editor');
            $role->add_cap($this->prefix . 'manager');

            return true;
        }

        /**
         * Deactivation hook.
         */
        function deactivate() {
            delete_option($this->prefix . 'group_stats');  // Clear cache
            flush_rewrite_rules();
        }

        function adjust_knockout() {
            echo '<style type="text/css">table.knockout {margin-left: -17em !important;}</style>';
        }

    }

    Football_Start::instance();
}
?>