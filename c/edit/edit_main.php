<?php if (FILEGEN != 1) die;
class edit_main {
////////////////////////////////////// edit interface class ////////////////////
/////// this is a utility class that supports the various elements of editing //

// edit window management related display type: (t=)
const DISPLAY_SPLITPARENT = 0;
const DISPLAY_EDIT = 1;
const DISPLAY_PREVIEW = 2;
const DISPLAY_DOEDIT = 3;
const DISPLAY_AJAXONLY = 4;

// modes (s=)
const MODE_NOSPLIT = 0;
const MODE_SPLITEDIT = 1;
const MODE_MODALEDIT = 2;
const MODE_AJAXEDITOR = 3;
const MODE_AJAXWATCHER = 4;
const MODE_TOGGLEPREVIEW = 5;

const MINPOLLINTERVAL = 100;
const POLLINTERVALDELTA = 200;
const MAXPOLLINTERVAL = 1300;

// owner bar related:
private $pagenames;		// names of protected pages
private $editables;		// names and info of editable objects

// modal editor related:
private $med;			// modal edit object
private $aushort;		// short name (index used to save the med in the state structure)

function __construct() {
	$this->editables=array();
}

/////////////////////////////////// owner bar (viewer side) related functions:

public function declareEditable($aushort,$longname,$description) {
	$this->editables[]=array($aushort,$longname,$description);
}

public function declareOBids($idstore,$aubody) {
	$idstore->declareHTMLlong('ownerbar_trigger');
	$idstore->declareHTMLlong('ownerbar_menu');
}

public function declareOBstyles($stylegen,$state,$aubody) {
	$stylegen->registerStyledId('ownerbar_trigger','div','ownerbar',$aubody->getLongName());
	$stylegen->registerStyledId('ownerbar_menu','div:h1,h2,ul,li,a','ownerbar',$aubody->getLongName());
}

public function initializeOB($pgen,$aubody,$watchid='') {
	global $qqi,$qqj,$qqs,$qqp,$qq;
	
	if ($watchid !== '') {
		// in a 'watch' situation, we have no ownerbar display, but a clear overlay over the window and
		// a routine to poll the site for what's changing
		if (False === ($i=strpos($watchid,'|')))
			throw new exception("illegally formatted watchid:  $watchid");
		$args=$qqj->seedArgs($aubody);
		$args['watchid']=$watchid;
		$args['requesturi']=$qq['request'];
		$args['bodyshort']=$aubody->getShortName();
		$qqj->addjs('$','edit::watchersetup',$args);
	} else {
		// 'ordinary' ownerbar setup
		// determine which privileged pages to display
		$protectedpages=$qqi->getProtectedPages();
		$this->pagenames=array();
		$arraydef=$arraysep='';
		foreach($protectedpages as $pp) {
			if ($qqs->checkAuth('view_'.$pp)) {
				list($pageid,$title,$description,$robots)=$qqp->getPageInfo($pp); 
				$this->pagenames[]=$description;
				$link=$qqi->hrefPrep($pp,True);
				$arraydef .= "$arraysep'$link'";
				$arraysep=',';			
			}
		}
		
		// determine which editable items to display
		$this->editnames=array();
		$pagename=$pgen->getPageName();
		$project= $qq['production'] ? '' : "&u={$qq['project']}";
		$lnarraydef=$lnarraysep='';
		foreach($this->editables as $editable) {
			list($aushort,$longname,$description)=$editable;
			$link=$qqi->hrefPrep("edit.php",False,'',idstore::ENCODE_NONE)."?q=".$qq['request']."&s=2&t=0&$project&au=$aushort";
			$arraydef .= "$arraysep'$link'";
			$arraysep=',';
			$lnarraydef .= "$lnarraysep'$longname'";
			$lnarraysep=',';			
		}

		// add action links
		// first one (logout):
		$formshort=$qqp->getAUShortName('au_auth_screendoor','login','');
		$link=$qqi->hrefprep("form.php",False,'',idstore::ENCODE_NONE).'?q='.urlencode($qq['request'])."&r=$formshort&s=logout&t=0$project";
		$arraydef .= "$arraysep'$link'";
		$arraysep=',';
		// (if there are more)
						
	
		$args=$qqj->seedArgs($aubody);
		$qqj->addjs('$','edit::obsetup',array_merge(array('arraydef'=>$arraydef,'lnarraydef'=>$lnarraydef),$args));
	} // if not watching

}

public function outputOB($pgen,$aubody,$watchid='') {
	global $qqi;
	if ($watchid !== '') {
		echo '<div id="ownerbar_veil" style="background-color:white;z-index:1000;position:fixed;left:0;top:0;"></div>';
	} else {
		// trigger
		echo "<div{$qqi->idcstr('ownerbar_trigger')}></div>";
		// menu
		echo "<div{$qqi->idcstr('ownerbar_menu')}>";
		echo "<h1>Owner Menu</h1>";
		
		if (sizeof($this->pagenames) > 0) {
			echo '<h2>Privileged Pages</h2><ul>';
			foreach ($this->pagenames as $page) {
				echo "<li class=\"ownerbar_link\">$page</li>";
			}
			echo '</ul>';
		}
		if (sizeof($this->editables) > 0) {
			echo '<h2>Edit Items</h2><ul>';
			foreach ($this->editables as $editable) {
				list($aushort,$longname,$description)=$editable;
				echo "<li class=\"ownerbar_link ownerbar_edit\">$description</li>";
			}
			echo '</ul>';
		}
		
		echo '<h2>Actions</h2><ul>';
		echo '<li class="ownerbar_link">Logout</li>';
		 
		echo "</div>";
	} // if not 'watching'	
}

public function watcherAjax($watcherid,$requesturi,$xnum,$pageextra) {
	global $qqu,$qqs;
	
	list($id,$aushort,$cookie)=explode('|',$watcherid,3);
	$state=$qqu->state_setup($cookie);
	if (!is_object($state) || $state->getStateCookie() != $cookie)
		throw new exception("could not initialize state with cookie $cookie");

	if (False === ($this->ed=$qqs->getEditData($aushort))) {
		global $qqp;
		
		$au=$qqp->createFromShort($aushort,Null,'',0);
		if (($eblk=$au->getEditBlock($pageextra)) === False)
			throw new exception("au specified to edit that doesn't support editing:  {$au->getLongName()}");
		$this->ed=$this->initEditBlock($state,$aushort,$eblk,$requesturi);
	}

	$this->doWatcher(&$this->ed,$id,$aushort,$xnum,False,$pageextra);
	
	// we're possibly saving to a foreign state.
	if ($state->getStateCookie() != $qqs->getStateCookie()) {
		$state->saveEditData($aushort,$this->ed);
		$state->saveState();
	} else
		$qqs->saveEditData($aushort,$this->ed);
}

private function doWatcher($ed,$id,$aushort,$xnum,$bModal,$pageextra) {	
	global $qqu,$qq,$qqp,$qqi,$qqj,$qqy,$qqs;
		
	if ($bModal) {
		$ismodal='true';
		$jsebname='top.modalwatch.fg.edit';
	} else {
		$ismodal='false';
		$jsebname='fg.edit';
	}	
	if ($ed['finished']) {
		// the editor is gone
		$this->cleanupModalEdit($aushort,$id);
		echo "window.location={$ed['requesturi']};";
		return;
	}	
	$au=$qqp->createFromShort($aushort,Null,'',0);
	if (($eblk=$au->getEditBlock($pageextra)) === False)
		throw new exception("au specified to edit that doesn't support editing:  {$au->getLongName()}");

	if (!isset($ed['watchers'][$id]))
		throw new exception("unknown watcher id $id while editing:  {$au->getLongName()}");	
	list($lastxnum,$lastval,$pollinterval,$lastbPreview)=$ed['watchers'][$id];
	
	// 'standard' edit blocks do their watcher output by processing input through the normal output routines.
	$bStandard=$eblk->isStandardOutput();
	$newval=$ed['bPreview'] ? $ed['currentval'] : $ed['originalval'];
	$bChanged=($ed['bPreview'] != $lastbPreview || $lastxnum+1 != $xnum || $newval != $lastval);
	
	if ($bChanged && $bStandard) {
		// because this is basically an output function, we must simulate the output of a page.
		// initialize the stylegen object.
		$stylefile="p/{$qq['project']}/styles.txt";
		global $filetime;
		if (False === $qqy || !$qqy->checkDependencies($filetime)) {
			$qqy=new stylegen($qq['project'],$stylefile);
			$qqj=False;
		}
		// initialize the page-based javascript object
		if (False === $qqj || $qqj->areFilesNewer($filetime)) {
			$qqj=new js($qqp,$qq['project']);
		}
			
		$pgen=new pagegen();
		$result=$pgen->setPage($qq['request'],$qqs->getRender());
		if ($result != pagegen::DISPLAY_NORMAL)
			throw new exception("page cannot be set up for output in ajax");
		
		ob_start();
		$pgen->output($au->getLongName(),'0',$newval);
		$newoutput=ob_get_clean();
	}	
		
	if ($lastxnum+1 == $xnum && $ed['bPreview'] == $lastbPreview) {
		// we can do this change incrementally
		++$lastxnum;
		++$xnum;
		echo "$jsebname.xnum=$xnum;";
		if ($bStandard && $bChanged) {
			// for standard editor blocks, get the new output and compare with the old output to determine what
			// javascript instructions to send down to update
			$pollinterval=self::MINPOLLINTERVAL;
			// get the last output so we can compare them
			ob_start();
			$pgen->output($au->getLongName(),'0',$lastval);
			$oldoutput=ob_get_clean();
			
			$newlen=strlen($newoutput);
			$oldlen=strlen($oldoutput);
			for ($i=0; $i<$newlen && $i<$oldlen && $newoutput[$i] == $oldoutput[$i]; ++$i)
				;
			if ($i == $newlen) {
				// $i cannot be $oldlen or they'd be the exact same--we tested for that above.  new is smaller than old, with something deleted from end
				$lastpos=$i-1;
				echo "$jsebname.lastval=$jsebname.lastval.substr(0,$i);";
			} else if ($i == $oldlen) {
				// concatenate new characters onto old output
				$newpiece=addslashes(substr($newoutput,$i+1));
				echo "$jsebname.lastval=$jsebname.lastval+'$newpiece';";
			} else {
				$jn=$newlen-1;
				$jo=$oldlen-1;
				while ($jn >= $i && $jo >= $i && $newoutput[$jn] == $oldoutput[$jo]) {
					$jn--;
					$jo--;
				}
				$dcount=$jo-$i-1;
				$insert=$jn >= $i ? '+"'.addslashes(substr($newoutput,$i,$jn-$i+1)).'"' : '';
				// take $i characters from beginning of old string, delete $dcount chars, then insert $insert right after $i
				$lastmatchstart=$jo+1;
				echo "$jsebname.lastval=$jsebname.lastval.substr(0,$i)$insert+$jsebname.lastval.substr($lastmatchstart);";
			}	
			$aushortname=$qqi->htmlShortFromLong($au->getLongName());
			echo "$jsebname.replace($ismodal,'$aushortname');";
		} else if ($bChanged) {
			// a non-standard editor block must implement this function to take care of incremental output:
			$newval=$eblk->incrementalOutput($lastval,$ed['currentval']);
			$pollinterval=self::MINPOLLINTERVAL;
		} else	
			$pollinterval= min($pollinterval+self::POLLINTERVALDELTA,self::MAXPOLLINTERVAL);		// no change, so no output necessary.
		// if this watcher isn't living in a window with an editor, it needs to poll on a timer:	
		if ($id != 1)	
			echo "setTimeout('fg.edit.watchit()',$pollinterval);";
	} else {
		// change cannot be done incrementally.
		$lastxnum=0;
		echo "$jsebname.xnum=1;";
		$pollinterval=10000;
		if ($id != 1)	
			echo "setTimeout('fg.edit.watchit()',$pollinterval);";
		if ($bStandard) {
			// standard editor blocks just work with output from the au output routine
			$newoutput=addslashes($newoutput);
			$aushortname=$qqi->htmlShortFromLong($au->getLongName());
			echo "$jsebname.lastval='$newoutput';";
			echo "$jsebname.replace($ismodal,'$aushortname');";
		} else
			$newval=$eblk->absoluteOutput($ed['currentval']);
	}
	$ed['watchers'][$id]=array($lastxnum,$newval,$pollinterval,$ed['bPreview']);
}


/////////////////////// editor block functions:
private function initEditBlock($state,$aushort,$eblk,$requesturi) {
	// initialize edit data
	$ed=array();
	$ed['originalval']=$eblk->getOriginalValue();
	
	$ed['originalval']=str_replace("\r",'',$ed['originalval']);

	$ed['xnum']=-2;		// forces full update
	$ed['currentval']=$ed['originalval'];
	$ed['bPreview']=True;
	// watcher array is id=>(xnum,currentval,timerincrement,lastpreviewstate) for each watcher.  Modal edit blocks automatically start with 1 watcher that is out of sync.
	$ed['watchers']=array();
	$ed['watchers'][1]=array(-2,$ed['originalval'],100,$ed['bPreview']);
	$ed['changename']='General Edit';
	$ed['requesturi']=$requesturi;
	$ed['finished']=False;
	$state->saveEditData($aushort,$ed);
	return $ed;
}


public function setupModalEdit($js,$au,$pageextra,$requesturi) {
	global $qqs;
	
	$aushort=$au->getShortName();
	if (($eblk=$au->getEditBlock($pageextra)) === False)
		throw new exception("au specified to edit that doesn't support editing:  {$au->getLongName()}");
		
	if (False === ($this->ed=$qqs->getEditData($aushort))) {
		$ed=$this->initEditBlock($qqs,$aushort,$eblk,$requesturi);
	}
	$this->ed=$ed;		
	$args=$js->seedArgs($au);
	$js->addjs('$','edit::modaleditsetup',array_merge(array('aushort'=>$aushort,'requesturi'=>$requesturi),$args));
	$eblk->js($au,$aushort,$js,$args);	
}

// set modal edit structure to die gracefully once all watchers have closed
public function cleanupModalEdit($aushort,$watchernum=0) {
	global $qqs;
	if (False !== ($ed=$qqs->getEditData($aushort))) {
		$ed['finished']=True;
		if ($watchernum != 0) {
			if (isset($ed['watchers'][$watchernum]))
				unset($ed['watchers'][$watchernum]);
		}
		if (sizeof($ed['watchers'] == 0))
			$qqs->deleteEditData($aushort);
		else
			$qqs->saveEditData($aushort,$ed);	
	}

}

// output modal edit window body text
public function modalEditOutput($au,$pageextra) {
	global $qq;
	
	$aushort=$au->getShortName();
	if (($eblk=$au->getEditBlock($pageextra)) === False)
		throw new exception("au specified to edit that doesn't support editing:  {$au->getLongName()}");

	$change=$this->ed['changename'];
	$bPreviewChecked=$this->ed['bPreview'];
	
	echo '<form method="post" target="_top" enctype="multipart/form-data" action="edit.php">';
	echo "<input type=\"hidden\" name=\"au\" value=\"{$aushort}\" />";
	if (!$qq['production'])
		echo "<input type=\"hidden\" name=\"proj\" value=\"{$qq['project']}\" />";
	echo "<input type=\"hidden\" name=\"q\" value=\"{$qq['request']}\" />";
	$mode=self::MODE_MODALEDIT;
	echo "<input type=\"hidden\" name=\"s\" value=\"$mode\" />";
	$requesttype=self::DISPLAY_DOEDIT;
	echo "<input type=\"hidden\" name=\"t\" value=\"{$requesttype}\" />";
	echo "<p style=\"line-height:30px\"><span style=\"white-space:nowrap\"><label>Change: <input type=\"text\" name=\"change\" value=\"$change\" size=\"20\" maxlength=\"40\" /></label></span>&nbsp&nbsp ";
	$checked=$bPreviewChecked ? " checked" : '';			
	echo "<span style=\"white-space:nowrap\"><label><input type=\"checkbox\" name=\"preview\" value=\"1\"$checked onclick=\"fg.edit.clickpreview(this)\" />Preview</label></span>";
	echo "&nbsp;&nbsp;&nbsp; ";
	echo "<span style=\"white-space:nowrap\"><input type=\"submit\" name=\"submit\" value=\"Ok\" />";
	echo "&nbsp;&nbsp;&nbsp;";
	echo "<input type=\"submit\" name=\"submit\" value=\"Cancel\" /></span>";
	echo "</p>";
	echo "<hr />";
	$eblk->formOutput($au,$this->ed['currentval']);
}

public function editorAjax($au,$aushort,$updatedata,$xnum,$wxnum,$pageextra) {
	global $qqs;
	
	if (False === ($ed=$qqs->getEditData($aushort)))
		throw new exception("expected edit block not found");
	if (($eblk=$au->getEditBlock($pageextra)) === False)
		throw new exception("au specified to edit that doesn't support editing:  {$au->getLongName()}");
		
	$len=strlen($updatedata);	
		
	if ($xnum == $ed['xnum']+1) {
		// incremental change
		$ed['currentval']=$eblk->applyIncrementalChange($ed['currentval'],$updatedata);
		++$ed['xnum'];
		++$xnum;
		echo "fg.edit.xnum=$xnum;";
		if (isset($ed['watchers'][1])) {
			// there is a modal watcher.  don't make it do a separate request for watch data
			$this->doWatcher(&$ed,1,$aushort,$wxnum,True,$pageextra);	
		}
	} else if ($xnum == 0) {
		// absolute update
		$ed['currentval']=$eblk->applyAbsoluteChange($updatedata);
		if (isset($ed['watchers'][1])) {
			// there is a modal watcher.  don't make it do a separate request for watch data
			$this->doWatcher(&$ed,1,$aushort,$wxnum,'parent.modalwatch.fg.edit',$pageextra);	
		}
		echo 'fg.edit.xnum=1;';
		$ed['xnum']=0;
	} else {
		// unsynchronized incremental update.  Ignore it and request an absolute one
		$ed['xnum']=-2;
		echo 'fg.edit.xnum=0;fg.edit.trigger();';
	}
	$qqs->saveEditData($aushort,$ed);
}

// toggles the live preview 
public function togglePreview($aushort,$bPreview) {
	global $qqs;
	
	if (False === ($ed=$qqs->getEditData($aushort)))
		throw new exception("expected edit block not found");
	$ed['bPreview']=$bPreview;
	
	$qqs->saveEditData($aushort,$ed);
	echo 'parent.modalwatch.fg.edit.watchit();';
}

////////////////////// edit window management functions:

public function outputHeader() {
	global $qq;
	
	echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01//EN\" \"http://www.w3.org/TR/html4/strict.dtd\"><html>";
	// do the head
	echo "<head>";
	echo "<title>{$this->getTitle()}</title>";
	echo '<meta http-equiv="Content-Type" content="text/xml; charset=windows-1252" />';
	echo '<meta http-equiv="imagetoolbar" content="no" />';
	echo '<meta name="MSSmartTagsPreventParsing" content="TRUE" />';
	echo '<meta http-equiv="EXPIRES" content="0">';
	echo '<meta http-equiv="CACHE-CONTROL" content="NO-CACHE">';
	echo '<meta name="robots" content="noindex, nofollow">';
}

public function getTitle() {
	return "Edit";
}


////////////////////////////////////// end of class definition /////////////////
} ?>
