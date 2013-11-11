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

// Plugin information
function mybbmobile_info()
{
	global $lang;

	$lang->load("mybbmobile");

	// Plugin information
	return array(
		"name"			=> $lang->mybbmobile,
		"description"	=> $lang->mybbmobile_desc,
		"website"		=> "http://mybbmobile.com",
		"author"		=> "MyBBMobile",
		"authorsite"	=> "http://mybbmobile.com",
		"version"		=> "0.1dev",
		"compatibility" => "16*"
	);
}

// Installation functions
function mybbmobile_install()
{
	global $db, $mybb, $lang;

	$lang->load("mybbmobile");

	// Clean up the database before installing
	// MyBB tables cleanup
	if($db->field_exists("mobile", "posts"))
	{
		$db->query("ALTER TABLE ".TABLE_PREFIX."posts DROP COLUMN mobile");
	}

	if($db->field_exists("mobile", "threads"))
	{
		$db->query("ALTER TABLE ".TABLE_PREFIX."threads DROP COLUMN mobile");
	}

	if($db->field_exists("usemobileversion", "users"))
	{
		$db->query("ALTER TABLE ".TABLE_PREFIX."users DROP COLUMN usemobileversion");
	}

	// Settings cleanup
	$db->query("DELETE FROM ".TABLE_PREFIX."settinggroups WHERE name='mybbmobile'");
	$db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE name='mybbmobile_header_text'");
	$db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE name='mybbmobile_theme_id'");
	$db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE name='mybbmobile_permstoggle'");
	$db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE name='mybbmobile_homename'");
	$db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE name='mybbmobile_homelink'");
	$db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE name='mybbmobile_strings'");

	// Add a column to the posts & threads tables for tracking mobile posts
	$db->query("ALTER TABLE ".TABLE_PREFIX."posts ADD mobile int NOT NULL default '0'");
	$db->query("ALTER TABLE ".TABLE_PREFIX."threads ADD mobile int NOT NULL default '0'");

	// And another to the users table for options
	$db->query("ALTER TABLE ".TABLE_PREFIX."users ADD usemobileversion int NOT NULL default '1'");

	// First, check that our theme doesn't already exist
	$query = $db->simple_select("themes", "tid", "LOWER(name) LIKE '%mybbmobile%'");
	if($db->num_rows($query))
	{
		// Theme is already installed
		$theme = $db->fetch_field($query, "tid");
	}
	else
	{
		// Import the theme for our users
		$theme = MYBB_ROOT."inc/plugins/mybbmobile_theme.xml";
		if(!file_exists($theme))
		{
			flash_message("Upload the MyBBMobile Theme XML to the plugin directory (./inc/plugins/) before continuing.", "error");
			admin_redirect("index.php?module=config/plugins");
		}

		$contents = @file_get_contents($theme);
		if($contents)
		{
			$options = array(
				'no_stylesheets' => 0,
				'no_templates' => 0,
				'version_compat' => 1,
				'parent' => 1,
				'force_name_check' => true,
			);

			require_once MYBB_ADMIN_DIR."inc/functions_themes.php";
			$theme = import_theme_xml($contents, $options);
		}
	}

	// Default strings
	$strings = "iPhone
iPod
mobile
Android
Opera Mini
BlackBerry
IEMobile
Windows Phone
HTC
Nokia
Netfront
SmartPhone
Symbian
SonyEricsson
AvantGo
DoCoMo
Pre/
UP.Browser
Playstation Vita";

	// Edit existing templates (shows when posts are from MyBBMobile)
	require_once MYBB_ROOT."inc/adminfunctions_templates.php";

	find_replace_templatesets("postbit_posturl", '#'.preg_quote('<span').'#', '<img src="{$mybb->settings[\'bburl\']}/images/mobile/posted_{$post[\'mobile\']}.gif" alt="" width="{$post[\'mobile\']}8" height="{$post[\'mobile\']}8" title="{$lang->mybbmobile_posted_from}" style="vertical-align: middle;" /> '.'<span');

	// Prepare to insert the settings
	$setting_group = array
	(
		"gid" => 0,
		"name" => "mybbmobile",
		"title" => "MyBBMobile Settings",
		"description" => "Options, settings and strings used by MyBBMobile.",
		"disporder" => 1,
		"isdefault" => 0,
	);

	$gid = $db->insert_query("settinggroups", $setting_group);
	$dispnum = 0;

	$settings = array(
		"mybbmobile_mobile_name" => array(
			"title"			=> $lang->mybbmobile_settings_mobile_name_title,
			"description"	=> $lang->mybbmobile_settings_mobile_name,
			"optionscode"	=> "text",
			"value"			=> $db->escape_string($mybb->settings['bbname']),
			"disporder"		=> ++$dispnum
		),
		"mybbmobile_theme_id" => array(
			"title"			=> $lang->mybbmobile_settings_theme_id_title,
			"description"	=> $lang->mybbmobile_settings_theme_id,
			"optionscode"	=> "text",
			"value"			=> $theme,
			"disporder"		=> ++$dispnum
		),
		"mybbmobile_permstoggle" => array(
			"title"			=> $lang->mybbmobile_settings_permstoggle_title,
			"description"	=> $lang->mybbmobile_settings_permstoggle,
			"optionscode"	=> "yesno",
			"value"			=> 0,
			"disporder"		=> ++$dispnum
		),
		"mybbmobile_homename" => array(
			"title"			=> $lang->mybbmobile_settings_homename_title,
			"description"	=> $lang->mybbmobile_settings_homename,
			"optionscode"	=> "text",
			"value"			=> $db->escape_string($mybb->settings['homename']),
			"disporder"		=> ++$dispnum
		),
		"mybbmobile_homelink" => array(
			"title"			=> $lang->mybbmobile_settings_homelink_title,
			"description"	=> $lang->mybbmobile_settings_homelink,
			"optionscode"	=> "text",
			"value"			=> $db->escape_string($mybb->settings['homeurl']),
			"disporder"		=> ++$dispnum
		),
		"mybbmobile_strings" => array(
			"title"			=> $lang->mybbmobile_settings_strings_title,
			"description"	=> $lang->mybbmobile_settings_strings,
			"optionscode"	=> "textarea",
			"value"			=> $db->escape_string($strings),
			"disporder"		=> ++$dispnum
		)
	);

	// Insert the settings listed above
	foreach($settings as $name => $setting)
	{
		$setting['gid'] = $gid;
		$setting['name'] = $name;

		$db->insert_query("settings", $setting);
	}
	rebuild_settings();
}

