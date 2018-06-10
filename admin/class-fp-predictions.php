<?php
/**
 * @package Football
 * 
 * Prediction Administration functions for the Football plugin.
 * 
 */
 
class FootballPredictions extends FootballAdmin {
	
	var $tab;
	
	/*
	 * Constructor
	 */
	function __construct($tab) {
		$this->tab = $tab;
		parent::__construct();
	}
	
	/*
	 * Display and manage predictions
	 */
	function predictions() {
		
		global $wpdb;
		
		$user_id = $match_id = -1;
		$home_goals = $away_goals = $points = $home_penalties = $away_penalties = 0;
		$wwhen = '2018-01-01 12:00:00';
		$prediction_id = -1;
		$filter_stage_id = -1;
		$filter_user_id = -1;
		$filter_team_id = -1;
		$filter_match_type = -1;
		
		if (isset($_POST[$this->prefix.'modifyPredictionCancel'])) {
			check_admin_referer($this->prefix . 'prediction-form');
			$this->selectTab($this->tab);
		}
		
		if (isset($_POST[$this->prefix.'filterPrediction'])) {
			check_admin_referer($this->prefix . 'prediction-form');
			extract($_POST, EXTR_IF_EXISTS);
			$this->selectTab($this->tab);
		}
		
		if (isset($_POST[$this->prefix.'addPrediction'])) {
			check_admin_referer($this->prefix . 'prediction-form');
			
			extract($_POST, EXTR_IF_EXISTS);
			
			// Save to database
			if ($this->insert($user_id, $match_id, $home_goals, $away_goals, $home_penalties, $away_penalties, $points, $wwhen) !== false) {
				$user_id = $match_id = -1;
				$home_goals = $away_goals = $home_penalties = $away_penalties = $points = 0;
				$wwhen = '2010-01-01 12:00:00';
				$this->setMessage(__('Changes saved', FP_PD));
			}
			$this->selectTab($this->tab);
		}
		
		/*
		 * Actually modify the result.
		 */
		if (isset($_POST[$this->prefix.'modifyPrediction'])) {
			check_admin_referer($this->prefix . 'prediction-form');
			
			extract($_POST, EXTR_IF_EXISTS);
			
			if ($this->update($prediction_id, $user_id, $match_id, $home_goals, $away_goals, $home_penalties, $away_penalties, $points, $wwhen) !== false) {
				$user_id = $match_id = -1;
				$home_goals = $away_goals = $home_penalties = $away_penalties = $points = 0;
				$wwhen = '2010-01-01 12:00:00';
				$prediction_id = -1;
				$this->setMessage(__('Changes saved', FP_PD));
			}
			$this->selectTab($this->tab);
		}
		
		/*
		 * Process GET request to retreive the prediction details and pre-fill
		 * the form.
		 */
		if (isset($_GET['modifyprediction_id'])) {
			$prediction_id = sanitize_text_field($_GET['modifyprediction_id']);
			$row = $this->get($prediction_id);
			if (empty($row)) $prediction_id = -1;	// Didn't find row. Prevent modification
			extract($row, EXTR_IF_EXISTS);
			$this->selectTab($this->tab);
		}
		
		if (isset($_POST[$this->prefix.'deletePrediction'])) {
			check_admin_referer($this->prefix . 'list-predictions');
			if (isset($_POST['prediction_id'])) {
				foreach ($_POST['prediction_id'] as $id) {
					$this->delete( int($id) );
				}
				$this->setMessage(__('Changes saved', FP_PD));
			}
			$this->selectTab($this->tab);
		}
?>
		<div class="wrap">
			
			<h2><?php _e('Manage predictions', FP_PD) ?></h2>
			
			<p><?php _e('For a manually entered prediction the prediction time must be before the kickoff time.', FP_PD); ?></p>
			
			<?php $this->printMessage(); ?>
			
			<form class="form-table <?php echo $this->prefix; ?>form" name="prediction" action="<?php echo $_SERVER['PHP_SELF'] ?>?page=<?php echo $this->prefix; ?>predictions" method="post">
				
				<?php wp_nonce_field( $this->prefix . 'prediction-form' ) ?>
				
				<table>
					<tr valign="top">
						<td scope="fp-row"><label for="user_id"><?php _e( 'User', FP_PD ) ?></label></td>
						<td><?php echo $this->getUsers($user_id, true, 'user_id', __('Select user', FP_PD)); ?></td>
					</tr>
					<tr valign="top">
						<td scope="fp-row"><label for="match_id"><?php _e( 'Match', FP_PD ) ?></label></td>
						<td><?php $matches = new FootballMatches(3); echo $matches->getMatches($match_id, true, 'match_id', __('Select match', FP_PD)); ?></td>
					</tr>
					<tr valign="top">
						<td scope="fp-row"><label for="home_goals"><?php _e( 'Goals A', FP_PD ) ?></label></td>
						<td>
							<input type="text" name="home_goals" value="<?php echo $home_goals;?>" size="4" />
							<label for="home_penalties"><?php _e( 'Penalties A', FP_PD ) ?></label>
							<input type="text" name="home_penalties" value="<?php echo $home_penalties;?>" size="4" />
						</td>
					</tr>
					<tr valign="top">
						<td scope="fp-row"><label for="away_goals"><?php _e( 'Goals B', FP_PD ) ?></label></td>
						<td>
							<input type="text" name="away_goals" value="<?php echo $away_goals;?>" size="4" />
							<label for="away_penalties"><?php _e( 'Penalties B', FP_PD ) ?></label>
							<input type="text" name="away_penalties" value="<?php echo $away_penalties;?>" size="4" />
						</td>
					</tr>
					<tr valign="top">
						<td scope="fp-row"><label for="points"><?php _e( 'Points', FP_PD ) ?></label></td>
						<td><input type="text" name="points" value="<?php echo $points;?>" size="4" /></td>
					</tr>
					<tr valign="top">
						<td scope="fp-row"><label for="wwhen"><?php _e( 'Prediction time', FP_PD ) ?></label></td>
						<td><input type="text" name="wwhen" value="<?php echo $wwhen;?>" size="20" /></td>
					</tr>
				</table>
				
				<p class="submit">
					<?php echo $this->getUsers($filter_user_id, true, 'filter_user_id', __('All Users', FP_PD)); ?>
					<?php $stages = new FootballStages(2); echo $stages->getStages($filter_stage_id, true, 'filter_stage_id', __('All Stages', FP_PD)); ?>
					<?php $teams = new FootballTeams(0); echo $teams->getTeams($filter_team_id, true, 'filter_team_id', __('All Teams', FP_PD)); ?>
					<?php echo $this->match_types($filter_match_type); ?>
					<input type="submit" name="<?php echo $this->prefix;?>filterPrediction" value="<?php _e( 'Filter', FP_PD ) ?>" class="button" />
				</p>
<?php 
			if  ($prediction_id != -1) {
?>
				<input type="hidden" value="<?php echo $prediction_id; ?>" name="prediction_id"></input>
				<p class="submit">
					<input type="submit" name="<?php echo $this->prefix;?>modifyPrediction" value="<?php _e( 'Modify Prediction', FP_PD ) ?>" class="button-primary" />
					<input type="submit" name="<?php echo $this->prefix;?>modifyPredictionCancel" value="<?php _e( 'Cancel', FP_PD ) ?>" class="button" />
				</p>
<?php 
			} else {
?>
				<p class="submit"><input type="submit" name="<?php echo $this->prefix;?>addPrediction" value="<?php _e( 'Add Prediction', FP_PD ) ?>" class="button-primary" /></p>
<?php 
			}
?>
			</form>
<?php 
		$user_filter = $stage_filter = $team_filter = $match_filter = '';
		if ($filter_user_id != -1) $user_filter = ' AND u.ID = ' . $filter_user_id;
		if ($filter_stage_id != -1) $stage_filter = ' AND s.stage_id = ' . $filter_stage_id;
		if ($filter_team_id != -1) $team_filter = ' AND (h.team_id = ' . $filter_team_id . ' OR a.team_id = ' . $filter_team_id . ')';
		if ($filter_match_type == 1) $match_filter = ' AND m.is_result = 1';
		if ($filter_match_type == 0) $match_filter = ' AND m.is_result = 0';
		
		/**
		 * Show the current prediction list in a table
		 */
		$sql = "SELECT prediction_id, u.display_name, match_no, 
					h.name AS home_team_name, a.name AS away_team_name, s.stage_name,
					p.home_goals, p.away_goals, p.home_penalties, p.away_penalties, p.wwhen, p.points, is_group
				FROM 
					{$wpdb->prefix}{$this->prefix}prediction p,
					{$wpdb->prefix}{$this->prefix}match m,
					{$wpdb->prefix}{$this->prefix}team h,
					{$wpdb->prefix}{$this->prefix}team a,
					{$wpdb->prefix}{$this->prefix}stage s,
					{$wpdb->users} u
				WHERE
					p.match_id = m.match_id AND u.ID = p.user_id AND
					m.home_team_id = h.team_id AND m.away_team_id = a.team_id AND
					s.stage_id = m.stage_id
					$user_filter $stage_filter $team_filter $match_filter
				ORDER BY
					u.display_name, s.sort_order, m.kickoff
				LIMIT 999";

					
		$result = $wpdb->get_results( $sql , OBJECT );

?>		
			<p><strong><?php _e('All times are UTC', FP_PD); ?></strong></p>
			<form id="listpredictions" name="listpredictions" method="post" action="<?php echo $_SERVER['PHP_SELF'] ?>?page=<?php echo $this->prefix; ?>predictions">
			
				<?php wp_nonce_field( $this->prefix . 'list-predictions' ) ?>
				
				<table class="<?php echo $this->prefix; ?>table" width="90%">
					<thead>
						<tr>
							<th scope="column"><input type="checkbox" value="" id="selectallprediction"/> <?php _e('Del', FP_PD) ?></th>
							<th scope="column"><?php _e('ID', FP_PD) ?></th>
							<th scope="column"><?php _e('User', FP_PD) ?></th>
							<th scope="column"><?php _e('Points', FP_PD) ?></th>
							<th scope="column"><?php _e('#', FP_PD) ?></th>
							<th scope="column"><?php _e('Stage', FP_PD) ?></th>
							<th scope="column"><?php _e('Team', FP_PD) ?></th>
							<th scope="column"><?php _e('A', FP_PD) ?></th>
							<th scope="column">&nbsp;</th>
							<th scope="column"><?php _e('B', FP_PD) ?></th>
							<th scope="column"><?php _e('Team', FP_PD) ?></th>
							<th scope="column"><?php _e('Last Modified', FP_PD) ?></th>
						</tr>
					</thead>
					<tbody>
<?php
					foreach ($result as $row) {
?>
						<tr>
							<td>
								<input type="checkbox" value="<?php echo $row->prediction_id; ?>" name ="prediction_id[<?php echo $row->prediction_id;?>]"/>
							</td>
							<td>
								<a title="<?php _e('Modify this prediction', FP_PD); ?>" href="<?php echo $_SERVER['PHP_SELF'] ?>?page=<?php echo $this->prefix; ?>predictions&amp;modifyprediction_id=<?php echo $row->prediction_id; ?>"><?php echo $row->prediction_id; ?></a>
							</td>
							<td><?php echo $row->display_name; ?></td>
							<td><?php echo $row->points; ?></td>
							<td><?php echo $row->match_no; ?></td>
							<td><?php echo $this->unclean($row->stage_name); ?></td>
							<td><?php echo $this->unclean($row->home_team_name); ?></td>
							<td>
							<?php echo $this->unclean($row->home_goals);
								if (!$row->is_group) { 
									echo ' ('.$this->unclean($row->home_penalties) . ')';
								}
							?>
							</td>
							<td>-</td>
							<td>
							<?php echo $this->unclean($row->away_goals);
								if (!$row->is_group) { 
									echo ' ('.$this->unclean($row->away_penalties) . ')';
								}
							?>
							</td>
							<td><?php echo $this->unclean($row->away_team_name); ?></td>
							<td><?php echo $row->wwhen; ?></td>
						</tr>
<?php
					}
?>
					</tbody>
				</table>
				
				<p>
					<input type="submit" name="<?php echo $this->prefix; ?>deletePrediction" value="<?php _e( 'Delete Selected', FP_PD ); ?>" class="button" />
				</p>
			</form>
			
		</div>
<?php
	}
	
