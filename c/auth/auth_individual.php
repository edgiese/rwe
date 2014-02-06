<?php if (FILEGEN != 1) die;
// authorization class for situations allowing only individual granularity
class auth_individual implements auth_interface {
///////////////////////////////////////////////////////////////////////////////
private $auth;		// array of name=>value	
	
function __construct() {
	$this->auth=array();
}
	
// basic authorization function returns a value based on a name.
public function checkAuth($name) {
	if (!isset($this->auth[$name])) {
		return False;
		global $qqi;
		infolog_dump("errortrap","qqi",$qqi);
		throw new exception("unregistered authorization code being queried: $name");
	}	
	return $this->auth[$name];	
}

// sets the user number or 0 for anonymous user or -1 for superuser
public function setAuthUser($iduser) {
	$this->auth=$this->read($iduser);	
}

///////////////// routines to support maintenance au:
public function read($iduser) {
	global $qqc,$qqi;
	
	if ($iduser == -1) {
		// these cannot be maintained.  complete au control:
		$retval=$qqi->getAuthDefaults(idstore::AUTHDEF_SUPER);
	} else if ($iduser == 0) {			
		// set defaults and update as necessary
		$retval=$qqi->getAuthDefaults(idstore::AUTHDEF_ANON);		

		// update defaults with stored default preferences for new users
		$rows=$qqc->getRows('auth/individual::read',-1,(int)$iduser);
		if ($rows !== False) {
			foreach ($rows as $row) {
				extract($row);	// sets name, value
				if (isset($retval[$name]))
					$retval[$name]=$value;
			}
		}	
	} else {
		// set defaults and update as necessary
		// new user is the default:
		$retval=$qqi->getAuthDefaults(idstore::AUTHDEF_NEWUSER);

		// update defaults with stored default preferences for new users
		$rows=$qqc->getRows('auth/individual::read',-1,idstore::AUTHDEF_NEWUSER);
		if ($rows !== False) {
			foreach ($rows as $row) {
				extract($row);	// sets name, value
				if (isset($retval[$name]))
					$retval[$name]=$value;
			}
		}	

		// update defaults with stored default preferences 
		$rows=$qqc->getRows('auth/individual::read',-1,(int)$iduser);
		if ($rows !== False) {
			foreach ($rows as $row) {
				extract($row);	// sets name, value
				if (isset($retval[$name]))
					$retval[$name]=$value;
			}
		}	
	}
	return $retval;
}

// updates a profile.  authorization profile for iduser may not exist, in which case this should create one
public function update($iduser,$name,$value) {
	global $qqc, $qqi;
	$iduser=(int)$iduser;
	$value=(int)$value;
	if ($iduser == idstore::AUTHDEF_SUPER)
		throw new exception("cannot update super user profile");
	// this will throw an exception for a missing name:	
	list($min,$max)=$qqi->getAuthLimits($name);
	if ($value < $min || $value > $max)
		throw new exception("set value for $name is $value, outside the range of $min to $max");	

	if ($qqc->getValue('auth/individual::pairexists',$iduser,$name))
		$qqc->act('auth/individual::update',$iduser,$name,$value);
	else
		$qqc->insert('auth/individual::create',$iduser,$name,$value);		
}

//////////////////////////////////////////// end of class definition	
} ?>
