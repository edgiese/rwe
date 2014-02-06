<?php if (FILEGEN != 1) die;
class pmod_arrays {
////////////// pmod class to read in variables and arrays for options //////////
private $v;			// values indexed by tag
private $a;			// array of (array[index]=False or Default), indexed by tag

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
		
		if ($arrayEnd != '') {
			if ($ln == $arrayEnd) {
				$arrayEnd='';
				continue;
			}
			$separator=$ln[0];
			$ln=trim(substr($ln,1));
			if ($separator == '>')
				throw new exception("illegal array end--must match starting tag");
			$values=explode($separator,$ln);
			if (sizeof($values) > sizeof($varray))
				throw new exception("array has more elements than specification allows");
			if (sizeof($values) < sizeof($varray)) {
				$ix=0;
				foreach($varray as $index=>$default) {
					if (++$ix > sizeof($values)) {
						if ($default === False)
							throw new exception("index $index must have a value--no default specified");
						$values[]=$default;	
					}
				}
			}
			$this->v[$tag][]=$values;
		} else {
			// every line must have an '='
			if (False === ($epos=strpos($ln,'=')))
				throw new exception("syntax error:  missing =");
			$tag=trim(substr($ln,0,$epos));
			$value=trim(substr($ln,$epos+1));	
			if ($tag[0] == '>') {
				$arrayEnd=$tag;
				$tag=substr($tag,1);
			}
			if (isset($this->v[$tag]))
				throw new exception("$tag already defined");
			if ($arrayEnd == '')
				$this->v[$tag]=$value;
			else {
				$indices=explode(',',$value);
				$varray=array();
				$bDefaultSet=False;
				foreach ($indices as $index) {
					if (False === ($epos=strpos($index,'='))) {
						// required value
						if ($bDefaultSet)
							throw new exception("must specify a default for every value once a default value set");
						$varray[$index]=False;
					} else {
						$default=trim(substr($index,$epos+1));	
						$index=trim(substr($ln,0,$epos));
						$bDefaultSet=True;
						$varray[$index]=$default;
					}
				}
				$this->a[$tag]=$varray;
				$this->v[$tag]=array();
			} // if line started with '>'	
		} // if no array active
	} // loop for all lines in file
}

public function getValue($index,$default=Null) {
	if (!isset($this->v[$index])) {
		if ($default === Null)
			throw new exception("array pmod read: required value $index not set");
		return $default;
	}	
	if (!is_array($this->v[$index]))
		return $this->v[$index];
	if (!isset($this->a[$index]))
		throw new exception("internal error--array tag array not set");
			
	$retval=array();
	$ix=0;
	foreach ($this->v[$index] as $values) {
		$namedvalues=array();
		$ix=0;
		foreach ($this->a[$index] as $name=>$default)
			$namedvalues[$name]=$values[$ix++];
		$retval[]=$namedvalues;
	}
	return $retval;
}

//// end of class definition ///////////////////////////////////////////////////
} ?>
