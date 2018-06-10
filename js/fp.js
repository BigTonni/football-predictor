/**
 * @package FootballCup
 * @author BigTonni
 */

jQuery(document).ready(function($) {

	/**
	 * Make a UTC date
	 */
	function fp_utc_date(utc, offset_mins) {
		var d = new Date();
		var year = parseInt(utc.substr(0,4), 10);
		var month = parseInt(utc.substr(4,2), 10);
		month = month - 1;
		var day = parseInt(utc.substr(6,2), 10);
		var hour = parseInt(utc.substr(8,2), 10);
		var min = parseInt(utc.substr(10,2), 10);
		min = min + offset_mins;
		d.setUTCFullYear(year, month, day);
		d.setUTCHours(hour, min, 0, 0);
		
		return d;
	}
	
	/**
	 * Format UTC to Client time using browsers locale.
	 */
	function fp_localtime(utc, offset_mins, date_only) {
		var d = fp_utc_date(utc, offset_mins);
		var str = '';
		if (date_only) {
			str = d.toLocaleDateString();
		} else {
			str = d.toLocaleDateString() + ' ' + d.toLocaleTimeString();
			// remove seconds
			if (str.length > 3 &&  str.substr(str.length-3, 3) == ':00') {
				str = str.substr(0, str.length-3 );
			} 
			if (str.length > 6 &&  str.substr(str.length-6, 3) == ':00') {
				str = str.substr(0, str.length-6 ) + str.substr(str.length-3, 3 );
			} 
		}
		return str;
	}
	
	/**
	 * Format UTC to BRST or Client time using browsers locale.
	 */
	function fp_match_time(element) {
		var utc = $(element).attr('utc');  // YYYYMMDDHHMM format
		if (utc == undefined) return;
		var tzoffset = $(element).attr('tzoffset');
		if (tzoffset == undefined) return;
		
		// Workout the timezone offset for this date
		var foo_d = fp_utc_date(utc, 0);
		var foo_offset = -foo_d.getTimezoneOffset();
		
		tzoffset = tzoffset * 60;
		var date_only = $(element).is('.date_only');
		
		// Note: toLocale...() adds the browser TZ offset so we remove it first !
		$(element).html(fp_localtime(utc, tzoffset - foo_offset, date_only));
	}
	
	/**
	 * Set each kickoff to localized date/time format in BRST time
	 */
	$('.fp_kickoff_time').each(function() {
		var utc = $(this).attr('utc');
		if (utc == undefined) return;
		var date_only = $(this).is('.date_only');
		$(this).html(fp_localtime(utc, 0, date_only));
	});

	/**
	 * Toggle between Match Local and Client time.
	 */
	
	// Switch from match local to client time
	$('#tzLocal').click(function() {
		$('.fp_kickoff_time').each(function() {
			var utc = $(this).attr('utc');
			if (utc == undefined) return;
			
			$(this).addClass('tzClient');
			var date_only = $(this).is('.date_only');
			$(this).html(fp_localtime(utc, 0, date_only));  // Client time no TZ offset
		});
		$(this).hide();
		$('#tzClient').show();
		return false;
	});
	
	// Switch from client to match local
	$('#tzClient').click(function() {
		$('.fp_kickoff_time').each(function() { 
			fp_match_time(this);
			$(this).removeClass('tzClient');
		});
		$(this).hide();
		$('#tzLocal').show();
		return false;
	});

	/**
	 * Save current form
	 */
	$('form.fp_user_form').submit(function() {
		var data = $(this).serialize();
		var msg_id = $(this).find("input[name='msg_id']");  // get element id for message
		
		$.ajax({
			type:"POST",
			cache:false,
			url: FPScript.ajax_url,
			dataType:"json",
			data:"action=footballpredictor_ajax&"+data,
			success: function(msg){
				// Using the hidden field for this form display message and scroll
				$('#'+msg_id.val()).html(msg.notice);
				$('.fp_user_pred_widget').replaceWith(msg.preds);
				$('html, body').animate({scrollTop: $('#'+msg_id.val()).offset().top}, 500);
			},
			error: function(xml, text, error) {
				alert("Error" + xml + text + error);
			}
		});
		
		return false;
	});
	
	$('.zebra tr.fp-row:even').addClass('even');
	$('.zebra tr.fp-row:odd').addClass('alt');
	
});

/*
Author:		Robert Hashemian (http://www.hashemian.com/)
Modified by:	Munsifali Rashid (http://www.munit.co.uk/)
*/

/*
Modified by: Ian Haycox
Countdown timer to next prediction deadline
*/
function fp_countdown(obj)
{
	this.obj		= obj;
	this.Div		= "clock1";
	this.BackColor		= "white";
	this.ForeColor		= "black";
	this.TargetDate		= "12/31/2020 5:00 AM";
	this.ServerDate		= "12/31/2020 5:00 AM";
	this.DisplayFormat	= "%%D%%d, %%H%%h, %%M%%m, %%S%%s.";
	this.FinishStr          = "Too Late";
	this.CountActive	= true;
	
	this.DisplayStr;
	
	this.Calcage		= fp_cd_Calcage;
	this.CountBack		= fp_cd_CountBack;
	this.Setup		= fp_cd_Setup;
}

function fp_cd_Calcage(secs, num1, num2)
{
	s = ((Math.floor(secs/num1))%num2).toString();
	if (s.length < 2) s = "0" + s;
	return (s);
}
function fp_cd_CountBack(secs)
{
	if (secs < 0) {
	  if (document.getElementById(this.Div) != null) {
		  document.getElementById(this.Div).innerHTML = this.FinishStr;
	  }
	  return;
	}
	
	this.DisplayStr = this.DisplayFormat.replace(/%%D%%/g,	this.Calcage(secs,86400,100000));
	this.DisplayStr = this.DisplayStr.replace(/%%H%%/g,		this.Calcage(secs,3600,24));
	this.DisplayStr = this.DisplayStr.replace(/%%M%%/g,		this.Calcage(secs,60,60));
	this.DisplayStr = this.DisplayStr.replace(/%%S%%/g,		this.Calcage(secs,1,60));
	
	if (document.getElementById(this.Div) != null) {
	  document.getElementById(this.Div).innerHTML = this.DisplayStr;
	}
	if (this.CountActive) setTimeout(this.obj +".CountBack(" + (secs-1) + ")", 990);
}
function fp_cd_Setup()
{
	var dthen	= new Date(this.TargetDate);
        var dnow	= new Date(this.ServerDate);
	ddiff		= new Date(dthen-dnow);
	gsecs		= Math.floor(ddiff.valueOf()/1000);
	this.CountBack(gsecs);
}
