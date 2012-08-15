<?php if (FILEGEN != 1) die;
///// Access Unit definition file for form & state testing
class au_proj_apgdiscovery extends au_wizard {
////////////////////////////////////////////////////////////////////////////////

function __construct($tag,$parent,$initstring,$state="",$data=0) {
	parent::__construct($tag,$parent,$initstring,$state,$data);
}

protected function enumStates() {
	return array('init','situation','personal','firm','mixture','strategy','legal');
}

protected function makeform($state) {
	$form=new llform($this,'','','discoveryform');
	switch ($state) {
		case 'init':
			$form->addFieldset('saveinfo',"Fill this part out ONLY if you are starting a new Discovery Profile");
			$form->addControl("saveinfo",new ll_edit($form,"email",40,40,"Your email (one we can reach you at privately)"));
			$form->addControl("saveinfo",new ll_edit($form,"salutation",12,12,"Your First Name"));
			$form->addControl("saveinfo",new ll_edit($form,"pw1",20,20,"Password",True));
			$form->addControl("saveinfo",new ll_edit($form,"pw2",20,20,"Verify Password",True));

			$form->addFieldset('restoreinfo',"Fill this part out ONLY if you are returning to complete a Discovery Profile you already started");
			$form->addControl("restoreinfo",new ll_edit($form,"returnemail",40,40,"Your email (the one you entered when you started your profile)"));
			$form->addControl("restoreinfo",new ll_edit($form,"pw",20,20,"Password",True));
		break;
		case 'situation':
			$form->addFieldset('situation',"What is your Current Situation?");
			$rg=new ll_radiogroup($form,"situation");
				$rg->addOption("business","I run an advising firm (OSJ group/multiple advisors) and am considering changing the Broker Dealer for my company.");
				$rg->addOption("individualchange","I am already on my own, but I would like to consider changing my Broker/Dealer.");
				$rg->addOption("individualstart","I am thinking about going out on my own for the first time as an investment advisor.");
				$rg->addOption("other","I have another situation - See comment below:");
			$form->addControl("situation",$rg);	
			$form->addControl("situation",new ll_textarea($form,"othersituation",60,3,""));
			$form->addFieldset('timeframe',"What is your Timeframe for Changing?");
			$rg=new ll_radiogroup($form,"timeframe");
				$rg->addOption("immediate","Immediate");
				$rg->addOption("month","Within a month");
				$rg->addOption("quarter","Within a the next quarter");
				$rg->addOption("other","Other:");
			$form->addControl("timeframe",$rg);	
			$form->addControl("timeframe",new ll_edit($form,"othertimeframe",30,30));
			$form->addFieldset('activity',"Which Broker/Dealers, if any, have you already spoken with?");
			$form->addControl("activity",new ll_textarea($form,"otherbd",50,5,"List or put 'none'"));
			$form->addControl("activity",new ll_edit($form,"currentbd",40,40,"Who is your current Broker/Dealer and Clearing Firm?"));
			$form->addControl("activity",new ll_edit($form,"howlong",5,5,"How many years have you been affiliated with them?"));
		break;
		case 'personal':
			$form->addFieldset("personal","Contact Information");
			$form->addControl("personal",new ll_edit($form,"name",30,30,"Full Name"));
			$form->addControl("personal",new ll_edit($form,"dba",30,30,"dba (If applicable)"));
			$form->addControl("personal",new ll_textarea($form,"address",40,4,"Mailing Address"));
			$form->addControl("personal",new ll_edit($form,"phone",20,20,"Business Phone (if we can call there)"));
			$form->addControl("personal",new ll_edit($form,"cell",20,20,"Personal/Cell"));
			$form->addControl("personal",new ll_edit($form,"email",40,40,"Email Address"));
			$form->addFieldset("pp","Your Personal Performance");
			$form->addControl("pp",new ll_checkbox($form,"lastyeargdcverified","(Last year's GDC Verified)"));
			$form->addControl("pp",new ll_edit($form,"lastyeargdc",10,10,"Last Full Year's GDC"));
			$form->addControl("pp",new ll_checkbox($form,"ytdgdcverified","(YTD GDC Verified)"));
			$form->addControl("pp",new ll_edit($form,"ytdgdc",10,10,"This Year-to-Date GDC"));
			$form->addControl("pp",new ll_edit($form,"totalclients",5,5,"Total Number of Clients"));
			$form->addControl("pp",new ll_edit($form,"totalhouseholds",5,5,"Total Number of Households"));
			$form->addFieldset("ps","Your Personal Business Needs & Preferences");
			$form->addControl("ps",new ll_edit($form,"vamfd",50,50,"VA & MFD Vendors you use"));
			$form->addControl("ps",new ll_edit($form,"thirdparty",50,50,"Third Party Vendors"));
			$form->addControl("ps",new ll_edit($form,"eia",50,50,"EIA & Fixed Insurance Vendors"));
			$form->addControl("ps",new ll_edit($form,"researchsoftware",30,50,"Research Software you Prefer"));
			$form->addControl("ps",new ll_edit($form,"planningsoftware",30,50,"Financial Planning Software you Prefer"));
			$form->addControl("ps",new ll_edit($form,"cms",30,50,"Contact Management System"));
			$form->addControl("ps",new ll_edit($form,"consolidation",30,50,"Account Consolidation Program"));
			$form->addControl('ps',new ll_textarea($form,"outsideinterests",60,3,"What, if any, outside business activities (OBAs) do you have?"));
		break;
		case 'firm':
			$form->addFieldset("firm","Firm Contact and Size Information");
			$form->addControl("firm",new ll_edit($form,"name",30,30,"Your Name (Contact Person)"));
			$form->addControl("firm",new ll_edit($form,"firm",30,30,"Firm Name"));
			$form->addControl("firm",new ll_textarea($form,"address",40,4,"Firm's Mailing Address"));
			$form->addControl("firm",new ll_edit($form,"phone",20,20,"Business Phone"));
			$form->addControl("firm",new ll_edit($form,"cell",20,20,"Contact Person's Personal/Cell"));
			$form->addControl("firm",new ll_edit($form,"email",40,40,"Contact Person's Email Address"));
			$form->addControl("firm",new ll_edit($form,"website",40,40,"Firm's Website Address"));
			$form->addControl("firm",new ll_edit($form,"manager",30,30,"Office Manager Name"));
			$form->addControl("firm",new ll_edit($form,"numreps",5,5,"Number of Producing Reps (Including yourself)"));
			$form->addControl("firm",new ll_edit($form,"numstaff",5,5,"Number of Support Staff"));
			$form->addControl("firm",new ll_checkbox($form,"multiple","Reps are in Multiple Locations"));
			$form->addFieldset("pp","Your Firm's Performance");
			$form->addControl("pp",new ll_checkbox($form,"lastyeargdcverified","(Verified) Last Full Year's GDC:"));
			$form->addControl("pp",new ll_edit($form,"lastyeargdc",10,10));
			$form->addControl("pp",new ll_checkbox($form,"ytdgdcverified","(Verified) This Year-to-Date GDC:"));
			$form->addControl("pp",new ll_edit($form,"ytdgdc",10,10));
			$form->addControl("pp",new ll_edit($form,"totalclients",5,5,"Total Number of Clients"));
			$form->addControl("pp",new ll_edit($form,"totalhouseholds",5,5,"Total Number of Households"));
			$form->addControl("pp",new ll_edit($form,"payout",10,10,"Current Payout"));
			$form->addFieldset("ps","Your Firm's Business Needs & Preferences");
			$form->addControl("ps",new ll_edit($form,"vamfd",50,50,"VA & MFD Vendors you use"));
			$form->addControl("ps",new ll_edit($form,"thirdparty",50,50,"Third Party Vendors"));
			$form->addControl("ps",new ll_edit($form,"eia",50,50,"EIA & Fixed Insurance Vendors"));
			$form->addControl("ps",new ll_edit($form,"researchsoftware",30,50,"Research Software you Prefer"));
			$form->addControl("ps",new ll_edit($form,"planningsoftware",30,50,"Financial Planning Software you Prefer"));
			$form->addControl("ps",new ll_edit($form,"cms",30,50,"Contact Management System"));
			$form->addControl("ps",new ll_edit($form,"consolidation",30,50,"Account Consolidation Program"));
			$form->addControl('ps',new ll_textarea($form,"outsideinterests",60,3,"What, if any, outside business activities (OBAs) do you have?"));
		break;
		case 'mixture':
			$form->addFieldset('mix',"Fee Types");
			$form->addControl("mix",new ll_edit($form,"aum",10,10,"Total AUM"));
			$form->addControl("mix",new ll_edit($form,"commissiongdc",10,10,"Commission GDC"));
			$form->addControl("mix",new ll_edit($form,"feegdc",10,10,"Fee GDC"));
			$form->addControl("mix",new ll_edit($form,"stocks",10,10,"Stocks"));
			$form->addControl("mix",new ll_edit($form,"bonds",10,10,"Bonds"));
			$form->addFieldset('bmix',"Percentages of the Business (Should add up to 100%)");
			$form->addControl("bmix",new ll_edit($form,"brokerage",10,10,"Brokerage %"));
			$form->addControl("bmix",new ll_edit($form,"mutualfunds",10,10,"Mutual Funds %"));
			$form->addControl("bmix",new ll_edit($form,"variableannuity",10,10,"Variable Annuity %"));
			$form->addControl("bmix",new ll_edit($form,"brokeragewrap",10,10,"Brokerage Wrap %"));
			$form->addControl("bmix",new ll_edit($form,"dpp",10,10,"DPP/Limited Partnerships %"));
			$form->addControl("bmix",new ll_edit($form,"variableinsurance",10,10,"Variable Insurance %"));
			$form->addControl("bmix",new ll_edit($form,"fixedinsurance",10,10,"Fixed Insurance %"));
			$form->addControl("bmix",new ll_edit($form,"equityindex",10,10,"Equity Index Annuity %"));
			$form->addControl("bmix",new ll_edit($form,"other",10,10,"Other %"));
		break;
		case 'strategy':
			$form->addFieldset('strategy',"Outline a few aspects of your Business Strategy");
			$form->addControl('strategy',new ll_textarea($form,"challenges",60,3,"What are the three biggest challenges for you with your current Broker/Dealer?"));
			$form->addControl('strategy',new ll_textarea($form,"ideals",60,3,"What are three things you would want from your ideal Broker/Dealer?"));
			$form->addControl('strategy',new ll_textarea($form,"initiatives",60,3,"Name some of your strategic Initiatives for your Business"));
			$form->addControl('strategy',new ll_textarea($form,"marketing",60,3,"How do you generate new clients?"));
			$form->addControl('strategy',new ll_textarea($form,"marketingplans",60,3,"What, if any, new marketing moves would you like to make?"));
			$form->addControl('strategy',new ll_textarea($form,"succession",60,3,"What, if any, is your succession plan or business exit strategy?"));
			$form->addFieldset('reps',"Are you interested in recruiting Reps?");
			$rg=new ll_radiogroup($form,"reps");
				$rg->addOption("repsno","No");
				$rg->addOption("repsyes","Yes");
			$form->addControl("reps",$rg);
			$form->setValue("reps","repsno");	
		break;
		case 'legal':
			$form->addFieldset('ria',"RIA Registration");
			$rg=new ll_radiogroup($form,"ria");
				$rg->addOption("ownria","Have own RIA");
				$rg->addOption("bdria","Use Broker/Dealer's RIA");
			$form->addControl("ria",$rg);
			$form->addFieldset('finra',"FINRA/Insurance Licenses");
			$form->addControl("finra",new ll_checkbox($form,"lookupfinra","Please Look this Up for me"));
			$form->addControl("finra",new ll_edit($form,"lastyeargdc",50,50,"FINRA/Insurance Licenses"));
			$form->addFieldset('discretion',"Discretion Needed?");
			$rg=new ll_radiogroup($form,"discretion");
				$rg->addOption("discretionyes","Yes");
				$rg->addOption("discretionno","No");
			$form->addControl("discretion",$rg);
			$form->addFieldset('u4',"Do you have any U4 Items?");
			$rg=new ll_radiogroup($form,"u4");
				$rg->addOption("u4na","N/A");
				$rg->addOption("u4no","No");
				$rg->addOption("u4yes","Yes");
			$form->addControl("u4",$rg);
			$form->addControl('u4',new ll_textarea($form,"u4notes",60,3,"Notes:"));
			$form->addFieldset('issues',"Do you have any Credit issues, bankruptcy judgments, or liens?");
			$rg=new ll_radiogroup($form,"issues");
				$rg->addOption("issuesna","N/A");
				$rg->addOption("issuesno","No");
				$rg->addOption("issuesyes","Yes");
			$form->addControl("issues",$rg);
			$form->addControl('issues',new ll_textarea($form,"issuesnotes",60,3,"Notes:"));
		break;
		default:
			throw new exception("Illegal wizard form state $state");
		break;
	}
	return $form;
}

private function saveForms($formarray,$data=null) {
	$email=$formarray['init']->getControl('returnemail')->getValue();
	if ($data==null) {
		$data=new mod_proj_apgdiscovery;
		$data->restoreEntry($email);
	}
	$alldata=array();
	foreach ($formarray as $state=>$form) {
		$alldata[$state]=array();
		foreach ($form->controlNames() as $name) {
			$ctrl=$form->getControl($name);
			if ($ctrl->hasValue()) {
				$alldata[$state][$name]=array($ctrl->label(),$ctrl->getValue());
			}
		}
	}
	$data->saveEntryValues($alldata);	
}

private function restoreForms($formarray,$data=null) {
	$email=$formarray['init']->getControl('returnemail')->getValue();
	if ($data==null) {
		$data=new mod_proj_apgdiscovery;
		$data->restoreEntry($email);
	}
	$alldata=$data->getValues();
	foreach ($alldata as $state=>$formdata) {
		$form=$formarray[$state];
		foreach ($formdata as $name=>$valarray) {
			list ($label,$value)=$valarray;
			if ($form->hasValue($name))
				$form->getControl($name)->setValue($value);
		}
	}
}

// returns html string
private function docFromForms($formarray) {
	$titles=array(
		'init'=>'Basic Information',
		'situation'=>'Business Situation',
		'personal'=>'Personal Information',
		'firm'=>'Information about Firm',
		'mixture'=>'Business Mixture',
		'strategy'=>'Business Strategic Outlook',
		'legal'=>'Legal Information'
	);
	$r='';
	foreach ($formarray as $state=>$form) {
		if ($state == 'init')
			continue;
		if ($state == 'personal' && $formarray['situation']->getControl('situation')->getValue() == 'business')
			continue;	
		if ($state == 'firm' && $formarray['situation']->getControl('situation')->getValue() != 'business')
			continue;	
		$r .="<h1>{$titles[$state]}</h1>";
		$r .='<table>';
		foreach ($form->getFieldsets() as $fsname) {
			$r .= "<tr><th colspan=\"2\" style=\"background-color:#eee; padding:0.3em;\">{$form->getFieldsetTitle($fsname)}</th></tr>";
			foreach ($form->getFieldsetFields($fsname) as $name) {
				$ctrl=$form->getControl($name);
				$value=$ctrl->getValue();
				if (strlen($value) == 0)
					$value='(Not Provided)';
				else
					$value=nl2br(htmlentities($value));	
				$r .= "<tr><td style=\"background-color:#eee; padding:0.3em; text-align:right;\">{$ctrl->label()}</th><td style=\"background-color:#eee; padding:0.3em;\">{$value}</td></tr>";
			}	
		}	
		$r .='</table>';
	}	
	return $r;
}

// checks form and updates state
protected function validate($formarray,$state) {
	$adminemail='Craig Enderlin <craig@advisorplacementgroup.com>';
	$adminfrom='Craig Enderlin <craig@advisorplacementgroup.com>';
	$form=$formarray[$state];
	switch ($state) {
		case 'init':
			$email=$form->getControl('email')->getValue();
			if (!($bNew=strlen($email) > 0))
				$email=$form->getControl('returnemail')->getValue();
			if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
				$form->getControl($bNew ? 'email' : 'returnemail')->setError('You must enter a valid email address');
				$retval='init';
				break;			
			}
			$password=$form->getControl($bNew ? 'pw1' : 'pw')->getValue();
			$data=new mod_proj_apgdiscovery;
			if ($bNew) {
				$bErrors=False;
				if (strlen($form->getControl('salutation')->getValue()) == 0) {
					$form->getControl('salutation')->setError('Please give us your first name');
					$bErrors=True;
				}
				if (strlen($form->getControl('pw1')->getValue()) == 0) {
					$form->getControl('pw1')->setError('You must specify a password');
					$bErrors=True;
				}
				if ($password != $form->getControl('pw2')->getValue()) {
					$form->getControl('pw2')->setError('Passwords must match');
					$bErrors=True;
				}
				if ($bErrors) {	
					$retval='init';
					break;			
				}
				$data->newEntry($email,$form->getControl('salutation')->getValue(),$form->getControl('pw2')->getValue());
				foreach (array('email','salutation','pw1','pw2') as $ctrlid) {
					$form->getControl($ctrlid)->setValue('');
					$form->getControl($ctrlid)->setError('');
				}	
				$form->getControl('returnemail')->setValue($email);
				mail($adminemail,'New discovery process begun',"A new discovery process has been begun for {$data->getSalutation()} at $email.  The password is '{$data->getPassword()}'");
			} else {
				if (!$data->restoreEntry($email)) {
					$form->getControl('returnemail')->setError('No discovery process found for this email');
					$retval='init';
					break;
				}
				if ($data->getPassword() != $form->getControl('pw')->getValue()) {
					$form->getControl('pw')->setError('Password does not match');
					$form->getControl('pw')->setValue('');
					$retval='init';
					break;
				}
				$this->restoreForms($formarray,$data);
				foreach (array('returnemail','pw') as $ctrlid) {
					$form->getControl($ctrlid)->setValue('');
					$form->getControl($ctrlid)->setError('');
				}	
				$form->getControl('returnemail')->setValue($email);
			}
			$this->saveForms($formarray,$data);
			$retval='situation';
		break;
		case 'situation':
			if ($form->getValue('situation') == 'business')
				$retval='firm';
			else
				$retval='personal';
			$this->saveForms($formarray);
		break;
		case 'personal':
			$retval='mixture';			
			$this->saveForms($formarray);
		break;
		case 'firm':
			$retval='mixture';			
			$this->saveForms($formarray);
		break;
		case 'mixture':
			$mixes=$form->getFieldsetFields('bmix');
			$total=0;
			foreach ($mixes as $name)
				$total += $form->getControl($name)->getValue();
			$errormsg=($total != 100) ? 'Totals must equal 100' : '';
			foreach ($mixes as $name)
				$form->getControl($name)->setError($errormsg);
			if ($total != 100) {
				$retval='mixture';
				break;
			}	
			$retval='strategy';			
			$this->saveForms($formarray);
		break;
		case 'strategy':
			$retval='legal';			
			$this->saveForms($formarray);
		break;
		case 'legal':
			$retval='finished';			
			$email=$formarray['init']->getControl('returnemail')->getValue();
			$data=new mod_proj_apgdiscovery;
			$data->restoreEntry($email);
			$this->saveForms($formarray,$data);
			$output=$this->docFromForms($formarray);
			$to=$email;

			$subject = 'Completed Discovery Process Form for APG';
			$message = '<html><head><title>Completed Discovery Process Form for APG</title></head><body><p>Dear '.$data->getSalutation().':</p><p>Thank you for your interest in APG.  We will be contacting you shortly.  Here are the data you submitted:</p>'.$output.'</body></html>';
			// To send HTML mail, the Content-type header must be set
			$headers  = 'MIME-Version: 1.0' . "\r\n";
			$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
			// Additional headers
			$headers .= "From: {$adminfrom}\r\n";
			$headers .= "Bcc: {$adminemail}\r\n";
			// Mail it
			mail($to,$subject,$message,$headers);				
		break;
		default:
			throw new exception ("illegal wizard form state $state");
		break;
	}
	return $retval;   
}

protected function getButtonText($state,$bNext=True) {
	if ($state == '') {
		return "Start the Discovery Process Now";
	}	
	if ($state == 'finished')
		return "Return to Website";	
	return ($bNext ? "Next" : "Previous");	
}

protected function outputForm($state,$form) {
	echo $form->getTableStyleFormOutput($state);
}


/////// end of AU definition ///////////////////////////////////////////////////
}?>
