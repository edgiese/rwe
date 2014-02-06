<?php if (FILEGEN != 1) die;
class mod_table {
///////////////////////////////// table module /////////////////////////////////
private $cols;
private $rows;
private $title;
private $description;

function __construct($id) {
	global $qqc;
	
	// read the entire table into memory
	$tableinfo=$qqc->getRows("mod/table::tableinfo",1,$id);
	if ($tableinfo == False)
		throw new exception("undefined table id $id");
	$this->title=$tableinfo["title"];	
	$this->description=$tableinfo["description"];
	
	// column info
	$colinfo=$qqc->getRows("mod/table::colinfo",-1,$id);
	if ($colinfo == False)
		throw new exception("no column definitions for table id $id");
	$cols=array();	
	foreach ($colinfo as $ci) {
		$cols[]=array($ci["name"],$ci["coltype"],$ci["heading"],$ci["description"],$ci["defaultval"],$ci["colid"]);
	}
	$this->cols=$cols;
	unset($cols);
			
	// row info--data of the table
	$rows=array();
	$rowinfo=$qqc->getRows("mod/table::strings",-1,$id);
	if ($rowinfo != False) {
		foreach($rowinfo as $ri) {
			extract($ri); // sets: $rownum,$col,$celldata
			if (!isset($rows[$rownum]))
				$rows[$rownum]=array();
			$rows[$rownum][$col]=$celldata;	
		}
		unset($rowinfo);
	}		
	$rowinfo=$qqc->getRows("mod/table::ints",-1,$id);
	if ($rowinfo != False) {
		foreach($rowinfo as $ri) {
			extract($ri); // sets: $rownum,$col,$celldata
			if (!isset($rows[$rownum]))
				$rows[$rownum]=array();
			$rows[$rownum][$col]=$celldata;	
		}
		unset($rowinfo);
	}
	$this->rows=$rows;
	unset($rows);		
	
	// go through and convert any integers loaded for images to images
	foreach ($this->cols as $col) {
		list($tag,$type,$heading,$desc,$default,$colid)=$col;
		if ($type == "image") {			
			foreach ($this->rows as &$row) {
				if ($row[$colid] != 0) {
					$row[$colid]=new image($row[$colid]);
				}
			}
		}
	}
}

// helper function for createTableFromFile
private static function insertFileRow($keycol,$cols,$values) {
	global $qq,$qqc;
	
	$row=(int)$qqc->getValue("mod/table::nextInsertRowId",$keycol);
	foreach ($cols as $col) {
		list($tag,$type,$heading,$desc,$default,$colid)=$col;
		$value=isset($values[$tag]) ? $values[$tag] : $default;
		if ($type == "int")
			$value=(int)$value;
		else if ($type == "image" && $value != $default) {
			list($filename,$alt)=explode("|",$value);
			if (!isset($alt))
				$alt="";
			$image=image::createFromFile("p/{$qq['project']}/src/".$filename,$alt);
			$value=(int)$image->getId();
			$type="int";
		}
		$qqc->insert($type == "int" ? "mod/table::insertint" : "mod/table::insertstring",$row,(int)$colid,$value);				
	}
}

// returns id of created table
static function createTableFromFile($filename) {
	global $qq,$qqc;
	
	$fullfile="p/{$qq['project']}/{$filename}";
	if (!is_file($fullfile))
		throw new exception("$fullfile was not found; could not make a table from it");
	$lastedit=filemtime($fullfile);
	
	// check to see if file is already in table and doesn't need updating
	$tableinfo=$qqc->getRows("mod/table::infofromfilename",1,$filename);
	if ($tableinfo != False) {
		$id=$tableinfo["id"];
		if ($lastedit <= $tableinfo["lastedit"])
			return $id;
		// database entries exist for this file, but they are out of date.  delete them all
		$qqc->act("mod/table::deletetabledata",$id);								
	} else {
		$id=(int)$qqc->insert("mod/table::insertstub",$filename);
	}
	// open and process the file 	
	$lines=file($fullfile);
	$values=array();
	$cols=array();
	$keycol=0;
	foreach ($lines as $ln) {
		$ln=trim($ln);
		if ($ln == "" || substr($ln,0,1) == ";")
			continue;
		if (substr($ln,0,5) == ">col=") {
			// column definition
			$colinfo=explode("|",substr($ln,5));
			if (sizeof($colinfo) != 5)
				throw new exception("syntax error in table column definition:  5 parts exactly are needed");
			list($tag,$type,$heading,$desc,$default)=$colinfo;
			$colinfo[5]=$qqc->insert("mod/table::coldef",$id,$tag,$type,$heading,$desc,$default);
			if ($keycol == 0) {
				$keycol=(int)$colinfo[5];
				$rowkey=$tag;
			}	
			$cols[$tag]=$colinfo;	
		} else if (substr($ln,0,6) == ">name=") {
			$nameinfo=explode("|",substr($ln,6));
			if (sizeof($nameinfo) != 2)
				throw new exception("syntax error in table name definition:  2 parts exactly are needed");
			list($title,$description)=$nameinfo;
		} else {
			if (substr($ln,0,1 == ">"))
				throw new exception("illegal keyword in table definition");
			list($col,$value)=explode("=",$ln);
			if (!isset($value))
				throw new exception("illegal syntax in table definition line.  expecting '='");
			if (sizeof($cols) == 0)
				throw new exception("no table columns specified before data");
			if ($col == $rowkey) {
				// starting a new row.  if there was an old row under construction, finish it
				if (sizeof($values) > 0) {
					self::insertFileRow($keycol,$cols,$values);
					$values=array();
				}
			}
			$values[$col]=$value;	 
		}
	} // loop for all file lines
	if (sizeof($values) > 0)
		self::insertFileRow($keycol,$cols,$values);
	$qqc->act("mod/table::updatenames",$id,$title,$description,$lastedit);	
	return $id;
}

// returns an array of arrays containing table data
function getTableRows($cols) {
	$retval=array();
	for ($i=0; $i<sizeof($this->rows); ++$i) {
		$row=$this->rows[$i];
		$retval[$i]=array();
		$j=0;
		foreach($cols as $col) {
			$retval[$i][$j]=$row[$col];
			++$j;
		}
	}
	return $retval;
}

// returns an array of (name,coltype,heading,description,defaultval,colid) 
function enumCols() {
	return $this->cols;
}


//////////////////////////////// end of module /////////////////////////////////
} ?>
