<?php
if (!defined('ABSPATH')) {
    exit;
}
/**
 * Widgets
 * 
 * Widget class for Wordpress plugin Football Predictor
 * 
 */
 
class FootballRankingWidget extends WP_Widget {
	
	function __construct() {
		$widget_ops = array('classname' => 'widget_'.FP_PD, 'description' => __('Display Leaders of Football Predictor', FP_PD) );
		parent::__construct(FP_PD, __('FP Top Ranking', FP_PD), $widget_ops);                
	}
	
	function widget($args, $instance) {
		// prints the widget
		extract($args);
		$title = apply_filters('widget_title', $instance['title']);
		$max = $instance['max'];
		$url = $instance['url'];
		$name = $instance['name'];
		$avatar = $instance['avatar'];
		$highlight = $instance['highlight'];
		
		echo $before_widget;
		if ( $title )
		echo $before_title . $title . $after_title;
		
		require_once FP_ABSPATH.'includes/class-fp-reports.php';
		$r = new FootballReport();
		
		echo $r->user_ranking($max, $avatar, $highlight);
		
		if (!empty($url) && !empty($name)) {
			echo '<p><a href="'.$url.'">'.$name.'</a></p>';
		}
		
		echo $after_widget;
	}
	
	function update($new_instance, $old_instance) {
		//save the widget
		$instance = $old_instance;
		$new_instance = wp_parse_args((array) $new_instance, array( 'title' => ''));
		$instance['title'] = strip_tags($new_instance['title']);
		$new_instance = wp_parse_args((array) $new_instance, array( 'max' => 10));
		$instance['max'] = strip_tags($new_instance['max']);
		$new_instance = wp_parse_args((array) $new_instance, array( 'url' => ''));
		$instance['avatar'] = strip_tags($new_instance['avatar']);
		$new_instance = wp_parse_args((array) $new_instance, array( 'avatar' => 0));
		$instance['url'] = strip_tags($new_instance['url']);
		$new_instance = wp_parse_args((array) $new_instance, array( 'name' => 'Full ranking'));
		$instance['name'] = strip_tags($new_instance['name']);
		$new_instance = wp_parse_args((array) $new_instance, array( 'highlight' => ''));
		$instance['highlight'] = strip_tags($new_instance['highlight']);
		
		return $instance;
	}
	
	function form($instance) {
		
		global $wpdb;
		
		//widgetform in backend
		$instance = wp_parse_args( (array) $instance, array( 'title' => '', 'max' => 10, 'avatar' => 0, 'url' => '', 'name' => 'Full ranking', 'highlight' => '') );
		$title = $instance['title'];
		$max = $instance['max'];
		if (!is_numeric($max)) $max = 10;
		$avatar = $instance['avatar'];
		$url = $instance['url'];
		$name = $instance['name'];
		$highlight = $instance['highlight'];
		
?>
		<p><?php _e('Display Rankings.', FP_PD); ?></p>
		<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', FP_PD); ?>
		<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" /></label></p>
		
		<p><label for="<?php echo $this->get_field_id('max'); ?>"><?php _e('Max rankings to show:', FP_PD); ?>
		<input class="widefat" id="<?php echo $this->get_field_id('max'); ?>" name="<?php echo $this->get_field_name('max'); ?>" type="text" value="<?php echo esc_attr($max); ?>" /></label></p>
		
		<p><label for="<?php echo $this->get_field_id('avatar'); ?>"><?php _e('Show Avatars:', FP_PD); ?>
		<input class="widefat" id="<?php echo $this->get_field_id('avatar'); ?>" name="<?php echo $this->get_field_name('avatar'); ?>" type="checkbox" value="1" <?php echo $avatar ? ' checked ' : ''; ?>" /></label></p>
		
		<p><label for="<?php echo $this->get_field_id('highlight'); ?>"><?php _e('CSS for current user:', FP_PD); ?>
		<input class="widefat" id="<?php echo $this->get_field_id('highlight'); ?>" name="<?php echo $this->get_field_name('highlight'); ?>" type="text" value="<?php echo esc_attr($highlight); ?>" /></label></p>
		
		<p><label for="<?php echo $this->get_field_id('url'); ?>"><?php _e('Full rankings page URL:', FP_PD); ?>
		<input class="widefat" id="<?php echo $this->get_field_id('url'); ?>" name="<?php echo $this->get_field_name('url'); ?>" type="text" value="<?php echo esc_attr($url); ?>" /></label></p>
		
		<p><label for="<?php echo $this->get_field_id('name'); ?>"><?php _e('Full rankings link name:', FP_PD); ?>
		<input class="widefat" id="<?php echo $this->get_field_id('name'); ?>" name="<?php echo $this->get_field_name('name'); ?>" type="text" value="<?php echo esc_attr($name); ?>" /></label></p>
<?php
	}
}

