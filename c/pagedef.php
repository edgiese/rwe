<?php if (FILEGEN != 1) die;
class pagedef {
////////////////////////// definition file for pagedef class ///////////////////
private $dependencies;		// dependent file names in a simple array of names
private $pmod;			// project module objects, an array indexed by name
private $pagedefinfo;
private $buildinfo;
private $aukeys;			// used for forms
private $aulist;
private $formids;			// array of [form_name_in_pagestxt]=>short_form_name 

function __construct($filename) {
	global $qq,$qqu,$qqc;
	
	$this->dependencies=array($filename);
	$this->pmod=array();
	$this->aulist=array();
	$this->pagedefinfo=array();
	$this->buildinfo=array();
	$this->aukeys=array();
	
	ob_start();
		
	echo <<<EOT
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html><head><meta http-equiv="content-type" content="text/html; charset=windows-1250">
<title>Make Filegen Page Tables</title>
<style type="text/css">
body {font-family: courier new;font-size:15px;}
body {background-color: #F0FFE0; }
p.section {background-color:yellow;}
p.query {background-color:#CCFFFF; border: 1px solid black; padding: 10px; margin: 20px;}
</style></head><body>
EOT;
	
	try {
		$lines=file($filename);
		$stype="(undefined)";
		$defsearch=array();
		$defreplace=array();
		$pageids=array();
		$texts=array();		// array of arrays of lines of creole. indexed by name of block
		$blocks=array();	// array of init blocks.  indexed by name of block
		$this->formids=array();
		$textindex="";		// if non blank, we are building this text block
		$blockindex='';		// if non blank, we are building this variable block
		for ($lineix=0; $lineix<sizeof($lines); ++$lineix) {
			$ln=rtrim($lines[$lineix]);
			echo htmlentities($ln)."<br>";
			
			$ln=str_replace($defsearch,$defreplace,$ln);
			
			// do includes
			if (substr($ln,0,8) == '#include') {
				$inclfilename=trim(substr($ln,8));
				if ($inclfilename[0] == '*') {
					$inclfilename="l/p/pages/".substr($inclfilename,1);
				} else {
					$inclfilename="p/{$qq['project']}/".$inclfilename;
				}	
				$this->dependencies[]=$inclfilename;
				if (!file_exists($inclfilename))
					throw new exception("included file name '$inclfilename' does not exist");
				$incllines=file($inclfilename);
				$lines=array_merge(array_slice($lines,0,$lineix+1),$incllines,array_slice($lines,$lineix+1));
				continue;
			}
			
			if ($textindex != "") {
				// building a text block
				if (substr($ln,0,3) == "<<<") {
					// text blocks endmarked with <<<* are 'au-special-use'.  Always update them and insert them into the _text table
					if (substr($ln,0,4) == '<<<*') {
						$bUserUpdateable=!(substr($ln,0,5) == '<<<**');
						$qqu->insertOrUpdateText($textindex,True,implode("\n",$texts[$textindex]),util::SRC_NONE,$bUserUpdateable);
						unset($texts[$textindex]);
					}	
					// done adding lines to this block for now
					$textindex="";
					continue;
				}
				$texts[$textindex][]=$ln;
				continue;
			}
			if ($ln == "" || substr($ln,0,1) == ";")
				continue;		// ignore blank and comment
			if (($i=strpos($ln,' ;')) !== False) {
				// strip off inline comment
				$ln=substr($ln,0,$i);
			}
			if ($blockindex != '') {
				list($blocks[$blockindex],$bContinueBlock)=$this->parseBlockData($ln,$blocks[$blockindex]);
				if (!$bContinueBlock)
					$blockindex='';
				continue;	
			}	
			if ($ln[0] == '{') {
				if (False === ($i=strpos($ln,':')))
					throw new exception("init block syntax error.  block must start with {name:");
				$blockindex=substr($ln,1,$i-1);
				$ln=substr($ln,$i+1);
				if (!isset($blocks[$blockindex]))
					$blocks[$blockindex]='';
				list($blocks[$blockindex],$bContinueBlock)=$this->parseBlockData($ln,$blocks[$blockindex]);
				if (!$bContinueBlock)
					$blockindex='';
				continue;	
			}
			if ($ln[0] == '@') {
				// a generator block.  syntax is @instancename:pmod[|filename]
				// if filename isn't specified, then block continues until @instancename occurs at the beginning of a line
				if (False === ($colonpos=strpos($ln,':')))
					throw new exception("syntax error in generator block spec");
				$instancename=trim(substr($ln,1,$colonpos-1));
				$class=trim(substr($ln,$colonpos+1));
				if (False !== ($filepos=strpos($class,'|'))) {
					$classfile=trim(substr($class,$filepos+1));
					$class=trim(substr($class,0,$filepos));
					$classfile="p/{$qq['project']}/$classfile";
					if (!file_exists($classfile))
						throw new exception("class file $classfile does not exist");
					$this->dependencies[]=$classfile;
					$classlines=file($classfile);
				} else {
					$classtest="@$instancename";
					$classlines=array();
					++$lineix;
					while ($lineix < sizeof($lines)) {
						if (0 === strpos($lines[$lineix],$classtest))
							break;
						$classlines[]=$lines[$lineix];
						++$lineix;	
					}
					if ($lineix >= sizeof($lines))
						throw new exception("no block closing line for pmod block @$instancename:$class");
				}
				$class="pmod_".$class;
				$this->pmod[$instancename]=new $class($classlines);
				continue;
			}
				
			if (substr($ln,0,1) == ">") {
				if (substr($ln,0,3) == ">>>") {
					// new text block
					$textindex=trim(substr($ln,3));
					$texts[$textindex]=array();
					continue;
				}
				$stype=substr($ln,1);
				$sectionname="";
				if (($i=strpos($stype,"=")) !== False) {
					$sectionname=substr($stype,$i+1);
					$stype=substr($stype,0,$i);
				}
				echo "<p class=\"section\"><strong>Changing sections type=$stype name=$sectionname</strong></p>";
				if ($stype == "aulist") {
					$sequence=0;
					$childlevel=0;
					$this->aulist[$sectionname]=array();
				} else if ($stype == "pagedef")
					$pagedefseq=1;		// we're saving 0 for an illegal value
				continue;
			}
			switch ($stype) {
				case "images":
					if (False === ($i=strpos($ln,'=')))
						throw new exception("missing '=' in image definition.  syntax is alt=imagename");
					$alt=trim(substr($ln,0,$i));
					$fname=trim(substr($ln,$i+1));
					echo "|$fname| ".strlen($fname)."<br>";	
					$image=image::createFromFile("p/{$qq['project']}/{$qq['sourcedir']}/".$fname,$alt,True);
					unset($image);
				break;
				
				case "files":
					if (False === ($i=strpos($ln,'=')))
						throw new exception("missing '=' in file definition.  syntax is alt=filename");
					$alt=trim(substr($ln,0,$i));
					$fname=trim(substr($ln,$i+1));
					echo "|$fname| ".strlen($fname)."<br>";	
					$file=image::createFromFile("p/{$qq['project']}/{$qq['sourcedir']}/".$fname,$alt);
					unset($file);
				break;
				
				case "aulist":
					// do the child level
					for ($newchildlevel=0; substr($ln,$newchildlevel,1) == " "; ++$newchildlevel)
						;
					if (($childdelta=($newchildlevel-$childlevel)) > 1)
						throw new exception("illegal child level change: only indent one space at a time");
					if ($sequence == 0)
						$childdelta=2;
					$childlevel=$newchildlevel;
					$ln=substr($ln,$childlevel);
					
					// get the tag, or * if it's a placeholder
					if ($ln[0] == "*") {
						// this is an argument placeholder
						$tag="*";
						$classname="";
						$initdata="";
						$bAutoLock=False;
					} else {
						$i=strpos($ln,"=");
						if ($i === False)
							throw new exception("expected = in au list definition");
						$tag=substr($ln,0,$i);
						// a tag starting with '#' indicates a forced lock
						if ($tag[0] == '#') {
							$tag=substr($tag,1);
							$bAutoLock=True;
						} else
							$bAutoLock=False;
						
						// full tag format:  tag<<formid.render  formid and render are optional
						
						// forms specify a special purpose form tag for identifying their state pages later in the file
						if (($j=strpos($tag,"<<")) !== False) {
							$formid=substr($tag,$j+2);
							$tag=substr($tag,0,$j);
						} else
							$formid='';
						// render mask will be a dot after the tag name, or default to all cases
						if (($j=strpos($tag,".")) !== False) {
							$rendermask=(int)substr($tag,$j+1);
							$tag=substr($tag,0,$j);
						} else
							$rendermask=0x7fffffff;
	
						$classname="au_".substr($ln,$i+1);
						$i=strpos($classname," ");
						if ($i !== False) {
							$initdata=trim(substr($classname,$i+1));
							$classname=substr($classname,0,$i);
							if (strlen($initdata) > 0 && $initdata[0] == '{') {
								list($initdata,$bContinueBlock)=$this->parseBlockData(substr($initdata,1),Null);
								if ($bContinueBlock)
									throw new exception("init blocks in au lists must complete in one line -- missing }");
							}								
						} else
							$initdata="";
						if (!class_exists($classname))
							throw new exception("illegal nonexistent class:  $classname");
					}
					
					if ($classname == "au_image") {
						// for an image, convert filename and alt into image id
						$initdata=au_base::getInit($initdata,'usage=image|filename|alt|smallscalepercent=100|*link=|linkdescription=');
						$image=image::createFromFile("p/{$qq['project']}/{$qq['sourcedir']}/".$initdata['filename'],$initdata['alt']);
						$initdata['id']=$image->getId();
						unset($image);
					} else if ($classname == "au_label") {
						// create text and substitute id--use label's tag
						// NOTE:  this default can be overridden by putting 'tag::' at start of inittext
						$name='label_'.$tag;
						$initdata=au_base::getInit($initdata,'type|usage=label|text|*href=|tip=');
						if ($initdata['type'] == "a") {
							if (!isset($initdata['href']) || !isset($initdata['tip']))
								throw new exception("type 'a' labels require href and tip to be specified");
						} else {
							if (isset($initdata['href']) || isset($initdata['tip']))
								throw new exception("only type 'a' labels may have href and tip");
						}
						if (False !== ($i=strpos($initdata['text'],'::'))) {
							$name=substr($initdata['text'],0,$i);
							$initdata['text']=substr($initdata['text'],$i+2);
						}
						$initdata['id']=$qqu->insertOrUpdateText($name,False,$initdata['text']);
						unset($initdata['text']);
						$initdata['name']=$name;
					} else if ($classname == "au_text") {
						$initdata=au_base::getInit($initdata,'usage|textinfo|*classdef=""|classinit=""');
						// take off edit description from text id
						$textinfo=$initdata['textinfo'];	
						if (False !== ($i=strpos($textinfo,'/')))
							$textinfo=substr($textinfo,0,$i);
						if (!isset($texts[$textinfo]))
							throw new exception("cannot find init section for au_text init to {$textinfo}.");	
						$qqu->insertOrUpdateText($textinfo,True,implode("\n",$texts[$textinfo]),util::SRC_NONE,True);
					}
					
					if ($formid != '')
						$this->formids[$formid]=$this->addAUShortName($classname,$tag,$initdata);
					else	
						$this->addAUShortName($classname,$tag,$initdata);
						
					$this->aulist[$sectionname][$sequence]=array($tag,$classname,$initdata,$childdelta,$rendermask,$bAutoLock); 
					++$sequence;
				break;
				
				case "pagedef":
					$pagedefinfo=explode("|",$ln);
					if (sizeof($pagedefinfo) <> 4)
						throw new exception("syntax for pagedef in pagedef file is uri|robots|title|sitemapdesc");
					list($uri,$robots,$title,$sitemapdesc)=$pagedefinfo;
					$this->pagedefinfo[$uri]=array($pagedefseq,$title,$sitemapdesc,$robots);
					++$pagedefseq;
				break;
	
				case "buildlist":
					$buildlistinfo=explode("=",$ln);
					if (sizeof($buildlistinfo) != 2)
						throw new exception("syntax for buildlist in pagedef file is page=buildlist");
					list($uri,$listspec)=$buildlistinfo;
					if (substr($uri,0,5) == "form/") {
						// special processing for forms
						$forminfo=explode("/",$uri);
						// messages are form 0--they can stay as is
						if (!is_numeric($forminfo[1])) {
							// substitute shortcut for form init info
							if (!isset($this->formids[$forminfo[1]])) {
								infolog_dump("errortrap","formids",$this->formids);
								throw new exception("form id {$forminfo[1]} info does not match any defined access units");
							}	
							$forminfo[1]=$this->formids[$forminfo[1]];
							$uri=implode("/",$forminfo);	
						}
					}
					$this->buildinfo[$uri]=$this->buildRecipe($listspec);
				break;
				
				case "definitions":
					$definfo=explode("=",$ln,2);
					if (sizeof($definfo) != 2)
						throw new exception("syntax for definition in pagedef file is term=definition");
					$defsearch[$definfo[0]]='##'.$definfo[0];
					$defreplace[$definfo[0]]=$definfo[1];
				break;
	
				default:
					throw new exception("illegal section type: $stype");
				break;
			}
		}
	} catch (Exception $e) {
		ob_end_flush();
		echo "<strong>Exception occurred:</strong><br />";
		echo '<table border="1">';
		echo "<tr><td>Message</td><td>{$e->getMessage()}</td></tr>";
		echo "<tr><td>Code</td><td>{$e->getCode()}</td></tr>";
		echo "<tr><td>File and Line</td><td>{$e->getFile()} Line {$e->getLine()}</td></tr>";
		$trace=str_ireplace("\n","<br />",$e->getTraceAsString());
		echo "<tr><td>Stack Trace</td><td>{$trace}</td></tr>";
		echo "</table></body></html>";
		exit;
	}
	ob_end_clean();
}

// returns True if object is up to date, or False otherwise
public function checkDependencies($pagedeftime) {
	if (!isset($this->dependencies)) {
		return False;
	}	
	foreach ($this->dependencies as $fname) {
		if (!file_exists($fname))
			throw new exception("pagedef file dependency '$fname' no longer exists!");
		if ($pagedeftime < filemtime($fname)) {
			return False;	
		}	
	}
	return True;
}

// returns object
public function getPMod($instancename) {
	if (!isset($this->pmod[$instancename]))
		throw new exception("PMod object $getinstancename was never defined in page file");
	return $this->pmod[$instancename];	
}

// function to read argument blocks
// returns an array of ($bdata,$bContinued)
private function parseBlockData($ln,$blkData) {
	if (!is_array($blkData)) {
		// newly created block
		$blkData=array();
	}
	$state=0; // start by skipping whitespace
	for ($i=0; $i<strlen($ln); ++$i) {
		$char=$ln[$i];
		switch ($state) {
			case 0:	// skipping whitespace before keyword
				if ($char == ' ' || $char == "\t")
					continue;
				if ($char == '}') {
					$state=4;
					continue;
				}	
				$state=1;
				$keyword=$char;
				if ($char == '=')
					throw new exception("unexpected =");
			break;
			case 1:	// building keyword
				if ($char == '=') {
					$state=2;
				} else
					$keyword .= $char;
			break;
			case 2: // skipping white space before value
				if ($char == ' ' || $char == "\t")
					continue;
				$state=3;
				if ($char == '"' || $char == "'") {
					$value='';
					$delimiter=$char;
				} else {
					$delimiter=' ';
					$value=$char;
				}
			break;
			case 3: // building value
				if ($char == $delimiter || $char == '}') {
					$blkData[$keyword]=$value;
					$state=($char == '}') ? 4 : 0;	
				} else
					$value .= $char;
			break;
			case 4: // past end delimiter
				if ($char == ' ' || $char == "\t")
					continue;
				throw new exception("illegal character after closing brace");				
			break;						
		} // state machine case
	} // loop for all keyword chars
	
	if ($state==3) {
		$blkData[$keyword]=$value;
		$state=0;
	}
	if ($state != 0 && $state != 4)
		throw new exception("cannot split key/value pairs across lines");
	return array($blkData,$state != 4);
}

// recursively called function to read arguments from a listspec.  returns an array of arrays of recipe elements
private function getArgLists($ls) {
	$retval=array();
	$childdelta=0;
	while (True) {
		$commapos=strpos($ls,',');
		if ($commapos === False)
			$commapos=strlen($ls);
		$lparenpos=strpos($ls,'(');
		if ($lparenpos === False)
			$lparenpos=strlen($ls);
		$listname=substr($ls,0,min($commapos,$lparenpos));
		if ($listname == "")
			throw new exception("syntax error in listspec: $ls");
		if (!isset($this->aulist[$listname]))	
			throw new exception("aulist name not found:  $listname");
		$listdata=$this->aulist[$listname];
		// check for wrapper arguments
		if ($lparenpos < $commapos) {
			// find the matching right paren position, if it exists
			$rparenpos=False;
			$nRParens=0;
			for ($i=strlen($ls)-1; $i > $lparenpos; --$i) {
				if ($ls[$i] == ')') {
					if ($nRParens == 0)
						$rparenpos=$i;
					++$nRParens;
				} else if ($ls[$i] == '(') {
					--$nRParens;
					if ($nRParens == 0)
						$rparenpos=False;
					else if ($nRParens < 0)	
						throw new exception("unmatched parentheses in $ls");
				}	
			}
			if ($rparenpos === False)
				throw new exception("unmatched parentheses in $ls");
			$argdata=$this->getArgLists(substr($ls,$lparenpos+1,$rparenpos-$lparenpos-1));
			// update commapos--
			$commapos=strpos($ls,',',$rparenpos);
			if ($commapos === False)
				$commapos=strlen($ls);
		} else {
			$argdata=array();		// dummy value, will cause exception if referenced
		}	
		$aulist=array();		// will receive au list	
		$argix=0;
		foreach ($listdata as $row) {
			list($tag,$classname,$initdata,$childlevel,$rendermask,$bAutoLock)=$row; 
			if ($tag == "*") {
				// an embedded argument!  To embed a list, simply copy, except for first
				// element of array, which gets the childlevel from the asterisk item.
				// note:  last element of argdata is child adjustment
				if ($argix >= sizeof($argdata))
					throw new exception("too few arguments for list:  need at least $argix + 1");
				$argdata[$argix][0][0]=$childlevel;
				$childdelta += $childlevel;
				// accumulate child level to correct for it at the end--except for the first child, which we ignore
				$childdelta=-$childlevel;
				foreach ($argdata[$argix] as $lrow) {
					$aulist[]=$lrow;
					$childdelta += $lrow[0];
				}
				++$argix;	// point to next argument	
			} else {
				// "normal" case is a non-argument entry
				if ($childdelta < 0)
					throw new exception("mismatched levels in child lists; cannot be net negative, but is $childdelta");
				$childlevel -= $childdelta;
				$childdelta=0;	
				$aulist[]=array($childlevel,$classname,$tag,$initdata,$rendermask,$bAutoLock);
			}
		} // loop for all returned data for a list
		if ($argix < sizeof($argdata))
			throw new exception("count of arguments in au list ($argix) was fewer than returned lists");
		unset($argdata);	
		// add the newly copied recipe elements to the return array
		$retval[]=$aulist;
		unset($aulist);
		// comma position points to the end of the string if we're finished
		if ($commapos+1 >= strlen($ls))
			break;		// our one way out of the "forever" loop besides exceptions
		// chop off the part we processed and do it again	
		$ls=substr($ls,$commapos+1);
	} // end 'forever' loop
	return $retval;			
}

private function buildRecipe($listspec) {
	$arglists=$this->getArgLists($listspec);
	if (sizeof($arglists) > 1)
		throw new exception("listspec can only have one top level (parent) au");
	$recipe=$arglists[0];
	unset($arglists);
	return $recipe;
}

// returns array of pagename,pageextra,formpage
public function parseRequest($request) {
	global $qq;		
	
	// handle cases where the web site resides in a subdirectory:
	$uristrip=$qq['hrefbase'];
	if ($uristrip != "" && 0 !== strpos($request,$uristrip)) {
		if (0 === strpos($request,'http:')) {
			// die silently for sites fishing for http forwarders.
			header("Status: 403");
			exit;
		}
		throw new exception("request '{$request}' must begin with href base: '{$uristrip}'");
	}	
	$request=substr($request,strlen($uristrip));

	// we always leave off leading and trailing slashes
	$pagename=trim($request,"/");
	$pageextra="";
	// only look for non-blank extra if it's not a form.
	if (substr($pagename,0,5) != "form/") {
		// the way that we're doing this, a root-directory blank homepage can't contain extra data.  too bad.
		// NOTE:  strrpos can return False or 0.  We don't care, the intended result is the same in this case:
		while (!$this->pageExists($pagename) && ($pos=strrpos($pagename,"/")) != 0) {
			$pageextra=substr($pagename,$pos).$pageextra;
			$pagename=substr($pagename,0,$pos);
		}
		$pageextra=trim($pageextra,'/');
		$formpage='';
	} else {
		// adjust the page name to account for wildcards in the build lists
		$formpage=$pagename;
		$pagename=$this->adjustPageForForm($pagename);
	}
	return array($pagename,$pageextra,$formpage);	
}

public function adjustPageForForm($page) {
	if (substr($page,0,5) != "form/")
		return $page;
	list($formclass,$formtag,$formstate,$formdata,$forminit,$formshort)=$this->getFormInfo($page);
	if ($formclass == "au_message") {
		// look for exact message match
		$try='form/0/'.$formdata;
		if (isset($this->buildinfo[$try]))
			return $try;
		// look for default message list
		$try='form/0';
		if (isset($this->buildinfo[$try]))
			return $try;
	} else {
		// look for exact match
		$try="form/$formshort/$formstate";
		if (isset($this->buildinfo[$try]))
			return $try;
		// look for a default form entry
		$try="form/$formshort";
		if (isset($this->buildinfo[$try]))
			return $try;
	}
	infolog_dump("errortrap","page names",$this->getPageNames());
	throw new exception("unknown form related page: $try");		
}

public function pageExists($page) {
	return isset($this->buildinfo[$page]);
}


// used by stylegen to allow form palettes to be specified by long name.
// also prevents bad page names in style file
public function checkAndNormalizePage($page) {
	// if it's in the array, it's ready to go
	if (isset($this->pagedefinfo[$page]))
		return $page;
	if ($page == 'form/0') {
		if (!isset($this->pagedefinfo['form/0']))
			throw new exception("master message form has not been defined for this site.");
		return 'form/0';
	}
	if (substr($page,0,5) != "form/") {
		throw new exception("page '$page' does not exist in page definitions");
	}	
	$formid=substr($page,5);
	if (False !== ($i=strpos($formid,'/'))) {
		$state=substr($formid,$i);
		$formid=substr($formid,0,$i);
	} else
		$state='';
			
	if (!isset($this->formids[$formid])) {
		infolog_dump("errortrap","formids",$this->formids);
		throw new exception("page '$page' refers to a form that has not been defined in the page definition file");
	}	
	return 'form/'.$this->formids[$formid].$state;				
}

public function getPageInfo($page) {
	if (isset($this->pagedefinfo[$page]))
		return $this->pagedefinfo[$page];
	if (substr($page,0,5) != "form/") {
		infolog_dump("errortrap","pagedefinfo",$this->pagedefinfo);
		throw new exception("page $page does not exist in page definitions");
	}	
	if (!isset($this->pagedefinfo['form']))
		throw new exception("a master form page definition has not been set in page definitions");
	return $this->pagedefinfo['form'];	
}
public function getPageNames() {
	return array_keys($this->buildinfo);
}
public function getBuildList($page) {
	if (!isset($this->buildinfo[$page])) {
		infolog_dump("errortrap","available pages",$this->getPageNames());
		throw new exception("invalid page $page");
	}	
	return $this->buildinfo[$page];
}
public function getPalette($page) {
	if (!isset($this->buildinfo[$page])) {
		infolog_dump("errortrap","available pages",$this->getPageNames());
		throw new exception("invalid page $page");
	}	
	return $this->buildinfo[$page];
}

// returns array of form info as appropriate
public function getFormInfo($page,$defaultFormState='') {
	if (substr($page,0,5) != 'form/')
		return array('','','','','','');
		
	// special processing for forms--provide state variable
	$forminfo=explode('/',$page);
	if (is_numeric($forminfo[1])) {
		// message only form
		$formclass="au_message";
		$formtag="message";		// hardcoded.  no other values allowed in pagedef
		$formstate="";
		if (sizeof($forminfo) >= 3)
			$formdata=$forminfo[2];
		else {
			$formdata='';
		}		
		$forminit="";
		$formshort='0';
	} else {
		// find state and get data
		$formshort=$forminfo[1];	
		$formdata='';
		$formstate=$defaultFormState;
		if (sizeof($forminfo) >= 3) {
			$formstate=$forminfo[2];
			if (sizeof($forminfo) >= 4)
				$formdata=$forminfo[3];
		}
		if (!isset($this->aukeys[$forminfo[1]]))
			throw new exception("unknown form shortname: {$forminfo[1]}");
		$formkey=$this->aukeys[$forminfo[1]];
		$forminfo=explode(" ",$formkey,3);
		if (sizeof($forminfo) == 3)	
			list($formclass,$formtag,$forminit)=$forminfo;
		else {
			list($formclass,$formtag)=$forminfo;
			$forminit="";
		}		
	}
	return array($formclass,$formtag,$formstate,$formdata,$forminit,$formshort);
}

private $seq=0;				// ascending sequence numbers for assigned aus

private function addAUShortName($classname,$tag,$initdata) {
	$initstring=serialize($initdata);
	$aukey=trim($classname.' '.$tag.' '.$initstring);
	if (False == ($newkey=array_search($aukey,$this->aukeys)))
		$this->aukeys[$newkey=idstore::getAlphaLabel($this->seq++)]=$aukey;
	return $newkey;	
}

public function getAUShortName($classname,$tag,$initdata) {
	$initstring=serialize($initdata);
	$aukey=trim($classname.' '.$tag.' '.$initstring);
	if (False == ($short=array_search($aukey,$this->aukeys))) {
		infolog_dump("errortrap","aukeys",$this->aukeys);
		throw new exception("au information not in table.  key:$aukey");
	}	
	return $short;	
}

// returns au
public function createFromShort($aushort,$parent,$formstate,$formdata) {
	if (!isset($this->aukeys[$aushort]))
		throw new exception("unknown short name: $aushort");
	$auinfo=explode(" ",$this->aukeys[$aushort],3);
	if (sizeof($auinfo) == 3) {	
		list($class,$tag,$initstring)=$auinfo;
		$initdata=unserialize($initstring);		
	} else {
		list($class,$tag)=$auinfo;
		$initdata="";
	}		
	return new $class($tag,$parent,$initdata,$formstate,$formdata);
}

public function createFromLong($aulong,$parent,$formstate,$formdata) {
	foreach ($this->aukeys as $short=>$aulongkey) {
		$auinfo=explode(" ",$aulongkey,3);
		$testlongname=trim($auinfo[0].'_'.$auinfo[1]);
		if ($testlongname == 'au_'.$aulong) {
			$bFound=True;
			break;
		}	
	}
	if (!isset($bFound))
		throw new exception("unknown au long name: $aulong");	
	if (sizeof($auinfo) == 3) {	
		list($class,$tag,$initstring)=$auinfo;
		$initdata=unserialize($initstring);		
	} else {
		list($class,$tag)=$auinfo;
		$initdata="";
	}		
	return new $class($tag,$parent,$initdata,$formstate,$formdata);
}


////////////////////////// end of class definition /////////////////////////////
} ?>
