<?php



public function buildStyleFiles() {
	global $qqs,$qq,$qqc;
	
	if (isset($this->css)) {
		$filename=$this->getStyleFileName($qqs->getRender(),"css");
		$this->css->buildStyleFiles($this,$this->pgpalette,$this->pageid,&$this->htmlids,$filename);
		unset($this->css);
		// this process should have completed building arrays of snippets, etc, so put them out
		$this->buildstaticjs();
		
		if ($qq['noidcache'])
			return;
		
		if ($this->pkgcode == self::STATED) {
			$intkey=$this->savedau->getState();
			$charkey=get_class($this->savedau);
		} else if ($this->pkgcode == self::PREFAB) {
			$intkey=$this->prefabcode;
			$charkey="";
		} else if ($this->pkgcode == self::BASE) {
			$intkey=$this->pageid;
			$charkey="";
		}
		$qqc->act("clearidcache",$intkey,$charkey,$render);
		$qqc->insert("idcache",$intkey,$charkey,$render,serialize($this->aushortnames),serialize($this->htmlids),serialize($this->js));
	}
}	


/////// javascript-related functions ///////////////////////////////////////////

// adds a named code snippet as an event listener to an object
public function addeventjs($longname,$event,$snippet,$args) {
	if (!isset($this->staticjs))
		throw new exception("cannot add static js--file already created.  dest: $destination snippet: $snippet");
	$this->staticjs[]=array(":".$longname.":".$event,$snippet,$args);			
}

// adds a named code snippet to an object in the static js file
public function addobjectjs($longname,$snippet,$args) {
	if (!isset($this->staticjs))
		throw new exception("cannot add static js--file already created.  dest: $destination snippet: $snippet");
	$this->staticjs[]=array(":".$longname.":",$snippet,$args);			
}

// adds a code snippet to a function
public function addstaticjs($destfunc,$snippet,$args) {
	if (!isset($this->staticjs))
		throw new exception("cannot add static js--file already created.  dest: $destfunc snippet: $snippet");
	$this->staticjs[]=array($destfunc,$snippet,$args);			
}

// adds a piece of code directly to the static js file
public function addstaticjscode($destfunc,$code) {
	if (!isset($this->staticjs))
		throw new exception("cannot add static js--file already created.  function: $destfunc");
	$this->staticjs[]=array($destfunc,$code,"*code*");			
} 

// sort callback sorts by destinations, keeping indeces stable
public function cmpjs($a,$b) {
	$adest=$a[0];
	$bdest=$b[0];
	$aix=$a[3];
	$bix=$b[3];
	if ($adest == $bdest)
		return $aix > $bix ? +1 : -1;
	return $adest > $bdest ? +1 : -1;	
}

