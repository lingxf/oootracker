<?php

function cal_hours($start_date, $end_date, $start_pm, $end_pm)
{
	$start = DateTime::createFromFormat('Y-m-d', $start_date);
	if($start === false)
		die("wrong start_date $start_date");
	$end = DateTime::createFromFormat('Y-m-d', $end_date);
	if($end === false)
		die("wrong end_date $end_date");
	$intv = $start->diff($end);
	$hours = ($intv->d + 1) * 8;
	if($start_pm == 1)
		$hours -= 4;
	if($end_pm == 0)
		$hours -= 4;
	return $hours;
}

function cal_endtime($start_time, $hours)
{
	$start = DateTime::createFromFormat('H:i', $start_time);
	if($start === false)
		die("wrong start_date $start_date");
	$interval = new DateInterval('PT'.$hours.'H');
	$start->add($interval);
	$end_time = $start->format("H:i:s");
	return $end_time;
}

function get_user_email($user_id)
{
	$to = "$user_id@qti.qualcomm.com";
	if($user_id == 'tommyz')
		$to = "$user_id@qualcomm.com";
	return $to;
}

function mail_approve($record_id, $action, $cancel = 0)
{
	global $cctam, $is_cchr, $login_id;
	$sql = "select ".
	"record_id, record_type, user_id, user_type, team_leads, priv_comments, name, a.type as type, date(start_date) as start, start_pm as sp, date(end_date) as end, end_pm as ep, apply_date, approve_date, approver, status, comments, submitter, approve_magic, status as action ".
	" from ooo.qtime a left join user.user b using(user_id)";
	$sql .= "where record_id = $record_id ";
	$res = read_mysql_query($sql);

	while($rows = mysql_fetch_array($res)) {
		$user_id = $rows['user_id'];
		$user_type = $rows['user_type'];
		$name = $rows['name'];
		$supervisor = $rows['approver'];
		$team_leads = $rows['team_leads'];
		$record_type = $rows['record_type'];
		$submitter = $rows['submitter'];
		$status = $rows['status'];
		$approve_magic = $rows['approve_magic'];
	}

	exec("php ooo_record.php $record_id $record_type $action", $output);
	$message = "<html>\r\n";
	$message .= "<meta http-equiv='Content-Type' content='text/html; charset=utf-8'>\r\n";
	if($action != 'record')
		$message .= "<a href='https://go/ceooo'>go/ceooo</a><br>\r\n";
	foreach($output as $line){
		$message .= $line . "\r\n"; 
	}
	$url = $_SERVER['SERVER_NAME'] . "/report";

	$record_name = array('None', 'Vacation', 'OT', 'Onsite');
	$record = $record_name[$record_type];
	$from = "";
	if($action == 'applying' || $action == 'record'){
		$to = get_user_email($supervisor);
		$cc = '';
		if($record_type == 3){
			$subject = "$record record for $name<$user_id> submitted by $submitter";
			$cc = get_user_email($user_id);
			if($cctam != ''){
				$tams = explode(',', $cctam);
				foreach($tams as $tam){
					$cc .= ",$tam@qti.qualcomm.com";
				}
			}
			if($supervisor != $team_leads)
				$cc .= ",".get_user_email($team_leads);
		}else if($record_type == 2 && $user_type == 1){
			$subject = "$record record for $name<$user_id> submitted by $submitter";
			$cc = get_user_email($user_id);
			if($supervisor != $team_leads)
				$cc .= ",".get_user_email($team_leads);
		}else if($record_type == 2 && $user_type == 0){
			$subject = "OT Apply";
			$from = get_user_email($login_id);
			$to = get_user_email($supervisor);
			$cc = get_user_email($user_id);
			if($supervisor != $team_leads)
				$cc .= ",".get_user_email($team_leads);
			$reply = $to.",".$cc.",china.hr@qti.qualcomm.com";
			$message .= "</html>";
			$message = wordwrap($message, 70);
			mail_html($to, $cc, $subject, $message, $from, $reply);
			return;
		}else{
			$subject = "$record applying for $name<$user_id> submitted by $submitter";
			$message .= "<br><a href=$url/ooo_action.php?action=approve_vacation&record_id=$record_id&approve_magic=$approve_magic>Approve</a>";
			$message .= "&nbsp;<a href=$url/ooo_action.php?action=reject_vacation&record_id=$record_id&approve_magic=$approve_magic>Reject</a>";
		}
		if($record_type == 2 && $is_cchr){
			//$cc .= ",china.hr@qualcomm.com";
			//$cc .= ",xling@cedump-sh.qualcomm.com";
		}
	}else if($action == 'applying_cancel'){
		$to = get_user_email($supervisor);
		$cc = "";
		$subject = "$record cancel applying for $name<$user_id> submitted by $submitter";
		$message .= "<br><a href=$url/ooo_action.php?action=approve_cancel_vacation&record_id=$record_id&approve_magic=$approve_magic>Approve</a>";
		$message .= "&nbsp;<a href=$url/ooo_action.php?action=reject_cancel_vacation&record_id=$record_id&approve_magic=$approve_magic>Reject</a>";
	}else if($action == 'approved' || $action == 'rejected'){
		$to = get_user_email($user_id);
		$cc = get_user_email($supervisor);
		if($supervisor != $team_leads)
			$cc .= ",".get_user_email($team_leads);
		if($cancel == 1)
			$subject = "$record cancel for $name<$user_id> has been $action";
		else
			$subject = "$record for $name<$user_id> has been $action";
	}
	if($record_type == 2 && $action == 'approved' ){
		//$cc .= ",china.hr@qualcomm.com";
		//$cc .= ",xling@cedump-sh.qualcomm.com";
		$from = get_user_email($supervisor);
	}
	//$message .= "To:$to  CC:$cc";
	$message .= "</html>";
	$message = wordwrap($message, 70);
	//$to = "xling@qti.qualcomm.com"; $cc = "xling@qti.qualcomm.com";
	mail_html($to, $cc, $subject, $message, $from);
}

