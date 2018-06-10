<?php
/**
 * Handle the main admin menu
 * 
 * @package Football
 * 
 */
 
class FootballMenu extends FootballAdmin {
	
	function menu() {
		
		$teams = new FootballTeams(0);
		$venues = new FootballVenues(1);
		$stages = new FootballStages(2);
		$matches = new FootballMatches(3);
?>
		<div class="wrap">
			
			<h2><?php _e('Football Manager', FP_PD) ?></h2>
			
			<div id="<?php echo $this->prefix; ?>tabs" class="ui-tabs">
				
				<ul class="ui-tabs-nav">
					<li><a href="#<?php echo $this->prefix; ?>tabs-1"><?php _e("Teams", FP_PD); ?></a></li>
					<li><a href="#<?php echo $this->prefix; ?>tabs-2"><?php _e("Venues", FP_PD); ?></a></li>
					<li><a href="#<?php echo $this->prefix; ?>tabs-3"><?php _e("Stages", FP_PD); ?></a></li>
					<li><a href="#<?php echo $this->prefix; ?>tabs-4"><?php _e("Matches", FP_PD); ?></a></li>
					<li><a href="#<?php echo $this->prefix; ?>tabs-5"><?php _e("Group Results", FP_PD); ?></a></li>
					<!--<li><a href="#<?php // echo $this->prefix; ?>tabs-6"><?php _e("Knockout Results", FP_PD); ?></a></li>-->
				</ul>
				
				<div id="<?php echo $this->prefix; ?>tabs-1">
					<?php echo $teams->teams(); ?>
				</div>
				
				<div id="<?php echo $this->prefix; ?>tabs-2">
					<?php echo $venues->venues(); ?>
				</div>
				
				<div id="<?php echo $this->prefix; ?>tabs-3">
					<?php echo $stages->stages(); ?>
				</div>
				
				<div id="<?php echo $this->prefix; ?>tabs-4">
					<?php echo $matches->matches(); ?>
				</div>
				
				<div id="<?php echo $this->prefix; ?>tabs-5">
					<?php echo $this->group_results(); ?>
				</div>
				
<!--				<div id="<?php // echo $this->prefix; ?>tabs-6">
					<?php // echo $this->knockout_results(); ?>
				</div>-->
				
			</div>
		
		</div>
<?php
	}
	
	function group_results() {
		
		$report = new FootballReport();
?>
		<div class="wrap" style="padding-bottom:1em;">
			
			<h2><?php _e('Group Results', FP_PD) ?></h2>
			
			<?php echo $report->group_tables(0, 1, '50%'); ?>
			
		</div>
<?php
	}
	
	function knockout_results() {
		
		$report = new FootballReport();
?>
		<div class="wrap" style="padding-bottom:1em;">
			
			<h2><?php _e('Knockout Results', FP_PD) ?></h2>
			
			<?php echo $report->knockout(); ?>
			
		</div>
<?php
	}
}