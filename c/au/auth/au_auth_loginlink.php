<?php if (FILEGEN != 1) die;
class au_auth_loginlink extends au_base {
/////////////////////////////////// au to present a link to either login or log out
private $link;

function __construct($tag,$parent,$initdata) {
	
	parent::__construct($tag,$parent,$initdata);
}


public function declareids($idstore,$state) {
//	$idstore->declareHTMLid($this,$this->bAutoLock);
}

public function declarestyles($stylegen,$state) {
/*
	if ($this->type == "a")
		$this->link->registerStyled($stylegen,$this->function,$this->getParent()->htmlContainerId());
	else
		$stylegen->registerStyledId($this->longname,$this->type,$this->function,$this->getParent()->htmlContainerId());
*/
}

public function output($pgen,$brotherid) {
	global $qqs,$qqu,$qqi,$qqp,$qq;
	
	$formshort=$qqp->getAUShortName('au_auth_screendoor','login','');
	$project=($qq['production']) ? '' : "&u={$qq['project']}";
	
	if (($userid=$qqs->getUser()) != 0) {
		// display signed-in user's name and allow signout
		if ($userid == -1)
			$name='superuser';
		else {	
			$username=$qqs->getProfile('name');
			$name=$username->output();
		}	
		echo "signed in as $name • <a href=\"".$qqi->hrefprep("form.php?q={$qq['request']}&r=$formshort&s=logout&t=0",False,'',idstore::ENCODE_NONE)."$project\">Logout</a>";
 
	} else {
		// put out link to signin
		echo "<a href=\"".$qqi->hrefprep("form.php?q={$qq['request']}&r=$formshort&s=&t=0")."$project\">Login</a>";
	}
}
/////////////////////////////////// end of au definition
} ?>
