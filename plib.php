<?php
require_once("phpbb.class.php");

//$phpbb_action = "user_delete";//login";//@$_REQUEST["op"];
//***************************************************************
//parameters used at class construction
//first parameter = absoulute physical path of the phpbb 3 forum ($phpbb_root_path variable)
//second parameter = php scripts extensions ($phpEx variable)
include('config.php');
include('kittenconfig.php');
function getUser($dbName,$userName,$isUserId){
	
	$con = mysql_connect($dbhost, $dbuser,$dbpasswd);
	mysql_select_db($dbName);
	if ($isUserId >0){
		$query = "SELECT *  FROM ".$phpSrc.".phpbb_users a where a.user_id = $userName";
		
	}
	else{
	$query = "SELECT *  FROM ".$phpSrc.".phpbb_users a where a.username like".$userName."\";
	}
	$result = mysql_query($query )
	or die(mysql_error());
	$row = mysql_fetch_array(  $result );
	return $row;
}
function getSinglePost($dbName,$postId){
	$con = mysql_connect($dbhost, $dbuser,$dbpasswd);
	mysql_select_db($dbName);
	
	$query = "SELECT *  FROM ".$phpSrc.".phpbb_posts a where a.post_id =".$postId.";";
	$result = mysql_query($query )
	or die(mysql_error());
	$row = mysql_fetch_array(  $result );
	return $row;
}
function addUser($dbName,$userName,$passWord, $email,$sig,$dateReg,$dateLast,$group){
	$phpbb = new phpbb("./", "php");
	//TESTING DATA
	$phpbb_vars = array("username" => $userName, "user_password" => $passWord, "user_email" => $email, "group_id" => $group
			,"user_regdate"=>$dateReg, "user_lastvisit" =>$dateLast);
	//END TESTING DATA
	$phpbb_result = $phpbb->user_add($phpbb_vars);
	
	$phpbb_vars = array(/*"user_id" => "53", */"username" => $userName, "user_sig"=>$sig,"user_regdate"=>$dateReg,"user_lastvisit"=>$dateLast);
	$phpbb_updateresult = $phpbb->user_update($phpbb_vars);
//	$query = "update %s.phpbb_users set user_sig = %s,"user_regdate = %s where username like %s";
	//$queryOut = sprintf($query,$dbName, $dateReg,$userName);
	echo $queryOut;
	
}

addUser("thekitt_phpb2","test2","1234", "a@b.com","sadas asdas dsaads","1336358570","1336358560","2");

function makePostAs()
{
	$temp_user_id = $user->data['user_id'];
	$user->data['user_id'] = 14; // user ID of message poster
//	submit_post(blah, blah);
	$user->data['user_id'] = $temp_user_id;	
}


$row = getSinglePost("full_clean","205489");

