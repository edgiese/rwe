<?php if (FILEGEN != 1) die;
class au_proj_hcncrg extends au_base {
/////////////au definition for hill country needs council resource guide ///////
private $action;		// action being performed in ajax mode
private $data;			// data (not currently used)
			
function __construct($tag,$parent,$initstring,$state="",$data=0) {
	parent::__construct($tag,$parent,$initstring);
	$this->action=$state;
	$this->transaction=(int)$data;
}


private function makeform() {
	global $qqi;
	
	$form=new llform($this);
	$form->addFieldset('d','');
	$form->addControl('d',new ll_listbox($form,'filters','Limit to these Keywords'));
	$form->addControl('d',new ll_button($form,'addfilter','<<','submit'));
	$form->addControl('d',new ll_button($form,'removefilter','>>','submit'));
	$form->addControl('d',new ll_listbox($form,'exclusions','Exclude these Keywords'));
	$form->addControl('d',new ll_button($form,'addexclusion','>>','submit'));
	$form->addControl('d',new ll_button($form,'removeexclusion','<<','submit'));
	$form->addControl('d',new ll_listbox($form,'keywords','Include Keywords'));
	$form->addControl('d',new ll_edit($form,'search',30,50,'Search for this string in Title or Description'));

	$form->addControl('d',new ll_checkbox($form,'showkeywords','Show Keywords for each Entry'));
	$form->addControl('d',new ll_checkbox($form,'shownotes','Show Notes for each Entry'));
	$form->addControl('d',new ll_checkbox($form,'showhistory','Show History for each Entry'));
	$form->addControl('d',new ll_edit($form,'mingroup',3,3,'Mininum Entries in a Keyword Category (Print Only)'));
	$form->addControl('d',new ll_checkbox($form,'fullsummary','Show Full Summary by Default (Print Only)'));
	
	$form->addControl('d',new ll_button($form,'screen','View Onscreen Directory','submit'));
	$form->addControl('d',new ll_button($form,'update','Update Options & Redisplay Report','submit'));
	$form->addControl('d',new ll_button($form,'print','Printable Directory','submit'));
	return $form;
}

private function makeeditform() {
	global $qqi;
	
	$form=new llform($this,"edit");
	$form->addFieldset('d','Entry Information');
	$form->addControl('d',new ll_edit($form,'title',100,120,'Title'));
	$form->addControl('d',new ll_textarea($form,'desc',100,5,'Description'));
	$form->addControl('d',new ll_edit($form,'keywords',100,120,'Keywords'));
	$form->addControl('d',new ll_textarea($form,'notes',100,5,'Notes'));
	$form->addControl('d',new ll_edit($form,'changedesc',50,80,'Description of Changes'));
	$form->addControl('d',new ll_button($form,'ok','Ok','submit'));
	$form->addControl('d',new ll_button($form,'cancel','Cancel','button'));
	$form->setExtraValue('editedid','-1');
	return $form;
}

public function declareids($idstore,$state) {
	$form=$this->makeform();
	$form->declareids($idstore);
	$idstore->lockHTMLid($this->longname.'_filters');
	$idstore->lockHTMLid($this->longname.'_exclusions');
	$idstore->lockHTMLid($this->longname.'_keywords');
	$editform=$this->makeeditform();
	$editform->declareids($idstore,True);
	$idstore->declareHTMLid($this,True,'formdiv');
	$idstore->declareHTMLid($this,True,'reportdiv');
	$idstore->declareHTMLid($this,True,'editdiv');
	
	$idstore->registerProfile('proj_hcncrg_filters',crud::TYPE_STRING,'','');
	$idstore->registerProfile('proj_hcncrg_exclusions',crud::TYPE_STRING,'','');
	$idstore->registerProfile('proj_hcncrg_search',crud::TYPE_STRING,'','');
	$idstore->registerProfile('proj_hcncrg_showkeywords',crud::TYPE_BOOL,'',False);
	$idstore->registerProfile('proj_hcncrg_shownotes',crud::TYPE_BOOL,'',False);
	$idstore->registerProfile('proj_hcncrg_showhistory',crud::TYPE_BOOL,'',False);
	$idstore->registerProfile('proj_hcncrg_mingroup',crud::TYPE_INT,'',3);
	$idstore->registerProfile('proj_hcncrg_fullsummary',crud::TYPE_BOOL,'',False);
	
	$idstore->registerAuthBool('proj_hcncrg_viewnotes','Resource Guide: view notes',True);	
	$idstore->registerAuthBool('proj_hcncrg_viewhistory','Resource Guide: view history',True);	
	$idstore->registerAuthBool('proj_hcncrg_edit','Resource guide: edit entries',False);	
	$idstore->registerAuthBool('proj_hcncrg_delete','Resource Guide: delete entries',False);	
}

public function declarestyles($stylegen,$state) {
	$form=$this->makeform();
	$form->declarestyles($stylegen,$this->longname);
	$editform=$this->makeeditform();
	$editform->declarestyles($stylegen,$this->longname."_edit");
	
	$stylegen->registerStyledId("{$this->longname}_formdiv",'div:fieldset,td,th','hcnc_resource',$this->getParent()->htmlContainerId());
	$stylegen->registerStyledId("{$this->longname}_reportdiv",'div:h1,h2,h3,p','hcnc_resource',$this->getParent()->htmlContainerId());
	$stylegen->registerStyledId("{$this->longname}_editdiv",'div','hcnc_resource',$this->getParent()->htmlContainerId());
	$stylegen->registerClass('proj_hcncrg_notes','div');
	$stylegen->registerClass('proj_hcncrg_history','p');
	$stylegen->registerClass('proj_hcncrg_keywords','div');
	$stylegen->registerClass('proj_hcncrg_linkbutton','p,a');
}

public function declarestaticjs($js) {
	global $qq;

	$form=$this->makeform();
	$args=$js->seedArgs($this);
	// add events to the filters box
	$js->addjs('$','proj_hcncrg::setup',$args);
}

// called when an ajax call occurs.  output is javascript to be executed upon return
public function processAjax($js,$originUri,$page) {
	global $qqs,$qqi,$qq;

	// currently only ajax processed by this au is to return a form for an edit				
	if (!$qqs->checkAuth('proj_hcncrg_edit')) {
		echo "<h1>Unauthorized Action</h1>";
		return;
	}
	if (!isset($_REQUEST['id'])) {
		echo "<h1>Resource Guide Internal Error #3353</h1>";
		return;
	}
	$id=$_REQUEST['id'];
	$form=$this->makeeditform();
	$rg=new mod_proj_hcncrg();
	list($title,$keywords,$desc,$notes,$historystring)=$rg->getInfo($id);
	
	$form->setValue('title',$title);
	$form->setValue('desc',$desc);
	$form->setValue('keywords',$keywords);
	$form->setValue('notes',$notes);
	$form->setValue('changedesc','General Editing');
	$form->setExtraValue('editedid',$id);

	$qqi->setOutputPage($page);
	$qq['request']=$qq[hrefbase].$page."/report";	
	echo $form->getDumpStyleFormOutput('',0);
}

const DESCRIPTION_FULL = 1;
const DESCRIPTION_PARTIAL_OR_FULL = 2;
const DESCRIPTION_PARTIAL_OR_NONE = 3;

// returns height required and updates $lasttitle
// NOTE: unless $bCalcOnly is set, the returned height is meaningless
private function reportEntry($pdf,$rg,$id,$showkeywords,$shownotes,$showhistory,&$lasttitle,$descOpt,$kwarray,$bCalcOnly=False) {
	$options=array('leading'=>12,'left'=>0,'justification'=>'left');
	$height=0;
	list($title,$keywords,$description,$notes,$historystring)=$rg->getInfo($id);
	$pdf->selectFont('l/pdflib/fonts/Helvetica-Bold');
	if ($title != $lasttitle) {
		$options['left']=0;	
		$options['leading']=25;
		$height += $pdf->ezText($title,15,$options,$bCalcOnly);
		$options['leading']=12;
	} else	
		$height += $pdf->ezText("\n",12,$options,$bCalcOnly);

	$pdf->selectFont('l/pdflib/fonts/Times-Roman');
	$descripton=str_replace('****','',$description);
	$description=preg_replace("/\\*\\*([^\\*]*)\\*\\*/", "<u>\\1</u>",$description);
	if (False !== ($i=strpos($description,'\\\\')) && $descOpt != self::DESCRIPTION_FULL) {
		$description=substr($description,0,$i);	
	} else if ($i === False && $descOpt == self::DESCRIPTION_PARTIAL_OR_NONE)
		$description="";
	$options['left']=8;
	if ($description != '')	
		$description=str_replace("\\\\","\n",$description);	
		$height += $pdf->ezText($description,12,$options,$bCalcOnly);
	if ($shownotes && $notes != '') {
		$pdf->selectFont('l/pdflib/fonts/Helvetica-Bold');
		$options['leading']=18;
		$height += $pdf->ezText("Notes:",12,$options,$bCalcOnly);
		$options['leading']=12;
		$pdf->selectFont('l/pdflib/fonts/Times-Roman');
		$notes=preg_replace("/\\*\\*([^\\*]*)\\*\\*/", "<u>\\1</u>",$notes);
		$notes=str_replace("\\\\","\n",$notes);	
		$options['left']=19;	
		$height += $pdf->ezText($notes,12,$options,$bCalcOnly);
	}
	if ($showkeywords) {
		if ($kwarray !== False) {
			// adjust keywords to reflect actual ones used
			$entrykeywords=explode(' ',trim($keywords));
			$keywords='';
			$separator='';
			foreach ($entrykeywords as $ekw) {
				if (isset($kwarray[$ekw])) {
					$keywords .= $separator.str_replace('_',' ',ucfirst($ekw))." ({$kwarray[$ekw]})";
					$separator=", ";
				}
			}
			if ($keywords == '') {
				$keywords="Miscellaneous ({$kwarray['miscellaneous']})";
			}
			$pdf->selectFont('l/pdflib/fonts/Times-Italic');
		} else {
			$keywords=str_replace('_',' ',str_replace(' ',', ',trim($keywords)));
			$pdf->selectFont('l/pdflib/fonts/Helvetica-Bold');
			$options['leading']=18;
			$options['left']=8;	
			$height += $pdf->ezText("Keywords:",12,$options,$bCalcOnly);
			$options['leading']=12;
			$pdf->selectFont('l/pdflib/fonts/Times-Roman');
		}	
		$options['left']=19;	
		$height += $pdf->ezText($keywords,12,$options,$bCalcOnly);
	}
	if ($showhistory) {
		$pdf->selectFont('l/pdflib/fonts/Helvetica-Bold');
		$options['leading']=18;
		$options['left']=8;	
		$height += $pdf->ezText("History:",12,$options,$bCalcOnly);
		$options['leading']=12;
		$pdf->selectFont('l/pdflib/fonts/Times-Roman');
		$options['left']=19;	
		$height += $pdf->ezText($historystring,12,$options,$bCalcOnly);
	}
	return $height;
}

// returns an array sectionname=>pagenumber
private function reportKeywordSections($pdf,$rg,$id,$filters,$exclusions,$search,$showkeywords,$shownotes,$showhistory,$mingroup) {
	// second section:  resources by category
	
	$bFirst=True;
	$retval=array();
	$keywords=$rg->getKeywords();
	$exclusionsmatch=" $exclusions ";
	$printedfilters=array();
	foreach($keywords as $kw) {
		if (False !== strpos($exclusionsmatch," $kw "))
			continue;	// we won't be including this one anyway
		// check to see if there are enough in this keyword to print a section
		$ids=$rg->getIds($filters." ".$kw,$exclusions,$search);
		if (sizeof($ids) > $mingroup) {
			$printedfilters[]=$kw;
			$pdf->ezNewPage();
			if ($bFirst)
				$pdf->ezStartPageNumbers(576,752,12,'left','{PAGENUM}',1);
			$bFirst=False;	
			$retval[$kw]=$pdf->ezGetCurrentPageNumber()-1;
			$pdf->ezText(str_replace('_',' ',strtoupper($kw))."\n",14,array('justification'=>'centre'));
			foreach ($ids as $id) {
				$height=$this->reportEntry($pdf,$rg,$id,$showkeywords,$shownotes,$showhistory,$lasttitle,self::DESCRIPTION_FULL,False,True);
				if (!$pdf->ezIsRoom($height))
					$pdf->ezNewPage();
				$this->reportEntry($pdf,$rg,$id,$showkeywords,$shownotes,$showhistory,$lasttitle,self::DESCRIPTION_FULL,False);
			}
		}		
	}
	// now see if there are any requested entries that haven't printed
	$ids=$rg->getIds($filters,$exclusions." ".implode(' ',$printedfilters),$search);
	if (sizeof($ids) > 0) {
		$pdf->ezNewPage();
		if ($bFirst)
			$pdf->ezStartPageNumbers(576,752,12,'left','{PAGENUM}',1);
		$retval['miscellaneous']=$pdf->ezGetCurrentPageNumber()-1;
		$pdf->ezText("MISCELLANEOUS\n",14,array('justification'=>'centre'));
		$lasttitle='';
		foreach ($ids as $id) {
			$height=$this->reportEntry($pdf,$rg,$id,$showkeywords,$shownotes,$showhistory,$lasttitle,self::DESCRIPTION_FULL,False,True);
			if (!$pdf->ezIsRoom($height))
				$pdf->ezNewPage();
			$this->reportEntry($pdf,$rg,$id,$showkeywords,$shownotes,$showhistory,$lasttitle,self::DESCRIPTION_FULL,False);
		}
	}
	return $retval;
}

private function doReport($search,$showkeywords,$shownotes,$showhistory,$mingroup,$filters,$exclusions,$bFullSummary) {
	global $qqs;

	// set up the pdf file
	include ('l/pdflib/ezpdf.php');
	
	$shownotes=$shownotes && $qqs->checkAuth('proj_hcncrg_viewnotes');
	$showhistory=$showhistory && $qqs->checkAuth('proj_hcncrg_viewhistory');
	
	$rg=new mod_proj_hcncrg();

	// first, we are going to go through the sections to determine page numbers to the TOC
	$pdf=new Cezpdf("LETTER");
	$pdf->selectFont('l/pdflib/fonts/Helvetica');
	$pdf->ezSetMargins(36,30,60,48);
	$keywordpages=$this->reportKeywordSections($pdf,$rg,$id,$filters,$exclusions,$search,$showkeywords,$shownotes,$showhistory,$mingroup);
	unset($pdf);
	$pdf=new Cezpdf("LETTER");
	$pdf->ezSetMargins(36,30,60,48);
	$pdf->selectFont('l/pdflib/fonts/Helvetica');

	// first section:  all matching resources
	$ids=$rg->getIds($filters,$exclusions,$search);
	$nMatch=sizeof($ids);
	$options=array("justification"=>"centre");
	$pdf->ezText("Hill Country Community Needs Council",16,$options);
	$pdf->ezText("Resource Guide",20,$options);
	$pdf->ezText("Generated from www.needscouncil.org on ".date('m/d/Y'),10,$options);
	if ($filters != '')
		$pdf->ezText("Including only entries with keywords:  $filters",11,$options);
	if ($exclusions != '')
		$pdf->ezText("Omitting all entries with keywords:  $exclusions",11,$options);
	if ($search != '')	
		$pdf->ezText("Including only entries with titles or descriptions containing text '$search'",11,$options);
		
	$pdf->selectFont('l/pdflib/fonts/Times-Italic');
	$options['justification']='left';
	$options['left']=22;
	$pdf->ezText("\n<u>Disclaimer</u>: Hill Country Community Needs Council assumes no responsibility for accuracy of information contained in the Resource Directory.\n\n",10);

	$options['justification']='centre';
	$options['left']=0;
	$pdf->selectFont('l/pdflib/fonts/Helvetica');
	$options['leading']=36;
	$pdf->ezText("Table of Contents & Summary",14,$options);
	
	$option=$bFullSummary ? self::DESCRIPTION_PARTIAL_OR_FULL : self::DESCRIPTION_PARTIAL_OR_NONE;
	$lasttitle='';
	foreach ($ids as $id) {
		$height=$this->reportEntry($pdf,$rg,$id,True,False,False,$lasttitle,$option,$keywordpages,True);
		if (!$pdf->ezIsRoom($height))
			$pdf->ezNewPage();
		$this->reportEntry($pdf,$rg,$id,True,False,False,$lasttitle,$option,$keywordpages);
	}
	
	// now actually output the sections
	$this->reportKeywordSections($pdf,$rg,$id,$filters,$exclusions,$search,$showkeywords,$shownotes,$showhistory,$mingroup);
			
	$debug=False;
	// output the file
	if ($debug){
		$pdfcode = $pdf->ezOutput(1);
		$pdfcode = str_replace("\n","\n<br>",htmlspecialchars($pdfcode));
		echo '<html><body>';
		echo trim($pdfcode);
		echo '</body></html>';
	} else
		$pdf->ezStream();
}

public function processVars($originUri) {
	global $qqs;
	
	// this is a stateless au, but it processes three different forms or commands
	if (isset($_REQUEST['delid'])) {
		if (!$qqs->checkAuth('proj_hcncrg_delete'))
			return $originUri;		// fail silently
		$rg=new mod_proj_hcncrg();
		$rg->deleteEntry($_REQUEST['delid']);
	} else if (isset($_REQUEST['editedid'])) {
		if (!$qqs->checkAuth('proj_hcncrg_edit'))
			return $originUri;		// fail silently
			
		// this is the edit form for individual entries
		$form=$this->makeeditform();
		// read all form fields into variables
		extract($form->getValueArray());
		$rg=new mod_proj_hcncrg();
		
		// process keywords
		$keywords=str_replace(',',' ',$keywords);
		$count=1;
		while ($count != 0)
			$keywords=str_replace('  ',' ',$keywords,&$count);
		$keywords=trim($keywords);
		
		if ($editedid == '0') {
			// authority to insert is same as ability to delete
			if (!$qqs->checkAuth('proj_hcncrg_delete'))
				return $originUri;		// fail silently
			// inserting a new entry
			$editedid=$rg->addItem($title,$desc,$keywords,$notes);			
		} else {
			$rg->updateItem($editedid,$title,$keywords,$desc,$notes,$changedesc);
		}
		// take us back to edited id
		$originUri .= "#rep_$editedid";		
	} else {
		// this is the overall report form
		$form=$this->makeform();
		
		// read all form fields into variables
		extract($form->getValueArray());
	
		// remove any of the hack-dummy lines used to widen the controls	
		if (isset($keywords['?']))
			unset($keywords['?']);
		if (isset($filters['?']))
			unset($filters['?']);
		if (isset($exclusions['?']))
			unset($exclusions['?']);
	
		// move keywords around as necessary
		if ($form->wasPressed('addfilter')) {
			// add a selected keywords to the filter list
			$newlist=strlen($qqs->getProfile('proj_hcncrg_filters')) > 0 ? explode(' ',$qqs->getProfile('proj_hcncrg_filters')) : array();
			foreach ($keywords as $name=>$dummy)
				$newlist[]=$name;
			$newlist=array_unique($newlist);
			sort($newlist);
			$qqs->setProfile('proj_hcncrg_filters',implode(' ',$newlist));
		} else if ($form->wasPressed('removefilter')) {
			// remove selected filters from the filter list
			$newlist=strlen($qqs->getProfile('proj_hcncrg_filters')) > 0 ? explode(' ',$qqs->getProfile('proj_hcncrg_filters')) : array();
			foreach ($filters as $name=>$remove) {
				if (False !== ($i=array_search($name,$newlist)))
					unset($newlist[$i]);
			}
			$qqs->setProfile('proj_hcncrg_filters',implode(' ',$newlist));
		} else if ($form->wasPressed('addexclusion')) {
			// add a selected exclusions to the exclusion list
			$newlist=strlen($qqs->getProfile('proj_hcncrg_exclusions')) > 0 ? explode(' ',$qqs->getProfile('proj_hcncrg_exclusions')) : array();
			foreach ($keywords as $name=>$dummy)
				$newlist[]=$name;
			$newlist=array_unique($newlist);
			sort($newlist);
			$qqs->setProfile('proj_hcncrg_exclusions',implode(' ',$newlist));
		} else if ($form->wasPressed('removeexclusion')) {
			// remove selected filters from the filter list
			$newlist=strlen($qqs->getProfile('proj_hcncrg_exclusions')) > 0 ? explode(' ',$qqs->getProfile('proj_hcncrg_exclusions')) : array();
			foreach ($exclusions as $name=>$remove) {
				if (False !== ($i=array_search($name,$newlist)))
					unset($newlist[$i]);
			}
			$qqs->setProfile('proj_hcncrg_exclusions',implode(' ',$newlist));
		} else {
			// read filters and exclusions from hidden variables in form.  the javascript may have changed those
			if (isset($_REQUEST['jsfilters'])) {
				$jsfilters= get_magic_quotes_gpc() ? stripslashes($_REQUEST['jsfilters']) : $_REQUEST['jsfilters'];
				$qqs->setProfile('proj_hcncrg_filters',$jsfilters);
			}	
			if (isset($_REQUEST['jsexclusions'])) {
				$jsexclusions= get_magic_quotes_gpc() ? stripslashes($_REQUEST['jsexclusions']) : $_REQUEST['jsexclusions'];
				$qqs->setProfile('proj_hcncrg_exclusions',$jsexclusions);
			}	
		}
		
		$qqs->setProfile('proj_hcncrg_search',$search);
		$qqs->setProfile('proj_hcncrg_showkeywords',$showkeywords);
		$qqs->setProfile('proj_hcncrg_shownotes',$shownotes && $qqs->checkAuth('proj_hcncrg_viewnotes'));
		$qqs->setProfile('proj_hcncrg_showhistory',$showhistory && $qqs->checkAuth('proj_hcncrg_viewhistory'));
		$qqs->setProfile('proj_hcncrg_mingroup',$mingroup);
		$qqs->setProfile('proj_hcncrg_fullsummary',$fullsummary);
	
		if ($form->wasPressed('screen'))
			$originUri .= "/report";
		else if ($form->wasPressed('print')) {
			$this->doReport($search,$showkeywords,$shownotes,$showhistory,$mingroup,$qqs->getProfile('proj_hcncrg_filters'),$qqs->getProfile('proj_hcncrg_exclusions'),$qqs->getProfile('proj_hcncrg_fullsummary'));
			// this will have done the output, so don't redirect:
			return False;
		}	
	} // if main report form
			
	return $originUri;	 
}

// does output of this plus all children
public function output($pgen,$brotherid) {
	global $qqs,$qqi;
	$form=$this->makeform();
	$form->setExtraValue('filters',$qqs->getProfile('proj_hcncrg_filters'));
	$form->setExtraValue('exclusions',$qqs->getProfile('proj_hcncrg_exclusions'));
	
	$rg=new mod_proj_hcncrg();
	
	$optionbutton=($pgen->getPageExtra() == 'report') ? 'update' : 'screen';

	// put out the data entry form, which is hidden, if it may appear later
	if ($qqs->checkAuth('proj_hcncrg_edit')) {
		$editform=$this->makeeditform();
		echo "<div".$qqi->idcstr($this->longname.'_editdiv').">";
		echo $editform->getDumpStyleFormOutput('',0);
		echo '</div>';
	}

	// set values in form according to the profile
	$filters=strlen($qqs->getProfile('proj_hcncrg_filters')) > 0 ? explode(' ',$qqs->getProfile('proj_hcncrg_filters')) : array();
	$listbox=$form->getControl('filters');
	// what a hack.  keep the stupid listboxes from being too narrow
	$dummystring='&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp';
	foreach($filters as $f)
		$listbox->addOption($f,str_replace('_',' ',$f));
	if (sizeof($filters) == 0)	
		$listbox->addOption('?',$dummystring);	

	$exclusions=strlen($qqs->getProfile('proj_hcncrg_exclusions')) > 0 ? explode(' ',$qqs->getProfile('proj_hcncrg_exclusions')) : array();
	$listbox=$form->getControl('exclusions');
	foreach($exclusions as $e)
		$listbox->addOption($e,str_replace('_',' ',$e));
	if (sizeof($exclusions) == 0)	
		$listbox->addOption('?',$dummystring);	

	// the central keywords listbox contains all available keywords less the ones in the side boxes
	$keywords=$rg->getKeywords();
	foreach($filters as $f) {
		if (False !== ($i=array_search($f,$keywords)))
			unset($keywords[$i]);
	}				
	foreach($exclusions as $e) {
		if (False !== ($i=array_search($e,$keywords)))
			unset($keywords[$i]);
	}
	$listbox=$form->getControl('keywords');
	foreach($keywords as $k)
		$listbox->addOption($k,str_replace('_',' ',$k));
	if (sizeof($keywords) == 0)	
		$listbox->addOption('?',$dummystring);	
	
	$search=$qqs->getProfile('proj_hcncrg_search');
	$form->setValue('search',$search);
	$bShowKeywords=$qqs->getProfile('proj_hcncrg_showkeywords');
	$form->setValue('showkeywords',$bShowKeywords);
	$bShowNotes=$qqs->getProfile('proj_hcncrg_shownotes');
	$form->setValue('shownotes',$bShowNotes);
	$bShowHistory=$qqs->getProfile('proj_hcncrg_showhistory');
	$form->setValue('showhistory',$bShowHistory);
	$mingroup=$qqs->getProfile('proj_hcncrg_mingroup');
	$form->setValue('mingroup',$mingroup);
	$bFullSummary=$qqs->getProfile('proj_hcncrg_fullsummary');
	$form->setValue('fullsummary',$bFullSummary);

	// now output the form
	$format="<div".$qqi->idcstr($this->longname.'_formdiv')."><fieldset><legend>Directory Contents</legend>";
	$format .= "<table><tr><th colspan=\"5\">Keywords for Entries to include in the Directory...</th></tr><tr><td>...Must Include:<br /><<filters>></td><td><br /><<addfilter>><br /><br /><<removefilter>></td>";
	$format .= "<td>...Can Include:<br /><<keywords>></td><td><br /><<addexclusion>><br /><br /><<removeexclusion>></td>";
	$format .= "<td>...Must not Include:<br /><<exclusions>></td></tr></table>";
	$format .= "<br /><<!search>>: <<search>></fieldset>";
	$format .= "<fieldset><legend>Directory Format</legend><<showkeywords>>";
	if ($qqs->checkAuth('proj_hcncrg_viewnotes'))
		$format .= "<br /><<shownotes>>";	
	if ($qqs->checkAuth('proj_hcncrg_viewhistory'))
		$format .= "<br /><<showhistory>>";	
	$format .= "<br /><<!mingroup>>: <<mingroup>><br /><<fullsummary>></fieldset>";
	$format .= "<<$optionbutton>>&nbsp&nbsp&nbsp<<print>></div>";
	echo $form->getFormattedOutput($format);
	
	// do report if necessary
	if ($optionbutton == 'update') {
		// we need to display an onscreen report
		$ids=$rg->getIds($filters,$exclusions,$search);
		$nMatch=sizeof($ids);
		echo "<div".$qqi->idcstr($this->longname.'_reportdiv')."><h1>Resources matching the criteria above: $nMatch</h1>";
		$lasttitle='';
		foreach ($ids as $id) {
			echo "<div id=\"rep_$id\">";
			list($title,$keywords,$description,$notes,$historystring)=$rg->getInfo($id);
			if ($title != $lasttitle) {
				echo "<h2>$title</h2>";
				$lasttitle=$title;	
			} else	
				echo "<br />";
			$descripton=str_replace('****','',$description);
			$description=preg_replace("/\\*\\*([^\\*]*)\\*\\*/", "<strong>\\1</strong>",$description);
			$description=str_replace("\\\\","\n",$description);	
			$description=nl2br($description);	
			echo "<p>$description</p>";
			if ($bShowNotes && $notes != '') {
				$notes=str_replace('****','',$notes);
				$notes=preg_replace("/\\*\\*([^\\*]*)\\*\\*/", "<strong>\\1</strong>",$notes);
				$notes=str_replace("\\\\","\n",$notes);	
				echo "<div{$qqi->cstr('proj_hcncrg_notes')}><h3>Notes:</h3><p>$notes</p></div>";
			}
			if ($bShowKeywords) {
				$keywords=str_replace('_',' ',str_replace(' ',', ',trim($keywords)));
				echo "<div{$qqi->cstr('proj_hcncrg_keywords')}><h3>Keywords:</h3><p>$keywords</p></div>";
			}
			if ($bShowHistory) {
				$historystring=nl2br($historystring);	
				echo "<p{$qqi->cstr('proj_hcncrg_history')}><strong>History:</strong><br />$historystring</p>";
			}
			if ($bShowHistory && $qqs->checkAuth('proj_hcncrg_edit')) {
				echo "<p{$qqi->cstr('proj_hcncrg_linkbutton')}><a{$qqi->cstr('proj_hcncrg_linkbutton')} href=\"javascript:fg.rg.edit($id)\">EDIT</a>";
				if ($qqs->checkAuth('proj_hcncrg_delete'))
					echo "&nbsp;&nbsp;&nbsp;<a{$qqi->cstr('proj_hcncrg_linkbutton')} href=\"javascript:fg.rg.remove($id)\">DELETE</a>";
				echo "</p>";	
			}
			echo "</div>";		// reporting block	
		}
		if ($qqs->checkAuth('proj_hcncrg_delete')) {
			echo "<br /><p id=\"insert_button\"{$qqi->cstr('proj_hcncrg_linkbutton')}><a{$qqi->cstr('proj_hcncrg_linkbutton')} href=\"javascript:fg.rg.edit(0)\">INSERT NEW ENTRY</a></p>";	
		}	
		echo "</div>";
	} else {
		echo "<div".$qqi->idcstr($this->longname.'_formdiv')."><h1>To View the Resource directory, Click Select Contents and Viewing options and use the 'View Onscreen Directory' Button</h1></div>";
	}
}
////////////////////////////////////////end of class definition/////////////////
} ?>
