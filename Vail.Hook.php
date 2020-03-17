<?php
/*
Authors:
Alexander Nietzen Morales
*/

Class myAddConference
{ // class closure
	var $add_user_params;
	var $add_conf_params;
	var $add_user_response;
	var $add_conf_response;
	var $vail_account_code; // this will be vail account number.  ## This will be usd to determine end result 
	var $vail_bridge_code; // this will be the vail bridge number. ## This will be usd to determine end result
	var $vail_friendly_response; // this will be a succedd or error code.  ## This will be usd to determine end result
	// aduser vars
	protected $ldap_salutation;
	protected $ldap_givenName;
	protected $ldap_sn;
	protected $ldap_mail;
	protected $status = 0;
	protected $dnis;
	protected $timeZone;
	protected $maxParties = 150;
	protected $joinWin;
	protected $onHoldMusic;
	protected $chimeInMusic;
	protected $accountCodeRq;
	protected $pin;
	// add conf vars
	protected $type;
	protected $account_number;
	protected $hosted;
	protected $entire;
	protected $securitycode;
	protected $duration;
	protected $record;
	protected $startMuted;
	protected $queue;
	protected $lock_mute;
	protected $conference_bridge_number;
	protected $date_month; // scheduled only
	protected $date_day; // scheduled only
	protected $date_year; // scheduled only
	protected $date_hour; // scheduled only
	protected $date_minute; // scheduled only
	protected $date_ampm; // scheduled only
	protected $daylightsavings; // scheduled only
	#######################################################################################
	function __construct($add_user_params, $add_conf_params)
	{
		include_once('./classes/API_HOOKS/Vail/addUser.php');
		$unique_id = $add_user_params[0] . date("ymd") . date("his"); // $add_user_params[0] is the last insert id from db
		$this->ldap_salutation = 'Mr.';
		$this->ldap_givenName = $unique_id;
		$this->ldap_sn = $unique_id;
		$this->ldap_mail = $unique_id . '@voxconferencing.com';
		$this->dnis = $add_user_params[1];
		switch($add_user_params[2])
		{
			case 'eastern':		$this->timeZone = '-5'; break;
			case 'central':		$this->timeZone = '-6'; break;
			case 'mountain':	$this->timeZone = '-7'; break;
			case 'pacific':		$this->timeZone = '-8'; break;
			default:			'-8'; break;
		}
		$this->daylightsavings = $add_user_params[3];
		$this->maxParties = $add_user_params[4];
		#$this->joinWin = ($add_user_params[5] > 70) ? '70': $add_user_params[5];
		$this->joinWin = $add_user_params[5];
		$this->onHoldMusic = $add_user_params[6];
		$this->chimeInMusic = $add_user_params[7];
		$this->accountCodeRq = ($add_user_params[8] == 'no') ? 0 : 1;
		if($add_conf_params[1] == 'no_host') { $this->accountCodeRq = 0; }
		$this->pin = $add_user_params[9];
		$this->hosted = ($add_conf_params[1] == 'no_host') ? 0 : 1; # prediefine this! it is used on add user as well!
		$add_user_args = array($this->ldap_salutation,$this->ldap_givenName,$this->ldap_sn,$this->ldap_mail,$this->status,$this->dnis,$this->timeZone,$this->daylightsavings,$this->maxParties,$this->joinWin,$this->onHoldMusic,$this->chimeInMusic,$this->accountCodeRq,$this->pin,$this->hosted);
		$this->add_user_response = new addUserResponse($add_user_args);
		$this->vail_friendly_response = $this->add_user_response->api_friendly_error_message; ## This will be usd to determine end result
		$this->add_user_response = (eregi("^Error -", $this->add_user_response->user_account_number)) ? 0 : $this->add_user_response->user_account_number;
		if($this->add_user_response)
		{ // user added into vail, account number is $this->add_user_response ... proceed
			$this->vail_account_code = $this->add_user_response; ## This will be usd to determine end result
			$this->type = ($add_conf_params[0] == 'standing') ? 2 : 1; // 1 = sceduled
			$this->account_number = $this->add_user_response;
			#$this->hosted = ($add_conf_params[1] == 'no_host') ? 0 : 1;
			$this->entire = ($add_conf_params[2] == 'yes') ? 1 : 0; // Do you want the conference to end when you leave? Default value: 1(Yes); valid settings: 0 (No), 1(Yes)
			$this->securitycode = ($add_conf_params[3]) ? $add_conf_params[3] : null;
			$this->duration = ($add_conf_params[4] > 4) ? 4 : $add_conf_params[4];
			switch($add_conf_params[5])
			{
				case 'no':		$this->record = 0; break;
				case 'yes':		$this->record = 1; break;
				case 'hc':		$this->record = 5; break;
			}
			# presentation mode
			switch($add_conf_params[6])
			{
				case 'yes':			$this->startMuted = 1; $this->queue = 1; $this->lock_mute = 1; break;
				case 'mut':			$this->startMuted = 1; $this->queue = 0; $this->lock_mute = 1; break;
				default:			$this->startMuted = 0; $this->queue = 0; $this->lock_mute = 0; break;
			}
			$this->conference_bridge_number = $add_conf_params[7];
			$this->date_month = $add_conf_params[8];
			$this->date_day = $add_conf_params[9];
			$this->date_year = $add_conf_params[10];
			$this->date_hour = $add_conf_params[11];
			$this->date_minute = $add_conf_params[12];
			$this->date_ampm = $add_conf_params[13];
			require_once('./classes/API_HOOKS/Vail/addConference.php');
			$standing_array = array($this->type, $this->account_number, $this->hosted, $this->entire, $this->accountCodeRq, $this->securitycode, $this->duration, $this->maxParties, $this->record, $this->startMuted, $this->queue, $this->lock_mute, $this->conference_bridge_number);
			$sched_array = array($this->date_month, $this->date_day, $this->date_year, $this->date_hour, $this->date_minute, $this->date_ampm, $this->timeZone, $this->daylightsavings);
			$sched_array = array_merge ($standing_array, $sched_array);
			
			switch($add_conf_params[0])
			{
				case 'scheduled':		$this->add_conf_response = new addScheduledConferenceResponse($sched_array); break;
				case 'standing':		$this->add_conf_response = new addStandingConferenceResponse($standing_array); break;
			}
			$this->vail_friendly_response = $this->add_conf_response->api_friendly_error_message; ## This will be usd to determine end result
			$this->vail_bridge_code = $this->add_conf_response->conference_number; ## This will be usd to determine end result
			if(!($this->add_conf_response->conference_number))
			{
				$del_accnt = new myInactivateUserAccount($this->account_number);
			}
			else
			{
				// if we made it here... we created account and bridge.
				$this->vail_friendly_response = 'success';
			}
		}
	}
	#######################################################################################
	private function InactivateUserAccount($account_number)
	{
		// Let's be a nice guy and keep the Vail Records clean!
		$vail_apai_params = '&requestType=editUser&account_number=' . $account_number . '&status=2'; // this will inactivate account;
		include_once('./classes/API_HOOKS/Vail/API.php');
		$vail_post_request = new ApiRequest($vail_apai_params);
	}
} // class closure EOC

