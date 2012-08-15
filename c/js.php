<?php if (FILEGEN != 1) die;
class js {
/////////////////////// javascript control and cache object ////////////////////
private $srets;					// array of [statickey]=>array(needBS,needBE,filenames) for static data
private $filecache;				// lines of files of js source code being read to build snippets
private $dependencies;			// files we've loaded snippets from
private $dynamic;				// array of snipdata for the most recent call--never stored in cache, just a workspace
private $currentPage;			// current page that js is being added for

function __construct($pagedef=Null,$project='') {
	$this->filecache=array();
	$this->dependencies=array();
	$this->dynamic=array();
	$this->srets=array();

	if ($pagedef != Null) {
		// delete all cached js files from generated directory
		$files=scandir("g");
		$plen=strlen($project);
		foreach ($files as $file) {
			if (substr($file,0,$plen) == $project && substr($file,-3) == ".js")
			unlink("g/".$file);
		}
	}
}

// returns True if source .js files are newer than this object's stored static js
public function areFilesNewer($objdate) {
	foreach ($this->dependencies as $fname) {
		if (filemtime($fname) > $objdate)
			return True;
	}
	return False;
} 

private $bDirty=True;		// if True, this object has changed and needs to be stored 
public function isDirty() {return $this->bDirty;}
public function setCurrentPage($page) {$this->currentPage=$page;}

public function setClean() {
	// clear this out to save space
	$this->filecache=array();
	
	$this->bDirty=False;
}

// returns the dynamic snippet array.  used by stylegen to remember style snippets for re-use
public function getDynamicSnippets() {return $this->dynamic;}

// returns an array(bsneeded,beneeded,file names).  could be empty if no js is required
public function getFileNames($stylegen=Null,$render=0,$browser=0,$page='') {
	global $qq,$qqs;
	
	if ($render == state::RENDER_GOOD) {
		// at render level 1, js is enforced nonexistent.  if level 1 isn't deliberate (unstated call), this file will cause a refresh:
		if (!$qqs->isStated())
			return array(True,False,array("j/lib/hello.js"));
		else
			return array(False,False,array());	
	}

	if ($stylegen == Null) {
		// this is a dynamic-only call
		$sret=array(False,False,array());
	} else {	
		// see if we need to create a static file
		$project=$qq['project'];
		$statickey=$stylegen->getStaticKey($render,$browser,$page);
		$filename="g/$project-$statickey.js";
	
		if (!file_exists($filename) || !isset($this->srets[$statickey])) {
			// static file must be generated
			global $qqp;
			
			// first, save any dynamic js that aus have generated:
			$saveDynamic=$this->dynamic;
			$savePage=$this->currentPage;	// probably unnecessary
			
			$this->dynamic=array();
			$this->currentPage=$page;			
			$recipe=$qqp->getBuildList($page);
			$bodyau=au_base::treeFromRecipe($recipe,$render);
			for ($au=$bodyau; $au != Null; $au=$au->getNextAU()) {
				$au->declarestaticjs($this);
			}	
			unset($bodyau);	
			$stylesnips=$stylegen->getStyleSnippets($statickey);
			if (0 == sizeof($stylesnips) && 0 == sizeof($this->dynamic)) {
				// there is no static js data for this page.  no file needed
				$sret=array(False,False,array());
			} else {
				// this file must be created and written
				list($lines,$sret)=$this->buildjs(array_merge($this->dynamic,$stylesnips),$page);
				// add the filename of the static js:
				$sret[2][]=$filename;
				// now write out the file
				file_put_contents($filename,implode("\n",$lines));
			} // building static file
			$this->srets[$statickey]=$sret;
			$this->bDirty=True;
			
			$this->dynamic=$saveDynamic;
			$this->currentPage=$savePage;
		} else
			$sret=$this->srets[$statickey];
	}

	// find lines for the dynamic js if necessary
	if (0 < sizeof($this->dynamic)) {
		// create the lines
		list($lines,$dret)=$this->buildjs($this->dynamic,$page);
		// we're done with these--they have to be rebuilt each time
		$this->dynamic=array();
	} else {
		$dret=array(False,False,array());
		$lines=array();
	}
	// merge file names, but eliminate any duplicate library requests
	$filenames=array();
	foreach ($sret[2] as $filename) {
		if (False === array_search($filename,$filenames))
			$filenames[]=$filename;
	}
	foreach ($dret[2] as $filename) {
		if (False === array_search($filename,$filenames))
			$filenames[]=$filename;
	}
	return array($sret[0] || $dret[0],$sret[1] || $dret[1],$filenames,$lines);
}

// adds a named code snippet as an event listener to an object
public function addeventjs($longname,$event,$snippet,$args=array()) {
	global $qqi;
	$qqi->lockHTMLid($longname,'j',$this->currentPage);
	$snipdata=array(":$longname:*$event",$snippet,$args);
	$this->dynamic[]=$snipdata;				
}

// scans a snippet for dependencies if it will be included later, i.e., in ajax call
public function scanjsdependencies($snippet,$args=array()) {
	$snipdata=array("!",$snippet,$args);
	$this->dynamic[]=$snipdata;				
}

// adds a named code snippet to an object in the js file
public function addobjectjs($longname,$methodname,$snippet,$args=array()) {
	global $qqi;
	
	$qqi->lockHTMLid($longname,'j',$this->currentPage);
	$snipdata=array(":$longname:$methodname",$snippet,$args);
	$this->dynamic[]=$snipdata;				
}

// adds a code snippet to a function
public function addjs($destfunc,$snippet,$args=array()) {
	$snipdata=array($destfunc,$snippet,$args);
	$this->dynamic[]=$snipdata;				
}

// adds a piece of code directly to the static js file
public function addjscode($destfunc,$code) {
	$snipdata=array($destfunc,$code,"*code*");
	$this->dynamic[]=$snipdata;				
} 

// sets up a basic array of global parameters used for snippet calls
public function seedArgs($au) {
	global $qq,$qqi;
	if (!isset($this->currentPage))
		throw new exception("page never initialized for javascript");
	
	if ($qqi->isPageEncoded($this->currentPage)) {
		$encoding= $qq['production'] ? 'https://'.$qq['domain'] : 'HTTP://'.$qq['domain'];
	} else
		$encoding='';
	return array(
		'QQpage'=>$this->currentPage,
		'QQaulong'=>$au->getLongName(),
		'QQaushort'=>$au->getShortName(),
		'QQproject'=>$qq['project'],
		'QQsrcbase'=>$qq['srcbase'],
		'QQhrefbase'=>$qq['hrefbase'],
		'QQdomain'=>$qq['domain'],
		'QQencoding'=>$encoding
	);	
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

// converts a snippet to code.  returns code.  outside this file, leave off all but first two args
public function snippetToCode($sname,$args,$page='',$objshort='',$libs=Null,$extrasnips=Null,$destination='') {
	global $qq,$qqi;

	// look for a filename in the snippet
	if (False != ($i=strpos($sname,"::"))) {
		// filename found
		$fname=substr($sname,0,$i);
		$sname=substr($sname,$i+2);
	} else
		$fname="default";
	
	if (False === strpos($fname,"/"))
		$fname="j/src/".$fname;
	if (False === strpos($fname,"."))
		$fname .= ".js";
	
	if (!isset($this->filecache[$fname])) {
		$snippets=file($fname);
		// accumulate file dependencies so we can check dates
		if (False === array_search($fname,$this->dependencies))
			$this->dependencies[]=$fname;
			
		if ($snippets === False)
			throw new exception("snippet file not found:  $fname");
		$nocomments=array();
		$snipdata=array();
		$ix=0;
		foreach($snippets as $line) {
			$line=rtrim($line);
			$first2=substr($line,0,2);
			$first3=substr($line,0,3);
			$first4=substr($line,0,4);
			if ($first4 == "//-!") {
				$snipdata[trim(substr($line,4))]=$ix;
			} else if ($line == "" || ($first2 == "//" && $first3 != "//-"))
				continue;
			$nocomments[]=$line;	 	
			++$ix;
		}
		$this->filecache[$fname]=array($nocomments,$snipdata);
	} // if data not in the cache
	
	if (!isset($this->filecache[$fname][1][$sname]))
		throw new exception("snippet $sname not found in $fname");
	$ix=$this->filecache[$fname][1][$sname];
	$sniplines=array();
	$ln="";
	while ($ix < sizeof($this->filecache[$fname][0]) && $ln != "}") {		
		$ln=rtrim($this->filecache[$fname][0][$ix]);
		$sniplines[]=$ln;
		++$ix;
	}
	
	$code="";
	$search=array();	// parameters searched for
	$bStartFound=False;	// True when we've found code
	foreach ($sniplines as $line) {
		if (substr($line,0,3) == "//-") {
			if ($bStartFound)
				throw new exception("directive comment found inside code:  snippet $sname");
			$type=substr($line,3,1);
			$data=substr($line,4);
			if ($type == ":") {
				// parameter substitution
				$params=explode(" ",$data,3);
				if (sizeof($params) == 2) {
					list($index,$searchterm)=$params;
					$comment='';
				} else if (sizeof($params) == 3)	
					list($index,$searchterm,$comment)=$params;
				else
					throw new exception("parameter substitution error in parameter definition line: $data");	
				if (substr($index,0,2) == 'QQ')				
					throw new exception("global parameters like $index must not be specified since they are implied.  in snippet $sname");
				if (!isset($args[$index]) && !isset($args['***'])) {	// the '***' allows dependency scans to ignore parameter substitution
					infolog_dump("errortrap","args",$args);
					throw new exception("missing parameter '$index' in snippet $sname");
				}	
				$search[$index]=$searchterm;	
			} else if ($type == "+") {
				// snippet dependency.  work through recursively, that is, if we're watching for extrasnips:
				if (!is_array($extrasnips))
					continue;
				if (0 == preg_match("/^(\\*|\\:|\\/|\\!)([^ ]*) ([^ ]+)/", $data, &$matches))
					throw new exception("illegal syntax for snippet dependency.  snippet: $sname  line: $line");
				list($dummy,$subtype,$routine,$subsnippet)=$matches;
				if ($subtype == '*' || $subtype == ':') {
					// snippet to event listener in current object or method in object
					if ($objshort == '')
						throw new exception("required object not available for snippet resolution.  snippet: $sname line:$line");
					$codestart=$data[0] == '*' ? "$('#$objshort').bind('$routine',function(e){\n" : "$('#$objshort').get(0).$routine=function(){\n";
					$codeend="\n});\n";
					$subdestination='$';
				} else if ($subtype == '/') {
					// snippet to destination function
					$subdestination=$routine;
					$codestart=$codeend='';
				} else if ($subtype == '!') {
					if ($destination == '')
						throw new exception("required destination not available for snippet resolution.  snippet: $sname line:$line");
					$subdestination=$destination;
					$codestart=$codeend='';
				}
				$code=$this->snippetToCode($subsnippet,$args,$page,$objshort,&$libs,&$extrasnips,$subdestination);
				$extrasnips[]=array($subdestination,$codestart.$code.$codeend,'*code*');
			} else if ($type == "<") {
				// library dependency
				$lib="j/lib/".$data;
				if (is_array($libs) && False === (array_search($lib,$libs)))
					$libs[]=$lib;
			} else if ($type != "!")	// skip the title
				throw new exception("unknown snippet directive type $type in snippet $sname");
			continue;		
		} // if this was a directive comment
		if (!$bStartFound) {
			if (substr($line,0,2) == "//")
				continue;
			// the only function declaration line allowed is 'function() {' or 'function(event) {'
			// really we should check event based on if this is an event listener, but there's no easy way to tell that here   	
			if (0 == preg_match('/^\\s*function\\s*(\\(\\)|\\(event\\))\\s*\\{\\s*$/',$line))
				throw new exception("missing or illegal function declaration in snippet $sname");
			$bStartFound=True;
			
			// set up substitution arrays. there can be more arguments passed in than needed, because maybe there are 
			// embedded dependencies that need different parameters.
			$params=$args;
			foreach($args as $index=>$arg) {
				if (substr($index,0,2) == 'QQ') {
					// global argument.  this does not need to exist in the search array to be searched
					$search[$index]=$index;
				} else if (!isset($search[$index]))
					unset($params[$index]);		// this parameter is not used
			}
			ksort($params);
			ksort($search);
			if (sizeof($params) != sizeof($search) && !isset($args['***'])) {
				infolog_dump("errortrap","params",$params);
				infolog_dump("errortrap","search",$search);
				throw new exception("unmatched parameters in snippet $sname");
			}
		} else {
			// note that this approach means that snippets must add a comment or something to lines like this:
			if (rtrim($line) == "}//-}") {
				$bEndFound=True;
				break;
			}
			// add another line to the block
			if ($qq['production']) {
				$line=ltrim($line)."\r\n";
				// TODO:  this is pretty cheap.  make it better by looking for comments in quotes, etc.
				if (False !== ($i=strrpos($line,"//")))
					$line=substr($line,0,$i);
			}
			// NOTE:  here is where you can strip comments, etc.
			$line=str_replace($search,$params,$line);
			// any long names that are left need to be converted to short names.  they are marked with <# longname #>
			preg_match_all("/<#([^#]+)#>/", $line, &$matches);
			$longnames=array_unique($matches[1]);
			$srch=array();
			$replace=array();
			foreach ($longnames as $longname) {
				$srch[]="<#$longname#>";
				$replace[]=$qqi->htmlShortFromLong($longname,$page);
				$qqi->lockHTMLid($longname,'p',$page);
			}
			$line=str_replace($srch,$replace,$line);
			
			// any tags that are left need to be appended to the main name to short names.  they are marked with <! tag !>
			preg_match_all("/<!([^!]+)!>/", $line, &$matches);
			$tags=array_unique($matches[1]);
			$srch=array();
			$replace=array();
			foreach ($tags as $tag) {
				if (!isset($args['QQaulong']))
					throw new exception("required global arg QQaulong not set in call to snippet $sname");
				if ($tag == '*')
					$longname=$args['QQaulong'];
				else
					$longname=$args['QQaulong'].'_'.$tag;	
				$srch[]="<!$tag!>";
				$replace[]=$qqi->htmlShortFromLong($longname,$page);
				$qqi->lockHTMLid($longname,'p',$page);
			}
			$line=str_replace($srch,$replace,$line); 
			 
			$code .= $line."\n";
		}
	} // loop through all lines in snippet section
	if (!$bEndFound)
		throw new exception("js snippet $sname has no end marker");
	return $code;	
}  

// builds an array of lines and of libraries required for a set of javascript snippet requests
private function buildjs($jsinfo,$page) {
	global $qq,$qqi;
	
	$libs=array();			// library dependencies uncovered (returned)
	$extrasnips=array();	// dependencies on other snippets accumulated (used internally)
	$bBS=$bBE=False;
	$lines=array();			// lines in js file generated
	
	// sort info by destination, maintaining order of addition within sort
	// first, add index into array item to facilitate comparison
	$i=0;
	foreach($jsinfo as &$info)
		$info[]=$i++;
	usort($jsinfo,array(&$this,"cmpjs"));	
	
	// TODO:  pre-loop through and expand all dependencies.  maybe needs to be
	// recursive, definitely needs to be able to pass on destination information some how
	
	// loop through all requests and convert them to direct function types
	for ($ix=0; $ix<sizeof($jsinfo); ++$ix) {
		list($destination,$snippet,$args)=$jsinfo[$ix];
		
		// convert all destinations to functions
		if ($destination[0] == ":") {
			$objdata=explode(":",substr($destination,1));
			if (sizeof($objdata) != 2)
				throw new exception("internal syntax error ':' in snippet destnation: $destination");
			// an event listener:
			list($obj,$method)=$objdata;
			$objshort=$qqi->htmlShortFromLong($obj,$page);
			$destination=$jsinfo[$ix][0]='$';
			if ($method[0] == '*') {
				$event=substr($method,1);
				$codestart="$('#$objshort').bind('$event',function(e){\n";
			} else if ($method[0] == '!') {
				// this is a dependency scan only: discard code
				$codestart='*discard*';
			} else	
				$codestart="$('#$objshort').get(0).$method=function(){\n";
			$codeend="\n});\n";		
			unset($objdata);	
		} else
			$codestart=$codeend=$objshort='';		// code is bare, as is
		
		if ($args != '*code*') {
			$code=$this->snippetToCode($snippet,$args,$page,$objshort,&$libs,&$extrasnips,$destination);
			// translate in snippet
			$jsinfo[$ix][2]="*code*";
			$jsinfo[$ix][1]=($codestart == '*discard*') ? '' : $codestart.$code.$codeend;	
		} else {
			// for *code*, "snippet" is actually a the function.  look for bs and be
			if ($destination == "bs")
				$bBS=True;
			else if ($destination == "be")
				$bBE=True;	
		}
	} // loop for all entries in jsinfo

	// append extra snippets to the BEGINNING of the jsinfo array.  these have dependencies
	$i=-sizeof($extrasnips);	// index to maintain sort order--see above where it was added to original jsinfo
	foreach ($extrasnips as &$es)
		$es[]=$i++;
	
	$jsinfo=array_merge($extrasnips,$jsinfo);
	
	// sort the array again so that revised destinations will be together
	usort($jsinfo,array(&$this,"cmpjs"));

	// write out the lines
	$lastfunction="";
	for ($i=0; $i<sizeof($jsinfo); ++$i) {
		list($function,$code,$marker,$ix)=$jsinfo[$i];
		if ($function == '!')
			continue;	// this one was scan only
		if ($lastfunction != $function) {
			if ($lastfunction == "$")
				$lines[]="\n});\n";
			else if ($lastfunction != "")
				$lines[]="\n}\n";
			if ($function == "$")
				$lines[]="jQuery(function($) {\n";
			else	
				$lines[]="function $function() {\n";
			$lastfunction=$function;	
		}
		$lines[]=$code;
	}
	if ($lastfunction == "$")
		$lines[]="\n});\n";
	else if ($lastfunction != "")
		$lines[]="\n}\n";
	return array($lines,array($bBS,$bBE,$libs));
}

////////////////////////////////////////////////// end of class definition /////
} ?>
