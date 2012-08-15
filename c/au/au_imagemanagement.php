<?php if (FILEGEN != 1) die;
class au_imagemanagement extends au_base {
///////////////////////// class definition for image management ////////////////
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
	$form->addFieldset('upload','Upload a New Image');
	$form->addFieldset('filter','Filter Images');
	$form->addFieldset('sort','Sort Order');
	$form->addFieldset('boundingbox','Bounding Box');
	$form->addFieldset('display','Display Options');
	$form->addFieldset('gotopage','Go to page');
	
	$form->addControl('upload',new ll_edit($form,'imagename',25,40,'Image name (leave blank for file name)'));
	$form->addControl('upload',new ll_file($form,'file',5*1024*1024));
	$form->addControl('upload',new ll_button($form,'upload','Upload'));
	
	$form->addControl('filter',$rgf=new ll_radiogroup($form,'filter'));
	$rgf->setOptionArrayDual(array('showall','byname'),array('Show All Entries','Show those whose name starts ...'));
	$form->addControl('filter',new ll_edit($form,'filterstring',12,20,'... with the following string:'));
	$form->addControl('sort',$rgs=new ll_radiogroup($form,'sorttype'));
	$rgs->setOptionArrayDual(array('newestfirst','oldestfirst','name'),array('Newest First','Oldest First','By Name'));
	$form->addControl('display',new ll_edit($form,'numperpage',3,3,'Number of images per page'));
	$form->addControl('display',new ll_button($form,'update','Update Display'));
	$form->addControl('display',new ll_button($form,'defaults','Restore Defaults'));
	$form->addControl('boundingbox',new ll_edit($form,'maxwidth',3,3,'Max Width'));
	$form->addControl('boundingbox',new ll_edit($form,'maxheight',3,3,'Max Height'));
	
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
	
	$idstore->registerAuthPage('maintain images',False);
	$idstore->registerProfile('imagemanagement_sorttype',crud::TYPE_STRING,'','newestfirst');
	$idstore->registerProfile('imagemanagement_filterstring',crud::TYPE_STRING,'','');
	$idstore->registerProfile('imagemanagement_numperpage',crud::TYPE_INT,'',12);
	$idstore->registerProfile('imagemanagement_maxwidth',crud::TYPE_INT,'',200);
	$idstore->registerProfile('imagemanagement_maxheight',crud::TYPE_INT,'',150);
	$idstore->registerProfile('imagemanagement_pagenum',crud::TYPE_INT,'',1);

}

public function declarestyles($stylegen,$state) {
	$stylegen->registerStyledId($this->longname,'div:table,td,tr,th,p','imagemanagement',$this->getParent()->htmlContainerId());
	$this->makeform()->declarestyles($stylegen,$this->longname);
	$stylegen->registerClass('imageformtable','td');
//	$stylegen->registerClass('visitorrequesterror','p,td');
}

public function declarestaticjs($js) {
	global $qq;

	$form=$this->makeform();
	$args=$js->seedArgs($this);
	// add events to the filters box
	$js->addjs('$','imagemanagement::setup',$args);
}

// process vars only called for uploaded image
public function processVars($originUri) {
	global $qq,$qqs,$qqi;

	// get data from form:
	$form=$this->makeForm();
	$vals=$form->getValueArray();
	if ($form->wasPressed('upload')) {
		$ctrl=$form->getControl('file');
		$maxwidth=isset($qq['maximagewidth']) ? $qq['maximagewidth'] : 640; 
		$maxheight=isset($qq['maximageheight']) ? $qq['maximageheight'] : 640; 
		$image=image::createFromUpload($ctrl,$vals['imagename'],$maxwidth,$maxheight);
		if (is_string($image)) {
			// error occurred
			throw new exception("need error handling:  $image");
		} else {
			// make certain that new upload is first displayed in table:
			$qqs->setProfile('imagemanagement_sorttype','newestfirst');
			$qqs->setProfile('imagemanagement_filterstring','');
			$qqs->setProfile('imagemanagement_numperpage',12);
			$qqs->setProfile('imagemanagement_pagenum',1);
			$retval=$qqi->hrefPrep($originUri);
		}
	} else if ($form->wasPressed('defaults')) {
		$qqs->setProfile('imagemanagement_sorttype','newestfirst');
		$qqs->setProfile('imagemanagement_filterstring','');
		$qqs->setProfile('imagemanagement_numperpage',12);
		$qqs->setProfile('imagemanagement_maxwidth',200);
		$qqs->setProfile('imagemanagement_maxheight',150);
		$qqs->setProfile('imagemanagement_pagenum',1);
		$retval=$qqi->hrefPrep($originUri);
	} else if ($form->wasPressed('update')) {
		$profileelements=array('sorttype','filterstring','numperpage','maxwidth','maxheight','pagenum');
		foreach ($profileelements as $pe) {
			if (isset($vals[$pe])) {
				$qqs->setProfile('imagemanagement_'.$pe,$vals[$pe]);
			}	
		}
		if ($vals['filter'] == 'showall')	
			$qqs->setProfile('imagemanagement_filterstring','');
		$retval=$qqi->hrefPrep($originUri);
	} else {
		// nav button pressed
		$p_filterstring=$qqs->getProfile('imagemanagement_filterstring');
		$ids=image::getAllIds(image::SORTBY_NEWESTFIRST,$p_filterstring);
		
		$p_numperpage=$qqs->getProfile('imagemanagement_numperpage');
		$p_pagenum=$qqs->getProfile('imagemanagement_pagenum');
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
		$qqs->setProfile('imagemanagement_pagenum',$p_pagenum);
		$retval=$qqi->hrefPrep($originUri);
	}
	return $retval;	 
}