Class myEditConference
{ // class closure
	var $edit_user_params;
	var $edit_conf_params;
	var $edit_user_response;
	var $edit_conf_response;
	var $vail_friendly_response; // this will be a succedd or error code.  ## This will be usd to determine end result
	var $update_account;  // this will be vail account number.  ## This will be usd to determine end result
	var $update_conf;  // this will be vail account number.  ## This will be usd to determine end result
	// edit user vars
	protected $account_number; // (manditory)
	protected $maxParties = '150';
	protected $joinWin; // Maximum Time Limit to Join Conference (minutes) - default value:70
	protected $onHoldMusic; // On Hold Music - default value is 1; valid settings are: 1 (Default 1), 2 (Default 2), 0 (None)
	protected $chimeInMusic; // Entry/Exit Chimes - default value is 1; valid settings are: 1 (Default 1), 2 (Default 2), 3 (Default 3), 0 (None)
	protected $accountCodeRq; // Prompt for an Account Code on Hosted Conferences - default value is 0; valid settings are: 0 (No), 1 (Yes)
	protected $pin;
	// edit conf vars
	protected $hosted; // Would you like to host this conference? Default value: 1(Yes); valid settings: 0 (No), 1(Yes) 		(optional)
	protected $entire; // Do you want the conference to end when you leave? Default value: 1(Yes); valid settings: 0 (No), 1(Yes) 		(optional)
	protected $securitycode; //	Security Code - optional - Must be four digits only 		(optional)
	protected $duration; // Duration in Hours - default setting: 1; valid settings: numeric value 1 though 4 		(optional)
	protected $record; // Would you like to record the conference? Default setting 0 [NEVER] 1 [WHEN 2ND PARTY JOINS] 3 [WHEN 2nd PARTY JOINS HOST CONTROLLED] 5 [START STOP ANYTIME HOST CONTROLLED] 	(optional)
	protected $startMuted; // Incoming calls will be - default setting: 0 (Audible); valid settings: 0 (Audible), 1(Muted) 		(optional)
	protected $queue; // Activate the ability to join a queue within the conference: default setting: 0 (JoinQueue inactive); valid settings: 0 (JoinQueue inactive) - 1 (JoinQueue active) 		(optional)
	protected $lock_mute; // Activate lock dtmf mute: default setting: 0 (Lock DTMF Mute inactive); valid settings: 0 (Lock DTMF Mute inactive) - 1 (Lock DTMF Mute active) 		(optional)
	protected $conference_bridge_number; // Manditory on Edit Conference!
	#######################################################################################
	function __construct($edit_user_params, $edit_conf_params)
	{
		include_once('./classes/API_HOOKS/Vail/editUser.php');
		$this->account_number = $edit_user_params[0];
		$this->maxParties = $edit_user_params[1];
		$this->joinWin = $edit_user_params[2];
		$this->onHoldMusic = $edit_user_params[3];
		$this->chimeInMusic = $edit_user_params[4];
		$this->accountCodeRq = ($edit_user_params[5] == 'no') ? 0 : 1;
		$this->pin = $edit_user_params[6];
		$this->hosted = ($edit_conf_params[0] == 'no_host') ? 0 : 1; # prediefine this! it is used on edit user as well!
		$edit_user_args = array($this->account_number,$this->maxParties,$this->joinWin,$this->onHoldMusic,$this->chimeInMusic,$this->accountCodeRq,$this->pin,$this->hosted);
		$this->edit_user_response = new editUserResponse($edit_user_args);
		$this->vail_friendly_response = $this->edit_user_response->api_friendly_error_message; ## This will be usd to determine end result
		$this->update_account = $this->edit_user_response->updated;
		if($this->update_account)
		{ // user updated in vail database - proceed
		
			$this->entire = ($edit_conf_params[1] == 'yes') ? 1 : 0; // Do you want the conference to end when you leave? Default value: 1(Yes); valid settings: 0 (No), 1(Yes)
			$this->securitycode = ($edit_conf_params[2]) ? $edit_conf_params[2] : null;
			$this->duration = ($edit_conf_params[3] > 4) ? 4 : $edit_conf_params[3];
			switch($edit_conf_params[4])
			{
				case 'no':		$this->record = 0; break;
				case 'yes':		$this->record = 1; break;
				case 'hc':		$this->record = 5; break;
			}
			switch($edit_conf_params[5])
			{
				case 'yes':			$this->startMuted = 1; $this->queue = 1; $this->lock_mute = 1; break;
				case 'mut':			$this->startMuted = 1; $this->queue = 0; $this->lock_mute = 1; break;
				default:			$this->startMuted = 0; $this->queue = 0; $this->lock_mute = 0; break;
			}
			$this->conference_bridge_number = $edit_conf_params[6];
			require_once('./classes/API_HOOKS/Vail/editConference.php');
			$array_in = array($this->account_number,$this->hosted,$this->entire,$this->accountCodeRq,$this->securitycode,$this->duration,$this->maxParties,$this->record,$this->startMuted,$this->queue,$this->lock_mute,$this->conference_bridge_number);
			$this->edit_conf_response = new editConferenceResponse($array_in);
			$this->vail_friendly_response = $this->edit_conf_response->api_friendly_error_message;
			$this->update_conf = $this->edit_conf_response->updated;
			if($this->update_conf) { $this->vail_friendly_response = 'success';	}
		}
	}
} // class closure EOC

