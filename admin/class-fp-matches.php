<?php
/**
 * @package Football
 * @author bigtonni
 * 
 * Match Administration functions for the Football plugin.
 * 
 */

class FootballMatches extends FootballAdmin {
	
	var $tab;
	
	/**
	 * Constructor
	 */
	function __construct($tab) {
		$this->tab = $tab;
		parent::__construct();
	}
	
	/**
	 * Display and manage matches
	 */
	function matches() {
		
		global $wpdb;
		
		$match_no = 0;
		$kickoff = '';
		$home_team_id = -1;
		$away_team_id = -1;
		$home_goals = 0;
		$away_goals = 0;
		$home_penalties = 0;
		$away_penalties = 0;
		$extra_time = 0;
		$venue_id = -1;
		$stage_id = -1;
		$match_id = -1;
		$is_result = 0;
		
		if (isset($_POST[$this->prefix.'modifyMatchCancel'])) {
			check_admin_referer($this->prefix . 'match-form');
			$this->selectTab($this->tab);
		}
		
		if (isset($_POST[$this->prefix.'addMatch'])) {
			check_admin_referer($this->prefix . 'match-form');
			
			extract($_POST, EXTR_IF_EXISTS);
			
			// Save to database
			if ($this->insert($match_no, $kickoff, $home_team_id, $away_team_id, $home_goals, $away_goals, $venue_id, $stage_id, $is_result, $home_penalties, $away_penalties, $extra_time) !== false) {
				$match_no = 0;
				$kickoff = '';
				$home_team_id = -1;
				$away_team_id = -1;
				$home_goals = 0;
				$away_goals = 0;
				$home_penalties = 0;
				$away_penalties = 0;
				$extra_time = 0;
				$venue_id = -1;
				$stage_id = -1;
				$is_result = 0;
				delete_option($this->prefix.'group_stats');  // Clear cache
				$this->setMessage(__('Changes saved', FP_PD));
			}
			$this->selectTab($this->tab);
		}
		
		/**
		 * Actually modify the result.
		 */
		if (isset($_POST[$this->prefix.'modifyMatch'])) {
			check_admin_referer($this->prefix . 'match-form');
			
			extract($_POST, EXTR_IF_EXISTS);
			
			if ($this->update($match_id, $match_no, $kickoff, $home_team_id, $away_team_id, $home_goals, $away_goals, $venue_id, $stage_id, $is_result, $home_penalties, $away_penalties, $extra_time) !== false) {
				$match_no = 0;
				$kickoff = '';
				$home_team_id = -1;
				$away_team_id = -1;
				$home_goals = 0;
				$away_goals = 0;
				$home_penalties = 0;
				$away_penalties = 0;
				$extra_time = 0;
				$venue_id = -1;
				$stage_id = -1;
				$is_result = 0;
				$match_id = -1;
				delete_option($this->prefix.'group_stats');  // Clear cache
				$this->setMessage(__('Changes saved and prediction points updated', FP_PD));
			}
			$this->selectTab($this->tab);
		}
		
		/**
		 * Process GET request to retreive the match details and pre-fill
		 * the form.
		 */
		if (isset($_GET['modifymatch_id'])) {
			$match_id = sanitize_text_field($_GET['modifymatch_id']);
			$row = $this->get($match_id);
			if (empty($row)) $match_id = -1;	// Didn't find row. Prevent modification
			extract($row, EXTR_IF_EXISTS);
			$this->selectTab($this->tab);
		}
		
		if (isset($_POST[$this->prefix.'deleteMatch'])) {
			check_admin_referer($this->prefix . 'list-matches');
			if (isset($_POST['match_id'])) {
				foreach ($_POST['match_id'] as $id) {
					$this->delete( int($id) );
				}
				delete_option($this->prefix.'group_stats');  // Clear cache
				$this->setMessage(__('Changes saved', FP_PD));
			}
			$this->selectTab($this->tab);
		}
?>
	<div class="wrap">
		
		<h2><?php _e('Manage matches', FP_PD) ?></h2>
		
		<p><?php _e('Once the match has finished edit the match, enter the score and check "Match Finished".', FP_PD); ?></p>
		
		<?php $this->printMessage(); ?>
		
		<form class="form-table <?php echo $this->prefix; ?>form" name="match" action="<?php echo $_SERVER['PHP_SELF'] ?>?page=<?php echo $this->prefix; ?>config" method="post">
			
			<?php wp_nonce_field( $this->prefix . 'match-form' ) ?>
			
			<p><a href="http://www.fifa.com/worldcup/matches/index.html" target="_blank">http://www.fifa.com/worldcup/matches/index.html</a></p>
			
			<table>
				<tr valign="top">
					<td scope="fp-row"><label for="match_no"><?php _e( 'Match#', FP_PD ) ?></label></td>
					<td><input type="text" name="match_no" value="<?php echo $match_no;?>" size="4" /></td>
				</tr>
				<tr valign="top">
					<td scope="fp-row"><label for="venue_id"><?php _e( 'Venue', FP_PD ) ?></label></td>
					<td><?php $venues = new FootballVenues(1); echo $venues->getVenues($venue_id, true, 'venue_id', __('Select Venue', FP_PD)); ?></td>
				</tr>
				<tr valign="top">
					<td scope="fp-row"><label for="stage_id"><?php _e( 'Stage', FP_PD ) ?></label></td>
					<td><?php $stages = new FootballStages(2); echo $stages->getStages($stage_id, true, 'stage_id', __('Select Stage', FP_PD)); ?></td>
				</tr>
				<tr valign="top">
					<td scope="fp-row"><label for="kickoff"><?php _e( 'Kickoff date time<br/>Must be <strong>UTC</strong>', FP_PD ) ?></label></td>
					<td><input type="text" name="kickoff" value="<?php echo $kickoff;?>" size="25" /> YYYY-MM-DD HH:MM:SS</td>
				</tr>
                                <?php
                                $teams = new FootballTeams(0);
                                ?>
				<tr valign="top">
					<td scope="fp-row"><label for="home_team_id"><?php _e( 'Team A', FP_PD ) ?></label></td>
					<td><?php echo $teams->getTeams($home_team_id, true, 'home_team_id', __('Select Team', FP_PD)); ?>
						<label for="home_goals"><?php _e( 'Goals A', FP_PD ) ?></label>
						<input type="text" name="home_goals" value="<?php echo $home_goals;?>" size="4" />
						<label for="home_penalties"><?php _e( 'Penalties A', FP_PD ) ?></label>
						<input type="text" name="home_penalties" value="<?php echo $home_penalties;?>" size="4" />
					</td>
				</tr>
				<tr valign="top">
					<td scope="fp-row"><label for="away_team_id"><?php _e( 'Team B', FP_PD ) ?></label></td>
					<td><?php echo $teams->getTeams($away_team_id, true, 'away_team_id', __('Select Team', FP_PD)); ?>
						<label for="away_goals"><?php _e( 'Goals B', FP_PD ) ?></label>
						<input type="text" name="away_goals" value="<?php echo $away_goals;?>" size="4" />
						<label for="away_penalties"><?php _e( 'Penalties B', FP_PD ) ?></label>
						<input type="text" name="away_penalties" value="<?php echo $away_penalties;?>" size="4" />
					</td>
				</tr>
				<tr valign="top">
					<td scope="fp-row"><label for="extra_time"><?php _e( 'Extra Time', FP_PD ) ?></label></td>
					<td><input <?php echo ($extra_time ? 'checked' : ''); ?> type="checkbox" name="extra_time" value="1" /></td>
				</tr>
				<tr valign="top">
					<td scope="fp-row"><label for="is_result"><?php _e( 'Match finished', FP_PD ) ?></label></td>
					<td><input <?php echo ($is_result ? 'checked' : ''); ?> type="checkbox" name="is_result" value="1" /></td>
				</tr>
			</table>
<?php 
			if  ($match_id != -1) {
?>
			<input type="hidden" value="<?php echo $match_id; ?>" name="match_id"></input>
			<p class="submit">
				<input type="submit" name="<?php echo $this->prefix;?>modifyMatch" value="<?php _e( 'Modify Match', FP_PD ) ?>" class="button-primary" />
				<input type="submit" name="<?php echo $this->prefix;?>modifyMatchCancel" value="<?php _e( 'Cancel', FP_PD ) ?>" class="button" />
			</p>
<?php 
			} else {
?>
			<p class="submit">
				<input type="submit" name="<?php echo $this->prefix;?>addMatch" value="<?php _e( 'Add Match', FP_PD ) ?>" class="button-primary" />
			</p>
<?php 
			}
?>
		</form>
		
<?php 
		/**
		 * Show the current match list in a table
		 */
		$sql = "SELECT match_id, match_no, kickoff AS utc_kickoff, h.name AS home_team_name, a.name AS away_team_name,
					home_goals, away_goals, home_penalties, away_penalties, extra_time, venue_name, stage_name, is_result, is_group, m.wwhen
				FROM 
					{$wpdb->prefix}{$this->prefix}match m,
					{$wpdb->prefix}{$this->prefix}venue v,
					{$wpdb->prefix}{$this->prefix}stage s,
					{$wpdb->prefix}{$this->prefix}team h,
					{$wpdb->prefix}{$this->prefix}team a
				WHERE
					m.venue_id = v.venue_id AND m.stage_id = s.stage_id AND
					m.home_team_id = h.team_id AND m.away_team_id = a.team_id
				ORDER BY
					sort_order, match_no, kickoff";
					
		$result = $wpdb->get_results( $sql , OBJECT );
?>		
		<form name="listmatches" method="post" action="<?php echo $_SERVER['PHP_SELF'] ?>?page=<?php echo $this->prefix; ?>config">
			<?php wp_nonce_field( $this->prefix . 'list-matches' ) ?>
			<table class="<?php echo $this->prefix; ?>table" width="90%">
				<thead>
					<tr>
						<th scope="column"><?php _e('Del', FP_PD) ?></th>
						<th scope="column"><?php _e('ID', FP_PD) ?></th>
						<th scope="column"><?php _e('#', FP_PD) ?></th>
						<th scope="column"><?php _e('Venue', FP_PD) ?></th>
						<th scope="column"><?php _e('Stage', FP_PD) ?></th>
						<th scope="column"><?php _e('Kickoff', FP_PD) ?></th>
						<th scope="column"><?php _e('Res', FP_PD) ?></th>
						<th scope="column"><?php _e('Team', FP_PD) ?></th>
						<th scope="column"><?php _e('A', FP_PD) ?></th>
						<th scope="column">&nbsp;</th>
						<th scope="column"><?php _e('B', FP_PD) ?></th>
						<th scope="column"><?php _e('Team', FP_PD) ?></th>
						<th scope="column"><?php _e('E.T.', FP_PD) ?></th>
						<th scope="column"><?php _e('Last Modified', FP_PD) ?></th>
					</tr>
				</thead>
				<tbody>
<?php
				foreach ($result as $row) {
?>
					<tr>
						<td><input type="checkbox" value="<?php echo $row->match_id; ?>" name ="match_id[<?php echo $row->match_id;?>]"/></td>
						<td><a title="<?php _e('Modify this match', FP_PD); ?>" 
					   href="<?php echo $_SERVER['PHP_SELF'] ?>?page=<?php echo $this->prefix; ?>config&amp;modifymatch_id=<?php echo $row->match_id; ?>"><?php echo $row->match_id; ?></a></td>
						<td><?php echo $this->unclean($row->match_no); ?></td>
						<td><?php echo $this->unclean($row->venue_name); ?></td>
						<td><?php echo $this->unclean($row->stage_name); ?></td>
						<td><?php echo $row->utc_kickoff; ?></td>
						<td><input <?php echo ($row->is_result ? 'checked' : ''); ?> type="checkbox" disabled /></td>
						<td><?php echo $this->unclean($row->home_team_name); ?></td>
						<td style="text-align:right"><?php echo $this->unclean($row->home_goals); 
						if (!$row->is_group && ($row->home_penalties > 0 || $row->away_penalties > 0)) { 
							echo ' ('.$this->unclean($row->home_penalties) . ')';
						}
						?></td>
						<td>&ndash;</td>
						<td><?php echo $this->unclean($row->away_goals);
						if (!$row->is_group && ($row->home_penalties > 0 || $row->away_penalties > 0)) { 
							echo ' ('.$this->unclean($row->away_penalties) . ')';
						}
						?></td>
						<td><?php echo $this->unclean($row->away_team_name); ?></td>
						<td><input <?php echo ($row->extra_time ? 'checked' : ''); ?> type="checkbox" disabled /></td>
						<td><?php echo $row->wwhen; ?></td>
					</tr>
<?php
				}
?>
				</tbody>
			</table>
			<p>
				<input type="submit" name="<?php echo $this->prefix; ?>deleteMatch" value="<?php _e( 'Delete Selected', FP_PD ); ?>" class="button" />
			</p>
		</form>
	</div>
<?php
	}
	
