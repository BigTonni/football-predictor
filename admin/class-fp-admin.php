<?php
/**
 * @package Football
 * @author bigtonni
 * 
 */
 
class FootballAdmin extends Football {
    
        public $prefix = FP_PREFIX;
	
	/**
	 * Constructor
	 */
	function __construct() {
		global $wpdb;
		$wpdb->show_errors(true);
		parent::__construct();
	}
	
	/**
	 * Create admin menu
	 */
	function admin_menu() {
		
		require_once FP_ABSPATH.'admin/class-fp-menu.php';
		$menu = new FootballMenu();
		$scoring = new FootballScoring();
		$overview = new FootballOverview();
		
		add_menu_page(__('Football Menu', FP_PD), __('Football', FP_PD), FP_PREFIX.'manager', FP_PREFIX.'menu', array($overview, 'overview'), WP_PLUGIN_URL.'/'.FP_PD .'/images/football.png');
		add_submenu_page(FP_PREFIX.'menu' ,__('Overview', FP_PD), __('Overview', FP_PD), FP_PREFIX.'manager', FP_PREFIX.'menu' , array($overview, 'overview'));
		add_submenu_page(FP_PREFIX.'menu' ,__('Teams and Matches', FP_PD), __('Teams and Matches', FP_PD), FP_PREFIX.'manager', FP_PREFIX.'config' , array($menu, 'menu'));
		add_submenu_page(FP_PREFIX.'menu' ,__('Predictions', FP_PD), __('Predictions', FP_PD), FP_PREFIX.'manager', FP_PREFIX.'predictions' , array($scoring, 'menu'));		
	}
	
	/**
	 * Style sheet for admin functions
	 */
	function admin_print_styles() {
?>		
<link type="text/css" rel="stylesheet" href="<?php echo WP_PLUGIN_URL . '/' . FP_PD; ?>/css/style.css" />
<link type="text/css" rel="stylesheet" href="<?php echo WP_PLUGIN_URL . '/' . FP_PD; ?>/css/admin-style.css" />
<link type="text/css" rel="stylesheet" href="<?php echo WP_PLUGIN_URL . '/' . FP_PD; ?>/css/jquery-ui.css" />
<?php		
		wp_admin_css( 'css/dashboard' );
	}
	
	/**
	 * Javascript for admin functions
	 */
	function admin_print_scripts() {
		wp_enqueue_script(FP_PREFIX.'admin_js', WP_PLUGIN_URL . '/' . FP_PD . '/js/fp-admin.js', array( 'jquery', 'jquery-ui-tabs', 'jquery-ui-dialog' ));
		wp_enqueue_script( 'postbox' );
	}
	
