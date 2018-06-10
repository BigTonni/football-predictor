<?php
if (!defined('ABSPATH')) {
    exit;
}

if(isset($_GET['fp']) && sanitize_text_field($_GET['fp']) == 'scores') {
	add_filter('the_content','fp_template_scores_content');
}

if(isset($_GET['fp']) && sanitize_text_field($_GET['fp']) == 'predictions') {
	add_filter('the_content','fp_template_predictions_content');
}

function fp_template_scores_content($match_id = NULL, $limit = NULL, $user_id = -1) {
	
	global $wpdb;
	
	require_once FP_ABSPATH.'includes/class-fp-reports.php';
	$r = new FootballReport();
	
	$match_id = isset($_GET['match_id']) ? sanitize_text_field($_GET['match_id']) : $match_id;
	
	// Performs method show_scores() that will call the method score_match() to show the guesses
	return $r->show_scores(array('match_id'=>$match_id));
}

function fp_template_predictions_content($user = NULL, $schedule_id = NULL, $month = NULL) {
	
	global $wpdb;
	
	require_once FP_ABSPATH.'includes/class-fp-reports.php';
	$r = new FootballReport();
	
	$user = isset($_GET['user']) ? sanitize_text_field($_GET['user']) : $user;
	
	// Performs method show_user_predictions() that will call the method score_match() to show the guesses
	return $r->show_user_predictions(array('user'=>$user));
}

//Debug
if (!function_exists('vardump')) {
    function vardump( $string ) {
        var_dump( '<pre>' );
        var_dump( $string );
        var_dump( '</pre>' );
    }
}