	/**
	 * Check valid input
	 */
	private function valid($match_no, $kickoff, $home_team_id, $away_team_id, $home_goals, $away_goals, $venue_id, $stage_id, $home_penalties, $away_penalties, $extra_time) {
		
		if (!is_numeric($match_no) || !is_numeric($home_goals) || !is_numeric($away_goals)) {
			$this->setMessage(__("Match#, and goals must be numeric", FP_PD), true);
			return false;
		}
		
		if (!is_numeric($home_penalties) || !is_numeric($away_penalties)) {
			$this->setMessage(__("Penalties must be numeric", FP_PD), true);
			return false;
		}
		
		if ($home_team_id == -1 || $away_team_id == -1 || $home_team_id == $away_team_id) {
			$this->setMessage(__("Select two different teams", FP_PD), true);
			return false;
		}
		
		if ($venue_id == -1 || $stage_id == -1) {
			$this->setMessage(__("Select a venue and stage", FP_PD), true);
			return false;
		}
		
		if (!$this->is_datetime($kickoff)) {
			$this->setMessage(__("Kickoff must be valid YYYY-MM-DD HH:MM:SS date time format.", FP_PD), true);
			return false;
		}
		
		return true;
	}
	
	/**
	 * Insert row
	 */
	private function insert($match_no, $kickoff, $home_team_id, $away_team_id, $home_goals, $away_goals, $venue_id, $stage_id, $is_result, $home_penalties, $away_penalties, $extra_time) {
		
		global $wpdb;
		
		if (!$this->valid($match_no, $kickoff, $home_team_id, $away_team_id, $home_goals, $away_goals, $venue_id, $stage_id, $home_penalties, $away_penalties, $extra_time)) {
			return false;
		}
		
		$sql = "INSERT INTO {$wpdb->prefix}{$this->prefix}match (match_no, kickoff, home_team_id, away_team_id, home_goals, away_goals, venue_id, stage_id, is_result, home_penalties, away_penalties, extra_time)
				VALUES (%d, %s, %d, %d, %d, %d, %d, %d, %d, %d, %d, %d)";
		
		$ret = $wpdb->query( $wpdb->prepare( $sql, $match_no, $kickoff, $home_team_id, $away_team_id, $home_goals, $away_goals, $venue_id, $stage_id, $is_result, $home_penalties, $away_penalties, $extra_time) );
		
		if ($ret == 1) {
			return $wpdb->insert_id;
		} else {
			return false;
		}
	}
	
