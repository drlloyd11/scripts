<?php

ini_set('error_reporting', E_ALL);
ini_set('display_errors', 'On');


// The default phpBB inclusion protection - required
define('IN_PHPBB', true);
$phpbb_root_path = '';
$phpEx = substr(strrchr(__FILE__, '.'), 1);
 

include($phpbb_root_path . 'common.' . $phpEx);
include($phpbb_root_path . 'includes/functions_admin.' . $phpEx);
include($phpbb_root_path . 'includes/functions_posting.' . $phpEx);
function getSinglePost($phpSrcx,$postId){
	include('config.php');
	include('kittenconfig.php');
	$con = mysql_connect($dbhost, $dbuser,$dbpasswd);
	mysql_select_db($phpSrc);
	$query = "SELECT * FROM ".$phpSrcx.".phpbb_posts where post_id =$postId";
	$result = mysql_query($query )
	or die(mysql_error());
	$row = mysql_fetch_array(  $result );
	return $row;
}

include('config.php');
include('kittenconfig2.php');
#include('phpbb-libs.php');
$con = mysql_connect($dbhost, $dbuser,$dbpasswd);
mysql_select_db($phpTaregt);
$run = 1;
$postCount= 0;
$postStop =100;
$postIndex = 100;
$topicTest = 2891;
if ($argc <3){
	echo "missing arguments\n";
	exit(0);
}
$target = $argv[2];
$forumId = $argv[1];
while ($run == 1){
	$query = "select * from ".$target.".phpbb_forums where forum_id=".$forumId.";";
 
	
	
	mysql_select_db($phpTarget);
	$result = mysql_query($query )
	or die(mysql_error());

	while($row = mysql_fetch_array(  $result )) {
		
		echo $row['forum_name']."\n";
		echo $row['forum_last_poster_id']."\n";
		echo $row['forum_last_post_time']."\n";
		$postid = $row['forum_last_post_id'];
		echo $row['forum_last_post_id']."\n";
		
		$post = getSinglePost($phpTarget,$postid);
		echo $post['post_time']."\n";
		$newTime = $post['post_time'];
		$querySub ="update ".$target.".phpbb_forums a set a.forum_last_post_time = ".$newTime." where a.forum_id=".$forumId.";";
		echo $querySub;
		$result2 = mysql_query($querySub )
		or die(mysql_error());
	}
	$run =0;
	/*
	 * | forum_last_post_id       | mediumint(8) unsigned | NO   | MUL | 0       |                |
| forum_last_poster_id     | mediumint(8) unsigned | NO   |     | 0       |                |
| forum_last_post_subject  | varchar(255)          | NO   |     |         |                |
| forum_last_post_time     | int(11) unsigned      | NO   |     | 0       |                |
| forum_last_poster_name   | varchar(255)          | NO   |     |         |                |
| forum_last_poster_colour | varchar(6)            | NO   |     |         |                |

	 */
	$postCount = $postCount + $postIndex;

	}