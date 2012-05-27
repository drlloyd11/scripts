<?php
require_once("phpbb.class.php");


function updateTable($phpSrc,$tableName, $nameValues){

	foreach ($nameValues as $key => &$value) {
	//	$query = "update ".$phpSrc."."$tableName." set ".$key." = ".$value." where "
	}
}
function getTopics($phpSrcx,$topicStart,$topicStop){
	include('config.php');
	include('kittenconfig.php');
	$con = mysql_connect($dbhost, $dbuser,$dbpasswd);
	mysql_select_db($phpSrc);
	$query = "SELECT * FROM ".$phpSrcx.".phpbb_topics where topic_id >=$topicStart and topic_id <$topicStop";
	$result = mysql_query($query )
	or die(mysql_error());
	return $result;
}

function getUserByName($phpSrcx,$userName){
	include('config.php');
	include('kittenconfig.php');
	$con = mysql_connect($dbhost, $dbuser,$dbpasswd);
	mysql_select_db($phpSrc);
	$query = "SELECT * FROM ".$phpSrcx.".phpbb_users where username like \"".$userName."\"";
	$result = mysql_query($query )
	or die(mysql_error());
	$row = mysql_fetch_array(  $result );
	return $row;
}
function getUserByID($phpSrcx,$userName){
	include('config.php');
	include('kittenconfig.php');
	$con = mysql_connect($dbhost, $dbuser,$dbpasswd);
	mysql_select_db($phpSrc);
	$query = "SELECT * FROM ".$phpSrcx.".phpbb_users where user_id like \"".$userName."\"";
	$result = mysql_query($query )
	or die(mysql_error());
	$row = mysql_fetch_array(  $result );
	return $row;
}
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
function getPosts($phpSrcx,$postId,$postStop){
	include('config.php');
	include('kittenconfig.php');
	$con = mysql_connect($dbhost, $dbuser,$dbpasswd);
	mysql_select_db($phpTarget);
	$query = "SELECT * FROM ".$phpSrcx.".phpbb_posts where post_id >=$postId and post_id < $postStop";
	$result = mysql_query($query )
	or die(mysql_error());
	$row = mysql_fetch_array(  $result );
	return $row;
}
include('config.php');
include('kittenconfig.php');
$result = getTopics($phpTarget,605,6278);
while($row = mysql_fetch_array(  $result )) {
	//echo $row['topic_title']."\n";
	$postStart = $row['topic_first_post_id'];
	$postStop = $row['topic_last_post_id'];
	$topicId=  $row['topic_id'];
	$firstPost = getSinglePost($phpTarget,$postStart);
	$lastPost = getSinglePost($phpTarget,$postStop);
	$firstPosterByName = $firstPost['post_username'];
	$lastPosterByName = $lastPost['post_username'];
	if (strlen( $firstPosterByName)< 1){
		$firstPoster = getUserByID($phpTarget,$firstPost['poster_id']);
		$firstPosterByName = $firstPoster['username'];
		if (strlen($firstPosterByName)< 1){
			echo "exit";
		exit();
		}
		
	}
	
	if (strlen( $lastPosterByName)< 1){
		$lastPoster = getUserByID($phpTarget,$lastPost['poster_id']);
		
		$lastPosterByName = $lastPoster['username'];
		if (strlen($lastPosterByName)< 1){
			echo "exit";
		exit();
		}
	
	}
	
		$query1 = "update ".$phpTarget.".phpbb_topics set topic_first_poster_name =\"".$firstPosterByName."\" where topic_id =".$topicId;
		//echo $query1."\n";
	
		$resultQ1 = mysql_query($query1 )
		or die(mysql_error());
	
		$query2 = "update ".$phpTarget.".phpbb_topics set topic_last_poster_name =\"".$lastPosterByName."\" where topic_id =".$topicId;
		//echo $query2."\n";
		$resultQ2 = mysql_query($query2 )
		or die(mysql_error());
	
	
	
}

