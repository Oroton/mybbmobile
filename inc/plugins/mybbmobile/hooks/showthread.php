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

// Page numbers
$plugins->add_hook("showthread_end", "mybbmobile_showthread");

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