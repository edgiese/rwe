<?php if (FILEGEN != 1) die;
///// Access Unit definition file for form wizard.  This class is usually subclassed - see protected functions below
class au_wizard extends au_base {
////////////////////////////////////////////////////////////////////////////////
private $state;

function __construct($tag,$parent,$initstring,$state="",$data=0) {
	parent::__construct($tag,$parent,$initstring);
	$this->state=$state;
	$this->transaction=(int)$data;
	dbg('state='.$this->state);
}
public function canTakeChildren() {return True;}

public function declareids($idstore,$state) {
	if ($state == '')
		// link into the wizard
		$idstore->declareHTMLid($this,$this->bAutoLock);
	else {
		if ($state != 'finished')
			$this->makeform($state)->declareids($idstore);
		// proceed and back buttons
		$idstore->declareHTMLid($this,$this->bAutoLock,'next');
		$idstore->declareHTMLid($this,$this->bAutoLock,'prev');
		$idstore->declareHTMLid($this,$this->bAutoLock,'finished');
	}	
}

public function declarestyles($stylegen,$state) {
	if ($state == '')
		// link into the wizard
		$stylegen->registerStyledId($this->longname,"a",'wizardinlink',$this->getParent()->htmlContainerId());
	else {
		if ($state != 'finished') {
			$this->makeform($state)->declarestyles($stylegen,$this->longname);
			// proceed and back buttons
			$stylegen->registerStyledId($this->longname.'_next','a','wizardfwdlink',$this->getParent()->htmlContainerId());
			$stylegen->registerStyledId($this->longname.'_prev','a','wizardbacklink',$this->getParent()->htmlContainerId());
		} else {
			$stylegen->registerStyledId($this->longname.'_finished','a','wizardfinishedlink',$this->getParent()->htmlContainerId());
		}	
	}	
	$this->extraStyles($stylegen,$state);		
}

public function updateAllForms($forms) {
	if (!$qqs->sessionDataExists(get_class($this)))
		throw new exception('attempting to update wizard form data before forms established');
	$wdata=$qqs->getSessionData(get_class($this));
	$wdata['forms']=$forms;
	$qqs->setSessionData(get_class($this),$wdata);
}

public function processVars($originUri) {
	global $qq,$qqs;
	
	// any transaction-based forms need to be stated
	if (!$qqs->isStated())
		return message::redirectString(message::MISSINGCOOKIE);
	$state=$this->state;
	
	$states=$this->enumStates();
	if ($state == '' || !$qqs->sessionDataExists(get_class($this))) {
		// initialize session data for this wizard.  Note that wizards are not transaction based
		$forms=array();
		foreach ($states as $st)
			$forms[$st]=$this->makeform($st);
		$wdata=array('originUri'=>$originUri,'progress'=>$states[0],'forms'=>$forms);
		ddbg($wdata);
		$qqs->setSessionData(get_class($this),$wdata);
	} else
		$wdata=$qqs->getSessionData(get_class($this));

	if (isset($_REQUEST['backbutton'])) {
		if ($_REQUEST['backbutton'] == '' || False === array_search($_REQUEST['backbutton'],$states))
			return $wdata['originUri'];		// off the back end
		return llform::redirectString($_REQUEST['backbutton'],0);
	}		

	// entering form (or reentering) moves to last possible state
	if ($state=='') {
		return llform::redirectString($wdata['progress'],0);
	}	
	
	// finished with wizard
	if ($state=='finished') {
		$qqs->clearSessionData(get_class($this));
		dbg("originuri={$wdata['originUri']}");
		return $wdata['originUri'];
	}	
	
	$stateIx=array_search($state,$states);
	if (False === $stateIx)
		throw new exception("illegal state for wizard: $state");
	// make certain that we are not trying to navigate past the end of progress.  can happen with back button once wizard done.
	$maxStateIx=array_search($wdata['progress'],$states);
	if ($stateIx > $maxStateIx)
		$state=$wdata['progress'];	
		
	// if we got here, we've gotten form data
	$form=$wdata['forms'][$state];
	$form->setFieldsFromRequests();
	
	$newstate=$this->validate($wdata['forms'],$state);
	if ($newstate[0] == '/') {
		// we are finishing up here.
		$retval=($newstate[0] == '/') ? $originUri : substr($newstate,1);
		$qqs->clearSessionData(get_class($this));
		return $retval;
	}
	$wdata['progress']=$newstate;
	$qqs->setSessionData(get_class($this),$wdata);
	return llform::redirectString($newstate,0);
}

// does output of this plus all children
public function output($pgen,$brotherid) {
	global $qqs,$qqi,$qq;
	
	$state=$this->state;
	if ($state == '') {
		// link only
		$text=$this->getButtonText('');
		$href=llform::buildFormURL($this,urlencode($qq['request']),'',0,array());
		
		// debugging
		if ($qqs->sessionDataExists(get_class($this)))
			$qqs->clearSessionData(get_class($this));
		
		echo "<a{$qqi->idcstr($this->longname)} href=\"$href\">$text</a>";
		return;
	}
	
	for ($au=$this->getFirstChild(); $au != Null; $au=$au->getNextSibling()) {
		$tag=$au->getTag();
		$need=$state.'_top';
		if (0 === strpos($tag,$need))
			$au->output($pgen,$brotherid);
	}	
	$wdata=$qqs->getSessionData(get_class($this));
	
	if ($state != 'finished') {
		$form=$wdata['forms'][$state];
		$qqi->lockHTMLid($form->longname());
		$this->outputForm($state,$form);

		// bottom text - note, not avaialable on 'finished' page
		for ($au=$this->getFirstChild(); $au != Null; $au=$au->getNextSibling()) {
			$tag=$au->getTag();
			$need=$state.'_bottom';
			if (0 === strpos($tag,$need))
				$au->output($pgen,$brotherid);
		}

		// previous button		
		$states=$this->enumStates();
		$curStateIx=array_search($state,$states);
		$prevState=($curStateIx > 0) ? $states[$curStateIx-1] : '';
		$textprev=$this->getButtonText($state,False);
		$hrefprev=llform::buildFormURL($this,urlencode($wdata['originUri']),$state,0,array('backbutton'=>$prevState));

		// next button - simulate form submit
		$textnext=$this->getButtonText($state,True);
		$hrefnext=llform::buildFormURL($this,urlencode($wdata['originUri']),$state,0,array());
		echo "<table style=\"border:none; width:100%; margin:auto;\"><tr><td style=\"text-align:left\"><a{$qqi->idcstr($this->longname.'_prev')} href=\"$hrefprev\">$textprev</a></td><td style=\"text-align:right\"><a{$qqi->idcstr($this->longname.'_next')} href=\"$hrefnext\" onclick=\"myform=document.getElementById('{$qqi->htmlShortFromLong($form->longname())}'); myform.submit(); return false;\">$textnext</a></td></tr></table>";
		
	} else {
		$text=$this->getButtonText('finished');
		$href=llform::buildFormURL($this,urlencode($wdata['originUri']),'finished',0,array());
		echo "<p><a{$qqi->idcstr($this->longname.'_finished')} href=\"$href\">$text</a></p>";
	}

	
}

///////////////////////////////////////////////////////// protected functions for subclassing:
protected function extraStyles($stylegen,$state) {
}

protected function enumStates() {
	return array('init','progress2','progress3');
}

protected function makeform($state) {
	$form=new llform($this);
	switch ($state) {
		case 'init':
			$form->addFieldset("aboutyou","About You");
			$form->addControl("aboutyou",new ll_edit($form,"name",28,40,"Your name",False));
			$form->addControl("aboutyou",new ll_button($form,"Ok","Continue"));
		break;
		
		case 'progress2':
			$form->addFieldset("landscapeinterest","Your interest in landscaping");
			$form->addControl("landscapeinterest",new ll_textarea($form,"otherinterest",25,2,""));
			$form->addControl("landscapeinterest",new ll_button($form,"Ok","Continue"));
		break;

		case 'progress3':
			$form->addFieldset("getback","How can we get back to you?");
			$form->addControl("getback",new ll_edit($form,"phone",20,20,"Phone Number"));
			$form->addControl("getback",new ll_button($form,"Ok","Continue"));
		break;
		default:
			throw new exception("Illegal wizard form state $state");
		break;
	}
	return $form;
}

// checks form and updates state
protected function validate($formarray,$state) {
	$form=$formarray[$state];
	if ($state == 'init') {
		$retval='progress2';
	} else if ($state == 'progress2') {
		if ($form->getValue("otherinterest") != 'everything') {
			$form->getControl('otherinterest')->setError("You can only answer 'everything'.");
			$retval='progress2';
		} else {
			$form->getControl('otherinterest')->setError('');
			$retval='progress3';
		}
	} else if ($state == 'progress3') {
		$retval='finished';
	}
	
	return $retval;   
}

protected function getButtonText($state,$bNext=True) {
	if ($state == '') {
		return "Start Wizard";
	}	
	if ($state == 'finished')
		return "All Done!";	
	return ($bNext ? "Next" : "Previous");	
}

protected function outputForm($state,$form) {
	echo $form->getDumpStyleFormOutput($state);
}


/////// end of AU definition ///////////////////////////////////////////////////
}?>
