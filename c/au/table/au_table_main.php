<?php if (FILEGEN != 1) die;
///// Access Unit definition file for form & state testing
class au_table_main extends au_base {
////////////////////////////////////////////////////////////////////////////////
private $table;		// the table object
private $cols;		// names of columns to display, in order, in an array of tag names

// initdata:  tablefile.txt|col1,col2,...
// a forward slash before a col means that it starts a new row.  `=multiple row column ~=multiple column row
function __construct($tag,$parent,$initdata) {
	parent::__construct($tag,$parent,$initdata);
	list($filename,$cols)=explode("|",$initdata);
	$id=mod_table::createTableFromFile($filename);
	$this->table=new mod_table($id);
	$this->cols=explode(",",$cols);
}

public function declareids($idstore,$state) {
	$idstore->declareHTMLid($this,$this->bAutoLock);
}

public function declarestyles($stylegen,$state) {
	$stylegen->registerStyledId($this->longname,"table:th,tr,td","table",$this->getParent()->htmlContainerId());
	$stylegen->registerClass("tablerow","td",2);
}

// does output of this plus all children
public function output($pgen,$brotherid) {
	global $qq,$qqi;

	$coldata=$this->table->enumCols();
	$colids=array();
	$rowspans=array();
	$colspans=array();
	$splits=array();
	$icons=array();
	$bASplitHasOccurred=False;
	echo "<table{$qqi->idcstr($this->longname)}>";
	echo "<tr>";
	foreach($this->cols as $coltag) {
		$rowspan=1;
		$split=False;
		if (substr($coltag,0,1) == "/") {
			$split=True;
			$bASplitHasOccurred=True;
			$coltag=substr($coltag,1);
		}
		$rowspan=1;
		while (substr($coltag,0,1) == "`") {
			$rowspan++;
			$coltag=substr($coltag,1);
		}
		$colspan=1;
		while (substr($coltag,0,1) == "~") {
			$colspan++;
			$coltag=substr($coltag,1);
		}
		$icon=0;
		if (False != ($i=strpos($coltag,"[+"))) {
			if (False != ($j=strpos($coltag,"]",$i+2))) {
				// there is an icon file specified
				$iconfile=substr($coltag,$i+2,$j-$i-2);
				$coltag=substr($coltag,0,$i);
				$icon=image::createFromFile("p/{$qq['project']}/src/".$iconfile,"");
			}
		}
		$nextcolid=0;
		foreach ($coldata as $cd) {
			list($tag,$type,$heading,$desc,$default,$colid)=$cd;
			if ($tag == $coltag) {
				$nextcolid=$colid;
				if (!$bASplitHasOccurred)
					echo "<th>$heading</th>";
				break;
			}
		} // loop to search for next col
		if ($nextcolid == 0)
			throw new exception("column tag $coltag was specified by au but not found in the table it referenced");
		$colids[]=$nextcolid;
		$rowspans[]=$rowspan;
		$colspans[]=$colspan;
		$splits[]=$split;
		$icons[]=$icon;	
	}
	echo "</tr>";
	
	$rows=$this->table->getTableRows($colids);
	foreach ($rows as $row) {
		echo "<tr>";
		$i=0;
		foreach ($row as $col=>$data) {
			$startcode=$splits[$i] ? "</tr><tr><td{$qqi->cstr("tablerow")}" : "<td{$qqi->cstr("tablerow")}";
			if ($colspans[$i] > 1)
				$startcode .= " colspan=\"{$colspans[$i]}\"";
			if ($rowspans[$i] > 1)
				$startcode .= " rowspan=\"{$rowspans[$i]}\"";
			$startcode .= ">";	
			if (is_object($data) && "image" == get_class($data)) {
				list($width,$height)=$data->getNaturalSize();
				$desiredheight=80;
				$desiredwidth=(int)($desiredheight*$width/$height+0.5);
				$data->requestSize($desiredwidth,$desiredheight);
				echo "$startcode{$data->getOutput()}</td>";
			} else {
				if (is_string($data))
					$data=str_replace("\\\\","<br />",$data);
				if (is_object($icons[$i])) {
					$n=$data;
					$data=0;
					for ($j=0; $j<$n; ++$j)
						$data .= $icons[$i]->getOutput();
					if ($n == 0)
						$data="&nbsp;";	
				}
				echo "$startcode$data</td>";
			}
			++$i;
		} // loop for all fields in row
		echo "</tr>";
		$qqi->bumpClassCount("tablerow");
	} // loop for all rows
	echo "</table>";
}

/////// end of AU definition ///////////////////////////////////////////////////
}?>
