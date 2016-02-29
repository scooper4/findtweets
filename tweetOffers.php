<?php
$startTime =microtime();	// time stamp for start of program
require "autoload.php";		//autoloader for twitter oauth
use Abraham\TwitterOAuth\TwitterOAuth;
require "autoload1.php"; 	//autoloader for phpdotenv
$dotenv = new Dotenv\Dotenv(__DIR__);	//create new dot env object
$dotenv->load();	//Load the enviromental variables from dotenv object

//Keys for Twitter API usuage //
$CONSUMER_KEY = getenv('CONSUMER_KEY');
$CONSUMER_SECRET = getenv('CONSUMER_SECRET');
$access_token = getenv("access_token");
$access_token_secret = getenv("access_token_secret");

$connection = new TwitterOAuth($CONSUMER_KEY, $CONSUMER_SECRET, $access_token, $access_token_secret);
$content = $connection->get("account/verify_credentials");
date_default_timezone_set('America/New_York');	//set time Zone to NY

#Open test file and check for errors
$testFilePath = "testfile.txt";
$testFile = fopen($testFilePath, "w+");
if( $testFile == false ) { 
            echo ( "Error in opening file" );
            exit();
}

// Open final file and check for errors
$finalFilePath = "final.txt";
$finalFile = fopen($finalFilePath, "r+");

// Find the Latest tweet and split by | delimiter we can use latestTweetTime 
// for adding to the file during the loop 0 is tweet time, 1 is screenname,2 is tweet string
$firstLine = file("final.txt");
$latestTweet = $firstLine[0]; 
$tweetSplit = explode("|", $latestTweet); 
$latestTweetTime = $tweetSplit[0];

# Return status from list offers- recruiting use url encode to ensure proper url protocol
$statuses = $connection->get("lists/statuses", ["slug" => urlencode("offers-recruiting1"), "owner_screen_name" => ballastv,"include_rts" => true
	,"count" => 1000]);

#loop through each status and see if it contains a substring print if true if false print nope
foreach ($statuses as $tweet) {
	if (strpos(strtolower($tweet->text), "offer") || strpos(strtolower($tweet->text), "commit")|| strpos(strtolower($tweet->text), "scholarship")){
		echo $tweet->text, "\n" ;
		$time = strtotime($tweet->created_at); //parse json time into php time
		$fileRow = $time."|".$tweet->user->screen_name."|".$tweet->text.PHP_EOL; #Concatenate string screename uses delimeter, from tweet text
		
		if($latestTweetTime < $time) { // check if latesttweetime in final final file is < this tweets creation if so write
			$write = fwrite($testFile, $fileRow);#Write each concatenated string to file in loop
			if( $write == false ) {
            echo ( "Error in writting to file" );
            exit();
         	}
	        // send direct messages to user  and error checking//
			$connection->post("direct_messages/new", ["screen_name" => urlencode("neballacademy"), "text" => $tweet->text]);
			$connection->post("direct_messages/new", ["screen_name" => urlencode("ballastv"), "text" => $tweet->text]);
				echo $connection->getLastHttpCode();
			if ($connection->getLastHttpCode() == 200) {
			    echo " Succeessly Sent Message \n"; // Tweet posted succesfully
			} else {
			    echo " Failure on Message Send \n"; // Handle error case
			    $body = $connection->getLastBody();
			    print_r($body);
			    echo " \n";
			}
		}
		
		
		
	}
	else {
		echo "Nope", "\n\n";

	}
} #end status's loop

appendFiles($testFilePath,$finalFilePath); // append files together
fclose($testFile); //close the file
fclose($finalFile); //close the file

///*****************************************************///
///	
///		Method Name: appendFiles()	
///
///		Description : Appends looped or test file to the begining 
///		of the final file.
///
///		Paramters : Takes in two paths test and final file paths
///		
///		Returns: Nothing
///		
///*****************************************************///
	function appendFiles($test,$final){
	$a = file_get_contents($test);
	$b = file_get_contents($final);
	$a .= $b;
	file_put_contents($final, $a);

}
$endTime = microtime();	//program end timestamp
$runTime =  $endTime - $startTime; // time it took for program to run
echo "This program took ", $runTime, " to run ";



?>