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

$today = get_today();
$start = get_persist_var('start', 0);
$team_id = get_myteam_id($login_id);
$is_supervisor = get_db_column('user.user', 'is_supervisor', "user_id = '$login_id'");
$tab = get_persist_var("oootab", "");
switch($tab){
	case 'tabs_vacation':
		if($is_supervisor){
			print("<label>Pending My Approve:</label>");
			show_vacation("my_approving", "approver = '$login_id' and (status = 0 or status = 2)", 2);
			print("My Approved:");
			show_vacation("my_approved", "approver = '$login_id' and (status = 1 or status = 3)", 2);
		}
		print("My vacation:");
		sum_vacation('sum_vacation', " user_id = '$login_id ' ");
		show_vacation("my_vacation", "user_id = '$login_id' and status != -1 ", 2);
		print("My Cancel:");
		show_vacation("cancel_vacation", "user_id = '$login_id' and status = 3", 2);
		if($is_supervisor){
			print("My Org Vacation:");
			$cond_owner = get_cond_by_author($login_id, 2, '`user_id`');
			show_vacation("vacation_org", " ($cond_owner ) and status != -1 ", 2);
		}
		break;
	case 'tabs_ot':
		print("My OT:");
		sum_ot('sum_ot', " user_id = '$login_id ' ");
		show_ot("my_ot", "user_id = '$login_id' and status != -1 ");
		if($is_supervisor){
			print("<label>Pending My Approve:</label>");
			show_ot("my_approving", "approver = '$login_id' and (status = 0 or status = 2)");
			print("My Approved:");
			show_ot("my_approved", "approver = '$login_id' and (status = 1 or status = 3)");
			print("My Org OT:");
			$cond_owner = get_cond_by_author($login_id, 2, '`user_id`');
			show_ot("sub_ot", " ($cond_owner ) and status != -1 ");
		}
		break;
	case 'tabs_onsite':
		print("My Onsite:");
		sum_onsite('sum_onsite', " user_id = '$login_id ' ");
		show_onsite("my_onsite", "user_id = '$login_id' and status != -1");
		if($is_supervisor){
			print("My Org Onsite:");
			$cond_owner = get_cond_by_author($login_id, 2, '`user_id`');
			show_onsite("onsite_org", " ($cond_owner ) and status != -1 ");
		}else{
			print("My Team Onsite:");
			show_onsite("team_onsite", "team_id = $team_id and status != -1");
		}
		break;
	case 'tabs_ooo':
		break;
	case 'tabs_summary':
		$cond_owner = get_cond_by_author($login_id, 2, '`user_id`');
		print("My Org Vacation:");
		sum_vacation("sum_vacation_org", " ($cond_owner ) and status != -1 ");
		print("My Org Onsite:");
		sum_onsite("sum_onsite_org", " ($cond_owner ) and status != -1 ");
		print("My Org OT:");
		sum_ot("sum_ot_org", " ($cond_owner ) and status != -1 ");
		break;
	case 'tabs_team':
		print("Today Leave:$today");
		show_vacation("", "team_id = $team_id and date(start_date) <= '$today' and date(end_date) >= '$today' and (status = 1 or status = 0)"); 
		print("Today Onsite:");
		show_onsite("team_onsite", "team_id = $team_id and date(start_date) <= '$today' and date(end_date) >= '$today' and (status != -1)"); 
		print("Last Week Onsite:");
		$lmonday = get_last_monday();
		$tmonday = get_current_monday();
		show_onsite("team_onsite", "team_id = $team_id and date(start_date) >= '$lmonday' and date(start_date) < '$tmonday'and (status != -1)"); 
		print("Team vacation:");
		sum_vacation("sum_team_vacation", "team_id = $team_id and status != -1 ");
		show_vacation("team_vacation", "team_id = $team_id and status != -1 ");
		print("Team Onsite:");
		sum_onsite("sum_team_onsite", "team_id = $team_id ");
		show_onsite("team_onsite", "team_id = $team_id and status != -1");
		break;
	case 'tabs_admin':
		if($login_id == 'xling' || $login_id == 'irene'){
			print("All vacation:");
			sum_vacation();
			show_vacation("all_vacation", "status != -1 ");
			print("All OT:");
			sum_ot();
			show_ot("all_ot", "status != -1");
			print("All onsite:");
			sum_onsite();
			show_onsite("all_onsite", "(status != -1)"); 
		}
		break;
	case 'tabs_today':
		print("Today Leave:$today");
		show_vacation("", "date(start_date) <= '$today' and date(end_date) >= '$today' and (status = 1 or status = 0)"); 
		print("Today Onsite:");
		show_onsite("team_onsite", "date(start_date) <= '$today' and date(end_date) >= '$today' and (status != -1)"); 
		print("Planned Leave in Future:");
		show_vacation("", "date(start_date) > '$today' and (status = 1 or status = 0)"); 
		break;
	default:
		print("Unknow action $tab");
		break;
}

?>
