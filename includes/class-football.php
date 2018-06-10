<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Football.
 *
 * @class       Football
 * @version     1.0
 * @author      Anton Shulga
 */
class Football {
	
	var $prefix = 'fp_';
	
	/**
	 * error handling
	 *
	 * @var boolean
	 */
	private $error = false;
	
	/**
	 * message
	 *
	 * @var string
	 */
	private $message = null;
	
	function __construct() {
        
		global $wpdb;
		if (defined('WPLANG') && WPLANG) {
			$wpdb->query($wpdb->prepare('SET lc_time_names = %s', WPLANG));
		}
                
                add_action('wp_enqueue_scripts', array($this, 'connect_resources'));
                
                add_action('widgets_init', array($this, 'widgets_init'));
		                
                add_shortcode(FP_PD, array($this, 'shortcode'));
	}
        	
	function connect_resources() {
                wp_enqueue_style($this->prefix.'css', WP_PLUGIN_URL . '/' . FP_PD . '/css/style.css');
		wp_enqueue_script($this->prefix . 'js', WP_PLUGIN_URL . '/' . FP_PD.'/js/fp.js', array( 'jquery' ));
                wp_localize_script( $this->prefix . 'js', 'FPScript', array(
                            'ajax_url'  => admin_url( 'admin-ajax.php' ),
//                            'login_nonce' => wp_create_nonce( 'fp_login_nonce_field' ),
                    )
                ); 

	}

        
	/**
	 * Initialize the plugin widgets
	 */
	function widgets_init() {
		/**
		 * For the results table
		 */
                require_once FP_ABSPATH.'includes/class-fp-widgets.php';
		register_widget('FootballRankingWidget');
		register_widget('FootballPredictionsWidget');
		register_widget('FootballStandingsWidget');
	}
		
	/**
	 * Process shortcode [football-predictor]
	 * 
	 */
	function shortcode($atts) {
		
		extract(shortcode_atts(array(
			'predict' => 1,
			'ranking' => 0,
			'tables' => 0,					// Show group tables
			'scores' => 0,					// All users predictions by match id
			'stage' => 0,					// Group id - zero = all
			'show_results' => 1,                            // Show match results below group tables
			'results' => 0,					// Match results
			'knockout' => 0,				// Knockout stage results
			'user' => 0,					// Display current users predictions
			'limit' => 999999,				// Limit ranking and prediction scores
			'highlight' => '',				// CSS style to apply to current user in rankings
			'show_total' => 0,				// Display total in user predictions
			'group' => false,				// Show only group stage matches
			'kickoff' => false,				// Order matches by kickoff time
			'predict_penalties' => true,                    // Users can predict penalty goals.
			'avatar' => 1,					// Display users' avatar
			'team' => 0						// Show match results for a specific team
		), $atts));
		
		$output = '';
		
		if (!is_numeric($stage)) {
			$stage = 0;
		}
		
		if (!is_numeric($limit)) {
			$limit = 999999;
		}
                
                if ($ranking || $tables || $scores || $results || $knockout || $user || $show_total) {
                    require_once FP_ABSPATH.'includes/class-fp-reports.php';
                    $r = new FootballReport();
                    if ($ranking) {
                            $output =  $r->user_ranking($limit, $avatar, $highlight);
                            return $output;
                    }

                    if ($tables) {
                            $output =  $r->group_tables($stage, $show_results);
                            return $output;
                    }

                    if ($scores) {
                            $output =  $r->user_scores($scores, $limit, -1, $highlight);
                            return $output;
                    }

                    if ($results) {
                            $output =  $r->results($stage, '100%', $team);
                            return $output;
                    }

                    if ($knockout) {
                            $output =  $r->knockout();
                            return $output;
                    }
                    if ($user || $show_total) {
                            $output =  $r->user_predictions($user, $show_total, $show_results);
                            return $output;
                    }
                }		
		
		if ($predict) {
			require_once FP_ABSPATH.'includes/class-fp-predict.php';
			$p = new FootballPredict();
			$output =  $p->prediction_form($stage, $limit, $group, $kickoff, $predict_penalties);
			return $output;
		}
	}
	
