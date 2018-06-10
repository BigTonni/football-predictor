<?php
if (!defined('ABSPATH')) {
    exit;
}
/**
 * @package Football
 * 
 * Prediction forms for the Football plugin.
 * 
 */
 
class FootballPredict extends Football {
	
	/**
	 * Constructor
	 */
	function __construct() {
                add_action('wp_ajax_footballpredictor_ajax', array($this,'ajax'));
		parent::__construct();
	}
	
	/**
	 * Show the prediction form for a logged in user.
	 * 
	 * @param $stage - Show only matches from a particular stage, i.e. Group A  0 == all
	 * @param $limit - Limit to n matches
	 * @param $group - Show group stages matches only
	 * @param $kickoff - Sort by kickoff time. Mutually exclusive with $stage
	 * @return unknown_type
	 */
	function prediction_form($stage, $limit = 0, $group = false, $kickoff = false, $predict_penalties = true) {
		
		global $wpdb;
		
		$disabled = '';
		$match = array();
		$output = '';
		$locale = get_option($this->prefix.'browser_locale', 1);
		
		$msg_id = $this->prefix . "s$stage";
		
		/**
		 * Protect against remote posts and gets from anonymous users.
		 */
		$logged_in = is_user_logged_in();
		if (!$logged_in) {
			
			$login_url = wp_login_url( get_permalink() );
			$register_url = esc_url(add_query_arg(array('action' => 'register'), $login_url));
			$this->setMessage(sprintf(__('Please <a href="%1$s">login</a> or <a href="%2$s">register</a> to make a prediction', FP_PD), $login_url, $register_url),false);
			$disabled = ' disabled ';			
		}
		
		/**
		 * Save entered goals
		 */
		if ($logged_in && isset($_POST[$this->prefix.'save'])) {
			if (isset($_POST['match'])) {
				$match = sanitize_text_field($_POST['match']);
			}
			$ret = $this->save_prediction($match);
		}
		
		/**
		 * For non logged in users this returns an empty array.
		 * $this->score() default unset match array elements to ''
		 */
		$match = $this->get_predictions();
		
		$stage_filter = '';
		$group_filter = '';
		if ($stage) $stage_filter = "AND s.stage_id = $stage";
		if ($group) $group_filter = ' AND is_group = 1';
		$sort_order = 'sort_order, match_no';
		if ($kickoff) $sort_order = 'kickoff';
		$limit_to = '';
		if ($limit) $limit_to = "LIMIT $limit";
		
		$sql = "SELECT s.stage_id, match_id, match_no, v.tz_offset AS tzoffset,
					h.name AS home_team_name, a.name AS away_team_name, is_group,
					home_goals, away_goals, venue_name, stage_name, 
					h.country AS home_country, a.country AS away_country,
					h.team_url AS home_url, a.team_url AS away_url,
					DATE_ADD(kickoff, INTERVAL v.tz_offset HOUR) AS local_kickoff,
					venue_url, stadium, DATE_FORMAT(kickoff, '%Y%m%d%H%i') AS utc_kickoff
				FROM 
					{$wpdb->prefix}{$this->prefix}match m,
					{$wpdb->prefix}{$this->prefix}venue v,
					{$wpdb->prefix}{$this->prefix}stage s,
					{$wpdb->prefix}{$this->prefix}team h,
					{$wpdb->prefix}{$this->prefix}team a
				WHERE
					m.venue_id = v.venue_id AND m.stage_id = s.stage_id AND
					m.home_team_id = h.team_id AND m.away_team_id = a.team_id AND
					UTC_TIMESTAMP() < kickoff
					$stage_filter $group_filter
				ORDER BY
					$sort_order
				$limit_to";
		
		$result = $wpdb->get_results( $sql , OBJECT );
		//echo $wpdb->prepare($sql, $tzdiff);
		
		if (empty($result)) {
			return __('No matches available in this championship.', FP_PD);
		}		
		
		$save = '<p class="save-button"><input '.$disabled.' class="button" type="submit" value="'.__('Save', FP_PD).'" name="'.$this->prefix.'save" /></p>';
		
		$output .= '<div id="'.$msg_id.'">' . $this->printMessage(false) . '</div>' . PHP_EOL;
		
 		$output .= $this->countdown_clock($stage, ($kickoff ? 'kickoff' : ''));
		
		$output .= '<form class="'.$this->prefix.'user_form" method="POST">' . PHP_EOL;
		$stage_id = -1;
		$stage_name = '';
		$tabindex = 2;
		foreach ($result as $row) {
			// Order by kickoff
			if ($kickoff) {
				$stage_name = $this->unclean($row->stage_name) . ' &ndash; ';
				if ($stage_id == -1) $output .= '<table class="predictor">' . PHP_EOL;
				$stage_id = 1;
			} else {
				if ($stage_id != $row->stage_id) {
					if ($stage_id != -1) {
						$output .= '</table>' . PHP_EOL;
						$output .= $save . PHP_EOL;
					}
					$output .= '<table class="predictor"><tr><th colspan="5">'.$this->unclean($row->stage_name).'</th></tr>' . PHP_EOL;
					$stage_id = $row->stage_id;
				}
			}
			
			$output .= '<tr><td class="l">'.$this->mklink($this->flag($row->home_country), $row->home_url, $this->unclean($row->home_team_name)) . ' ' . $this->mklink($this->unclean($row->home_team_name), $row->home_url, $this->unclean($row->home_team_name));
			$output .= '</td><td class="cl"><input '.$disabled.' type="text" tabindex="'.$tabindex.'" value="'.$this->score($match,$row->match_id,'home').'" name="match['.$row->match_id.'][home]" /></td>';
			$tabindex++;
			$output .= '<td class="c">x</td>';
			$output .= '<td class="cr"><input '.$disabled.' type="text" tabindex="'.$tabindex.'" value="'.$this->score($match,$row->match_id,'away').'" name="match['.$row->match_id.'][away]" /></td><td class="r">';
			$tabindex++;
			$output .= $this->mklink($this->unclean($row->away_team_name), $row->away_url, $this->unclean($row->away_team_name)).' '.$this->mklink($this->flag($row->away_country), $row->away_url, $this->unclean($row->away_team_name)).'</td></tr>' . PHP_EOL;
			
			/**
			 * For knockout stage allow penalty scores.
			 */
			if (!$row->is_group && $predict_penalties) {
				$output .= '<tr><td class="r">' . __('Penalties', FP_PD);
				$output .= '</td><td class="cl"><input '.$disabled.' type="text" tabindex="'.$tabindex.'" value="'.$this->score($match,$row->match_id,'pen_home').'" name="match['.$row->match_id.'][pen_home]" /></td>';
				$tabindex++;
				$output .= '<td class="c">x</td>';
				$output .= '<td class="cr"><input '.$disabled.' type="text" tabindex="'.$tabindex.'" value="'.$this->score($match,$row->match_id,'pen_away').'" name="match['.$row->match_id.'][pen_away]" /></td><td class="l">';
				$tabindex++;
				$output .= __('shootout', FP_PD).'</td></tr>' . PHP_EOL;
			}
			$output .= '<tr class="venue"><td  class="sep" colspan="5">' . $stage_name . $this->mklink($row->venue_name,$row->venue_url, $row->stadium).' &ndash; ';
			$output .= '<span ';
			if ($locale) $output .= 'tzoffset="'.$row->tzoffset.'" utc="'.$row->utc_kickoff.'"';
			$output .= 'class="'.$this->prefix.'kickoff_time">'.$this->format_date($row->local_kickoff).
				'</span></td></tr>' . PHP_EOL;
		}
		if (count($result)) {
			$output .= '</table>' . PHP_EOL;
			$output .= $save . PHP_EOL;
		}
		
		$output .= '<input type="hidden" name="msg_id" value="'.$msg_id.'" />' . PHP_EOL;
		$output .= '</form>' . PHP_EOL;
		
		return $output;
	}
	
