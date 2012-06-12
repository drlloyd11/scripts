<?php
include('config.php');
include('kittenconfig.php');
$con = mysql_connect($dbhost, $dbuser,$dbpasswd);
mysql_select_db($phpSrc);
$run = 1;
$postCount= 500;
$postStop =600;
$postIndex = 100;
$topicTest = 2891;
while ($run == 1){
	$query = "SELECT * FROM ".$phpSrc.".phpbb_posts where post_id >$postCount and post_id <$postStop  order by post_id "  ;
	//$query = "SELECT * FROM ".$phpSrc.".phpbb_posts    where topic_id = 3000 order by post_id"  ;
	
	
	#echo $query."\n";
	#the below converts one topic
	#$queryFind = "SELECT * FROM ".$phpSrc.".phpbb_posts where topic_id =$topicTest  order by post_id "  ;
	#$run =0;  #run once
	if ($postCount > 500000){
	//	break;
	}
	mysql_select_db($phpSrc);
	$result = mysql_query($query )
	or die(mysql_error());

	while($row = mysql_fetch_array(  $result )) {


		if (count($row) <1){
			$run =0;
			continue;
		}

		$postPI = $row['post_id'];
		$newPost = $row['post_text'];
		mysql_select_db("phpbb2");
		$tags = get_indices($newPost,"&lt;");
		//echo count($tags)."\n";
		$exceptions = array('a href','img src','br');
		foreach ($tags as $curTag){	
			$tmpTag = substr($newPost,$curTag+3,10);
			foreach ($exceptions as $valid){
				$val = substr($newPost, $tmpTag,strlen($valid));
			if (strcasecmp($val,$valid) == 0){
				echo "matched on ".substr($newPost, $curTag + 4,strlen($valid))."--".$valid."--\n";
			}
			else{
				echo "no match --\n";
			}
			}
			
		}
		/*	
		$newPost = htmlspecialchars_decode($newPost);

		


		$newPost = str_replace("&lt","<",$newPost );

		$newPost = str_replace("&gt",">",$newPost );
		$newPost = str_replace("<em>","<i>",$newPost );
		$newPost = str_replace("<em>","</i>",$newPost );

		$newPost = ez2bbcode($newPost);
		*/
		$newPost = str_replace("http://users.adelphia.net/~xita/kitten/heart.gif","http://thekittenboard.com/board/images/smilies/smiley.gif" ,$newPost);
			
		$newPost = str_replace("http://users.adelphia.net/~xita/kitten/","http://thekittenboard.com/board/images/smilies/",$newPost);
		$newPost = str_replace("http://fractalcore.com/nocgi/","http://thekittenboard.com/board/images/smilies/",$newPost);

		$newPost = str_replace("\"","&quot;",$newPost );
		$newQuery = sprintf("UPDATE ".$phpTarget.".phpbb_posts SET post_text = '%s' where post_id= '%s'", mysql_real_escape_string($newPost), $postPI);
		$val  =1;
		#echo "\n===========================\n";
			#echo $newQuery;
		#echo "\n=============================\n";
		$val =  mysql_query($newQuery);
		if (!$val){
			echo mysql_error();
		}
	}


	$postCount = $postCount + $postIndex;
	$postStop = $postStop + $postIndex;
	echo $postCount;
	echo "\n";
}

function ez2bbcode($s) {
	$ez2tags= array(
			'/(<!--EZCODE BOLD START-->)/is',#1
			'/(<!--EZCODE BOLD END-->)/is',#2
			'/(<!--EZCODE ITALIC START-->)/is',#3
			'/(<!--EZCODE ITALIC END-->)/is',#4
			'/(<!--EZCODE QUOTE START-->)/is',#5
			'/(<!--EZCODE QUOTE END-->)/is',#6
			'/(<!--EZCODE)[^>]*>/is',#7
			'/<!--.*?-->/is',#8
			'/(<!--EZCODE EMOTICON START-->)/is',#9
			'/(<!--EZCODE EMOTICON END-->)/is',#10
			



	);
	$bbtags= array(
			'<b>',#1
			'</b>',#2
			'<i>',#3
			'</i>',#4
			'<blockquote>',#5
			'</blockquote>',#6
			'',#7
			'',#8
			'',#9
			'',#10
			
				

	);
	$text = preg_replace ($ez2tags, $bbtags, html_entity_decode($s));
	return $text;
}
 
function replaceTags($startPoint, $endPoint, $newText, $source) {
	return preg_replace('#('.preg_quote($startPoint).')(.*)('.preg_quote($endPoint).')#si', '$1'.$newText.'$3', $source);
}
function GetBetween($content,$start,$end){
	$r = explode($start, $content);
	if (isset($r[1])){
		$r = explode($end, $r[1]);
		return $r[0];
	}
	return '';
}
function get_indices($haystack, $needle){
	$returns = array();
	$position = 0;
	while(strpos($haystack, $needle, $position) > -1){
		$index = strpos($haystack, $needle, $position);
		$returns[] = $index;
		$position = $index + strlen($needle);
	}
	return $returns;
}