Class myInactivateUserAccount
{ // class closure
	protected $post_request;
	var $updated;
	#######################################################################################
	function __construct($account_number)
	{
		// Let's be a nice guy and keep the Vail Records clean!
		$vail_api_params = '&requestType=editUser&account_number=' . $account_number . '&status=2'; // this will inactivate account;
		include_once('./classes/API_HOOKS/Vail/API.php');
		$this->post_request = new ApiRequest($vail_api_params);
		$this->updated = eregi("^success", $this->post_request->api_content) ? 1 : null;
	}
} // class closure EOC

Class myActiveConference
{ // class closure
	protected $conference_bridge_number; // The number for the Active Conference
	protected $account_number; // Vail Account Number for the Active Conference
	protected $party_ani; // Ani for Party to be un-muted in an Active Conference
	protected $party_id; // The party_id to be un-muted in an Active Conference - retrieved from activeList
	var $active_reponse;
	var $vail_friendly_response;
	var $post_request;
	var $proceed;
	#######################################################################################
	function __construct($active_conf_params, $type)
	{
		$this->conference_bridge_number = $active_conf_params[0];
		$this->account_number = $active_conf_params[1];
		$this->party_ani = (isset($active_conf_params[2])) ? $active_conf_params[2] : null;
		$this->party_id = (isset($active_conf_params[3])) ? $active_conf_params[3] : null;
		$array_in = array($this->conference_bridge_number,$this->account_number,$this->party_ani,$this->party_id);
		include_once('./classes/API_HOOKS/Vail/API.php');
		switch($type)
		{
			case 'dial':		include_once('./classes/API_HOOKS/Vail/activeDial.php');		$this->post_request = new activeDialResponse($array_in);		break;
			case 'drop':		include_once('./classes/API_HOOKS/Vail/activeDrop.php');		$this->post_request = new activeDropResponse($array_in);		break;
			case 'list':		include_once('./classes/API_HOOKS/Vail/activeList.php');		$this->post_request = new activeListResponse($array_in);		break;
			case 'mute':		include_once('./classes/API_HOOKS/Vail/activeMute.php');		$this->post_request = new activeMuteResponse($array_in);		break;
			case 'unmute':		include_once('./classes/API_HOOKS/Vail/activeUnmute.php');		$this->post_request = new activeUnmuteResponse($array_in);		break;
			case 'muteall':		include_once('./classes/API_HOOKS/Vail/activeMuteAll.php');		$this->post_request = new activeMuteAllResponse($array_in);		break;
			case 'unmuteall':	include_once('./classes/API_HOOKS/Vail/activeUnmuteAll.php');	$this->post_request = new activeUnmuteAllResponse($array_in);	break;
			case 'unqueue':		include_once('./classes/API_HOOKS/Vail/activeUnqueue.php');		$this->post_request = new activeUnqueueResponse($array_in);		break;
			case 'unqueueall':	include_once('./classes/API_HOOKS/Vail/activeUnqueueAll.php');	$this->post_request = new activeUnqueueAllResponse($array_in);	break;
		}
		$this->vail_friendly_response = $this->post_request->api_friendly_error_message;
		$this->proceed = $this->post_request->proceed;
	}
} // class closure EOC

