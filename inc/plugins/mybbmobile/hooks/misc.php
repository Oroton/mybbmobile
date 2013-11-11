<?php
/**
* MyBBMobile v0.1dev
* Licensed under GNU/GPL v3
* 
* Based off of MyBB GoMobile
*/

if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

// Misc. hooks
$plugins->add_hook("misc_start", "mybbmobile_switch_version");

// Switch to the mobile view via the footer link
function mybbmobile_switch_version()
{
	global $db, $lang, $mybb;

	if($mybb->input['action'] != "switch_version")
	{
		return false;
	}

	$url = "index.php";
	if(isset($_SERVER['HTTP_REFERER']))
	{
		$url = htmlentities($_SERVER['HTTP_REFERER']);
	}

	if(md5($mybb->post_code) != $mybb->input['my_post_key'])
	{
		redirect($url, $lang->invalid_post_code);
	}

	if($mybb->input['do'] == "full")
	{
		// Disable the mobile theme
		my_setcookie("mybbmobile", "disabled", -1);
	}
	elseif($mybb->input['do'] == "clear")
	{
		// Clear the mobile theme cookie
		my_setcookie("mybbmobile", "nothing", -1);
	}
	else
	{
		// Assume we're wanting to switch to the mobile version
		my_setcookie("mybbmobile", "force", -1);
	}

	$lang->load("mybbmobile");
	redirect($url, $lang->mybbmobile_switched_version);
}