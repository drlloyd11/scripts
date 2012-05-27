<?php
include_once('simple_html_dom.php');
include('config.php');
include('kittenconfig.php');
$dbms = 'mysqli';
$dbhost = 'localhost';
$dbport = '3306';
$dbname = 'mydb';
$dbuser = 'root';
$dbpasswd = 'root';
$table_prefix = 'phpbb_';
$tableIs = 'netherData'; 
echo $argv[1]."-\n";
$con = mysql_connect($dbhost, $dbuser,$dbpasswd);
mysql_select_db("phptrans");

$dirName = $argv[1];   
echo $dirName;
try{
	//$conHandle= dbAccess();
}
catch (Exception $x){
	echo $ex."\n	";
}
$fullUserName = null;
$fullDate=null;
$fullPost =  null;
$fullForum = null;
$fullTopic = null;
$fullUrl = null;
//if ($handle = opendir($dirName)) {
//	while (false !== ($entry = readdir($handle))) {
date_default_timezone_set("America/New_York");
foreach(glob($dirName."*.html") as $entry){
	//echo ">>".$entry."\n";
	$fullFileName =  $entry;
	if ($entry != "." && $entry != ".." && is_file($fullFileName)) {

		//echo "filename ".$fullFileName."\n";
		// get DOM from URL or file
		$html = file_get_html($fullFileName);
		if(strstr( $html->plaintext," This post is missing or couldn't be ") != FALSE){
			continue;
		}
		$subject = $html->find('tr');
		//var_dump($subject);
	$length = count($subject);
	$title = $subject[0]->find('b');
	$topic = $title[0]->plaintext;
	
	//print "\ntitle=".$title[0]->plaintext."\n";
	if (strpbrk($fullFileName,"-")==FALSE){
		
		//continue;
		print "odd\n";
		$startVal = 1;
		$strIdx =1;
		$strBoundry=2;
	}
	else{
		print "even\n";
		$startVal = 1;
		$strIdx =1;
		$strBoundry=2;
	}
	for ($i = $startVal; $i < $length -$strBoundry	; $i+=$strIdx) {
  		//print $subject[$i]."\n";
  		
  		$panels = $subject[$i]->find('td');
  		$users = $panels[0]->find('a[name]');
  		$userName = $users[0];
  		print "--".$userName."--\n"; 
  		list($un,$two) = sscanf($userName,"<a name=%d>%s");
  		//print "Number:".$un[0]."\n";
  		$count = $un;
  		//echo "\n.....".$un."    --\n";
  		//print "...".$un."\n";
  		$userName= $userName->plaintext;
  		print "--------".$userName."\n";
  		//print "Name:".$userName."\n";
  		//print strpbrk($userName,"=");
  		//print $userName->plaintext."\n";
  		//print $users[0]."\n";
  		$hr = $subject[$i +1];
  		$text= $hr->find('font');
  		//$body = $text[0]->find('font');
  		//foreach ($text as $x){
  			//print"-->". $x."<--\n";
  		//}
  		$body = $text[4];
  		//print $body;
  		$postDate  =$text[3]->plaintext;
  		//print "\n--->".$postDate."<---\n";
  		list($dates,$month,$day,$year) = sscanf($postDate,"%s %s %d,%d %s");
  		#print "\n month:-->".$month."<--\n";
  		#print "\n day:-->".$day."<--\n";
  		#print "\n year:-->".$year."<--\n";
  		$postTime= strtotime("$day $month $year" );
  	//	print "title =".$title;
/*
 * 
 * +-------------+-------------+------+-----+---------+----------------+
| Field       | Type        | Null | Key | Default | Extra          |
+-------------+-------------+------+-----+---------+----------------+
| phpid       | int(11)     | NO   | PRI | NULL    | auto_increment |
| username    | varchar(45) | YES  |     | NULL    |                |
| topic_title | varchar(45) | YES  |     | NULL    |                |
| postdate    | int(11)     | YES  |     | NULL    |                |
| post_index  | varchar(45) | NO   | PRI | NULL    |                |
| post_text   | mediumtext  | YES  |     | NULL    |                |
| notes       | varchar(45) | YES  |     | NULL    |                |
+-------------+-------------+------+-----+---------+----------------+
7 rows in set (0.00 sec)


 */
  		$queryString ="INSERT INTO phptrans.posts (index, username, topic_title, postdate, post_index,post_text,notes)"; 
  				$queryString =$queryString."VALUES (%d,\"%s\",\"%s\",%s,\"%s\", \"%s\",\"%s\", \"%s\",\"%s\", \"%s\", \"%s\" )";
  		
  		$query = sprintf(,$queryString,
  				 $userName,mysql_real_escape_string($topic), $postTime,$count, mysql_real_escape_string ($body),"Novogate");
  		//,mysql_real_escape_string($fullFileName), mysql_real_escape_string ($body),mysql_real_escape_string ($body),"Novogate",1,"2",);
  	//	$query ="INSERT INTO posts (index, topic_title, user_name, postdate,origin,file_name,post_text) " ;
  		
  	//	$query = $query."VALUES (".$count.", mysql_real_escape_string($title),$userName, $postTime,Novogate,mysql_real_escape_string($fullFileName), mysql_real_escape_string ($body))";
  		print $query."..\n";
  	//	$result = mysql_query($query )
  		//		or die(mysql_error());

  		//print $hr->outerhtml."\n";
  		//print "\n post time $postTime\n";
}
	//	foreach($subject as $name){
		//	echo $name."-------------\n";
		//}
		
// get subject
//
//<A name=2>
/*
$subject = $html->find('title');
echo $subject[0]."-------------\n";
		
		$first = 1;
		foreach( $html->find('A[name^=name') as $entry){ //every other one
			if ($first == 1){
				$first = 0;
				continue;	
			}   
			echo $entry."\n";
		
			}
			*/
	}

	
}
