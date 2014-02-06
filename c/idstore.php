<?php if (FILEGEN != 1) die;
class idstore {
//////////////////////////// idstore class is repository for all ids ///////////
private $htmlids;			// key=>array(long=>array(short,mask,lock,classes))
private $htmlseq = 0;		// sequential assignment of htmlids
private $classes;			// long=>short
private $classseq = 0;		// sequential assignment of classes
private $registerpage;		// key of currently registering page 
private $registerrender;	// render currently being registered
private $outputpage;
private $auth;				// auth data--names and defaults (name=>(desc,defanon,defnew,super,min,max))
private $authpage;			// page auth data (pagename,reason)
private $profile;
private $encodedPages;		// pages requiring encryption

function __construct() {
	$this->htmlids=array();
	$this->classes=array();
	$this->auth=array();
	$this->authpage=array();
	$this->profile=array();
	$this->encodedPages=array();
}

public function initIds() {
	global $qqp;
		
	$pages=$qqp->getPageNames();
	$renders=array(1,2,4);
	foreach ($pages as $page) {
		list($formclass,$formtag,$formstate,$formdata,$forminit)=$qqp->getFormInfo($page);
		$this->registerpage=$page;
		$this->htmlids[$page]=array();
		$recipe=$qqp->getBuildList($page);
		$lockedids=array();
		foreach ($renders as $render) {
			$this->registerrender=$render;
			$bodyau=au_base::treeFromRecipe($recipe,$render,$formclass,$formtag,$formstate,$formdata);
			for ($au=$bodyau; $au != Null; $au=$au->getNextAU()) {
				$myformstate=(get_class($au) == $formclass) ? $formstate : '';
				$au->declareids($this,$myformstate);
			}
			foreach ($lockedids as $longname)
				$this->lockHTMLid($longname,'c',$page);	
			unset($bodyau);	
		}	
	}
	// check for presence of all required auths:
	$this->registerAuthRead('',True);
	unset($this->registerpage); 
}

public function getRender() {
	if (!isset($this->registerpage))
		throw new exception("registration call made out of context");
	return $this->registerrender;	
}

private $bDirty=True;		// if True, this object has changed and needs to be stored 
public function isDirty() {return $this->bDirty;}
public function setClean() {$this->bDirty=False;}

// assigns a new shortname
public function assignNewShortname($page,$longname,$shortname) {
	if (!isset($this->htmlids[$page][$longname]))
		throw new exception("longname $longname does not exist in buildset $page.  cannot rename to $shortname.");
	$this->htmlids[$page][$longname][0]=$shortname;
	$this->bDirty=True;	
}

// returns long name of html id created
public function declareHTMLid($au,$bAutoLock=False,$tag="") {
	$longname=$au->getLongName();
	if ($tag != "")
		$longname .= "_".$tag;
	return $this->declareHTMLlong($longname,$bAutoLock);
}		

// same as above, but more straightforward.		
public function declareHTMLlong($longname,$bAutoLock=False) {		
	if (!isset($this->registerpage))
		throw new exception("declaration call is out of context for $longname");
	$page=$this->registerpage;	
	if (isset($this->htmlids[$page][$longname])) {
		// already there.  if render masks conflict, this is a re-registration
		if ($this->registerrender & $this->htmlids[$page][$longname][1])
			throw new exception("cannot redeclare $longname in page $page");
		$this->htmlids[$page][$longname][1] |= $this->registerrender;				
	} else {
		// add a new value
		$this->htmlids[$page][$longname]=array(self::getAlphaLabel($this->htmlseq++),$this->registerrender,$bAutoLock ? 'a' : '',"");
	}
	return $longname;
}

public function addClassToID($class,$longname,$page) {
	if (!isset($this->htmlids[$page][$longname])) {
		infolog_dump("errortrap","htmlids for $page",$this->htmlids);
		throw new exception("longname $longname does not exist in buildset $page.  cannot add class.");
	}
	$class=trim($class);
	$existingclass=$this->htmlids[$page][$longname][3];
	// only add class if it isn't already in there
	if (False === (strpos(' '.$existingclass.' ',' '.$class.' '))) {
		$separator = $existingclass != '' ? ' ' : '';
		$this->htmlids[$page][$longname][3] .= $separator.$class;
	}	
	$this->bDirty=True;	
}


// no return value
public function lockHTMLid($longname,$type="p",$page="") {
	if ($page == "") {
		if (isset($this->registerpage)) {
			$page=$this->registerpage;
		} else if (isset($this->outputpage)) {
			$page=$this->outputpage;
		} else
			throw new exception("no context for LOCK of html id: $longname");
	}
	if (!isset($this->htmlids[$page][$longname])) {
		infolog_dump("errortrap","htmlids for $page",$this->htmlids);
		throw new exception("longname $longname does not exist in buildset $page.  cannot lock.");
	}	
	if (False === strpos($this->htmlids[$page][$longname][2],$type)) {
		$this->htmlids[$page][$longname][2] .= $type;
		$this->bDirty=True;
	}	
}

public function isValidLongname($longname,$page="") {
	if ($page == "") {
		if (isset($this->registerpage)) {
			$page=$this->registerpage;
		} else if (isset($this->outputpage)) {
			$page=$this->outputpage;
		} else
			throw new exception("no context for short name lookup of html id: $longname");
	}
	if (!isset($this->htmlids[$page][$longname])) {
		$pages=implode(" ",array_keys($this->htmlids[$page]));
		infolog("errortrap","available pages: $pages");
	}	
	return (isset($this->htmlids[$page][$longname])); 
}

public function htmlShortFromLong($longname,$page="") {
	global $qq;
	
	if ($page == "") {
		if (isset($this->registerpage)) {
			$page=$this->registerpage;
		} else if (isset($this->outputpage)) {
			$page=$this->outputpage;
		} else
			throw new exception("no context for short name lookup of html id: $longname");
	}
	if (!isset($this->htmlids[$page][$longname])) {
		$pages=implode(" ",array_keys($this->htmlids[$page]));
		infolog("errortrap","available pages: $pages");
		throw new exception("longname $longname does not exist in buildset $page.");
	}	
	return $qq['uselongnames'] ? $longname : $this->htmlids[$page][$longname][0];	
}

// returns modified selector.  long names converted to short
public function scanSelector($spec,$page='') {
	$output='';
	while (False !== ($i=strpos($spec,'#'))) {
		// gets anything leading up to # and # itself:
		$output .= substr($spec,0,$i+1);
		$spec=substr($spec,$i+1);
		// now get longname
		if (0 == preg_match('/^(\\w+)\\b/',$spec,$matches))
			throw new exception("illegal selector--# specified without legal long name");
		$longname=$matches[1];
		$spec=substr($spec,strlen($longname));
		$output .= $this->htmlShortFromLong($longname,$page);	
	}
	return $output;
}

const ENCODE_NONE = 0;
const ENCODE_BYTARGET = 1;
const ENCODE_BYSOURCE = 2;

// this function returns a qualified href.
public function hrefPrep($href,$bLink=True,$page="",$encodetype=self::ENCODE_BYTARGET) {
	global $qq;
	
	if ($href == '')
		return '#';
	if (substr($href,0,11) == 'javascript:')
		return $href;	// caller's responsibility
	
	// longname addresses are marked with two ##. translate them into short names
	if (preg_match('/^([^#]*)##([^\\/]+)(.*)$/',$href,$matches)) {
		$pagename=$matches[1];
		// because users can type in these hrefs, silently ignore the errors.
		try {
			$short=$this->htmlShortFromLong($matches[2],$pagename);
			$href=preg_replace('/^([^#]*)##([^\\/]+)(.*)$/','$1#'.$short.'$3',$href);
		} catch (Exception $e) {
			// just strip out the in-page marker
			$href=preg_replace('/^([^#]*)##([^\\/]+)(.*)$/','$1$3',$href);
		}	
	} else
		$pagename=$href;
		
	// in page links need no preparation:				
	if ($href[0] == '#')
		return $href;
						
	// check to see if this is an absolute url.  in the first case, look for more than one dot.
	if (False !== ($i=strpos($href,'.')) && False !== strpos($href,'.',$i+1)) {
		// this contains something like www.example.com  if it doesn't have the RFC flags, add them:
		if (!filter_var($href,FILTER_VALIDATE_URL,FILTER_FLAG_SCHEME_REQUIRED))
			$href='http://'.$href;
		// don't touch this one:	
		return $href;	
	}
	
	if (substr($href,0,1) == "/" || filter_var($href,FILTER_VALIDATE_URL,FILTER_FLAG_SCHEME_REQUIRED))
		return $href;

	$encoding='';
	if ($page == '' && isset($this->outputpage))
		$page=$this->outputpage;
	$bOnEncodedPage = ($page != '' && $this->isPageEncoded($page));

	if ($encodetype == self::ENCODE_BYTARGET) {
		// if pagename is blank, this is a plain link, like '#toc'.  But it must be the full page so we can see if it's encoded
		if ($pagename == '') {
			if ($page != '')
				throw new exception("no context for link fix of html id: $longname");
			$pagename=$page;
		}
		if ($this->isPageEncoded($pagename))
			$encoding=($qq['production']) ? 'https://'.$qq['domain'] : 'HTTP://'.$qq['domain'];
		else if ($bOnEncodedPage)
			$encoding='http://'.$qq['domain'];
			
	} else if ($encodetype == self::ENCODE_BYSOURCE) {
		if ($page == '')
			throw new exception("no context for link fix of html id: $longname");
		if ($bOnEncodedPage)
			$encoding=($qq['production']) ? 'https://'.$qq['domain'] : 'HTTP://LOCALHOST';
	}
	
	$prefix=$pagename != '' ? ($bLink ? $qq['hrefbase'] : $qq['srcbase']) : '';
	return $encoding.$prefix.$href;
}

// returns a url for a resource file from a simple file name
public function resourceFileURL($filename) {
	global $qq;
	return $this->hrefPrep("p/{$qq['project']}/{$qq['sourcedir']}/{$filename}",False,'',self::ENCODE_NONE);
}

// the purpose of this is to provide names for already registered ids
public function htmlLongShort($au,$tag) {
	$longname=$au->getLongName();
	if ($tag != "")
		$longname .= "_".$tag;
	return array($longname,$this->htmlShortFromLong($longname));	
}

// returns True if class has already been declared or False otherwise
public function declareClass($classname,$cycle=0) {
	global $qq;
	
	// duplicate declarations allowed for classes
	if (!isset($this->classes[$classname])) {
		$this->classes[$classname]=array($qq['uselongnames'] ? $classname : self::getAlphaLabel($this->classseq++),$cycle,0);
		return False;
	}
	return True;
}

// this function establishes the page that provides the context for which shortnames are used for html long names
public function setOutputPage($page) {
	if ($page == "")
		unset($this->outputpage);
	else
		$this->outputpage=$page;
}

// returns id and class string, only if necessary
public function idcstr($longname,$extraclass="") {
	global $qq;
	
	if (!isset($this->outputpage))
		throw new exception("cannot call idc until output page set"); 
	if (!isset($this->htmlids[$this->outputpage][$longname])) {
		infolog("errortrap","buildpage={$this->outputpage}");
		infolog_dump("errortrap","htmlids",$this->htmlids[$this->outputpage]);
		infolog_dump("errortrap","all htmlids",$this->htmlids);
		throw new exception("undeclared html id: $longname");
	}	
	list($short,$mask,$lock,$classes)=$this->htmlids[$this->outputpage][$longname];
	
	if ($qq['uselongnames'])
		$short=$longname;
	$retval=($lock != "") ? " id=\"$short\"" : "";

	$separator=($classes == '') ? '' : ' ';

	// reset all class counts
	foreach ($this->classes as &$classinfo) {
		$classinfo[2]=0;
	}
	$retval .= $this->cstr($classes.$separator.$extraclass);
	return $retval;
}

public function resetClassCount($long) {
	if (!isset($this->classes[$long]))
		throw new exception("undeclared class being reset:  $long");
	$this->classes[$long][2]=0;
}

public function bumpClassCount($long) {
	if (!isset($this->classes[$long]))
		throw new exception("undeclared class being reset:  $long");
	if ($this->classes[$long][1] > 1)
		$this->classes[$long][2] = ($this->classes[$long][2]+1) % $this->classes[$long][1];
	else
		$this->classes[$long][2]++;
}

public function classExists($long) {
	$retval=isset($this->classes[$long]);
	if (!$retval)
		infolog("warning","class $long does not exist!");
	return $retval;
}

public function cstr($classes) {
	$retval='';
	if ($classes != '') {
		$classarray=explode(" ",trim($classes));
		$between='';
		foreach ($classarray as $long) {
			// strip off a period if necessary
			if (substr($long,0,1) == ".")
				$long=$substr($long,1);
			if (!isset($this->classes[$long])) {
				infolog("errortrap","class string: $classes");
				infolog_dump("errortrap",'all classes',$this->classes);
				throw new exception("undeclared class being output:  $long");
			}	
			$classname=$this->classes[$long][0];
			// append modulo number if necessary
			if ($this->classes[$long][1] > 1) {
				// rotate through numbers 1...n
				$classname=$between.$this->classes[$long][0].($this->classes[$long][2]+1);
			} else if ($this->classes[$long][1] == 1) {
				// first item only gets class, until reset
				if ($this->classes[$long][2] == 0)
					$classname=$between.$this->classes[$long][0];
				else
					$classname="";	
			} else {
				// normal case:  just append the class name as it is
				$classname=$between.$this->classes[$long][0];
			}
			$retval .= $classname;
			$between=" ";	
		}
		if ($retval != "")
			$retval=" class=\"{$retval}\"";
	}
	return $retval;
}

public function getClassShort($longname) {
	$longname=ltrim($longname,".");
	$long=rtrim($longname,"0123456789");
	$suffix=$longname == $long ? "" : substr($longname,strlen($long));
	if (!isset($this->classes[$long]))
		throw new exception("undeclared class being output:  $long");
	$classname=$this->classes[$long][0];
	return $classname.$suffix;
}

/////// id functions //////////////////////////////////////////////////////////
// returns an alphanumeric label based on a sequence number
static function getAlphaLabel($seq) {
	// first character= a-p
	$short=chr(97+($seq % 16));
	$seq >>= 4;	
	// remaining characters, 0-9 or a-v
	while ($seq > 0) {
		$digit=$seq & 31;
		if ($digit <= 9)
			$short .= chr(48+$digit);
		else
			$short .= chr(87+$digit);
		$seq >>= 5;		
	}
	return $short;
}

// reverse of getAlphaLabel
static function seqFromAlphaLabel($label) {
	$seq=0;
	$multiplier=1;
	$bFirstDigit=True;
	for ($c=substr($label,0,1); $c != ""; $label=substr($label,1)) {
		$val=ord($c);
		if ($val > 48 && $val < 58) {
			// 0-9
			if ($bFirstDigit)
				throw new exception("illegal first digit in alpha label");
			$val -= 48;	
		} else if ($val > 96 && $val <123) {
			// a-z (actually less, depends on digit)
			$val -= 97;
			if ($bFirstDigit && $val > 15)
				throw new exception("illegal first digit in alpha label");
			else if ($val > 31)	
				throw new exception("illegal digit in alpha label");
			if (!$bFirstDigit)
				$val += 10;	
		} else
			throw new exception("illegal digit in alpha label");
		$seq += $val*$multiplier;
		if ($bFirstDigit) {
			$bFirstDigit=False;
			$multiplier *= 16;
		} else
			$multiplier *= 32;	
	}
	if ($bFirstDigit)
		throw new exception("illegal empty alpha label");
	return $seq;	 	
}

////////////////////////////////////////////// authorization functions

// declare a boolean auth value.  should be structured so superuser is always 1  
public function registerAuthBool($name,$desc,$defNew,$defAnon=False) {
	$this->registerAuthInt($name,$desc,0,1,$defAnon,$defNew,1);
}

// declare a page to need an https url
public function registerEncodedPage() {
	if (!isset($this->registerpage))
		throw new exception("declaration call is out of context for $longname");
	$this->encodedPages[$this->registerpage]=True;
	$this->bDirty=True;	
}

public function isPageEncoded($page='') {
	if ($page == '') {
		if (isset($this->outputpage))
			$page=$this->outputpage;
		else
			throw new exception('required page not set');
	}
	// encoded pages can have extra data.
	while (True){
		if (isset($this->encodedPages[$page]))
			return True;
		if (False === ($i=strrpos('/',$page)))
			break;
		$page=substr($page,0,$i);		
	}
	return False;		
}

// default is always for anonymous to not have access.
public function registerAuthPage($reason,$defNew,$defAnon=False) {
	if (!isset($this->registerpage))
		throw new exception("declaration call is out of context for $longname");
	// duplicate calls are allowed
	$bFound=False;
	foreach ($this->authpage as &$authpage) {
		if ($authpage[0] == $this->registerpage) {
			// list all the reasons the page is protected
			if (False === strpos($authpage[1],$reason)) {
				$reason=$authpage[1].", $reason";
				$authpage[1]=$reason;
			}	
			$bFound=True;
			break;
		}
	}
	$name="view_{$this->registerpage}";
	$desc="View {$this->registerpage} ($reason)";
	if ($bFound) {
		if (!isset($this->auth[$name])) {
			infolog_dump("errortrap","auth",$this->auth);
			throw new exception("$name should have been set in auth array but was not");
		}
		$this->auth[$name][0]=$desc;
		$this->bDirty=True;	
	} else {
		$this->authpage[]=array($this->registerpage,$reason);
		$this->registerAuthInt($name,$desc,0,1,(int)$defAnon,(int)$defNew,1);
		$this->bDirty=True;
	}	
}

// returns True is security check is needed on this page, or false otherwise
public function protectedPage($page) {
	foreach ($this->authpage as &$authpage) {
		if ($authpage[0] == $page) {
			return True;
		}	
	}
	return False;
}

public function getProtectedPages() {
	$retval=array();
	foreach ($this->authpage as $authpage)
		$retval[]=$authpage[0];
	return $retval;	
}

public function registerAuthInt($name,$desc,$min,$max,$defAnon,$defNew,$valSuper) {
	if (!isset($this->registerpage))
		throw new exception("declaration call is out of context for $longname");
		
	if (!isset($this->auth[$name])) {
		$this->auth[$name]=array($desc,$defAnon,$defNew,$valSuper,$min,$max);
		$this->bDirty=True;
	} else {
		if ($this->auth[$name][1] != $defAnon || $this->auth[$name][2] != $defNew || $this->auth[$name][3] != $valSuper || $this->auth[$name][4] != $min || $this->auth[$name][5] != $max)
			throw new exception("security item $name has already been registered with different values.  Detected on page ($this->registerpage)");
	}	
}

// declare a name as something that will be referenced.  This makes certain that the value will be available when the time comes
// call with name as (bool)True to make check of all names to make certain they have been declared.	
public function registerAuthRead($auname,$name) {
	static $names;
	if ($name === True) {
		if (isset($names)) {
			foreach ($names as $n) {
				if (!isset($this->auth[$n[1]]))
					throw new exception("authorization code name {$n[1]} was requested by au {$n[0]} but never registered");
			}
			unset($names);
		}
	} else {
		if (!isset($names))
			$names=array();
		$names[]=array($auname,$name);
	}	
}

// return array of authorization names
// returns:  array of name=>desc
public function getAuthNames() {
	$retval=array();
	foreach ($this->auth as $name=>$stuff) {
		$retval[$name]=$stuff[0];
	}
	return $retval;
}

// gets the value limits for an auth item.  returns in an array (min,max)
public function getAuthLimits($name) {
	if (!isset($this->auth[$name]))
		throw new exception("unregistered authorization item being queried.  name=$name");
	return array($this->auth[$name][4],$this->auth[$name][5]);	
}

const AUTHDEF_ANON = 1;
const AUTHDEF_NEWUSER = 2;
const AUTHDEF_SUPER = 3;
// returns array of name=>value
public function getAuthDefaults($level) {
	$retval=array();
	foreach ($this->auth as $name=>$stuff) {
		$retval[$name]=$stuff[$level];
	}
	return $retval;
}

/////////////////////////////////////////// profile functions

// if prompt is string, profile box maintains value; if it is blank, it is invisible; if type is 'user', prompt is callback for maintenance
public function registerProfile($name,$type,$prompt,$default) {
	if (!isset($this->registerpage))
		throw new exception("profile registration call is out of context for $name");
		
	if (isset($this->profile[$name])) {
		if ($type != $this->profile[$name][0] && $prompt != $this->profile[$name][1])
			throw new exception("profile for $name is already registered with a different type or prompt");
		// no need to re-register	
	} else {
		$this->profile[$name]=array($type,$prompt,$default);
		$this->bDirty=True;
	}		
}

// used by profile management
public function getFullProfile() {return $this->profile;}

// used by state function to initialize a profile for a new user
public function getDefaultProfile() {
	$retval=array();
	foreach ($this->profile as $name=>$profelem) {
		$retval[$name]= is_object($profelem[2]) ? clone $profelem[2] : $profelem[2];
	}	
	return $retval;	
}

////////////////////////////// end of class definition /////////////////////////
} ?>
