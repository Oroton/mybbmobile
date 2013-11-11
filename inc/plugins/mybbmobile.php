<?php
/*
* MyBBMobile v0.1dev
* Licensed under GNU/GPL v3
* 
* Based off of MyBB GoMobile
*/

// Disallow direct access to this file for security reasons
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

// Page numbers
$plugins->add_hook("showthread_end", "mybbmobile_showthread");

// UCP options
$plugins->add_hook("usercp_options_end", "mybbmobile_usercp_options");
$plugins->add_hook("usercp_do_options_end", "mybbmobile_usercp_options");

// Misc. hooks
$plugins->add_hook("misc_start", "mybbmobile_switch_version");

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

// Uninstall MyBBMobile
// Not that anyone would want to do that, right? ;P
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
}

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

// Undo the slight change we may have made earlier to get around per-forum themes
function mybbmobile_fixcurrentpage()
{
	global $current_page;
	
	$current_page = my_strtolower(basename(THIS_SCRIPT));
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

// Page numbers and links, whoop
function mybbmobile_showthread()
{
	global $mybb, $lang, $postcount, $perpage, $thread, $pagejump, $pages, $page_location;
	
	// Display the total number of pages
	if($pages > 0) {
		$page_location = " {$lang->mybbmobile_of} {$pages}";
	}
	
	// If there's more than one page, display links to the first & last posts
	if($postcount > $perpage){
		$pj_template = "<div class=\"float_left\" style=\"padding-top: 12px;\">
			<a href=\"".get_thread_link($thread['tid'])."\" class=\"pagination_a\">{$lang->mybbmobile_jump_fpost}</a>
			<a href=\"".get_thread_link($thread['tid'], 0, 'lastpost')."\" class=\"pagination_a\">{$lang->mybbmobile_jump_lpost}</a>
			</div>";
		$pagejump = $pj_template;
	}
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

// Add MyBBMobile-related options to the UCP
function mybbmobile_usercp_options()
{
	global $db, $mybb, $templates, $user;

	if(isset($GLOBALS['gmb_orig_style']))
	{
		// Because we override this above, reset it to the original
		$mybb->user['style'] = $GLOBALS['gmb_orig_style'];
	}

	if($mybb->request_method == "post")
	{
		// We're saving our options here
		$update_array = array(
			"usemobileversion" => intval($mybb->input['usemobileversion'])
		);

		$db->update_query("users", $update_array, "uid = '".$user['uid']."'");
	}

	$usercp_option = '</tr><tr>
<td valign="top" width="1"><input type="checkbox" class="checkbox" name="usemobileversion" id="usemobileversion" value="1" {$GLOBALS[\'$usemobileversioncheck\']} /></td>
<td><span class="smalltext"><label for="usemobileversion">{$lang->mybbmobile_use_mobile_version}(<a href="misc.php?action=switch_version&amp;do=clear&amp;my_post_key={$GLOBALS[\'gmb_post_key\']}">{$lang->mybbmobile_clear_cookies}</a>)</label></span></td>';

	$find = '{$lang->show_codebuttons}</label></span></td>';
	$templates->cache['usercp_options'] = str_replace($find, $find.$usercp_option, $templates->cache['usercp_options']);

	// We're just viewing the page
	$GLOBALS['$usemobileversioncheck'] = '';
	if($user['usemobileversion'])
	{
		$GLOBALS['$usemobileversioncheck'] = "checked=\"checked\"";
	}
}

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

?>