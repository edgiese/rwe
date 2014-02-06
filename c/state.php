<?php if (FILEGEN != 1) die;
// class definition file for the state class--used to track state of session
class state {
///////////////////////////////////////////////////////////////////////////////
private $key;			// the db key used to save ourselves - an arbitrary md5 hash sent in a cookie
private $idlog;			// id of the log entry created
private $idlogpage;		// id of the log page entry created
private $bStated;		// if True, then browser connection is 'stated,' i.e. known to support states
private $browser;		// browser type.  see browser functions
private $user;			// id signed in user
private $transactions;	// array of transactions done in this session indexed seqentially: array(shortname,brother,status,key,au)
private $nexttransnumber;
private $render;		// render mode
private $bForceStateless;// if True, indicate to system that we are in stateless mode (debugging tool)
private $screendoorpass;// screen door password (for entire site)
private $profile;		// array of profile items
private $profstore;		// profile storage module
private $bProfileDirty;	// if true, profile has been changed this session and needs to be stored
private $auth;			// the authorization object
private $bAuthSet=False;	// flag to avoid needless loading of profile and authorization codes
private $editdata;		// array of edit data objects
private $sessiondata;	// array of session data items

const RENDER_GOOD = 0x0001;
const RENDER_BETTER = 0x0002;
const RENDER_BEST = 0x0004;
const RENDER_PRINT = 0x0008;

// state is instantiated once, and then if stated, restored.
function __construct($key) {
	global $qqu,$qq,$qqi,$qqc;

	$this->nexttransnumber=0;
	// TODO:  add full functionality
	$this->render=self::RENDER_BEST;
	
	$this->transactions=array();
	$this->profile=array();
	$this->bOutputNeeded=True;		// assumed that we will need to output
	$this->bStated=False;			// assume that we have not been cleared for stated transactions

	$this->screendoorpass='';		// default screen door password	
	
	// set up authorization
	$authname=isset($qq['authorizeclass']) ? $qq['authorizeclass'] : 'auth_allok'; 	
	if (!class_exists($authname))
		throw new exception("unknown authorization class {$qq['authorizeclass']}");
	$this->auth=new $authname();
	$this->setBrowser();
	$this->user=0;
	
	// set up profile
	$profname=isset($qq['profileclass']) ? $qq['profileclass'] : 'auth_allmisc'; 	
	if (!class_exists($profname))
		throw new exception("unknown profile storage class {$qq['profileclass']}");
	$this->profstore=new $profname();
	
	$this->editdata=array();
	$this->sessiondata=array();
	$referer=(isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER'] != '') ? $_SERVER['HTTP_REFERER'] : '(unknown)';
	$language=(isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) && $_SERVER['HTTP_ACCEPT_LANGUAGE'] != '') ? $_SERVER['HTTP_ACCEPT_LANGUAGE'] : '(unknown)';
	$ua=(isset($_SERVER['HTTP_USER_AGENT']) && $_SERVER['HTTP_USER_AGENT'] != '') ? $_SERVER['HTTP_USER_AGENT'] : '(unknown)';
	$address=(isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] != '') ? $_SERVER['REMOTE_ADDR'] : '(unknown)';
	$this->idlog=$qqc->insert('util::newlog',$address,$language,$referer,$ua);
		
	$this->key=$key;
	$qqu->state_save($this,$key,3600);
}

public function getStateCookie() {return $this->key;}

public function logPage($page,$pageextra) {
	global $qqc;
	$qqc->insert('util::logpage',$this->idlog,$page,$pageextra);
}

public function logException($e) {
	global $qqc;
	$qqc->insert('util::logexception',$this->idlog,$e->getMessage(),$e->getFile(),$e->getLine(),$e->getTraceAsString());
}

public function logEvent($type,$data) {
	global $qqc;
	$qqc->insert('util::logevent',$this->idlog,$type,$data);
}

public function setStated() {
	$this->bStated=True;
}