// Uninstall MyBBMobile
function mybbmobile_uninstall()
{
	global $db, $cache;

	// Smarter uninstall, same as install function's cleanup
	// MyBB tables cleanup
	if($db->field_exists("mobile", "posts"))
	{
		$db->query("ALTER TABLE ".TABLE_PREFIX."posts DROP COLUMN mobile");
	}

	if($db->field_exists("mobile", "threads"))
	{
		$db->query("ALTER TABLE ".TABLE_PREFIX."threads DROP COLUMN mobile");
	}

	if($db->field_exists("usemobileversion", "users"))
	{
		$db->query("ALTER TABLE ".TABLE_PREFIX."users DROP COLUMN usemobileversion");
	}

	// Settings cleanup
	$db->query("DELETE FROM ".TABLE_PREFIX."settinggroups WHERE name='mybbmobile'");
	$db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE name='mybbmobile_header_text'");
	$db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE name='mybbmobile_theme_id'");
	$db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE name='mybbmobile_permstoggle'");
	$db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE name='mybbmobile_homename'");
	$db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE name='mybbmobile_homelink'");
	$db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE name='mybbmobile_strings'");

	// Can the template edits we made earlier
	require_once MYBB_ROOT."inc/adminfunctions_templates.php";

	// Undo the template edits
	find_replace_templatesets("postbit_posturl", '#'.preg_quote('<img src="{$mybb->settings[\'bburl\']}/images/mobile/posted_{$post[\'mobile\']}.gif" alt="" width="{$post[\'mobile\']}8" height="{$post[\'mobile\']}8" title="{$lang->mybbmobile_posted_from}" style="vertical-align: middle;" /> '.'').'#', '', 0);
	rebuild_settings();
}

// Checks to see if the plugin is installed already
function mybbmobile_is_installed()
{
	global $db;

	// Is the cache [the last installation step performed] ready for use?
	$installed = $db->simple_select("settings", "*", "name='mybbmobile_strings'");

	if($db->num_rows($installed))
	{
		return true;
	}
	return false;
}