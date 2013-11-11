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

// Theme overriding
$plugins->add_hook("global_start", "mybbmobile_forcetheme");
$plugins->add_hook("global_end", "mybbmobile_fixcurrentpage");

// Used to insert data into the posts/threads table for posts made via MyBBMobile
$plugins->add_hook("datahandler_post_insert_post", "mybbmobile_posts");
$plugins->add_hook("datahandler_post_insert_thread_post", "mybbmobile_threads");

// This function checks if the UA string matches the database
// If so, it displays the MyBBMobile theme
function mybbmobile_forcetheme()
{
	global $db, $mybb, $plugins, $cache, $lang, $current_page;

	// We're going to work around the per forum theme setting by altering the $current_page value throughout global.php
	// Then set it back to what it's supposed to be at global_end so it doesn't muck anything up (hopefully)
	$valid = array(
		"showthread.php",
		"forumdisplay.php",
		"newthread.php",
		"newreply.php",
		"ratethread.php",
		"editpost.php",
		"polls.php",
		"sendthread.php",
		"printthread.php",
		"moderation.php"
	);

	$lang->load("mybbmobile");

	if($mybb->session->is_spider == false)
	{
		// Force some changes to our footer, but only if we're not a bot
		$GLOBALS['gmb_orig_style'] = intval($mybb->user['style']);
		$GLOBALS['gmb_post_key'] = md5($mybb->post_code);

		$plugins->add_hook("global_end", "mybbmobile_forcefooter");
	}

	// Has the user chosen to disable MyBBMobile completely?
	if(isset($mybb->user['usemobileversion']) && $mybb->user['usemobileversion'] == 0 && $mybb->user['uid'] && $mybb->cookies['mybbmobile'] != "force" )
	{
		return false;
	}

	// Has the user temporarily disabled MyBBMobile via cookies?
	if($mybb->cookies['mybbmobile'] == "disabled")
	{
		return false;
	}

	// Is the admin using theme permission settings?
	// If so, check them
	if($mybb->settings['mybbmobile_permstoggle'] == 1)
	{
		// Fetch the theme permissions from the database
		$tquery = $db->simple_select("themes", "*", "tid = '{$mybb->settings['mybbmobile_theme_id']}'");
		$tperms = $db->fetch_field($tquery, "allowedgroups");

		if($tperms != "all")
		{
			$canuse = explode(",", $tperms);
		}

		// Also explode our user's additional groups
		if($mybb->user['additionalgroups'])
		{
			$userag = explode(",", $mybb->user['additionalgroups']);
		}

		// If the user doesn't have permission to use the theme...
		if($tperms != "all")
		{
			if(!in_array($mybb->user['usergroup'], $canuse) && !in_array($userag, $canuse))
			{
				return false;
			}
		}
	}

	// Grab the strings and put them into an array
	$list = $mybb->settings['mybbmobile_strings'];

	$replace = array("\n", "\r");
	$list = str_replace($replace, ",", $list);
	$list = str_replace(",,", ",", $list);

	$list = explode(",", $list);

	$switch = false;
	foreach($list as $uastring)
	{
		// Run as long as there hasn't been a match yet
		if(!$switch && $uastring)
		{
			// Switch to MyBBMobile if the UA matches our list
			if(stristr($_SERVER['HTTP_USER_AGENT'], $uastring))
			{
				$switch = true;
				$mybb->user['style'] = $mybb->settings['mybbmobile_theme_id'];
			}
		}
	}

	// Have we got this far without catching somewhere? Have we enabled mobile version?
	if($mybb->cookies['mybbmobile'] == "force" && $switch == false)
	{
		$mybb->user['style'] = $mybb->settings['mybbmobile_theme_id'];
	}

	if(in_array($current_page, $valid) && $mybb->user['style'] == $mybb->settings['mybbmobile_theme_id'])
	{
		$current_page = "mybbmobile_temp";
	}
}

// Add a link in the footer only if we're not a bot
function mybbmobile_forcefooter()
{
	global $lang, $footer, $mybb, $navbits;

	$footer = str_replace("<a href=\"<archive_url>\">".$lang->bottomlinks_litemode."</a>", "<a href=\"misc.php?action=switch_version&amp;my_post_key=".$GLOBALS['gmb_post_key']."\">".$lang->mybbmobile_mobile_version."</a>", $footer);

	// If we have a match, override the default breadcrumb
	if($mybb->user['style'] == $mybb->settings['mybbmobile_theme_id'])
	{
		$navbits = array();
		$navbits[0]['url'] = $mybb->settings['bburl'];
		$navbits[0]['name'] = $mybb->settings['mybbmobile_mobile_name'];
	}
}

// Undo the slight change we may have made earlier to get around per-forum themes
function mybbmobile_fixcurrentpage()
{
	global $current_page;

	$current_page = my_strtolower(basename(THIS_SCRIPT));
}

// Was this post sent from MyBBMobile?
function mybbmobile_posts($p)
{
	global $mybb;

	$is_mobile = intval($mybb->input['mobile']);

	if($is_mobile != 1) {
		$is_mobile = 0;
	}
	else {
		$is_mobile = 1;
	}

	// If so, we're going to store it for future use
	$p->post_insert_data['mobile'] = $is_mobile;
	return $p;
}

// Was this thread sent from MyBBMobile? (identical to above, only for threads)
// Might be redundant to some people, but meh
function mybbmobile_threads($p)
{
	global $mybb;

	$is_mobile = intval($mybb->input['mobile']);

	if($is_mobile != 1) {
		$is_mobile = 0;
	}
	else {
		$is_mobile = 1;
	}

	$p->post_insert_data['mobile'] = $is_mobile;
	return $p;
}