public function forceRender($renderstring) {
	if (!is_numeric($renderstring))
		return 'Not set:  must be a numeric';
	$render=(int)$renderstring;
	if ($render < 0 || $render > 4 || $render == 3)
		return 'Not set:  must be 0,1,2,or 4';
	if ($render == 0) {
		$this->bForceStateless=True;
		$message='Set: forced stateless, render 1';
		$render=1;
	} else {
		$this->bForceStateless=False;
		$message="Set:  render $render";
	}	
	$this->render=$render;
	return $message;	 	
}

public function getScreenDoorPass() {return $this->screendoorpass;}
public function setScreenDoorPass($newpass) {$this->screendoorpass=$newpass;}
public function isStated() { return (!$this->bForceStateless && $this->bStated);}
public function getRender() {return $this->render;}
public function isJsOK() {return $this->render != self::RENDER_GOOD;}
public function isBigBitmapOK() {return $this->render == self::RENDER_BEST;}

//////// session data functions
public function setSessionData($name,$data) {
	$this->sessiondata[$name]=$data;
}
public function sessionDataExists($name) {return isset($this->sessiondata[$name]);}
public function getSessionData($name) {
	if (!isset($this->sessiondata[$name]))
		throw new exception("unset session data $name being queried");
	return $this->sessiondata[$name];	
}
public function clearSessionData($name) {
	if (isset($this->sessiondata[$name])) {
		unset($this->sessiondata[$name]);
	}	
}

//////// profile and signin functions and authorization functions

public function getProfile($name) {
	if (!$this->bAuthSet)
		throw new exception("profile being queried when it is not available");
	if (!isset($this->profile[$name])) {
		infolog_dump("errortrap","profile",$this->profile);
		throw new exception("unregistered profile item: $name");
	}		
	return $this->profile[$name];
}

public function setProfile($name,$value,$bWrite=False) {
	if (!$this->bAuthSet)
		throw new exception("profile being set when it is not available");
	if (!isset($this->profile[$name]))
		throw new exception("unregistered profile item: $name");	
	$this->profile[$name]=$value;
	if ($bWrite && $this->user != 0) {
		$this->profstore->update($this->user,$this->profile);
		$this->bProfileDirty=False;
	} else
		$this->bProfileDirty=True;
}

// returns array of ids or False if none
public function lookupProfile($args) {
	return $this->profstore->lookup($args);
}

public function getUserProfile($iduser,$name) {
	if (!$this->bAuthSet)
		throw new exception("profile being queried when it is not available");
	$profile=$this->profstore->read((int)$iduser);
	if (!isset($profile[$name])) {
		infolog_dump("errortrap","profile",$profile);
		throw new exception("profile item $name not found for user $userid");
	}		
	return $profile[$name];
}

// returns id of a profile for a newly registered user
public function createNewProfile() {
	global $qqi;
	
	if (!$this->bAuthSet)
		throw new exception("profile being created when it is not defined");
	return $this->profstore->update(0,$qqi->getDefaultProfile());
}

public function setUserProfile($iduser,$name,$value) {
	if (!$this->bAuthSet)
		throw new exception("profile being set when it is not available");
	$profile=$this->profstore->read($iduser);
	if (!isset($profile[$name]))
		throw new exception("profile item $name not found for user $userid");
	$profile[$name]=$value;
	$this->profstore->update((int)$iduser,$profile);
}


// 0 is signout, and -1 is superuser signin
public function signin($iduser) {
	global $qqi;
	
	if (!$this->bAuthSet)
		throw new exception("authorization being queried when it is not available");
	
	$this->user=$iduser;
	$this->auth->setAuthUser($iduser);
	$defaultprofile=$qqi->getDefaultProfile();
	if ($iduser == 0)
		$this->profile=$defaultprofile;
	else {	
		// stored user profiles don't always have all the latest profile stuff, particularly in development.
		// merge a stored profile with a default profile.
		$profile=$this->profstore->read($iduser);
		foreach ($defaultprofile as $name=>$value) {
			if (!isset($profile[$name]))
				$profile[$name]=$value;
		}
		$this->profile=$profile;
	}	
}

