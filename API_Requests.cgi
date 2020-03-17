#!/usr/bin/perl

#############################################################################################
#
# Authors:
# Alexander Nietzen Morales 
#############################################################################################

use LWP::Simple qw(!head);
use CGI qw(:standard);
use lib($ENV{'DOCUMENT_ROOT'});
use config::VailConfig;
use strict;

#print "Content-type: text/html\n\n";
#print "$config::VailConfig::admin_id";

my $page = param('page');
if($page eq 'add_conference'){ add_conference(); }
elsif($page eq 'edit_conference'){ edit_conference(); }
elsif($page eq 'delete_conference'){ delete_conference(); }
elsif($page eq 'view_active_conference'){ view_active_conference(); }

############################################
sub add_conference {
	# add user vars!
	my $unique_id = param('unique_id');
	my $pin = param('pin'); # host pin
	my $dnis = param('dnis');
	my $timeZone = param('timeZone');
	my $maxParties= param('maxParties') || '08';
	my $onHoldMusic = param('onHoldMusic');
	my $chimeInMusic = param('chimeInMusic');
	my $accountCodeRq = param('accountCodeRq');
	my $joinWin = param('joinWin');
	# convert param values (also validate)
	if($joinWin > 70) { $joinWin = '70'; }
	$dnis =~ s/\-//g;
	if(length($maxParties) eq 1) { $maxParties = '0' . $maxParties; }
	if($onHoldMusic eq 'Default 1') { $onHoldMusic = 1; }
	elsif($onHoldMusic eq 'Default 2') { $onHoldMusic = 2; }
	elsif($onHoldMusic eq 'Default 3') { $onHoldMusic = 3; }
	elsif($onHoldMusic eq 'None') { $onHoldMusic = 0; }
	if($chimeInMusic eq 'Default 1') { $chimeInMusic = 1; }
	elsif($chimeInMusic eq 'Default 2') { $chimeInMusic = 2; }
	elsif($chimeInMusic eq 'Default 3') { $chimeInMusic = 3; }
	elsif($chimeInMusic eq 'None') { $chimeInMusic = 0; }
	if($timeZone eq 'eastern') { $timeZone = '-5'; }
	elsif($timeZone eq 'central') { $timeZone = '-6'; }
	elsif($timeZone eq 'mountain') { $timeZone = '-7'; }
	elsif($timeZone eq 'pacific') { $timeZone = '-8'; }
	if($accountCodeRq eq 'no') { $accountCodeRq = '0'; }
	else { $accountCodeRq = '1'; }

	# posting url to API here for adding a user
	my $url_to_add_usr = $api_url . 'addUser&ldap_salutation=Mr.&ldap_givenName=' . $unique_id . '&ldap_sn=' . $unique_id . '&ldap_mail=' . $unique_id . '@voxconferencing.com';
	$url_to_add_usr .= '&admin=5&dnis=' . $dnis . '&timeZone=' . $timeZone . '&maxParties=' . $maxParties . '&onHoldMusic=' . $onHoldMusic . '&chimeInMusic=' . $chimeInMusic . '&accountCodeRq=' . $accountCodeRq . '&joinWin=' . $joinWin;
	if($pin ne '') { $url_to_add_usr .= '&pin=' . $pin; } # host pin!

	warn "URL TO ADD USER:" . $url_to_add_usr if($enable_warnings eq 'on');
	my $v_add_user = get($url_to_add_usr); # request to api to add user.
	my @v_account_number = split(/=/,$v_add_user);
	my $v_account_number = $v_account_number[1]; # the account number!
	$v_account_number =~ s/\n\n//g; # remove newlines.

	# add conference _vars
	my $type = param('type');
	my $hosted = param('hosted');
	my $entire = param('entire');
	my $securitycode =param('securitycode');
	my $duration = param('duration');
	# add conference vars (for hosted conference only!)
	my $date_month = param('date_month');
	my $date_day = param('date_day');
	my $date_year = param('date_year');
	my $date_hour = param('date_hour');
	my $date_minute = param('date_minute');
	my $date_ampm = param('date_ampm');
	my $bridge_number = param('bridge_number');
	my $entire = param('entire');
	my $record = param('record');
	# convert param vales;
	if($hosted eq 'no_host') { $hosted = 0; }
	else { $hosted = 1; }
	if($entire eq 'yes') { $entire = 1; }
	else { $entire = 0; }
	if($type eq 'scheduled') { $type = 1; }
	else { $type = 2; }
	if($record eq 'yes') { $record = '1'; }
	else { $record = '0'; }

	# posting url to API here for adding a conference
	my $url_to_add_conf = $api_url . 'addConference&account_number=' . $v_account_number . "&type=" . $type;
	$url_to_add_conf .= "&duration=" . $duration . "&record=" . $record . "&hosted=" . $hosted;
	if($hosted eq 1){
		$url_to_add_conf .= "&entire=" . $entire;
	}
	if($securitycode ne '') {
		$url_to_add_conf .= "&securitycode=" . $securitycode;
	}
	if($type eq 1){
		$url_to_add_conf .= "&date_month=" . $date_month . "&date_day=" . $date_day . "&date_year=" . $date_year;
		$url_to_add_conf .= "&date_hour=" . $date_hour ."&date_minute=" . $date_minute ."&date_ampm=" . uc($date_ampm);
	}
	else {
		$url_to_add_conf .= '&conference_bridge_number=' . $bridge_number;
	}

	warn "URL TO ADD CONF:" . $url_to_add_conf if($enable_warnings eq 'on');
	
	my $v_add_conf = get($url_to_add_conf); # request to api to add conf.
	my @v_conf_number = split(/=/,$v_add_conf);
	my $v_conf_number = $v_conf_number[1]; # the account number!
	$v_conf_number =~ s/\n\n//g; # remove newlines.
	$v_conf_number =~ s/ //g; # remove blank chars

	# this is to pass me back the error codes
	if($v_account_number == '') { $v_account_number = $v_account_number[0]; }
	if($v_conf_number == '') {
		$v_conf_number = $v_conf_number[0];
		$v_conf_number =~s/Error \- //g;
	}

	my $cid = param('cid');
	my $jump_to_url = 'http://' . $ENV{'HTTP_HOST'} . '/?page=user&funct=view_conf';
	$jump_to_url .= '&cid=' . $cid . '&type=' . $type;
	$jump_to_url .= '&v_account_number=' . $v_account_number . '&v_conf_number=' . $v_conf_number;

	#print $url_to_add_usr . "\n\n<br><Br>\n\n";
	#print "$url_to_add_conf" . "\n\n<br><Br>\n\n";
	#print "$jump_to_url";

	print "Location: $jump_to_url" . "\n\n";
}
############################################
sub edit_conference {
	#print "Content-type: text/html\n\n";
	my $v_account_number = param('v_account_number');
	my $actnum = param('v_account_number');
	my $pin = param('pin'); # host pin
	my $dnis = param('dnis');
	my $timeZone = param('timeZone');
	my $maxParties= param('maxParties') || '08';
	my $onHoldMusic = param('onHoldMusic');
	my $chimeInMusic = param('chimeInMusic');
	my $accountCodeRq = param('accountCodeRq');
	my $joinWin = param('joinWin');
	my $presentation_mode = param('presentation_mode'); # added june 14 2006
	# convert param values (also validate)
	if($joinWin > 70) { $joinWin = '70'; }
	$dnis =~ s/\-//g;
	if(length($maxParties) eq 1) { $maxParties = '0' . $maxParties; }
	#if($onHoldMusic eq 'Default 1') { $onHoldMusic = 1; }
	#elsif($onHoldMusic eq 'Default 2') { $onHoldMusic = 2; }
	#elsif($onHoldMusic eq 'Default 3') { $onHoldMusic = 3; }
	#elsif($onHoldMusic eq 'None') { $onHoldMusic = 0; }
	#if($chimeInMusic eq 'Default 1') { $chimeInMusic = 1; }
	#elsif($chimeInMusic eq 'Default 2') { $chimeInMusic = 2; }
	#elsif($chimeInMusic eq 'Default 3') { $chimeInMusic = 3; }
	#elsif($chimeInMusic eq 'None') { $chimeInMusic = 0; }
	if($timeZone eq 'eastern') { $timeZone = '-5'; }
	elsif($timeZone eq 'central') { $timeZone = '-6'; }
	elsif($timeZone eq 'mountain') { $timeZone = '-7'; }
	elsif($timeZone eq 'pacific') { $timeZone = '-8'; }
	if($accountCodeRq eq 'no') { $accountCodeRq = '0'; }
	else { $accountCodeRq = '1'; }

	# posting url to API here for updating user info
	my $url_to_add_usr = $api_url . 'editUser&account_number=' . $v_account_number;
	$url_to_add_usr .= '&dnis=' . $dnis . '&timeZone=' . $timeZone . '&maxParties=150' . '&onHoldMusic=' . $onHoldMusic . '&chimeInMusic=' . $chimeInMusic . '&accountCodeRq=' . $accountCodeRq . '&joinWin=' . $joinWin;
	if($pin ne '') { # host pin!
		$url_to_add_usr .= '&pin=' . $pin;
	}
	else {
		$url_to_add_usr .= '&pin=3801'; # vail does not generate a pin on edituser if it is not pass - this is a fix
	}

	warn "URL TO EDIT USER:" . $url_to_add_usr if($enable_warnings eq 'on');

	# This will re-asign the value to v_account_number... oh well.
	my $v_add_user = get($url_to_add_usr); # request to api to add user.
	my @v_account_number = split(/=/,$v_add_user);
	my $v_account_number = $v_account_number[1]; # the account number!
	$v_account_number =~ s/\n\n//g; # remove newlines.

	# edit conference _vars
	my $type = param('type');
	my $hosted = param('hosted');
	my $entire = param('entire');
	my $securitycode =param('securitycode');
	my $duration = param('duration');
	# edit conference vars (for hosted conference only!)
	my $date_month = param('date_month');
	my $date_day = param('date_day');
	my $date_year = param('date_year');
	my $date_hour = param('date_hour');
	my $date_minute = param('date_minute');
	my $date_ampm = param('date_ampm');
	my $bridge_number = param('bridge_number');
	my $entire = param('entire');
	my $record = param('record');
	# convert param vales;
	if($hosted eq 'no_host') { $hosted = 0; }
	else { $hosted = 1; }
	if($entire eq 'yes') { $entire = 1; }
	else { $entire = 0; }
	if($type eq 'scheduled') { $type = 1; }
	else { $type = 2; }
	if($record eq 'yes') { $record = '1'; }
	else { $record = '0'; }

	# posting url to API here for edit a conference
	my $url_to_add_conf = $api_url . 'editConference&conference_bridge_number=' . $bridge_number . "&type=" . $type	. '&accountCodeRq=' . $accountCodeRq;
	$url_to_add_conf .= "&duration=" . $duration . "&record=" . $record . "&hosted=" . $hosted;
	if($hosted eq 1){
		$url_to_add_conf .= "&entire=" . $entire;
	}
	if($securitycode ne '') {
		$url_to_add_conf .= "&securitycode=" . $securitycode;
	}
	if($type eq 1){
		$url_to_add_conf .= "&date_month=" . $date_month . "&date_day=" . $date_day . "&date_year=" . $date_year;
		$url_to_add_conf .= "&date_hour=" . $date_hour ."&date_minute=" . $date_minute ."&date_ampm=" . uc($date_ampm);
		$url_to_add_conf .= "&maxParties=150&daylightsavings=1";
	}
	else {
		$url_to_add_conf .= "&maxParties=150";
	}
	$url_to_add_conf .= "&account_number=" . $actnum;
	
	if($presentation_mode eq 'yes'){  # added june 14 2006
		$url_to_add_conf .= '&startMuted=1&queue=1&lock_mute=1';
	}
	else {
		$url_to_add_conf .= '&startMuted=0&queue=0&lock_mute=0';
	}
	
	my $v_add_conf = get($url_to_add_conf); # request to api to add conf.
	my @v_conf_number = split(/=/,$v_add_conf);
	my $v_conf_number = $v_conf_number[1]; # the account number!
	$v_conf_number =~ s/\n\n//g; # remove newlines.
	$v_conf_number =~ s/ //g; # remove blank chars

	warn "URL TO EDIT CONF:" . $url_to_add_conf if($enable_warnings eq 'on');

	# this is to pass me back the error codes
	if($v_account_number == '') { $v_account_number = $v_account_number[0]; }
	if($v_conf_number == '') {
		$v_conf_number = $v_conf_number[0];
		$v_conf_number =~s/Error \- //g;
	}

	my $cid = param('cid');
	my $jump_to_url = 'http://' . $ENV{'HTTP_HOST'} . '/?page=user&funct=view_conf&act_update=' . $v_account_number . '&conf_update=' . $v_conf_number;

	#print "$jump_to_url";

	#my $jump_to_url = 'http://' . $ENV{'HTTP_HOST'} . '/?page=user&funct=view_conf&conf_update=' . $v_conf_number;
	#$jump_to_url .= '&cid=' . $cid . '&type=' . $type;
	#$jump_to_url .= '&v_account_number=' . $v_account_number . '&v_conf_number=' . $v_conf_number;

	#print $url_to_add_usr . "\n\n<br><Br>\n\n";
	#print "$url_to_add_conf" . "\n\n<br><Br>\n\n";
	#print "$jump_to_url";

	print "Location: $jump_to_url" . "\n\n";

}

