<?php
/**
 * Handle the main admin welcome screen
 * 
 * @package Football
 *
 */
 
class FootballOverview extends FootballAdmin {
	
	function dashboard() {
		
		global $wpdb;
		$disabled = '';
		
		$teams    = intval( $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}{$this->prefix}team WHERE country <> 'xxx'") );
		$venues = intval( $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}{$this->prefix}venue") );
		$matches    = intval( $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}{$this->prefix}match") );
		$predictions = intval( $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}{$this->prefix}prediction") );
		
		if ($teams || $venues || $matches) {
			$disabled = ' disabled ';
		}
		
		?>
		
		<p class="sub"><?php _e('At a Glance', FP_PD); ?></p>
		<div class="table">
			<table>
				<tbody>
					<tr class="first">
						<td class="first b"><?php echo $teams; ?></td>
						<td class="t"><?php echo _n( 'Team', 'Teams', $teams, FP_PD ); ?></td>
					</tr>
					<tr class="first">
						<td class="first b"><?php echo $venues; ?></td>
						<td class="t"><?php echo _n( 'Venue', 'Venues', $venues, FP_PD ); ?></td>
					</tr>
					<tr class="first">
						<td class="first b"><?php echo $matches; ?></td>
						<td class="t"><?php echo _n( 'Match', 'Matches', $matches, FP_PD ); ?></td>
					</tr>
					<tr class="first">
						<td class="first b"><?php echo $predictions; ?></td>
						<td class="t"><?php echo _n( 'Prediction', 'Predictions', $predictions, FP_PD ); ?></td>
					</tr>
				</tbody>
			</table>
		</div>
		<div class="versions">
			<?php _e('Here you can import your teams, venues and match schedules.', FP_PD) ?>
			<?php if(current_user_can($this->prefix . 'manager')): ?>
				<form method="POST">
					<div class="form-group">
						<label for="champs"><?php _e('Championship', FP_PD); ?></label>
						<select class="select2" name="champs"> 
							<option value="wcup-2018"><?php _e('World Cup 2018', FP_PD); ?></option>
						</select>
					</div>
                                        <p class="submit">
                                                <input <?php echo $disabled; ?> type="submit" name="<?php echo $this->prefix; ?>import" class="button rbutton" value="<?php _e('Import', FP_PD) ?>" />
                                        </p>
				</form>
			<?php endif; ?>
			<p>
			<?php
                            $userlevel = '<span class="b">' . (current_user_can($this->prefix . 'manager') ? __('Football Manager', FP_PD) : __('no', FP_PD)) . '</span>';
                            printf(__('You currently have %s rights.', FP_PD), $userlevel);
                        ?>
                        </p>
		</div>
		<?php
	}
		
	function get_server_info() {
		global $wpdb;
		
		// Get MYSQL Version
		$sqlversion = $wpdb->get_var("SELECT VERSION() AS version");
		// GET SQL Mode
		$mysqlinfo = $wpdb->get_results("SHOW VARIABLES LIKE 'sql_mode'");
		if (is_array($mysqlinfo)) $sql_mode = $mysqlinfo[0]->Value;
		if (empty($sql_mode)) $sql_mode = __('Not set', FP_PD);
		// Get PHP Safe Mode
		if(ini_get('safe_mode')) $safe_mode = __('On', FP_PD);
		else $safe_mode = __('Off', FP_PD);
		
	?>
		<li><?php _e('Operating System', FP_PD); ?> : <span><?php echo PHP_OS; ?></span></li>
		<li><?php _e('Server', FP_PD); ?> : <span><?php echo $_SERVER["SERVER_SOFTWARE"]; ?></span></li>
		<li><?php _e('MYSQL Version', FP_PD); ?> : <span><?php echo $sqlversion; ?></span></li>
		<li><?php _e('SQL Mode', FP_PD); ?> : <span><?php echo $sql_mode; ?></span></li>
		<li><?php _e('PHP Version', FP_PD); ?> : <span><?php echo PHP_VERSION; ?></span></li>
		<li><?php _e('PHP Safe Mode', FP_PD); ?> : <span><?php echo $safe_mode; ?></span></li>
	<?php
	}
	
	function server() { ?>
		<div id="dashboard_server_settings" class="dashboard-widget-holder wp_dashboard_empty">
			<div class="fp-dashboard-widget">
			  	<div class="dashboard-widget-content">
		      		<ul class="fp-settings">
		      		<?php $this->get_server_info(); ?>
			   		</ul>
				</div>
		    </div>
		</div>
		<?php	
	}
	
	function overview() {		
		add_meta_box('dashboard_right_now', __('Welcome to Football Predictor V ', FP_PD) . FP_VERSION, array($this, 'dashboard'), 'fp_overview', 'left', 'core');
		add_meta_box('fp_server', __('Server Settings', FP_PD), array($this, 'server'), 'fp_overview', 'right', 'core');
		add_meta_box('fp_settings', __('Settings', FP_PD), array($this, 'settings'), 'fp_overview', 'left', 'core');
                if (isset($_POST[$this->prefix.'import'])) {
			$this->import();
		}
	?>
	<div class="wrap fp-wrap">
		<h2><?php _e('Football Predictor Overview', FP_PD) ?></h2>
		<div id="dashboard-widgets-wrap" class="fp-overview">
		    <div id="dashboard-widgets" class="metabox-holder">
				<div id="post-body">
					<div id="dashboard-widgets-main-content">
						<div class="postbox-container" style="width:49%;">
							<?php do_meta_boxes('fp_overview', 'left', ''); ?>
						</div>
			    		<div class="postbox-container" style="width:49%;">
							<?php do_meta_boxes('fp_overview', 'right', ''); ?>
						</div>						
					</div>
				</div>
		    </div>
		</div>
	</div>
	<script type="text/javascript">
		//<![CDATA[
		jQuery(document).ready( function($) {
			// postboxes setup
			postboxes.add_postbox_toggles('fp-overview');
		});
		//]]>
	</script>
	<?php
		
	}
	
	/**
	 * Read an array from a remote url
	 * 
	 * @param string $url
	 * @return array of the content
	 */
	function get_remote_array($url) {
		if ( function_exists('wp_remote_request') ) {
			
			$options = array();
			$options['headers'] = array(
				'User-Agent' => 'Football Predictor V' . FP_VERSION . '; (' . get_bloginfo('url') .')'
			);
			
			$response = wp_remote_request($url, $options);
			
			if ( is_wp_error( $response ) )
				return false;
			
			if ( 200 != $response['response']['code'] )
				return false;
		   	
			$content = unserialize($response['body']);
			
			if (is_array($content)) 
				return $content;
		}
		
		return false;	
	}
	/**
	 * import
	 * 
         * @version 1.0
	 * @return string
	 */
	function import() {		
		global $wpdb;
		
		$champs = '';		
		extract($_POST, EXTR_IF_EXISTS);

		if(!empty($champs)) {
			require_once FP_ABSPATH."admin/import/$champs.php";
		}
		
		$this->printMessage();
	}        
}