function ooo_apply_check()
{
	global $cctam, $is_cchr;
	$sql = "select ".
	"record_id, record_type, user_id, user_type, team_leads, priv_comments, name, a.type as type, date(start_date) as start, start_pm as sp, date(end_date) as end, end_pm as ep, apply_date, approve_date, approver, status, comments, submitter, approve_magic, status as action ".
	" from ooo.qtime a left join user.user b using(user_id) ";
	$sql .= "where status = 0 or status = 2";
	$res = read_mysql_query($sql);

	$action = 'applying';
	while($rows = mysql_fetch_array($res)) {
		$user_id = $rows['user_id'];
		$record_id = $rows['record_id'];
		$user_type = $rows['user_type'];
		$record_type = $rows['record_type'];
		$name = $rows['name'];
		$supervisor = $rows['approver'];
		$team_leads = $rows['team_leads'];
		$status = $rows['status'];
		$submitter = $rows['submitter'];
		$status = $rows['status'];
		$approve_magic = $rows['approve_magic'];
		$output = '';

		exec("php ooo_record.php $record_id $record_type $action", $output);
		$message = "<html>\r\n";
		$message .= "<meta http-equiv='Content-Type' content='text/html; charset=utf-8'>\r\n";
		$message .= "<a href='https://go/ceooo'>go/ceooo</a><br>\r\n";
		foreach($output as $line){
			$message .= $line . "\r\n"; 
		}
		$url = "http://cedump-sh.ap.qualcomm.com/report";

		$record_name = array('None', 'Vacation', 'OT', 'Onsite');
		$record = $record_name[$record_type];
		if($status == 0){ //applying
			$to = get_user_email($supervisor);
			$cc = '';
			if($record_type == 3 ){
			}else if($record_type == 2 && $user_type == 1){
			}else{
				$subject = "$record applying for $name<$user_id> submitted by $submitter is pending your approve";
				$message .= "<br><a href=$url/ooo_action.php?action=approve_vacation&record_id=$record_id&approve_magic=$approve_magic>Approve</a>";
				$message .= "&nbsp;<a href=$url/ooo_action.php?action=reject_vacation&record_id=$record_id&approve_magic=$approve_magic>Reject</a>";
			}
		}else if($status == 2 ){ //'applying_cancel'
			$to = get_user_email($supervisor);
			$cc = "";
			$subject = "$record cancel applying for $name<$user_id> submitted by $submitter is pending your approve";
			$message .= "<br><a href=$url/ooo_action.php?action=approve_cancel_vacation&record_id=$record_id&approve_magic=$approve_magic>Approve</a>";
			$message .= "&nbsp;<a href=$url/ooo_action.php?action=reject_cancel_vacation&record_id=$record_id&approve_magic=$approve_magic>Reject</a>";
		}
		//$message .= "To:$to  CC:$cc";
		$message .= "</html>";
		$message = wordwrap($message, 70);
		//$to = "xling@qti.qualcomm.com"; $cc = "xling@qti.qualcomm.com";
		mail_html($to, $cc, $subject, $message);
	}
}


