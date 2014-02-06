<?php if (FILEGEN != 1) die;
// class definition file for the 'all miscellaneous' storage class.  use this one for profiles
// where there is no need to search profiles for individual fields.
class auth_allmisc implements auth_profstore {
////////////////////////////////////////////////////

// updates a profile.  profile for iduser may not exist, in which case this should create one
// updates a profile.  profile for iduser may not exist, in which case this should create one
public function update($iduser,$profile) {
	global $qqc;

	if ($iduser != 0 && $qqc->getValue('auth/allmisc::idexists',$iduser))
		$qqc->act('auth/allmisc::update',$iduser,serialize($profile));
	else {
		if ($iduser != 0) 
			$qqc->insert('auth/allmisc::createwithid',$iduser,serialize($profile));
		else	
			$iduser=$qqc->insert('auth/allmisc::create',serialize($profile));
	}
	return $iduser;			
}

// reads a profile.  iduser may not exist--in which case we should return a default profile
public function read($iduser) {
	global $qqc,$qqi;

	if ($qqc->getValue('auth/allmisc::idexists',$iduser)) {
		$retval=unserialize($qqc->getValue('auth/allmisc::read',$iduser));
	} else
		$retval=$qqi->getDefaultProfile();
		
	return $retval;			
}

//////////////////////////////// end of class definition
} ?>
