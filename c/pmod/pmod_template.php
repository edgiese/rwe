<?php if (FILEGEN != 1) die;
class pmod_arrays {
////////////// pmod class to read in variables and arrays for options //////////

function __construct($lines) {
	global $qqu;

	$this->v=array();
	$arrayEnd='';
	foreach ($lines as $line) {
		echo htmlentities($line)."<br />";
		$ln=trim($line);
		if ($ln == "" || substr($ln,0,1) == ";")
			continue;		// ignore blank and comment
		if (($i=strpos($ln,' ;')) !== False) {
			// strip off inline comment
			$ln=trim(substr($ln,0,$i));
		}

		// process line
		
	} // loop for all lines in file
	
}



//// end of class definition ///////////////////////////////////////////////////
} ?>
