<?php if (FILEGEN != 1) die;
// utility library for filegen project
class util {
////////////////////////////////////////////////
/////// state support functions ////////////////////////////////////////////////

// set up the state structure.  create new or read from cookie
// returns state structure of False if not found looking for 'mycookie'
public function state_setup($mycookie='') {
	global $qq;
	global $qqc;
	
	if ($mycookie != '' || isset($_COOKIE['_state_'])) {
		// cookies up and running -- 'ordinary' case.  load in state information
		$key=($mycookie != '') ? $mycookie : $_COOKIE['_state_'];
		$statestring=$qqc->getRows("util::state",1,$key);
		if ($statestring !== False) {
			$state=unserialize($statestring);	
			if (isset($_COOKIE['_state_']))
				$state->setStated();
			return $state;
		}
		if ($mycookie != '')
			return False;
	}
	// need to set a key
	$newkey=md5('fgs'.microtime());
	while ($qqc->getValue("util::sessionkeyexists",$newkey)) {
		// key was not OK.  Keep picking one until it works
		$newkey=md5('fgs'.microtime());
	}
	// send the cookie that we've settled on for the session
	setcookie('_state_',$newkey,0,'/');
	
	// initialize a new state if necessary
	if (!isset($state) || $state === Null) {
		$state=new state($newkey);
	}
	return $state;
}

public function state_save($state,$key,$timeout) {
	global $qqc;
	
	$statestring=serialize($state);
	$qqc->insert('util::state',$key,$timeout,$statestring);
}

public function state_update($key,$state) {
	global $qqc;

	$statestring=serialize($state);
	$qqc->act('util::updatestate',$key,$statestring,date('Y-m-d H:i:s'));
	$qqc->act('util::stateclean');
}


/////// miscellaneous utility functions ////////////////////////////////////////
static function addpx($val) {
	return (is_numeric($val)) ? $val."px" : $val;
}

public function mail($email,$subject,$message) {
	global $qq;
	if ($qq['production']) {
		mail($email,$subject,$message);
	} else {
		infolog("email","email to: $email");
		infolog("email","subject: $subject");
		infolog("email","message: $message");
	}
}

////////////////////////////////////////////////////////////////////////////////
/// text manipulation functions.  these really are kind of module level, but ///
/// because of their ubiquity, they are put here to simplify coding ////////////
// returns unique id for newly created text

// TODO:  make a more general constant-assignment scheme if creation of AUs ever goes public
const SRC_NONE=0;
const SRC_PRODTITLE=1;
const SRC_PRODDESC=2;
const SRC_MINIWIKI=3;
const SRC_SCRAPBOOK=4;

public function newText($bCreole,$inittext="",$src=self::SRC_NONE,$name='') {
	global $qqc;
	$newid=$qqc->insert("text::insert",$bCreole,$name,$inittext);
	utilrare1::searchIndexText($newid);
	return $newid;	
}

// returns id or False (0) if name doesn't exist
public function getTextIndex($name) {
	global $qqc;
	
	$id=$qqc->getRows("text::getfromname",1,$name);
	if ($id === False)
		return 0;
	return $id;	
}

// returns text formatted for display--i.e., in html unless a label.  in that case it's plain text
public function getDisplayText($id,$callbacks=array(),$classes=array()) {
	global $qqc;
	
	if (False === ($textinfo=$qqc->getRows("text::getfromid",1,(string)$id)))
		throw new exception("cannot find text for textid $id");
	if ($textinfo["iscreole"]) {
		return $this->creole2html($textinfo["text"],$callbacks,$classes);
	} else {
		return $textinfo["text"];
	}	
}

public function updateText($id,$text) {
	global $qqc;
	
	if (False === ($textinfo=$qqc->getRows("text::getfromid",1,(string)$id)))
		throw new exception("cannot find text for textid $id");
	// here is the place if we want to implement partial wiki exposure.  escape unwanted codes.  will work like magic!	
	$qqc->act("text::updatetext",$id,$text);	
	utilrare1::searchIndexText($id);
}

// returns id
public function insertOrUpdateText($name,$bCreole,$text,$src=self::SRC_NONE,$bNoProductionUpdate=False) {
	global $qq;
	
	$id=$this->getTextIndex($name);
	if ($id == 0) {
		$this->newText($bCreole,$text,$src,$name);
	} else {
		if ($bCreole != $this->isCreole($id))
			throw new exception("mismatched creole type on update text:  $name");
		if (!$qq['production'] || !$bNoProductionUpdate)	
			$this->updateText($id,$text);	
	}
	return $id;
}

// returns text type (html tag or * for wiki) or False if id doesn't exist
public function isCreole($id) {
	global $qqc;
	
	if (False === ($textinfo=$qqc->getRows("text::getfromid",1,(string)$id)))
		throw new exception("unknown id passed into isCreole: $id");
	return $textinfo["iscreole"];	
}

// returns text formatted for editing
public function getEditText($id) {
	global $qqc;
	
	if (False === ($textinfo=$qqc->getRows("text::getfromid",1,(string)$id)))
		throw new exception("cannot find text for textid $id");
	return $textinfo["text"];	
}

// deletes a text id
public function deleteText($id) {
	global $qqc;
	
	if (False === ($textinfo=$qqc->getRows("text::getfromid",1,(string)$id)))
		throw new exception("cannot find text for textid $id");
	$qqc->act("text::delete",(string)$id);	
}


// returns array of versions, descriptions, and dates
public function getVersions($id) {
	global $qqc;
	
	throw new exception("unimplemented feature");
}

// returns tags that could be used by creole text.  used for registration
public function getTextRegistrationTags() {return "div:p,a,span,span a,h1 a,h2 a,h3 a,h4 a,h5 a,blockquote,pre,dl,dd,dt,table,th,tr,td,ul,ol,li,ol li,ul li,hr,h1,h2,h3,h4,h5,h6,div div,img";}

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////// CREOLE PARSING /////////////////////////////////////


static $automapid=1;	// used to autogenerate image map ids for link

// helper function for creole2html() function below
private function parseOneLine($line,&$codes,$bForceClose,&$callbacks,&$classes) {
	global $qqi,$qq;
	
	$o="";
	while ($line != "") {
		// clip off next non-remarkable text piece
		if (1 == preg_match("/^[^@\\~\\/\\*\\[\\{\\\\`]+/",$line,$matches)) {
			$o .= htmlspecialchars($matches[0]);
			$line=substr($line,strlen($matches[0]));
		}
		if (strlen($line) < 2) {
			$o .= $line;			// output partial codes as text
			$line="";
		}
		$first=substr($line,0,1);
		$first2=substr($line,0,2);
		if ($first == '`') {
			// escape character
			$o .= substr($line,1,1);
			$line=substr($line,2);
		} else if ($first == '~') {
			// common ones:
			$special=array(' '=>'nbsp','*'=>'bull','(c)'=>'copy','tm'=>'trade');
			$bFound=False;
			foreach ($special as $search=>$replace) {
				if (substr($line,1,strlen($search)) == $search) {
					$bFound=True;
					$o .= "&$replace;";
					$line=substr($line,1+strlen($search));
					break;
				}
			}
			if (!$bFound) {
				// to be completely squeaky clean here, I'd have to encode every possible html special entity.  For now,
				// just pass through alpha or numeric strings  
				if (1 == preg_match("/^~([\\d]+|[A-Za-z]+);/",$line,$matches)) {
					$o .= is_numeric($matches[1]) ? "&#{$matches[1]};" : "&{$matches[1]};";
					$line=substr($line,strlen($matches[0]));
					$bFound=True;
				}
			}
			if (!$bFound) {
				$o .= '~';
				$line=substr($line,1);
			}
		} else if ($first2 == "//") {
			// italics on or off
			if (False === array_search("</em>",$codes)) {
				$codes[]="</em>";
				$o .= $this->startHTML('em',$classes);
			} else {
				// turning off.  force previous codes to end--they should be nested
				while (($code=array_pop($codes)) != NULL) {
					$o .= $code;
					if ($code == "</em>")
						break;
				}
			}
			$line=substr($line,2);
		} else if ($first2 == "**") {
			// bold on or off
			if (False === array_search("</strong>",$codes)) {
				$codes[]="</strong>";
				$o .= $this->startHTML('strong',$classes);
			} else {
				// turning off.  force previous codes to end--they should be nested
				while (($code=array_pop($codes)) != NULL) {
					$o .= $code;
					if ($code == "</strong>")
						break;
				}
			}
			$line=substr($line,2);
		} else if ($first2 == "@@") {
			// span on or off
			if (False === array_search("</span>",$codes)) {
				$codes[]="</span>";
				$o .= $this->startHTML('span',$classes);
			} else {
				// turning off.  force previous codes to end--they should be nested
				while (($code=array_pop($codes)) != NULL) {
					$o .= $code;
					if ($code == "</span>")
						break;
				}
			}
			$line=substr($line,2);
		} else if ($first2 == "[[") {
			// link
/*			
			if (1 == preg_match("/^\\[\\[([^|]+?)\|([^\\]]+?)\|([^\\]]+?)]]/",$line,$matches)) {
				// link with explanatory text and title
				$href=$qqi->hrefPrep($matches[1]);
				$o .= substr($this->startHTML('a',$classes),0,-1)." href=\"{$href}\" title=\"$matches[3]\" onMouseOver=\"window.status=this.title;return true;\" onMouseOut=\"window.status='';return true;\">{$matches[2]}</a>";
				$line=substr($line,strlen($matches[0]));
				
			} else*/ if (1 == preg_match("/^\\[\\[([^|]+?)\|([^\\]]+?)]]/",$line,$matches)) {
				// link with explanatory text
				$href=$qqi->hrefPrep($matches[1]);
				$o .= substr($this->startHTML('a',$classes),0,-1)." href=\"{$href}\">{$matches[2]}</a>";
				$line=substr($line,strlen($matches[0]));
			} else if (1 == preg_match("/^\\[\\[([^\\]]+?)]]/",$line,$matches)) {
				// link without explanatory text--use link text as explanatory text
				$href=$qqi->hrefPrep($matches[1]);
				$o .= substr($this->startHTML('a',$classes),0,-1)." href=\"{$href}\">{$matches[1]}</a>";
				$line=substr($line,strlen($matches[0]));
			} else {
				// closing braces missing.  output braces
				$line=substr($line,2);
				$o .= "[[";
			}				
		} else if ($first2 == "\\\\") {
			// break
			$o .= $this->startHTML('br',$classes,' /');
			$line=substr($line,2);
		} else if ($first2 == "{{") {
			if (substr($line,0,3) == "{{{") {
				// in-line nowiki
				if (False !==($i=strpos($line,"}}}"))) {
					$o .= $this->startHTML('pre',$classes).htmlspecialchars(substr($line,3,$i-3))."</pre>";
					$line=substr($line,$i+3);
				} else {
					// unclosed no-wiki:  output a single brace.  maybe it's a graphic with a typo
					$o .= "{";
					$line=substr($line,1);
				}	
			} else {
				// embedded code/media
				// 1. look for entire text.  if not, output '?{{' and continue
				if (1 != preg_match("/^\\{\\{([^}]+?)}}/",$line,$matches)) {
					$o .= '?{{';
					$line=substr($line,2);
					continue;
				}
				// strip off the entire thing right now.  that way we can continue more easily
				$line=substr($line,strlen($matches[0]));
				// parse the entire thing into an array of keyword=>value.  set first one as special name; that is the type
				$ct=$matches[1];	// 'codetext'
				$cvpairs=array();
				$state=0;	// whitespace
				$error=0;	// if non-zero, it's the offset of the error into the string; all errors are 'syntax'
				$first='';
				for ($i=0; $i<strlen($ct); ++$i) {
					$char=$ct[$i];
					switch ($state) {
						case 0:	// skipping whitespace before keyword
							if ($char == ' ' || $char == "\t")
								continue;
							$state=1;
							$keyword=$char;
							if ($char == '=')
								$error=$i;
						break;
						case 1:	// building keyword
							if ($char == '=') {
								$state=2;
								if ($first == '')
									$first=$keyword;
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
							if ($char == $delimiter) {
								$cvpairs[$keyword]=$value;
								$state=0;	
							} else
								$value .= $char;
						break;						
					} // state machine case
				} // loop for all keyword chars
				if ($error != 0) {
					$o .= '{{'.substr($ct,0,$i).'??'.substr($ct,$i).'}}';
					continue;
				}
				if ($state==3) {
					$cvpairs[$keyword]=$value;
					$state=0;	
				}
				if ($state != 0) {
					$o .= '{{'.$ct.'??'.'}}';
					continue;
				}
				// now process the keyword appropriately
				if ($first == 'img') {
					// first make certain that there are no keywords that don't belong.
					$legalnames=array('img'=>1,'maxwidth'=>1,'maxheight'=>1,'link'=>1,'linkdesc'=>1,'caption'=>1);
					$illegal='';
					foreach ($cvpairs as $name=>$value) {
						if (!isset($legalnames[$name])) {
							$illegal=$name;
							break;
						}
					} 
					if ($illegal != '') {
						$o .= $matches[0]."??Illegal-Keyword:$illegal??";
						continue;
					}
					extract($cvpairs);
					try {
						$image=new image($img);
					} catch (Exception $e) {
						$o .= $matches[0].'??No-image??';
						continue;
					}	
					list($width,$height)=$image->getNaturalSize();
					if (isset($maxwidth) || isset($maxheight)) {
						if (isset($maxwidth) && $maxwidth > 0)
							$maxwidth=min((int)$maxwidth,$width);
						else
							$maxwidth=$width;	
						if (isset($maxheight) && $maxheight > 0)
							$maxheight=min((int)$maxheight,$height);
						else
							$maxheight=$height;	
						$image->setMaximumSize((int)$maxwidth,(int)$maxheight);
						list($width,$height)=$image->getActualSize();
					}
					// add link to full size image if it's been scaled down, unless specifically turned off
					list($winwidth,$winheight)=$image->getNaturalSize();
					if ($winwidth > $width || $winheight > $height || isset($link)) {
						if (!isset($linkdesc))
							$linkdesc='';
						if (!isset($link))
							$link='*';	
						if ($link == '*') {
							if ($linkdesc == '')
								$linkdesc='full sized image';
							// link to full sized image
							$mycaption=isset($caption) ? $caption : $image->getName();
							$proj=$qq['production'] ? '' : "&u={$qq['project']}";
							$url=$qqi->hrefPrep("picture.php",False,'',idstore::ENCODE_NONE)."?i=".urlencode($image->getId()).'&t='.urlencode($mycaption).$proj;
							$linkobj=new link('',$url,$linkdesc);
							// allow for a little window slop
							$winwidth += 20;
							$winheight += 20;
							$js="onclick=\"window.open('".$qqi->hrefPrep("picture.php",False,'',idstore::ENCODE_NONE)."?i=".urlencode($image->getId()).'&t='.urlencode($mycaption).$proj."','popup','width=$winwidth,height=$winheight,scrollbars=no,resizable=no,toolbar=no,directories=no,location=no,menubar=no,status=no,left=0,top=0');return false;\"";
						} else {
							$linkobj=new link('',$link,$linkdesc);
							$js='';
						}	
						$mapname="automap_".self::$automapid++;	
						$o .= $image->getOutput($mapname,$classes);
						$linkobj->setShape(link::SHAPE_RECT,array(0,0,$width,$height));
						$o .= "<map name=\"{$mapname}\">{$linkobj->getOutput(True,$js)}</map>";
						unset($linkobj); 
					} else
						$o .= $image->getOutput('',$classes);
					unset($image);
					if (isset($caption))
						$o .= $this->startHTML('h6',$classes).str_replace("\\\\",'<br />',$caption).'</h6>';
				} else if ($first == 'file') {
					// first make certain that there are no keywords that don't belong.
					$legalnames=array('file'=>1);
					$illegal='';
					foreach ($cvpairs as $name=>$value) {
						if (!isset($legalnames[$name])) {
							$illegal=$name;
							break;
						}
					} 
					if ($illegal != '') {
						$o .= $matches[0]."??Illegal-Keyword:$illegal??";
						continue;
					}
					extract($cvpairs);
					try {
						$fobj=new file($file);
					} catch (Exception $e) {
						$o .= $matches[0].'??No-file??';
						continue;
					}	
					$o .= $fobj->getOutput(False,$classes);
					unset($fobj);
				} else if ($first == 'youtube') {
					// first make certain that there are no keywords that don't belong.
					$legalnames=array('youtube'=>1);
					$illegal='';
					foreach ($cvpairs as $name=>$value) {
						if (!isset($legalnames[$name])) {
							$illegal=$name;
							break;
						}
					} 
					if ($illegal != '') {
						$o .= $matches[0]."??Illegal-Keyword:$illegal??";
						continue;
					}
					$yttag=$cvpairs['youtube'];
					if ($yttag[0] == '<') {
						if (0 == preg_match('/embed src="([^"]+)"/',$yttag,$ytmatches)) {
							$o .= $matches[0]."??Illegal-Youtube Tag:$illegal??";
							continue;
						}
						$yttag=$ytmatches[1];
					}
					$o .= '<object width="425" height="344">';
					// TODO:  put this in and read height and width
					//$o .= $this->startHTML('object',$classes);
					
					$o .= '<param name="movie" value="'.$yttag.'"></param><param name="allowFullScreen" value="true"></param><param name="allowscriptaccess" value="always"></param><embed src="'.$yttag.'" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" width="425" height="344"></embed>';
					$o .= '</object>';
				} else {
					// by default, use the callbacks to determine what it ought to be
					$extoutput=False;
					$size=sizeof($callbacks);
					foreach ($callbacks as $cb) {
						if (is_array($cb[0])) {
							$type=get_class($cb[0][0]);
							$extoutput=$cb[0][0]->$cb[0][1]($first,$cvpairs,$cb[1]);
						} else {
							$extoutput=$cb[0]($first,$cvpairs,$cb[1]);
						}		
						if (False !== $extoutput) {
							$o .= $extoutput;
							break;
						}	
					}
					// if we never found a matching function print out the nonmatching extension into the output
					if (False === $extoutput)
						$o .= $matches[0].'??Unknown-Extension??';
				}				
			}
		} else {
			// a special character, but doesn't match syntax.  output as text
			$o .= substr($line,0,1);
			$line=substr($line,1);
		}
	} // loop through line
	// if this is a line where we need to force end of formatting codes, do so
	if ($bForceClose) {
		while (($code=array_pop($codes)) != NULL)
		$o .= $code;
	}
	return $o;
}

// helper function to find cell length in tables
private function checkRange($line,$ixbar,$offset,$startcode,$endcode) {
	$ixblockstart=strpos($line,$startcode,$offset);
	if ($ixblockstart !== False && $ixblockstart < $ixbar) {
		// it's possible that our bar is in the range.  first of all, is there really a range at all?
		// note:  false degrading to 0 is OK here:
		$ixblockend=strpos($line,$endcode,$offset);
		if ($ixblockend > $ixbar) {
			// bar is inside range-- move past
			return $ixblockend+strlen($endcode);
		}
	}
	return False;	// no problem with this range
}

// returns an error string ready to be appended to output
public function addClasses(&$classes,$classAddString) {
	if ($classAddString == '')
		return '';
	$classdata=explode(';',$classAddString);
	$retval='';
	foreach ($classdata as $line) {
		$i=strpos($line,'.');
		if ($i != 0) {
			$classkeys=explode(',',substr($line,0,$i));
			$j=strpos($line,'/');
			if ($j === False) {
				// default if no slash is just to display with class and bump count
				$classname=substr($line,$i+1);
				$classact='*+';
			} else {
				// get specified actions
				$classname=substr($line,$i+1,$j-$i-1);
				$classact=substr($line,$j+1);
			}
			foreach ($classkeys as $classkey) {
				if (!isset($classes[$classkey]))
					$classes[$classkey]=array();
				$classes[$classkey][$classname]=$classact;
			}	
		} else
			$retval .= '<pre>++'.$line.' (bad class form--missing .)</pre>';
	} // for all semicolon-separated class specs
	return $retval;		
}

// returns an html start tag with class strings as necessary
public function startHTML($keyword,$classes,$suffix='') {
	global $qqi;
	
	$class='';
	$between='';
	if (isset($classes[$keyword])) {
		// find out which classes to ask for
		foreach ($classes[$keyword] as $name=>$option) {
			if (False !== strpos($option,'-') && $qqi->classExists($name))
				$qqi->resetClassCount($name);
			if (False !== strpos($option,'*') && $qqi->classExists($name)) {
				$class .= $between.$name;
				$between=' ';
			}	
			if (False !== strpos($option,'+') && $qqi->classExists($name))
				$qqi->bumpClassCount($name);
		}
		$class=$qqi->cstr($class);	
	}
	return "<$keyword$class$suffix>";
}

// returns an html string from a creole string
function creole2html($creole,$callbacks=array(),$classes=array()) {
	$o="";		// returned output
	// state variables:
	$closeFormatCodes=array();	// codes to close character formatting in lines/paragraphs
	$closeBlockCode='';			// code/status for multi-line blocks
	$bBlockQuote=False;			// if TRUE, we are in a block quote
	$bDiv=False;				// if TRUE, we are in a div (used for sidebars)
	$nestingLevel=0;			// used for levels in ordered & unordered lists.  level 0 is first level, higher for more
	
	// split string into lines
	$lines=explode("\n",$creole);
	foreach ($lines as $line) {
		$first=substr($line,0,1);
		$first2=substr($line,0,2);
		if ($first == "!")
			continue;		// comment line
		// four different cases require closure when a line does not continue them:
		if (($closeBlockCode=="</ol>" && $first != "#") ||
			($closeBlockCode=="</ul>" && ($first != "*" || substr($line,1,1) == "|")) ||
			($closeBlockCode=="</table>" && $first != "|") ||
			($closeBlockCode=="</dl>" && substr($line,0,2) != "*|")) {
			if ($nestingLevel > 0) {
				$o .= str_repeat($closeBlockCode,$nestingLevel);
				$nestingLevel=0;
			} else
				$o .= $closeBlockCode;	
			$closeBlockCode="";
			$bOrderedList=False;
		}
		if ($closeBlockCode == "</pre>") {
			if (substr($line,0,3) == "}}}") {
				// ending block.  any additional stuff on this line will be ignored
				$o .= $closeBlockCode;
				$closeBlockCode="";
				continue;
			}
			if (substr($line,0,4) == " }}}") {
				// creole spec says to strip off one space from this pattern
				$line=substr($line,1);
			}
			// TODO:  put tab width as an option
			$line=str_replace("\t",'    ',$line);
			
			// not much else to do.  make string safe and output
			$o .= htmlspecialchars($line)."<br />";
			continue;
		}
		$line=trim($line);
		// only a "pure" {{{ line will start a nowiki block--otherwise, it's inline
		if ($line == "{{{") {
			// nowiki block begins
			$closeBlockCode="</pre>";
			$o .= $this->startHTML('pre',$classes);
			continue;
		}
		// if a paragraph is started, see if it has to end
		if ($closeBlockCode == "</p>" && (False !== strpos('|*#=`',$first) || $line == "----" || $line == "" || $line == '""' || $line == '^^')) {
			// close out any pending character formatting	
			while (($code=array_pop($closeFormatCodes)) != NULL)
				$o .= $code;
			$o .= $closeBlockCode;
			$closeBlockCode="";
			if ($line == "")
				continue;		// blank line did its work-- don't add another break	
		}						
		if ($line == "") {
			// extra blank line
			$o .= $this->startHTML('br',$classes,' /');
			continue;
		}
		if ($line == '""') {
			// block quote--starting or ending
			$o .= $bBlockQuote ? '</blockquote>' : $this->startHTML('blockquote',$classes);
			$bBlockQuote= !$bBlockQuote;
			continue;
		}
		if ($line == '^^') {
			// divs trump block quotes and cannot appear in or cross block quotes
			if ($bBlockQuote) {
				$o .= '</blockquote>';
				$bBlockQuote=False;
			}
			$o .= $bDiv ? '</div>' : $this->startHTML('div',$classes);
			$bDiv= !$bDiv;
			continue;
		}
		if ($line == "----") {
			// horizontal rule
			$o .= $this->startHTML('hr',$classes,' /');
			continue;				
		}
		if (substr($line,0,2) == "*|") {
			// definition list
			if ($closeBlockCode != "</dl>") {
				$o .= $this->startHTML('dl',$classes);
				$closeBlockCode="</dl>";
			}
			$pos=strpos($line,"|",2);
			if ($pos !== False) {
				$term=substr($line,2,$pos-2);
				$def=substr($line,$pos+1);
			} else {
				$term=substr($line,2);
				$def="";
			}
			$o .= $this->startHTML('dt',$classes).$this->parseOneLine($term,$closeFormatCodes,True,$callbacks,$classes)."</dt>";
			$o .= $this->startHTML('dd',$classes).$this->parseOneLine($def,$closeFormatCodes,True,$callbacks,$classes)."</dd>";
			continue;				
		} else if ($first == "#" || $first == "*") {
			// ordered and unordered lists can be treated the same--just different codes
			if ($first == "#") {
				$code=$this->startHTML('ol',$classes);
				$closeBlockCode='</ol>';
			} else {
				$code=$this->startHTML('ul',$classes);
				$closeBlockCode='</ul>';
			}
			$oldNestingLevel=$nestingLevel;
			for ($nestingLevel=0; substr($line,$nestingLevel,1) == $first && $nestingLevel < 5; ++$nestingLevel)
				;
			// output codes to get nesting level to the right amount
			if ($oldNestingLevel < $nestingLevel) {
				// need to add new codes
				$o .= str_repeat($code,$nestingLevel-$oldNestingLevel);
			} else if ($oldNestingLevel > $nestingLevel) {
				$o .= str_repeat($closeBlockCode,$oldNestingLevel-$nestingLevel);
			}
			// a single space after a list marker is ignored
			$spaceoffset= (substr($line,$nestingLevel,1) == " ") ? 1 : 0;	
			$o .= $this->startHTML('li',$classes).$this->parseOneLine(substr($line,$nestingLevel+$spaceoffset),$closeFormatCodes,True,$callbacks,$classes)."</li>";
			continue;
		}
		if ($first2 == '++') {
			// this line allows a comment after an '!'
			if (False !== ($i=strpos($line,'!')))
				$line=substr($line,0,$i);
			$line=trim(substr($line,2));
			$o .= $this->addClasses($classes,$line);				
			continue;
		} // if a style add marker
		if ($first2 == '+-') {
			// this line allows a comment after an '!'
			if (False !== ($i=strpos($line,'!')))
				$line=substr($line,0,$i);
			// subtract a style from the style array
			$line=trim(substr($line,2));
			$i=strpos($line,'.');
			if ($i !== False) {
				if ($i != 0)
					$classkeys=explode(',',substr($line,0,$i));
				else
					$classkeys=array_keys($classes);
				$classname=substr($line,$i+1);
				
				foreach ($classkeys as $classkey) {
					if ($classname == '') {
						// eliminate all classes for this key
						unset($classes[$classkey]);
					} else if (isset($classes[$classkey][$classname])) {
						// eliminate just the class
						unset($classes[$classkey][$classname]);
					}
				}	
			} else
				$o .= '<pre>+-'.$line.' (bad class form--missing .)</pre>';
			continue;
		} // if a style subtract marker
		if ($first == '=') {
			// header line -- count '='s to determine level of heading, max 4
			for ($level=0; $level < 5 && substr($line,$level,1) == '='; ++$level)
				;
			$line=substr($line,$level);
			// trim off optional trailing '='s
			$len=strlen($line);
			for ($numtrim=0; $numtrim < $level && substr($line,$len-1-$numtrim,1) == '='; ++$numtrim)
				;
			$line=substr($line,0,$len-$numtrim);
			// check for optional anchor -- format ==|anchorname|Title==  and Title must be non-blank
			if ($line[0] == '|' && False !== ($i=strpos($line,'|',1)) && $i < strlen($line)-1) {
				$name=substr($line,1,$i-1);
				$line=substr($line,$i+1);
				// don't allow double quotes in names
				$name=str_replace('"','',$name);
				$aopen='<a name="'.$name.'">';
				$aclose='</a>';
			} else
				$aopen=$aclose='';	
			$o .= $this->startHTML("h$level",$classes).$aopen.$this->parseOneLine($line,$closeFormatCodes,True,$callbacks,$classes)."$aclose</h{$level}>";
			continue;
		}
		if ($first == '|') {
			// table
			if ($closeBlockCode != "</table>") {
				$closeBlockCode="</table>";
				$o .= $this->startHTML('table',$classes);
			}
			$o .= $this->startHTML('tr',$classes);
			// loop through all cells in row
			$line=substr($line,1);		// strip off starting bar
			while ($line != "") {
				// determine cell text
				// loop until closing bar for cell found, skipping over bars in links & graphics
				$offset=0;
				while (True) {
					$ixbar=strpos($line,"|",$offset);	// find next bar
					if ($ixbar === False) {
						// no bar found--be nice and provide the final one
						$ixbar=strlen($line);
						break;
					}
					// escape character
					if ($ixbar > 0 && substr($line,$ixbar-1,1) == '`') {
						$offset=$ixbar+1;
						continue;
					}
					// links
					if (($newoffset=$this->checkRange($line,$ixbar,$offset,"[[","]]")) !== False) {
						$offset=$newoffset;
						continue;
					}
					// be sure to do nowiki before graphics--avoids confusion
					if (($newoffset=$this->checkRange($line,$ixbar,$offset,"{{{","}}}")) !== False) {
						$offset=$newoffset;
						continue;
					}
					// graphics
					if (($newoffset=$this->checkRange($line,$ixbar,$offset,"{{","}}")) !== False) {
						$offset=$newoffset;
						continue;
					}
					// if we got here, then we found the position!
					break;
				} // loop until cell end found
				$cell=substr($line,0,$ixbar);
				if ($ixbar+1 >= strlen($line)) {
					$line="";
				} else {
					$line=substr($line,$ixbar+1);
				}
				// check for header
				if (substr($cell,0,1) == '=') {
					$keyword="th";
					$cell=substr($cell,1);
				} else
					$keyword="td";
				$rowspan=$colspan=1;
				$bColSet=False;
				// note--code duplicated below, change in both places
				if (1 == preg_match("/^_([0-9]+)_/",$cell,$matches)) {
					$cell=substr($cell,strlen($matches[0]));
					$colspan=(int)$matches[1];
					//if ($colspan > 10)
					//	$colspan=10;
					$bColSet=True;		
				}
				if (1 == preg_match("/^\^([0-9]+)\^/",$cell,$matches)) {
					$cell=substr($cell,strlen($matches[0]));
					$rowspan=(int)$matches[1];
					//if ($rowspan > 10)
					//	$rowspan=10;
					// make order indifferent.  yuck: duplicated code, almost	
					if (!$bColSet && 1 == preg_match("/^_[0-9]+_/",$cell,$matches)) {
						$cell=substr($cell,strlen($matches[0]));
						$colspan=(int)$matches[1];
						if ($colspan > 10)
							$colspan=10;
					}
				}
				$o .= substr($this->startHTML($keyword,$classes),0,-1);
				if ($colspan > 1)
					$o .= " colspan=\"{$colspan}\"";
				if ($rowspan > 1)
					$o .= " rowspan=\"{$rowspan}\"";
				// make certain table cell contents are not blank	
				$cell=$this->parseOneLine($cell,$closeFormatCodes,True,$callbacks,$classes);
				if ($cell == "")
					$cell="&nbsp;";
				$o .= ">{$cell}</{$keyword}>";
			} // loop for all cells in row
			$o .= "</tr>";
			continue;
		}
		// if we reached here, this is ordinary text going into a paragraph
		if ($closeBlockCode == '') {
			$closeBlockCode='</p>';
			$o .= $this->startHTML('p',$classes);
		} else
			$o .= ' ';		// line break always implies at least one space	
		if ($closeBlockCode != '</p>')
			throw new exception("Inernal error:  close block code mismatch in paragraph");
		$o .= $this->parseOneLine($line,$closeFormatCodes,False,$callbacks,$classes);		
	} // loop for all lines
	// clean up
	while (($code=array_pop($closeFormatCodes)) != NULL)
		$o .= $code;
	if ($nestingLevel > 0) {
		$o .= str_repeat($closeBlockCode,$nestingLevel);
		$nestingLevel=0;
	} else
		$o .= $closeBlockCode;	
	if ($bBlockQuote)
		$o .= "</blockquote>";
	if ($bDiv)
		$o .= "</div>";
	return $o;
}

// a creole lite line is all one paragraph.  It is returned without <p> markers
public function creolelite2html($creole) {
	$callbacks=array();
	$classes=array();
	$closeFormatCodes=array();
	return $this->parseOneLine($creole,$closeFormatCodes,True,$callbacks,$classes);
}

// end of util class ///////////////////////////
} ?>
