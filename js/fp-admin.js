/**
 * @package Football
 */
 
jQuery(document).ready(function($) {
	
	/**
	 * Tabs for menu options
	 */
	$('#fp_tabs').tabs();
	
	$('#selectallprediction').click(function () {
		var state = this.checked;
		$("#listpredictions input[type='checkbox']:not([disabled='disabled'])").attr('checked', state);
	});

	$('#selectallmatch').click(function () {
		var state = this.checked;
		$("#scorematches input[type='checkbox']:not([disabled='disabled'])").attr('checked', state);
	});

});