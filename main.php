<?php
 if (defined('FILEGEN')) die;
// main entry point for all filegen display pages

// set up class autoloader and trace functions.  sets $qq: 	
include('setup.php');

try {
	// sets up database access and $qq objects
	include('setupdb.php');

	// $filetime set in setupdb.php
	// initialize the stylegen object and the id object.
	$stylefile="p/{$qq['project']}/styles.txt";
	if (False === $qqy || False === $qqi || !$qqy->checkDependencies($filetime)) {
		$qqy=new stylegen($qq['project'],$stylefile);
		$qqs->resetAuth();
		$qqi=new idstore($qqp);
		$qqi->initIds();
		$qqs->initAuth();
		$qqj=False;
	}
	// initialize the page-based javascript object
	if ($qqj === False || $qqj->areFilesNewer($filetime)) {
		$qqj=new js($qqp,$qq['project']);
	}

	$pgen=new pagegen();
	switch ($result=$pgen->setPage($qq['request'],$qqs->getRender())) {
		case pagegen::DISPLAY_404:
			infolog('exception',"404 location:$result");
			readfile("m/404.htm");
		break;
		
		case pagegen::DISPLAY_UNAUTHORIZED:
			$loginformname=isset($qq['loginformname']) ? $qq['loginformname'] : 'form/loginform/unauthorized';
			$loginformname=$qqp->checkAndNormalizePage($loginformname);
			list($formstring,$aushort,$state)=explode('/',$loginformname,3);
			$request=urlencode($qq['request']);
			$loginform="{$qq['srcbase']}form.php?s=$state&t=0&q=$request&r=$aushort";
			if (!$qq['production'])
				$loginform .="&u={$qq['project']}";
			header("location:$loginform");
		break;
		
		case pagegen::DISPLAY_NORMAL:
			$qqs->logPage($pgen->getPageName(),$pgen->getPageExtra());	
			header("Status: 200");
			header("HTTP/1.1 200 OK");
			header("Pragma: No-cache");
			header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
			header("Expires: 0");
			if ($qq['production'])
				ob_start();
			$pgen->output();
			if ($qq['production'])
				ob_flush();
		break;
		
		default:
			infolog('exception',"location:$result");
			header("location:$result");
		break;
	}
	include('cleanupdb.php');			
} catch (Exception $e) {
	include('exception.php');
}
include('cleanup.php');
?>
