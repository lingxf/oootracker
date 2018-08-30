<?php
include_once 'debug.php';
include_once 'db_connect.php';
$home_page='ooo.php';
$cur_name = session_name($web_name);
if($cur_name != $web_name)
	session_start();

print("<!DOCTYPE html>");

include_once 'myphp/disp_lib.php';
include_once 'myphp/mysf_lib.php';
include_once 'myphp/common.php';
include_once 'myphp/login_action.php';
include_once 'myphp/common_js.php';
include_once 'ooo_lib.php';

include 'main_menu.php';

//<script src="https://code.jquery.com/jquery-1.12.4.js"></script>
//<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
$today = get_today();
$start = get_persist_var('start', 0);
$team_id = get_myteam_id($login_id);
$is_supervisor = get_db_column('user.user', 'is_supervisor', "user_id = '$login_id'");
$oootab = get_persist_var('oootab', 'tabs_today');

?>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="jquery-ui/jquery-ui.min.css">
  <title>OOO management</title>
  <style>
    input.text { margin-bottom:12px; width:95%; padding: .4em; }
    fieldset { padding:0; border:0; margin-top:25px; }
    h1 { font-size: 1.2em; margin: .6em 0; }
    div#users-contain { width: 350px; margin: 20px 0; }
    div#users-contain table { margin: 1em 0; border-collapse: collapse; width: 100%; }
    div#users-contain table td, div#users-contain table th { border: 1px solid #eee; padding: .6em 10px; text-align: left; }
    .ui-dialog .ui-state-error { padding: .3em; }
    .validateTips { border: 1px solid transparent; padding: 0.3em; }
  </style>
