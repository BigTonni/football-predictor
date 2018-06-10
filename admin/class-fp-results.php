<?php
/**
 * @package Football
 * 
 * Match Results Administration functions for the Football plugin.
 * 
 */
 
class FootballResults extends FootballAdmin {
	
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
	function results() {
		
		global $wpdb;
		
		if (isset($_POST[$this->prefix.'scoreMatch']) && isset( $_POST['match_id'] )) {
			$this->setMessage(__("Prediction points updated.", FP_PD));
		}
?>
		<div class="wrap">
			
			<h2><?php _e('Assign prediction scores for completed matches', FP_PD) ?></h2>
			
			<p><?php _e('Select each newly completed match to assign users\' scores.', FP_PD); ?> <a href="admin.php?page=<?php echo $this->prefix; ?>config#<?php echo $this->prefix; ?>_tabs-4"><?php echo _e('Enter Match Results', FP_PD);?></a></p>
			
			<p><?php echo sprintf(__('Matches marked with a green tick (%s) are finished and have been scored.', FP_PD), '<img src="' . WP_PLUGIN_URL.'/'. FP_PD .'/images/greentick.png" />'); ?></p>
			
			<?php $this->printMessage();
			
			/**
			 * Show the current match results in a table
			 */
			$sql = "SELECT match_id, match_no, kickoff AS utc_kickoff, h.name AS home_team_name, a.name AS away_team_name,
					home_goals, away_goals, home_penalties, away_penalties, stage_name, is_group, is_result, scored, m.wwhen
				FROM 
					{$wpdb->prefix}{$this->prefix}match m,
					{$wpdb->prefix}{$this->prefix}stage s,
					{$wpdb->prefix}{$this->prefix}team h,
					{$wpdb->prefix}{$this->prefix}team a
				WHERE
					m.stage_id = s.stage_id AND
					m.home_team_id = h.team_id AND m.away_team_id = a.team_id AND
					is_result = 1
				ORDER BY
					kickoff DESC";
					
			$result = $wpdb->get_results( $sql , OBJECT );
?>		
			<form id="scorematches" name="listmatches" method="post" action="<?php echo $_SERVER['PHP_SELF'] ?>?page=<?php echo $this->prefix; ?>predictions">
				
				<?php wp_nonce_field( $this->prefix . 'list-matches' ) ?>
				
				<table class="<?php echo $this->prefix; ?>table" width="90%">
					<thead>
						<tr>
							<th scope="column"><input type="checkbox" value="" id="selectallmatch"/> <?php _e('Sel', FP_PD) ?></th>
							<th scope="column"><?php _e('ID', FP_PD) ?></th>
							<th scope="column"><?php _e('#', FP_PD) ?></th>
							<th scope="column"><?php _e('Stage', FP_PD) ?></th>
							<th scope="column"><?php _e('Kickoff', FP_PD) ?></th>
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
						$hpen = '';
						$apen = '';
						if (!$row->is_group && ($row->home_penalties > 0 || $row->away_penalties > 0)) { 
							$hpen = ' ('.$row->home_penalties . ')';
							$apen = ' ('.$row->away_penalties . ')';
						}
?>
						<tr>
							<td>
								<input type="checkbox" value="<?php echo $row->match_id; ?>" name ="match_id[<?php echo $row->match_id;?>]"/> 
								<?php if ($row->scored == 1) { ?>
								<img src="<?php echo WP_PLUGIN_URL.'/'.FP_PD; ?>/images/greentick.png" />
								<?php } ?>
							</td>
							<td><?php echo $row->match_id; ?></td>
							<td><?php echo $this->unclean($row->match_no); ?></td>
							<td><?php echo $this->unclean($row->stage_name); ?></td>
							<td><?php echo $row->utc_kickoff; ?></td>
							<td><?php echo $this->unclean($row->home_team_name); ?></td>
							<td><?php echo $this->unclean($row->home_goals) . $hpen; ?></td>
							<td>-</td>
							<td><?php echo $this->unclean($row->away_goals) . $apen; ?></td>
							<td><?php echo $this->unclean($row->away_team_name); ?></td>
							<td><?php echo $row->wwhen; ?></td>
						</tr>
<?php
					}
?>
					</tbody>
				</table>
				
				<p>
					<input type="submit" name="<?php echo $this->prefix; ?>scoreMatch" value="<?php _e( 'Score Selected', FP_PD ); ?>" class="button-primary" />		
				</p>
				
			</form>
			
		</div>
<?php
	}
}

?>