	/**
	 * Return user entry on form if set
	 */
	function score($match, $match_id, $team) {
		if (is_array($match)) {
			if (isset($match[$match_id])) {
				return $match[$match_id][$team];
			}
		}
		return "";
	}
	
	/**
	 * Get my predictions
	 */
	function get_predictions() {
		global $wpdb;
                
		$current_user = wp_get_current_user();
                if( 0 == $current_user->ID ){
                    die('Error');
                }
		
		$match = array();
		$sql = "SELECT match_id, home_goals, away_goals, home_penalties, away_penalties
				FROM {$wpdb->prefix}{$this->prefix}prediction
				WHERE user_id = %d";
		$result = $wpdb->get_results($wpdb->prepare($sql, $current_user->ID));
		foreach ($result as $row) {
			$match[$row->match_id] = array('home' => $row->home_goals, 'away' => $row->away_goals,
				'pen_home' => ($row->home_penalties > 0 ? $row->home_penalties : ''),
				'pen_away' => ($row->away_penalties > 0 ? $row->away_penalties : ''));
		}
		return $match;
	}
	
	/**
	 * Save this submitted prediction
	 */
	function save_prediction($match) {
		global $wpdb;
		
		$ret = false;
		if (!is_array($match)) {
			return false;
		}
		
		$this->setMessage(__('Prediction saved', FP_PD));
		
		/**
		 * Clean input
		 */
		foreach ($match as $key=>$m) {
			$match[$key] = array_map('trim', $m);
		}
		
		/**
		 * Validate, both home and away scores must be present or both blank.
		 */
		foreach ($match as $key=>$m) {
			if ((!empty($m['home']) || !empty($m['away'])) && (!$this->isint($m['home']) || !$this->isint($m['away']))) {
				$this->setMessage(__('Match score must be numeric', FP_PD), true);
				return false;
			}
			
			/**
			 * Both penality scores must be present or blank.
			 */
			if (isset($m['pen_home'])) {
				if ((!empty($m['pen_home']) || !empty($m['pen_away'])) && !$this->isint($m['pen_home'])) {
					$this->setMessage(__('Match score must be numeric', FP_PD), true);
					return false;
				}
				if (empty($m['pen_home'])) $match[$key]['pen_home'] = 0;
			} else {
				$match[$key]['pen_home'] = 0;
			}
			if (isset($m['pen_away'])) {
				if ((!empty($m['pen_home']) || !empty($m['pen_away'])) && !$this->isint($m['pen_away'])) {
					$this->setMessage(__('Match score must be numeric', FP_PD), true);
					return false;
				}
				if (empty($m['pen_away'])) $match[$key]['pen_away'] = 0;
			} else {
				$match[$key]['pen_away'] = 0;
			}
			
			/**
			 * Entered penalities but no goals !
			 */
			if (($match[$key]['pen_home'] > 0 || $match[$key]['pen_away'] > 0) && (!$this->isint($m['home']) || !$this->isint($m['away']))) {
					$this->setMessage(__('Match score must be numeric', FP_PD), true);
					return false;
			}
			
			/**
			 * Entered penalities but not a draw
			 */
			if (($match[$key]['pen_home'] > 0 || $match[$key]['pen_away'] > 0) && ($m['home'] != $m['away'])) {
					$this->setMessage(__('You can only enter penalties for a draw', FP_PD), true);
					return false;
			}
		}
		
		/**
		 * Save each prediction - checking that they are before the prediction deadline
		 * as the user have have left the form displayed over the deadline !
		 */
		
		$current_user = wp_get_current_user();
                if( 0 == $current_user->ID ){
                    die('Error');
                }
		foreach ($match as $key=>$m) {
			
			/**
			 * Don't save 'blank' predictions
			 */
			if ($m['home'] == '' && $m['away'] == '') {
				
				$sql = "DELETE FROM
							{$wpdb->prefix}{$this->prefix}prediction
						WHERE
							user_id = %d AND match_id = %d";
				$wpdb->query($wpdb->prepare($sql, $current_user->ID, $key));
				continue;
			}
			
			$sql = "SELECT kickoff FROM {$wpdb->prefix}{$this->prefix}match WHERE match_id = %d";
			$match_start = $wpdb->get_row($wpdb->prepare($sql, $key));
			if (!$match_start) {
				$this->setMessage(__('Missing match. Prediction not saved.', FP_PD), true);
				return false;
			}
			
			$sql = "SELECT prediction_id
					FROM
						{$wpdb->prefix}{$this->prefix}prediction p
					WHERE
						user_id = %d AND match_id = %d";
			$row = $wpdb->get_row($wpdb->prepare($sql, $current_user->ID, $key));
			if ($row) {
				$sql = "UPDATE {$wpdb->prefix}{$this->prefix}prediction
						SET
							home_goals = %d,
							away_goals = %d,
							home_penalties = %d,
							away_penalties = %d,
							wwhen = UTC_TIMESTAMP()
						WHERE
							prediction_id = %d AND UTC_TIMESTAMP() < %s";
				$ret = $wpdb->query($wpdb->prepare($sql, $m['home'], $m['away'], $m['pen_home'], $m['pen_away'], $row->prediction_id,$match_start->kickoff));
			} else {
				
				$sql = "INSERT INTO {$wpdb->prefix}{$this->prefix}prediction
							(user_id, match_id, home_goals, away_goals, home_penalties, away_penalties, points, wwhen)
						SELECT %d, %d, %d, %d, %d, %d, 0, UTC_TIMESTAMP() FROM DUAL
						WHERE
							UTC_TIMESTAMP() < %s";
				$ret = $wpdb->query($wpdb->prepare($sql, $current_user->ID, $key, $m['home'], $m['away'], $m['pen_home'], $m['pen_away'], $match_start->kickoff));
			}
			if ($ret === false) {  // can return 0 as successfully modified zero rows
				$this->setMessage(__('Error saving prediction', FP_PD) . $wpdb->last_error, true);
				return false;
			}
			if ($ret == 0) {
				// No rows updated/inserted, so prediction must be too late !!!
				$this->setMessage(__('Some, or all, of your predictions are too late and will not be counted.', FP_PD), true);
			}
		}
		return $ret;
	}
	
