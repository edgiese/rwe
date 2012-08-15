<?php if (FILEGEN == 1) die;
// entry point for all filegen forms. processing is done here, and the user agent is
// redirected to a display that can be refreshed without consequences

// set up class autoloader and trace functions: 	

$qq=array();
$qq['tracefile']='m/formfile.txt';

include("setup.php");
try {
	if (!isset($_REQUEST['q']) || !isset($_REQUEST['r']) || !isset($_REQUEST['s']) || !isset($_REQUEST['t']))
		die;		// didn't come from us
	$originUri=urldecode($_REQUEST['q']);
	$formshort=$_REQUEST['r'];
	$state=$_REQUEST['s'];
	$data=$_REQUEST['t'];

	// sets up database access and $qqc, $qqu, $qqs, and $qqp:
	include("setupdb.php");
	// NOTE:  qqj and qqy (js and stylegen) are not being initialized for commands.  their use is verboten in processVars!!
	
	$au=$qqp->createFromShort($formshort,Null,$state,$data);

	$redirect=$au->processVars($originUri);
	// if au returns False, then it did the output already
	if (False !== $redirect) {
		infolog("form","form processed, redirecting to $redirect");
		header("location:$redirect");
	}	
	include('cleanupdb.php');			
} catch (Exception $e) {
	include('exception.php');
}
include('cleanup.php');
?>