function ooo_lastweek($team_id, $always=true)
{
	ooo_notice($team_id, $always, 'last_week');
}

function ooo_notice($team_id, $always=true, $date='today')
{
	$sql = "select a.user_id as user_id, b.lead_id as lead_alias, team_name, team_email  from user.user a left join user.teams b using(team_id)";
	if($team_id != 0)
		$sql .= "where team_id = $team_id";
	$res = read_mysql_query($sql);
	$to = "";
	while($rows = mysql_fetch_array($res)) {
		$user_id = $rows['user_id'];
		$lead_alias = $rows['lead_alias'];
		$team_name = $rows['team_name'];
		$team_email = $rows['team_email'];
		$to .= ",".get_user_email($user_id);
	}
	$to = get_user_email($lead_alias).$to;
	$rows = 0;
	exec("php ooo_record.php 0 2 $date $team_id", $output, $rows);
	if($team_id == 0){
		if($date == 'last_week'){
			if($rows == 0){
				$subject = "No onsite for CE last week";
			}else{
				$subject = "Last Week's onsite";
			}
		}else if($date == 'today'){
			if($rows == 0){
				$subject = "No OOO for CE $date";
			}else{
				$subject = "Today's OOO($rows people)";
			}
		}else if($date == 'tomorrow'){
			if($rows == 0){
				$subject = "No OOO for CE $date";
			}else{
				$subject = "Tomorrow's OOO($rows people)";
			}
		}
	}else{
		if($rows == 0){
			if(!$always){
				print("check always not true: $always\n");
				return;
			}
			print("check always true: $always\n");
			if($date == 'last_week'){
				$subject = "No onsite for $team_name lastweek";
			}else {
				$subject = "No OOO for $team_name $date";
				print $subject;
				return;
			}
			$to = get_user_email($lead_alias);
		}else{
			if($date == 'last_week'){
				$subject = "Last Week's onsite for $team_name";
			}else if($date == 'today'){
				$subject = "Today's OOO($rows people) for $team_name";
			}else if($date == 'tomorrow'){
				$subject = "Tomorrow's OOO($rows people) for $team_name";
			}
		}
	}
	$message = "<html>\r\n";
	$message .= "<a href='https://go/ceooo'>go/ceooo</a><br>\r\n";
	foreach($output as $line){
		$message .= $line . "\r\n"; 
	}
	if($team_email != '')
		$to = "$team_email";
	$cc = get_user_email($lead_alias);
	if($team_id == 0){
		$to = "qctchina.apps@qualcomm.com,qctchina.apps2@qualcomm.com";
		$cc = "";
	}
	//$message .= "To:$to  CC:$cc";
	$message .= "</html>";
	$message = wordwrap($message, 70);
	//$to = "xling@qti.qualcomm.com"; $cc = "xling@qti.qualcomm.com";
	mail_html($to, $cc, $subject, $message);
}

function update_vacation($record_id, $user_id, $type, $start_date, $end_date, $start_pm, $end_pm, $hours, $comments, $priv_comments)
{
	global $login_id;
	$approve_magic = mt_rand();
	$approver = get_db_column('user.user', 'supervisor', "user_id = '$user_id'");
	$comments = str_replace("'", "''", $comments );
	$priv_comments = str_replace("'", "''", $priv_comments );
	$sql = " update ooo.qtime set record_type = 1 , user_id = '$user_id', type = '$type', start_date = '$start_date', end_date = '$end_date'," .
		 "start_pm = $start_pm, end_pm = $end_pm, ".
		 "submitter = '$login_id', hours = '$hours', status = 0, comments = '$comments', priv_comments = '$priv_comments', approve_magic = '$approve_magic'".
		 ", approver = '$approver'  ";
	$sql .= " where record_id = $record_id and status = 0";
	update_mysql_query($sql);
	$rows = mysql_affected_rows();
	print("ok$rows record update");
}


