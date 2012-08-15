<?php if (FILEGEN != 1) die;
///// Access Unit definition file for 'wizard' multi screen form initialization
class au_wizardinit extends au_base {
////////////////////////////////////////////////////////////////////////////////
private $state;
private $data;

function __construct($tag,$parent,$initstring,$state="",$data=0) {
	parent::__construct($tag,$parent,$initstring);
	$this->state=$state;
	$this->transaction=(int)$data;
}

private function makeform() {
	$form=new llform($this);
	$form->addFieldset("aboutyou","About You");
	$form->addFieldset("landscapeinterest","Your interest in landscaping");
	$form->addFieldset("serviceinterest","Which services interest you?");
	$form->addFieldset("helprequest","How can we help you?");
	$form->addFieldset("getback","How can we get back to you?");

	$form->addControl("aboutyou",new ll_edit($form,"name",28,40,"Your name",False));
	$form->addControl("aboutyou",new ll_edit($form,"city",28,40,"City and state where you live",False));
	
	$rg=new ll_radiogroup($form,"interest");
		$rg->addOption("homeowner","I own a house");
		$rg->addOption("building","I am building a new house");
		$rg->addOption("contractor","I am a contractor");
		$rg->addOption("business","I own a business");
		$rg->addOption("other","Other:");
	$form->addControl("landscapeinterest",$rg);	
	$form->addControl("landscapeinterest",new ll_textarea($form,"otherinterest",25,2,""));
	
	$form->addControl("serviceinterest",new ll_checkbox($form,"xeriscaping","Xeriscaping"));
	$form->addControl("serviceinterest",new ll_checkbox($form,"general","General Landscaping"));
	$form->addControl("serviceinterest",new ll_checkbox($form,"outdoor","Other outdoor improvements"));
	
	$rg2=new ll_radiogroup($form,"request");
		$rg2->addOption("quote","I would like an estimate");
		$rg2->addOption("shopping","I am shopping for landscaping services");
		$rg2->addOption("question","I am just looking for information");
	$form->addControl("helprequest",$rg2);	
	$form->addControl("helprequest",new ll_textarea($form,"comment",35,5,"Comment"));

	$form->addControl("getback",new ll_checkbox($form,"useemail","Email me (no mass emails)"));
	$form->addControl("getback",new ll_edit($form,"email",28,80,"Email address"));
	$form->addControl("getback",new ll_checkbox($form,"usephone","Call me"));
	$form->addControl("getback",new ll_edit($form,"phone",20,20,"Phone Number"));
	$form->addControl("getback",new ll_checkbox($form,"usemail","U.S. Mail"));
	$form->addControl("getback",new ll_textarea($form,"mail",28,4,"Address"));
	$form->addControl("getback",new ll_button($form,"Ok","Contact Us"));
	return $form;
}

private function makeforwardbackform($bForwardOK) {
	$form=new llform($this,"fwdback");
	$form->addFieldset("dummy","");
	if ($bForwardOK)
		$form->addControl("dummy",new ll_button($form,"send","Send the message"));
	$form->addControl("dummy",new ll_button($form,"back","Go back"));
	return $form;
}

public function declareids($idstore,$state) {
	global $qqi;
	if ($state == "" || $state == "collecting" || $state == '*') {
		$this->makeform()->declareids($idstore);
	}
	if ($state == "confirming" || $state == '*') {
		$this->makeforwardbackform(True)->declareids($idstore);
	}
	if ($state == "finished" || $state == '*') {
		$idstore->declareHTMLlong($this->longname.'_finished');
	}	
}

public function hardcodestyles($stylegen,$state) {
	if ($state == "" || $state == "collecting" || $state == '*') {
		$form=$this->makeform();
		
		$stylegen->styleByLong($form->getFieldsetLong("aboutyou"),"fieldset","position","D 10px|10px");
		$stylegen->styleByLong($form->getFieldsetLong("aboutyou"),"fieldset","width","200px");
		$stylegen->styleByLong($form->getFieldsetLong("aboutyou"),"fieldset","height","100px");
		$stylegen->styleByLong($form->getFieldsetLong("landscapeinterest"),"fieldset","position","D 10px|133px");
		$stylegen->styleByLong($form->getFieldsetLong("landscapeinterest"),"fieldset","width","200px");
		$stylegen->styleByLong($form->getFieldsetLong("landscapeinterest"),"fieldset","height","167px");
		$stylegen->styleByLong($form->getFieldsetLong("serviceinterest"),"fieldset","position","D 245px|10px");
		$stylegen->styleByLong($form->getFieldsetLong("serviceinterest"),"fieldset","width","270px");
		$stylegen->styleByLong($form->getFieldsetLong("serviceinterest"),"fieldset","height","80px");
		$stylegen->styleByLong($form->getFieldsetLong("helprequest"),"fieldset","position","D 245px|120px");
		$stylegen->styleByLong($form->getFieldsetLong("helprequest"),"fieldset","width","270px");
		$stylegen->styleByLong($form->getFieldsetLong("helprequest"),"fieldset","height","180px");
		$stylegen->styleByLong($form->getFieldsetLong("getback"),"fieldset","position","D 550px|10px");
		$stylegen->styleByLong($form->getFieldsetLong("getback"),"fieldset","width","215px");
		$stylegen->styleByLong($form->getFieldsetLong("getback"),"fieldset","height","290px");
	}
}

public function declarestyles($stylegen,$state) {
	if ($state == "" || $state == "collecting" || $state == '*') {
		$form=$this->makeform();
		$form->declarestyles($stylegen,$this->longname);
	}
	if ($state == "confirming" || $state == '*') {
		$form=$this->makeforwardbackform(True);
		$form->declarestyles($stylegen,$this->longname);
		$stylegen->registerClass('proj_hcgemail','h3,p');
	}
	if ($state == "finished" || $state == '*') {
		$stylegen->registerStyledId($this->longname.'_finished',"div:h1,a,p","form_finished",$this->getParent()->htmlContainerId());
	}	
}

private function makemessage($vals) {
	extract($vals);
	if (!isset($name) || $name == "")
		$name="(not given)";
	if (!isset($city) || $city == "")
		$city="(not given)";
	$sentence1=array("homeowner"=>"for my existing home" ,"building"=>"for the new home I'm building","contractor"=>"because I'm a contractor","business"=>"for my business","other"=>"for this reason: ".$otherinterest);
	if (!isset($interest))
		$sentence1="".$otherinterest;
	else
		$sentence1=$sentence1[$interest];	
	$sentence2="";
	if ($xeriscaping)
		$sentence2 .= "I am interested in Xeriscaping.  ";
	if ($general)
		$sentence2 .= "I would like to know about other kinds of landscaping you do.  ";
	if ($outdoor)
		$sentence2 .= "The general outdoor services you offer interest me.  ";
	$sentence3=(isset($comment) && $comment != "") ? "I would like to make the following comment:  ".$comment : "";
	$contacts="";
	if ($useemail || (isset($email)) && $email != "")
		$contacts .= "email:  $email\n";
	if ($usephone || (isset($phone)) && $phone != "")
		$contacts .= "phone:  $phone\n";
	if ($usemail || (isset($mail)) && $mail != "")
		$contacts .= "mail:  $mail\n";
		
	$message= <<<EOM
Dear Jeff:
My name is {$name} from {$city}.  I am interested in your services {$sentence1}.

{$sentence2}{$sentence3}

You can reach me as follows:
$contacts

Thank you for your prompt attention.
EOM;
	return $message;
}

public function processVars($originUri) {
	global $qq,$qqs;
	
	// any transaction-based forms need to be stated
	if (!$qqs->isStated())
		return message::redirectString(message::MISSINGCOOKIE);
		
	if ($this->transaction != 0) {
		$transaction=$this->transaction;
		if ($qqs->isTransactionFinished($transaction))
			return message::redirectString(message::TRANSACTIONFINISHED);
	} else
		$transaction=0;		

	switch ($this->state) {
		case '':
		case 'collecting':
			// get data from form and save it:
			if ($transaction == 0)
				$transaction=$qqs->beginTransaction($originUri);
			$vals=$this->makeform()->getValueArray();
			$qqs->setTransactionData($transaction,"vals",$vals);
			$retval=llform::redirectString('confirming',$transaction);
		break;
		
		case 'confirming':
			if ($transaction == 0)
				throw new exception("expected transaction not present");
			// either move forward and send the email or return to edit.
			$retval=$this->makeforwardbackform(True)->setFieldsFromRequests();
			if ($retval == "send") {
				// send email
				$message=$this->makemessage($qqs->getTransactionData($transaction,"vals"));
				infolog("email","sent message: $message");
				mail('hcgardens@awesomenet.net','New Contact from Web Site',wordwrap($message,70),$qq['websitemailfrom']);				
				$qqs->finishTransaction($transaction);
				$retval=llform::redirectString('finished');
			} else
				$retval=llform::redirectString('collecting',$transaction);
		break;
		
		default:
			throw new exception("illegal state $state in {$this->longname}");
		break;
	}
	return $retval;
}

// does output of this plus all children
public function output($pgen,$brotherid) {
	global $qqs,$qqi;
	
	$transaction=$this->transaction;
	
	switch ($this->state) {
		case 'collecting':
		case '':
			$form=$this->makeForm();
			if ($transaction != 0) {
				$vals=$qqs->getTransactionData($transaction,"vals");
				$form->setValueArray($vals);
				$transactiondata=(string)$transaction;
			} else
				$transactiondata=0;	
			echo $form->getDumpStyleFormOutput('collecting',$transactiondata);
		break;
		case 'confirming':
			if ($transaction == 0)
				throw new exception("expected transaction not present");
			// make certain that enough has been done that the email will make sense
			$vals=$qqs->getTransactionData($transaction,"vals");
			extract($vals);
			// extremely rudimentary data filtering:
			$bForwardOK=(isset($name) && trim($name) != "" && ((isset($email) && trim($email) != "") || (isset($mail) && trim($mail) != "") || (isset($phone) && trim($phone) != "")));
			
			$message=$this->makemessage($vals);
			$form=$this->makeforwardbackform($bForwardOK);
			
			echo $form->getOutputFormStart();
			echo "<h3{$qqi->cstr("proj_hcgemail")}>Here is the message you will send:</h3>";
			echo "<p{$qqi->cstr("proj_hcgemail")}>".nl2br($message)."</p>";
			if (!$bForwardOK)
				echo "<h4{$qqi->cstr("proj_hcgemail")}>You must fill out your name and a way we can contact you before sending the message.</h4>";	
			echo $form->getControl("back")->output();
			if ($bForwardOK)
				echo $form->getControl("send")->output();
			echo $form->getOutputFormEnd('confirming',$transaction);
		break;
		case 'finished':
			if ($transaction == 0)
				throw new exception("expected transaction not present");
			echo "<div{$qqi->idcstr($this->longname.'_finished')}>";	
			echo "<h1>Thank you for your interest.</h1>";
			echo "<p>We will be getting back to you shortly.</p>";
			echo "<p>".link::anchorHTML('Home',"Back to home page").'Continue Looking</a></p>';
			echo "</div>";	
		break;
		default:
			throw new exception("illegal state value in formtest:  $state");
		break;
	}
}

/////// end of AU definition ///////////////////////////////////////////////////
}?>
