<?php if (FILEGEN != 1) die;
///// Access Unit definition file for low-volume visitor request forms
class au_visitorrequest extends au_base {
////////////////////////////////////////////////////////////////////////////////
private $state;
private $data;

const EMAIL_NONE = 0;
const EMAIL_SINGLE = 1;
const EMAIL_VERIFIED = 2;

const ADDRESS_NONE = 0;
const ADDRESS_USA = 1;
const ADDRESS_USAINTERNATIONAL = 2;

const EXTRA_CHECKBOX = 'C';
const EXTRA_RADIOITEM = 'R';
const EXTRA_EDIT = 'E';
const EXTRA_TEXT = 'T';
const EXTRA_CREDITCARD = '$';

// data to drive display of form:
private $email=self::EMAIL_NONE;
private $mail=self::ADDRESS_NONE;
private $nTelephone=0;
private $bGetTelephoneType=False;
private $bEncoded=False;	// if TRUE, this form has credit card info and needs to be encoded

private $actionname;		// name of this action, to distinguish from other forms
private $actiondesc;		// description of this action for messages ..?
private $submittext;		// text for 'submit' button
private $required;			// array of field names and whether they are required or not

private $edfields;
private $bWide;
private $notifyEmail;

			
function __construct($tag,$parent,$initstring,$state="",$data=0) {
	parent::__construct($tag,$parent,$initstring);
	$this->state=$state;
	$this->transaction=(int)$data;
	
	$initdata=explode('|',$initstring);
	if (sizeof($initdata) != 4)
		throw new exception("visitor request form au $tag has illegal initstring.  should be [N or W][email notify]|userflags|action|[data], instead is $initstring");
	
	// parse overall options
	$widthoption=substr($initdata[0],0,1);
	if ($widthoption == 'N')
		$this->bWide=False;
	else if ($widthoption == 'W')
		$this->bWide=True;
	else
		throw new exception("visitor request form must have width option of N or W, has $widthoption");
	
	$this->notifyEmail=substr($initdata[0],1);
	
	// parse user flags
	$userflagdata=explode(';',strtoupper($initdata[1]));

	foreach ($userflagdata as $ufd) {
		$ufd=trim($ufd);
		$type=substr($ufd,0,1);
		$ufd=substr($ufd,1);
		$nRequired=0;
		while ($type == '*') {
			++$nRequired;
			$type=substr($ufd,0,1);
			$ufd=substr($ufd,1);
		}
		switch ($type) {
			case 'E';	// email
				if ($ufd == '1')
					$this->email=self::EMAIL_SINGLE;
				else if ($ufd == '2')		
					$this->email=self::EMAIL_VERIFIED;
				else		
					throw new exception("visitor request form au $tag has illegal email code.  initstring: $initstring");
				$this->required['email']=$nRequired;	
			break;
			case 'A';	// snail mail
				if ($ufd == '0')
					$this->mail=self::ADDRESS_NONE;
				else if ($ufd == 'A')
					$this->mail=self::ADDRESS_USA;
				else if ($ufd == 'I')		
					$this->mail=self::ADDRESS_USAINTERNATIONAL;
				else		
					throw new exception("visitor request form au $tag has illegal mail code.  initstring: $initstring");
				$this->required['mail']=$nRequired;	
			break;
			case 'T';	// telephone
				if (substr($ufd,-1) == 'T') {
					$this->bGetTelephoneType=True;
					$ufd=substr($ufd,0,-1);
				}
				if (!is_numeric($ufd))
					throw new exception("visitor request form au $tag has illegal telephone count.  initstring: $initstring");
				$this->nTelephone=(int)$ufd;	
				$this->required['telephone']=$nRequired;	
			break;
			default;
				throw new exception("visitor request form au $tag has illegal user flag data.  initstring: $initstring");
			break;
		}
	} // for each user flag

	// parse action description
	$actiondescription=explode(';',trim($initdata[2]));
	if (sizeof($actiondescription) != 3)
		throw new exception("visitor request form au $tag has illegal action description.  initstring: $initstring");
	list($this->actionname,$this->actiondesc,$this->submittext)=$actiondescription;
	
	// parse extra data fields
	$extradata=explode(';',$initdata[3]);
	$this->edfields=array();
	foreach ($extradata as $ed) {
		$ed=trim($ed);
		if ($ed == '')
			continue;
		$nRequired=0;
		while (substr($ed,0,1) == '*') {
			++$nRequired;
			$ed=substr($ed,1);
		}
		if (False === strpos($ed,':'))
			throw new exception("visitor request form au $tag has illegal extra field specification $ed.  initstring: $initstring");
		list($ftag,$fdata)=explode(':',$ed);
		$this->required[$ftag]=$nRequired;
		$ftype=substr($fdata,0,1);
		$fdata=substr($fdata,1);
		switch ($ftype) {
			case self::EXTRA_CHECKBOX:
				//  tag:C*Checked Item Text   OR  tag:Unchecked Item Text
				//  data(Item Text, bChecked)
				if (substr($fdata,0,1) == '*') {
					$data=array(substr($fdata,1),True);
				} else {
					$data=array($fdata,False);
				}	
			break;
			case self::EXTRA_RADIOITEM:
				// tag:R*Checked Radio Item/group  OR tag:RUnchecked Radio Item/group
				// data(Item Text, group, bChecked)
				if (False === ($i=strpos($fdata,'/')) || $i == strlen($fdata)-1 )
					throw new exception("visitor request form au $tag has illegal extra radioitem specification $fdata.  initstring: $initstring");
				if (substr($fdata,0,1) == '*') {
					$data=array(substr($fdata,1,$i-1),substr($fdata,$i+1),True);
				} else {
					$data=array(substr($fdata,0,$i),substr($fdata,$i+1),False);
				}	
			break;
			case self::EXTRA_EDIT:
				// tag:E30,50,prompt[,initial text]  is an edit control 30 chars wide accepting up to 50 chars
				// data(width, maxchars, Item Text)
				$data=explode(',',$fdata,4);
				if (sizeof($data) < 4) {
					if (sizeof($data) < 3)
						throw new exception("visitor request form au $tag has illegal extra edit specification $fdata.  initstring: $initstring");
					$data[3]='';	
				}
				$data[0]=(int)$data[0];
				$data[1]=(int)$data[1];
			break;
			case self::EXTRA_TEXT:
				// tag:T30,5,prompt,[,initial text]  is a text area 30 chars wide by 5 rows
				// data(width, height, Item Text)
				$data=explode(',',$fdata,4);
				if (sizeof($data) < 4) {
					if (sizeof($data) < 3)
						throw new exception("visitor request form au $tag has illegal extra text specification $fdata.  initstring: $initstring");
					$data[3]='';	
				}
				$data[0]=(int)$data[0];
				$data[1]=(int)$data[1];
			break;
			case self::EXTRA_CREDITCARD:
				// tag:$
				$data=array();		// not used
				$this->bEncoded=True; 
			break;
			default:
				throw new exception("visitor request form au $tag has unknown extra field type $ftype.  initstring: $initstring");
			break;
		}
		$this->edfields[]=array($ftag,$ftype,$data);	
	} // loop for all extra data fields
}

private function setupFormData($ctrl,$tag,&$formdata,$default='') {
	global $qqi;
	
	if (isset($formdata[$tag]))
		$ctrl->setValue($formdata[$tag]);
	else if ($default != '')
		$ctrl->setValue($default);
	if (isset($formdata['*'.$tag])) {
		$formdata['*'.$tag]="<p{$qqi->cstr('visitorrequesterror')}>{$formdata['*'.$tag]}</p>";
	} else		
		$formdata['*'.$tag]='';
}

// returns form and form formatting string
private function makeform($bDraw=False,$formdata=array()) {
	global $qqi;
	
	$form=new llform($this);
	$form->addFieldset('d','');
	$w=$this->bWide;

	$bInitDefaults=($bDraw == True && sizeof($formdata) == 0);

	// don't call cstr before class is registered:
	$cstr= $bDraw ? $qqi->cstr('visitorrequest') : '';
	
	// request title
	if ('' != trim($this->actiondesc)) {
		$f="<h1$cstr>{$this->actiondesc}</h1><br />";
		$f .= $w ? "<table{$cstr}>" : '';
	}	
	
	// add personal information
	$form->addControl('d',$ctrl=new ll_edit($form,"name",28,40,'',False));
	$this->setupFormData($ctrl,'name',$formdata);
	$f .= $w ? "<tr><td$cstr>* Your name:</td><td$cstr>{$formdata['*name']}<<name>></td></tr>" : "* Your Name:<br />{$formdata['*name']}<<name>><br />";
	if ($this->email != self::EMAIL_NONE) {
		$form->addControl('d',$ctrl=new ll_edit($form,"email",28,40,'',False));
		$this->setupFormData($ctrl,'email',$formdata);
		$asterisk= $this->required['email'] ? '* ' : '';
		$f .= $w ? "<tr><td$cstr>{$asterisk}Email Address:</td><td$cstr>{$formdata['*email']}<<email>>" : "{$asterisk}Email Address:<br />{$formdata['*email']}<<email>><br />";
		if ($this->email == self::EMAIL_VERIFIED) {
			$form->addControl('d',$ctrl=new ll_edit($form,"email2",28,40,'',False));
			$this->setupFormData($ctrl,'email2',$formdata);
			$f .= "{$asterisk}Verify email:<br />{$formdata['*email2']}<<email2>>";
		}
		$f .= $w ? '</td></tr>' : '<br />';
	}
	if ($this->mail != self::ADDRESS_NONE) {
		$asterisk= $this->required['mail'] ? '* Address:' : 'Address (optional):';
		$f .= $w ? "<tr><td$cstr>{$asterisk}</td><td$cstr>" : "{$asterisk}<br />";
		if ($this->mail == self::ADDRESS_USA) {
			$form->addControl('d',$ctrl=new ll_edit($form,"line1",28,40,'',False));
			$this->setupFormData($ctrl,'line1',$formdata);
			$form->addControl('d',$ctrl=new ll_edit($form,"line2",28,40,'',False));
			$this->setupFormData($ctrl,'line2',$formdata);
			$form->addControl('d',$ctrl=new ll_edit($form,"city",13,40,'',False));
			$this->setupFormData($ctrl,'city',$formdata);
			$dd=new ll_dropdown($form,'state2');
			if ($this->bWide)
				$dd->setOptionArray(data_address::getStates(True));
			else
				$dd->setOptionDisplayVal(data_address::getStates(False));
			$form->addControl('d',$dd);
			$this->setupFormData($dd,'state2',$formdata);
			$form->addControl('d',$ctrl=new ll_edit($form,"zip",10,10,'',False));
			$this->setupFormData($ctrl,'zip',$formdata);
			
			$f .= "{$formdata['*line1']}<<line1>><br />{$formdata['*line2']}<<line2>><br />{$formdata['*city']}{$formdata['*state2']}{$formdata['*zip']}<table{$cstr}><tr><td$cstr>City</td><td$cstr>State</td><td$cstr>ZIP</td></tr><tr><td$cstr><<city>></td><td$cstr><<state2>></td><td$cstr><<zip>></td></tr></table>";
			$form->setExtraValue('country','USA');
		} else {
			// international addresses go here
		}
		$f .= $w ? '</td></tr>' : '<br />';
	}
	
	for ($i=0; $i<$this->nTelephone; ++$i) {
		$extra = ($i == 0) ? '' : 'Alternate ';
		$asterisk= ($this->required['telephone'] > $i) ? '* ' : '';
		$form->addControl('d',$ctrl=new ll_edit($form,"phone{$i}",11,15,'',False));
		$this->setupFormData($ctrl,"phone{$i}",$formdata);
		$phonemsg=$formdata["*phone{$i}"];
		$f .= $w ? "<tr><td$cstr>{$asterisk}{$extra}Telephone:</td><td$cstr>$phonemsg<<phone{$i}>>" : "{$asterisk}{$extra}Telephone:<br />$phonemsg<<phone{$i}>>";
		if ($this->bGetTelephoneType) {
			$f .= "<<type{$i}>>";
			$rg=new ll_radiogroup($form,"type{$i}",'radio',' ');
			$rg->addOption('home','Home');
			$rg->addOption('work','Work');
			$rg->addOption('cell','Cell');
			$form->addControl('d',$rg);
			$this->setupFormData($rg,"type{$i}",$formdata);
		}	
		$f .= $w ? '</td></tr>' : '<br />';
	}
	
	$rgs=array();
	// add extra fields
	foreach ($this->edfields as $ed) {
		list($ftag,$ftype,$data)=$ed;
		switch ($ftype) {
			case self::EXTRA_CHECKBOX:
				//  data(Item Text, bChecked)
				$f .= $w ? "<tr><td$cstr>&nbsp</td><td$cstr><<$ftag>></td><tr>" : "<<$ftag>><br />";
				$form->addControl('d',$cb=new ll_checkbox($form,$ftag,$data[0]));
				if ($bInitDefaults) {
					if ($data[1])
						$cb->setValue(True);
				} else {
					if (isset($formdata[$ftag]) && $formdata[$ftag] != '')
						$cb->setValue(True);						
				}
			break;
			case self::EXTRA_RADIOITEM:
				// data(Item Text, group, bChecked)
				if (!isset($rgs[$data[1]])) {
					$rgs[$data[1]]=new ll_radiogroup($form,$data[1]);
					$f .= $w ? "<tr><td$cstr>&nbsp</td><td$cstr><<{$data[1]}>></td><tr>" : "<br /><<{$data[1]}>><br /><br />";
				}	
				$rgs[$data[1]]->addOption($ftag,$data[0]);
				if ($bInitDefaults) {
					if ($data[2])
						$rgs[$data[1]]->setValue($ftag);
				} else {
					if (isset($formdata[$data[1]]))
						$rgs[$data[1]]->setValue($formdata[$data[1]]);						
				}
			break;
			case self::EXTRA_EDIT:
				// data(width, maxchars, prompt, Item Text)
				$asterisk= $this->required[$ftag] ? '* ' : '';
				$form->addControl('d',$ctrl=new ll_edit($form,$ftag,$data[0],$data[1]));
				if ($bInitDefaults)
					$ctrl->setValue($data[3]);
				$this->setupFormData($ctrl,$ftag,$formdata);
				$extramsg=$formdata["*$ftag"];
				$f .= $w ? "<tr><td$cstr>$asterisk{$data[2]}</td><td$cstr>$extramsg<<$ftag>></td><tr>" : "$asterisk{$data[2]}<br />$extramsg<<$ftag>><br />";
			break;
			case self::EXTRA_TEXT:
				// data(width, height, prompt, Item Text)
				// data(width, maxchars, prompt, Item Text)
				$asterisk= $this->required[$ftag] ? '* ' : '';
				$form->addControl('d',$ta=new ll_textarea($form,$ftag,$data[0],$data[1]));
				if ($bInitDefaults)
					$ta->setValue($data[3]);
				$this->setupFormData($ta,$ftag,$formdata);
				$extramsg=$formdata["*$ftag"];
				$f .= $w ? "<tr><td$cstr>$asterisk{$data[2]}</td><td$cstr>$extramsg<<$ftag>></td><tr>" : "$asterisk{$data[2]}<br />$extramsg<<$ftag>><br />";
			break;
			case self::EXTRA_CREDITCARD:
				// tag:$
				
				// credit card type:
				$asterisk= $this->required[$ftag] ? '* ' : '';
				$dd=new ll_dropdown($form,"{$ftag}_type");
				$dd->setOptionArray(data_cc::getTypes());
				$form->addControl('d',$dd);
				$this->setupFormData($dd,"{$ftag}_type",$formdata);
				$extramsg=$formdata["*{$ftag}_type"];
				$f .= $w ? "<tr><td$cstr>{$asterisk}Credit Card Type</td><td$cstr>$extramsg<<{$ftag}_type>></td><tr>" : "{$asterisk}Credit Card Type<br />$extramsg<<{$ftag}_type>><br />";
				
				// credit card number:
				$ctrl=new ll_edit($form,"{$ftag}_number",22,22);
				$form->addControl('d',$ctrl);
				$this->setupFormData($ctrl,"{$ftag}_number",$formdata);
				$extramsg=$formdata["*{$ftag}_number"];
				$f .= $w ? "<tr><td$cstr>{$asterisk}Credit Card Number</td><td$cstr>$extramsg<<{$ftag}_number>></td><tr>" : "{$asterisk}Credit Card Number<br />$extramsg<<{$ftag}_number>><br />";
				
				// credit card cv2:
				if (data_cc::useCv2()) {
					$ctrl=new ll_edit($form,"{$ftag}_cv2",4,4);
					$form->addControl('d',$ctrl);
					$this->setupFormData($ctrl,"{$ftag}_cv2",$formdata);
					$extramsg=$formdata["*{$ftag}_cv2"];
					$cv2string1=" and CV2 code (back of card)";
					$cv2string2="&nbsp&nbsp;CV2: <<{$ftag}_cv2>>";
				} else {
					$extramsg=$cv2string1=$cv2string2='';
				}	
				
				// expiration date:
				$ctrl=new ll_edit($form,"{$ftag}_exp",5,5);
				$form->addControl('d',$ctrl);
				$this->setupFormData($ctrl,"{$ftag}_exp",$formdata);
				$extramsg .= $formdata["*{$ftag}_exp"];
				$f .= $w ? "<tr><td$cstr>{$asterisk}Expiration date (mm/yy)$cv2string1</td><td$cstr>$extramsg<<{$ftag}_exp>>{$cv2string2}</td><tr>" : "{$asterisk}Expiration date (mm/yy){$cv2string1}<br />$extramsg<<{$ftag}_exp>>{$cv2string2}<br />";
				
				// name on card:
				$ctrl=new ll_edit($form,"{$ftag}_name",28,28);
				$form->addControl('d',$ctrl);
				$this->setupFormData($ctrl,"{$ftag}_name",$formdata);
				$extramsg=$formdata["*{$ftag}_name"];
				$f .= $w ? "<tr><td$cstr>{$asterisk}Name on Card</td><td$cstr>$extramsg<<{$ftag}_name>></td><tr>" : "{$asterisk}Name on Card<br />$extramsg<<{$ftag}_name>><br />";
			break;
			default:
				throw new exception("visitor request form au $tag has unknown extra field type $ftype.  initstring: $initstring");
			break;
		}
	}
	foreach ($rgs as $rg)
		$form->addControl('d',$rg);
	$f .= $w ? '</table>' : '<br />';
	$f .= '* Required Information<br />';

	$f .= '<br /><<ok>>';
	$form->addControl('d',new ll_button($form,'ok',$this->submittext));
	if (!$bInitDefaults) {	
		$f .= '&nbsp;&nbsp;&nbsp;<<cancel>>';
		$form->addControl('d',new ll_button($form,'cancel','Cancel'));
	}
	$f .= '<br />';
	
		
	return array($form,$f);
}

public function declareids($idstore,$state) {
	list($form,$format)=$this->makeform();
	$form->declareids($idstore);
	if ($this->bEncoded)
		$idstore->registerEncodedPage();
}

public function declarestyles($stylegen,$state) {
	list($form,$format)=$this->makeform();
	$form->declarestyles($stylegen,$this->longname);
	$stylegen->registerClass('visitorrequest','form:h1,p,table,td');
	$stylegen->registerClass('visitorrequesterror','p,td');
}

// returns array of (True or False, correct values or form error values)
private function validateData($v) {
	$validated=array();
	extract($v);
	
	$bSuccess=True;		// assume it's OK until it's not
	
	// person's name
	if (!isset($name) || $name == '') {
		$bSuccess=False;
		$v['*name']='You need to provide your name';
	} else {
		$validated['name']=new data_name();
		try {
			$validated['name']->setFromSingle($name);
		} catch (data_exception $e) {
			$bSuccess=False;
			$v['*name']=$e->getMessage();
		}	
	}
	
	/// validate email address
	if (!isset($email) || $email == '') {
		if ($this->required['email']) {
			$bSuccess=False;
			$v['*email']='You need to provide an email address';
		}
	} else {
		if ($this->email == self::EMAIL_VERIFIED) {
			if (!isset($email2) || $email2 != $email) {
				$bSuccess=False;
				$v['*email2']='This field must match the email field';
			}
		}
		$validated['email']=new data_email();
		try {
			$validated['email']->set($email);
		} catch (data_exception $e) {
			$bSuccess=False;
			$v['*email']=$e->getMessage();
		}	
	}
	
	/// validate mailing address
	if ($this->mail != self::ADDRESS_NONE) {
		$validated['mail']=new data_address(True);
		if (True !== ($datapairs=$validated['mail']->verifyAndSetAddress($v,$this->required['mail']))) {
			$bSuccess=False;
			$v=array_merge($v,$datapairs);
		}
	}

	/// validate phone numbers	
	for ($i=0; $i<$this->nTelephone; ++$i) {
		if (!isset($v["phone$i"]) || $v["phone$i"] == '') {
			if ($this->required['telephone'] > $i) {
				$v["*phone$i"]='You need to provide this phone';
				$bSuccess=False;
			}
		} else {
			$validated["phone$i"]=new data_phone();
			if (isset($v["type$i"]) && $v["type$i"] != '') {
				if ($v["type$i"] == "home")
					$type=data_phone::TYPE_HOME;
				else if ($v["type$i"] == "work")
					$type=data_phone::TYPE_WORK;
				else if ($v["type$i"] == "cell")
					$type=data_phone::TYPE_CELL;
				else
					throw new exception("impossible data type for phone: {$v["type$i"]}");
			} else
				$type=data_phone::TYPE_UNKNOWN;
				
			try {
				$validated["phone$i"]->set($v["phone$i"],$type);
				$v["phone$i"]=$validated["phone$i"]->output('');
			} catch (data_exception $e) {
				$bSuccess=False;
				$v["*phone$i"]=$e->getMessage();
			}
		} // if phone # set
	} // loop for all phone #s
	
	/// validate extra fields
	foreach ($this->edfields as $ed) {
		list($ftag,$ftype,$data)=$ed;
		if ($ftype == self::EXTRA_CREDITCARD) {
			// do credit card validation
			$validated[$ftag]=new data_cc();
			// there can be more than one credit card (!!!) so normalize the fields sent back from form to the data type's indices
			$ccvals=array();
			foreach ($v as $vname=>$vfield) {
				// NOT false!  Zero!
				if (0 === strpos($vname,$ftag.'_'))
					$ccvals['cc'.substr($vname,strlen($ftag)+1)]=$vfield;
			}
			if (True !== ($datapairs=$validated[$ftag]->verifyAndSet($ccvals,$this->required[$ftag]))) {
				$bSuccess=False;
				// change all fields back to original field prefixes
				$ccvals=array();
				foreach ($datapairs as $name=>$vfield)
					$ccvals['*'.$ftag.'_'.substr($name,3)]=$vfield;
				$v=array_merge($v,$ccvals);
			}
		} else {
			// the only check on most of these types is if it's required
			if (!isset($$ftag) || $$ftag == '') {
				if ($this->required[$ftag]) {
					$v['*'.$ftag]='You must provide this information';
					$bSuccess=False;
				}
			} else {
				$validated[$ftag]=$$ftag;
			}
		}
	}
	return $bSuccess ? array(True,$validated) : array(False,$v);
}

// returns formatted message string (for emails)
private function formatData($v,$oldvals) {
	extract($v);
	$o='';	
	if (isset($name)) {
		$o .= "Name: {$name->output()}\n";
	}
	
	if (isset($email)) {
		$o .= "Email address: {$email->output()}\n";
	}
	
	if ($this->mail != self::ADDRESS_NONE) {
		$address=new data_address(True);
		$address->verifyAndSetAddress($v,False);
		$o .= "\nAddress:".$address->output("\n")."\n";
	}

	for ($i=0; $i<$this->nTelephone; ++$i) {
		$phone=new data_phone();
		if (isset($v["type$i"]) && $v["type$i"] != '') {
			if ($v["type$i"] == "home")
				$type=data_phone::TYPE_HOME;
			else if ($v["type$i"] == "work")
				$type=data_phone::TYPE_WORK;
			else if ($v["type$i"] == "cell")
				$type=data_phone::TYPE_CELL;
			else
				throw new exception("impossible data type for phone: {$v["type$i"]}");
		} else
			$type=data_phone::TYPE_UNKNOWN;
				
		$phone=$v["phone$i"]->output();
		$o .= $phone."\n";
	} // loop for all phone #s
	
	/// all other fields
	$fixed=array('name','email','mail','phone0','phone1','phone2');
	$separator='';
	
	foreach ($v as $name=>$item) {
		if (False !== array_search($name,$fixed))
			continue;  // already output
		if ($item instanceof data_cc)
			$o .= "(Credit card information not sent in emails)\n";
		else	
			$o .= "$name: $item\n";
	}
	
	// bullshit pass to get radio buttons
	foreach ($oldvals as $name=>$item) {
		if (False !== array_search($name,$fixed) || isset($v[$name]))
			continue;  // already output
		$o .= "$name: $item\n";
	}	
	
	return $o;
}



public function processVars($originUri) {
	global $qq,$qqs,$qqi;

	// any transaction-based forms need to be stated
	if (!$qqs->isStated())
		return message::redirectString(message::MISSINGCOOKIE);
	if ($this->transaction != 0 && $qqs->isTransactionFinished($this->transaction))
		return message::redirectString(message::TRANSACTIONFINISHED);

	// get data from form:
	list($form,$format)=$this->makeForm();	
	$vals=$form->getValueArray();
	
	// check to see if user cancelled
	if ($form->wasPressed('cancel')) {
		if ($this->transaction == 0)
			return "{$qq['hrefbase']}{$qq['homepage']}";
		else
			return $qqi->hrefPrep($qqs->transactionOriginUri($this->transaction));	 
	}
	
	// validate the form here:
	$oldvals=$vals;
	list($bValidated,$vals)=$this->validateData($vals);
	
	if ($bValidated) {
		$details=$this->formatData($vals,$oldvals);
		// put out the new data
		$vr=new mod_visitorrequest();
		$vr->createAction($this->actionname,$vals);
		
		// notify the administrator
		if ($this->notifyEmail != '') {
			$message="A user has made a request for your action on the web site.\nRequest type: {$this->actionname}\nSee the web site for more details.\n{$details}";
			infolog("email","sent message: $message");
			mail($this->notifyEmail,'New Request from Web Site',wordwrap($message,70),$qq['websitemailfrom']);
		}
		// close out transaction if necessary
		if ($this->transaction != 0)	
			$qqs->finishTransaction($this->transaction);
		$retval=llform::redirectString('finished');
	} else {
		// there are errors in the form.  need to correct them.  start a transaction if necessary to save the data,
		// then post it for corrections
		if ($this->transaction == 0)
			$this->transaction=$qqs->beginTransaction($originUri);
		$qqs->setTransactionData($this->transaction,'vals',$vals);
		$retval=llform::redirectString('correcting',$this->transaction);
	}
	return $retval;
}

// does output of this plus all children
public function output($pgen,$brotherid) {
	global $qqs,$qqi;
	
	$transaction=$this->transaction;
	$state=$this->state;
	
	switch ($state) {
		case '':
			list($form,$format)=$this->makeForm(True);
			echo $form->getFormattedOutput($format);		
		break;

		case 'correcting':
			$vals=$qqs->getTransactionData($transaction,'vals');
			list($form,$format)=$this->makeForm(True,$vals);
			echo $form->getFormattedOutput($format,True,$state,$transaction);		
		break;
		
		default:
			throw new exception("illegal state value in formtest:  $state");
		break;
	}
}

/////// end of AU definition ///////////////////////////////////////////////////
}?>
