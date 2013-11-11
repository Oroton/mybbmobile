<?php
/**
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

if(defined("IN_ADMINCP"))
{
	require_once MYBB_ROOT."inc/plugins/mybbmobile/admin.php";
}
else
{
	require_once MYBB_ROOT."inc/plugins/mybbmobile/hooks/global.php";
	switch(THIS_SCRIPT)
	{
		case "misc.php": require_once MYBB_ROOT."inc/plugins/mybbmobile/hooks/misc.php"; break;
		case "showthread.php": require_once MYBB_ROOT."inc/plugins/mybbmobile/hooks/showthread.php"; break;
		case "usercp.php": require_once MYBB_ROOT."inc/plugins/mybbmobile/hooks/usercp.php"; break;
	}
}