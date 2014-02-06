<?php if (FILEGEN != 1) die;
// this is a library of utility functions that is outside the mainstream of
// page generation.  It is in a separate file to optimize source code
class utilrare1 {

// this routine is useful for outputting xml template files.  the template needs to
// have variables marked by %param%.  There is one repeating section that can be marked with
// <!-- start repeat --> and <!-- end repeat -->
// returns the contents of the file.
public function fileStringSubstitution($templatefile,$globalsub,$repeatsub) {
	$ftemplate=file_get_contents($templatefile);
	$startmark='<!-- start repeat -->';
	$endmark='<!-- end repeat -->';
	$startmarkpos=strpos($ftemplate,$startmark);
	if ($startmarkpos === False) {
		$feedstart=$ftemplate;
		$feedend='';
		if (is_array($repeatsub))
			throw new exception("template file $templatefile does not contain a repeating section, but one is needed");	
	} else {
		$feedstart=substr($ftemplate,0,$startmarkpos);
		$endmarkpos=strpos($ftemplate,$endmark);
		if ($endmarkpos === False) {
			// repeating section goes all the way to the end
			$feedrepeat=substr($ftemplate,$startmarkpos+strlen($startmark));
			$feedend='';
		} else {
			// repeating section has an end afterwards
			$feedrepeat=substr($ftemplate,$startmarkpos+strlen($startmark),$endmarkpos-$startmarkpos-strlen($startmark));
			$feedend=substr($ftemplate,$endmarkpos+strlen($endmark));
		}
	}
	// global search & replace arrays
	$gsearch=array();
	$greplace=array();
	if (is_array($globalsub)) {
		foreach ($globalsub as $sitem=>$ritem) {
			$search[]='%'.$sitem.'%';
			$replace[]=$ritem;
		}
	}
	// initialize feed with starting piece
	$feed=str_replace($gsearch,$greplace,$feedstart);
	
	if (is_array($repeatsub)) {
		foreach ($repeatsub as $rsubarray) {
			$feedpiece=str_replace($gsearch,$greplace,$feedrepeat);
			$rsearch=array();
			$rreplace=array();
			if (is_array($rsubarray)) {
				foreach ($rsubarray as $sitem=>$ritem) {
					$rsearch[]='%'.$sitem.'%';
					$rreplace[]=$ritem;
				}
				$feedpiece=str_replace($rsearch,$rreplace,$feedpiece);
			}
			$feed .= $feedpiece;
		} // for each array of substitutions
	} // if there are repeated substitutions
	
	$feed .= str_replace($gsearch,$greplace,$feedend);
	return $feed;
	
	while (($row=$result->FetchRow()) && $nPodcast++ < 4) {
		$rss .= str_replace(
					array("%title%","%subtitle%","%summary%","%fileurl%","%size%","%description%","%pubdate%","%duration%"),
					array(
						"Sermon for ".$row["fdate"],
						$row["text"],
						$row["theme"],
						"http://www.resurrectionfbg.org/audio/".$row["file"],
						$row["size"],
						sprintf("Sermon given at Resurrection Lutheran Church in Fredericksburg, Texas on %s by Pastor Ed Giese.  The theme of the worship service is \"%s,\" and the text of the sermon is %s.",$row["fdate"],$row["theme"],$row["text"]),
						gmdate("D, j M Y H:i:s T",$row["timestamp"]),
						sprintf("%02d:%02d",(int)$row["lengthsec"]/60,(int)$row["lengthsec"]%60)
					),
			$rss_middle);
	}
	$result->Close();
	$rss .= $rss_bottom;
}

////////////////////////////////////////////////////// text indexing routines

// returns a text string separated by spaces (only)
static function getSearchableText($text,$bCreole=False) {

	// we're removing a lot of semantic information here.  room for improvement.
	$text=trim(preg_replace("/[^0-9a-zA-Z'.]/",' ',$text));
	do {
		$text=str_replace('  ',' ',$text);
	} while (False !== strpos($text,'  '));
	$words=explode(' ',$text);
	$keywords=array();
	foreach ($words as $word) {
		$word=strtolower(trim($word,"'."));
		if ($word != '') {
			$keywords[]=$word;
			if (substr($word,-1) == 's') {
				$word=substr($word,0,-1);
				$keywords[]=trim($word,"'");				
			}
		}
	}
	return implode(' ',$keywords);
}

// returns array of words and counts
static function getKeywords($text,$bCreole=False) {
	$words=explode(' ',self::getSearchableText($text,$bCreole));
	sort($words);
	$count=0;
	$retval=array();
	for ($i=0; $i<sizeof($words); ++$i) {
		++$count;
		if ($i >= sizeof($words)-1 || $words[$i+1] != $words[$i]) {
			$retval[$words[$i]]=$count;
			$count=0;
		}
	}
	return $retval;
}

// returns number of words indexed
static function searchIndexText($id) {
	global $qqc;
	
	if (False === ($textinfo=$qqc->getRows("text::getfromid",1,(string)$id)))
		throw new exception("cannot find keywords for textid $id");
		
	$qqc->act("search::clearIndexForId",$id);
	$keywords=self::getKeywords($textinfo['text'],$textinfo['iscreole']);
	$nWords=0;
	foreach ($keywords as $keyword=>$count) {
		if (strlen($keyword) > 30)
			continue;
		++$nWords;	
		if (False ===($idword=$qqc->getRows('search::idFromSearchWord',1,(string)$keyword))) {
			$idword=(int)$qqc->insert('search::addSearchWord',(string)$keyword);
		}
		$qqc->insert('search::addIndexItem',$id,$idword,$count);
	}
	return $nWords;
}

// rebuilds the entire search index for the site.  should be admin level only
// returns array(# of items added,# of words added)
static function rebuildSearchIndex() {
	global $qqc;
	
	set_time_limit(0);
	
	$qqc->act("search::deleteAllSrchIndex");
	$qqc->act("search::deleteAllKeywords");
	
	$ids=$qqc->getCols("text::getAllTextIds",-1);
	$totalWords=$totalItems=0;
	foreach ($ids as $id) {
		$totalWords += self::searchIndexText($id);
		$totalItems++;
	}
	return array($totalItems,$totalWords);
}

// returns an array of id=>count of text entities containing a keyword
static function getKeywordCounts($keyword,$src=-1) {
	global $qqc;
	
	$retval=array();	
	$kwindex=$qqc->getRows("search::idFromSearchWord",1,$keyword);
	if ($kwindex === False)
		return $retval;		// keyword does not exist in any entries

	if ($src > -1) {
		$cols=$qqc->getCols("search::getAllMatchingIds1src",-1,$kwindex,$src);			
	} else {
		$cols=$qqc->getCols("search::getAllMatchingIds",-1,$kwindex);			
	}
	if ($cols === False)
		return $retval;
	for ($i=0; $i<sizeof($cols['idtext']); ++$i)
		$retval[$cols['idtext'][$i]]=$cols['wcount'][$i];
				
	return $retval;			
}

// end of library definition
} ?>