<script src="jquery-3.3.1.min.js"></script>
<script src="jquery-ui/jquery-ui.min.js"></script>
<script>
$(document).ready(function(){
	$("#bt_submit").click(function(){
	  //$(this).hide();
	  on_submit('apply_vacation');
	});

});
$(function(){
	$("#tabs").removeAttr("hidden");
	login_id = "<?php echo $login_id?>";
	if(login_id == 'xling' || login_id == 'irene')
		$("#id_li_admin").removeAttr("hidden");
	$( "#tabs" ).tabs({
		beforeLoad:function(event, ui){
		}
	});
	$("#tabs").tabs("disable", "#tabs_ooo");
	oootab = "<?php echo $oootab ?>";
	tab = oootab.replace('#', '');
	$("div#"+tab + " p").html("Loading...");
	$("div#"+tab + " p").load("ooo_view.php?oootab="+tab, function(response, status, xhr){
		if(status == "success"){
			register_action();
		}
	});

	for(i = 0; i < 6; i++){
		href = $("#tabs a:eq("+i+")").attr("href");
		if(tab == href.replace("#", "" )){
			$("#tabs").tabs("option", "active", i);
		}
	}

	record_id = $("#record_id").val();
	if(record_id == 0)
		action = 'apply_vacation';
	else
		action = 'update_vacation';

	function on_submit()
	{
		record_id = $("#record_id").val();
		record_type = $("#record_type").val();
		if(record_id == 0)
			action = 'apply_vacation';
		else
			action = 'update_vacation';
		if(record_type == 1)
			type = $("#vacation_type").val();
		else if(record_type == 2)
			type = $("#ot_type").val();
		else if(record_type == 3)
			type = $("#onsite_type").val();

		user_id = $("#user_id").val();
		start = $("#text_start").val();
		start_time = $("#start_time").val();
		cctam = $("#text_cctam").val();
		is_cchr = $('input#id_cchr:checked').val() == 'on' ? 1:0;
		end = $("#text_end").val();
		start_pm = $('input:radio[name="start_pm"]:checked').val();
		end_pm = $('input:radio[name="end_pm"]:checked').val();
		console.log(start_pm, end_pm);

		nocase = $("#nocase").val();
		comments = $("#id_comments").val();
		hours = $("#text_hours").val();
		customer = $("#text_customer").val();
		priv_comments = $("#id_priv_comments").val();

		if((record_type == 2 || record_type == 3) && customer == ''){
			alert("Please choose or fill in customer");
			return false;
		}
		if(record_type == 1 && type == 'sk' ) {
			if(comments == '' && priv_comments == '' ){
				alert("Please fill in comment or priviate_comments for the Sick Leave");
				return false;
			}
		}
		if(record_type == 2 ) {
			var m = Date.parse(start + ' ' + start_time + ":00");
			var d = new Date();
			var now = d.getTime();
			if(m < now){
				//priv_comments = prompt("Please submit OT applying before OT happen unless special reason");
			}
			if(comments == ''){
				//alert("Please fill in comment for the OT, what work you will do in OT");
				//return false;
			}
		}

		$( "#my_vacation1 tbody" ).append( "<tr>" +
					"<td>" + user_id + "</td>" +
					"<td>" + type + "</td>" +
					"<td>" + start + "</td>" +
					"</tr>" );
		dialog.dialog( "close" );

		url = "ooo_action.php?action="+action;
		url += "&record_id="+record_id;
		url += "&record_type="+record_type;
		url += "&user_id="+user_id;
		url += "&type="+type;
		url += "&start_time="+start_time;
		url += "&hours="+hours;
		url += "&customer="+customer;
		url += "&ostart_date="+start;
		url += "&oend_date="+end;
		url += "&start_pm="+start_pm;
		url += "&end_pm="+end_pm;
		url += "&comments="+comments;
		url += "&nocase="+nocase;
		url += "&cctam="+cctam;
		url += "&is_cchr="+is_cchr;
		url += "&priv_comments="+priv_comments;
		console.log(url);
		load_url(url);
		//window.location.reload();
		setTimeout("window.location.href =\"ooo.php\"", 3000);
	}

	$("#text_start").datepicker();
	$("#text_start").datepicker("option", "dateFormat","yy-mm-dd");
	$("#text_start").val("<?php echo $today?>");
	
	$("#text_end").datepicker();
	$("#text_end").datepicker("option", "dateFormat","yy-mm-dd");
	$("#text_end").val("<?php echo $today?>");

	$("#sel_hours").change(function(){
		$("#text_hours").val($(this).val());
	});
	$("#sel_customer").change(function(){
		$("#text_customer").val($(this).val());
	});

    dialog = $( "#dialog-form" ).dialog({
      autoOpen: false,
      height: 450,
      width: 650,
      modal: true,
      buttons: {
        "Submit": on_submit,
        Cancel: function() {
          dialog.dialog( "close" );
        }
      },
      close: function() {
        form[ 0 ].reset();
        //allFields.removeClass( "ui-state-error" );
      },
	  open:function(){
	  	record_type = $("#record_type").val();

    	$("input.end_pmr" ).checkboxradio({icon:false});
    	$("input.start_pmr" ).checkboxradio({icon:false});
		$(".overtime").attr("hidden", "true");
		$(".vacation").attr("hidden", "true");
		$(".onsite").attr("hidden", "true");
    	$("input.end_pmr" ).checkboxradio("destroy");
    	$("input.start_pmr" ).checkboxradio("destroy");
		if(record_type == 1){
			$(".vacation").removeAttr("hidden");
    		$("input.end_pmr" ).checkboxradio({icon:false});
    		$("input.start_pmr" ).checkboxradio({icon:false});
		}else if(record_type == 2){
			$(".overtime").removeAttr("hidden");
		}else{
			$(".onsite").removeAttr("hidden");
    		$("input.start_pmr" ).checkboxradio({icon:false});
    		$("input.end_pmr" ).checkboxradio({icon:false});
		}
	  }
    });

    form = dialog.find( "form" ).on( "submit", function( event ) {
      event.preventDefault();
      on_submit();
    });

/*
	$("#tabs").tabs({beforeActivate:function(event, ui){
		hf = ui.newTab.prevObject.attr("href");
		tab = hf.replace('#', '');
		$(hf + " p").html("Loading...").load("ooo_view.php?oootab="+tab);	
		}
	})
*/
	var login_id = "<?php echo $login_id?>";
    $( "input#apply_new" ).click(function() {
		if(login_id == 'guest'){
			alert("Please login first!")
			return false;
		}
		$("#record_id").val(0);
		$("#record_type").val(1);
		dialog.dialog("option", "title", "Apply Vacation");
      	dialog.dialog( "open" );
	});

    $( "input#apply_ot" ).click(function() {
		if(login_id == 'guest'){
			alert("Please login first!")
			return false;
		}
		alert("As HR does not agree to use tool, please still use old way of applying OT by email, you can only record OT here");
		return false;
		$("#record_id").val(0);
		$("#record_type").val(2);
		dialog.dialog("option", "title", "Apply OT");
      	dialog.dialog( "open" );
	});

    $( "input#add_ot" ).click(function() {
		if(login_id == 'guest'){
			alert("Please login first!")
			return false;
		}
		$("#record_id").val(0);
		$("#record_type").val(2);
		dialog.dialog("option", "title", "Add OT Record");
      	dialog.dialog( "open" );
	});


  	$( "input#apply_onsite" ).click(function() {
		if(login_id == 'guest'){
			alert("Please login first!")
			return false;
		}
		$("#record_id").val(0);
		$("#record_type").val(3);
		dialog.dialog("option", "title", "Add Onsite");
      	dialog.dialog( "open" );
	});

	function register_action()
	{
    	$( "input.bt_update" ).click(function() {
			comments = $(this).parents("td").siblings("td").eq(10).text();
			$("#record_id").val($(this).attr('record_id'));
			record_type = $(this).attr('record_type');
			$("#record_type").val(record_type);
			$("#user_id").val($(this).attr('user_id'));
			$("#text_start").val($(this).attr('start_date'));
			$("#id_comments").val(comments);
			if(record_type == 1){
				$("#vacation_type").val($(this).attr('vacation_type'));
				$("#text_end").val($(this).attr('end_date'));
				$("#start_pm").val($(this).attr('start_pm'));
				$("#end_pm").val($(this).attr('end_pm'));
				$("#id_priv_comments").val($(this).attr('priv_comments'));
				dialog.dialog("option", "title", "Update Vacation");
			}else if(record_type == 2){
				$("#ot_type").val($(this).attr('vacation_type'));
				$("#start_time").val($(this).attr('start_time'));
				$("#text_hours").val($(this).attr('hours'));
				$("#text_customer").val($(this).attr('customer'));
				dialog.dialog("option", "title", "Update OT");
			}
    	  	dialog.dialog( "open" );
    	});

    	$( "input.bt_cancel" ).click(function() {
			if(confirm("Do you really want to cancel?")){
				url = "ooo_action.php?action=cancel_vacation&record_id="+$(this).attr('record_id');
				$(this).parents("tr").remove();
				load_url(url);
			}
    	});
    	$( "input.bt_apply_cancel" ).click(function() {
			if(confirm("Do you really want to cancel?")){
				url = "ooo_action.php?action=apply_cancel_vacation&record_id="+$(this).attr('record_id');
				//$(this).parents("table").parent().siblings("label").load(url);
				//window.location.reload();
				load_url_reload(url);
			}
    	});

    	$( "input.bt_approve" ).click(function() {
			url = "ooo_action.php?action=approve_vacation&record_id="+$(this).attr('record_id');
			load_url_reload(url);
    	});

    	$( "input.bt_reject" ).click(function() {
			url = "ooo_action.php?action=reject_vacation&record_id="+$(this).attr('record_id');
			load_url_reload(url);
    	});


    	$( "input.bt_approve_cancel" ).click(function() {
			url = "ooo_action.php?action=approve_cancel_vacation&record_id="+$(this).attr('record_id');
			load_url_reload(url);
    	});

    	$( "input.bt_reject_cancel" ).click(function() {
			url = "ooo_action.php?action=reject_cancel_vacation&record_id="+$(this).attr('record_id');
			load_url_reload(url);
    	});
	}

	$("#tabs a[href]").click(function(){
		hf = $(this).attr("href");
		tab = hf.replace('#', '');
		$("div#"+tab + " p").html("Loading...");
		$("div#"+tab + " p").load("ooo_view.php?oootab="+tab, function(response, status, xhr){
			if(status == "success"){
				register_action();
			}
		});
	});
});