	private function match_types($filter_match_type) {
		
		$output = '<select name="filter_match_type" id="filter_match_type">';
		$output .= "<option " . ($filter_match_type == -1 ? 'selected' : '') . ' value="-1">'.__('All Matches', FP_PD).'</option>';
		$output .= "<option " . ($filter_match_type == 1 ? 'selected' : '') . ' value="1">'.__('Results only', FP_PD).'</option>';
		$output .= "<option " . ($filter_match_type == 0 ? 'selected' : '') . ' value="0">'.__('Pending matches only', FP_PD).'</option>';
		$output .= '</select>';
		
		return $output;
	}
	
	/**
	 * Check valid input
	 */
	private function valid($user_id, $match_id, $home_goals, $away_goals, $home_penalties, $away_penalties, $points, $wwhen) {
		
		if (!is_numeric($home_goals) || !is_numeric($away_goals) || !is_numeric($points)) {
			$this->setMessage(__("Goals and points must be numeric", FP_PD), true);
			return false;
		}
		
		if (!is_numeric($home_penalties) || !is_numeric($away_penalties)) {
			$this->setMessage(__("Penalties must be numeric", FP_PD), true);
			return false;
		}
		
		if ($user_id == -1 || $match_id == -1) {
			$this->setMessage(__("Select a user and match", FP_PD), true);
			return false;
		}
		
		if (!$this->is_datetime($wwhen)) {
			$this->setMessage(__("Prediction time must be valid YYYY-MM-DD HH:MM:SS date time format.", FP_PD), true);
			return false;
		}
		
		return true;
	}
	
