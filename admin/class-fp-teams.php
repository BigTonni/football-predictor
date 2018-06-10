<?php
/**
 * @package Football
 * 
 * Team Administration functions for the Football plugin.
 * 
 */
 
class FootballTeams extends FootballAdmin {
	
	var $tab;
	
	/**
	 * Constructor
	 */
	function __construct($tab) {
		$this->tab = $tab;
		parent::__construct();
	}
	
	/**
	 * Display and manage teams
	 */
	function teams() {
		
		global $wpdb;
		
		$name = '';
		$country = '';
		$team_url = '';
		$team_id = -1;
		$group_order = 0;
		
		if (isset($_POST[$this->prefix.'modifyTeamCancel'])) {
			check_admin_referer($this->prefix . 'team-form');
			$this->selectTab($this->tab);
		}
		
		if (isset($_POST[$this->prefix.'addTeam'])) {
			check_admin_referer($this->prefix . 'team-form');
			
			extract($_POST, EXTR_IF_EXISTS);
			
			// Save to database
			if ($this->insert($name, $country, $team_url, $group_order) !== false) {
				$name = '';
				$country = '';
				$team_url = '';
				$group_order = 0;
				delete_option($this->prefix.'group_stats');  // Clear cache
				$this->setMessage(__('Changes saved', FP_PD));
			}
			$this->selectTab($this->tab);
		}
		
		/**
		 * Actually modify the result.
		 */
		if (isset($_POST[$this->prefix.'modifyTeam'])) {
			check_admin_referer($this->prefix . 'team-form');
			
			extract($_POST, EXTR_IF_EXISTS);
			
			if ($this->update($team_id, $name, $country, $team_url, $group_order) !== false) {
				$name = '';
				$country = '';
				$team_url = '';
				$group_order = 0;
				$team_id = -1;
				delete_option($this->prefix.'group_stats');  // Clear cache
				$this->setMessage(__('Changes saved', FP_PD));
			}
			$this->selectTab($this->tab);
		}
		
		/**
		 * Process GET request to retreive the team details and pre-fill the form.
		 */
		if (isset($_GET['modifyteam_id'])) {
			$team_id = sanitize_text_field($_GET['modifyteam_id']);
			$row = $this->get($team_id);
			if (empty($row)) $team_id = -1;	// Didn't find row. Prevent modification
			extract($row, EXTR_IF_EXISTS);
			$this->selectTab($this->tab);
		}
		
		if (isset($_POST[$this->prefix.'deleteTeam'])) {
			check_admin_referer($this->prefix . 'list-teams');
			if (isset($_POST['team_id'])) {
				foreach ($_POST['team_id'] as $id) {
					$this->delete((int)$id);
				}
				delete_option($this->prefix.'group_stats');  // Clear cache
				$this->setMessage(__('Changes saved', FP_PD));
			}
			$this->selectTab($this->tab);
		}
?>
		<div class="wrap">
			
			<h2><?php _e('Manage teams', FP_PD) ?></h2>
			
			<?php $this->printMessage(); ?>
			
			<form class="form-table <?php echo $this->prefix; ?>form" name="team" action="<?php echo $_SERVER['PHP_SELF'] ?>?page=<?php echo $this->prefix; ?>config" method="post">
			
				<?php wp_nonce_field( $this->prefix . 'team-form' ) ?>
				
				<p><a href="http://www.fifa.com/worldcup/teams/index.html" target="_blank">http://www.fifa.com/worldcup/teams/index.html</a></p>
				
				<table>
					<tr valign="top">
						<td scope="fp-row"><label for="name"><?php _e( 'Team Name', FP_PD ) ?></label></td>
						<td><input type="text" name="name" value="<?php echo $name;?>" size="45" /></td>
					</tr>
					<tr valign="top">
						<td scope="fp-row"><label for="country"><?php _e( 'Country Code', FP_PD ) ?></label></td>
						<td><input type="text" name="country" value="<?php echo $country;?>" size="3" /></td>
					</tr>
					<tr valign="top">
						<td scope="fp-row"><label for="team_url"><?php _e( 'Team URL', FP_PD ) ?></label></td>
						<td><input type="text" name="team_url" value="<?php echo $team_url;?>" size="60" /></td>
					</tr>
					<tr valign="top">
						<td scope="fp-row"><label for="group_order"><?php _e( 'Group Order', FP_PD ) ?></label></td>
						<td><input type="text" name="group_order" value="<?php echo $group_order;?>" size="3" /></td>
					</tr>
				</table>
<?php 
			if  ($team_id != -1) {
?>
				<input type="hidden" value="<?php echo $team_id; ?>" name="team_id"></input>
				<p class="submit">
					<input type="submit" name="<?php echo $this->prefix;?>modifyTeam" value="<?php _e( 'Modify Team', FP_PD ) ?>" class="button-primary" />
					<input type="submit" name="<?php echo $this->prefix;?>modifyTeamCancel" value="<?php _e( 'Cancel', FP_PD ) ?>" class="button" />
				</p>
<?php 
			} else {
?>
				<p class="submit"><input type="submit" name="<?php echo $this->prefix;?>addTeam" value="<?php _e( 'Add Team', FP_PD ) ?>" class="button-primary" /></p>
<?php 
			}
?>
			</form>
<?php 
		/**
		 * Show the current team list in a table
		 */
		$sql = "SELECT team_id, name, country, team_url, group_order, wwhen
				FROM 
					{$wpdb->prefix}{$this->prefix}team
				ORDER BY
					team_id, name";
					
		$result = $wpdb->get_results( $sql , OBJECT );

?>		
			<form name="listteams" method="post" action="<?php echo $_SERVER['PHP_SELF'] ?>?page=<?php echo $this->prefix; ?>config">
				
				<?php wp_nonce_field( $this->prefix . 'list-teams' ) ?>
				
				<table class="<?php echo $this->prefix; ?>table" width="90%">
					<thead>
						<tr>
							<th scope="column"><?php _e('Del', FP_PD) ?></th>
							<th scope="column"><?php _e('ID', FP_PD) ?></th>
							<th scope="column"><?php _e('Name', FP_PD) ?></th>
							<th scope="column"><?php _e('Flag', FP_PD) ?></th>
							<th scope="column"><?php _e('Country Code', FP_PD) ?></th>
							<th scope="column"><?php _e('Group Order', FP_PD) ?></th>
							<th scope="column"><?php _e('Team URL', FP_PD) ?></th>
							<th scope="column"><?php _e('Last Modified', FP_PD) ?></th>
						</tr>
					</thead>
					<tbody>
<?php
					foreach ($result as $row) {
?>
						<tr>
							<td><input type="checkbox" value="<?php echo $row->team_id; ?>" name ="team_id[<?php echo $row->team_id;?>]"/></td>
							<td><a title="<?php _e('Modify this team', FP_PD); ?>" href="<?php echo $_SERVER['PHP_SELF'] ?>?page=<?php echo $this->prefix; ?>config&amp;modifyteam_id=<?php echo $row->team_id; ?>"><?php echo $row->team_id; ?></a></td>
							<td><?php echo $this->unclean($row->name); ?></td>
							<td><?php echo $this->flag($this->unclean($row->country)); ?></td>
							<td><?php echo $this->unclean($row->country); ?></td>
							<td><?php echo $row->group_order; ?></td>
							<td><?php echo $row->team_url; ?></td>
							<td><?php echo $row->wwhen; ?></td>
						</tr>
<?php
					}
?>
					</tbody>
				</table>
			
				<p><input type="submit" name="<?php echo $this->prefix; ?>deleteTeam" value="<?php _e( 'Delete Selected', FP_PD ); ?>" class="button" /></p>
			
			</form>
		
		</div>
<?php
	}
	