	function debug($var, $echo = true) {
		$output = "<pre>";
		$output .= print_r($var, true);
		$output .= "</pre>";
		if ($echo) echo $output;
		return $output;
	}
	
	/**
	 * Clean the input string of dangerous input.
	 * @param $str input string
	 * @return cleaned string.
	 */
	function clean($str) {
		$str = strip_tags($str);
		return @trim(htmlspecialchars($str, ENT_QUOTES));
	}
	
	/**
	 * Reverse clean() after getting from DB
	 * @param $str input string
	 * @return cleaned string.
	 */
	function unclean($str) {
		return stripslashes($str);
	}
	
	function flag($country) {
		$class = ($country != 'xxx' ? $this->prefix.'flag' : '');
		return '<img alt="" class="'.$class.'" src="'.WP_PLUGIN_URL.'/'.FP_PD.'/images/'.strtolower($country).'.png" />';
	}
	
	/**
	 * set message
	 *
	 * @param string $message
	 * @param boolean $error triggers error message if true
	 * @return none
	 */
	function setMessage( $message, $error = false ) {
		$type = 'success';
		if ( $error ) {
			$this->error = true;
			$type = 'error';
		}
		$this->message[$type] = $message;
	}
	
	/**
	 * return message
	 *
	 * @param none
	 * @return string
	 */
	function getMessage() {
		if (is_null($this->message) || (empty($this->message))) return false;
		
		if ( $this->error )
			return $this->message['error'];
		else
			return $this->message['success'];
	}
	
	/**
	 * print formatted message
	 *
	 * @param none
	 * @return string
	 */
	function printMessage($echo = true) {
		if ($this->getMessage() === false)  return '';
		
		$str = '';
		
		if ( $this->error )
			$str = "<div class='message error'><p>".$this->getMessage()."</p></div>";
		else
			$str = "<div class='message updated fade'><p><strong>".$this->getMessage()."</strong></p></div>";
		$this->message = null;
		
		if (!$echo) return $str;
		echo $str;
	}
	
	/*
	 * Check for positive integer 
	 * TODO allow leading zeros !
	 */
	function isint($i) {
		return ((string)$i === (string)(int)$i && (int)$i >= 0);
	}
	
	/*
	 * Return an href
	 */
	function mklink($str, $url, $title) {	
		$link = $str;
		if (!empty($url)) {
			$link = '<a href="'.$url.'" title="'.$title.'" alt="'.$title.'" target="_blank" >'.$str.'</a>';
		} else {
			$link = '<span title="'.$title.'">'.$str.'</span>';
		}
		return $link;
	}
	
	function format_date($mysql_date) {
		return mysql2date(get_option('date_format') . ' ' . get_option('time_format'), $mysql_date);
	}
        
        
        /**
        * Do things on plugin uninstall.
        */
        public function uninstall() {
                if (!current_user_can('activate_plugins'))
                    return;
                check_admin_referer('bulk-plugins');

                if (__FILE__ != WP_UNINSTALL_PLUGIN)
                    return;

                global $wpdb;

                $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}fp_stage");
                $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}fp_team");
                $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}fp_venue");
                $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}fp_match");
                $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}fp_prediction");

                delete_option( 'fp_db_version' );
                delete_option( 'fp_promo_link' );
                delete_option( 'fp_group_stats' );
                delete_option( 'fp_scoring' );
                delete_option( 'fp_countdown_format' );
                delete_option( 'fp_browser_locale' );

                $roles = array("subscriber", "contributor", "author", "editor", "administrator");
                foreach ($roles as $role) {
                        $arole = get_role($role);
                        $arole->remove_cap('fp_manager');
                }
        }
}