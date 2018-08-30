<?php

include_once 'debug.php';
include_once 'db_connect.php';
$cur_name = session_name($web_name);
if($cur_name != $web_name)
	session_start();

include_once 'ooo_lib.php';
include_once 'myphp/common.php';
include_once 'myphp/login_action.php';

$user_id = get_url_var('user_id', '');
$type = get_url_var('type', 0);
$start_date = get_url_var('ostart_date', '');
$start_pm = get_url_var('start_pm', 0);
$end_pm = get_url_var('end_pm', 1);
$end_date = get_url_var('oend_date', '');
$comments = get_url_var('comments', '');
$priv_comments = get_url_var('priv_comments', '');
$record_id = get_url_var('record_id', '');
$record_type = get_url_var('record_type', '');
$hours = get_url_var('hours', '');
$customer = get_url_var('customer', '');
$start_time = get_url_var('start_time', '');
$nocase = get_url_var('nocase', 0);
$approve_magic = get_url_var('approve_magic', '');
$cctam = get_url_var('cctam', '');
$is_cchr = get_url_var('is_cchr', 0);
$action = get_url_var('action', '');

/*
stauts
0 applying
1 approved
2 cacel apply
3 cancel approved
4 not defined
5 rejected 
-1 cancelled
*/

switch($action){
	case 'apply_vacation':
		if($record_type == 1){
			$hours = strptime($start_date, "%F");
			$hours = cal_hours($start_date, $end_date, $start_pm, $end_pm);
			apply_vacation($user_id, $type, $start_date, $end_date, $start_pm, $end_pm, $hours, $comments, $priv_comments);
		}else if($record_type == 2){
			apply_ot($user_id, $type, $customer, $start_date, $start_time, $hours, $comments);
		}else if($record_type == 3){
			$hours = cal_hours($start_date, $end_date, $start_pm, $end_pm);
			apply_onsite($user_id, $type, $customer, $start_date, $end_date, $start_pm, $end_pm, $hours, $comments, $nocase);
		}
		break;

	case 'update_vacation':
		if($record_type == 1){
			$hours = cal_hours($start_date, $end_date, $start_pm, $end_pm);
			update_vacation($record_id, $user_id, $type, $start_date, $end_date, $start_pm, $end_pm, $hours, $comments, $priv_comments);
		}else if($record_type == 2){
			update_ot($record_id, $user_id, $type, $start_date, $start_time, $hours, $comments);
		}
		break;

	case 'reject_vacation':
		$rows = set_db_columns("ooo.qtime", "status = 5, approve_date = now() ", " record_id = $record_id and (approver = '$login_id' or approve_magic = '$approve_magic') and status = 0 ");
		if($rows == 1){
			print("ok$rows record rejected");
			mail_approve($record_id, "rejected");
		}else
			print("This record's status already change or You have no permission to change the status");
		break;

	case 'approve_vacation':
		$rows = set_db_columns("ooo.qtime", "status = 1, approve_date = now() ", " record_id = $record_id and (approver = '$login_id' or approve_magic = '$approve_magic') and status = 0 ");
		if($rows == 1){
			print("ok$rows record approved");
			mail_approve($record_id, "approved");
		}else
			print("This record's status already change or You have no permission to change the status");
		break;

	case 'apply_cancel_vacation':
		$rows = set_db_column("ooo.qtime", 'status', 2, " record_id = $record_id and user_id = '$login_id' and status = 1 ");
		if($rows == 1){
			print("ok$rows cancel request submit, wait for approve");
			mail_approve($record_id, 'applying_cancel'); 
		}else
			print("This record's status already change or You have no permission to change the status");
		break;

	case 'cancel_vacation':
		$rows = set_db_column("ooo.qtime", 'status', -1, " record_id = $record_id and user_id = '$login_id' and (status = 0 or status = 4) ");
		if($rows == 1)
			print("ok");
		else
			print("This record's status already change or You have no permission to change the status");
		break;

	case 'reject_cancel_vacation':
		$rows = set_db_column("ooo.qtime", 'status', 1, " (approver = '$login_id' or approve_magic = '$approve_magic') and status = 2 ");
		if($rows == 1){
			print("ok$rows record rejected");
			mail_approve($record_id, "rejected", 1);
		}else
			print("This record's status already change or You have no permission to change the status");
		break;

	case 'approve_cancel_vacation':
		$rows = set_db_column("ooo.qtime", 'status', 3, " (approver = '$login_id' or approve_magic = '$approve_magic') and status = 2 ");
		if($rows == 1){
			print("ok$rows record approved");
			mail_approve($record_id, "approved", 1);
		}else
			print("This record's status already change or You have no permission to change the status");
		break;

	default:
		print("Unknow action $action");
		break;
}
if($approve_magic != ''){
	print('<script type="text/javascript">setTimeout("window.location.href=\'ooo.php\'",3000);</script>');
}

?>