	/**
	 * Insert row
	 */
	private function insert($user_id, $match_id, $home_goals, $away_goals, $home_penalties, $away_penalties, $points, $wwhen) {
		global $wpdb;

		if (!$this->valid($user_id, $match_id, $home_goals, $away_goals, $home_penalties, $away_penalties, $points, $wwhen)) {
			return false;
		}
		
		$sql = "INSERT INTO {$wpdb->prefix}{$this->prefix}prediction (user_id, match_id, home_goals, away_goals, home_penalties, away_penalties, points, wwhen)
				VALUES (%d, %d, %d, %d, %d, %d, %d, %s)";
		
		$ret = $wpdb->query( $wpdb->prepare( $sql, $user_id, $match_id, $home_goals, $away_goals, $home_penalties, $away_penalties, $points, $wwhen) );
		
		if ($ret == 1) {
			return $wpdb->insert_id;
		} else {
			return false;
		}
	}
	
	/**
	 * Update row
	 */
	private function update($prediction_id, $user_id, $match_id, $home_goals, $away_goals, $home_penalties, $away_penalties, $points, $wwhen) {
		global $wpdb;
		
		if (!$this->valid($user_id, $match_id, $home_goals, $away_goals, $home_penalties, $away_penalties, $points, $wwhen)) {
			return false;
		}
		
		$sql = "UPDATE {$wpdb->prefix}{$this->prefix}prediction
				SET
					user_id = %d,
					match_id = %d,
					home_goals = %d,
					away_goals = %d,
					home_penalties = %d,
					away_penalties = %d,
					points = %d,
					wwhen = %s
				WHERE prediction_id = %d";
		
		return $wpdb->query( $wpdb->prepare( $sql, $user_id, $match_id, $home_goals, $away_goals, $home_penalties, $away_penalties, $points, $wwhen, $prediction_id ) );
	}
	
	/**
	 * Get row by id.
	 */
	private function get($prediction_id) {
		global $wpdb;
		
		$sql = "SELECT user_id, match_id, home_goals, away_goals, home_penalties, away_penalties, points, wwhen
				FROM {$wpdb->prefix}{$this->prefix}prediction WHERE prediction_id = %d";
		
		$row = $wpdb->get_row( $wpdb->prepare($sql, $prediction_id) , ARRAY_A );
		
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
	private function delete($prediction_id) {
		global $wpdb;
		
		$sql = "DELETE FROM {$wpdb->prefix}{$this->prefix}prediction WHERE prediction_id = %d";
		
		$wpdb->query( $wpdb->prepare( $sql, $prediction_id ) );
	}
}

?>