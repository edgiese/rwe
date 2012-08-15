<?php if (FILEGEN == 1) die;
// entry point for filegen xml feeds. aus that access this must have stable long names, since the longname is encoded into a persistent link for the feed

// set up class autoloader and trace functions: 	
$qq=array();
$qq['tracefile']='m/feedfile.txt';

include("setup.php");

// format is file.php?r=au_long_name&s=subtype[&p=project]
try {
	if (!isset($_REQUEST['r']) || !isset($_REQUEST['s']))
		die;		// didn't come from us
	$formlong=$_REQUEST['r'];
	$subtype=$_REQUEST['s'];
	// sets up database access and $qqc, $qqu, and $qqs:
	include("setupdb.php");
	// NOTE:  qqj and qqy (js and stylegen) are not being initialized for commands.  their use is verboten in processFeed!!

	$au=$qqp->createFromLong($formlong,Null,'',0);
	$au->processFile($subtype);
	include('cleanupdb.php');			
} catch (Exception $e) {
	include('exception.php');
}
include('cleanup.php');
?>