	/**
	 * Update row
	 */
	private function update($match_id, $match_no, $kickoff, $home_team_id, $away_team_id, $home_goals, $away_goals, $venue_id, $stage_id, $is_result, $home_penalties, $away_penalties, $extra_time) {
		
		global $wpdb;
		
		if (!$this->valid($match_no, $kickoff, $home_team_id, $away_team_id, $home_goals, $away_goals, $venue_id, $stage_id, $home_penalties, $away_penalties, $extra_time)) {
			return false;
		}
		
		$sql = "UPDATE {$wpdb->prefix}{$this->prefix}match
				SET
					match_no = %d,
					kickoff = %s,
					home_team_id = %d,
					away_team_id = %d,
					home_goals = %d,
					away_goals = %d,
					venue_id = %d,
					stage_id = %d,
					is_result = %d,
					home_penalties = %d,
					away_penalties = %d,
					extra_time = %d,
					scored = 0
				WHERE match_id = %d";
		
		$ret = $wpdb->query( $wpdb->prepare( $sql, $match_no, $kickoff, $home_team_id, $away_team_id, $home_goals, $away_goals, $venue_id, $stage_id, $is_result, $home_penalties, $away_penalties, $extra_time, $match_id ) );
		if ($ret) {
			require_once FP_ABSPATH . 'admin/class-fp-scoring.php';
			$scoring = new FootballScoring();
			$scoring->calculate_scores((int)$match_id);
		}
	}
	
