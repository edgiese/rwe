<?php if (FILEGEN != 1) die;
class au_filemanagement extends au_base {
///////////////////////// class definition for file management ////////////////
private $state;
private $data;
			
function __construct($tag,$parent,$initstring,$state="",$data=0) {
	parent::__construct($tag,$parent,$initstring);
	$this->state=$state;
	$this->transaction=(int)$data;
}

// returns form and form formatting string
private function makeform() {

	$form=new llform($this);
	$form->addFieldset('upload','Upload a New File');
	$form->addFieldset('filter','Filter Files');
	$form->addFieldset('sort','Sort Order');
	$form->addFieldset('display','Display Options');
	$form->addFieldset('gotopage','Go to page');
	
	$form->addControl('upload',new ll_edit($form,'filename',25,40,'Description (leave blank to use file name)'));
	$form->addControl('upload',new ll_file($form,'file',5*1024*1024));
	$form->addControl('upload',new ll_button($form,'upload','Upload'));
	
	$form->addControl('filter',$rgf=new ll_radiogroup($form,'filter'));
	$rgf->setOptionArrayDual(array('showall','byname'),array('Show All Entries','Show those whose name starts ...'));
	$form->addControl('filter',new ll_edit($form,'filterstring',12,20,'... with the following string:'));
	$form->addControl('sort',$rgs=new ll_radiogroup($form,'sorttype'));
	$rgs->setOptionArrayDual(array('newestfirst','oldestfirst','name'),array('Newest First','Oldest First','By Name'));
	$form->addControl('display',new ll_edit($form,'numperpage',3,3,'Number of files per page'));
	$form->addControl('display',new ll_button($form,'update','Update Display'));
	$form->addControl('display',new ll_button($form,'defaults','Restore Defaults'));
	
	$form->addControl('gotopage',new ll_button($form,'firstpage','<<< First Page'));
	$form->addControl('gotopage',new ll_button($form,'prevpage','<< Prev Page'));
	$form->addControl('gotopage',new ll_dropdown($form,'pagenum'));
	$form->addControl('gotopage',new ll_button($form,'nextpage','Next Page >>'));
	$form->addControl('gotopage',new ll_button($form,'lastpage','Last Page >>>'));
	
	return $form;
}

public function declareids($idstore,$state) {
	$form=$this->makeform();
	$form->declareids($idstore);
	// in case you want to attach js to the upload button.  using the form submit event might be better...
	//$idstore->lockHTMLid($form->getControl('upload')->getLongName(),"j")
	
	$idstore->registerAuthPage('maintain files',False);
	$idstore->registerProfile('filemanagement_sorttype',crud::TYPE_STRING,'','newestfirst');
	$idstore->registerProfile('filemanagement_filterstring',crud::TYPE_STRING,'','');
	$idstore->registerProfile('filemanagement_numperpage',crud::TYPE_INT,'',50);
	$idstore->registerProfile('filemanagement_pagenum',crud::TYPE_INT,'',1);

}

public function declarestyles($stylegen,$state) {
	$stylegen->registerStyledId($this->longname,'div:table,td,tr,th,p,a','filemanagement',$this->getParent()->htmlContainerId());
	$this->makeform()->declarestyles($stylegen,$this->longname);
	$stylegen->registerClass('fileformtable','td');
}

// process vars only called for uploaded file
public function processVars($originUri) {
	global $qq,$qqs,$qqi;

	// get data from form:
	$form=$this->makeForm();
	$vals=$form->getValueArray();
	if ($form->wasPressed('upload')) {
		$ctrl=$form->getControl('file');
		$file=file::createFromUpload($ctrl,$vals['filename']);
		if (is_string($file)) {
			// error occurred
			throw new exception("need error handling:  $file");
		} else {
			// make certain that new upload is first displayed in table:
			$qqs->setProfile('filemanagement_sorttype','newestfirst');
			$qqs->setProfile('filemanagement_filterstring','');
			$qqs->setProfile('filemanagement_numperpage',12);
			$qqs->setProfile('filemanagement_pagenum',1);
			$retval=$qqi->hrefPrep($originUri);
		}
	} else if ($form->wasPressed('defaults')) {
		$qqs->setProfile('filemanagement_sorttype','newestfirst');
		$qqs->setProfile('filemanagement_filterstring','');
		$qqs->setProfile('filemanagement_numperpage',12);
		$qqs->setProfile('filemanagement_pagenum',1);
		$retval=$qqi->hrefPrep($originUri);
	} else if ($form->wasPressed('update')) {
		$profileelements=array('sorttype','filterstring','numperpage','pagenum');
		foreach ($profileelements as $pe) {
			if (isset($vals[$pe])) {
				$qqs->setProfile('filemanagement_'.$pe,$vals[$pe]);
			}	
		}
		if ($vals['filter'] == 'showall')	
			$qqs->setProfile('filemanagement_filterstring','');
		$retval=$qqi->hrefPrep($originUri);
	} else {
		// nav button pressed
		$p_filterstring=$qqs->getProfile('filemanagement_filterstring');
		$ids=file::getAllIds(file::SORTBY_NEWESTFIRST,$p_filterstring);
		
		$p_numperpage=$qqs->getProfile('filemanagement_numperpage');
		$p_pagenum=$qqs->getProfile('filemanagement_pagenum');
		if (isset($vals['pagenum']))
			$p_pagenum=$vals['pagenum'];
		$numpages=sizeof($ids)/$p_numperpage;
		if ((sizeof($ids) % $p_numperpage) != 0)
			++$numpages;
		$numpages=max(1,$numpages);
		if ($form->wasPressed('firstpage'))
			$p_pagenum=1;
		else if ($form->wasPressed('prevpage'))
			--$p_pagenum;
		else if ($form->wasPressed('nextpage'))
			++$p_pagenum;
		else if ($form->wasPressed('lastpage'))
			$p_pagenum=$numpages;
			
		$p_pagenum=min($p_pagenum,$numpages);
		$p_pagenum=max($p_pagenum,1);
		$qqs->setProfile('filemanagement_pagenum',$p_pagenum);
		$retval=$qqi->hrefPrep($originUri);
	}
	return $retval;	 
}

// does output of this plus all children
public function output($pgen,$brotherid) {
	global $qqs,$qqi;

	// read all the profile elements for easy display
	$profileelements=array('sorttype','filterstring','numperpage','pagenum');
	$profile=array();
	foreach ($profileelements as $pe)
		$profile['p_'.$pe]=$qqs->getProfile('filemanagement_'.$pe);
	extract($profile);
		
	$sortopts=array('newestfirst'=>file::SORTBY_NEWESTFIRST,'oldestfirst'=>file::SORTBY_OLDESTFIRST,'name'=>file::SORTBY_NAME,'type'=>file::SORTBY_TYPE);
	$ids=file::getAllIds($sortopts[$p_sorttype],$p_filterstring);
	$position=$p_pagenum*$p_numperpage;
	
	echo "<div{$qqi->idcstr($this->longname)}>";
	
	$form=$this->makeform();
	echo $form->getOutputFormStart('',0);
	echo $form->getDumpStyleFieldsetOutput('upload','&nbsp;&nbsp;&nbsp;',':&nbsp;&nbsp;');
	echo "<table><tr><td{$qqi->cstr('fileformtable')}>";
	if ($p_filterstring != '') {
		$form->setValue('filterstring',$p_filterstring);
		$form->setValue('filter','byname');
	} else
		$form->setValue('filter','showall');
	echo $form->getDumpStyleFieldsetOutput('filter');
	echo "</td><td{$qqi->cstr('fileformtable')}>";
	$form->setValue('sorttype',$p_sorttype);
	echo $form->getDumpStyleFieldsetOutput('sort');
	echo "</td><td{$qqi->cstr('fileformtable')}>";
	$form->setValue('numperpage',$p_numperpage);
	echo $form->getDumpStyleFieldsetOutput('display');
	echo "</td></tr></table>";
	$numpages=sizeof($ids)/$p_numperpage;
	if ((sizeof($ids) % $p_numperpage) != 0)
		++$numpages;
	$numpages=max(1,$numpages);
	$p_pagenum=min($p_pagenum,$numpages);
	$plb=$form->getControl('pagenum');
	for ($i=1; $i<=$numpages; ++$i)
		$plb->addOption((string)$i,(string)$i);
	$plb->setValue((string)$p_pagenum);	
	echo $form->getDumpStyleFieldsetOutput('gotopage','&nbsp;&nbsp;&nbsp;');
	echo $form->getOutputFormEnd('',0);
	echo "<br />";

	$iStart=($p_pagenum-1)*$p_numperpage;
	$iEnd=$iStart+$p_numperpage-1;
	$iStart=min($iStart,sizeof($ids)-1);
	$iEnd=min($iEnd,sizeof($ids)-1);
	
	if (sizeof($ids) > 0) {
		echo "<table><tr><th>Name</th><th>Type</th><th>Wiki Coding</th><th>Link</th></tr>";
		for ($i=$iStart; $i<=$iEnd; ++$i) {
			$file=new file($ids[$i]);
			echo "<tr>";
			// name
			echo "<td>{$file->getName()}</td>";
			// type
			echo "<td>{$file->getType()}</td>";
			// wiki coding
			echo "<td>{{file='{$file->getName()}'}}</td>";
			// link output
			echo "<td>{$file->getOutput()}</td>";
			echo "</tr>";
			unset($file);
		}
		echo "</table>";
	} // if there were files to display	

	echo "</div>";
}

/////// end of AU definition ///////////////////////////////////////////////////
}?>
