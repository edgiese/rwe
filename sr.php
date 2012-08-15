<?php if (FILEGEN == 1) die;
// 'set render' routine for debugging and render overrides
// sr.php?r=[0,1,2,4]&q='page to redirect to'&u='project'
// 0 == simulate stateless

// set up class autoloader and trace functions: 	
$qq=array();
include("setup.php");
try {
	if (!isset($_REQUEST['r']))
		die;		// didn't come from us
	$originUri=isset($_REQUEST['q']) ? urldecode($_REQUEST['q']) : False;

	// sets up database access and $qqc, $qqu, and $qqs:
	include("setupdb.php");

	$message=$qqs->forceRender($_REQUEST['r']);	

	// if au returns False, then it did the output already
	if (False !== $originUri) {
		header("location:$originUri");
	}
	echo "<p>render reset to {$_REQUEST['r']}.  Status message: $message</p>";
		
	include('cleanupdb.php');			
} catch (Exception $e) {
	include('exception.php');
}
include('cleanup.php');
?>