Class myDeleteConfRemoveBridge
{ // class closure
	// This will delete the bridge and release the conference number along with setting the vail acocunt to inactive!
	protected $conference_bridge_number; // Conference Bridge Number
	protected $account_number; // Vail Account Number
	var $vail_friendly_response;
	var $post_request;
	var $proceed;
	var $delete_conf_response;
	var $delete_conf_result;
	var $remove_bridge_response;
	var $remove_bridge_result;
	#######################################################################################
	function __construct($conf_params)
	{
		$this->conference_bridge_number = $conf_params[0];
		$this->account_number = $conf_params[1];
		$array_in = array($this->conference_bridge_number,$this->account_number);
		include_once('./classes/API_HOOKS/Vail/deleteConference.php');
		$this->delete_conf_response = new deleteConferenceResponse($array_in);
		$this->vail_friendly_response = $this->delete_conf_response->api_friendly_error_message;
		$this->delete_conf_result = (eregi("^Error -", $this->delete_conf_response->api_content)) ? null : $this->delete_conf_response->proceed;
		if($this->delete_conf_result)
		{
			include_once('./classes/API_HOOKS/Vail/removeBridge.php');
			$this->remove_bridge_response = new removeBridgeResponse($array_in);
			$this->vail_friendly_response = $this->remove_bridge_response->api_friendly_error_message;
			$this->remove_bridge_result = (eregi("^Error -", $this->remove_bridge_response->api_content)) ? null : $this->remove_bridge_response->proceed;
			if($this->remove_bridge_result)
			{
				$del_accnt = new myInactivateUserAccount($this->account_number);
			}
		}
	}
} // class closure EOC
?>