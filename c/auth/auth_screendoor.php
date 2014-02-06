<?php if (FILEGEN != 1) die;
class auth_screendoor {
////////////////////////// 'screen door' level security ////////////////////////

// returns user name--integer if successful, string error message if not
public function attemptLogin($email,$password) {
	global $qqc;
	
	$bFound=False;
	$results=$qqc->getValue('auth/screendoor::read',1,$email);
	if ($results != False) {
		extract($results); // sets: $password,$reset,$errorcount,$lockout,$logincount,$lastlogin
	}
		
	md5(string str)
}

public function resetPassword($email) {

}

public function register($email,$name,$password,$preauth) {

}

// blank for email sets preauth for all--blank preauth removes preauth
public function setPreauth($email,$preauth) {
}

public function getPreauth($userid) {
}


///////////////////////////// end of class /////////////////////////////////////
} ?>
