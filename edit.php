<?php if (FILEGEN == 1) die;
// edit window for filegen

if (!isset($_REQUEST['q']) || !isset($_REQUEST['s']) || !isset($_REQUEST['t']))
	die;		// didn't come from us

$qq=array();
$qq['request']=$_REQUEST['q'];

$qq['editsplittype']=$_REQUEST['t'];
$qq['editsplitmode']=$_REQUEST['s'];

//if (isset($_REQUEST['proj']))
//	$qq['project']=$_REQUEST['proj'];
	
if ($qq['editsplittype'] == 2) {	//edit_main::DISPLAY_PREVIEW (haven't called setup.php yet)
	$qq['watchid']=$_REQUEST['w'];	
	// this is a viewing window.  do page display
	include ('main.php');
	exit;
}

// set up class autoloader and trace functions.  sets $qq: 	

$qq['tracefile']= ($qq['editsplittype'] == 4) ? 'm/ajaxfile.txt' : 'm/editfile.txt';	//edit_MAIN::DISPLAY_AJAXONLY (havent called setup.php yet)

include('setup.php');
try {
	// sets up database access and $qq objects
	include('setupdb.php');
	// NOTE:  stylegen and js are NOT automatically initialized for edit calls

	list($pagename,$pageextra,$formpage)=$qqp->parseRequest($qq['request']);	
	$qqe=new edit_main();
	
	if ($qq['editsplittype'] == edit_main::DISPLAY_SPLITPARENT) {
		if (!isset($_REQUEST['au']))
			throw new exception("required au shortname for editing not set");
		$qqe->outputHeader();
		echo '</head><frameset cols="300,*">';
		$type=edit_main::DISPLAY_EDIT;
		$project=($qq['production']) ? '' : "&u={$qq['project']}";
		echo "<frame name=\"modaledit\" src=\"{$qq['srcbase']}edit.php?&q={$qq['request']}&s={$qq['editsplitmode']}&t=$type$project&au={$_REQUEST['au']}&name={$_REQUEST['name']}&selector={$_REQUEST['selector']}\">";
		$type=edit_main::DISPLAY_PREVIEW;
		// watcher child is initialized here as id #1 watcher of the modal edit window
		$watchid="1|{$_REQUEST['au']}|{$qqs->getStateCookie()}";
		echo "<frame name=\"modalwatch\" src=\"{$qq['srcbase']}edit.php?&q={$qq['request']}&s={$qq['editsplitmode']}&t=$type$project&w=$watchid\">";
		echo '<noframes><p>In order to perform this edit operation, you <em>must</em> use a browser that is capable of frames.  The one you are currently using is not.</p></noframes>';
		echo '</frameset>';
		echo '</html>';
	} else if ($qq['editsplittype'] == edit_main::DISPLAY_DOEDIT) {
		if ($_REQUEST['submit'] == "Ok") {
			// perform changes
			$au=$qqp->createFromShort($_REQUEST['au'],Null,'',0);
			if (($eblk=$au->getEditBlock($pageextra)) === False)
				throw new exception("au specified to edit that doesn't support editing:  {$au->getLongName()}");
			$eblk->makeChanges();
		}
		// either we go back to displaying a basic display window or the main edit window
		if ($qq['editsplitmode'] == edit_main::MODE_MODALEDIT) {
			// go back to display window
			$qqe->cleanupModalEdit($_REQUEST['au'],1);
			header("location:{$qq['request']}");
		} else {
			// display main edit form
			echo '<p>Edit Window Master Display</p>';
		}
	} else if ($qq['editsplittype'] == edit_main::DISPLAY_AJAXONLY) {
		if ($qq['editsplitmode'] == edit_main::MODE_AJAXEDITOR) {
			if (!isset($_REQUEST['x']))
				throw new exception("xnum required for ajax but not set");
			if (!isset($_REQUEST['wx']))
				throw new exception("xnum required for ajax but not set");
			if (!isset($_REQUEST['r']))
				throw new exception("required au shortname for ajax not set");
			if (!isset($_REQUEST['v']))
				throw new exception("required update data for ajax not set");	
			$aushort=$_REQUEST['r'];	
			$au=$qqp->createFromShort($aushort,Null,'',0);
			$updatedata= get_magic_quotes_gpc() ? stripslashes($_REQUEST['v']) : $_REQUEST['v'];
			$qqe->editorAjax($au,$aushort,$updatedata,$_REQUEST['x'],$_REQUEST['wx'],$pageextra);
		} else if ($qq['editsplitmode'] == edit_main::MODE_AJAXWATCHER) {
			// modal watchers are all identified by either watching a modal window or the one modeless window.		
			if (!isset($_REQUEST['x']))
				throw new exception("xnum required for ajax but not set");
			if (!isset($_REQUEST['w']))
				throw new exception("required watcherid for ajax not set");
			$qqe->watcherAjax($_REQUEST['w'],$qq['request'],$_REQUEST['x'],$pageextra);
		} else if ($qq['editsplitmode'] == edit_main::MODE_TOGGLEPREVIEW) {
			if (!isset($_REQUEST['r']))
				throw new exception("required au shortname for ajax not set");
			if (!isset($_REQUEST['v']))
				throw new exception("required update data for ajax not set");	
			$qqe->togglePreview($_REQUEST['r'],$_REQUEST['v']);
		} else
			throw new exception("illegal split mode:  {$qq['editsplitmode']}");
	} else if ($qq['editsplittype'] == edit_main::DISPLAY_EDIT) {
		// this is an edit window.  find out which type
		if ($qq['editsplitmode'] == edit_main::MODE_MODALEDIT) {

			// initialize edit block if necessary
			if (!isset($_REQUEST['au']))
				throw new exception("au shortname must be set to create a modal edit window");
			$aushort=$_REQUEST['au'];	
			$au=$qqp->createFromShort($aushort,Null,'',0);

			// initialize the javascript object.  this is NOT the same javascript object used for ordinary pages.
			// it only processes dynamic js.
			$qqj=new js();
			$qqj->setCurrentPage($pagename);
			
			$qqe->setupModalEdit($qqj,$au,$pageextra,$qq['request']);
			$qqe->outputHeader();

			echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"{$qq['srcbase']}m/modaledit.css\" />";
			// output javascript			
			list($bNeedBS,$bNeedBE,$files,$lines)=$qqj->getFileNames();
			foreach ($files as $file)
				echo "<script type=\"text/javascript\" src=\"{$qq['srcbase']}$file\"></script>";
			// javascript--dynamic	
			if (sizeof($lines) > 0) {
				echo "<script type=\"text/javascript\">";
				echo ($qq['production']) ? str_replace("\n","",implode("",$lines)) : "\n".implode("",$lines)."\n";
				echo "</script>";
			}	
			echo "<script type=\"text/javascript\">";
			echo ($qq['production']) ? str_replace("\n",'',implode('',$lines)) : "\n".implode('',$lines)."\n";
			echo "</script>";
			echo '</head><body>';
			
			$qqe->modalEditOutput($au,$pageextra);
			echo '</form></body></html>';
			// prevent our temporary qqj from overwriting the cache:
			unset($qqj);
		} else {
			// editor master
			echo '<p>Edit Window Master Display</p>';
		} 
	}

	include('cleanupdb.php');			
} catch (Exception $e) {
	include('exception.php');
}
include('cleanup.php');
?>
