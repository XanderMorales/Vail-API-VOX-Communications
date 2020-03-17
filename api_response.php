<?php
/*
Authors:
Alexander Nietzen Morales
*/

class API_Response { // Begin Class Closure
	var $request_type;
	var $url_string;
	var $api_url;
	var $api_params;
	var $api_post;
	var $api_response;
	var $api_content;
	
	#######################################################################################
	function API_Response($request_type,$url_string){
		$this->request_type = $request_type;
		$this->url_string = $url_string;
		$this->api_url = $GLOBALS['VAIL_URL'];
		$this->api_params = "admin_id={$GLOBALS['VAIL_ADMIN']}&admin_password={$GLOBALS['VAIL_PASS']}&requestType=" . $this->request_type . "&" . $this->url_string;
		$this->api_post;
		$this->api_response;
		$this->api_content;

		if($this->request_type == 'addUser'){
			// API Return: success=accnt_number or error
			$this->api_post = $this->Curl_Post();
			if($this->api_post == 'yes'){
				$this->api_response = str_replace("\n", "",$this->api_content);
				if(!(eregi("^success", $this->api_response) == 1)){
					$this->api_response = $this->Check_Errors($this->api_response);
				}
				else {
					$this->Api_Success($this->api_response);
				}
			}
		}

		else if($this->request_type == 'editUser'){
			// API Return: success=true or error
			$this->api_post = $this->Curl_Post();
			if($this->api_post == 'yes'){
				$this->api_response = str_replace("\n", "",$this->api_content); // remove new lines
				$this->api_response = str_replace(" ", "",$this->api_content); // remove blank spaces
				if(!(eregi("^success", $this->api_response) == 1)){
					$this->api_response = $this->Check_Errors($this->api_response);
				}
				else {
					$this->api_response = str_replace("\n", "",$this->api_content); // remove new lines
					$this->api_response = str_replace(" ", "",$this->api_response); // remove blank spaces!
					$this->Api_Success($this->api_response);
				}
			}
		}

		else if($this->request_type == 'addConference'){
			// API Return: success=[conference number] or error
			$this->api_post = $this->Curl_Post();
			if($this->api_post == 'yes'){
				$this->api_response = str_replace("\n", "",$this->api_content);
				if(!(eregi("^success", $this->api_response) == 1)){
					$this->api_response = $this->Check_Errors($this->api_response);
				}
				else {
					$this->api_response = str_replace(" ", "",$this->api_response); // there is a blank  space after the "=", so let's fix it.
					$this->Api_Success($this->api_response);
				}
			}
		}

		else if($this->request_type == 'editConference'){
			// API Return: success=true or error
			$this->api_post = $this->Curl_Post();
			if($this->api_post == 'yes'){
				$this->api_response = str_replace("\n", "",$this->api_content); // remove new lines
				$this->api_response = str_replace(" ", "",$this->api_content); // remove blank spaces
				if(!(eregi("^success", $this->api_response) == 1)){
					$this->api_response = $this->Check_Errors($this->api_response);
				}
				else {
					$this->api_response = str_replace("\n", "",$this->api_content); // remove new lines
					$this->api_response = str_replace(" ", "",$this->api_response); // there is a blank  space after the "=", so let's fix it.
					$this->Api_Success($this->api_response);
				}
			}
		}

		else if($this->request_type == 'deleteConference'){
			// API Return: success=true or error
			$this->api_post = $this->Curl_Post();
			if($this->api_post == 'yes'){
				$this->api_response = str_replace("\n", "",$this->api_content);
				if(!(eregi("^success", $this->api_response) == 1)){
					$this->api_response = $this->Check_Errors($this->api_response);
				}
				else {
					$this->api_response = str_replace(" ", "",$this->api_response); // remove blank spaces!
					$this->Api_Success($this->api_response);
				}
			}
		}

		else if($this->request_type == 'activeList'){
			// API Return: tab delimitated list (participant's ani - participant's call status)
			$this->api_post = $this->Curl_Post();
			if($this->api_post == 'yes'){
				$this->api_response = $this->api_content;
				$this->Api_Success($this->api_response);
			}
		}

		else if($this->request_type == 'activeDrop'){
			$this->api_post = $this->Curl_Post();
			if($this->api_post == 'yes'){
				$this->api_response = str_replace("\n", "",$this->api_content);
				if(!(eregi("^success", $this->api_response) == 1)){
					$this->api_response = $this->Check_Errors($this->api_response);
				}
				else {
					$this->Api_Success($this->api_response);
				}
			}
		}

		else if($this->request_type == 'activeMute'){
			$this->api_post = $this->Curl_Post();
			if($this->api_post == 'yes'){
				$this->api_response = str_replace("\n", "",$this->api_content);
				if(!(eregi("^success", $this->api_response) == 1)){
					$this->api_response = $this->Check_Errors($this->api_response);
				}
				else {
					$this->Api_Success($this->api_response);
				}
			}
		}

		else if($this->request_type == 'active_MuteAll'){
			$this->api_post = $this->Curl_Post();
			if($this->api_post == 'yes'){
				$this->api_response = str_replace("\n", "",$this->api_content);
				if(!(eregi("^success", $this->api_response) == 1)){
					$this->api_response = $this->Check_Errors($this->api_response);
				}
				else {
					$this->Api_Success($this->api_response);
				}
			}
		}

		else if($this->request_type == 'activeUnmute'){
			$this->api_post = $this->Curl_Post();
			if($this->api_post == 'yes'){
				$this->api_response = str_replace("\n", "",$this->api_content);
				if(!(eregi("^success", $this->api_response) == 1)){
					$this->api_response = $this->Check_Errors($this->api_response);
				}
				else {
					$this->Api_Success($this->api_response);
				}
			}
		}

		else if($this->request_type == 'active_UnmuteAll'){
			$this->api_post = $this->Curl_Post();
			if($this->api_post == 'yes'){
				$this->api_response = str_replace("\n", "",$this->api_content);
				if(!(eregi("^success", $this->api_response) == 1)){
					$this->api_response = $this->Check_Errors($this->api_response);
				}
				else {
					$this->Api_Success($this->api_response);
				}
			}
		}

		else if($this->request_type == 'activeDial'){
			$this->api_post = $this->Curl_Post();
			if($this->api_post == 'yes'){
				$this->api_response = str_replace("\n", "",$this->api_content);
				if(!(eregi("^success", $this->api_response) == 1)){
					$this->api_response = $this->Check_Errors($this->api_response);
				}
				else {
					$this->Api_Success($this->api_response);
				}
			}
		}

		else if($this->request_type == 'removeBridge'){
			// API Return: success=[The Standing Bridge was successfully removed] or error
			$this->api_post = $this->Curl_Post();
			if($this->api_post == 'yes'){
				$this->api_response = str_replace("\n", "",$this->api_content);
				if($this->api_response != "The Standing Bridge was successfully removed."){
					$this->api_response = $this->Check_Errors($this->api_response);
				}
				else {
					$this->Api_Success($this->api_response);
					$this->api_response = 'Conference successfully deleted';
				}
			}
		}

		else if($this->request_type == 'activeUnqueue'){
		$this->api_post = $this->Curl_Post();
			if($this->api_post == 'yes'){
				$this->api_response = str_replace("\n", "",$this->api_content);
				if(!(eregi("^success", $this->api_response) == 1)){
					$this->api_response = $this->Check_Errors($this->api_response);
				}
				else {
					$this->Api_Success($this->api_response);
				}
			}
		}

	}
	#######################################################################################
	function Check_Errors($api_response){
	$errors['timedate_passed']  = 'The time and date you have selected has already passed.';
	$errors['timedate_invalid'] = 'The time and date you have selected is not valid. Please try again.';
	$errors['conf_active'] = 'This conference is currently active and cannot be edited. Please try again later.';
	$errors['code_length']  = 'Your security code must be 4 digits. Please try again.';
	$errors['party_drop']  = 'The attempt to drop this party failed. Please try again.';
	$errors['party_mute']  = 'The attempt to mute this party failed. Please try again.';
	$errors['party_mute_all']   = 'The attempt to mute all parties failed. Please try again.';
	$errors['party_unmute'] = 'The attempt to unmute this party failed. Please try again.';
	$errors['party_unmute_all']  = 'The attempt to unmute all parties failed. Please try again.';
	$errors['party_shutdown']  = 'The attempt to add this party failed. The conference is shutting down.';
	$errors['party_full']  = 'The attempt to add this party failed. The conference is currently full.';
	$errors['party_add']   = 'The system encountered a problem adding a party. Please try again.';
	$errors['account_closed']   = 'Your account has been closed. Please contact your service provider.';
	$errors['valid_areacode'] = ' is not an accepted areacode. Please try again.';
	$errors['system_phone']  = 'The conferencing system cannot dial itself. Please try again.';
	$errors['valid_maxParties']  = 'Maximum Number of Participants must be between 2 and 150. Please try again.';
	$errors['valid_joinWin']  = 'Maximum time limit to join conference must be between 2 and 150. Please try again.';

	$errors['valid_accountNo']  = 'AccountNo setting is invalid. Please try again.';
	$errors['valid_dnis']  = 'Dnis setting is invalid. Please try again.';
	$errors['valid_status']  = 'Status setting is invalid. Please try again.';
	$errors['valid_timeZone']  = 'TimeZone setting is invalid. Please try again.';
	$errors['valid_onHoldMusic']  = 'OnHoldMusic setting is invalid. Please try again.';
	$errors['valid_chimeInMusic'] = 'ChimeInMusic setting is invalid. Please try again.';
	$errors['valid_outbndPrmpt']  = 'OutbndPrmpt setting is invalid. Please try again.';
	$errors['valid_loginPage'] = 'LoginPage setting is invalid. Please try again.';
	$errors['valid_state'] = 'State setting is invalid. Please try again.';
	
	$errors['admin_permissions']	= 'You do not have that admin privilege.';
	$errors['invalid_salutation']  =  'You eneterd an improper Salutation or no Salutation.';
	$errors['no_first_name'] =  'You did not enter a First Name.';
	$errors['no_last_name'] = 'You did not enter a Last Name.';
	$errors['invalid_email'] = 'You entered an invalid Email Address.';
	$errors['no_email']	= 'You did not enter an Email Address.';
	$errors['invalid_company_name'] =  'You entered an invalid Company Name.';
	$errors['invalid_job_title'] = 'You entered an invalid Job Title.';
	$errors['invalid_office'] = 'You entered an invalid Office.';
	$errors['invalid_department'] = 'You entered an invalid Department.';
	$errors['invalid_address'] = 'You entered an invalid Address.';
	$errors['invalid_city'] = 'You entered an invalid City.';
	$errors['invalid_state'] = 'You entered an invalid State.';
	$errors['invalid_zipcode'] = 'You entered an invalid State.';
	$errors['invalid_telephone'] = 'You entered an invalid Telephone Number.';
	$errors['user_exists'] = 'This user already exists.';
	$errors['no_account_number'] = 'Account Number is missing.';
	$errors['invalid_dnis']= 'DNIS not on Reseller list.';
	$errors['no_uid'] = 'User ID was not found.';
	$errors['invalid_security_code'] = 'You entered an invalid Security Code.';
	$errors['active_conference']= 'Settings cannot be changed while a conference is active. Please try again later.';
	$errors['invalid_vanity_bridge'] = 'Error - You entered an invalid Vanity Bridge number.';
	$errors['duplicate_vanity_bridge'] = 'You entered a Vanity Bridge number already in use.';
	$errors['ten_bridges'] = '10 Bridges already exists for this account.';
	$errors['no_bridge_number']  = 'Bridge Number is missing.';
	$errors['no_login_uid'] = 'Please contact customer service.';
	$errors['no_conference_number'] = 'Conference is missing.';
	$errors['invalid_confNo'] = 'You entered an invalid Conference Number.';
	$errors['no_dnis'] = 'You did not enter a DNIS number.';
	$errors['valid_accountNo_confNo'] = 'The Conference Number was not found under the Account given.';
	$errors['conf_not_active'] = 'Not an Active Conference.';
	$errors['no_ani'] = 'You did not enter a Party Ani Number.';
	$errors['invalid_ani']  = 'You entered an invalid Party Ani Number.';

	$errors['req_login']  = 'You must type your Login ID / Email Address.';
	$errors['req_password']  = 'You must type your password.';
	$errors['req_name_sir']  = 'Salutation is a required field. Please try again.';
	$errors['req_name_salutation'] = 'Salutation is a required field. Please try again.';
	$errors['req_name_first']  = 'First name is a required field. Please try again.';
	$errors['req_name_last']  = 'Last name is a required field. Please try again.';
	$errors['req_email']  = 'A valid email address is required. Please try again.';

	$errors['req_pin_current'] = 'Current account PIN is a required field. Please try again.';

	$errors['req_pass_current'] = 'Web password is a required field. Please try again.';

	$errors['valid_admin'] = 'admin setting is invalid. Please try again.';
	$errors['valid_login'] = 'The Login ID / Email you typed is invalid. Please try again.';
	$errors['valid_password'] = 'Your password was not recognized. Please try again.';
	$errors['valid_email'] = 'A valid email address is required. Please try again.';

	$errors['valid_pin_match']  = 'Current account PIN does not match our records. Please try again.';
	$errors['valid_pin_length'] = 'Your new account PIN must be 4 digits. Please try again.';
	$errors['valid_pin_num']    = 'Your new account PIN must be 4 digits. Please try again.';
	$errors['valid_pins'] = 'Your new account PIN entries did not match. Please try again.';

	$errors['valid_pass_match']  = 'Web password does not match our records. Please try again.';
	$errors['valid_pass_length'] = 'Your new web password must be at least 8 characters long. Please try again.';
	$errors['valid_pass_space']  = 'Your new web password must not contain any spaces. Please try again.';
	$errors['valid_pass_alpha']  = 'Your new web password must contain at least one letter. Please try again.';
	$errors['valid_pass_num'] = 'Your new web password must contain at least one numeric character. Please try again.';
	$errors['valid_pass_login']  = 'Your new web password must not match your login. Please try again.';
	$errors['valid_passes']  = 'Your new web password entries did not match. Please try again.';

	$errors['error_save'] = 'There was an error saving your changes. Please try again at a later time.';
	$errors['error_password'] = 'Your Login ID / Email Address was not found. Please try again.';
	$errors['error_login'] = 'Your login ID / Email Address was not found. Please try again.';

	$errors['lock_login'] = 'Your account has been locked due to multiple unsuccessful login attempts. Please try again in ';
	
	## Our Defined Error for API:
	$errors['vail_server_down'] = 'API SERVER DOWN';
	$errors['access_denied'] = "You don't have permission to access";
	$errors['time_error'] = 'Error - The time and date you have selected has already passed.';



	###############################################################################################
	## START VVC ERRORS
	###############################################################################################
	$VVCerrors['timedate_passed']  = 'The time and date you have selected has already passed.';
	$VVCerrors['timedate_invalid'] = 'The time and date you have selected is not valid. Please try again.';
	$VVCerrors['conf_active'] = 'This conference is currently active and cannot be edited. Please try again later.';
	$VVCerrors['code_length']  = 'Your security code must be 4 digits. Please try again.';
	$VVCerrors['party_drop']  = 'The attempt to drop this party failed. Please try again.';
	$VVCerrors['party_mute']  = 'The attempt to mute this party failed. Please try again.';
	$VVCerrors['party_mute_all']   = 'The attempt to mute all parties failed. Please try again.';
	$VVCerrors['party_unmute'] = 'The attempt to unmute this party failed. Please try again.';
	$VVCerrors['party_unmute_all']  = 'The attempt to unmute all parties failed. Please try again.';
	$VVCerrors['party_shutdown']  = 'The attempt to add this party failed. The conference is shutting down.';
	$VVCerrors['party_full']  = 'The attempt to add this party failed. The conference is currently full.';
	$VVCerrors['party_add']   = 'The system encountered a problem adding a party. Please try again.';
	$VVCerrors['account_closed']   = 'Your account has been closed. Please contact your service provider.';
	$VVCerrors['valid_areacode'] = 'This is not an accepted areacode. Please try again.';
	$VVCerrors['system_phone']  = 'The conferencing system cannot dial itself. Please try again.';
	$VVCerrors['valid_maxParties']  = 'Maximum Number of Participants must be between 2 and 150. Please try again.';
	$VVCerrors['valid_joinWin']  = 'Maximum time limit to join conference must be between 2 and 150. Please try again.';

	$VVCerrors['valid_accountNo']  = 'Account Number setting is invalid. Please try again.';
	$VVCerrors['valid_dnis']  = 'Dial-in Number is unavailable. Please select another.';
	$VVCerrors['valid_status']  = 'Status setting is invalid. Please try again.';
	$VVCerrors['valid_timeZone']  = 'TimeZone setting is invalid. Please try again.';
	$VVCerrors['valid_onHoldMusic']  = 'On Hold Music setting is invalid. Please try again.';
	$VVCerrors['valid_chimeInMusic'] = 'Chime In Music setting is invalid. Please try again.';
	$VVCerrors['valid_outbndPrmpt']  = 'Out bounnd Prompt setting is invalid. Please try again.';
	$VVCerrors['valid_loginPage'] = 'LoginPage setting is invalid. Please try again.';
	$VVCerrors['valid_state'] = 'State setting is invalid. Please try again.';
	
	$VVCerrors['admin_permissions']	= 'You do not have that admin privilege.';
	$VVCerrors['invalid_salutation']  =  'You eneterd an improper Salutation or no Salutation.';
	$VVCerrors['no_first_name'] =  'You did not enter a First Name.';
	$VVCerrors['no_last_name'] = 'You did not enter a Last Name.';
	$VVCerrors['invalid_email'] = 'You entered an invalid Email Address.';
	$VVCerrors['no_email']	= 'You did not enter an Email Address.';
	$VVCerrors['invalid_company_name'] =  'You entered an invalid Company Name.';
	$VVCerrors['invalid_job_title'] = 'You entered an invalid Job Title.';
	$VVCerrors['invalid_office'] = 'You entered an invalid Office.';
	$VVCerrors['invalid_department'] = 'You entered an invalid Department.';
	$VVCerrors['invalid_address'] = 'You entered an invalid Address.';
	$VVCerrors['invalid_city'] = 'You entered an invalid City.';
	$VVCerrors['invalid_state'] = 'You entered an invalid State.';
	$VVCerrors['invalid_zipcode'] = 'You entered an invalid State.';
	$VVCerrors['invalid_telephone'] = 'You entered an invalid Telephone Number.';
	$VVCerrors['user_exists'] = 'This user already exists.';
	$VVCerrors['no_account_number'] = 'Account Number is missing.';
	$VVCerrors['invalid_dnis']= 'Dial-in number is not on Reseller list.';
	$VVCerrors['no_uid'] = 'User ID was not found.';
	$VVCerrors['invalid_security_code'] = 'You entered an invalid Security Code.';
	$VVCerrors['active_conference']= 'Settings cannot be changed while a conference is active. Please try again later.';
	$VVCerrors['invalid_vanity_bridge'] = 'Sorry that Conference Number already exists, please try another.';
	$VVCerrors['duplicate_vanity_bridge'] = 'Sorry that Conference Number already exists, please try another.';
	$VVCerrors['ten_bridges'] = '10 Bridges already exists for this account.';
	$VVCerrors['no_bridge_number'] = 'Conference Number is missing please try again.';
	$VVCerrors['no_login_uid'] = 'Please contact customer service.';
	$VVCerrors['no_conference_number'] = 'Conference is missing.';
	$VVCerrors['invalid_confNo'] = 'You entered an invalid Conference Number.';
	$VVCerrors['no_dnis'] = 'You did not select a Dial-in number.';
	$VVCerrors['valid_accountNo_confNo'] = 'The Conference Number was not found under the Account given.';
	$VVCerrors['conf_not_active'] = 'Not an Active Conference.';
	$VVCerrors['no_ani'] = 'You did not enter a phone number, please try again.';
	$VVCerrors['invalid_ani']  = 'You entered an invalid phone Number, please try again.';

	$VVCerrors['req_login']  = 'You must type your Login ID / Email Address.';
	$VVCerrors['req_password']  = 'You must type your password.';
	$VVCerrors['req_name_sir']  = 'Salutation is a required field. Please try again.';
	$VVCerrors['req_name_salutation'] = 'Salutation is a required field. Please try again.';
	$VVCerrors['req_name_first']  = 'First name is a required field. Please try again.';
	$VVCerrors['req_name_last']  = 'Last name is a required field. Please try again.';
	$VVCerrors['req_email']  = 'A valid email address is required. Please try again.';

	$VVCerrors['req_pin_current'] = 'Current account PIN is a required field. Please try again.';

	$VVCerrors['req_pass_current'] = 'Web password is a required field. Please try again.';

	$VVCerrors['valid_admin'] = 'admin setting is invalid. Please try again.';
	$VVCerrors['valid_login'] = 'The Login ID / Email you typed is invalid. Please try again.';
	$VVCerrors['valid_password'] = 'Your password was not recognized. Please try again.';
	$VVCerrors['valid_email'] = 'A valid email address is required. Please try again.';

	$VVCerrors['valid_pin_match']  = 'Current account PIN does not match our records. Please try again.';
	$VVCerrors['valid_pin_length'] = 'Your new account PIN must be 4 digits. Please try again.';
	$VVCerrors['valid_pin_num']    = 'Your new account PIN must be 4 digits. Please try again.';
	$VVCerrors['valid_pins'] = 'Your new account PIN entries did not match. Please try again.';

	$VVCerrors['valid_pass_match']  = 'Web password does not match our records. Please try again.';
	$VVCerrors['valid_pass_length'] = 'Your new web password must be at least 8 characters long. Please try again.';
	$VVCerrors['valid_pass_space']  = 'Your new web password must not contain any spaces. Please try again.';
	$VVCerrors['valid_pass_alpha']  = 'Your new web password must contain at least one letter. Please try again.';
	$VVCerrors['valid_pass_num'] = 'Your new web password must contain at least one numeric character. Please try again.';
	$VVCerrors['valid_pass_login']  = 'Your new web password must not match your login. Please try again.';
	$VVCerrors['valid_passes']  = 'Your new web password entries did not match. Please try again.';

	$VVCerrors['error_save'] = 'There was an error saving your changes. Please try again.';
	$VVCerrors['error_password'] = 'Your Login ID / Email Address was not found. Please try again.';
	$VVCerrors['error_login'] = 'Your login ID / Email Address was not found. Please try again.';

	$VVCerrors['lock_login'] = 'Your account has been locked due to multiple unsuccessful login attempts. Please try again in 15 minutes';
	
	## Our Defined Error for API:
	$VVCerrors['vail_server_down'] = 'API SERVER DOWN';
	$VVCerrors['access_denied'] = "You don't have permission to access";
	$VVCerrors['time_error'] = 'The time and date you have selected has already passed. Please try a later time.';
	###############################################################################################
	## STOP VVC ERRORS
	###############################################################################################



		$error_to_return = '';
		$found_error = 'no';
		foreach($errors as $name=>$value){
			if($this->api_response == $value){
				$found_error = 'yes';
				$error_to_return = $VVCerrors[$name];
			}
		}

		if(ereg($errors['vail_server_down'], $api_response)){
			$found_error = 'yes';
			$error_to_return = 'Server is down, please try again at a later time';
		}
		else if(ereg($errors['access_denied'], $api_response)){
			$found_error = 'yes';
			$error_to_return = 'Access denied, please try again at a later time';
		}
		
		if($found_error == 'no'){
			$found_error = 'yes';
			// $error_to_return = 'No Defined Error Found in %ERRORS: ' . $api_response;
			$error_to_return = $api_response;
		}

		// open API ERROR LOG FILE
		$out = fopen("{$GLOBALS['VAIL_ERROR_LOG']}","a");
		$error_occured = date("D M j g:i:s T Y") . ": ";
		$apr_arg = "USER ID: " . $_SESSION['user_id'] . " API URL: " . $this->api_url . " API PARAMS: " . $this->api_params . " API RESPONSE: ". $api_response;
		$error_to_write = "######## BEGIN NEW ERROR RESPONE ########\n" . $error_occured . $apr_arg . "\n";
		fwrite($out,$error_to_write); 
		fclose($out); 
		// close API ERROR LOG FILE

		return $error_to_return;
	}
	#######################################################################################
	function Curl_Post(){
		$proceed = '';
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_POST,1); // set url to post to 
		curl_setopt($ch, CURLOPT_URL,$this->api_url);
		curl_setopt($ch, CURLOPT_POSTFIELDS,$this->api_params);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION,1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1); // return into a variable 
		$this->api_content = curl_exec($ch); // run the whole process
		if (curl_errno($ch)) { // no connectio to vail API!
			$this->api_response = $this->Check_Errors('API SERVER DOWN');
			$proceed = 'no';
		}
		else {
			curl_close($ch);
			$proceed = 'yes';
		}
		return $proceed;
	}
	#######################################################################################
	function Api_Success($api_response){
		// open API ERROR LOG FILE
		$out = fopen("{$GLOBALS['VAIL_ACCESS_LOG']}","a");
		$access_occured = date("D M j g:i:s T Y") . ": ";
		
		// begin fix for autamtic bridge creation on acocunt sign ups! starts in ./functions/signup.php
		if(isset($_SESSION['user_id'])){ $u_id = $_SESSION['user_id']; }
		else { $u_id = $_REQUEST['u_id']; }
		
		$apr_arg = "USER ID: " . $u_id . " API URL: " . $this->api_url . " API PARAMS: " . $this->api_params . " API RESPONSE: ". $api_response;
		$access_to_write = "######## BEGIN NEW ACCESS RESPONE ########\n" . $access_occured . $apr_arg . "\n";
		fwrite($out,$access_to_write); 
		fclose($out); 
		// close API ERROR LOG FILE
	}
} // Close Class Closure

?>