<?php

include_once 'debug.php';
include_once 'db_connect.php';
$cur_name = session_name($web_name);
if($cur_name != $web_name)
	session_start();

include_once 'ooo_lib.php';
include_once 'myphp/common.php';
include_once 'myphp/mysf_lib.php';
include_once 'myphp/login_action.php';

$record_id = $argv[1];
$record_type = $argv[2];
$action = $argv[3];
$today = get_today();
$tomorrow = get_tomorrow();

if($action == 'today'){
	$team_id = $argv[4];
	if($team_id == 0)
		$team_cond = ' 1 ';
	else
		$team_cond = "team_id = $team_id" ;
	print("Today Leave:$today");
	$rows = show_vacation("", "$team_cond and date(start_date) <= '$today' and date(end_date) >= '$today' and (status = 1 or status = 0)"); 
	print("Today Onsite:");
	$rows += show_onsite("team_onsite", "$team_cond and date(start_date) <= '$today' and date(end_date) >= '$today' and (status != -1)"); 
	$rows2 = query_record("$team_cond and date(start_date) = '$today' and date(end_date) >= '$today' and (status = 1 or status = 0)", 1); 
	$rows2 += query_record("$team_cond and date(start_date) = '$today' and date(end_date) >= '$today' and (status = 1 or status = 0)", 3); 
	if($rows2 == 0)
		exit(0);
	else
		exit($rows);
}

if($action == 'tomorrow'){
	$team_id = $argv[4];
	if($team_id == 0)
		$team_cond = ' 1 ';
	else
		$team_cond = "team_id = $team_id" ;
	print("Tomorrow Leave:$today");
	$rows = show_vacation("", "$team_cond and date(start_date) <= '$tomorrow' and date(end_date) >= '$tomorrow' and (status = 1 or status = 0)"); 
	print("Tomorrow Onsite:");
	$rows += show_onsite("team_onsite", "$team_cond and date(start_date) <= '$tomorrow' and date(end_date) >= '$tomorrow' and (status != -1)"); 
	$rows2 = query_record("$team_cond and date(start_date) = '$tomorrow' and date(end_date) >= '$tomorrow' and (status = 1 or status = 0)", 1); 
	$rows2 += query_record("$team_cond and date(start_date) = '$tomorrow' and date(end_date) >= '$tomorrow' and (status = 1 or status = 0)", 3); 
	if($rows2 == 0)
		exit(0);
	else
		exit($rows);
}

if($action == 'last_week'){
	$team_id = $argv[4];
	if($team_id == 0)
		$team_cond = ' 1 ';
	else
		$team_cond = "team_id = $team_id" ;
	switch($record_type){
		case 2:
			print("Last Week on site summary:");
			$rows = sum_onsite("onsite", " $team_cond and date(start_date) < '$today' and date(start_date) >= date_sub(curdate(), interval 7 day)  ");
			print("Last Week on site list:");
			$rows += show_onsite("onsite", " $team_cond and date(start_date) < '$today' and date(start_date) >= date_sub(curdate(), interval 7 day) and (status != -1) ");
			exit($rows);
	}
}

switch($record_type){
	case 1:
		show_vacation("my_vacation", "record_id = '$record_id'", 1);
		break;
	case 2:
		if($action == 'applying' || $action == 'reject')
			show_ot("my_ot",  "record_id = '$record_id'");
		else
			show_ot("my_ot",  "record_id = '$record_id'", 1);
		break;
	case 3:
		show_onsite("my_onsite", "record_id = '$record_id'", 1);
		break;
	case 4:
		break;
	default:
		print("Unknow record_type");
		break;
}

?>