	/**
	 * Save via AJAX
	 */
	function ajax() {
            
                $current_user = wp_get_current_user();
                if( 0 == $current_user->ID ){
                    die('Error');
                }
		
		$match = array();
		
		if (isset($_POST['match'])) {
		
			$match = sanitize_text_field($_POST['match']);
			$this->save_prediction($match);
		} else {
			$this->setMessage(__('No data to save', FP_PD), true);
		}
		
		require_once FP_ABSPATH.'includes/class-fp-reports.php';
		$r = new FootballReport();
		
		/**
		 * Determine the widget settings. Not a good check as there may be more than
		 * one widget with different settings. TODO - Improve check.
		 */
		require_once FP_ABSPATH.'includes/class-fp-widgets.php';
		$w = new FootballPredictionsWidget();
		$show_total = false;
		$show_result = false;
		$settings = $w->get_settings();
		foreach ($settings as $setting) {
			if (isset($setting['total'])) $show_total = $setting['total'];
			if (isset($setting['results'])) $show_result = $setting['results'];
		}
		$ret = array('notice' => $this->printMessage(false), 'preds' => $r->user_predictions(1, $show_total, $show_result));
		
		die(json_encode($ret));
	}
	
	/**
	 * Display a Javascript clock for the next match
	 */
	function countdown_clock($stage, $kickoff) {
		/**
		 * Gets the current date and time from the server and the
		 * date and time of the next prediction deadline.
		 */
		global $wpdb;
		$deadline = array();
		$output = '';
		
		$stage_filter = '';
		if ($stage) $stage_filter = "AND stage_id = $stage";
		
		$sql = "SELECT DATE_FORMAT(UTC_TIMESTAMP(), '%m/%d/%Y %r') AS 'now', 
			 		DATE_FORMAT(MIN(kickoff), '%m/%d/%Y %r') AS 'target'
			 	FROM
					{$wpdb->prefix}{$this->prefix}match
			 	WHERE
			 		kickoff > UTC_TIMESTAMP() $stage_filter";
		$result = $wpdb->get_row( $sql , OBJECT );
		
		if (is_null($result) || is_null($result->target)) {
			return "";
		}
		
		$deadline['now'] = $result->now;
		$deadline['target'] = $result->target;
		$stage = $stage . $kickoff;  // Uniquely identify this clock if multiple shortcodes on 1 page
		
		/**
		 * If there is a prediction deadline coming up display a Javascript countdown clock.
		 */
		if ($deadline) {
			$fmt = get_option($this->prefix.'countdown_format', __('Next prediction deadline in', FP_PD) . ' %%D%%d, %%H%%h, %%M%%m, %%S%%s');
			$output .= '<div class="fp_clock" id="fp_clock_'.$stage.'">Clock</div>';
			$output .= '
		<script type="text/javascript">
			<!--
			var fp_cd'.$stage.' = new fp_countdown(\'fp_cd'.$stage.'\');
			fp_cd'.$stage.'.Div			= "fp_clock_'.$stage.'";
			fp_cd'.$stage.'.TargetDate		= "'.$deadline['target'].'";
			fp_cd'.$stage.'.ServerDate		= "'.$deadline['now'].'";
			fp_cd'.$stage.'.DisplayFormat	= "'.$fmt.'";
			fp_cd'.$stage.'.FinishStr    	= "'.__('Too Late - Prediction deadline passed', FP_PD).'";
			fp_cd'.$stage.'.Setup();
			
			//-->
		</script>
				
			';
		} else {
			$output .= '<div class="fp_clock" id="fp_clock_'.$stage.'">' .
				__('No matches available in this championship.', FP_PD) . '</div>';
		}
		
		return $output;
	}
}

?>