class FootballPredictionsWidget extends WP_Widget {
	
	function __construct() {
		$widget_ops = array('classname' => 'widget_user_'.FP_PD, 'description' => __('Display User Predictions of Football', FP_PD) );
		parent::__construct(FP_PD.'user', __('FP User Predictions', FP_PD), $widget_ops);
	}
	
	function widget($args, $instance) {
		// prints the widget
		extract($args);
		$title = apply_filters('widget_title', $instance['title']);
		$total = $instance['total'];
		$results = $instance['results'];
		
		if(is_user_logged_in()) {
		
			echo $before_widget;
			if ( $title )
			echo $before_title . $title . $after_title;
		
			require_once FP_ABSPATH.'includes/class-fp-reports.php';
			$r = new FootballReport();
		
			echo $r->user_predictions(1, $total, $results);
		
			echo $after_widget;
		}
	}
	
	function update($new_instance, $old_instance) {
		//save the widget
		$instance = $old_instance;
		$new_instance = wp_parse_args((array) $new_instance, array( 'title' => ''));
		$instance['title'] = strip_tags($new_instance['title']);
		$new_instance = wp_parse_args((array) $new_instance, array( 'total' => 0));
		$instance['total'] = strip_tags($new_instance['total']);
		$new_instance = wp_parse_args((array) $new_instance, array( 'results' => 0));
		$instance['results'] = strip_tags($new_instance['results']);
		return $instance;
	}
	
	function form($instance) {
		
		global $wpdb;
		
		//widgetform in backend
		$instance = wp_parse_args( (array) $instance, array( 'title' => '', 'total' => 0, 'results' => 0) );
		$title = $instance['title'];
		$total = $instance['total'];
		$results = $instance['results'];
?>
		<p><?php _e('Display User Predictions.', FP_PD); ?></p>
		<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', FP_PD); ?>
		<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" /></label></p>
		
		<p><label for="<?php echo $this->get_field_id('total'); ?>"><?php _e('Show Total:', FP_PD); ?>
		<input class="widefat" id="<?php echo $this->get_field_id('total'); ?>" name="<?php echo $this->get_field_name('total'); ?>" type="checkbox" value="1" <?php echo $total ? ' checked ' : ''; ?> /></label></p>
		
		<p><label for="<?php echo $this->get_field_id('results'); ?>"><?php _e('Show Results:', FP_PD); ?>
		<input class="widefat" id="<?php echo $this->get_field_id('results'); ?>" name="<?php echo $this->get_field_name('results'); ?>" type="checkbox" value="1" <?php echo $results ? ' checked ' : ''; ?> /></label></p>
<?php
	}
}

class FootballStandingsWidget extends WP_Widget {
	
	function __construct() {
		$widget_ops = array('classname' => 'widget_standings_'.FP_PD, 'description' => __('Display Standings of Football', FP_PD) );
		parent::__construct(FP_PD.'standings', __('FP Standings', FP_PD), $widget_ops);
	}
	
	function widget($args, $instance) {
		// prints the widget
		extract($args);
		$title = apply_filters('widget_title', $instance['title']);
		
		echo $before_widget;
		if ( $title )
		echo $before_title . $title . $after_title;
		
		require_once FP_ABSPATH.'includes/class-fp-reports.php';
		$r = new FootballReport();
		
		echo $r->group_tables(0, false, '100%', true);
		
		echo $after_widget;
	}
	
	function update($new_instance, $old_instance) {
		//save the widget
		$instance = $old_instance;
		$new_instance = wp_parse_args((array) $new_instance, array( 'title' => ''));
		$instance['title'] = strip_tags($new_instance['title']);
		return $instance;
	}
	
	function form($instance) {		
		global $wpdb;
		
		//widgetform in backend
		$instance = wp_parse_args( (array) $instance, array( 'title' => '') );
		$title = $instance['title'];
?>
		<p><?php _e('Display Football Standings.', FP_PD); ?></p>
		<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:', FP_PD); ?>
		<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" /></label></p>
<?php
	}
}