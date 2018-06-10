<?php
/**
 * @package Football
 * 
 * Stage Administration functions for the Football plugin.
 * 
 */
 
class FootballStages extends FootballAdmin {
	
	var $tab;
	
	/**
	 * Constructor
	 */
	function __construct($tab) {
		$this->tab = $tab;
		parent::__construct();
	}
	
	/**
	 * Display and manage stages
	 */
	function stages() {
		
		global $wpdb;
		
		$stage_name = '';
		$is_group = 0;
		$sort_order = 0;
		$stage_id = -1;
		
		if (isset($_POST[$this->prefix.'modifyStageCancel'])) {
			check_admin_referer($this->prefix . 'stage-form');
			$this->selectTab($this->tab);
		}
		
		if (isset($_POST[$this->prefix.'addStage'])) {
			check_admin_referer($this->prefix . 'stage-form');
			
			extract($_POST, EXTR_IF_EXISTS);
			
			// Save to database
			if ($this->insert($stage_name, $is_group, $sort_order) !== false) {
				$stage_name = '';
				$is_group = 0;
				$sort_order = 0;
				delete_option($this->prefix.'group_stats');  // Clear cache
				$this->setMessage(__('Changes saved', FP_PD));
			}
			$this->selectTab($this->tab);
		}
		
		/**
		 * Actually modify the result.
		 */
		if (isset($_POST[$this->prefix.'modifyStage'])) {
			check_admin_referer($this->prefix . 'stage-form');
			
			extract($_POST, EXTR_IF_EXISTS);
			
			if ($this->update($stage_id, $stage_name, $is_group, $sort_order) !== false) {
				$stage_name = '';
				$is_group = 0;
				$sort_order = 0;
				$stage_id = -1;
				delete_option($this->prefix.'group_stats');  // Clear cache
				$this->setMessage(__('Changes saved', FP_PD));
			}
			$this->selectTab($this->tab);
		}
		
		/**
		 * Process GET request to retreive the stage details and pre-fill
		 * the form.
		 */
		if (isset($_GET['modifystage_id'])) {
			$stage_id = sanitize_text_field($_GET['modifystage_id']);
			$row = $this->get($stage_id);
			if (empty($row)) $stage_id = -1;	// Didn't find row. Prevent modification
			extract($row, EXTR_IF_EXISTS);
			$this->selectTab($this->tab);
		}
		
		if (isset($_POST[$this->prefix.'deleteStage'])) {
			check_admin_referer($this->prefix . 'list-stages');
			if (isset($_POST['stage_id'])) {
				foreach ($_POST['stage_id'] as $id) {
					$this->delete((int)$id);
				}
				delete_option($this->prefix.'group_stats');  // Clear cache
				$this->setMessage(__('Changes saved', FP_PD));
			}
			$this->selectTab($this->tab);
		}
?>
		<div class="wrap">
			
			<h2><?php _e('Manage stages', FP_PD) ?></h2>
			
			<?php $this->printMessage(); ?>
			
			<form class="form-table <?php echo $this->prefix; ?>form" name="stage" action="<?php echo $_SERVER['PHP_SELF'] ?>?page=<?php echo $this->prefix; ?>config" method="post">
				
				<?php wp_nonce_field( $this->prefix . 'stage-form' ) ?>
				
				<table>
					<tr valign="top">
						<td scope="fp-row"><label for="stage_name"><?php _e( 'Stage Name', FP_PD ) ?></label></td>
						<td><input type="text" name="stage_name" value="<?php echo $stage_name;?>" size="45" /></td>
					</tr>
					<tr valign="top">
						<td scope="fp-row"><label for="is_group"><?php _e( 'Group Stage', FP_PD ) ?></label></td>
						<td><input type="checkbox" name="is_group" value="1" <?php echo ($is_group ? ' checked ' : ''); ?> /></td>
					</tr>
					<tr valign="top">
						<td scope="fp-row"><label for="sort_order"><?php _e( 'Sort Order', FP_PD ) ?></label></td>
						<td><input type="text" name="sort_order" value="<?php echo $sort_order;?>" size="4" /></td>
					</tr>
				</table>
<?php 
			if  ($stage_id != -1) {
?>
				<input type="hidden" value="<?php echo $stage_id; ?>" name="stage_id"></input>
				<p class="submit">
					<input type="submit" name="<?php echo $this->prefix;?>modifyStage" value="<?php _e( 'Modify Stage', FP_PD ) ?>" class="button-primary" />
					<input type="submit" name="<?php echo $this->prefix;?>modifyStageCancel" value="<?php _e( 'Cancel', FP_PD ) ?>" class="button" />
				</p>
<?php 
			} else {
?>
				<p class="submit"><input type="submit" name="<?php echo $this->prefix;?>addStage" value="<?php _e( 'Add Stage', FP_PD ) ?>" class="button-primary" /></p>
<?php 
			}
?>
			</form>
<?php 
		/**
		 * Show the current stage list in a table
		 */
		$sql = "SELECT stage_id, stage_name, is_group, sort_order, wwhen
				FROM 
					{$wpdb->prefix}{$this->prefix}stage
				ORDER BY
					sort_order";
					
		$result = $wpdb->get_results( $sql , OBJECT );
?>		
			<form name="liststages" method="post" action="<?php echo $_SERVER['PHP_SELF'] ?>?page=<?php echo $this->prefix; ?>config">
				<?php wp_nonce_field( $this->prefix . 'list-stages' ) ?>
				<table class="<?php echo $this->prefix; ?>table" width="90%">
					<thead>
						<tr>
							<th scope="column"><?php _e('Del', FP_PD) ?></th>
							<th scope="column"><?php _e('ID', FP_PD) ?></th>
							<th scope="column"><?php _e('Name', FP_PD) ?></th>
							<th scope="column"><?php _e('Group', FP_PD) ?></th>
							<th scope="column"><?php _e('Sort', FP_PD) ?></th>
							<th scope="column"><?php _e('Last Modified', FP_PD) ?></th>
						</tr>
					</thead>
					<tbody>
<?php
					foreach ($result as $row) {
?>
						<tr>
							<td><input type="checkbox" value="<?php echo $row->stage_id; ?>" name ="stage_id[<?php echo $row->stage_id;?>]"/></td>
							<td><a title="<?php _e('Modify this stage', FP_PD); ?>" href="<?php echo $_SERVER['PHP_SELF'] ?>?page=<?php echo $this->prefix; ?>config&amp;modifystage_id=<?php echo $row->stage_id; ?>"><?php echo $row->stage_id; ?></a></td>
							<td><?php echo $this->unclean($row->stage_name); ?></td>
							<td><input type="checkbox" disabled <?php echo ($row->is_group ? ' checked ' : ''); ?> /></td>
							<td><?php echo $row->sort_order; ?></td>
							<td><?php echo $row->wwhen; ?></td>
						</tr>
<?php
					}
?>
					</tbody>
				</table>
				
				<p><input type="submit" name="<?php echo $this->prefix; ?>deleteStage" value="<?php _e( 'Delete Selected', FP_PD ); ?>" class="button" /></p>
				
			</form>
			
		</div>
<?php
	}
	
