<?php if (FILEGEN != 1) die;
// class definition file for the 'email and name' storage class.  use this one for profiles
// where userids must be searchable by email or name.
class auth_emailname implements auth_profstore {
////////////////////////////////////////////////////

// updates a profile.  profile for iduser may not exist, in which case this should create one
public function update($iduser,$profile) {
	global $qqc;

	if (!isset($profile['name']) || !isset($profile['email']))
		throw new exception("required profile fields do not exist");
	$name=$profile['name'];	
	$email=$profile['email'];
	unset($profile['name']);	
	unset($profile['email']);	

	if ($iduser != 0 && $qqc->getValue('auth/emailname::idexists',$iduser))
		$qqc->act('auth/emailname::update',$iduser,$name,$email,serialize($profile));
	else {
		if ($iduser != 0) 
			$qqc->insert('auth/emailname::createwithid',$iduser,$name,$email,serialize($profile));
		else	
			$iduser=(int)$qqc->insert('auth/emailname::create',$name,$email,serialize($profile));
	}
	return $iduser;			
}

// reads a profile.  iduser may not exist--in which case we should return a default profile
public function read($iduser) {
	global $qqc,$qqi;

	if ($qqc->getValue('auth/emailname::idexists',$iduser)) {
		$row=$qqc->getValue('auth/emailname::read',$iduser);
		$retval=unserialize($row['profile']);
		$retval['name']=$row['name'];
		$retval['email']=$row['email'];
	} else
		$retval=$qqi->getDefaultProfile();
		
	return $retval;			
}

public function lookup($args) {
	global $qqc;
	
	$type=0;
	if (isset($args['name'])) {
		$type += 1;
		$name=$args['name'];
		unset($args['name']);
	}
	if (isset($args['email'])) {
		$type += 2;
		$email=$args['email'];
		unset($args['email']);
	}
	if (sizeof($args) > 0) {
		infolog_dump("errortrap","bad args",$args);
		throw new exception("unknown arguments in emailname lookup");
	}	
	switch ($type) {
		case 0:
			$ids=$qqc->getCols('auth/emailname::allids',-1);
		break;
		
		case 1:
			$ids=$qqc->getCols('auth/emailname::idsbyname',-1,$name);
		break;

		case 2:
			$ids=$qqc->getCols('auth/emailname::idsbyemail',-1,$email);
		break;

		case 3:
			$ids=$qqc->getCols('auth/emailname::idsbynameandemail',-1,$name,$email);
		break;

		default:
			throw new exception("illegal lookup type: $type");
		break;
	}
	return $ids;				
}

//////////////////////////////// end of class definition
} ?>
