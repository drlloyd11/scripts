<?php
include_once('simple_html_dom.php');
//include('config.php');
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
	echo ">>".$entry."\n";
	$fullFileName =  $entry;
	if ($entry != "." && $entry != ".." && is_file($fullFileName)) {

		echo "filename ".$fullFileName."\n";
		// get DOM from URL or file
		$html = file_get_html($fullFileName);
		if(strstr( $html->plaintext," This post is missing or couldn't be ") != FALSE){
			continue;
		}
		$subject = $html->find('tr');
		//var_dump($subject);
	$length = count($subject);
	$title = $subject[0]->find('b');
	print "\ntitle=".$title[0]->plaintext."\n";
	for ($i = 2; $i < $length -2	; $i+=2) {
  		//print $subject[$i]."\n";
  		
  		$panels = $subject[$i]->find('td');
  		$users = $panels[0]->find('a[name]');
  		$userName = $users[0];
  		list($un,$two) = sscanf($userName,"<a name=%s>%s");
  		print "Number:".$un[0]."\n";
  		$count = $un[1];
  		
  		$userName= $userName->plaintext;
  		print "Name:".$userName."\n";
  		//print strpbrk($userName,"=");
  		//print $userName->plaintext."\n";
  		//print $users[0]."\n";
  		$hr = $subject[$i +1];
  		$text= $hr->find('font');
  		//$body = $text[0]->find('font');
  		$body = $text[4];
  		print $body;
  		$postDate  = $text[3]->plaintext;
  		print $postDate."\n";
  		//print $hr->outerhtml."\n";
  		print "\n ---------------------\n";
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
