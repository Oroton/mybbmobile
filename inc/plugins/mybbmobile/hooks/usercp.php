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

// UCP options
$plugins->add_hook("usercp_options_end", "mybbmobile_usercp_options");
$plugins->add_hook("usercp_do_options_end", "mybbmobile_usercp_options");

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