</script>
</head>

<div hidden id='dialog-form' title='Apply' >
<form>
<fieldset>
<p class='vacation '>Note:This tool is to replace email approve/notification, you still need to fill QTime</p>
<p class='overtime'>Note:This tool is for record OT, supervisor need to reply the email and CC HR to approve</p>
<p>
<input type='hidden' id='record_id' value='0'></input>
<input type='hidden' id='record_type' value='1'></input>
Employee Id <input type='textbox' style="width:50" id='user_id' class='input' value='<?php print $login_id?>' ></input>
<label class='vacation'>Vacation Type</label>
<select class='vacation' id="vacation_type">
<option value='al'>Annual Leave</option>
<option value='sk'>Sick Leave</option>
<option value='ed'>EDH</option>
<option value='sf'>Work Shift</option>
<option value='ml'>Marriage Leave</option>
<option value='pl'>Paternity Leave</option>
<option value='bl'>Bereavement Leave</option>
<option value='tb'>Team Building</option>
</select>
<label class='overtime'>OT Type</label>
<select class='overtime' id="ot_type">
<option value='wd'>Work Day</option>
<option value='we'>Weekend</option>
<option value='hd'>Holiday</option>
</select>
<label class='onsite'>Onsite Type</label>
<select class='onsite' id="onsite_type">
<option value='lc'>Local</option>
<option value='tr'>Travel</option>
<option value='la'>Lab</option>
</select>
<label hidden class='overtime1' for="id_cchr">Need suppervisor reply and CC china.hr after getting mail</label>
<input class='overtime1' hidden type='checkbox' id='id_cchr'  ></input>
</p>
<p>
<label class='overtime onsite'>Customer</label>
<select class='overtime onsite' id="sel_customer">
<option value=""      >Other</option>
<option value="General"   >General</option>
<option value="Xiaomi"   >Xiaomi</option>
<option value="OPPO"     >OPPO</option>
<option value="BBK"     >BBK</option>
<option value="Oneplus"     >Oneplus</option>
<option value="Huawei"   >Huawei</option>
<option value="ZTE"   >ZTE</option>
<option value="Lenovo"   >Lenovo</option>
<option value="Longcheer"   >Longcheer</option>
<option value="Wingtech"   >Wingtech</option>
<option value="Huaqin"   >Huaqin</option>
<option value="Meitu"      >Meitu</option>
<option value="Meizu"      >Meizu</option>
</select>
<input id='text_customer' style="width:100" class='input overtime onsite' value='' ></input>
<label class='onsite' for="nocase">No Case</label>
<input class='onsite' type='checkbox' id='nocase' value='0' ></input>
</p>
<p>
<label class='onsite' for="text_cctam">CC TAM(Only user id, seperated by comma)</label>
<input class='onsite' type='text' id='text_cctam' value='' ></input>
</p>
<p>Start <input id='text_start' style="width:100" class='input' value='<?php print $today?>' ></input>
<label class='vacation onsite' for="start_pm1">AM</label>
<input class='vacation onsite start_pmr' type="radio" value="0" checked name="start_pm" id="start_pm1">
<label class='vacation onsite' for="start_pm2">PM</label>
<input class='vacation onsite start_pmr' type="radio" value="1" name="start_pm" id="start_pm2">
<!--
<select class='vacation onsite' id="start_pm" >
<option value='0'>AM</option>
<option value='1'>PM</option>
</select>
-->
<select class='overtime ' id="start_time" >
<?php
	for($i = 18; $i <= 24; $i++)
		print("<option value='$i:00'>$i:00</option>");
	for($i = 9; $i <= 17; $i++)
		print("<option value='$i:00'>$i:00</option>");
	for($i = 0; $i <= 8; $i++)
		print("<option value='$i:00'>$i:00</option>");