############################################
sub delete_conference {
	my $v_account_number = param('v_account_number');
	my $v_conf_number = param('v_conf_number');
	my $cid = param('conference_bridges_id');

	my $url_to_del_conf = $api_url . 'deleteConference&conference_bridge_number=' . $v_conf_number . "&account_number=" . $v_account_number;

	warn "URL TO DELETE CONF:" . $url_to_del_conf if($enable_warnings eq 'on');

	my $v_del_conf = get($url_to_del_conf); # request to api to add conf.
	my @v = split(/=/,$v_del_conf);
	my $v = $v[1];
	$v =~ s/\n\n//g; # remove newlines
	$v =~ s/ //g; # remove blank chars

	if($v == '') {
		$v = $v[0];
		$v =~s/Error \- //g;
	}
	
	## delete bridge from accnt.
	my $url_to_delete_bridge_num_from_acct = $api_url . 'removeBridge&account_number=' . $v_account_number . '&conference_bridge_number=' . $v_conf_number;
	my $del_bridge = get($url_to_delete_bridge_num_from_acct);
	warn "URL TO DELETE BRIDGE FROM ACCNT:" . $del_bridge if($enable_warnings eq 'on');
	
	my $jump_to_url = 'http://' . $ENV{'HTTP_HOST'} . '/?page=user&funct=view_conf&cid=' . $cid . '&v=' . $v . '&acct_num=' . $v_account_number . '&c_num=' . $v_conf_number;

	print "Location: $jump_to_url" . "\n\n";

}
############################################
sub view_active_conference {
	my $v_account_number = param('v_account_number');
	my $v_conf_number = param('v_conf_number');

	my $url_to_view_active_conf = $api_url . 'activeList&conference_bridge_number=' . $v_conf_number . '&account_number=' . $v_account_number;
	my $data = get($url_to_view_active_conf);

	## URL ENCODE DATA
	require URI::URL;
	$data = URI::URL->new($data);
	warn $data if($enable_warnings eq 'on');

	my $jump_to_url = 'http://' . $ENV{'HTTP_HOST'} . '/?page=user&funct=active_conf&method=show&v_conf_number=' . $v_conf_number . '&v_account_number=' . $v_account_number . '&data=' .$data;
	print "Location: $jump_to_url" . "\n\n";
}
1;
