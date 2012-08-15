<?php if (FILEGEN != 1) die;
	// initialize data access and utility objects
	// mark the dbo as "main_only" because while it is global, it should not be used directly 
	$qq_main_only_dbo=new PDO("mysql:host={$qq['dbhostname']};dbname={$qq['dbname']}","{$qq['dbuser']}","{$qq['dbpassword']}", 
	      array(PDO::ATTR_PERSISTENT => true));
	$qq_main_only_dbo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$qqc=new crud($qq_main_only_dbo);
	$qqu=new util();

	$qqs=$qqu->state_setup('');

	if (isset($new_sitescreenpass))
		$qqs->setScreenDoorPass($new_sitescreenpass);
	else if (isset($qq['sitescreenpw']) && $qqs->getScreenDoorPass() != $qq['sitescreenpw']) {
		include("sitescreen.php");
		exit;
	}

	// saved objs: qqp   qqi   qqy   qqj
	$qqobjs=array(False,False,False,False);
	$savefile="g/{$qq['project']}_data.sav";
	if (False !== ($filetime=@filemtime($savefile))) {
		$qqobjs=unserialize(file_get_contents($savefile));
	}	
	list($qqp,$qqi,$qqy,$qqj)=$qqobjs;
	// see if pagedefs need to be built
	$pagedeffile="p/{$qq['project']}/pages.txt";
	if (False === $qqp || !$qqp->checkDependencies($filetime)) {
		$qqs->resetAuth();
		$qqp=new pagedef($pagedeffile);
		// remaking pagedef forces remake of id structures, style files, and js files:
		$qqi=$qqy=$qqj=False;
	}
	if (False === $qqi) {
		$qqs->resetAuth();
		$qqi=new idstore($qqp);
		$qqs->initAuth();
		$qqi->setClean();
	} else
		$qqs->initAuth();	
	
?>