	/**
	 * Get row by id.
	 */
	private function get($match_id) {
		
		global $wpdb;
		
		$sql = "SELECT match_no, kickoff, home_team_id, away_team_id, home_goals, away_goals, venue_id, stage_id, is_result, home_penalties, away_penalties, extra_time
				FROM {$wpdb->prefix}{$this->prefix}match WHERE match_id = %d";
		
		$row = $wpdb->get_row( $wpdb->prepare($sql, $match_id) , ARRAY_A );
		
		if (!is_null($row)) {
			foreach ($row as $key=>$r) {
				$row[$key] = $this->unclean($r);
			}
		}
		
		return ($row ? $row : array());
	}
	
	/**
	 * Delete row
	 */
	private function delete($match_id) {
		
		global $wpdb;
		
		$sql = "SELECT COUNT(*) FROM {$wpdb->prefix}{$this->prefix}prediction WHERE match_id = %d";
		$count = $wpdb->get_var($wpdb->prepare($sql, $match_id));
		if ($count) {
			$this->setMessage(__('Can not delete a match with predictions', EURO_TD), true);
			return false;
		}		
		
		$sql = "DELETE FROM {$wpdb->prefix}{$this->prefix}match WHERE match_id = %d";
		
		$wpdb->query( $wpdb->prepare( $sql, $match_id ) );
	}
	
