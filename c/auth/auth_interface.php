<?php if (FILEGEN != 1) die;
// interface file for authorization class
interface auth_interface {
	// basic authorization function returns a value based on a name.
	public function checkAuth($name);
	
	// sets the user number or 0 for anonymous user or -1 for superuser
	public function setAuthUser($iduser);
} ?>