function apply_vacation($user_id, $type, $start_date, $end_date, $start_pm, $end_pm, $hours, $comments, $priv_comments)
{
	global $login_id;
	$approve_magic = mt_rand();
	$approver = get_db_column('user.user', 'supervisor', "user_id = '$user_id'");
	$record_id = alloc_auto_id('record_id', 'ooo.param');
	$comments = str_replace("'", "''", $comments );
	$priv_comments = str_replace("'", "''", $priv_comments );
	$sql = " insert into ooo.qtime set record_id = $record_id, record_type = 1 , user_id = '$user_id', type = '$type', start_date = '$start_date', end_date = '$end_date'," .
		 "start_pm = $start_pm, end_pm = $end_pm, ".
		 "submitter = '$login_id', hours = '$hours', status = 0, comments = '$comments', ".
		 "priv_comments = '$priv_comments', approve_magic = '$approve_magic'".
		 ", approver = '$approver'  ";
	update_mysql_query($sql);
	$rows = mysql_affected_rows();
	mail_approve($record_id, 'applying');
	print("ok$rows record Submitted");
}

function apply_onsite($user_id, $type, $customer, $start_date, $end_date, $start_pm, $end_pm, $hours, $comments, $nocase)
{
	global $login_id;
	$approve_magic = mt_rand();
	$approver = get_db_column('user.user', 'supervisor', "user_id = '$user_id'");
	$record_id = alloc_auto_id('record_id', 'ooo.param');
	$status = 4;
	$comments = str_replace("'", "''", $comments );
	$sql = " insert into ooo.qtime set record_id = $record_id, record_type = 3 , user_id = '$user_id', type = '$type'".
		", start_date = '$start_date', end_date = '$end_date'" .
		", customer = '$customer'".
		",start_pm = $start_pm, end_pm = $end_pm ".
		",submitter = '$login_id', hours = '$hours', status = $status, comments = '$comments' ".
		",extra = $nocase".
		",approve_magic = '$approve_magic'".
		", approver = '$approver'  ";
	update_mysql_query($sql);
	$rows = mysql_affected_rows();
	mail_approve($record_id, 'applying');
	print("ok$rows record Submitted");
}

function update_ot($record_id, $user_id, $type, $customer, $start_date, $start_time, $hours, $comments)
{
	global $login_id;
	$approve_magic = mt_rand();
	$approver = get_db_column('user.user', 'supervisor', "user_id = '$user_id'");
	$comments = str_replace("'", "''", $comments );
	$sql = " update ooo.qtime set user_id = '$user_id', type = '$type', start_date = '$start_date', end_date = '$start_date'," .
		 " start_time = '$start_time'," .
		 "start_pm = 0, end_pm = 0, ".
		 "submitter = '$login_id', hours = '$hours', status = 0, comments = '$comments', ".
		 "approve_magic = '$approve_magic'".
		 ", approver = '$approver'  ";
	$sql .= " where record_id = $record_id and status = 0";
	update_mysql_query($sql);
	$rows = mysql_affected_rows();
	print("ok$rows record updated");
}


function apply_ot($user_id, $type, $customer, $start_date, $start_time, $hours, $comments)
{
	global $login_id;
	$approve_magic = mt_rand();
	$approver = get_db_column('user.user', 'supervisor', "user_id = '$user_id'");
	$record_id = alloc_auto_id('record_id', 'ooo.param');
	$comments = str_replace("'", "''", $comments );
	$end_time = cal_endtime($start_time, $hours);
	$user_location = get_user_prop($user_id, 'Office');
	if(strpos($user_location, 'SHN') !== false || strpos($user_location, 'XIA') !== false){
		$status = 4;
		$user_type = 1;
	}else{
		$status = 4;
		$user_type = 0;
	}
	$sql = " insert into ooo.qtime set record_id = $record_id, user_type = $user_type, record_type = 2 , user_id = '$user_id', type = '$type', start_date = '$start_date', end_date = '$start_date'," .
		 "start_time = '$start_time'," .
		 "end_time = '$end_time',".
		 "customer = '$customer',".
		 "start_pm = 0, end_pm = 0, ".
		 "submitter = '$login_id', hours = '$hours', status = $status, comments = '$comments', ".
		 "approve_magic = '$approve_magic'".
		 ", approver = '$approver'  ";
	update_mysql_query($sql);
	$rows = mysql_affected_rows();
	mail_approve($record_id, 'record');
	print("ok$rows record Submitted");
}

