<?php if (FILEGEN != 1) die;
///// Access Unit definition file for bulk product description editing
class au_ecp_export extends au_base {
////////////////////////////////////////////////////////////////////////////////
function __construct($tag,$parent,$initdata,$state="",$data=0) {
	parent::__construct($tag,$parent,$initdata);
}

private function makeform() {
	$form=new llform($this,'form');
	
	$form->addFieldset('desc','Text Description Info');
	$form->addControl('desc',new ll_file($form,'descfile',5*1024*1024));
	$form->addControl('desc',new ll_button($form,'uploaddesc','Upload'));
	
	$form->addFieldset('image','Product Images (ZIP file of id.jpg)');
	$form->addControl('image',new ll_file($form,'imagefile',5*1024*1024));
	$form->addControl('image',new ll_button($form,'uploadimage','Upload'));

	
	return $form;
}

public function declareids($idstore,$state) {
	$idstore->declareHTMLid($this);
	$this->makeform()->declareids($idstore);
		
	$idstore->registerAuthPage('view product edit page',False);
	$idstore->registerAuthBool('editproddesc',"Edit Product Entries",False);
}

public function declarestyles($stylegen,$state) {
	$stylegen->registerStyledId($this->longname,'div:p,a','bulkedit',$this->getParent()->htmlContainerId());
	$this->makeform()->declarestyles($stylegen,$this->longname);
}


public function processVars($originUri) {
	global $qq,$qqs;

	$pi=new mod_prodinfo;
	$pm=new mod_prodmain;
	
	if (!$qqs->checkAuth('editproddesc'))
		return $originUri;		// no authorization.  die silently--this shouldn't happen, since links won't appear if not authorized
	$form=$this->makeForm();
	$vals=$form->getValueArray();
	$msgs=array();

	if ($form->wasPressed('uploadimage')) {
		$filename=$form->getControl('imagefile')->getTempName();
	    $zip = new ZipArchive;
	    $nProcessed=0;
	    if ($zip->open($filename)) {
	        for ($i=0; $i<$zip->numFiles; $i++) {
	            $entry=$zip->getNameIndex($i);
	            if (substr($entry,-1) == '/')
					continue; // skip directories
				if (!preg_match('`^(.*/)*(\\d+)\\.(jpg|gif|jpeg|png)$`i',$entry,$matches))
					continue; // skip non-compliant file names
				$id=(int)$matches[2];
				$zip->extractTo('g/',$entry);
				$image=image::createFromUpload("g/$entry","product image for $id",320,320);
				unlink("g/$entry");
				if ($image instanceof image)
					$pi->setMainImg($id,$image->getId());
				$msgs[]="Processed image for #$id";	
				++$nProcessed;
	        }
            $msgs[]="  ... {$nProcessed} Image(s) Processed.";
            $zip->close();
	    }
	} else if ($form->wasPressed('uploaddesc')) {
		$filename=$form->getControl('descfile')->getTempName();
		if ($filename === False) {
			$msgs[]='No file name passed.  Nothing processed.';
			$qqs->setSessionData('bulkmessages',$msgs);
			return $originUri;
		}	
		$lines=file($filename);
		$id=0;
		$bCollectingDesc=False;
		$nProcessed;
		foreach ($lines as $line) {
			$cmdline=trim($line);
			try {
				if (substr($cmdline,0,6) == '>>>id=') {
					++$nProcessed;
					$id=(int)trim(substr($cmdline,6),'+');
					if (False === $pi->getProdInfo($id))
						$pi->initProdDesc($id);
					continue;
				} else if ($id == 0)
					continue;		// skip lines until id set
				if ($bCollectingDesc) {
					if ($cmdline == '>>>/desc') {
						$pi->setDesc($id,$desc);
						$bCollectingDesc=False;
						continue;
					}
					$desc .= $line;
					continue;
				}	
				if ($cmdline == '>>>desc:') {
					$bCollectingDesc=True;
					$desc='';
					continue;
				}
				if (substr($cmdline,0,7) == '>>>mfr=') {
					$txtitle=trim(substr($cmdline,7));
					if ($txtitle != '') {
						if ($pi->setMfr($id,$txtitle))
							$msgs[]="Added new manufacturer for id #$id: $txtitle";
						
					} else {
						$msgs[]="Skipped Mfr for id #$id";
					}	
					continue;
				}
				if (substr($cmdline,0,9) == '>>>title=') {
					$txtitle=trim(substr($cmdline,9));
					$pi->setTitle($id,$txtitle);
					continue;
				}
				if (substr($cmdline,0,12) == '>>>prodhold=') {
					$info['prodhold']=(int)(trim(substr($cmdline,12)));
					$pm->setProductInfo($id,$info);
					continue;
				}
				if (substr($cmdline,0,9) == '>>>notes=') {
					$info['notes']=trim(substr($cmdline,9));
					$pm->setProductInfo($id,$info);
					continue;
				}
			} catch (Exception $e) {
				$msgs[]="problem processing product id #$id: {$e->getMessage()}";
			}
		}
		$msgs[]=" ... Information for $nProcessed id(s) processed.";	
	}
	$msgs[]='Processing Successful.';
	$qqs->setSessionData('bulkmessages',$msgs);
	return $originUri;
}

public function processFile($subtype) {
	global $qqs,$qqi,$qqu;
	header ("Content-type: application/text");
	header("Content-Disposition: attachment; filename=$subtype.txt");
	header("Content-Description: Bulk Edit Data");

	$pm=new mod_prodmain;
	$pi=new mod_prodinfo;
	
	if ($subtype == 'onhold')
		$ids=$pm->onHoldItems(True);
	else if ($subtype == 'all')
		$ids=$pm->getAllIds();
	else if ($subtype == 'addedbysync')
		$ids=$pm->getAllAddedBySync();
			
	echo sizeof($ids)." items found\n";	
	foreach ($ids as $id) {
		echo ">>>id=+++++++++++++++++++++++++++++++++++++++++++++++++++$id\n";
		$maininfo=$pm->getProductInfo($id);
		echo ">>>*invtitle={$maininfo['invdesc']}\n";
		echo ">>>*upc={$maininfo['barcodes'][0]}\n";
		echo ">>>*discontinued=".($maininfo['discontinued'] ? '1' : '0')."\n";
		echo ">>>prodhold=".($maininfo['prodhold'] ? '1' : '0')."\n";
		echo ">>>notes={$maininfo['notes']}\n";
		$info=$pi->getProdInfo($id);
		echo ">>>mfr={$info['mfr']}\n";
		echo ">>>title={$info['title']}\n";
		echo ">>>image=".($info['imageid'] == 0 ? 'No' : 'Yes')."\n";
		echo ">>>desc:\n{$info['desc']}\n>>>/desc\n";
		echo "\n";
	}

}

// does output of this plus all children
public function output($pgen,$brotherid) {
	global $qq,$qqs,$qqi;

	$project=$qq['production'] ? '' : "&u={$qq['project']}";
	$hrefall=$qqi->hrefPrep("file.php?r={$this->longname}&s=all$project",False);
	$hrefonhold=$qqi->hrefPrep("file.php?r={$this->longname}&s=onhold$project",False);
	$hrefnew=$qqi->hrefPrep("file.php?r={$this->longname}&s=addedbysync$project",False);

	$form=$this->makeForm();
	
	echo "<div{$qqi->idcstr($this->longname)}>";	
	if ($qqs->sessionDataExists('bulkmessages')) {
		$msgs=$qqs->getSessionData('bulkmessages');
		if (sizeof($msgs) > 0) {
			echo '<div style="position: static; background-color:white; color:black; margin:20px; padding:20px;"><p>Processing Results:</p>';
			foreach ($msgs as $msg) {
				echo '<p>'.htmlentities($msg)."</p>";
			}
			echo '</div>';
		}
		$qqs->clearSessionData('bulkmessages');
	}
	echo $form->getDumpStyleFormOutput('',0);
	echo "<p><a href=\"$hrefall\">All Products</a></p>";
	echo "<p><a href=\"$hrefonhold\">Products on Hold</a></p>";
	echo "<p><a href=\"$hrefnew\">Products added by sync</a></p>";
	echo '</div>';
}

/////// end of AU definition ///////////////////////////////////////////////////
}?>