public function resetAuth() {$this->bAuthSet=False;}
// re-initializes after authorization codes are known to be available or when initializing state
public function initAuth() {
	if (!$this->bAuthSet) {
		$this->bAuthSet=True;
		$this->signin($this->user);
	}	
}

public function getAuthObject() {return $this->auth;}

public function checkAuth($name) {
	if (!$this->bAuthSet)
		throw new exception("authorization being queried when it is not available");
	return $this->auth->checkAuth($name);
}

public function checkPageAuth($page) {
	global $qqi;
	if (!$this->bAuthSet)
		throw new exception("authorization being queried when it is not available");
	
	if (!$qqi->protectedPage($page))
		return True;
		
	return $this->auth->checkAuth("view_$page");
}


public function getUser() {return $this->user;}

//////////////////// Transaction functions
const TIX_ORIGIN = 0;
const TIX_STATUS = 1;
const TIX_DATA = 2;

const TRANS_ACTIVE = 0;
const TRANS_FINISHED = 1;

public function beginTransaction($originUri) {
	$retval=++$this->nexttransnumber;
	$this->transactions[$retval]=array($originUri,self::TRANS_ACTIVE,array());
	return $retval;
}

public function isTransactionFinished($transaction) {
	if (!isset($this->transactions[$transaction]))
		throw new exception("undefined transaction $transaction");
	return $this->transactions[$transaction][self::TIX_STATUS];	
}

public function setTransactionData($transaction,$dataname,$vals) {
	if (!isset($this->transactions[$transaction]))
		throw new exception("undefined transaction $transaction");
	$this->transactions[$transaction][self::TIX_DATA][$dataname]=$vals;	
}

public function getTransactionData($transaction,$dataname) {
	if (!isset($this->transactions[$transaction])) {
		infolog_dump("errortrap","transactions",$this->transactions);
		throw new exception("undefined transaction $transaction");
	}	
	if (!isset($this->transactions[$transaction][self::TIX_DATA][$dataname])) {
		infolog_dump("errortrap","transaction",$this->transactions[$transaction]);
		throw new exception("undefined data requested. transaction=$transaction dataname=$dataname");
	}		
	return $this->transactions[$transaction][self::TIX_DATA][$dataname];	
}

public function transactionOriginUri($transaction) {
	if (!isset($this->transactions[$transaction]))
		throw new exception("undefined transaction $transaction");
	return $this->transactions[$transaction][self::TIX_ORIGIN];	
}

public function finishTransaction($transaction) {
	if (!isset($this->transactions[$transaction]))
		throw new exception("undefined transaction $transaction");
	$this->transactions[$transaction][self::TIX_STATUS]=self::TRANS_FINISHED;	
}

////////////////////////////// edit data functions
public function saveEditData($aushort,$ed) {
	global $qqu;
	
	$this->editdata[$aushort]=$ed;
	$qqu->state_update($this->key,$this);
}

public function getEditData($aushort) {
	return isset($this->editdata[$aushort]) ? $this->editdata[$aushort] : False; 
}

public function deleteEditData($aushort) {
	if (isset($this->editdata[$aushort]))
		unset($this->editdata[$aushort]);
}

