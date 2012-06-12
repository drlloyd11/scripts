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

function testOne(){
	
	$forumtoPost = 361;
	
	$msgSubject = "This is a new thread";
	//$msgSubject = "The new Cliche Thread3";
	$msgText = "--Here is an automatic post from my new function code";
	
	$newPostID = createPost ($forumtoPost,"Brand new thread",$msgText,"xita","post",0,891239402);
	
	//$newPostID = createPost ($forumtoPost,$msgSubject,$msgText,"drlloyd11","reply",2539,0);
	
	echo "post id is now  $newPostID" .
	// Restore the original backed up logged in user data
	extract($backup);
	updatePostTime($phpTargetToUse, $newPostID,891239402);
}

function updatePostTime($phpSrcx,$postId,$time){
	include('config.php');
	include('kittenconfig.php');
	$con = mysql_connect($dbhost, $dbuser,$dbpasswd);
	mysql_select_db($phpSrcx);
	$query = "update ".$phpSrcx.".phpbb_posts set post_time=  ".$time." where post_id =".$postId;
//	echo $query;
	$result = mysql_query($query )
	or die(mysql_error());
	
	//$row = mysql_fetch_array(  $result );
	//return $row;
}

function getTopics($phpSrcx,$topicStart,$topicStop){
	include('config.php');
	include('kittenconfig.php');
	$con = mysql_connect($dbhost, $dbuser,$dbpasswd);
	mysql_select_db($phpSrcx);
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
function updateTopicTime($phpSrcx,$topicId,$time){
	include('config.php');
	include('kittenconfig.php');
	$con = mysql_connect($dbhost, $dbuser,$dbpasswd);
	mysql_select_db($phpSrcx);
	$query = "update ".$phpSrcx.".phpbb_topics set topic_time=  ".$time." where topic_id =".$topicId;
	$result = mysql_query($query )
	or die(mysql_error());
	$query = "update ".$phpSrcx.".phpbb_topics set topic_last_post_time=  ".$time." where topic_id =".$topicId;
	$result = mysql_query($query )
	or die(mysql_error());
	$query = "update ".$phpSrcx.".phpbb_topics set topic_last_view_time=  ".$time." where topic_id =".$topicId;
	$result = mysql_query($query )
	or die(mysql_error());
	//$row = mysql_fetch_array(  $result );
	//return $row;
}
function createPost ($forum,$msgSubject,$msgText,$userName,$type,$topic,$time) {

	// note that multibyte support is enabled here
	$my_subject   = utf8_normalize_nfc($msgSubject); // request_var('subject', '', true) was in place of $msgSubject


	$my_text   = utf8_normalize_nfc($msgText); // request_var('my_text', '', true) was in place of $msgText

	// variables to hold the parameters for submit_post
	$poll = $uid = $bitfield = $options = '';

	generate_text_for_storage($my_subject, $uid, $bitfield, $options, false, false, false);
	$useTopic = 0;
	if ($topic >0){
		$useTopic = $topic;
	}
	generate_text_for_storage($my_text, $uid, $bitfield, $options, true, true, true);
	$data = array(
			// General Posting Settings
			'forum_id'            => $forum,    // The forum ID in which the post will be placed. (int)
			'topic_id'            => $useTopic,    // Post a new topic or in an existing one? Set to 0 to create a new one, if not, specify your topic ID here instead.
			'icon_id'            => false,    // The Icon ID in which the post will be displayed with on the viewforum, set to false for icon_id. (int)

			// Defining Post Options
			'enable_bbcode'    => true,    // Enable BBcode in this post. (bool)
			'enable_smilies'    => true,    // Enabe smilies in this post. (bool)
			'enable_urls'        => true,    // Enable self-parsing URL links in this post. (bool)
			'enable_sig'        => true,    // Enable the signature of the poster to be displayed in the post. (bool)

			// Message Body
			'message'            => $my_text,        // Your text you wish to have submitted. It should pass through generate_text_for_storage() before this. (string)
			'message_md5'    => md5($my_text),// The md5 hash of your message

			// Values from generate_text_for_storage()
			'bbcode_bitfield'    => $bitfield,    // Value created from the generate_text_for_storage() function.
			'bbcode_uid'        => $uid,        // Value created from the generate_text_for_storage() function.

			// Other Options
			'post_edit_locked'    => 0,        // Disallow post editing? 1 = Yes, 0 = No
			'topic_title'        => $my_subject,    // Subject/Title of the topic. (string)

			// Email Notification Settings
			'notify_set'        => false,        // (bool)
			'notify'            => false,        // (bool)
			'post_time'         => $time,        // Set a specific time, use 0 to let submit_post() take care of getting the proper time (int)
			'forum_name'        => '',        // For identifying the name of the forum in a notification email. (string)

			// Indexing
			'enable_indexing'    => true,        // Allow indexing the post? (bool)

			// 3.0.6
			'force_approved_state'    => true, // Allow the post to be submitted without going into unapproved queue
	);
	submit_post($type, $my_subject, $userName, POST_NORMAL, $poll, $data);
	
	
	updateTopicTime($phpTargetToUse, $data['topic_id'],$time);
	updatePostTime($phpTargetToUse, $data['post_id'],$time);
	
	return $data;
	//return $data['post_id'];
 
}


function initPoster(){
	$user->session_begin();
	$auth->acl($user->data);
	$user->setup();
	
	//echo "---;";
	
	// Backup the details of the logged in user
	$backup = array(
			'user'   => $user,
			'auth'   => $auth,
	);
}
function testUpdate($user,$auth){
$user->session_begin();
$auth->acl($user->data);
$user->setup();

//echo "---;";

// Backup the details of the logged in user
$backup = array(
  'user'   => $user,
  'auth'   => $auth,
);

// The ID of the parent category / forum
$forumParentID = 15;

// The forum to copy permissions from
$forumPpermFrom = 2;

// The type of forum to create. FORUM_CAT for category and FORUM_POST for regular forum and FORUM_LINK for a link forum (?)
$forumType = FORUM_POST;
  

$forumDesc = "Automatically created by Iains new function Script as if by magic!";

// Check that user (System) has permissions to create forums. Taken from includes/acp/acp_forums.php around line 71
// Error trigger taken out and replaced with simple echo. The error shouldnevr happen but it's just in case..
//if (!$auth->acl_get('a_forumadd'))
//{
  //echo "ERROR! Problem with forum creation permissions for user";
//}

//$newForumID = createCategoryForum($forumParentID,$forumPpermFrom,$forumType,$forumName,$forumDesc);

//echo "Forum created with ID of $newForumID";

$forumtoPost = 6;      
 
$msgSubject = "This is a new thread";
//$msgSubject = "The new Cliche Thread3";
$msgText = "--Here is an automatic post from my new function code";

$newData = createPost ($forumtoPost,"Brand new thread",$msgText,"xita","post",0,0);

$newPostID = $newData['post_id'];
echo $newData['topic_id'];
//$newPostID = createPost ($forumtoPost,$msgSubject,$msgText,"drlloyd11","reply",2539,0);

echo "post id is now  $newPostID" . 
// Restore the original backed up logged in user data

updatePostTime($phpTargetToUse, $newPostID,891239402);
updateTopicTime($phpTargetToUse, $newData['topic_id'],891239402);
extract($backup);
}
//testUpdate($user,$auth);
$user->session_begin();
$auth->acl($user->data);
$user->setup();

//echo "---;";

// Backup the details of the logged in user
$backup = array(
		'user'   => $user,
		'auth'   => $auth,
);

//$queryStr ="select * from thekitt_archive.posts limit 10";
//$result = mysql_query($queryStr )
//or die(mysql_error());
//get all 
include('config.php');
include('kittenconfig.php');
//include ('phpbb-libs.php');
$con = mysql_connect($dbhost, $dbuser,$dbpasswd);
mysql_select_db($phpTargetToUse);
$postCount =1;
$postStop =300;
$postIndex =100;
$run = 1;
if ($argc <3){
	echo "too few arguments\n";
	exit();
}
$phpTargetToUse = $argv[1];
$forumIdToUse = $argv[2];
$forumNameToUse= $argv[3];
if ($argc >4){  
	$safety=true;
}
else
{
	$safety = false;
}
//get topics

$query = "SELECT distinct topic_title  FROM thekitt_archive.posts" ;
mysql_select_db("thekitt_archive");
$result = mysql_query($query )
or die(mysql_error());

//while($row = mysql_fetch_array(  $result )) {
$row = mysql_fetch_array(  $result );
echo sizeof($row);
$topicList = array();
while($row = mysql_fetch_array(  $result )){
	echo  $row['topic_title']."\n";
	$topicList.array_push($topicList,$row['topic_title'] );
}
//echo "--".sizeof($topicList)."\n";
foreach ($topicList as $topic){
	$query = "SELECT *  FROM thekitt_archive.posts where topic_title = \"". mysql_real_escape_string($topic)."\" order by post_index;";
	mysql_select_db("thekitt_archive");
	$result = mysql_query($query )
	or die(mysql_error());
	$first =1;
	$newTopicId = null;
	
	echo "\nNew topic".$topic."\n";    
	while($row = mysql_fetch_array(  $result )){
		//echo "x \n";
		if (strcmp($row['notes'],$forumNameToUse) ==0){
			$forumtoPost = $forumIdToUse;
		 
		
			if ($first == 1){
				$first =0;
				mysql_select_db($phpTargetToUse);
				$newDataList = createPost($forumtoPost,$row['topic_title'],$row['post_text'],$row['username'],"post",0,$row['postdate']);
				$newTopicId =$newDataList['topic_id'];
			}
			else
			{
				mysql_select_db($phpTargetToUse);
				$newData = createPost ($forumtoPost,$row['topic_title'],$row['post_text'],$row['username'],"reply",$newTopicId,$row['postdate']);
			}
		}

	}
	
}
$postCount = $postCount + $postIndex;
$postStop = $postStop + $postIndex;
echo $postCount;

?>