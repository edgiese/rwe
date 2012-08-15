<?php if (FILEGEN != 1) die;
///// Access Unit definition file to put out an icon & title for podcasting.  also contains the code to generate the podcasting feed
class au_audiolibpodcast extends au_base {
////////////////////////////////////////////////////////////////////////////////
private $libname;								// name of this audiolibrary
private $text;			// text on css line
private $symbolfile;	// file containing rss symbol
private $template;
private $maxids;


function __construct($tag,$parent,$initdata,$state="",$data=0) {
	parent::__construct($tag,$parent,$initdata);
	$initdata=parent::getInit($initdata,'name|text|symbolfile|xmltemplate|*maxids=12');
	
	$this->libname=$initdata['name'];
	$this->symbolfile=$initdata['symbolfile'];
	$this->text=$initdata['text'];
	$this->template=$initdata['xmltemplate'];
	$this->maxIds=(int)$initdata['maxids'];
}


public function declareids($idstore,$state) {
	$idstore->declareHTMLid($this);
}

public function declarestyles($stylegen,$state) {
	$stylegen->registerStyledId($this->longname,"a:img","podcastlink",$this->getParent()->htmlContainerId());
}

// called when a feed is requested from feed.php
public function processFeed($subtype) {
	global $qqi;
	
	if ($subtype != 'podcast')
		throw new exception("unsupported feed type for this au:  $subtype");
	$globalsub=array("current_gmt_timestamp"=>gmdate("D, j M Y H:i:s"));
	$mod=new mod_audiolib($this->libname);
	$ids=$mod->getAllEntries();
	$nIds=min(sizeof($ids),$this->maxIds);
	if ($nIds > 0) {
		$repeatsub=array();
		for ($i=0; $i<$nIds; ++$i){
			$info=$mod->getFileInfo($ids[$i]);
			$recorddate=$info['recorddate']->format("l F j, Y");	
			$duration=sprintf("%02d:%02d",(int)($info['duration'] / 60),$info['duration'] % 60);
			$file=new file($info['fileid']);
			$url=$file->getURL();
			$length=$file->getLength();

			$repeatsub[]=array(
				'info1'=>$info['info1'],
				'info2'=>$info['info2'],
				'info3'=>$info['info3'],
				'duration'=>$duration,
				'fileurl'=>$url,
				'length'=>$length,
				'recorddate'=>$recorddate
			);
		}
	} else {
		$repeatsub=Null;		// no entries to output
	}	
	$util1=new utilrare1();
	$template=$this->template;
	$template=ltrim($qq['srcbase'].$template,'/');
	return $util1->fileStringSubstitution($template,$globalsub,$repeatsub);
}

// does output of this plus all children
public function output($pgen,$brotherid) {
	global $qqs,$qqi,$qq;
	
	$project=$qq['production'] ? '' : "&u={$qq['project']}";
	$href=$qqi->hrefPrep("feed.php?r={$this->longname}&s=podcast$project",False);
	$src=$qqi->hrefPrep($this->symbolfile,False);
	$text=$this->text;
	if ($text != '')
		$text=' '.$text;
	echo "<a{$qqi->idcstr($this->longname)} href=\"$href\"><img src=\"{$src}\">$text</a>";
}

/////// end of AU definition ///////////////////////////////////////////////////
}?>