// builds an array of lines and of libraries required for a set of javascript snippet requests
private function buildjs($jsinfo) {
	$libs=array();
	$lines=array();
	// add lines to make certain that bs and be are both defined no matter what
	$jsinfo[]=array("bs","\n","*code*");
	$jsinfo[]=array("be","\n","*code*");
	// sort info by destination, maintaining order of addition within sort
	// first, add index into array item to facilitate comparison
	$i=0;
	foreach($jsinfo as &$info)
		$info[]=$i++;
	usort($jsinfo,array(&$this,"cmpjs"));	
	
	// build snippet file index
	$snippets=file("j/src/collection1.js");
	$snipdata=array();
	$ix=-1;
	foreach($snippets as $line) {
		++$ix;
		$first3=substr($line,0,3);
		if (substr($line,0,3) != "//-")
			continue;
		$first4=substr($line,0,4);
		if ($first4 == "//-!") {
			$snipdata[trim(substr($line,4))]=$ix;
		}	
	}
	
	// TODO:  pre-loop through and expand all dependencies.  maybe needs to be
	// recursive, definitely needs to be able to pass on destination information some how
	
	// loop through all requests and convert them to direct function types
	for ($ix=0; $ix<sizeof($jsinfo); ++$ix) {
		list($destination,$snippet,$args,$dummy)=$jsinfo[$ix];
		
		// convert all destinations to functions
		if (substr($destination,0,1) == ":") {
			list($obj,$event)=explode(":",substr($destination,1));
			// TODO:  finish this code
		}
		// convert all snippet calls to code inserts
		if ($args != "*code*") {
			if (!isset($snipdata[$snippet]))
				throw new exception("unknown snippet requested:  $snippet");
			// allow single argument to earlier functions to not be put in an array
			if (!is_array($args))
				$args=array("only1"=>$args);
				
			$code="";
			$search=array();	// parameters searched for
			$ixs=$snipdata[$snippet]+1;
			$bStartFound=False;
			$bEndFound=False;
			while ($ixs < sizeof($snippets)) {
				$line=$snippets[$ixs++];
				if (substr($line,0,3) == "//-") {
					if ($bStartFound)
						throw new exception("directive comment found inside code:  snippet $snippet");
					$type=substr($line,3,1);
					$data=substr($line,4);
					if ($type == ":") {
						// parameter substitution
						list($index,$searchterm,$comment)=explode(" ",$data,3);
						if (!isset($args[$index]))
							throw new exception("missing parameter '$index' in snippet $snippet");
						$search[$index]=$searchterm;	
					} else if ($type == "+") {
						// snippet dependency.  nothing to do here, should be taken care of already
					} else if ($type == "<") {
						// library dependency
						$lib="j/".$data;
						if (False === (array_search($lib,$libs)))
							$libs[]=$lib;
					} else
						throw new exception("unknown snippet directive type $type in snippet $snippet");
					continue;		
				} // if this was a directive comment
				if (!$bStartFound) {
					if (substr($line,0,2) == "//")
						continue;
					if (0 != substr_compare($line,"function",0,8))
						throw new exception("missing or illegal function declaration in snippet $snippet");
					$bStartFound=True;
					$data=trim(substr($line,8));
					// no arguments are allowed here.  these "functions" are just wrappers for code to be written into a function
					if (0 != substr_compare($data,"()",0,2) || "{" != trim(substr($data,2)))
						throw new exception("illegal arguments or syntax error in dummy function declaration in snippet $snippet");
					// set up substitution arrays
					ksort($args);
					ksort($search);
					if (sizeof($args) != sizeof($search)) {
						throw new exception("unmatched parameters in snippet $snippet");
					}	
				} else {
					// note that this approach means that snippets must add a comment or something to lines like this:
					if (rtrim($line) == "}") {
						$bEndFound=True;
						break;
					}
					// add another line to the block
					// NOTE:  here is where you can strip comments, etc.
					$code .= str_replace($search,$args,$line);
				}
			} // loop through all lines in snippet section
			if (!$bEndFound)
				throw new exception("js snippet $snippet has no end marker");
			// translate in snippet
			$jsinfo[$ix][2]="*code*";
			$jsinfo[$ix][1]=$code;	
		}
	}
	
	// sort the array again so that revised destinations will be together
	usort($jsinfo,array(&$this,"cmpjs"));

	// write out the lines
	$lastfunction="";
	for ($i=0; $i<sizeof($jsinfo); ++$i) {
		list($function,$code,$marker,$ix)=$jsinfo[$i];
		if ($lastfunction != $function) {
			if ($lastfunction != "")
				$lines[]="}\n";
			$lines[]="function $function() {\n";
			$lastfunction=$function;	
		}
		$lines[]=$code;
	}
	if ($lastfunction != "")
		$lines[]="}\n";
	return array($lines,$libs);
}


// builds static javascript file.  returns array of required libraries
public function buildstaticjs() {
	global $qqs;

	list($jslines,$jslibs)=$this->buildjs($this->staticjs);	
	file_put_contents($this->getStyleFileName($qqs->getRender(),"js"),$jslines);
	return $jslibs;
}


// allows low-level access to styles by aus.  html can be tag or * for any/all elements
public function styleByLong($longname,$html,$classname,$formatstring) {
	if (!isset($this->htmlids[$longname]))
		throw new exception("attempting to set style for unknown id longname=$longname");
	if (!isset($this->css))
		throw new exception("cannot set style for id $longname.  style file already built");
	$this->css->styleByLong($longname,$html,$classname,$formatstring);	
}


/// style functions ////////////////////////////////////////////////////////////


// helper function used by outputHead and buildStyleFiles
private function getStyleFileName($render,$type) {
	if ($this->pkgcode == self::STATED) {
		$middle=get_class($this->savedau)."-".$this->savedau->getState();
	} else if ($this->pkgcode == self::PREFAB) {
		$middle="c".$this->prefabcode;
	} else if ($this->pkgcode == self::BASE) {
		$middle="p".$this->pageid;
	}
	$directory=($type == "css") ? "s/" : "j/";
	return "{$directory}t{$render}{$middle}.{$type}";		
}








?>