////////////////////////////////////////////// browser related functions
// Figure out what browser is used, its version and the platform it is running on. Hat tip:  Daniel Frechette (from php.net get_browser comments)
// The following code was ported in part from JQuery v1.3.1
private static function detectBrowser() {
    $userAgent = strtolower($_SERVER['HTTP_USER_AGENT']);

    // Identify the browser. Check Opera and Safari first in case of spoof. Let Google Chrome be identified as Safari.
    if (preg_match('/opera/', $userAgent)) {
        $name = 'opera';
    }
    elseif (preg_match('/webkit/', $userAgent)) {
        $name = 'safari';
    }
    elseif (preg_match('/msie/', $userAgent)) {
        $name = 'msie';
    }
    elseif (preg_match('/mozilla/', $userAgent) && !preg_match('/compatible/', $userAgent)) {
        $name = 'mozilla';
    }
    else {
        $name = 'unrecognized';
    }

    // What version?
    if (preg_match('/.+(?:rv|it|ra|ie)[\/: ]([\d.]+)/', $userAgent, $matches)) {
        $version = $matches[1];
    }
    else {
        $version = 'unknown';
    }

    // Running on what platform?
    if (preg_match('/linux/', $userAgent)) {
        $platform = 'linux';
    }
    elseif (preg_match('/macintosh|mac os x/', $userAgent)) {
        $platform = 'mac';
    }
    elseif (preg_match('/windows|win32/', $userAgent)) {
        $platform = 'windows';
    }
    else {
        $platform = 'unrecognized';
    }

    return array(
        'name'      => $name,
        'version'   => $version,
        'platform'  => $platform,
        'userAgent' => $userAgent
    );
}

public function getBrowser() {return $this->browser;}

const BROWSER_DEFAULT = 0;
const BROWSER_DEFAULT_MS = 1;

// sets browser by looking at request
private function setBrowser() {
	$browser=self::detectBrowser();
	if ($browser['name'] == 'msie')
		$this->browser=self::BROWSER_DEFAULT_MS;
	else	
		$this->browser=self::BROWSER_DEFAULT;
}

// note-value 0 reserved for none
const QUESTION_NONE = 0;
const QUESTION_OPACITYBYFILTER = 1;
const QUESTION_TRANSPARENTGIFFILTER = 2;
const QUESTION_CSSTYPE = 3;
const QUESTION_TRANSPARENTBYOPACITY = 4;

// css file types
const CSS_GENERAL_MOZILLA = 1;
const CSS_GENERAL_MSIE = 2;

// answers a question about a browser.  this is an inherently ugly function and
// needs to be expanded at some point to allow a cleaner expansion mechanism
public function answerBrowserQuestion($question,$browser=Null) {
	if ($browser == Null)
		$browser=$this->browser;
	
	switch ($question) {
		case self::QUESTION_OPACITYBYFILTER:
			$retval=($browser == self::BROWSER_DEFAULT_MS) ? True : False; 
		break;
		
		// for MSIE, transparent divs need to have a color in them and zero opacity
		case self::QUESTION_TRANSPARENTBYOPACITY:
			$retval=($browser == self::BROWSER_DEFAULT_MS) ? True : False; 
		break;
		
		case self::QUESTION_CSSTYPE:
			$csstypes=array(
				self::BROWSER_DEFAULT_MS=>self::CSS_GENERAL_MSIE,
				// last line:
				self::BROWSER_DEFAULT=>self::CSS_GENERAL_MOZILLA
			);
			if (!isset($csstypes[$browser]))
				$retval=self::CSS_GENERAL_MOZILLA;
			else	
				$retval=$csstypes[$browser]; 
		break;
		
		default:
			throw new exception("unknown browser capability question: $question");
		break;
	}	
		
	return $retval;
}

// enumerates all possible browser questions.  returns array of text=>value
static function enumerateBrowserQuestions() {
	return array(
		'needs_filter_for_transparent_gif'=>self::QUESTION_TRANSPARENTGIFFILTER,
		'uses_filter_for_opacity'=>self::QUESTION_OPACITYBYFILTER,
		'transparent_div_needs_fill'=>self::QUESTION_TRANSPARENTBYOPACITY,
		'none'=>self::QUESTION_NONE // LAST ONE
	);
}

////////////////////////////////////////////////////////////////	
public function saveState() {
	global $qqu;

	if ($this->bProfileDirty) {
		if ($this->user != 0)	
			$this->profstore->update($this->user,$this->profile);
		$this->bProfileDirty=False;
	}
	$qqu->state_update($this->key,$this);
}

static function getRev() {return 0;}

//////////////////////////////////////////////////////// end of class //////////
}?>
