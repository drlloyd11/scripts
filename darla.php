<?php
include('config.php');
include('kittenconfig.php');
include ('phpbb-libs.php');




$con = mysql_connect($dbhost, $dbuser,$dbpasswd);
mysql_select_db($phpSrc);
$run = 1;
$postCount= 500;
$postStop =600;
$postIndex = 100;
$topicTest = 2891;
while ($run == 1){
	#echo $argc."-\n";
	if ($argc >2){
		$query = "SELECT * FROM ".$phpSrc.".phpbb_posts where topic_id =".$argv[2].";\n";
		$run  =0;
	}
	else
	{
		$query = "SELECT * FROM ".$phpSrc.".phpbb_posts where post_id >=$postCount and post_id <$postStop  order by post_id "  ;
	}
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
			
		$newPost = htmlspecialchars_decode($newPost);




		$newPost = str_replace("&lt","<",$newPost );

		$newPost = str_replace("&gt",">",$newPost );
		$newPost = str_replace("<em>","<i>",$newPost );
		$newPost = str_replace("<em>","</i>",$newPost );
		$newPost = str_ireplace("{smilies_path}","\images\smilies",$newPost );

		$newPost = ez2bbcode($newPost);
		$newPost = str_replace("http://users.adelphia.net/~xita/kitten/heart.gif","http://thekittenboard.com/board/images/smilies/smiley.gif" ,$newPost);
			
		$newPost = str_replace("http://users.adelphia.net/~xita/kitten/","http://thekittenboard.com/board/images/smilies/",$newPost);
		$newPost = str_replace("http://fractalcore.com/nocgi/","http://thekittenboard.com/board/images/smilies/",$newPost);

		$newPost = str_replace("\"","&quot;",$newPost );
		
		if (($argc >1 ) ){
			if ($argv[1] == 's'){
			$newPost =scrubber($newPost,$postPI);}
		}
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
			'/(<!--EZCODE FONT START-->)/is',#11
			'/(<!--EZCODE FONT END-->)/is',#12
			'/(<!--EZCODE UNDERLINE START-->)/is',#13
			'/(<!--EZCODE UNDERLINE END-->)/is',#14
		
			



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
			'',#11
			'',#12
			'',#13
			'',#14
			
				

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
function scrubber($value,$postID){
	$fronts = get_indices($value,"<");
	#echo "fronts ".count($fronts)."\n";
	$goodValues = array('<b>','\b','<strong>','</strong>','<blockquote>','</blockquote>','<em>','</em>','<hr>','</b>','<span ','</span>','</font>','<div ', '<hr />' , '</div>',
			'<a class','<p class','<span>','</br>','<br align','<font >','</font/>','</u>','<u>','<center>','</center>','</font ', '<p class','<p align','<small>','</small>','<p align',
			'<blockquote ');
	$tags =array();
	$skip = 0;
	foreach (array_reverse($fronts) as $lt){
	// echo "ptr--".$lt."--\n";
		$back = strpos($value,">",$lt + 1);
		$val = substr($value,$lt, $back- $lt +1);
		#echo "bk=".$val."\n";
		array_push($tags,$val);
		if (strncasecmp( $val,"<br>",4)==0){
			continue;
		}
		if (strncasecmp( $val,"<p>",3)==0){
			continue;
		}
		if (strncasecmp( $val,"<img",4)==0){
			continue;
		}
		if (strncasecmp ($val,"<i>",3)==0){
			continue;
		}
		if (strncasecmp ($val,"</i>",3)==0){
			continue;
		}
		if (strncasecmp ($val,"</p>",3)==0){
			continue;
		}
		if (strncasecmp ($val,"</a>",3)==0){
			continue;
		}
		if (strncasecmp ($val,"<font",5)==0){
			continue;
		}
		if (strncasecmp ($val,"<a href",7)==0){
			continue;
		}
		foreach ($goodValues as $validName){
			if (strncasecmp ($val,$validName,strlen($validName))==0){
				
				$skip =1;
				break;
			}
		}
		if ($skip == 1){
			$skip = 0;
			continue;
		}
		//echo "suspect: ".$postID." =".$val."\n";
		$value =substr_replace($value,"</i>",$back,1);
		$value= substr_replace($value,"<i>",$lt,1);
		//$value[$lt] = "x";
		//$value[$back] = "x";
		//echo "\n--".(strlen($val) - $lt)."--\n";
	
	}
	return $value;
}
function get_indices($haystack, $needle){
	$returns = array();
	$position = 0;
	while(strpos($haystack, $needle, $position) > -1){
		$index = strpos($haystack, $needle, $position);
		array_push($returns,$index);
	//	$returns[] = $index;
		$position = $index + strlen($needle);
	}
	return $returns;
}