$vacation_type = array(
		'al'=>"Annual Leave",
		'sk'=>"Sick Leave",
		'ed'=>"EDH",
		'sf'=>"Work Shift",
		'ml'=>"Marriage Leave",
		'pl'=>"Paternity Leave",
		'bl'=>"Bereavement Leave",
		'tb'=>"Team Building",
		'lc'=>"Local",
		'tr'=>"Travel",
		'la'=>"Lab",
		'wd'=>"Work Day",
		'we'=>"Weekend",
		'hd'=>"Holiday"
	);

function get_leave_type($type)
{
	global $vacation_type;
	if(array_key_exists($type, $vacation_type))
		return $vacation_type[$type];
	return $type;
}

function get_status_type($type)
{
	$status_text = array('Applying', 'Approved', 'Canceling', 'Cancel', 'Record', 'Rejected');
	return isset($status_text[$type])?$status_text[$type]:$type;
}

function show_vacation_callback($field_name, $value, $row='')
{
	global $login_id;
	if($field_name == $value)
		return $value;
	if($field_name == 'type'){
		$value = get_leave_type($value);
	}else if($field_name == 'status'){
		$value = get_status_type($value);
	}else if($field_name == 'pi'){
		//$value = "<a href='javascript:show_full_rule(this,$value)'>$value</a>";
		$script = "<a onclick='show_full_rule(this, $value, \"$value\");return false' href=''>$value</a>";
		$value = $script;
	}else if($field_name == 'sp' || $field_name == 'ep'){
		if($value == 0)
			$value = 'am';
		else
			$value = 'pm';
	}else if($field_name == 'action'){
		$record_id = $row['record_id'];
		$record_type = $row['record_type'];
		$approver = $row['approver'];
		$status = get_status_type($value); 
		$user_id = $row['user_id'];
		$apply_date = $row['apply_date'];

		$script = '';
		if($status == 'Applying' && $approver == $login_id){
			$script .= "<input class='bt_approve' type='button' record_id='$record_id' value='Approve' href=''></a>";
			$script .= "<input class='bt_reject' type='button' record_id='$record_id' value='Reject' href=''></a>";
		}
		if($status == 'Canceling' && $approver == $login_id){
			$script .= "<input class='bt_approve_cancel' type='button' record_id='$record_id' value='Approve' href=''></a>";
			$script .= "<input class='bt_rejecte_cancel' type='button' record_id='$record_id' value='Reject' href=''></a>";
		}
		if($status == 'Applying'  && $user_id == $login_id){
			$script .= "<input class='bt_cancel' type='button' record_id='$record_id' value='Cancel' href=''></a>";
		}
		if( $status == 'Record' && !date_before($apply_date, get_last_nday(7)) && $user_id == $login_id){
			$script .= "<input class='bt_cancel' type='button' record_id='$record_id' value='Cancel' href=''></a>";
		}
		if(( $status == 'Approved' ) && $user_id == $login_id){
			$script .= "<input class='bt_apply_cancel' type='button' record_id='$record_id' value='Cancel' href=''></a>";
		}

		$submitter = $row['submitter'];
		$type = $row['type'];
		$start_date = $row['start'];
		$comments = $row['comments'];


		if(( $status == 'Applying') && ($user_id == $login_id || $submitter == $login_id)){

			$attr = "user_id='$user_id' record_id='$record_id' record_type='$record_type' vacation_type='$type' start_date='$start_date' ";
			$attr .= " comments = '$comments'";
			if($record_type == 1){
				$end_date = $row['end'];
				$start_pm = $row['sp'];
				$end_pm = $row['ep'];
				$priv_comments = $row['priv_comments'];
				$attr .= " end_date='$end_date' start_pm='$start_pm' end_pm='$end_pm' priv_comments='$priv_comments' ";
			}else if($record_type == 2){
				$start_time = $row['start_time'];
				$hours = $row['hours'];
				$customer = $row['customer'];
				$attr .= " start_time='$start_time' hours='$hours' customer = '$customer' ";
			}
			if($record_type != 3){
				$script .= "<input class='bt_update' type='button' $attr value='Edit' href=''></a>";
			}
		}
		$value = $script;
	}
	return $value;
}