	/**
	 * Get a list of matches in a dropdown select box
	 * 
	 * @param $match_id - Preselect this match
	 */
	function getMatches($match_id, $empty = true, $id = 'match_id', $empty_str = 'Select match', $is_result = 0) {
		
		global $wpdb;
		
		$filter_result = '';
		if ($is_result) $filter_result = ' AND (m.is_result = 1 OR m.kickoff < UTC_TIMESTAMP())';
		
		$sql = "SELECT match_id, h.name AS home_team_name, a.name AS away_team_name,
						s.stage_name
				FROM
					{$wpdb->prefix}{$this->prefix}match m,
					{$wpdb->prefix}{$this->prefix}team h,
					{$wpdb->prefix}{$this->prefix}team a,
					{$wpdb->prefix}{$this->prefix}stage s
				WHERE
					s.stage_id = m.stage_id AND
					m.home_team_id = h.team_id AND
					m.away_team_id = a.team_id
					$filter_result
				ORDER BY
					sort_order, kickoff";
		
		$result = $wpdb->get_results( $sql );
		
		$output = '<select name="'.$id.'" id="'.$id.'">';
		if ($empty) $output .= '<option value = "-1">'.$empty_str.'</option>';
		
		foreach ($result as $row) {
			$output .= "<option ";
			if (!is_null($id) && $match_id == $row->match_id) {
				$output .= " selected ";
			}
			$output .= "value=\"$row->match_id\">".$this->unclean($row->stage_name).' - '.$this->unclean($row->home_team_name).' vs. '.$this->unclean($row->away_team_name)."</option>";
		}
		$output .= "</select>";
		
		return $output;
	}
}

?>