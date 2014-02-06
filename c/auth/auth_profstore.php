<?php if (FILEGEN != 1) die;
// interface file for profile storage class
interface auth_profstore {
	// updates a profile.  profile for iduser may not exist, in which case this should create one
	public function update($iduser,$profile);
	
	// reads a profile.  iduser may not exist--in which case we should return a default profile
	public function read($iduser);
	
} ?>
