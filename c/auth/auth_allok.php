<?php if (FILEGEN != 1) die;
// class definition file for authorization class
class auth_allok implements auth_interface {
////////////////////////////////////////////////////
	// basic authorization function returns a value based on a name.
	// note:  the function 'page_(pagename)' is reserved for page authorizations	
	public function checkAuth($name) {
		return True;
	}
	
	// sets the user number or 0 for anonymous user
	public function setAuthUser($iduser) {}
	
///////////////// routines to support maintenance au:
public function read($iduser) {
	global $qqi;
	
	$retval=$qqi->getAuthDefaults(idstore::AUTHDEF_SUPER);
	return $retval;
}

// updates a profile.  authorization profile for iduser may not exist, in which case this should create one
public function update($iduser,$name,$value) {
}
	
	
// end of class definition
} ?>