function show_onsite($tb_id, $cond = "1", $format = 0)
{
	$sql = "select ".
	"record_id, record_type, user_id, extra, approver, name, team_name as team, a.type as type, customer, date(start_date) as start, start_pm as sp, date(end_date) as end, end_pm as ep, apply_date, status, comments, submitter, status as action ".
	"from ooo.qtime a left join user.user using(user_id) left join user.teams c using(team_id) ".
	" where record_type = 3 and $cond".
	" order by start_date desc ";
	$sql .= " limit 0, 200 ";
	$rows = show_table_by_sql($tb_id, 'ooo', 1024, $sql, array(), array(-1, -1, -1, -1, -1, 10, 30, 150, 150, 30, 80, 30, 30, 30, 30, 50, 100, 50), 'show_vacation_callback', 1);
	return $rows;
}


function sum_vacation($tb_id='id_sum', $cond = " 1 ")
{
	$record_type = 1;
	$sql = "select ".
		"name ".
		", sum(if(a.type = 'al', (datediff(date(end_date) , date(start_date)) + 1 - if(start_pm = 1, 0.5, 0) - if(end_pm = 0, 0.5, 0)), 0)) as 'Annual'".
		", sum(if(a.type = 'sk', (datediff(date(end_date) , date(start_date)) + 1 - if(start_pm = 1, 0.5, 0) - if(end_pm = 0, 0.5, 0)), 0)) as 'Sick'".
		", sum(if(a.type = 'ed', (datediff(date(end_date) , date(start_date)) + 1 - if(start_pm = 1, 0.5, 0) - if(end_pm = 0, 0.5, 0)), 0)) as 'EDH'".
		", sum(if(a.type = 'pl', (datediff(date(end_date) , date(start_date)) + 1 - if(start_pm = 1, 0.5, 0) - if(end_pm = 0, 0.5, 0)), 0)) as 'Paternity'".
		", sum(if(a.type = 'ml', (datediff(date(end_date) , date(start_date)) + 1 - if(start_pm = 1, 0.5, 0) - if(end_pm = 0, 0.5, 0)), 0)) as 'Marriage'".
		", sum(if(a.type = 'bl', (datediff(date(end_date) , date(start_date)) + 1 - if(start_pm = 1, 0.5, 0) - if(end_pm = 0, 0.5, 0)), 0)) as 'Bereavement'".
		", sum(if(a.type = 'sf', (datediff(date(end_date) , date(start_date)) + 1 - if(start_pm = 1, 0.5, 0) - if(end_pm = 0, 0.5, 0)), 0)) as 'Shift'".
		"from ooo.qtime a left join user.user b using(user_id)".
		" where record_type = $record_type and (status = 1  or status = 0) and $cond" .
		"group by user_id ";
	$rows = show_table_by_sql($tb_id, 'ooo', 800, $sql, array(), array(), 'show_vacation_callback', 1);
	return $rows;
}

function sum_onsite($tb_id='id_sum', $cond = " 1 ")
{
	$record_type = 3;
	$sql = "select ".
		"name, team_name as team ".
		", sum(if(a.type = 'lc', (datediff(date(end_date) , date(start_date)) + 1 - if(start_pm = 1, 0.5, 0) - if(end_pm = 0, 0.5, 0)), 0)) as 'Local'".
		", sum(if(a.type = 'tr', (datediff(date(end_date) , date(start_date)) + 1 - if(start_pm = 1, 0.5, 0) - if(end_pm = 0, 0.5, 0)), 0)) as 'Travel'".
		", sum(if(a.type = 'la', (datediff(date(end_date) , date(start_date)) + 1 - if(start_pm = 1, 0.5, 0) - if(end_pm = 0, 0.5, 0)), 0)) as 'Lab'".
		"from ooo.qtime a left join user.user b using(user_id) left join user.teams c using(team_id)".
		" where record_type = $record_type and (status = 4) and $cond" .
		"group by user_id ";
	$rows = show_table_by_sql($tb_id, 'ooo', 500, $sql, array(), array(), 'show_vacation_callback', 1);
	return $rows;
}

function sum_ot($tb_id='id_sum', $cond = " 1 ")
{
	$record_type = 2;
	$sql = "select ".
		"name ".
		", sum(if(a.type = 'wd', hour(if(end_time > start_time, timediff(end_time , start_time), addtime('2018-1-1 0:0:0', timediff(end_time, start_time)))), 0)) as 'WorkDay'".
		", sum(if(a.type = 'we', hour(if(end_time > start_time, timediff(end_time , start_time), addtime('2018-1-1 0:0:0', timediff(end_time, start_time)))), 0)) as 'Weekend'".
		", sum(if(a.type = 'hd', hour(if(end_time > start_time, timediff(end_time , start_time), addtime('2018-1-1 0:0:0', timediff(end_time, start_time)))), 0)) as 'Holiday'".
		"from ooo.qtime a left join user.user b using(user_id)".
		" where record_type = $record_type and (status = 1) and $cond" .
		"group by user_id ";
	$rows = show_table_by_sql($tb_id, 'ooo', 500, $sql, array(), array(), 'show_vacation_callback', 1);
	return $rows;
}

