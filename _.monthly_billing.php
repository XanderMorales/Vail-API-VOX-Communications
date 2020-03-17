<?php

/*
Authors:
Alexander Nietzen Morales
*/

require_once ("./functions/master/billing_tools.php");

$GLOBALS['server_billing_path'] = '/usr/local/apache/billing/';

############################################################
function enter_password() {
?>
<br>
<table width="500" border="0" cellspacing="0" cellpadding="0" align="center"><tr><td>
<fieldset>
<legend><nobr>Generate Invoices for Customers</nobr></legend>
<br>
<form action="/" method="post" style="margin:0px;"">
<input type="hidden" name="page" value="user">
<input type="hidden" name="funct" value="create_invoices">
<br>
password: <input type="password" name="pass">
<input type="submit" value="create_invoices" class="submit">
</form>
<br>
<?php if(isset($_REQUEST['pass'])) { if($_REQUEST['pass'] == 'passwordhere'){ execute_invoice_generation(); } else { echo "Sorry, Unathorized Access!"; } } ?>
</fieldset>
<?php

?>
</td></tr></table>
<br>


<?php
/*
    $tomorrow  = mktime (0,0,0,date("m"), date("d")+1, date("Y"));
    $lastmonth = mktime (0,0,0,date("m")-1, date("d"), date("Y"));
    $nextyear  = mktime (0,0,0,date("m"), date("d"), date("Y")+1);

    // Now you can use date values with date() function
    echo "Tomorrow is " . date("m/d/Y", $tomorrow) . "</br>";
    echo "Before one month it was " . date("m/d/Y", $lastmonth) . "</br>";
    echo "After a year it will be " . date("m/d/Y", $nextyear) . "</br>";
*/

}
############################################################
function execute_invoice_generation(){
	require_once "./config/config.billing.php";

	$qstart_date = date("Y-m-d",  mktime (0,0,0,date("m")-1, date("d"), date("Y"))); // $qstart_date = '2006-04-01'; year-month-day
	$qend_date = date("Y-m-d"); // $qend_date = '2006-05-01'; year-month-day.. should be the following first of the month from $qstart_date
	$inv_due_date = "Due Upon Receipt"; // The "due date" of invoice!
	$inv_date = date('m-d-Y'); // $inv_date = "05-01-2006"; The "invoice date"... Should be first of the month!!!!!!!
	$invoice_month = date("m",  mktime (0,0,0,date("m")-1, date("d"), date("Y"))); // $invoice_month = '04'; - last month!!!
	$invoice_year = date('Y'); // $invoice_year = '2006';
	$days_in_lastmonth = date("t", mktime(0,0,0, date("n") - 1)); // $days_in_lastmonth = '30' - the month you are billing for

	$inv_start = $qstart_date; // $inv_start = "2006-04-01"; "Billing Period Start" (storing this value in invoices table)
	$inv_end = $invoice_year . "-" . $invoice_month . "-" . $days_in_lastmonth; // $inv_end = "2006-04-30"; "Billing Period End"  (storing this value in invoices table)

	$grand_total_minutes = ''; // for voice
	$vnet = ''; // for voice
	$vgross = ''; // for voice
	$grand_total_web_keys = ''; // for web
	$wnet = ''; // for web
	$wgross = ''; // for web

	// print header of csv  file for non active users
	$array_of_accounts_with_no_balance = array();
	// print header of csv  file for non active users EOC

	$prev_bill_dir = '';
	$array_of_biling_dirs = array();
	$count_key = 0;
	foreach($BILLING_BRAND as $key=>$val){ // OPEN FOREACH $BILLING_BRAND
	//if($key == 'flsconferencing.com'){ ########### OPEN TEMP - IF KEY

		$master_site = $key;
		$master_id = $val[0];
		$reseller_user_info_id = $val[1];
		$invoice_table = $val[2];
		$invoice_report_table = $val[3];
		$will_collect_checks = $val[4];
		$customers_gateway = $val[5];
		$billing_cc_email = $val[6];
		$is_branded_invoice = $val[7];
		$billing_directory = $GLOBALS['server_billing_path'] . $val[8];

		$db_connect = DB_CONNECTION('vvc');
		$query00 = "SELECT * FROM user_info p
			INNER JOIN user_info_relationships t ON p.user_id = t.corporate_user_info_id
			WHERE
			(
				t.$reseller_user_info_id = '$master_id'
				AND p.user_account_type = 'corporate'
				AND p.user_status = 'active'
				AND t.corporate_user_info_id > 0
                AND p. pricing_model != 'pre_paid'
			)
			GROUP BY p.user_id
			ORDER BY p.user_id ASC";

		$DetailRS00 = EXECUTE_SQL($query00,$db_connect);
		$array_of_accounts_with_a_balance = array();
		while($row_DetailRS00 = mysql_fetch_assoc($DetailRS00))
		{

			// build an array of individual_user_id's
			$array_of_indv_ids = array();
			$query01 = "SELECT individual_user_info_id,corporate_user_info_id FROM user_info_relationships
						WHERE corporate_user_info_id = '{$row_DetailRS00['user_id']}'
						AND individual_user_info_id > '0'";
			$DetailRS01 = EXECUTE_SQL($query01,$db_connect);
			$corp_and_indv_ids = "{$row_DetailRS00['user_id']},";
			while($row_DetailRS01 = mysql_fetch_assoc($DetailRS01))
			{
				array_push($array_of_indv_ids, $row_DetailRS01['individual_user_info_id']);
				$corp_and_indv_ids .= "{$row_DetailRS01['individual_user_info_id']},"; // building string for sql
			}
			$corp_and_indv_ids = substr("$corp_and_indv_ids", 0, -1); # removes the last character
			// build an string of individual_user_id's EOC

			// build a multidimensional array of corp to indv id's
			$array_of_accounts_with_a_balance[$row_DetailRS00['user_id']] = $array_of_indv_ids;
			$array_of_accounts_with_no_balance[$row_DetailRS00['user_id']] = $array_of_indv_ids;
			// build a multidimensional array of corp to indv id's EOC

			// ----------------------------------------------------------------------------------------//
			// determine which accounts have usage for this month's billing cycle for voice services
			$has_account_balance_due = "no";
			$query02 = "SELECT * FROM conference_ccdr ccdr
					WHERE ccdr.user_id IN ($corp_and_indv_ids)
					AND conference_start between '$qstart_date' AND '$qend_date'
					ORDER BY conference_start ASC";
			$numresults1 = mysql_query($query02);
			$numrows1 = mysql_num_rows($numresults1);
			if ($numrows1 == 0){ $has_account_balance_due = "no"; }
			else { $has_account_balance_due = "yes"; }

			// check for active license keys
			$query03 = "SELECT * FROM active_services
						WHERE user_id IN ($corp_and_indv_ids)
						AND webconf_key_status = 'active'";
			$numresults2=mysql_query($query03);
			$numrows2=mysql_num_rows($numresults2);
			if ($numrows2 == 0){ if($has_account_balance_due != "yes") { $has_account_balance_due = "no"; } }
			else { $has_account_balance_due = "yes"; }
			// check for active license keys EOC

			// unset users in array if no acount balance is due!
			if($has_account_balance_due == "no"){ unset($array_of_accounts_with_a_balance[$row_DetailRS00['user_id']]); }
			// unset users in array if acount balance is due!
			if($has_account_balance_due == "yes"){ unset($array_of_accounts_with_no_balance[$row_DetailRS00['user_id']]); }
			// determine which accounts have usage for this month's billing cycle for voice services EOC

		}

		// iterate through $array_of_accounts_with_a_balance
		foreach($array_of_accounts_with_a_balance as $key2=>$val2)
		{
			// convert indv_id array to a string.
			$indv_ids = implode(",", $val2);
			// build customer invoice
			if(!($indv_ids)) { $indv_ids = 0; }
			//if($key2 == '3326') // for testing a single invoice
			//{
				list($customer_invoice, $xgrand_total_minutes, $xnet, $xgross, $xgrand_total_web_keys, $xwnet, $xwgross) = build_invoice($master_id, $key2,$indv_ids, $inv_due_date, $inv_date, $invoice_month, $invoice_year, $days_in_lastmonth, $qstart_date, $qend_date, $invoice_table, $invoice_report_table, $billing_directory, $inv_start, $inv_end);
				$grand_total_web_keys = $xgrand_total_web_keys + $grand_total_web_keys;
				$wnet = $xwnet + $wnet;
				$wgross = $xwgross + $wgross;

				$grand_total_minutes = $xgrand_total_minutes + $grand_total_minutes;
				$vnet = $xnet + $vnet;
				$vgross = $xgross + $vgross;

				if($GLOBALS['dev_test'] == 'on'){ echo "<table width=670><tr><td><h1>$key</h1><br>$customer_invoice</td></tr></table>"; }
			//}
		}
		// iterate through $array_of_accounts_with_a_balance EOC

		// build $array_of_biling_dirs
		if($count_key == 0){ $prev_bill_dir = $billing_directory; }
		if($prev_bill_dir != $billing_directory)
		{
			$array_of_biling_dirs[$billing_directory] = $val;
		}
		$prev_bill_dir = $billing_directory;
		$count_key ++;
		// build $array_of_biling_dirs EOC

	//} ########### CLOSE TEMP - IF KEY
	} // CLOSE FOREACH $BILLING_BRAND


	// iterate through $array_of_accounts_with_no_balance
	$non_user_csv = $GLOBALS['server_billing_path'] . $invoice_year . "_" . $invoice_month . '_non_user.csv';
	$non_user_file_handle = fopen($non_user_csv, "w");
	fwrite($non_user_file_handle,"master_id,client_id,company,fname,lname,user_state,user_province,user_phone,user_email,user_start_date\n");
	foreach($array_of_accounts_with_no_balance as $ua_user_id=>$ua_indv_id)
	{
		$users_not_active_sql = "SELECT * FROM user_info u
				INNER JOIN user_info_relationships t
				ON u.user_id = t.corporate_user_info_id
				WHERE u.user_id = $ua_user_id";
		$DetailR_ua = EXECUTE_SQL($users_not_active_sql,$db_connect);
		$row_DetailR_ua = mysql_fetch_assoc($DetailR_ua);
		$mi = $row_DetailR_ua['reseller_user_info_id'];
		if($mi == 0){ $mi = $row_DetailR_ua['sub_reseller_user_info_id']; }

		$company = preg_replace('/\,/', ' ', $row_DetailR_ua['user_company']);

		fwrite($non_user_file_handle,"$mi,{$row_DetailR_ua['user_id']},$company,{$row_DetailR_ua['user_fname']},{$row_DetailR_ua['user_lname']},{$row_DetailR_ua['user_state']},{$row_DetailR_ua['user_province']},{$row_DetailR_ua['user_phone']},{$row_DetailR_ua['user_email']},{$row_DetailR_ua['user_start_date']}\n");
	}
	fclose($non_user_file_handle);
	// iterate through $array_of_accounts_with_no_balance EOC


	$directories_to_clean = array
	(
			'/pdf/',
			'/pdf/email/',
			'/pdf/paper/',
			'/pdf/email/check/',
			'/pdf/paper/check/',
			'/pdf/email/credit_card/',
			'/pdf/paper/credit_card/',
			'/pdf/email/credit_card/amex/',
			'/pdf/email/credit_card/discover/',
			'/pdf/email/credit_card/mc/',
			'/pdf/email/credit_card/visa/',
			'/pdf/paper/credit_card/amex/',
			'/pdf/paper/credit_card/discover/',
			'/pdf/paper/credit_card/mc/',
			'/pdf/paper/credit_card/visa/'
	);

//	print_r($array_of_biling_dirs);
	foreach($array_of_biling_dirs as $key3=>$val3)
	{

		// create csv file
		$csv_sql = "SELECT * from $val3[3]
				WHERE invoice_date >= '$qend_date'";
		$DetailRScsv = EXECUTE_SQL($csv_sql,$db_connect);
		$csv_file_name = "$key3/pdf/" . $inv_start . "-" . $inv_end . "-" . $val3[8] . "_customers.csv";
		$file_handle = fopen($csv_file_name, "w");
		fwrite($file_handle,"invoice_number,client_id,master_id,billing_method,first_name,last_name,email,invoice_date,billing_period_start,billing_period_end,voice_minutes,current_voice_charges,current_web_charges,taxes_and_fees,paper_charge,amount_due,status,billing_card_type,billing_cc_num,billing_cc_fname,billing_cc_lname,billing_exp\n");
		while($row_DetailRScsv = mysql_fetch_assoc($DetailRScsv))
		{
			fwrite($file_handle,"{$row_DetailRScsv['invoice_number']},{$row_DetailRScsv['client_id']},{$row_DetailRScsv['master_id']},{$row_DetailRScsv['billing_method']},{$row_DetailRScsv['first_name']},{$row_DetailRScsv['last_name']},{$row_DetailRScsv['email']},{$row_DetailRScsv['invoice_date']},{$row_DetailRScsv['billing_period_start']},{$row_DetailRScsv['billing_period_end']},{$row_DetailRScsv['voice_min']},{$row_DetailRScsv['current_voice_charges']},{$row_DetailRScsv['current_web_charges']},{$row_DetailRScsv['taxes_and_fees']},{$row_DetailRScsv['paper_charge']},{$row_DetailRScsv['amount_due']},{$row_DetailRScsv['status']},{$row_DetailRScsv['billing_card_type']},{$row_DetailRScsv['billing_cc_num']},{$row_DetailRScsv['billing_cc_fname']},{$row_DetailRScsv['billing_cc_lname']},{$row_DetailRScsv['billing_exp']}\n");
		}
		fclose($file_handle);
		// create csv file EOC

		// gzip invoices
		$gzip_files = 'tar cvfzp ' . $GLOBALS['server_billing_path'] . $invoice_month . $invoice_year . '_' . $val3[8] . '_invoices.tgz ' . $key3 . '/pdf/';
		exec($gzip_files);
		// gzip invoices EOC

		// now clean-up csv file
		$rm_csv_file = 'rm ' . $csv_file_name;
		exec($rm_csv_file);
		// now clean-up csv file

		// now clean-up pfd's
		foreach($directories_to_clean as $x)
		{
			$clean_command = 'rm -Rf ' . $key3 . $x . '*.pdf';
			exec($clean_command);
		}
		// now clean-up pfd's EOC

	}

	echo 	"<br><strong>grand_total_minutes</strong>: $grand_total_minutes<br><strong>voice net</strong>: \$$vnet<br><strong>voice gross</strong>: \$$vgross";
	echo 	"<br><strong>grand_total_web_keys</strong>: $grand_total_web_keys<br><strong>web net</strong>: \$$wnet<br><strong>web gross</strong>: \$$wgross";
}
############################################################
function build_invoice($master_id,$corp_id,$indv_id,$inv_due_date,$inv_date,$invoice_month,$invoice_year,$days_in_lastmonth,$qstart_date,$qend_date, $invoice_table, $invoice_report_table, $billing_directory, $inv_start, $inv_end){

	$db_connect = DB_CONNECTION('vvc');
	$sql = "SELECT * FROM user_info u
			INNER JOIN invoice_method i
			ON u.user_id = i.user_id
			INNER JOIN billing_cc_info x
			ON u.user_id = x.user_id
			INNER JOIN billing_method b
			ON u.user_id = b.user_id
			WHERE u.user_id = '$corp_id'
			AND x.billing_default = 'yes'";
	//echo $sql;
	$DetailRS00 = EXECUTE_SQL($sql,$db_connect);
	$row_DetailRS00 = mysql_fetch_assoc($DetailRS00);

	$invoice_html = invoice_template($master_id);

	$pattern = '/\$\$DUE_DATE\$\$/';
	$invoice_html = preg_replace($pattern, $inv_due_date, $invoice_html);

	$pattern = '/\$\$INVOICE_DATE\$\$/';
	$invoice_html = preg_replace($pattern, $inv_date, $invoice_html);

	$pattern = '/\$\$CLIENT_ID\$\$/';
	$invoice_html = preg_replace($pattern, $corp_id, $invoice_html);

	$pattern = '/\$\$FIRST_NAME\$\$/';
	$invoice_html = preg_replace($pattern, $row_DetailRS00['user_fname'], $invoice_html);

	$pattern = '/\$\$LAST_NAME\$\$/';
	$invoice_html = preg_replace($pattern, $row_DetailRS00['user_lname'], $invoice_html);

	$company_name = "";
	if(stripslashes($row_DetailRS00['user_company']) != ''){ $company_name = "<br>" . stripslashes($row_DetailRS00['user_company']); }
	else { $company_name = ""; }
	$pattern = '/\$\$COMPANY\$\$/';
	$invoice_html = preg_replace($pattern, $company_name, $invoice_html);

	$pattern = '/\$\$CC_INFO\$\$/';
	$path_to_cc_dir = '';
	if($row_DetailRS00['billing_method'] == 'check'){ $invoice_html = preg_replace($pattern, '', $invoice_html); }
	else if($row_DetailRS00['billing_method'] == 'credit_card')
	{
		$cc_num = decrypt_cc_show_x($row_DetailRS00['billing_cc_num']);
		if($cc_num{0} == '3')
		{
			$cc_val = "American Express: " . $cc_num;
			$path_to_cc_dir = 'amex/';
		}
		else if($cc_num{0} == '4')
		{
			$cc_val = "Visa: " . $cc_num;
			$path_to_cc_dir = 'visa/';
		}
		else if($cc_num{0} == '5')
		{
			$cc_val = "Master Card: " . $cc_num;
			$path_to_cc_dir = 'mc/';
		}
		else if($cc_num{0} == '6')
		{
			$cc_val = "Discover: " . $cc_num;
			$path_to_cc_dir = 'discover/';
		}
		$invoice_html = preg_replace($pattern, $cc_val , $invoice_html);
	}

	$address = $row_DetailRS00['user_address_1'];
	$address .= " " . $row_DetailRS00['user_address_2'];
	$pattern = '/\$\$ADDRESS\$\$/';
	$invoice_html = preg_replace($pattern, $address, $invoice_html);

	$pattern = '/\$\$CITY\$\$/';
	$invoice_html = preg_replace($pattern, $row_DetailRS00['user_city'], $invoice_html);

	$state = $row_DetailRS00['user_state'];
	if($row_DetailRS00['user_state'] == 'Outside US') { $state = $row_DetailRS00['user_province']; }
	$pattern = '/\$\$STATE\$\$/';
	$invoice_html = preg_replace($pattern, $state, $invoice_html);

	$pattern = '/\$\$ZIP\$\$/';
	$invoice_html = preg_replace($pattern, $row_DetailRS00['user_zip'], $invoice_html);

	$pattern = '/\$\$START\$\$/';
	$invoice_html = preg_replace($pattern, "$invoice_month-01-$invoice_year", $invoice_html);
	$pattern = '/\$\$END\$\$/';
	$invoice_html = preg_replace($pattern, "$invoice_month-$days_in_lastmonth-$invoice_year", $invoice_html);

	####################################################
	###### CHECK FOR ALL POSSIBLE ACCOUNT CHARGES
	####################################################

	// get voice conferencing usage
	list($cdrs,$rate_per_min,$total_minutes,$orig_total_voice_amount_due) = build_cdr_invoice_reports($master_id,$corp_id,$indv_id,$qstart_date,$qend_date,$row_DetailRS00['conf_cpm'],$row_DetailRS00['promo'],$row_DetailRS00['user_fname'],$row_DetailRS00['user_lname']);
	$pattern = '/\$\$T_M\$\$/';
	$t_m = round($total_minutes / 60, 2);
	$invoice_html = preg_replace($pattern, $t_m, $invoice_html);
	$pattern = '/\$\$CDR\$\$/';
	$invoice_html = preg_replace($pattern, $cdrs, $invoice_html);
	// get voice conferencing usage EOC

	// this is the original amount due on voice before any promos - this value is always printed on invoices and never adjusted
	$pattern = '/\$\$CURRENT_ACTIVITY_CHARGES\$\$/';
	$invoice_html = preg_replace($pattern, '\$' . $orig_total_voice_amount_due, $invoice_html);

	// get web conferencing usage
	list($web_conf_html,$web_conf_charges,$count_web_keys) = calculate_web_conference_usage($master_id,$corp_id,$indv_id,$row_DetailRS00['web_cpm']);
	$pattern = '/\$\$WEB_CONF\$\$/';
	$invoice_html = preg_replace($pattern, $web_conf_html, $invoice_html);
	// get web conferencing usage EOC

	$pattern = '/\$\$RATE\$\$/';
	if($rate_per_min == '') { $rate_per_min = $row_DetailRS00['conf_cpm']; $invoice_html = preg_replace($pattern, $row_DetailRS00['conf_cpm'], $invoice_html); }
	else { $invoice_html = preg_replace($pattern, $rate_per_min, $invoice_html); }

	// determine if client requests paper invoice
	if($row_DetailRS00['invoice_method'] == 'paper')
	{
		$paper_charge = '';
		if($total_amount_due == '0.00'){ $paper_charge = '0.00'; }
		else { $paper_charge = '5.00'; }
		$paper_bill = "<tr><td><font size=\"2\" face=\"arial\">Paper Bill Charge</font></td><td><font size=\"2\" face=\"arial\">&#36;$paper_charge</font></td></tr>";
	}
	else
	{
		$paper_bill = '';
		$paper_charge = '0.00';
	}
	$pattern = '/\$\$PAPER_BILL\$\$/';
	$invoice_html = preg_replace($pattern, $paper_bill, $invoice_html);
	// determine if client requests paper invoice EOC

	// find promo rule
	if($row_DetailRS00['promo'] == 'yes')
	{ // voice conf promo only!!
		$query_p = "SELECT * FROM promo_relationships r
			INNER JOIN promo_definition d
			ON r.promo_definition_id = d.promo_definition_id
			WHERE r.user_id = '$corp_id'";
		//echo $query_p;
		$DetailRS0p = EXECUTE_SQL($query_p,$db_connect);
		$row_DetailRS0p = mysql_fetch_assoc($DetailRS0p);
		$promo_query = "SELECT promo_script FROM promo_type WHERE promo_type_id = '{$row_DetailRS0p['promo_type_id']}'";
		$DetailRS0promo = EXECUTE_SQL($promo_query,$db_connect);
		$row_DetailRS0promo = mysql_fetch_assoc($DetailRS0promo);
		if($row_DetailRS0promo['promo_script'] == 'free_x_minutes.php')
		{
			require_once("./functions/promo/" . $row_DetailRS0promo['promo_script']);
			$m_r = $row_DetailRS0p['value'];
			if($total_minutes == '') { $total_minutes = '0'; }
			eval('list($total_voice_amount_due, $voice_taxes, $promo_money) = ' . substr($row_DetailRS0promo['promo_script'], 0, -4) . '_calc_promo($total_minutes,$m_r,$rate_per_min,$orig_total_voice_amount_due,$corp_id);'); # removes the last 4 character (.php) and add the '_calc_promo'
			$pattern = '/\$\$PROMO\$\$/';
			$promo_html = '<tr><td><font size="2" face="arial">Adjustments / Promotions</font></td><td><font size="2" face="arial">&#36;' . $promo_money . '</font></td></tr>';
			$invoice_html = preg_replace($pattern, $promo_html, $invoice_html);
		}
		else
		{
			// let's turn the promo off for the user here - it is on here because I did not turn it iff on signup - no big deal - we caught it here.
			$user_info_sql = "UPDATE user_info SET promo = 'off' WHERE user_id = '$corp_id'";
			if($GLOBALS['dev_test'] == 'off') { $DetailRS00 = EXECUTE_SQL($user_info_sql,$db_connect); }

			$total_voice_amount_due = $orig_total_voice_amount_due;
			$voice_taxes = check_change(round($total_voice_amount_due * .129, 2));

			$pattern = '/\$\$PROMO\$\$/';
			$invoice_html = preg_replace($pattern, '', $invoice_html);
		}
	}
	else
	{
		$total_voice_amount_due = $orig_total_voice_amount_due;
		$voice_taxes = check_change(round($total_voice_amount_due * .129, 2));
		$pattern = '/\$\$PROMO\$\$/';
		$invoice_html = preg_replace($pattern, '', $invoice_html);
	}
	// find promo rule EOC

	// calculate taxes
	$web_conf_taxes = check_change(round($web_conf_charges * .08, 2)); /// 8% web conf taxes and fees!

	$sub_total_taxes = check_change(round($voice_taxes + $web_conf_taxes, 2));
 	$pattern = '/\$\$TAXES_AND_FEES\$\$/';
	$invoice_html = preg_replace($pattern, '\$' . $sub_total_taxes, $invoice_html);
	// calculate taxes EOC

	// calculate total amount due
	$total_due_with_taxes = check_change(round($voice_taxes + $web_conf_taxes + $total_voice_amount_due + $web_conf_charges, 2));

	$pattern = '/\$\$AMOUNT_DUE\$\$/';
	$invoice_html = preg_replace($pattern, '\$' . $total_due_with_taxes, $invoice_html);

	$pattern = '/\$\$TOTAL_AMOUNT_DUE\$\$/';
	$invoice_html = preg_replace($pattern, '\$' . $total_due_with_taxes, $invoice_html);
	// calculate total amount due EOC

	####################################################
	###### CHECK FOR ALL POSSIBLE ACCOUNT CHARGES EOC
	####################################################

	// determine if invoice payment type is check
	if($row_DetailRS00['billing_method'] == 'check'){ $paper_type = "<center><font size=\"1\">Please cut along dotted line and return with payment</font><br>--------------------------------------------------------------------------------------------------------------------------------------</center>"; }
	else { $paper_type = ''; }
	$pattern = '/\$\$INVOICE_TYPE\$\$/';
	$invoice_html = preg_replace($pattern, $paper_type, $invoice_html);
	// determine if invoice payment type is check EOC

	$xgrand_total_minutes = round($total_minutes/60,2);
	$xnet = check_change(round($total_voice_amount_due, 2));
	$xgross = check_change(round($total_voice_amount_due + $voice_taxes, 2));

	$xgrand_total_web_keys = $count_web_keys;
	$xwnet = check_change(round($web_conf_charges, 2));
	$xwgross = check_change(round($web_conf_charges + $web_conf_taxes, 2));

	// generate an invoice number now and instert invoice html into table
	$dbquery01 = "INSERT INTO $invoice_table
			SET user_id = '$corp_id',
			invoice_period_start = '$inv_start',
			invoice_period_end = '$inv_end'";
	$dbDetailRS01 = EXECUTE_SQL($dbquery01,$db_connect);
	$invoice_number = GET_LAST_ID();
	$pattern = '/\$\$INVOICE_NUMBER\$\$/';
	$invoice_html = preg_replace($pattern, $invoice_number, $invoice_html);
	$slashes_added_to_html = addslashes($invoice_html);
	$dbquery02 = "UPDATE $invoice_table
			SET invoice_html = '$slashes_added_to_html'
			WHERE invoice_id = '$invoice_number'";
	$dbDetailRS01 = EXECUTE_SQL($dbquery02,$db_connect);
	// generate an invoice number now and instert invoice html into table EOC

	$inv_ym = date('Y') . date('m');
	$path_to_pdf = html_to_postscript_to_pdf($corp_id, $billing_directory, $invoice_html, $row_DetailRS00['invoice_method'], $row_DetailRS00['billing_method'], $row_DetailRS00['billing_cc_type'], $invoice_number, $inv_ym);

	$invoice_status = 'PENDING';
	###############################################
	####### SEND INVOICE EMAIL TO CUSTOMER
	###############################################
	$email_to_send_to = $row_DetailRS00['user_email'];
	if($GLOBALS['dev_test'] == 'on'){ $email_to_send_to = "alexander@webeditors.com"; }
	$message_type = '';
	$cust_name = $row_DetailRS00['user_fname'] . ' ' . $row_DetailRS00['user_lname'];
	if($row_DetailRS00['billing_method'] == 'check' AND $row_DetailRS00['invoice_method'] == 'email')
	{
		if($total_due_with_taxes == '0.00'){ $message_type = 'check_email_zero_balance'; }
		else { $message_type = 'check_email'; }
	}
	elseif($row_DetailRS00['billing_method'] == 'check' AND $row_DetailRS00['invoice_method'] == 'paper')
	{
		if($total_due_with_taxes == '0.00'){ $message_type = 'check_paper_zero_balance'; $invoice_status = 'ZERO BALANCE'; }
		else { $message_type = 'check_paper'; }
	}
	elseif($row_DetailRS00['billing_method'] == 'credit_card' AND $row_DetailRS00['invoice_method'] == 'email')
	{
		if($total_due_with_taxes == '0.00'){ $message_type = 'credit_card_email_zero_balance'; $invoice_status = 'ZERO BALANCE'; }
		else {
			/////////////////////////////////
			// run through VeriSign gateway
			/////////////////////////////////
			$b_name = $row_DetailRS00['billing_cc_fname'] . " " . $row_DetailRS00['billing_cc_lname'];
			$b_address = $row_DetailRS00['billing_cc_address_1'] . " " . $row_DetailRS00['billing_cc_address_2'];
			$b_city = $row_DetailRS00['billing_cc_city'];
			$b_state = $row_DetailRS00['billing_cc_state'];
			$b_zip = $row_DetailRS00['billing_cc_zip'];
			if($GLOBALS['dev_test'] == 'on')
			{
				$b_cc = '4222222222222';
				$b_amount = "14.42";
			}
			else
			{
				$b_cc = decrypt_data($row_DetailRS00['billing_cc_num']);
				$b_amount = $total_due_with_taxes;
			}
			$b_exp = $row_DetailRS00['billing_cc_exp'];
			$b_cc_code = $row_DetailRS00['billing_cc_code'];
			$result = exec_gateway($b_name, $b_address, $b_city, $b_state, $b_zip, $b_cc, $b_exp, $b_cc_code, $b_amount, $invoice_number);
			// $result = 'RESPMSG=Approved'; // FOR TESTING ONLY!
			if($result == 'RESPMSG=Approved')
			{ // CC CHARGED - APPROVED!
				$invoice_status = 'APPROVED';
				$message_type = 'credit_card_email';
			}
			else
			{
				$invoice_status = 'DECLINED';
				$message_type = 'credit_card_email_error';
			}
		}
	}
	elseif($row_DetailRS00['billing_method'] == 'credit_card' AND $row_DetailRS00['invoice_method'] == 'paper'){
		if($total_due_with_taxes == '0.00'){ $message_type = 'credit_card_paper_zero_balance';  $invoice_status = 'ZERO BALANCE'; }
		else {
			/////////////////////////////////
			// run through VeriSign gateway
			/////////////////////////////////
			$b_name = $row_DetailRS00['billing_cc_fname'] . " " . $row_DetailRS00['billing_cc_lname'];
			$b_address = $row_DetailRS00['billing_cc_address_1'] . " " . $row_DetailRS00['billing_cc_address_2'];
			$b_city = $row_DetailRS00['billing_cc_city'];
			$b_state = $row_DetailRS00['billing_cc_state'];
			$b_zip = $row_DetailRS00['billing_cc_zip'];
			if($GLOBALS['dev_test'] == 'on')
			{
				$b_cc = '4222222222222';
				$b_amount = "14.42";
			}
			else
			{
				$b_cc = decrypt_data($row_DetailRS00['billing_cc_num']);
				$b_amount = $total_due_with_taxes;
			}
			$b_exp = $row_DetailRS00['billing_cc_exp'];
			$b_cc_code = $row_DetailRS00['billing_cc_code'];
			$result = exec_gateway($b_name, $b_address, $b_city, $b_state, $b_zip, $b_cc, $b_exp, $b_cc_code, $b_amount, $invoice_number);
			if($result == 'RESPMSG=Approved')
			{ // CC CHARGED - APPROVED!
				$invoice_status = 'APPROVED';
				$message_type = 'credit_card_paper';
			}
			else
			{
				$invoice_status = 'DECLINED';
				$message_type = 'credit_card_paper_error';
			}
		}
	}
	else
	{ // customer does not have billing method or invoice method defined in database! This should not happen - better safe than sorry
		$invoice_status = 'ERROR';
		$message_type = 'database_error';
	}
	//echo "<h1>$path_to_pdf</h1>";
	email_invoice_to_customer($email_to_send_to, $path_to_pdf, $row_DetailRS00['user_id'], $invoice_month, $invoice_year, $message_type, $cust_name, $master_id, $invoice_number, $inv_ym);
	###############################################
	####### SEND INVOICE EMAIL TO CUSTOMER EOC
	###############################################

	// update invoice report table
	list($inv_m, $inv_d, $inv_y) = split("-", $inv_date);
	$invoices_report_sql = "INSERT INTO $invoice_report_table
				SET invoice_number = '$invoice_number',
				client_id = '$corp_id',
				master_id = '$master_id',
				billing_method = '{$row_DetailRS00['billing_method']}',
				first_name = '{$row_DetailRS00['user_fname']}',
				last_name = '{$row_DetailRS00['user_lname']}',
				email = '{$row_DetailRS00['user_email']}',
				invoice_date = '$inv_y-$inv_m-$inv_d',
				billing_period_start = '$invoice_year-$invoice_month-01',
				billing_period_end = '$invoice_year-$invoice_month-$days_in_lastmonth',
				voice_min = '$t_m',
				current_voice_charges = '$xnet',
				current_web_charges = '$xwnet',
				taxes_and_fees = '$sub_total_taxes',
				paper_charge = '$paper_charge',
				amount_due = '$total_due_with_taxes',
				status = '$invoice_status'";
				if($row_DetailRS00['billing_method'] == 'credit_card')
				{
					list($cc_name, $cc_num) = split(":",$cc_val);
					$invoices_report_sql .= ", billing_card_type = '$cc_name',
					billing_cc_num = '$cc_num',
					billing_cc_fname = '{$row_DetailRS00['billing_cc_fname']}',
					billing_cc_lname = '{$row_DetailRS00['billing_cc_lname']}',
					billing_exp = '{$row_DetailRS00['billing_cc_exp']}'";
				}
	// echo $invoices_report_sql;
	$inDetailRS00 = EXECUTE_SQL($invoices_report_sql,$db_connect);
	// update invoice report table EOC

	return array($invoice_html, $xgrand_total_minutes, $xnet, $xgross, $xgrand_total_web_keys, $xwnet, $xwgross);
}
############################################################
function calculate_web_conference_usage($master_id, $corp_id, $idv_ids, $web_cpm){
	$db_connect = DB_CONNECTION('vvc');

	$web_conf_charges = '0.00';

	// now query dbase and count num of keys!
	$query_wc = "SELECT * FROM active_services
				WHERE user_id IN ($corp_id,$idv_ids)
				ORDER BY user_id ASC";
	$Detail_wc = EXECUTE_SQL($query_wc,$db_connect);
	$count_web_keys = 0;
	while($row_Detail_wc = mysql_fetch_assoc($Detail_wc)){
		if($row_Detail_wc['webconf_key_status'] == 'active')
		{
			$count_web_keys++;
		}
	}

	// now run through web_conf rate table!
	// Single  	1 - 4  	$59.00
	// Corporate 	5 - 9 	$49.00
	// Enterprise 	10 or more 	$39.00
	if($count_web_keys != 0) { $web_conf_charges = web_conf_table($count_web_keys,$corp_id,$web_cpm); }
	else { $web_conf_charges = '0.00'; }

	// fix web_conf_charges
	if($web_conf_charges == 0) { $web_conf_charges = '0.00'; }
	$web_conf_charges = check_change($web_conf_charges);

$web_conf_html = <<<END
  <tr>
	<td><font size="2" face="arial">Web Conferencing Charges</font></td>
	<td><font size="2" face="arial">&#36;$web_conf_charges</font></td>
  </tr>
END;
	return array($web_conf_html,$web_conf_charges,$count_web_keys);
}
############################################################
function build_cdr_invoice_reports($master_id,$corp_id,$indv_id,$qstart_date,$qend_date,$conf_cpm,$promo,$fname,$lname){
	$db_connect = DB_CONNECTION('vvc');
	/// grab conference reports from corporate user
	$query01 = "SELECT * from conference_ccdr ccdr
		INNER JOIN user_info u
		ON ccdr.user_id = u.user_id
		WHERE u.user_id = '$corp_id'
		AND conference_start between '$qstart_date' AND '$qend_date'
		ORDER BY u.user_id ASC, conference_start ASC";

	$DetailRS01 = EXECUTE_SQL($query01,$db_connect);
	$cdr = '';
	$rate_per_min = '';
	$get_rate_to_charge = 'no';
	$total_minutes = '';
	$total_amount_due = '';
	while($row_DetailRS01 = mysql_fetch_assoc($DetailRS01))
	{
		// find the rate!
		list($rate_per_min, $chargable_minutes) = rate_table($corp_id,$conf_cpm,$qstart_date,$qend_date,$promo,$master_id);

		$get_rate_to_charge = 'yes';
		$cdr .= "<tr>\n";
		$cdr .= "<td align=\"right\"><font size=\"1\" face=\"arial\">*</font> <font size=\"2\" face=\"arial\">$fname $lname</font></td>\n";
		$conf_date_time = explode(' ', $row_DetailRS01['conference_start']);
		$conf_date = $conf_date_time[0];
		$new_date = split('\-', $conf_date);
		$date_arg = $new_date[1] . '-' . $new_date[2] . '-' . $new_date[0];
		$cdr .= "<td align=\"right\"><font size=\"2\" face=\"arial\">$date_arg</font></td>\n";
		$cdr .= "<td align=\"right\"><font size=\"2\" face=\"arial\">{$row_DetailRS01['conf_name']}</font></td>\n";
		$cdr .= "<td align=\"right\"><font size=\"2\" face=\"arial\">{$row_DetailRS01['api_bridge_number']}</font></td>\n";
		$acct_num = '';
		if($row_DetailRS01['collected_account_number'] == '') { $acct_num = 'N/A'; }
		$cdr .= "<td align=\"right\"><font size=\"2\" face=\"arial\">$acct_num</font></td>\n";
		$cdr .= "<td align=\"right\"><font size=\"2\" face=\"arial\">{$row_DetailRS01['dnis']}</font></td>\n";
		$cdr .= "<td align=\"right\"><font size=\"2\" face=\"arial\">{$row_DetailRS01['high_water_level']}</font></td>\n";
		$cdr .= "<td align=\"right\"><font size=\"2\" face=\"arial\">{$row_DetailRS01['total_connect_time']}</font></td>\n";
		$minuites = round((MYSQL_TIME_TO_SECONDS($row_DetailRS01['total_connect_time'])/60) * $rate_per_min, 2);
		$total_minutes = $total_minutes + MYSQL_TIME_TO_SECONDS($row_DetailRS01['total_connect_time']);
		$cdr_cost = check_change($minuites);
		$total_amount_due = $total_amount_due + $cdr_cost;
		$total_amount_due = check_change($total_amount_due);
		$cdr .= "<td align=\"right\"><font size=\"2\" face=\"arial\">" . '\$' . "$cdr_cost</font></td>\n";
		$cdr .= "</tr>\n";
	}

	// grab conference reports from indv users!
	if($indv_id != '0')
	{ // OPEN IF SUB USERS!
		$query0d_sub = "SELECT * FROM conference_ccdr ccdr
			INNER JOIN user_info u
			ON ccdr.user_id = u.user_id
			WHERE ccdr.user_id IN ($indv_id)
			AND conference_start between '$qstart_date' AND '$qend_date'
			ORDER BY u.user_id ASC, conference_start ASC";
		$DetailRS0d_sub = EXECUTE_SQL($query0d_sub,$db_connect);
		while($row_DetailRS0d_sub = mysql_fetch_assoc($DetailRS0d_sub)){
			// find the rate!
			list($rate_per_min, $chargable_minutes) = rate_table($corp_id,$conf_cpm,$qstart_date,$qend_date,$promo,$master_id);
			$cdr .= "<tr>\n";
			$cdr .= "<td align=\"right\"><font size=\"2\" face=\"arial\">{$row_DetailRS0d_sub['user_fname']} {$row_DetailRS0d_sub['user_lname']}</font></td>\n";
			$conf_date_time = explode(' ', $row_DetailRS0d_sub['conference_start']);
			$conf_date = $conf_date_time[0];
			$new_date = split('\-', $conf_date);
			$date_arg = $new_date[1] . '-' . $new_date[2] . '-' . $new_date[0];
			$cdr .= "<td align=\"right\"><font size=\"2\" face=\"arial\">$date_arg</font></td>\n";
			$cdr .= "<td align=\"right\"><font size=\"2\" face=\"arial\">{$row_DetailRS0d_sub['conf_name']}</font></td>\n";
			$cdr .= "<td align=\"right\"><font size=\"2\" face=\"arial\">{$row_DetailRS0d_sub['api_bridge_number']}</font></td>\n";
			$acct_num = '';
			if($row_DetailRS0d_sub['collected_account_number'] == '') { $acct_num = 'N/A'; }
			$cdr .= "<td align=\"right\"><font size=\"2\" face=\"arial\">$acct_num</font></td>\n";
			$cdr .= "<td align=\"right\"><font size=\"2\" face=\"arial\">{$row_DetailRS0d_sub['dnis']}</font></td>\n";
			$cdr .= "<td align=\"right\"><font size=\"2\" face=\"arial\">{$row_DetailRS0d_sub['high_water_level']}</font></td>\n";
			$cdr .= "<td align=\"right\"><font size=\"2\" face=\"arial\">{$row_DetailRS0d_sub['total_connect_time']}</font></td>\n";
			// calculate total cost here!

			$minuites = round((MYSQL_TIME_TO_SECONDS($row_DetailRS0d_sub['total_connect_time'])/60) * $rate_per_min, 2);
			$total_minutes = $total_minutes + MYSQL_TIME_TO_SECONDS($row_DetailRS0d_sub['total_connect_time']);
			$cdr_cost = check_change($minuites);
			$total_amount_due = $total_amount_due + $cdr_cost;
			$total_amount_due = check_change($total_amount_due);
			$cdr .= "<td align=\"right\"><font size=\"2\" face=\"arial\">" . '\$' . "$cdr_cost</font></td>\n";
			$cdr .= "</tr>\n";
		}
	} // CLOSE IF SUB USERS

	if($total_amount_due == ''){ $total_amount_due = '0.00'; }
	return array($cdr,$rate_per_min,$total_minutes,$total_amount_due);

}
############################################################
function invoice_template($master_id){

// GRAB RESELLER ID's FROM /config/config.branded_sites.php
$path_to_logo_to_use_for_invoices = '';
$logo_to_use_for_invoices = '';
$blling_address_use_for_invoices = '';
	foreach($GLOBALS['AVAIL_URLS'] AS $key => $val){
		if($val['3'] == $master_id){
			$path_to_logo_to_use_for_invoices = 'images/branding/' . $val[6] . '/logo.gif';
			$logo_to_use_for_invoices = 'http://' . $val[6] . '/images/branding/' . $val[6] . '/logo.gif';
			$blling_address_use_for_invoices = 'includes/' . $master_id . '_invoice_address.php';
		}
	}

	if (file_exists($path_to_logo_to_use_for_invoices)) {
	  // file exist!!
	}
	else {
		$logo_to_use_for_invoices = 'http://www.voxconferencing.com/images/branding/voxconferencing.com/logo.gif';
	}

	if (file_exists($blling_address_use_for_invoices)) {
		$fh = fopen($blling_address_use_for_invoices, 'r');
		$content = fread($fh, filesize($blling_address_use_for_invoices));
		fclose($fh);
		$blling_address_use_for_invoices = $content;
	}
	else {
$blling_address_use_for_invoices = <<<END
<table border="0"cellspacing="0" cellpadding="0" align="right">
  <tr>
  	<td><font size="2" face="arial">VoxNet Commmunications, LLC</font></td>
  </tr>
  <tr>
  	<td><font size="2" face="arial">P.O. Box 540487</font></td>
  </tr>
  <tr>
  	<td><font size="2" face="arial">Omaha, NE 68154</font></td>
  </tr>
  <tr>
  	<td><font size="2" face="arial">800-888-8337</font></td>
  </tr>
</table>
END;
	}

$invoice_template = <<<END
<html>
<head>
<title>INVOICE</title>
</head>
<body>
<table width="670" cellspacing="0" cellpadding="0">
  <tr>
  	<td><img src="$logo_to_use_for_invoices" align="left"></td>
	<td>
<table border="1" cellspacing="0" cellpadding="2" align="right">
  <tr>
  	<td width="90"><font size="2" face="arial"><strong>Due Date</strong></font></td>
	<td width="90"><font size="2" face="arial"><strong>Invoice Date</strong></font></td>
	<td width="90"><font size="2" face="arial"><strong>Amount Due</strong></font></td>
  </tr>
  <tr>
  	<td><font size="2" face="arial">\$\$DUE_DATE\$\$</font></td>
	<td><font size="2" face="arial">\$\$INVOICE_DATE\$\$</font></td>
	<td><font size="2" face="arial">\$\$AMOUNT_DUE\$\$</font></td>
  </tr>
   <tr>
  	<td><font size="2" face="arial"><strong>Client ID</strong></font></td>
	<td><font size="2" face="arial"><strong>Invoice Number</strong></font></td>
	<td><font size="2" face="arial"><strong>Amount Paid</strong></font></td>
  </tr>
  <tr>
  	<td><font size="2" face="arial">\$\$CLIENT_ID\$\$</font></td>
	<td><font size="2" face="arial">\$\$INVOICE_NUMBER\$\$</font></td>
	<td>&nbsp;</td>
  </tr>
</table>
	</td>
  </tr>
</table>
	<br>
<table width="670" cellspacing="0" cellpadding="0">
  <tr>
	<td>
<table border="0" cellspacing="0" cellpadding="0" align="left">
  <tr>
  	<td><font size="2" face="arial">\$\$FIRST_NAME\$\$ \$\$LAST_NAME\$\$ \$\$COMPANY\$\$</font></td>
  </tr>
  <tr>
  	<td><font size="2" face="arial">\$\$ADDRESS\$\$</font></td>
  </tr>
  <tr>
  	<td><font size="2" face="arial">\$\$CITY\$\$, \$\$STATE\$\$, \$\$ZIP\$\$</font></td>
  </tr>
  <tr>
  	<td><font size="2" face="arial">\$\$CC_INFO\$\$</font></td>
  </tr>
</table>
	</td>
	<td>
$blling_address_use_for_invoices
</td></tr></table>
<br>
<table width="670" cellspacing="0" cellpadding="0"><tr><td>
\$\$INVOICE_TYPE\$\$
<br>
</td></tr></table>
<table border="1" cellspacing="0" cellpadding="4" align="left">
  <tr>
  	<td><font size="2" face="arial">Billing Period Start</font></td>
	<td><font size="2" face="arial">\$\$START\$\$</font></td>
  </tr>
  <tr>
	<td><font size="2" face="arial">Billing Period End</font></td>
	<td><font size="2" face="arial">\$\$END\$\$</font></td>
  </tr>
  <tr>
	<td><font size="2" face="arial">Voice Conferencing Rate Per Min.</font></td>
	<td><font size="2" face="arial">\$\$RATE\$\$</font></td>
  </tr>
  <tr>
  	<td><font size="2" face="arial">Voice Conferencing Charges</font></td>
	<td><font size="2" face="arial">\$\$CURRENT_ACTIVITY_CHARGES\$\$</font></td>
  </tr>
  \$\$WEB_CONF\$\$
  \$\$PROMO\$\$
  <tr>
  	<td><font size="2" face="arial">State, Local, Federal Tax and Fees</font></td>
	<td><font size="2" face="arial">\$\$TAXES_AND_FEES\$\$</font></td>
  </tr>
  \$\$PAPER_BILL\$\$
  <tr>
  	<td><font size="2" face="arial"><strong>Total Amount Due:</strong></font></td>
	<td><font size="2" face="arial">\$\$TOTAL_AMOUNT_DUE\$\$</font></td>
  </tr>
</table>
<br>
<br>
<br>
<br>
<br>
<br>
<br>
<br>
<br>
<br>
<table border="1" cellspacing="0" cellpadding="4" align="left" width="670">
  <tr>
	<td colspan="9"><font size="3" face="arial"><strong>Account Activity</strong></font><br><font size="1">Administrative usage marked with an asterisk (*)<br>\$\$T_M\$\$</font></td>
  </tr>
  <tr>
  	<td align="center"><font size="2" face="arial"><strong>User<br>Name</strong></font></td>
	<td align="center"><font size="2" face="arial"><strong>Conf.<br>Date</strong></font></td>
  	<td align="center"><font size="2" face="arial"><strong>Conf.<br>Name</strong></font></td>
	<td align="center"><font size="2" face="arial"><strong>Conf.<br>Number</strong></font></td>
	<td align="center"><font size="2" face="arial"><strong>Accnt.<br>Code</strong></font></td>
	<td align="center"><font size="2" face="arial"><strong>Dial in<br>Number</strong></font></td>
	<td align="center"><font size="2" face="arial"><strong>No. of<br>Atten.</strong></font></td>
	<td align="center"><font size="2" face="arial"><strong>Total<br>Minutes</strong></font></td>
	<td align="center"><font size="2" face="arial"><strong>Cost</strong></font></td>
  </tr>
  \$\$CDR\$\$
  <tr>
  	<td colspan="9"><center><font size="2" face="arial">Powered by VoxNet Communications, LLC.</font></center></td>
  </tr>
</table>
</body>
</html>
END;
	return $invoice_template;
}
############################################################
function rate_table($user_id,$current_rate,$qstart_date,$qend_date,$promo,$master_id){
	$db_connect = DB_CONNECTION('vvc');

	$query01 = "SELECT * from conference_ccdr ccdr
	INNER JOIN user_info u
	ON ccdr.user_id = u.user_id
	WHERE u.user_id = '$user_id'
	AND conference_start between '$qstart_date' AND '$qend_date'
	ORDER BY u.user_id ASC, conference_start ASC";
	$DetailRS01 = EXECUTE_SQL($query01,$db_connect);
	$sub_min = '';
	while($row_DetailRS01 = mysql_fetch_assoc($DetailRS01)){  // open first while
		$sub_min = $sub_min + (MYSQL_TIME_TO_SECONDS($row_DetailRS01['total_connect_time']));
	}

	// grab conference reports from indv users!
	$query0d_sub = "SELECT * FROM conference_ccdr ccdr
	INNER JOIN user_info u
	ON ccdr.user_id = u.user_id
	WHERE ccdr.user_id IN (";

	$query0d = "SELECT * FROM user_info_relationships WHERE corporate_user_info_id = '$user_id'";
	$DetailRS0d = EXECUTE_SQL($query0d,$db_connect);
	$sub_users = 'no';
	while($row_DetailRS0d = mysql_fetch_assoc($DetailRS0d)){
		if($row_DetailRS0d['individual_user_info_id'] != 0){
			$sub_users = 'yes';
			$query0d_sub .= "{$row_DetailRS0d['individual_user_info_id']},";
		}
	}

	$query0d_sub = substr("$query0d_sub", 0, -1); # removes the last character
	$query0d_sub .= ") AND conference_start between '$qstart_date' AND '$qend_date'
	ORDER BY u.user_id ASC, conference_start ASC";
	if($sub_users == 'yes'){ // OPEN IF SUB USERS!
		$DetailRS0d_sub = EXECUTE_SQL($query0d_sub,$db_connect);
		while($row_DetailRS0d_sub = mysql_fetch_assoc($DetailRS0d_sub)){
			$sub_min = $sub_min + (MYSQL_TIME_TO_SECONDS($row_DetailRS0d_sub['total_connect_time']));
		}
	}

	$promo_minutes_remainding = '';
	if($promo == 'yes'){
		$query_p = "SELECT * FROM promo_relationships r
		INNER JOIN promo_definition d
		ON r.promo_definition_id = d.promo_definition_id
		WHERE r.user_id = '$user_id'";
		$DetailRS0p = EXECUTE_SQL($query_p,$db_connect);
		$row_DetailRS0p = mysql_fetch_assoc($DetailRS0p);

		$promo_query = "SELECT promo_script FROM promo_type WHERE promo_type_id = '{$row_DetailRS0p['promo_type_id']}'";
		$DetailRS0promo = EXECUTE_SQL($promo_query,$db_connect);
		$row_DetailRS0promo = mysql_fetch_assoc($DetailRS0promo);

		$promo_script = "{$row_DetailRS0promo['promo_script']}";
		if($promo_script == 'free_x_minutes.php'){
			$promo_minutes_remainding = $row_DetailRS0p['value'];
		}
	}


	$total_connect_time = round($sub_min/60,2);
	$rate_to_return = '';


	# Determine if flat or rate table
	$sql_rate = "SELECT pricing_model FROM user_info WHERE user_id = '$user_id'";
	$DetailR_Rate = EXECUTE_SQL($sql_rate,$db_connect);
	$row_DetailR_Rate = mysql_fetch_assoc($DetailR_Rate);

	if($row_DetailR_Rate['pricing_model'] == 'flat_rate'){
		$rate_to_return = $current_rate;
	}
	elseif($row_DetailR_Rate['pricing_model'] == 'mid_rate'){ // open elseif mid_rate
		## VOX RATE TABLE
		if($total_connect_time <= '999'){
			if($current_rate < '.15'){
				$rate_to_return = $current_rate;
			}
			else {
				$rate_to_return = '.15';
			}
		}
		else if($total_connect_time <= '1999'){
			if($current_rate < '.14'){
				$rate_to_return = $current_rate;
			}
			else {
				$rate_to_return = '.14';
			}
		}
		else if($total_connect_time <= '2999'){
			if($current_rate < '.13'){
				$rate_to_return = $current_rate;
			}
			else {
				$rate_to_return = '.13';
			}
		}
		else if($total_connect_time <= '3999'){
			if($current_rate < '.12'){
				$rate_to_return = $current_rate;
			}
			else {
				$rate_to_return = '.12';
			}
		}
		else if($total_connect_time <= '4999'){
			if($current_rate < '.11'){
				$rate_to_return = $current_rate;
			}
			else {
				$rate_to_return = '.11';
			}
		}
		else if($total_connect_time <= '7999'){
			if($current_rate < '.10'){
				$rate_to_return = $current_rate;
			}
			else {
				$rate_to_return = '.10';
			}
		}
		else if($total_connect_time <= '9999'){
			if($current_rate < '.09'){
				$rate_to_return = $current_rate;
			}
			else {
				$rate_to_return = '.09';
			}
		}
		else if($total_connect_time > '10000'){
			if($current_rate < '.08'){
				$rate_to_return = $current_rate;
			}
			else {
				$rate_to_return = '.08';
			}
		}
	} // close elseif mid_rate
	elseif($row_DetailR_Rate['pricing_model'] == 'low_rate'){ // open elseif low_rate
		if($total_connect_time <= '1999'){
			if($current_rate < '.11'){
				$rate_to_return = $current_rate;
			}
			else {
				$rate_to_return = '.11';
			}
		}
		else if($total_connect_time <= '4999'){
			if($current_rate < '.09'){
				$rate_to_return = $current_rate;
			}
			else {
				$rate_to_return = '.09';
			}
		}
		else if($total_connect_time <= '9999'){
			if($current_rate < '.07'){
				$rate_to_return = $current_rate;
			}
			else {
				$rate_to_return = '.07';
			}
		}
		else if($total_connect_time > '10000'){
			if($current_rate < '.05'){
				$rate_to_return = $current_rate;
			}
			else {
				$rate_to_return = '.05';
			}
		}
	} // close elseif low_rate

	if($rate_to_return == ''){
		//grab default rate from database - this means no miutes has been used!
		$default_rate_query = "SELECT conf_cpm FROM user_info WHERE user_id = '$user_id'";
		$Detail_rate = EXECUTE_SQL($default_rate_query,$db_connect);
		$row_rate = mysql_fetch_assoc($Detail_rate);
		$rate_to_return = $row_rate['conf_cpm'];
		$total_connect_time = '0';
	}

	return array($rate_to_return, $total_connect_time);

}
?>