	/**
	 * Check valid input
	 */
	private function valid($stage_name, $is_group, $sort_order) {
		if (empty($stage_name)) {
			$this->setMessage(__("Stage Name can not be empty", FP_PD), true);
			return false;
		}
		if (!is_numeric($sort_order)) {
			$this->setMessage(__("Sort order must be numeric", FP_PD), true);
			return false;
		}
		
		return true;
	}
	
	/**
	 * Insert row
	 */
	private function insert($stage_name, $is_group, $sort_order) {
		
		global $wpdb;
		
		$stage_name = $this->clean($stage_name);
		
		if (!$this->valid($stage_name, $is_group, $sort_order)) {
			return false;
		}
		
		$sql = "INSERT INTO {$wpdb->prefix}{$this->prefix}stage (stage_name, is_group, sort_order)
				VALUES (%s, %d, %d)";
		
		$ret = $wpdb->query( $wpdb->prepare( $sql, $stage_name, $is_group, $sort_order) );
		
		if ($ret == 1) {
			return $wpdb->insert_id;
		} else {
			return false;
		}
	}
	
	/**
	 * Update row
	 */
	private function update($stage_id, $stage_name, $is_group, $sort_order) {
		
		global $wpdb;
		
		$stage_name = $this->clean($stage_name);
		
		if (!$this->valid($stage_name, $is_group, $sort_order)) {
			return false;
		}
		
		$sql = "UPDATE {$wpdb->prefix}{$this->prefix}stage
				SET
					stage_name = %s,
					is_group = %d,
					sort_order = %d
				WHERE stage_id = %d";
		
		return $wpdb->query( $wpdb->prepare( $sql, $stage_name, $is_group, $sort_order, $stage_id ) );
	}
	
	/**
	 * Get row by id.
	 */
	private function get($stage_id) {
		
		global $wpdb;
		
		$sql = "SELECT stage_name, is_group, sort_order
				FROM {$wpdb->prefix}{$this->prefix}stage WHERE stage_id = %d";
		
		$row = $wpdb->get_row( $wpdb->prepare($sql, $stage_id) , ARRAY_A );
		
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
	private function delete($stage_id) {
		
		global $wpdb;
		
		$sql = "SELECT COUNT(*) FROM {$wpdb->prefix}{$this->prefix}match WHERE stage_id = %d";
		$count = $wpdb->get_var($wpdb->prepare($sql, $stage_id));
		if ($count) {
			$this->setMessage(__('Can not delete a stage with matches', FP_PD), true);
			return false;
		}		
		
		$sql = "DELETE FROM {$wpdb->prefix}{$this->prefix}stage WHERE stage_id = %d";
		
		$wpdb->query( $wpdb->prepare( $sql, $stage_id ) );
	}
	
	/**
	 * Get a list of stages in a dropdown select box
	 * 
	 * @param $stage_id - Preselect this stage
	 */
	function getStages($stage_id, $empty = true, $id = 'stage_id', $empty_str = '') {
		
		global $wpdb;
		
		$sql = "SELECT stage_id, stage_name FROM {$wpdb->prefix}{$this->prefix}stage ORDER BY sort_order";
		
		$result = $wpdb->get_results( $sql );
		
		$output = '<select name="'.$id.'" id="'.$id.'">';
		if ($empty) $output .= '<option value = "-1">'.$empty_str.'</option>';
		
		foreach ($result as $row) {
			$output .= "<option ";
			if (!is_null($id) && $stage_id == $row->stage_id) {
				$output .= " selected ";
			}
			$output .= "value=\"$row->stage_id\">".$this->unclean($row->stage_name)."</option>";
		}
		
		$output .= "</select>";
		
		return $output;
	}
}

?>