function query_record($cond, $record_type)
{
		$sql = "select ".
			"record_id ".
			"from ooo.qtime a left join user.user b using(user_id) left join user.teams c using(team_id) ".
			" where record_type = $record_type and $cond".
			" order by start_date desc ";
		$res = read_mysql_query($sql);
		$line = 0;
		while($rows = mysql_fetch_array($res)){
			$line += 1;
		}
		return $line;
}

function show_vacation($tb_id, $cond = "1", $format=0)
{
	if($format == 0){
		$sql = "select ".
			"record_id, record_type, user_id, priv_comments, name, team_name as team, a.type as type, date(start_date) as start, start_pm as sp, date(end_date) as end, end_pm as ep, apply_date, approve_date, approver, status, comments, submitter, status as action ".
			"from ooo.qtime a left join user.user b using(user_id) left join user.teams c using(team_id) ".
			" where record_type = 1 and $cond".
			" order by start_date desc ";
		$sql .= " limit 0, 200 ";
		$rows = show_table_by_sql($tb_id, 'ooo', 1024, $sql, array(), array(-1, -1, -1, -1, 10, 30, 150, 150, 30, 80, 30, 30, 30, 30, 50, 100, 50), 'show_vacation_callback', 1);
	} else if($format == 1){
		$sql = "select ".
			"record_id, record_type, user_id, name, a.type as type, date(start_date) as start, start_pm as sp, date(end_date) as end, end_pm as ep, apply_date, approver, status, submitter, comments, priv_comments ".
			"from ooo.qtime a left join user.user b using(user_id) left join user.teams c using(team_id) ".
			" where record_type = 1 and $cond".
			" order by start_date desc ";
		$rows = show_table_by_sql($tb_id, 'ooo', 1024, $sql, array(), array(-1, -1, -1, 10, 30, 150, 150, 30, 80, 30, 30, 30, 30, 50, 50, 50), 'show_vacation_callback', 1);
	} else if($format == 2){
		$sql = "select ".
			"record_id, record_type, user_id, name, a.type as type, date(start_date) as start, start_pm as sp, date(end_date) as end, end_pm as ep, apply_date, approver, status, comments, priv_comments, submitter, status as action ".
			"from ooo.qtime a left join user.user b using(user_id) left join user.teams c using(team_id) ".
			" where record_type = 1 and $cond".
			" order by start_date desc ";
		$rows = show_table_by_sql($tb_id, 'ooo', 1024, $sql, array(), array(-1, -1, -1, 10, 30, 150, 150, 30, 80, 30, 30, 30, 30, 50, 50, 50), 'show_vacation_callback', 1);
	}
	return $rows;
}

function show_ot($tb_id, $cond = "1", $format=0)
{
	if($format == 1){
		$sql = "select ".
			"name, date(start_date) as 'Date', time_format(start_time,'%k:%i') as 'Start Time', time_format(end_time,'%k:%i') as 'Stop Time',  hours as 'Total OT Hours', comments as ".
			"'Description of Overtime Services Requested' " .
			"from ooo.qtime a left join user.user b using(user_id)".
			" where record_type = 2 and $cond ".
			" order by start_date desc ";
		$rows = show_table_by_sql($tb_id, 'mysf', 1024, $sql, array(), array(), 'show_vacation_callback', 1);
	}else if($format == 0){
		$sql = "select ".
			"record_id, record_type, user_id, name, a.type as type, customer, date(start_date) as start, time_format(start_time,'%k:%i') as start_time,  hours, apply_date, approve_date, approver, status, comments, submitter, status as action ".
			"from ooo.qtime a left join user.user b using(user_id)".
			" where record_type = 2 and $cond ".
			" order by start_date desc ";
		$sql .= " limit 0, 200 ";
		$rows = show_table_by_sql($tb_id, 'mysf', 1024, $sql, array(), array(-1,-1, -1, 10, 30, 150, 150, 30, 80, 30, 30, 30, 30, 50, 200, 50), 'show_vacation_callback', 1);
	}
	return $rows;
}

?>