	/**
	 * Check valid input
	 */
	private function valid($name, $country, $team_url, $group_order) {
		if (empty($country) || empty($name)) {
			$this->setMessage(__("Team Name or Country Code can not be empty", FP_PD), true);
			return false;
		}
		
		if (!is_numeric($group_order)) {
			$this->setMessage(__("Group Order must be numeric", FP_PD), true);
			return false;
		}
		return true;
	}
	
	/**
	 * Insert row
	 */
	private function insert($name, $country, $team_url, $group_order) {
		
		global $wpdb;
		
		$name = $this->clean($name);
		$country = $this->clean($country);
		
		if (!$this->valid($name, $country, $team_url, $group_order)) {
			return false;
		}
		
		$sql = "INSERT INTO {$wpdb->prefix}{$this->prefix}team (name, country, team_url, group_order)
				VALUES (%s, %s, %s, %d)";
		
		$ret = $wpdb->query( $wpdb->prepare( $sql, $name, $country, $team_url, $group_order ) );
		
		if ($ret == 1) {
			return $wpdb->insert_id;
		} else {
			return false;
		}
	}
	
	/**
	 * Update row
	 */
	private function update($team_id, $name, $country, $team_url, $group_order) {
		
		global $wpdb;
		
		$name = $this->clean($name);
		$country = $this->clean($country);
		
		if (!$this->valid($name, $country, $team_url, $group_order)) {
			return false;
		}
		
		$sql = "UPDATE {$wpdb->prefix}{$this->prefix}team
				SET
					name = %s,
					country = %s,
					team_url = %s,
					group_order = %d
				WHERE team_id = %d";
		
		return $wpdb->query( $wpdb->prepare( $sql, $name, $country, $team_url, $group_order, $team_id ) );
	}
	
	/**
	 * Get row by id.
	 */
	private function get($team_id) {
		
		global $wpdb;
		
		$sql = "SELECT name, country, team_url, group_order
				FROM {$wpdb->prefix}{$this->prefix}team WHERE team_id = %d";
		
		$row = $wpdb->get_row( $wpdb->prepare($sql, $team_id) , ARRAY_A );
		
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
	private function delete($team_id) {
		
		global $wpdb;
		
		$sql = "SELECT COUNT(*) FROM {$wpdb->prefix}{$this->prefix}match WHERE home_team_id = %d OR away_team_id = %d";
		$count = $wpdb->get_var($wpdb->prepare($sql, $team_id, $team_id));
		if ($count) {
			$this->setMessage(__('Can not delete a team with matches', FP_PD), true);
			return false;
		}
		
		$sql = "DELETE FROM {$wpdb->prefix}{$this->prefix}team WHERE team_id = %d";
		
		$wpdb->query( $wpdb->prepare( $sql, $team_id ) );
	}
	
	/**
	 * Get a list of teams in a dropdown select box
	 * 
	 * @param $team_id - Preselect this team
	 */
	function getTeams($team_id, $empty = true, $id = 'team_id', $empty_str='') {
		
		global $wpdb;
		
		$sql = "SELECT team_id, name, country FROM {$wpdb->prefix}{$this->prefix}team ORDER BY name";
		
		$result = $wpdb->get_results( $sql );
		
		$output = '<select name="'.$id.'" id="'.$id.'">';
		if ($empty) $output .= '<option value = "-1">'.$empty_str.'</option>';
		
		foreach ($result as $row) {
			$output .= "<option ";
			if (!is_null($id) && $team_id == $row->team_id) {
				$output .= " selected ";
			}
			$output .= "value=\"$row->team_id\">".$this->unclean($row->name)."</option>";
		}
		$output .= "</select>";
		
		return $output;
	}
}

?>