?>
</select>
<label class='overtime' id="label_hours" for="text_hours">Hours</label>
<select class='overtime' id="sel_hours">
<option value='2'>2</option>
<option value='3'>3</option>
<option value='4'>4</option>
<option value='5'>5</option>
</select>
<input style="width:30" class='overtime' type='textbox' id='text_hours' class='input' value='2'></input>


<br>
<label class='vacation onsite' id="label_end">End&nbsp;&nbsp;</label><input class='vacation onsite' style="width:100" type='textbox' id='text_end' class='input' value='<?php print $today?>'></input>
<label class='vacation onsite' for="end_pm1">AM</label>
<input class='vacation onsite end_pmr' type="radio" value="0" checked name="end_pm" id="end_pm1">
<label class='vacation onsite end_pmr' for="end_pm2">PM</label>
<input class='vacation onsite end_pmr' type="radio" checked value="1" name="end_pm" id="end_pm2">
<!--
<select class='vacation onsite' id="end_pm" >
<option value='0'>AM</option>
<option value='1' selected>PM</option>
</select>
-->
</p>
Comments:
<textarea id='id_comments' warp='soft' style='width:460; padding: 2px; border: 1px solid black' type='textarea' rows='2' maxlength='2000' cols='280' value=''></textarea>
<br>
<label class='vacation'> Private Comments: </label>
<textarea class='vacation' id='id_priv_comments' warp='soft' style='width:460; padding: 2px; border: 1px solid black' type='textarea' rows='2' maxlength='2000' cols='280' value=''></textarea>
<br>
</fieldset>
</form>
</div>