// called when an ajax call occurs.  output is javascript to be executed upon return
public function processAjax($js,$originUri,$page) {
	global $qqs,$qqi;

	if (!isset($_REQUEST['id']) || !isset($_REQUEST['scl'])) {
		echo "alert('image management internal error #3343')";
		return;
	}
	$id=$_REQUEST['id'];
	$image=new image($id);
	$bScaled=$_REQUEST['scl'];
	if ($bScaled) {
		$image->setMaximumSize($qqs->getProfile('imagemanagement_maxwidth'),$qqs->getProfile('imagemanagement_maxheight'));
		$rowid='sir_'.$id;
	} else
		$rowid='fir_'.$id;
	echo "fg.image.insert($id,$bScaled,'<tr id=\"$rowid\"><td colspan=\"5\">{$image->getOutput()}</td></tr>');";
}


// does output of this plus all children
public function output($pgen,$brotherid) {
	global $qqs,$qqi;

	// read all the profile elements for easy display
	$profileelements=array('sorttype','filterstring','numperpage','maxwidth','maxheight','pagenum');
	$profile=array();
	foreach ($profileelements as $pe)
		$profile['p_'.$pe]=$qqs->getProfile('imagemanagement_'.$pe);
	extract($profile);
		
	$sortopts=array('newestfirst'=>image::SORTBY_NEWESTFIRST,'oldestfirst'=>image::SORTBY_OLDESTFIRST,'name'=>image::SORTBY_NAME);
	$ids=image::getAllIds($sortopts[$p_sorttype],$p_filterstring);
	$position=$p_pagenum*$p_numperpage;
	
	echo "<div{$qqi->idcstr($this->longname)}>";
	
	$form=$this->makeform();
	echo $form->getOutputFormStart('',0);
	echo $form->getDumpStyleFieldsetOutput('upload','&nbsp;&nbsp;&nbsp;',':&nbsp;&nbsp;');
	echo "<table><tr><td{$qqi->cstr('imageformtable')}>";
	if ($p_filterstring != '') {
		$form->setValue('filterstring',$p_filterstring);
		$form->setValue('filter','byname');
	} else
		$form->setValue('filter','showall');
	echo $form->getDumpStyleFieldsetOutput('filter');
	echo "</td><td{$qqi->cstr('imageformtable')}>";
	$form->setValue('sorttype',$p_sorttype);
	echo $form->getDumpStyleFieldsetOutput('sort');
	echo "</td><td{$qqi->cstr('imageformtable')}>";
	$form->setValue('maxwidth',$p_maxwidth);
	$form->setValue('maxheight',$p_maxheight);
	echo $form->getDumpStyleFieldsetOutput('boundingbox');
	echo "</td><td{$qqi->cstr('imageformtable')}>";
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
		echo "<table><tr><th>Name</th><th>Full Size Display Coding</th><th>Scaled Display Coding</th><th>Full Dimensions</th><th>Thumbnail</th></tr>";
		for ($i=$iStart; $i<=$iEnd; ++$i) {
			$img=new image($ids[$i]);
			echo "<tr id=\"row_{$ids[$i]}\">";
			// name
			echo "<td>{$img->getName()}</td>";
			// full size display encoding
			echo "<td>";
			echo "{{img='{$img->getName()}'}}<br /><br />";
			echo "<label><input type=\"checkbox\" id=\"f_{$ids[$i]}\" onclick=\"fg.image.prv({$ids[$i]},0)\"/>Preview</label>";
			echo "</td>";
			// scaled size display encoding
			echo "<td>";
			echo "{{img='{$img->getName()}' maxwidth=$p_maxwidth maxheight=$p_maxheight}}<br /><br />";
			echo "<label><input type=\"checkbox\" id=\"s_{$ids[$i]}\" onclick=\"fg.image.prv({$ids[$i]},1)\"/>Preview</label>";
			echo "</td>";
			// image dimensions
			list($width,$height)=$img->getNaturalSize();
			echo "<td>$width x $height</td>";
			// thumbnail
			$img->setMaximumSize(96,96);
			echo "<td>{$img->getOutput()}</td>";
			echo "</tr>";
			unset($img);
		}
		echo "</table>";
	} // if there were images to display	

	echo "</div>";
}

/////// end of AU definition ///////////////////////////////////////////////////
}?>
