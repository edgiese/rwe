<?php if (FILEGEN == 1) die;
// entry point for filegen xml feeds. aus that access this must have stable long names, since the longname is encoded into a persistent link for the feed

// set up class autoloader and trace functions: 	
$qq=array();
$qq['tracefile']='m/feedfile.txt';

include("setup.php");

// format is feed.php?r=au_long_name&s=feedname
try {
	if (!isset($_REQUEST['r']) || !isset($_REQUEST['s']))
		die;		// didn't come from us
	$formlong=$_REQUEST['r'];
	$subtype=$_REQUEST['s'];
	// sets up database access and $qqc, $qqu, and $qqs:
	include("setupdb.php");
	// NOTE:  qqj and qqy (js and stylegen) are not being initialized for commands.  their use is verboten in processFeed!!

	$au=$qqp->createFromLong($formlong,Null,'',0);
	$output=$au->processFeed($subtype);
	// if au returns False, then it did the output already
	if (False !== $output) {
		header ("Content-type: application/xml");
		echo $output;		
	}	
	include('cleanupdb.php');			
} catch (Exception $e) {
	include('exception.php');
}
include('cleanup.php');
?>