	/**
	 * Init hook
	 */
	function admin_init() {
		
		register_setting( FP_PREFIX.'option-group', FP_PREFIX.'show_predictions');
		register_setting( FP_PREFIX.'option-group', FP_PREFIX.'promo_link');
		register_setting( FP_PREFIX.'option-group', FP_PREFIX.'countdown_format');
		register_setting( FP_PREFIX.'option-group', FP_PREFIX.'browser_locale');
		register_setting( FP_PREFIX.'option-group', FP_PREFIX.'adjust_knockout');
		register_setting( FP_PREFIX.'option-group', FP_PREFIX.'match_predictions');
		register_setting( FP_PREFIX.'option-group', FP_PREFIX.'user_predictions');
				
		if (isset($_REQUEST['page']) && stripos(sanitize_text_field($_REQUEST['page']), 'fp') !== false) {
			
                        $count = (int)get_option(FP_PREFIX.'nag') - 1;
                        if ($count <= 0) {
                                $count = 20;
                        }
                        update_option(FP_PREFIX.'nag', $count);
		}
	}
	
	
	function settings() { ?>
            <form method="post" action="options.php">
		
		<?php settings_fields( FP_PREFIX.'option-group' );?>
		
		<table class="form-table">
			<tr valign="top">
				<th scope="fp-row" colspan="2"><?php _e('Countdown clock format', FP_PD); ?></th>
			</tr>
			<tr>
				<td colspan="2"><input style="font-family:'Courier New', Courier, monospace;" type="text" size="60" name="<?php echo FP_PREFIX; ?>countdown_format" value="<?php echo get_option(FP_PREFIX.'countdown_format', __('Next prediction deadline in', FP_PD) . ' %%D%%d, %%H%%h, %%M%%m, %%S%%s'); ?>" /></td>
			</tr>
			<tr valign="top">
				<td scope="fp-row"><?php _e('Convert kickoff times to local timezone. If unchecked kickoff times are displayed as match local time', FP_PD); ?></td>
				<td><input type="checkbox" id="<?php echo FP_PREFIX; ?>browser_locale" name="<?php echo FP_PREFIX; ?>browser_locale" value="1" <?php echo get_option(FP_PREFIX.'browser_locale', 1) ? ' checked ' : ''; ?> /></td>
			</tr>
			<tr valign="top">
				<td scope="fp-row"><?php _e('Show users predictions before kickoff', FP_PD); ?></td>
				<td><input type="checkbox" id="<?php echo FP_PREFIX; ?>show_predictions" name="<?php echo FP_PREFIX; ?>show_predictions" value="1" <?php echo get_option(FP_PREFIX.'show_predictions', 1) ? ' checked ' : ''; ?> /></td>
			</tr>
			<tr valign="top">
				<td scope="fp-row"><?php _e('Adjust Knockout Table to the theme Twenty Fourteen', FP_PD); ?></td>
				<td><input type="checkbox" id="<?php echo FP_PREFIX; ?>adjust_knockout" name="<?php echo FP_PREFIX; ?>adjust_knockout" value="1" <?php echo get_option(FP_PREFIX.'adjust_knockout', 1) ? ' checked ' : ''; ?> /></td>
			</tr>
			<tr>
				<td scope="row"><?php _e('Page Predictions by Match', FP_PD); ?></td>
				<td>
					<select class="select2" name="<?php echo FP_PREFIX; ?>match_predictions"name="redirect"> 
						<option value=""><?php _e('Choose Page', FP_PD);?>...</option> 
						<?php 
							$pages = get_pages(); 
							foreach ( $pages as $page ) {
								if(get_option(FP_PREFIX.'match_predictions') == rtrim($page->guid,"/")) { 
									$selected = ' selected="selected"'; 
								} else { 
									$selected = ''; 
								}
								$option = '<option'.$selected.' value="' . $page->guid . '">';
								$option .= $page->post_title;
								$option .= '</option>';
								echo $option;
							}
						?>
					</select>
				</td>
			</tr>
			<tr>
				<td scope="row"><?php _e('Page Predictions by User', FP_PD); ?></td>
				<td>
					<select class="select2" name="<?php echo FP_PREFIX; ?>user_predictions"name="redirect"> 
						<option value=""><?php _e('Choose Page', FP_PD);?>...</option> 
						<?php 
							$pages = get_pages(); 
							foreach ( $pages as $page ) {
								if(get_option(FP_PREFIX.'user_predictions') == rtrim($page->guid,"/")) { 
									$selected = ' selected="selected"'; 
								} else { 
									$selected = ''; 
								}
								$option = '<option'.$selected.' value="' . $page->guid . '">';
								$option .= $page->post_title;
								$option .= '</option>';
								echo $option;
							}
						?>
					</select>
				</td>
			</tr>
		</table>
		
		<p class="submit">
			<input type="submit" class="button" value="<?php _e('Save Changes', FP_PD) ?>" />
		</p>
		
	</form>
<?php		
	}
		
	/**
	 * Set to current JQuery tab
	 * 
	 * @param int $i tab number indexed from 0
	 * @return none
	 */
	function selectTab($i) {
        ?>
		<script type="text/javascript">
		jQuery(function($) {
		  	$("#<?php echo FP_PREFIX; ?>tabs").tabs({ active: <?php echo $i; ?> });
		});
		</script>
        <?php 
	}
	
	/**
	 * Get a list of registered users in a dropdown select box
	 * 
	 * @param $player_id - Preselect this user
	 */
	function getUsers($user_id, $empty = true, $id = 'user_id', $empty_str = 'All Users') {
		
		global $wpdb;
		
		$sql = 'SELECT ID,user_login, display_name FROM ' . $wpdb->users . ' ORDER BY display_name';
		
		$users = $wpdb->get_results( $sql , OBJECT );
		
		$output = '<select name="'.$id.'" id="'.$id.'">';
		if ($empty) $output .= '<option value = "-1">'.$empty_str.'</option>';
		
		foreach ($users as $row) {
			$output .= "<option ";
			if (!is_null($user_id) && $user_id == $row->ID) {
				$output .= " selected ";
			}
			$output .= "value=\"$row->ID\">$row->user_login ($row->display_name)</option>";
		}
		$output .= "</select>";
		
		return $output;
	}
	
	/*
	 * Check is [+/-]HH:MM format
	 */
	function is_hhmm($s) {
		return (preg_match("/^[+-]{0,1}([0-9]{2}):([0-9]{2})/", $s));
	}
	
	/*
	 * Check YYYY-MM-DD HH:MM:SS format
	 */
	function is_datetime($d) {
		if (empty($d) || $d == '0000-00-00 00:00:00') return false;
		return (preg_match("/([0-9]{4})-([0-9]{2})-([0-9]{2}) ([0-9]{2}):([0-9]{2}):([0-9]{2})/", $d));
	}
}