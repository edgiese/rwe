<?php if (FILEGEN != 1) die;
///// Access Unit definition file for audio library
class au_audiolib extends au_base {
////////////////////////////////////////////////////////////////////////////////
private $state;
private $data;
private $libname;								// name of this audiolibrary
private $info1title,$info2title,$info3title;	// title of fields

function __construct($tag,$parent,$initdata,$state="",$data=0) {
	parent::__construct($tag,$parent,$initdata);
	$initdata=parent::getInit($initdata,'name|info1|info2|info3');
	
	$this->libname=$initdata['name'];
	$this->info1title=$initdata['info1'];
	$this->info2title=$initdata['info2'];
	$this->info3title=$initdata['info3'];

	$this->state=$state;
	$this->transaction=(int)$data;
}

const STATE_NONE = '';
const STATE_GETDATA = 'updating';
const STATE_FINISHUPDATE = 'finishupdate';
const STATE_GETUPLOADDATA = 'uploading';
const STATE_FINISHUPLOAD = 'finishuploading';
const STATE_DELETE = 'deleting';

private function makeform($state) {
	$form=new llform($this,'form');
	switch ($state) {
		case self::STATE_NONE:
			$form->addFieldset('upload','Upload a New File');
			$form->addControl('upload',new ll_file($form,'file',5*1024*1024));
			$form->addControl('upload',new ll_button($form,'upload','Upload'));
		break;

		case self::STATE_GETUPLOADDATA:
		case self::STATE_GETDATA:
			$form->addFieldset('entrydata','Information about this Entry');
			$form->addControl('entrydata',new ll_edit($form,'info1',25,128,$this->info1title));
			$form->addControl('entrydata',new ll_edit($form,'info2',25,128,$this->info2title));
			$form->addControl('entrydata',new ll_edit($form,'info3',25,128,$this->info3title));
			$form->addControl('entrydata',new ll_edit($form,'recorddate',25,128,'Date Recorded'));
			$form->addControl('entrydata',new ll_button($form,'upload','Set Data'));
			$form->addControl('entrydata',new ll_button($form,'cancel','Cancel'));
		break;			
	}
	return $form;
}

public function declareids($idstore,$state) {
	$idstore->declareHTMLid($this);
	if ($state == '*') {
		$this->makeform(self::STATE_NONE)->declareids($idstore);
		$this->makeform(self::STATE_GETDATA)->declareids($idstore);
	} else
		$this->makeform($state)->declareids($idstore);
		
	$idstore->registerAuthBool('addto_'.$this->longname,"Add entries to audio library",False);
	$idstore->registerAuthBool('edit_'.$this->longname,"Edit entries in audio library",False);
	$idstore->registerAuthBool('delete_'.$this->longname,"Delete entries from audio library",False);
}

public function declarestyles($stylegen,$state) {
	$stylegen->registerStyledId($this->longname,"div:h1,a,p,table,th,td","audiolib",$this->getParent()->htmlContainerId());
	if ($state == '*') {
		$this->makeform(self::STATE_NONE)->declarestyles($stylegen,$this->longname);
		$this->makeform(self::STATE_GETDATA)->declarestyles($stylegen,$this->longname);
	} else
		$this->makeform($state)->declarestyles($stylegen,$this->longname);
	$stylegen->registerClass('audiolibcmdlink','a');
}


public function processVars($originUri) {
	global $qq,$qqs;
	
	// any transaction-based forms need to be stated
	if (!$qqs->isStated())
		return message::redirectString(message::MISSINGCOOKIE);
		
	if ($this->transaction != 0) {
		$transaction=$this->transaction;
		if ($qqs->isTransactionFinished($transaction))
			return message::redirectString(message::TRANSACTIONFINISHED);
	} else
		$transaction=0;		

	switch ($this->state) {
		case self::STATE_NONE:
			throw new exception("logic error.  no state available during variable processing");
		break;
		
		case self::STATE_DELETE:
			if (!$qqs->checkAuth('delete_'.$this->longname))
				return $originUri;		// no authorization.  die silently--this shouldn't happen, since links won't appear if not authorized
			if (!isset($_REQUEST['identry']))
				throw new exception("required id missing from delete command");
			$mod=new mod_audiolib($this->libname);
			$mod->deleteEntry($_REQUEST['identry']);		
			return $originUri;		
		break;
		
		case self::STATE_GETUPLOADDATA:
			if (!$qqs->checkAuth('addto_'.$this->longname))
				return $originUri;		// no authorization.  die silently--this shouldn't happen, since links won't appear if not authorized
			// user specified an uploaded file.  create a file object, get the length/check the file, and
			// set up to get the rest of the information
			$form=$this->makeForm(self::STATE_NONE);
			$vals=$form->getValueArray();
			$file=file::createFromUpload($form->getControl('file'));
			if (is_string($file)) {
				// error occurred
				throw new exception("need error handling:  $file");
			} else {
				// check to make certain that this is a valid file.
				$mod=new mod_audiolib($this->libname);
				$duration=$mod->getFileDuration($file);
				if ($duration == 0)
					return message::redirectString("=Invalid Audio File=\nThe file you uploaded does not appear to be an audio file.  There may have been a transmission error.\n\n[[$originUri|Go Back]]");
				
				if ($transaction == 0)
					$transaction=$qqs->beginTransaction($originUri);
				$qqs->setTransactionData($transaction,"filedata",array('file'=>$file,'duration'=>$duration));
				$retval=llform::redirectString(self::STATE_GETUPLOADDATA,$transaction);
			}
		break;

		case self::STATE_FINISHUPLOAD:
			// user specified information about the file.  finish making the entry, or if cancel pressed, cancel the operation
			if ($transaction != 0) {
				$tdata=$qqs->getTransactionData($transaction,'filedata');
				$duration=$tdata['duration'];
				$file=$tdata['file'];
			}	
			$form=$this->makeForm(self::STATE_GETUPLOADDATA);
			$vals=$form->getValueArray();
			if ($form->wasPressed('cancel')) {
				// delete file if it exists
				if (isset($file))
					file::delete($file->getId());
				return $originUri;
			}
			// add the entry
			if (!isset($file))
				throw new exception("expected file not set");
			$mod=new mod_audiolib($this->libname);
			$date=date_create($vals['recorddate']);
			$mod->createEntry($file,$vals['info1'],$vals['info2'],$vals['info3'],$date,$duration);
			if ($transaction != 0)
				$qqs->finishTransaction($transaction);
			$retval=$qqs->transactionOriginUri($transaction);
		break;

		case self::STATE_GETDATA:
			if (!$qqs->checkAuth('edit_'.$this->longname))
				return $originUri;		// no authorization.  die silently--this shouldn't happen, since links won't appear if not authorized
			// link to this point should set 'identry' to the id to fill data with.
			if (!isset($_REQUEST['identry']))
				throw new exception("expected value for identry not set");
			if ($transaction == 0)
				$transaction=$qqs->beginTransaction($originUri);
			$mod=new mod_audiolib($this->libname);
			$vals=$mod->getFileInfo($_REQUEST['identry']);
			$vals['recorddate']=$vals['recorddate']->format('m/d/Y');
			if ($vals === False)
				throw new exception("expected data for identry={$_REQUEST['identry']} not found");
			$vals['identry']=$_REQUEST['identry'];	
			$qqs->setTransactionData($transaction,"fileinfo",$vals);
			$retval=llform::redirectString($this->state,$transaction);
		break;

		case self::STATE_FINISHUPDATE:
			// user specified information to update the file.  update the entry
			if ($transaction == 0)
				throw new exception("transaction must be initialized to finish update");
			$form=$this->makeForm(self::STATE_GETDATA);
			$newvals=$form->getValueArray();
			if ($form->wasPressed('cancel')) {
				return $qqs->transactionOriginUri($transaction);
			}
			$oldvals=$qqs->getTransactionData($transaction,'fileinfo');
			
			$mod=new mod_audiolib($this->libname);
			$fileobj=new file($oldvals['fileid']);
			$date=date_create($newvals['recorddate']);
			$mod->updateEntry($oldvals['identry'],$fileobj,$newvals['info1'],$newvals['info2'],$newvals['info3'],$date);
			$qqs->finishTransaction($transaction);
			$retval=$qqs->transactionOriginUri($transaction);
		break;

		default:
			throw new exception("illegal state $state in {$this->longname}");
		break;
	}
	return $retval;
}

// does output of this plus all children
public function output($pgen,$brotherid) {
	global $qqs,$qqi;
	
	$transaction=$this->transaction;
	echo "<div{$qqi->idcstr($this->longname)}>";	
	
	switch ($this->state) {
		case self::STATE_NONE:
			if ($qqs->checkAuth('addto_'.$this->longname)) {
				// do the upload form
				$form=$this->makeForm(self::STATE_NONE);
				echo $form->getDumpStyleFormOutput(self::STATE_GETUPLOADDATA,$transaction,'&nbsp;&nbsp;&nbsp');
			}
			// now do the main table
			$bDel=$qqs->checkAuth('delete_'.$this->longname);	
			$bUpd=$qqs->checkAuth('edit_'.$this->longname);
			
			echo '<table><tr>';
			if ($bUpd || $bDel)
				echo '<th>&nbsp;</th>';
			echo "<th>Date Recorded</th><th>Duration</th><th>{$this->info1title}</th><th>{$this->info2title}</th><th>{$this->info3title}</th><th>Link</th></tr>";
					
			$mod=new mod_audiolib($this->libname);
			$ids=$mod->getAllEntries();
			if ($ids !== False) {
				foreach ($ids as $id) {
					echo '<tr>';
					if ($bUpd || $bDel) {
						echo '<td>';
						$sep='';
						if ($bUpd) {
							echo llform::getLinkText($this,self::STATE_GETDATA,$transaction,array('identry'=>$id),'[Update]',$qqi->cstr('audiolibcmdlink'));
							$sep='&nbsp;&nbsp;';
						}	
						if ($bDel) {
							$linktext=llform::getLinkText($this,self::STATE_DELETE,$transaction,array('identry'=>$id),'[Delete]',$qqi->cstr('audiolibcmdlink'));
							$linktext=preg_replace('/href="([^"]*)"/',"href=\"javascript:if (true === confirm('Sure you want to delete?  Action cannot be undone'))window.location='\\1';\"",$linktext);
							echo $sep.$linktext;
						}	
						echo '</td>';	
					}	
					$info=$mod->getFileInfo($id);	
					$recorddate=$info['recorddate']->format("l F j, Y");	
					$duration=sprintf("%02d:%02d",(int)($info['duration'] / 60),$info['duration'] % 60);
					echo "<td>$recorddate</td><td>$duration</td><td>{$info['info1']}</td><td>{$info['info2']}</td><td>{$info['info3']}</td>";
					$file=new file($info['fileid']);
					echo "<td>{$file->getOutput(False,Null,'Download')}</td></tr>";	
				} // loop for all rows
			} // if there were rows	
			echo '</table>';
		break;
		
		case self::STATE_GETUPLOADDATA:
			// user specified an uploaded file.  create a file object, get the length/check the file, and
			// set up to get the rest of the information
			if ($transaction == 0)
				throw new exception("transaction needed not defined");
			$data=$qqs->getTransactionData($transaction,'filedata');
			$duration=sprintf("%02d:%02d",(int)($data['duration'] / 60),$data['duration'] % 60);
			echo "<p>You've uploaded an audio file with a length of $duration.  Please provide the following information:</p>";
							
			$form=$this->makeForm(self::STATE_GETUPLOADDATA);
			echo $form->getDumpStyleFormOutput(self::STATE_FINISHUPLOAD,$transaction);
		break;

		case self::STATE_GETDATA:
			// user set up to edit an entry's information
			if ($transaction == 0)
				throw new exception("transaction needed not defined");
			$vals=$qqs->getTransactionData($transaction,"fileinfo");
							
			$form=$this->makeForm(self::STATE_GETDATA);
			$form->setFieldsFromRequests($vals);
			echo $form->getDumpStyleFormOutput(self::STATE_FINISHUPDATE,$transaction);
		break;

		default:
			throw new exception("illegal state $state in {$this->longname}");
		break;
	}
	echo '</div>';
}

/////// end of AU definition ///////////////////////////////////////////////////
}?>
