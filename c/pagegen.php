<?php if (FILEGEN != 1) die;
// class definition for package generator data
class pagegen {
////////////////////////////////////////////////////////////////////////////////

// note:  a 0 value here is reserved for a string return, i.e., redirect
const DISPLAY_404 = 1;		// display a 404 error
const DISPLAY_NORMAL = 2;	// normal page display 
const DISPLAY_UNAUTHORIZED = 3;	// unauthorized page access; display a login form
		 
const TITLE_WHOLE = 1;		// set whole title
const TITLE_PREFIX=2;		// set prefix portion of title
const TITLE_SUFFIX=3;		// set suffix portion of title
const TITLE_SEPARATOR=4;	// set separator portion of title

private $request;				// request, without anything done to it
private $pagename;				// uri that created this page
private $pageextra;				// extra data for this page
private $formpage;				// page name including form state and data
private $title_prefix = "Need a Title";
private $title_separator = " - ";
private $title_suffix = "";
private $head_description="description of site";
private $bodyAU;				// body au
private $render;				// render mode
private $robots;				// if true, allow indexing this page, otherwise not

public function __construct() {
}

public function setPage($request,$render) {
	global $qqs, $qq, $qqi, $qqj, $qqp;

	$this->render=$render;
	$this->request=$request;
	list($pagename,$pageextra,$this->formpage)=$qqp->parseRequest($request);

	if (!$qqp->pageExists($pagename)) {
		// no valid page found, even checking for subdata
		// redirect to home page if that exists if homepage doesn't exist, we're in an endless loop
		if ($pagename == $qq['homepage'])
			return self::DISPLAY_404;
		return "http://{$qq['domain']}{$qq['hrefbase']}{$qq['homepage']}";
	}
	
	// check page authorization
	if (!$qqs->checkPageAuth($pagename))
		return self::DISPLAY_UNAUTHORIZED;

	$this->pagename=$pagename;
	if (isset($qqj) && $qqj instanceof js)
		$qqj->setCurrentPage($pagename);
	$this->pageextra=$pageextra;
	list($pageid,$title,$description,$this->robots)=$qqp->getPageInfo($pagename); 
	// default page info for title and description can be changed by modules and aus.
	$this->setTitleData($title,self::TITLE_WHOLE);
	$this->setDescription($qq['sitedesc']);
	$this->setDescription($description,True);
	return self::DISPLAY_NORMAL;
}

public function output($aulong='',$brotherid='',$outputdata='') {
	global $qqs, $qq, $qqi, $qqj, $qqp, $qqy;
	
	$qqi->setOutputPage($this->pagename);
	list($formclass,$formtag,$formstate,$formdata,$forminit)=$qqp->getFormInfo($this->formpage);
	$recipe=$qqp->getBuildList($this->pagename);
	$this->bodyAU=au_base::treeFromRecipe($recipe,$this->render,$formclass,$formtag,$formstate,$formdata);
	
	// allow aus to talk before putting them out
	for ($au=$this->bodyAU; $au != Null; $au=$au->getNextAU()) {
		$au->initialize($this);
	}

	if ($aulong == '') {
		// this will call all the other aus, but only if necessary:
		$this->bodyAU->initializeOwnerBar($this,Null);
		//echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"><html>';
		echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01//EN\" \"http://www.w3.org/TR/html4/strict.dtd\"><html>";
		// do the head
		echo "<head>";
		echo "<title>{$this->getTitle()}</title>";
		echo "<meta name=\"description\" content=\"{$this->getDescription()}\" />";
		echo "<meta name=\"copyright\" content=\"{$qq['copyright']}\" />";
		echo '<meta http-equiv="Content-Type" content="text/xml; charset=windows-1252" />';
		echo '<meta http-equiv="imagetoolbar" content="no" />';
		echo '<meta name="MSSmartTagsPreventParsing" content="TRUE" />';
		echo '<meta http-equiv="EXPIRES" content="0">';
		echo '<meta http-equiv="CACHE-CONTROL" content="NO-CACHE">';
		if ($this->robots == False)
			echo '<meta name="robots" content="noindex, nofollow">';
		// these are probably not necessary:
		// echo '<meta http-equiv="Content-Script-Type" content="text/javascript" />';
		// echo '<meta http-equiv="Content-Style-Type" content="text/css" />';
		
		// TODO link prefetch control (Firefox?)
		// TODO favicon
		// TODO xhtml alternate format
		
		if (isset($qq['extrameta'])) {
			foreach ($qq['extrameta'] as $name=>$content) {
				echo "<meta name=\"$name\" content=\"$content\" />";
			}
		}
		
		// stylesheets
		$file=$qqy->getFileName($this->render,$qqs->getBrowser(),$this->pagename);
		echo "<link rel=\"stylesheet\" type=\"text/css\" media=\"screen\" href=\"{$qq['srcbase']}{$file}\" />";
	
		$file=$qqy->getFileName(state::RENDER_PRINT,$qqs->getBrowser(),$this->pagename);
		echo "<link rel=\"stylesheet\" type=\"text/css\" media=\"print\" href=\"{$qq['srcbase']}{$file}\" />";
		
		// javascript--static
		list($bNeedBS,$bNeedBE,$files,$lines)=$qqj->getFileNames($qqy,$this->render,$qqs->getBrowser(),$this->pagename);
		foreach ($files as $file)
			echo "<script type=\"text/javascript\" src=\"{$qq['srcbase']}$file\"></script>";
		// javascript--dynamic	
		if (sizeof($lines) > 0) {
			echo "<script type=\"text/javascript\">";
			echo ($qq['production']) ? str_replace("\n","",implode("",$lines)) : "\n".implode("",$lines)."\n";
			echo "</script>";
		}
		if (isset($qq['polltime'])) {
			if ($qqs->getRender() == state::RENDER_GOOD)
				echo "<script type=\"text/javascript\" src=\"{$qq['srcbase']}j/lib/jquery.js\"></script>";
			echo "<script type=\"text/javascript\" src=\"{$qq['srcbase']}j/lib/fgpoll.js\"></script>";
			echo "<script type=\"text/javascript\">fg.poll.interval={$qq['polltime']};fg.poll.proj='{$qq['project']}';fg.poll.url='{$qq['srcbase']}t/poll.php';</script>";
		}	
		
		echo "</head>";

		$this->bodyAU->bodyOutput($this,$bNeedBS,$bNeedBE);
		echo "</html>";
	} else {
		// only putting out out one au:
		$bFound=False;
		for ($au=$this->bodyAU; $au != Null; $au=$au->getNextAU()) {
			if ($au->getLongName()==$aulong) {
				$bFound=True;
				break;
			}
		}
		if (!$bFound)
			throw new exception("output requested for non existent au $aulong");
		$au->outputWithData($this,$brotherid,$outputdata);	
	}
}

/////////////// Functions that are useful to aus as callbacks: /////////////////
public function getPageExtra() {return $this->pageextra;}
public function getPageName() {return $this->pagename;}

public function findAU($longname) {
	if (!isset($this->bodyAU))
		throw new exception("cannot search for an au--list of au's not loaded yet");
	for ($au=$this->bodyAU; $au != Null; $au=$au->getNextAU()) {
		if ($au->getLongName() == $longname)
			return $au;
	}
	throw new exception("au $longname not found");	
}

public function findFlaggedAU($flag) {
	if (!isset($this->bodyAU))
		throw new exception("cannot search for an au--list of au's not loaded yet");
	for ($au=$this->bodyAU; $au != Null; $au=$au->getNextAU()) {
		if ($au->checkFlag($flag))
			return $au;
	}
	return False;	
}

/////// functions to set output package and data ///////////////////////////////
/// these functions can only be meaningfully called from a modal AU
public function setBasePackage() {
	$this->pkgcode=self::BASE;
}

public function setMessage($code) {
	$this->pkgcode=self::PREFAB;
	$this->prefabcode=$code;
	$this->prefabdata=func_get_args();
}

public function setModal($au) {
	$this->modalau=$au;
	$this->pkgcode=self::STATED;
}

/////// head-related functions /////////////////////////////////////////////////
public function getTitle() {
	return ($this->title_suffix != "") ? "{$this->title_prefix}{$this->title_separator}{$this->title_suffix}" 
	                                   : $this->title_prefix;
}

// set title, or portion of title, to text string or message index.  If data is numeric, it is an id
public function setTitleData($data,$type=self::TITLE_SUFFIX) {
	global $qqu;
	
	switch ($type) {
		case self::TITLE_WHOLE:
			$this->title_suffix="";
			$this->title_prefix=htmlentities($data);
		break;
		case self::TITLE_PREFIX:
			$this->title_prefix=htmlentities($data);
		break;
		case self::TITLE_SUFFIX:
			$this->title_suffix=htmlentities($data);
		break;
		case self::TITLE_SEPARATOR:
			$this->title_separator=htmlentities($data);
		break;
		default:
			// do nothing
		break;			
	}
}

// set description to text string or message index.  If data is numeric, it is an id
public function getDescription() {return $this->head_description;}
public function setDescription($desc,$bAppend=False) {
	global $qqu;
	
	if ($bAppend)
		$this->head_description .= " ".$desc;
	else
		$this->head_description=$desc;	
}

//  end of class definition ////////////////////////////////////////////////////
}
?>
