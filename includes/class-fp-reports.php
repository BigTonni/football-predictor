<?php
if (!defined('ABSPATH')) {
    exit;
}
/**
 * @package Football
 * 
 */
 
/**
 * Match stats
 */
class FootballStats {
	var $team_id = 0;
	var $played = 0;
	var $won = 0;
	var $lost = 0;
	var $drawn = 0;
	var $gf = 0;
	var $ga = 0;
	var $points = 0;
	var $stage_id = 0;
	var $team_country = '';
	var $team_name = '';
	var $team_url = '';
	var $sort_order = 0;
	var $group_order = 0;
}

class FootballReport extends Football {
	
	private $knockout;  // Knockout stage match results
	private $locale; 	// cache locale request
	
	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
	}
	
	function table_header($stage_name, $compact = false) {
		
		$team = __('Team',FP_PD);
		$mp = __('MP', FP_PD);
		$mp_l = __('Matches Played', FP_PD);
		$w = __('W', FP_PD);
		$w_l = __('Won', FP_PD);
		$d = __('D', FP_PD);
		$d_l = __('Draw', FP_PD);
		$l = __('L', FP_PD);
		$l_l = __('Lost', FP_PD);
		$gf = __('GF', FP_PD);
		$gf_l = __('Goals For', FP_PD);
		$ga = __('GA', FP_PD);
		$ga_l = __('Goals Against', FP_PD);
		$gd = __('GD', FP_PD);
		$gd_l = __('Goal difference', FP_PD);
		$p = __('Pts', FP_PD);
		$p_l = __('Points', FP_PD);
		
		if($compact == true) {
			
			$output = "<table class='group zebra'>
			<tr><th colspan='8'><h3>$stage_name</h3></th></tr>
			<tr class='row-header'>
				<th class='column-team'>$team</th>
				<th class='column-goal'><span title='$p_l'>$p</span></th>
				<th class='column-goal'><span title='$gd_l'>$gd</span></th>
			</tr>";
			
		} else {
			
			$output = "<table class='group zebra'>
			<tr><th colspan='8'><h3>$stage_name</h3></th></tr>
			<tr class='row-header'>
				<th class='column-team'>$team</th>
				<th class='column-goal'><span title='$mp_l'>$mp</span></th>
				<th class='column-goal'><span title='$w_l'>$w</span></th>
				<th class='column-goal'><span title='$d_l'>$d</span></th>
				<th class='column-goal'><span title='$l_l'>$l</span></th>
				<th class='column-goal'><span title='$gf_l'>$gf</span></th>
				<th class='column-goal'><span title='$ga_l'>$ga</span></th>
				<th class='column-goal'><span title='$p_l'>$p</span></th>
			</tr>";			
		}
		
		return $output;
	}
	
	function table_row($row, $compact = false) {	
		$flag = $this->mklink($this->flag($row->team_country), $this->unclean($row->team_url), $this->unclean($row->team_name));
		$name = $this->mklink($this->unclean($row->team_name), $this->unclean($row->team_url), $this->unclean($row->team_name));
		$gd = $row->gf - $row->ga;
		
		if($compact == true) {
		
			$output = "<tr class='fp-row'>
				<td class='column-team'>$flag $name</td>
				<td class='column-goal'>$row->points</td>
				<td class='column-goal'>$gd</td>
			</tr>";
			
		} else {
			
			$output = "<tr class='fp-row'>
				<td class='column-team'>$flag $name</td>
				<td class='column-goal'>$row->played</td>
				<td class='column-goal'>$row->won</td>
				<td class='column-goal'>$row->drawn</td>
				<td class='column-goal'>$row->lost</td>
				<td class='column-goal'>$row->gf</td>
				<td class='column-goal'>$row->ga</td>
				<td class='column-goal'>$row->points</td>
			</tr>";		
		}
		
		return $output;
	}
	
	/**
	 * For each team - calculate the matches played, points, etc.
	 */
	function calculate_group_tables() {
		
		global $wpdb;
		
		/**
		 * All matches
		 */
		$sql = "SELECT DISTINCT t.team_id, t.name AS team_name, t.team_url, t.country AS team_country, stage_name, s.stage_id, s.sort_order, t.group_order
				FROM
					{$wpdb->prefix}{$this->prefix}stage s,
					{$wpdb->prefix}{$this->prefix}team t,
					{$wpdb->prefix}{$this->prefix}match m
				WHERE
					(m.home_team_id = t.team_id OR m.away_team_id = t.team_id) AND
					m.stage_id = s.stage_id AND s.is_group = 1 
				ORDER BY
					s.sort_order, match_no";
		$teams = @$wpdb->get_results($sql, OBJECT_K);
		
		/**
		 * Matches played
		 */
		$sql = "SELECT t.team_id, COUNT(*) AS played, t.name, s.stage_id FROM
					{$wpdb->prefix}{$this->prefix}stage s,
					{$wpdb->prefix}{$this->prefix}team t,
					{$wpdb->prefix}{$this->prefix}match m
				WHERE
					(m.home_team_id = t.team_id OR m.away_team_id = t.team_id) AND
					m.stage_id = s.stage_id AND s.is_group = 1 AND m.is_result = 1 
				GROUP BY
					t.team_id";
		$mp =  @$wpdb->get_results($sql, OBJECT_K);
		
		/**
		 * Matches won
		 */
		$sql = "SELECT t.team_id, COUNT(*) AS won, t.name FROM
					{$wpdb->prefix}{$this->prefix}stage s,
					{$wpdb->prefix}{$this->prefix}team t,
					{$wpdb->prefix}{$this->prefix}match m
				WHERE
					((m.home_team_id = t.team_id AND m.home_goals > m.away_goals) OR
					 (m.away_team_id = t.team_id AND m.away_goals > m.home_goals))
					AND m.stage_id = s.stage_id AND s.is_group = 1 AND m.is_result = 1 
				GROUP BY
					t.team_id";
		$won =  @$wpdb->get_results($sql, OBJECT_K);
		
		/**
		 * Matches lost
		 */
		$sql = "SELECT t.team_id, COUNT(*) AS lost, t.name FROM
					{$wpdb->prefix}{$this->prefix}stage s,
					{$wpdb->prefix}{$this->prefix}team t,
					{$wpdb->prefix}{$this->prefix}match m
				WHERE
					((m.home_team_id = t.team_id AND m.home_goals < m.away_goals) OR
					 (m.away_team_id = t.team_id AND m.away_goals < m.home_goals))
					AND m.stage_id = s.stage_id AND s.is_group = 1 AND m.is_result = 1 
				GROUP BY
					t.team_id";
		$lost =  @$wpdb->get_results($sql, OBJECT_K);
		
		/**
		 * Matches drawn
		 */
		$sql = "SELECT t.team_id, COUNT(*) AS drawn, t.name FROM
					{$wpdb->prefix}{$this->prefix}stage s,
					{$wpdb->prefix}{$this->prefix}team t,
					{$wpdb->prefix}{$this->prefix}match m
				WHERE
					((m.home_team_id = t.team_id AND m.home_goals = m.away_goals) OR
					 (m.away_team_id = t.team_id AND m.home_goals = m.away_goals))
					AND m.stage_id = s.stage_id AND s.is_group = 1 AND m.is_result = 1 
				GROUP BY
					t.team_id";
		$drawn =  @$wpdb->get_results($sql, OBJECT_K);
		
		/**
		 * Goals for and against
		 */
		$sql = "SELECT home_team_id AS team_id, SUM(home_goals) AS gf, SUM(away_goals) AS ga FROM
					{$wpdb->prefix}{$this->prefix}stage s,
					{$wpdb->prefix}{$this->prefix}match m
				WHERE
					m.stage_id = s.stage_id AND s.is_group = 1 AND m.is_result = 1 
				GROUP BY
					m.home_team_id";
		$goals1 =  @$wpdb->get_results($sql, OBJECT_K);
		$sql = "SELECT away_team_id AS team_id, SUM(away_goals) AS gf, SUM(home_goals) AS ga FROM
					{$wpdb->prefix}{$this->prefix}stage s,
					{$wpdb->prefix}{$this->prefix}match m
				WHERE
					m.stage_id = s.stage_id AND s.is_group = 1 AND m.is_result = 1 
				GROUP BY
					m.away_team_id";
		$goals2 =  @$wpdb->get_results($sql, OBJECT_K);
		
		$stats = array();
		
		/**
		 * Merge the results from above.
		 */
		foreach ($teams as $team_id=>$val) {
			
			$temp = new FootballStats();
			
			$temp->team_id = $team_id;
			$temp->stage_id = $val->stage_id;
			$temp->team_url = $val->team_url;
			$temp->stage_name = $val->stage_name;
			$temp->team_country = $val->team_country;
			$temp->team_name = $val->team_name;
			$temp->sort_order = $val->sort_order;
			$temp->group_order = $val->group_order;
			
			if (is_array($mp) && array_key_exists($team_id,$mp)) {
				$temp->played = $mp[$team_id]->played;
			}
			if (is_array($won) && array_key_exists($team_id,$won)) {
				$temp->won = $won[$team_id]->won;
				$temp->points += (3 * $temp->won);
			}
			if (is_array($lost) && array_key_exists($team_id,$lost)) {
				$temp->lost = $lost[$team_id]->lost;
			}
			if (is_array($drawn) && array_key_exists($team_id,$drawn)) {
				$temp->drawn = $drawn[$team_id]->drawn;
				$temp->points += (1 * $temp->drawn);
			}
			if (is_array($goals1) && array_key_exists($team_id,$goals1)) {
				$temp->gf = $goals1[$team_id]->gf;
				$temp->ga = $goals1[$team_id]->ga;
			}
			if (is_array($goals2) && array_key_exists($team_id,$goals2)) {
				$temp->gf += $goals2[$team_id]->gf;
				$temp->ga += $goals2[$team_id]->ga;
			}
			
			$stats[] = $temp;
		}
		
		usort($stats, array($this, 'stats_sort'));
		
		update_option($this->prefix.'group_stats', $stats);  // Cache stats
		
		return $stats;
	}
	
	/**
	 * User sort
	 * 
	 * Sort by group sort order (keep groups together), then points, then matches played, then,
	 * 
	 * From http://en.wikipedia.org/wiki/2010_FIFA_World_Cup#Group_stage
	 * 
	 * Tie-breaking criteria
	 * 
	 * For the Football Cup tournament, FIFA uses the following criteria to rank teams in the Group Stage.[88]
	 * 
	 * 1. greatest number of points in all group matches;
	 * 2. goal difference in all group matches;
	 * 3. greatest number of goals scored in all group matches.
	 * 4. greatest number of points in matches between tied teams;
	 * 5. goal difference in matches between tied teams;
	 * 6. greatest number of goals scored in matches between tied teams;
	 * 7. drawing of lots by the FIFA Organising Committee.
	 * 
	 */
	function stats_sort($a, $b) {
		
		if ($a->sort_order == $b->sort_order) {
			if ($a->points == $b->points) {					// Tie break 1.
				if ($a->played == $b->played) {				// Before all matches played promote better
															// points for lesser matches
					$agd = $a->gf - $a->ga;  // Goal difference
					$bgd = $b->gf - $b->ga;
					if ($agd == $bgd) {						// Tie break 2
						
						if ($a->gf == $b->gf) {				// Tie break 3
							
							/*
							 * Tie break criteria between tied teams
							 */
							if ($a->points == $b->points) {	// Tie break 4
								$agd = $a->gf - $a->ga;  // Goal difference
								$bgd = $b->gf - $b->ga;
								if ($agd == $bgd) {						// Tie break 5
									
									if ($a->gf == $b->gf) {				// Tie break 6
										
										if ($a->group_order == $b->group_order) {
											return 0;
										}
					    				return ($a->group_order < $b->group_order) ? -1 : 1; // Tie Break 7 - draw lots		
									}
					    			return ($a->gf < $b->gf) ? 1 : -1;		
								}
					    		return ($agd < $bgd) ? 1 : -1;		
							}
						    return ($a->points < $b->points) ? 1 : -1;
						}
		    			return ($a->gf < $b->gf) ? 1 : -1;		
					}
		    		return ($agd < $bgd) ? 1 : -1;		
			    }
				return ($a->played < $b->played) ? -1 : 1;
			}
		    return ($a->points < $b->points) ? 1 : -1;
		}
	    return ($a->sort_order < $b->sort_order) ? -1 : 1;		
	}
	
	/**
	 * Return the cached group tables
	 */
	function group_match_stats() {
		$stats = get_option($this->prefix.'group_stats');  // Get cached stats
		if (!$stats) {
			$stats = $this->calculate_group_tables();
		}
		return $stats;
	}
	
	/**
	 * Display group tables.
	 * 
	 * $stage 0 == all, or limit to stage_id
	 * $show_results - if true display match results under group table.
	 */
	function group_tables($stage, $show_results, $width='100%', $compact = false) {
		
		$output = '';
		
		$stats = $this->group_match_stats();
	
		$stage_id = -1;
		foreach ($stats as $row) {
			if ($stage && $stage != $row->stage_id) continue;
			if ($stage_id != $row->stage_id) {
				if ($stage_id != -1) {
					$output .= '</table>' . PHP_EOL;
					if ($show_results) $output .= $this->results($stage_id, $width);
				}
				$output .= $this->table_header($this->unclean($row->stage_name), $compact);
				$stage_id = $row->stage_id;
			}
			$output .= $this->table_row($row, $compact);
		}
		if (count($stats) && !empty($output)) {
			$output .= '</table>' . PHP_EOL;
			if ($show_results) $output .= $this->results($stage_id, $width);
		}
		
		return $output;
	}
	
	/**
	 * Display team name - bold if winner, append (a.e.t) or (pen.) if required.
	 */
	function team_name($row, $home, $justify = true) {
		$output = '';
		$suffix = '';
		$winner1 = '';
		$winner2 = '';
		
		if ($home) {
			$flag = $this->mklink($this->flag($row->home_country), $row->home_url, $this->unclean($row->home_team_name));
			$team = $this->mklink($this->unclean($row->home_team_name), $row->home_url, $this->unclean($row->home_team_name));
			// Bold winner
			if ($row->home_goals + $row->home_penalties > $row->away_goals + $row->away_penalties) {
				$winner1 = '<strong>';
				$winner2 = '</strong>';
				if (!$row->is_group && $row->is_result) {
				
					if ($row->home_penalties != 0 || $row->away_penalties != 0) {
						$suffix = ' (<span title="'.__('Penalties', FP_PD).'">' . __('pen.', FP_PD) . '</span>)';
					} elseif ($row->extra_time) {
						$suffix = ' (<span title="'.__('Extra Time', FP_PD).'">' . __('a.e.t.', FP_PD) . '</span>)';
					}
				}
			}
			$output = "$flag $winner1{$team}{$winner2} $suffix";
			
		} else {
			$flag = $this->mklink($this->flag($row->away_country), $row->away_url, $this->unclean($row->away_team_name));
			$team = $this->mklink($this->unclean($row->away_team_name), $row->away_url, $this->unclean($row->away_team_name));
			// Bold winner
			if ($row->away_goals + $row->away_penalties > $row->home_goals + $row->home_penalties) {
				$winner1 = '<strong>';
				$winner2 = '</strong>';
				if (!$row->is_group && $row->is_result) {
					
					if ($row->home_penalties != 0 || $row->away_penalties != 0) {
						$suffix = ' (<span title="'.__('Penalties', FP_PD).'">' . __('pen.', FP_PD) . '</span>)';
					} elseif ($row->extra_time) {
						$suffix = ' (<span title="'.__('Extra Time', FP_PD).'">' . __('a.e.t.', FP_PD) . '</span>)';
					}
				}
			}
			if ($justify) {
				$output = "$suffix $winner1{$team}{$winner2} $flag";
			} else {
				$output = "$flag $winner1{$team}{$winner2} $suffix";
			}
		}
		
		return $output;
	}
	
	/**
	 * Display teams score - bold if winner - optionally add penalties (n)
	 */
	function team_score($row, $home) {
		$output = '';
		$pen = '';
		$winner1 = '';
		$winner2 = '';
		
		if (!$row->is_result) return '&nbsp;';
		
		if ($home) {
			// Bold winner
			if ($row->home_goals + $row->home_penalties > $row->away_goals + $row->away_penalties) {
				$winner1 = '<strong>';
				$winner2 = '</strong>';
			}
			if (!$row->is_group && $row->is_result) {
				
				if ($row->home_penalties != 0 || $row->away_penalties != 0) {
					$pen = ' ('.$row->home_penalties.')';
				}
			}
			$output = "{$winner1}{$row->home_goals}{$pen}{$winner2}";
			
		} else {
			// Bold winner
			if ($row->away_goals + $row->away_penalties > $row->home_goals + $row->home_penalties) {
				$winner1 = '<strong>';
				$winner2 = '</strong>';
			}
			if (!$row->is_group && $row->is_result) {
				
				if ($row->home_penalties != 0 || $row->away_penalties != 0) {
					$pen = ' ('.$row->away_penalties.')';
				}
			}
			$output = "{$winner1}{$row->away_goals}{$pen}{$winner2}";
		}
		
		return $output;
	}
	
	/**
	 * Match results in a simple table for each stage or team
	 */
	function results($stage, $width="100%", $team = 0) {
		global $wpdb;
		
		$locale = get_option($this->prefix.'browser_locale', 1);
		
		$output = '';
		$stage_filter = '';
		if ($stage) $stage_filter = "AND s.stage_id = $stage";
		$team_filter = '';
		if ($team) $team_filter = "AND (m.home_team_id = $team OR m.away_team_id = $team)";
		
		$sql = "SELECT s.stage_id, match_id, match_no, DATE_ADD(kickoff, INTERVAL v.tz_offset HOUR) AS local_kickoff,
					h.name AS home_team_name, a.name AS away_team_name,
					home_goals, away_goals, home_penalties, away_penalties, venue_name, stage_name, 
					h.country AS home_country, a.country AS away_country,
					h.team_url AS home_url, a.team_url AS away_url, tz_offset,
					venue_url, stadium, is_group, is_result, extra_time,
					DATE_FORMAT(kickoff, '%Y%m%d%H%i') AS utc_kickoff
				FROM 
					{$wpdb->prefix}{$this->prefix}match m,
					{$wpdb->prefix}{$this->prefix}venue v,
					{$wpdb->prefix}{$this->prefix}stage s,
					{$wpdb->prefix}{$this->prefix}team h,
					{$wpdb->prefix}{$this->prefix}team a
				WHERE
					m.venue_id = v.venue_id AND m.stage_id = s.stage_id AND
					m.home_team_id = h.team_id AND m.away_team_id = a.team_id
					$stage_filter $team_filter
				ORDER BY
					sort_order, kickoff";
		
		$result = $wpdb->get_results( $sql , OBJECT );
		
		if ($result) $output .= '<table style="table-layout:fixed;margin-top:1em;width:'.$width.'" class="predictor">' .PHP_EOL;
		
		foreach ($result as $row) {
			$output .= '<tr><td class="l">'.$this->team_name($row, true);
			$output .= '</td><td class="cl">'.$this->team_score($row, true).'</td>';
			$output .= '<td class="c">&ndash;</td>';
			$output .= '<td class="cr">'.$this->team_score($row, false).'</td><td class="r">';
			$output .= $this->team_name($row, false) . '</td><td>';
			if (get_option($this->prefix.'match_predictions', 1)) {
			
				if (get_option($this->prefix.'show_predictions', 0) || ($row->utc_kickoff < date("YmdHi"))) {
					$output .= '<a class="predictions-link" title="'.__('Predictions', FP_PD).'" href="'.get_option($this->prefix.'match_predictions').'&fp=scores&match_id='.$row->match_id.'"><img src="'. WP_PLUGIN_URL.'/'.FP_PD .'/images/predictions.png" /></a>';
				}
			}
			$output .= '</td></tr>' . PHP_EOL;
			$output .= '<tr class="venue"><td  class="sep" colspan="6">'.$this->mklink($row->venue_name,$row->venue_url, $row->stadium).' &ndash; ';
			$output .= '<span ';
			if ($locale) $output .= 'tzoffset="'.$row->tz_offset.'" utc="'.$row->utc_kickoff.'"';
			$output .= ' class="'.$this->prefix.'kickoff_time">'.$this->format_date($row->local_kickoff).'</span></td></tr>' . PHP_EOL;
		}				
		
		if ($result) $output .= "</table>" .PHP_EOL;
		
		return $output;
	}
	
	/**
	 * Get match details
	 */
	function get_stage2_matches() {
		global $wpdb;
		
		$sql = "SELECT match_no, s.stage_id, match_id, tz_offset,
					DATE_ADD(kickoff, INTERVAL v.tz_offset HOUR) AS local_kickoff,
					h.name AS home_team_name, a.name AS away_team_name,
					home_goals, away_goals, 
					home_penalties, away_penalties, venue_name, stage_name, 
					h.country AS home_country, a.country AS away_country,
					h.team_url AS home_url, a.team_url AS away_url,
					venue_url, stadium, is_result, is_group, extra_time,
					DATE_FORMAT(kickoff, '%Y%m%d%H%i') AS utc_kickoff
				FROM 
					{$wpdb->prefix}{$this->prefix}match m,
					{$wpdb->prefix}{$this->prefix}venue v,
					{$wpdb->prefix}{$this->prefix}stage s,
					{$wpdb->prefix}{$this->prefix}team h,
					{$wpdb->prefix}{$this->prefix}team a
				WHERE
					m.venue_id = v.venue_id AND m.stage_id = s.stage_id AND
					m.home_team_id = h.team_id AND m.away_team_id = a.team_id AND
					match_no > 48";   // TODO - Yuck 48
		
		return $wpdb->get_results($sql, OBJECT_K);
	}
	
	function stage($i) {
		return "<!--S{$i}-->" . $this->clean($this->knockout[$i]->stage_name);
	}
	
	function venue($i) {
		
		$url = $this->mklink($this->clean($this->knockout[$i]->venue_name),
								$this->clean($this->knockout[$i]->venue_url),
								$this->clean($this->knockout[$i]->stadium));
		
		$str = "<!--V{$i}--><span ";
		if ($this->locale) $str .= 'tzoffset="'.$this->knockout[$i]->tz_offset."\" utc=\"{$this->knockout[$i]->utc_kickoff}\"";
		$str .= " class=\"date_only {$this->prefix}kickoff_time\">".$this->format_date($this->knockout[$i]->local_kickoff)."</span> &ndash; $url";
		return $str;
	}
	
	function home_score($i) {
		return "<!--GH{$i}-->" . $this->team_score($this->knockout[$i], true);
	}
	
	function home_team($i) {
		return "<!--TH{$i}-->" . $this->team_name($this->knockout[$i], true);
	}
	
	function away_score($i) {
		return "<!--GA{$i}-->" . $this->team_score($this->knockout[$i], false);
	}
	
	function away_team($i) {
		return "<!--TA{$i}-->" . $this->team_name($this->knockout[$i], false, false);
	}
	
	/**
	 * Display the knockout match tables
	 */
	function knockout() {
		
		$flag = array('xxx' => '');
		$this->locale = get_option($this->prefix.'browser_locale', 1);
		
		$this->knockout = $this->get_stage2_matches();
		
		$output = <<<EOT
<table cellspacing="0" cellpadding="0" border="0" class="knockout">
<tbody><tr>
<td height="5"></td>
<td bgcolor="#f2f2f2" align="center" style="border: 1px solid rgb(170, 170, 170);" colspan="2">{$this->stage(49)}</td>
<td colspan="2"></td>
<td bgcolor="#f2f2f2" align="center" style="border: 1px solid rgb(170, 170, 170);" colspan="2">{$this->stage(57)}</td>
<td colspan="2"></td>
<td bgcolor="#f2f2f2" align="center" style="border: 1px solid rgb(170, 170, 170);" colspan="2">{$this->stage(61)}</td>
<td colspan="2"></td>
<td bgcolor="#f2f2f2" align="center" style="border: 1px solid rgb(170, 170, 170);" colspan="2">{$this->stage(64)}</td>
</tr>
<tr>
<td height="5"></td>
<td width="170">&nbsp;</td>
<td width="40">&nbsp;</td>
<td width="10">&nbsp;</td>
<td width="10">&nbsp;</td>
<td width="170">&nbsp;</td>
<td width="40">&nbsp;</td>
<td width="10">&nbsp;</td>
<td width="10">&nbsp;</td>
<td width="170">&nbsp;</td>
<td width="40">&nbsp;</td>
<td width="10">&nbsp;</td>
<td width="10">&nbsp;</td>
<td width="170">&nbsp;</td>
<td width="40">&nbsp;</td>
</tr>
<tr>
<td height="5"></td>
<td rowspan="2" colspan="2">#49 - {$this->venue(49)}</td>
<td style="border-style: solid; border-color: black; border-width: 0pt 0pt 1px;" rowspan="4">&nbsp;</td>
<td style="border-style: solid; border-color: black; border-width: 0pt 0pt 1px;" rowspan="7">&nbsp;</td>
<td rowspan="3" colspan="2"></td>
<td style="border-style: solid; border-color: black; border-width: 0pt 0pt 1px;" rowspan="7">&nbsp;</td>
<td style="border-style: solid; border-color: black; border-width: 0pt 0pt 1px;" rowspan="13">&nbsp;</td>
<td rowspan="9" colspan="2"></td>
<td style="border-style: solid; border-color: black; border-width: 0pt 0pt 1px;" rowspan="13">&nbsp;</td>
<td style="border-style: solid; border-color: black; border-width: 0pt 0pt 1px;" rowspan="25">&nbsp;</td>
<td rowspan="21" colspan="2"></td>
</tr>
<tr>
<td height="5"></td>
</tr>
<tr>
<td height="5"></td>
<td bgcolor="#f9f9f9" style="border: 1px solid rgb(170, 170, 170);" rowspan="2">{$this->home_team(49)}</td>
<td bgcolor="#f9f9f9" align="center" style="border: 1px solid rgb(170, 170, 170);" rowspan="2">{$this->home_score(49)}</td>
</tr>
<tr>
<td height="5"></td>
<td rowspan="2" colspan="2">#57 - {$this->venue(57)}</td>
</tr>
<tr>
<td height="5"></td>
<td bgcolor="#f9f9f9" style="border: 1px solid rgb(170, 170, 170);" rowspan="2">{$this->away_team(49)}</td>
<td bgcolor="#f9f9f9" align="center" style="border: 1px solid rgb(170, 170, 170);" rowspan="2">{$this->away_score(49)}</td>
<td style="border-style: solid; border-color: black; border-width: 2px 3px 1px 0pt;" rowspan="6">&nbsp;</td>
</tr>
<tr>
<td height="5"></td>
<td bgcolor="#f9f9f9" style="border: 1px solid rgb(170, 170, 170);" rowspan="2">{$this->home_team(57)}</td>
<td bgcolor="#f9f9f9" align="center" style="border: 1px solid rgb(170, 170, 170);" rowspan="2">{$this->home_score(57)}</td>
</tr>
<tr>
<td height="5"></td>
<td rowspan="2" colspan="2">#50 - {$this->venue(50)}</td>
</tr>
<tr>
<td height="5"></td>
<td style="border-style: solid; border-color: black; border-width: 2px 0pt 1px;" rowspan="12">&nbsp;</td>
<td bgcolor="#f9f9f9" style="border: 1px solid rgb(170, 170, 170);" rowspan="2">{$this->away_team(57)}</td>
<td bgcolor="#f9f9f9" align="center" style="border: 1px solid rgb(170, 170, 170);" rowspan="2">{$this->away_score(57)}</td>
<td style="border-style: solid; border-color: black; border-width: 2px 3px 1px 0pt;" rowspan="12">&nbsp;</td>
</tr>
<tr>
<td height="5"></td>
<td bgcolor="#f9f9f9" style="border: 1px solid rgb(170, 170, 170);" rowspan="2">{$this->home_team(50)}</td>
<td bgcolor="#f9f9f9" align="center" style="border: 1px solid rgb(170, 170, 170);" rowspan="2">{$this->home_score(50)}</td>
</tr>
<tr>
<td height="5"></td>
<td rowspan="6" colspan="2"></td>
<td rowspan="2" colspan="2">#61 - {$this->venue(61)}</td>
</tr>
<tr>
<td height="5"></td>
<td bgcolor="#f9f9f9" style="border: 1px solid rgb(170, 170, 170);" rowspan="2">{$this->away_team(50)}</td>
<td bgcolor="#f9f9f9" align="center" style="border: 1px solid rgb(170, 170, 170);" rowspan="2">{$this->away_score(50)}</td>
<td style="border-style: solid; border-color: black; border-width: 2px 0pt 1px;" rowspan="6">&nbsp;</td>
</tr>
<tr>
<td height="5"></td>
<td bgcolor="#f9f9f9" style="border: 1px solid rgb(170, 170, 170);" rowspan="2">{$this->home_team(61)}</td>
<td bgcolor="#f9f9f9" align="center" style="border: 1px solid rgb(170, 170, 170);" rowspan="2">{$this->home_score(61)}</td>
</tr>
<tr>
<td height="5"></td>
<td rowspan="2" colspan="2">#53 - {$this->venue(53)}</td>
</tr>
<tr>
<td height="5"></td>
<td style="border-style: solid; border-color: black; border-width: 2px 0pt 1px;" rowspan="24">&nbsp;</td>
<td bgcolor="#f9f9f9" style="border: 1px solid rgb(170, 170, 170);" rowspan="2">{$this->away_team(61)}</td>
<td bgcolor="#f9f9f9" align="center" style="border: 1px solid rgb(170, 170, 170);" rowspan="2">{$this->away_score(61)}</td>
<td style="border-style: solid; border-color: black; border-width: 2px 3px 1px 0pt;" rowspan="24">&nbsp;</td>
</tr>
<tr>
<td height="5"></td>
<td bgcolor="#f9f9f9" style="border: 1px solid rgb(170, 170, 170);" rowspan="2">{$this->home_team(53)}</td>
<td bgcolor="#f9f9f9" align="center" style="border: 1px solid rgb(170, 170, 170);" rowspan="2">{$this->home_score(53)}</td>
</tr>
<tr>
<td height="5"></td>
<td rowspan="2" colspan="2">#58 - {$this->venue(58)}</td>
<td rowspan="18" colspan="2"></td>
</tr>
<tr>
<td height="5"></td>
<td bgcolor="#f9f9f9" style="border: 1px solid rgb(170, 170, 170);" rowspan="2">&nbsp;{$this->away_team(53)}</td>
<td bgcolor="#f9f9f9" align="center" style="border: 1px solid rgb(170, 170, 170);" rowspan="2">&nbsp;{$this->away_score(53)}</td>
<td style="border-style: solid; border-color: black; border-width: 2px 3px 1px 0pt;" rowspan="6">&nbsp;</td>
</tr>
<tr>
<td height="5"></td>
<td bgcolor="#f9f9f9" style="border: 1px solid rgb(170, 170, 170);" rowspan="2">{$this->home_team(58)}</td>
<td bgcolor="#f9f9f9" align="center" style="border: 1px solid rgb(170, 170, 170);" rowspan="2">{$this->home_score(58)}</td>
</tr>
<tr>
<td height="5"></td>
<td rowspan="2" colspan="2">#54 - {$this->venue(54)}</td>
</tr>
<tr>
<td height="5"></td>
<td style="border-style: solid; border-color: black; border-width: 2px 0pt 1px;" rowspan="12">&nbsp;</td>
<td bgcolor="#f9f9f9" style="border: 1px solid rgb(170, 170, 170);" rowspan="2">{$this->away_team(58)}</td>
<td bgcolor="#f9f9f9" align="center" style="border: 1px solid rgb(170, 170, 170);" rowspan="2">{$this->away_score(58)}</td>
<td style="border-style: solid; border-color: black; border-width: 2px 0pt 1px;" rowspan="12">&nbsp;</td>
</tr>
<tr>
<td height="5"></td>
<td bgcolor="#f9f9f9" style="border: 1px solid rgb(170, 170, 170);" rowspan="2">{$this->home_team(54)}</td>
<td bgcolor="#f9f9f9" align="center" style="border: 1px solid rgb(170, 170, 170);" rowspan="2">&nbsp;{$this->home_score(54)}</td>
</tr>
<tr>
<td height="5"></td>
<td rowspan="6" colspan="2"></td>
<td rowspan="2" colspan="2">#64 - {$this->venue(64)}</td>
</tr>
<tr>
<td height="5"></td>
<td bgcolor="#f9f9f9" style="border: 1px solid rgb(170, 170, 170);" rowspan="2">{$this->away_team(54)}</td>
<td bgcolor="#f9f9f9" align="center" style="border: 1px solid rgb(170, 170, 170);" rowspan="2">{$this->away_score(54)}</td>
<td style="border-style: solid; border-color: black; border-width: 2px 0pt 1px;" rowspan="6">&nbsp;</td>
</tr>
<tr>
<td height="5"></td>
<td bgcolor="#f9f9f9" style="border: 1px solid rgb(170, 170, 170);" rowspan="2">{$this->home_team(64)}</td>
<td bgcolor="#f9f9f9" align="center" style="border: 1px solid rgb(170, 170, 170);" rowspan="2">{$this->home_score(64)}</td>
</tr>
<tr>
<td height="5"></td>
<td rowspan="2" colspan="2">#51 - {$this->venue(51)}</td>
</tr>
<tr>
<td height="5"></td>
<td style="border-style: solid; border-color: black; border-width: 2px 0pt 0pt;" rowspan="23">&nbsp;</td>
<td bgcolor="#f9f9f9" style="border: 1px solid rgb(170, 170, 170);" rowspan="2">{$this->away_team(64)}</td>
<td bgcolor="#f9f9f9" align="center" style="border: 1px solid rgb(170, 170, 170);" rowspan="2">{$this->away_score(64)}</td>
</tr>
<tr>
<td height="5"></td>
<td bgcolor="#f9f9f9" style="border: 1px solid rgb(170, 170, 170);" rowspan="2">{$this->home_team(51)}</td>
<td bgcolor="#f9f9f9" align="center" style="border: 1px solid rgb(170, 170, 170);" rowspan="2">{$this->home_score(51)}</td>
</tr>
<tr>
<td height="5"></td>
<td rowspan="2" colspan="2">#59 - {$this->venue(59)}</td>
<td rowspan="10" colspan="2"></td>
</tr>
<tr>
<td height="5"></td>
<td bgcolor="#f9f9f9" style="border: 1px solid rgb(170, 170, 170);" rowspan="2">{$this->away_team(51)}</td>
<td bgcolor="#f9f9f9" align="center" style="border: 1px solid rgb(170, 170, 170);" rowspan="2">{$this->away_score(51)}</td>
<td style="border-style: solid; border-color: black; border-width: 2px 3px 1px 0pt;" rowspan="6">&nbsp;</td>
</tr>
<tr>
<td height="5"></td>
<td bgcolor="#f9f9f9" style="border: 1px solid rgb(170, 170, 170);" rowspan="2">{$this->home_team(59)}</td>
<td bgcolor="#f9f9f9" align="center" style="border: 1px solid rgb(170, 170, 170);" rowspan="2">{$this->home_score(59)}</td>
</tr>
<tr>
<td height="5"></td>
<td rowspan="2" colspan="2">#52 - {$this->venue(52)}</td>
</tr>
<tr>
<td height="5"></td>
<td style="border-style: solid; border-color: black; border-width: 2px 0pt 1px;" rowspan="12">&nbsp;</td>
<td bgcolor="#f9f9f9" style="border: 1px solid rgb(170, 170, 170);" rowspan="2">{$this->away_team(59)}</td>
<td bgcolor="#f9f9f9" align="center" style="border: 1px solid rgb(170, 170, 170);" rowspan="2">{$this->away_score(59)}</td>
<td style="border-style: solid; border-color: black; border-width: 2px 3px 1px 0pt;" rowspan="12">&nbsp;</td>
</tr>
<tr>
<td height="5"></td>
<td bgcolor="#f9f9f9" style="border: 1px solid rgb(170, 170, 170);" rowspan="2">{$this->home_team(52)}</td>
<td bgcolor="#f9f9f9" align="center" style="border: 1px solid rgb(170, 170, 170);" rowspan="2">{$this->home_score(52)}</td>
</tr>
<tr>
<td height="5"></td>
<td rowspan="6" colspan="2"></td>
<td rowspan="2" colspan="2">#62 - {$this->venue(62)}</td>
</tr>
<tr>
<td height="5"></td>
<td bgcolor="#f9f9f9" style="border: 1px solid rgb(170, 170, 170);" rowspan="2">{$this->away_team(52)}</td>
<td bgcolor="#f9f9f9" align="center" style="border: 1px solid rgb(170, 170, 170);" rowspan="2">{$this->away_score(52)}</td>
<td style="border-style: solid; border-color: black; border-width: 2px 0pt 1px;" rowspan="6">&nbsp;</td>
</tr>
<tr>
<td height="5"></td>
<td bgcolor="#f9f9f9" style="border: 1px solid rgb(170, 170, 170);" rowspan="2">{$this->away_team(62)}</td>
<td bgcolor="#f9f9f9" align="center" style="border: 1px solid rgb(170, 170, 170);" rowspan="2">{$this->away_score(62)}</td>
</tr>
<tr>
<td height="5"></td>
<td rowspan="2" colspan="2">#55 - {$this->venue(55)}</td>
</tr>
<tr>
<td height="5"></td>
<td style="border-style: solid; border-color: black; border-width: 2px 0pt 0pt;" rowspan="11">&nbsp;</td>
<td bgcolor="#f9f9f9" style="border: 1px solid rgb(170, 170, 170);" rowspan="2">{$this->home_team(62)}</td>
<td bgcolor="#f9f9f9" align="center" style="border: 1px solid rgb(170, 170, 170);" rowspan="2">{$this->home_score(62)}</td>
<td style="border-style: solid; border-color: black; border-width: 2px 0pt 0pt;" rowspan="11">&nbsp;</td>
<td bgcolor="#f2f2f2" align="center" style="border: 1px solid rgb(170, 170, 170);" rowspan="2" colspan="2">{$this->stage(63)}</td>
</tr>
<tr>
<td height="5"></td>
<td bgcolor="#f9f9f9" style="border: 1px solid rgb(170, 170, 170);" rowspan="2">{$this->home_team(55)}</td>
<td bgcolor="#f9f9f9" align="center" style="border: 1px solid rgb(170, 170, 170);" rowspan="2">{$this->home_score(55)}</td>
</tr>
<tr>
<td height="5"></td>
<td rowspan="2" colspan="2">#60 - {$this->venue(60)}</td>
<td rowspan="9" colspan="2"></td>
<td rowspan="2" colspan="2">#63 - {$this->venue(63)}</td>
</tr>
<tr>
<td height="5"></td>
<td bgcolor="#f9f9f9" style="border: 1px solid rgb(170, 170, 170);" rowspan="2">{$this->away_team(55)}</td>
<td bgcolor="#f9f9f9" align="center" style="border: 1px solid rgb(170, 170, 170);" rowspan="2">{$this->away_score(55)}</td>
<td style="border-style: solid; border-color: black; border-width: 2px 3px 1px 0pt;" rowspan="6">&nbsp;</td>
</tr>
<tr>
<td height="5"></td>
<td bgcolor="#f9f9f9" style="border: 1px solid rgb(170, 170, 170);" rowspan="2">{$this->home_team(60)}</td>
<td bgcolor="#f9f9f9" align="center" style="border: 1px solid rgb(170, 170, 170);" rowspan="2">{$this->home_score(60)}</td>
<td bgcolor="#f9f9f9" style="border: 1px solid rgb(170, 170, 170);" rowspan="2">{$this->home_team(63)}</td>
<td bgcolor="#f9f9f9" align="center" style="border: 1px solid rgb(170, 170, 170);" rowspan="2">{$this->home_score(63)}</td>
</tr>
<tr>
<td height="5"></td>
<td rowspan="2" colspan="2">#56 - {$this->venue(56)}</td>
</tr>
<tr>
<td height="5"></td>
<td style="border-style: solid; border-color: black; border-width: 2px 0pt 0pt;" rowspan="5">&nbsp;</td>
<td bgcolor="#f9f9f9" style="border: 1px solid rgb(170, 170, 170);" rowspan="2">{$this->away_team(60)}</td>
<td bgcolor="#f9f9f9" align="center" style="border: 1px solid rgb(170, 170, 170);" rowspan="2">{$this->away_score(60)}</td>
<td style="border-style: solid; border-color: black; border-width: 2px 0pt 0pt;" rowspan="5">&nbsp;</td>
<td bgcolor="#f9f9f9" style="border: 1px solid rgb(170, 170, 170);" rowspan="2">{$this->away_team(63)}</td>
<td bgcolor="#f9f9f9" align="center" style="border: 1px solid rgb(170, 170, 170);" rowspan="2">{$this->away_score(63)}</td>
</tr>
<tr>
<td height="5"></td>
<td bgcolor="#f9f9f9" style="border: 1px solid rgb(170, 170, 170);" rowspan="2">{$this->home_team(56)}</td>
<td bgcolor="#f9f9f9" align="center" style="border: 1px solid rgb(170, 170, 170);" rowspan="2">{$this->home_score(56)}</td>
</tr>
<tr>
<td height="5"></td>
<td rowspan="3" colspan="2"></td>
<td rowspan="3" colspan="2"></td>
</tr>
<tr>
<td height="5"></td>
<td bgcolor="#f9f9f9" style="border: 1px solid rgb(170, 170, 170);" rowspan="2">{$this->away_team(56)}</td>
<td bgcolor="#f9f9f9" align="center" style="border: 1px solid rgb(170, 170, 170);" rowspan="2">{$this->away_score(56)}</td>
<td style="border-style: solid; border-color: black; border-width: 2px 0pt 0pt;" rowspan="2">&nbsp;</td>
</tr>
<tr>
<td height="5"></td>
</tr>
</tbody></table>

EOT;
		return $output;
	}
	
	/**
	 * Display a ranking table of all predictions for all matches
	 * 
	 * @param unknown_type $limit
	 * @param unknown_type $avatar
	 * @return unknown_type
	 */
	function user_ranking($limit = 999999, $avatar = false, $highlight = '') {
		global $wpdb;
		
		$output = '';
		$curr_user_id = -1;
		
		if (!empty($highlight) && is_user_logged_in()) {
			$current_user = wp_get_current_user();
                
			$curr_user_id = $current_user->ID;
		}
		
		
		$output = '';
		
		if (!is_numeric($limit)) {
			$limit = 999999;
		}
		
		$sql = "SELECT SUM(points) AS total, u.display_name, u.ID
				FROM
					{$wpdb->prefix}{$this->prefix}prediction p,
					{$wpdb->users} u
				WHERE
					p.user_id = u.ID
				GROUP BY
					user_id
				ORDER BY
					total DESC, user_registered ASC
				LIMIT %d";
					
		$result = $wpdb->get_results($wpdb->prepare($sql, $limit));
		$output .= "<table class='group zebra'><tr class='row-header'><th class='column-user'>".__('User', FP_PD)."</th><th class='column-goal'>".__('Points', FP_PD)."</th><th class='column-goal'>&nbsp;</th></tr>" . PHP_EOL;
		foreach ($result as $row) {
			if ($row->ID == $curr_user_id) {
				$style = "style=\"$highlight\"";
			} else {
				$style = 'class="fp-row"';
			}
			$output .= "<tr $style><td class='column-user'>".($avatar ? get_avatar( $row->ID, '16', '', $row->display_name ) : '').' '.$row->display_name."</td><td class='column-goal'>$row->total</td><td class='column-goal'>";
			
			if (get_option($this->prefix.'user_predictions', 1)) {
				$output .= "<a class='ranking-link' title='".__('Predictions', FP_PD)."' href=\"".get_option($this->prefix.'user_predictions')."&fp=predictions&user=".$row->ID."\"><img src='". WP_PLUGIN_URL."/".FP_PD ."/images/predictions.png' /></a>";
			}			
					
			$output .= "</td></tr>" . PHP_EOL;
		}
		$output .= "</table>" . PHP_EOL;
		
		return $output;
	}
	
	/**
	 * Show user scores for one match
	 */
	function score_match($match_id, $limit = 999999, $highlight = '', $curr_user_id, $filter_user, $avatar, $is_group, $is_result) {
		
		global $wpdb;
		
		$output = '';
		
		$sql = "SELECT points, u.display_name, u.ID, p.home_goals, p.away_goals, p.home_penalties, p.away_penalties
				FROM
					{$wpdb->prefix}{$this->prefix}match m,
					{$wpdb->prefix}{$this->prefix}prediction p,
					{$wpdb->users} u
				WHERE
					p.user_id = u.ID AND m.match_id = p.match_id AND m.match_id = %d $filter_user
				ORDER BY
					points DESC
				LIMIT %d";
					
		$result = $wpdb->get_results($wpdb->prepare($sql, $match_id, $limit));
		foreach ($result as $row) {
			$hpen = '';
			$apen = '';
			if (!$is_group && ($row->home_penalties > 0 || $row->away_penalties > 0)) { 
				$hpen = '('.$row->home_penalties . ')';
				$apen = '('.$row->away_penalties . ')';
			}
			if ($row->ID == $curr_user_id) {
				$style = "style=\"$highlight\"";
			} else {
				$style = 'class="fp-row"';
			}
			$output .= "<tr $style>";
			$output .= "<td class='column-user'>".($avatar ? get_avatar( $row->ID, '16', '', $row->display_name ) : '').' '.$row->display_name."</td>";
			$output .= "<td class='column-score'>$row->home_goals{$hpen}&ndash;$row->away_goals{$apen}</td>";
			if ($is_result) {
				$output .= "<td class='column-points'>$row->points</td>";
			} else {
				$output .= "<td class='column-points'>&ndash;</td>";
			}
			$output .= "</tr>";
		}
		
		return $output;
	}
	
	/**
	 * Display the guesses for each match and the points awarded
	 * 
	 * @param $match_id  (if -1 all matches)
	 * @param $limit
	 */
	function user_scores($match_id, $limit = 999999, $user_id = -1, $highlight = '', $show_all = 0) {
		
		global $wpdb;
		global $current_user;
		
		$output = '';
		$avatar = false;
		
		if (!is_numeric($limit)) {
			$limit = 999999;
		}
		
		$curr_user_id = -1;
		
		if (!empty($highlight) && is_user_logged_in()) {
			get_currentuserinfo();
			$curr_user_id = $current_user->ID;
		}
		
		$filter_user = '';
		if ($user_id != -1) $filter_user = " AND u.ID = $user_id ";

		// Show all matches after kickoff expired unless the option 'show predictions' is set
		$throttle = 'AND kickoff < UTC_TIMESTAMP()';
		$show_predictions = get_option($this->prefix.'show_predictions', 0);
		if ($show_predictions || $show_all) {
			$throttle = '';
		}		
		$sql = "SELECT m.match_id,
					h.country AS home_country, a.country AS away_country,
					h.team_url AS home_url, a.team_url AS away_url,
					h.name AS home_team_name, home_goals, home_penalties,
					a.name AS away_team_name, away_goals, away_penalties,
					is_group, extra_time, is_result
				FROM
					{$wpdb->prefix}{$this->prefix}match m,
					{$wpdb->prefix}{$this->prefix}stage s,
					{$wpdb->prefix}{$this->prefix}team h,
					{$wpdb->prefix}{$this->prefix}team a
				WHERE
					s.stage_id = m.stage_id AND (m.match_id = %d OR %d = -1) $throttle AND
					h.team_id = m.home_team_id AND a.team_id = m.away_team_id AND (m.is_result OR EXISTS
						(SELECT * FROM {$wpdb->prefix}{$this->prefix}prediction p WHERE p.match_id = m.match_id) )
				ORDER BY
					m.kickoff";
					
		$toprow = @$wpdb->get_results($wpdb->prepare($sql, $match_id, $match_id), OBJECT_K);
//		$this->debug($toprow);
		
		if ($toprow) {
			$output .= "<table class='group zebra'>";
			$num = count($toprow);
			$i = 1;
			foreach ($toprow as $key=>$row) {
				
				$output .= "<tr class='row-header'><th nowrap class='l'>".$this->team_name($row, true).' '. $this->team_score($row, true)."&ndash;".$this->team_score($row, false).' '. $this->team_name($row, false)."</th><th class='column-score'>".__('Prediction', FP_PD)."</th><th class='column-points'>".__('Pts', FP_PD)."</th></tr>";
	
				$output .= $this->score_match($key, $limit, $highlight, $curr_user_id, $filter_user, $avatar, $row->is_group, $row->is_result);
				
				if ($i < $num) {
					$output .= "<tr><td colspan='5'>&nbsp;</td></tr>" .PHP_EOL;
				}
				$i++;
			}
			$output .= "</table>";
		}
		
		return $output;
	}
	
	function show_scores($atts, $content = null) {
		
		extract(shortcode_atts(array(
			'match_id' => '', 
			'limit' => 999,
			'user_id' => -1,
			'highlight' => 'background:red;font-weight:bold'
		), $atts));
		
		global $wpdb;
	
		ob_start();
		$output = '';
		$output .= '<a class="predictions-link" href="javascript:history.go(-1)"><h6>'.__('Back', FP_PD).'</h6></a>' . PHP_EOL;
		$avatar = false;
		
		if (!is_numeric($limit)) {
			$limit = 999;
		}
		
		$curr_user_id = -1;
		
		if (!empty($highlight) && is_user_logged_in()) {
			$current_user = wp_get_current_user();
			$curr_user_id = $current_user->ID;
		}
		
		$filter_user = '';
		if ($user_id != -1) $filter_user = " AND r.user_id = $user_id ";
		
		// Show all matches after kickoff expired unless the option 'show predictions' is set
		$throttle = 'AND kickoff < UTC_TIMESTAMP()';
		$show_predictions = get_option($this->prefix.'show_predictions', 0);
		if ($show_predictions) {
			$throttle = '';
		}
		
		$sql = "SELECT m.match_id,
					h.country AS home_country, a.country AS away_country,
					h.team_url AS home_url, a.team_url AS away_url,
					h.name AS home_team_name, home_goals, home_penalties,
					a.name AS away_team_name, away_goals, away_penalties,
					is_group, extra_time, is_result
				FROM
					{$wpdb->prefix}{$this->prefix}match m,
					{$wpdb->prefix}{$this->prefix}stage s,
					{$wpdb->prefix}{$this->prefix}team h,
					{$wpdb->prefix}{$this->prefix}team a
				WHERE
					s.stage_id = m.stage_id AND (m.match_id = %d OR %d = -1) $throttle AND
					h.team_id = m.home_team_id AND a.team_id = m.away_team_id AND (m.is_result OR EXISTS
						(SELECT * FROM {$wpdb->prefix}{$this->prefix}prediction p WHERE p.match_id = m.match_id) )
				ORDER BY
					m.kickoff";
					
		$toprow = $wpdb->get_results($wpdb->prepare($sql, $match_id, $match_id), OBJECT_K);
		
		if ($toprow) {
			$output .= "<table class='group zebra'>";
			$num = count($toprow);
			$i = 1;
			foreach ($toprow as $key=>$row) {
				
				$output .= "<tr class='row-header'><th nowrap class='l'>".$this->team_name($row, true).' '. $this->team_score($row, true)."&ndash;".$this->team_score($row, false).' '. $this->team_name($row, false)."</th><th class='column-score'>".__('Prediction', FP_PD)."</th><th class='column-points'>".__('Pts', FP_PD)."</th></tr>";
	
				$output .= $this->score_match($key, $limit, $highlight, $curr_user_id, $filter_user, $avatar, $row->is_group, $row->is_result);
				
				if ($i < $num) {
					$output .= "<tr><td colspan='5'>&nbsp;</td></tr>" .PHP_EOL;
				}
				$i++;
			}
			$output .= "</table>";
		}
		
		$output .= '<a class="predictions-link" href="javascript:history.go(-1)"><h6>'.__('Back', FP_PD).'</h6></a>' . PHP_EOL;
		$output .= ob_get_contents();
		ob_end_clean();
		return $output;
	}	
	
	function user_predictions($user = 0, $show_total = 0, $show_result = 0) {
		
		global $wpdb;
		
		$output = '';
		
		/*
		 * Depending on request, return nice default for widget and shortcode if not logged in.
		 */
		if (!is_user_logged_in()) {
			if (!$user && $show_total) {
				return "0";
			}
			return "";
		}
		
		$current_user = wp_get_current_user();
		
		$total = 0;
		if ($show_total) {
			$sql = "SELECT COALESCE(SUM(points),0) AS total
					FROM
						{$wpdb->prefix}{$this->prefix}prediction p
					WHERE
						p.user_id = %d";
			$row = $wpdb->get_row( $wpdb->prepare($sql, $current_user->ID) , OBJECT );
			$total = $row->total;
		}
		
		/*
		 * Only want total score, not table.
		 */
		if (!$user) {
			return "$total";
		}
		
		$sql = "SELECT is_group,
			h.name AS home_team_name, a.name AS away_team_name, is_result,
			p.home_goals, p.away_goals, p.home_penalties, p.away_penalties,
			m.home_goals AS mhg, m.away_goals AS mag, m.home_penalties AS mhp, m.away_penalties AS map,
			h.country AS home_country, a.country AS away_country, points
		FROM 
			{$wpdb->prefix}{$this->prefix}match m,
			{$wpdb->prefix}{$this->prefix}prediction p,
			{$wpdb->prefix}{$this->prefix}stage s,
			{$wpdb->prefix}{$this->prefix}team h,
			{$wpdb->prefix}{$this->prefix}team a
		WHERE
			m.stage_id = s.stage_id AND p.match_id = m.match_id AND
			m.home_team_id = h.team_id AND m.away_team_id = a.team_id AND
			p.user_id = %d
		ORDER BY
			sort_order, kickoff";

		$result = $wpdb->get_results( $wpdb->prepare($sql, $current_user->ID) , OBJECT );
		$output .= '<table class="group zebra '.$this->prefix.'user_pred_widget">';
		$output .= "<tr class='row-header'>";
		$output .= "<th>".__('Match', FP_PD)."</th>";
		$output .= "<th class='column-score'>"._n( 'Prediction', 'Predictions', 1, FP_PD )."</th>";
		$output .= "<th class='column-points'>".__('Pts', FP_PD)."</th>";
		$output .= "</tr>";
		foreach ($result as $row) {
			$hpen = '';
			$apen = '';
			$match_result = '';
			if ($row->is_result && $show_result) {
				$mhpen = '';
				$mapen = '';
				if (!$row->is_group && ($row->mhp > 0 || $row->map > 0)) { 
					$mhpen = '('.$row->mhp . ')';
					$mapen = '('.$row->map . ')';
				}
				$match_result = "$row->mhg{$mhpen}&nbsp;&ndash;&nbsp;$row->mag{$mapen}";
			}
			if (!$row->is_group && ($row->home_penalties > 0 || $row->away_penalties > 0)) { 
				$hpen = '('.$row->home_penalties . ')';
				$apen = '('.$row->away_penalties . ')';
			}
			$output .= "<tr class='fp-row'>";
			$output .= "<td>".$this->unclean($row->home_team_name)." ".__('vs',FP_PD)." ".$this->unclean($row->away_team_name)." $match_result</td>";
			$output .= "<td class='column-score'>$row->home_goals{$hpen}&nbsp;&ndash;&nbsp;$row->away_goals{$apen}</td>";
			if ($row->is_result) {
				$output .= "<td class='column-points'>$row->points</td>";
			} else {
				$output .= "<td class='column-points'>&ndash;</td>";
			}
			$output .= "</tr>";
		}
		
		if ($show_total) {
			$output .= "<tr>";
			$output .= "<th colspan='2'>".__('Total', FP_PD)."</th>";
			$output .= "<th class='column-points'>$total</th>";
			$output .= "</tr>";
		}
		
		$output .= "</table>";
		
		return $output;
	}
	
	function show_user_predictions($atts, $content = null) {
		
		extract(shortcode_atts(array(
			'user' => '', 
			'show_total' => 1
		), $atts));
		
		global $wpdb;
                
		$current_user = wp_get_current_user();
                if( 0 == $current_user->ID ){
                    die('Error');
                }
		
		ob_start();
		$output = '';
		$output .= '<a class="predictions-link" href="javascript:history.go(-1)"><h6>'.__('Back', FP_PD).'</h6></a>' . PHP_EOL;
				
		$total = 0;
		if ($show_total) {
			$sql = "SELECT display_name, COALESCE(SUM(points),0) AS total
					FROM
						{$wpdb->prefix}{$this->prefix}prediction p,
						{$wpdb->users} u
					WHERE
						p.user_id = %d AND p.user_id = u.ID";
			
			$row = $wpdb->get_row( $wpdb->prepare($sql, $user) , OBJECT );
			$display_name = $row->display_name;
			$total = $row->total;
		}
		
		$throttle = 'AND kickoff < UTC_TIMESTAMP()';
		$show_predictions = get_option($this->prefix.'show_predictions', 0);
		if ($show_predictions) {
			$throttle = '';
		}
		
		$sql = "SELECT is_group,
			h.name AS home_team_name, a.name AS away_team_name, is_result,
			p.home_goals, p.away_goals, p.home_penalties, p.away_penalties,
			m.home_goals AS mhg, m.away_goals AS mag, m.home_penalties AS mhp, m.away_penalties AS map,
			h.country AS home_country, a.country AS away_country, points
		FROM 
			{$wpdb->prefix}{$this->prefix}match m,
			{$wpdb->prefix}{$this->prefix}prediction p,
			{$wpdb->prefix}{$this->prefix}stage s,
			{$wpdb->prefix}{$this->prefix}team h,
			{$wpdb->prefix}{$this->prefix}team a
		WHERE
			m.stage_id = s.stage_id AND p.match_id = m.match_id AND
			m.home_team_id = h.team_id AND m.away_team_id = a.team_id AND
			p.user_id = %d $throttle
		ORDER BY
			sort_order, kickoff";
		
		$result = $wpdb->get_results( $wpdb->prepare($sql, $user) , OBJECT );
		$output .= '<h6>' . $display_name . '</h6>';
		$output .= '<table class="group zebra '.$this->prefix.'user_pred_widget">';
		$output .= "<tr class='row-header'>";
		$output .= "<th>".__('Match', FP_PD)."</th>";
		$output .= "<th class='column-score'>"._n( 'Prediction', 'Predictions', 1, FP_PD )."</th>";
		$output .= "<th class='column-points'>".__('Pts', FP_PD)."</th>";
		$output .= "</tr>";
		foreach ($result as $row) {
			$hpen = '';
			$apen = '';
			$match_result = '';
			if ($row->is_result && $show_result) {
				$mhpen = '';
				$mapen = '';
				if (!$row->is_group && ($row->mhp > 0 || $row->map > 0)) { 
					$mhpen = '('.$row->mhp . ')';
					$mapen = '('.$row->map . ')';
				}
				$match_result = "$row->mhg{$mhpen}&nbsp;&ndash;&nbsp;$row->mag{$mapen}";
			}
			if (!$row->is_group && ($row->home_penalties > 0 || $row->away_penalties > 0)) { 
				$hpen = '('.$row->home_penalties . ')';
				$apen = '('.$row->away_penalties . ')';
			}
			$output .= "<tr class='fp-row'>";
			$output .= "<td>".$this->unclean($row->home_team_name)." ".__('vs',FP_PD)." ".$this->unclean($row->away_team_name)." $match_result</td>";
			$output .= "<td class='column-score'>$row->home_goals{$hpen}&nbsp;&ndash;&nbsp;$row->away_goals{$apen}</td>";
			if ($row->is_result) {
				$output .= "<td class='column-points'>$row->points</td>";
			} else {
				$output .= "<td class='column-points'>&ndash;</td>";
			}
			$output .= "</tr>";
		}
		
		if ($show_total) {
			$output .= "<tr>";
			$output .= "<th colspan='2'>".__('Total', FP_PD)."</th>";
			$output .= "<th class='column-points'>$total</th>";
			$output .= "</tr>";
		}
		
		$output .= "</table>";
		
		$output .= '<a class="predictions-link" href="javascript:history.go(-1)"><h6>'.__('Back', FP_PD).'</h6></a>' . PHP_EOL;
		
		$output .= ob_get_contents();
		ob_end_clean();
		return $output;
	}	
}

?>