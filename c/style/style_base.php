<?php if (FILEGEN != 1) die;
// base object for all stylers
class style_base {
	// used by the aggregator to determine whether this style is required for an html type:
	static function isRequired($html) {return True;}

	// processed codes for variables.  BITMASKS!!!!
	const PROC_NONE = 0;
	const PROC_SINGLE = 0x0001;
	const PROC_POSITION = 0x0002; 
	
	// does checks between styles and gets ready for output MUST RETURN PROC_SINGLE or more if anything is done!
	public function process($js,$browser,$render,$stype,$selector,$parent) {return self::PROC_NONE;} 
	
	// puts out css output based on values stored.  returns:
	// False : no output needed
	// array(key,value) : one value
	// array(array(key,value), ...) : more than one value
	public function cssOutput($browser) {return array("keyword","value");}
	
}
?>