<div hidden id="tabs">
  <ul>
    <li><a id="head_tabs_today"    href="#tabs_today">Today</a></li>
    <li><a id="head_tabs_vacation" href="#tabs_vacation">Vacation</a></li>
    <li><a id="head_tabs_ot"       href="#tabs_ot">OT</a></li>
    <li><a id="head_tabs_onsite"   href="#tabs_onsite">Onsite</a></li>
    <li><a id="head_tabs_team"     href="#tabs_team">Team</a></li>
    <li><a id="head_tabs_summary"     href="#tabs_summary">Summary</a></li>
    <li > <a id="head_tabs_admin"     href="#tabs_admin">Admin</a></li>
  </ul>
<div id="tabs_vacation">
<input id='apply_new' type='button' value='Apply Vacation'></input>
<label></label>
<p>
</p>
</div>
<div class='oootab' id="tabs_ot">
<input id='apply_ot' hidden type='button' value='Apply OT'></input>
<input id='add_ot' type='button' value='Record OT'></input>
<label></label>
<p>
</p>
</div>
<div class='oootab' id="tabs_onsite">
<input id='apply_onsite' type='button' value='Add Onsite'></input>
<label></label>
<p>
</p>
</div>
<div id="tabs_ooo">
</div>
<div class='oootab' id="tabs_team">
<p>
</p>
</div>
<div class='oootab' id="tabs_summary">
<p>
</p>
</div>
<div class='oootab' id="tabs_admin"><p></p></div>
<div class='oootab' id="tabs_today">
<input id='apply_new' type='button' value='Apply Vacation'></input>
<input id='apply_ot' hidden type='button' value='Apply OT'></input>
<input id='add_ot' type='button' value='Record OT'></input>
<input id='apply_onsite' type='button' value='Add Onsite'></input>
<p>
</p>
</div>
</div>
