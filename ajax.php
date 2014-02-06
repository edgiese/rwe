<?php if (FILEGEN == 1) die;
// entry point for all filegen forms. processing is done here, and the user agent is
// redirected to a display that can be refreshed without consequences

// set up class autoloader and trace functions: 	

$qq=array();
$qq['tracefile']='m/ajaxfile.txt';

include("setup.php");
try {
	if (!isset($_REQUEST['q']) || !isset($_REQUEST['r']) || !isset($_REQUEST['s']) || !isset($_REQUEST['t']) || !isset($_REQUEST['u']) || !isset($_REQUEST['v']))
		die('alert("Foreign request detected");');		// didn't come from us
	$originUri=$_REQUEST['q'];
	$formshort=$_REQUEST['r'];
	$state=$_REQUEST['s'];
	$transaction=$_REQUEST['t'];
	$project=$_REQUEST['u'];
	$page=$_REQUEST['v'];

	// sets up database access and $qqc, $qqu, and $qqs:
	include("setupdb.php");
	
	$au=$qqp->createFromShort($formshort,Null,$state,$transaction);
	$js=new js();
	$js->setCurrentPage($page);
	$qqi->setOutputPage($page);

	header("Pragma: No-cache");
	header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1

	$redirect=$au->processAjax($js,$originUri,$page);
	infolog("ajax","ajax processed");
	include('cleanupdb.php');			
} catch (Exception $e) {
	include('exception.php');
	echo 'alert("Server Internal Error.\n\nSorry for the inconvenience, but a server internal error has occurred.\nIt has already been logged, and we will be looking into it.");';
}
include('cleanup.php');
?>
