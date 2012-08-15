<?php if (FILEGEN != 1) die;
// interface definition for name
class data_name implements data_customtype {
	private $title='';
	private $first='';
	private $middle='';
	private $last='';
	private $generation='';

	public function __construct($initvalue='',$bNoError=True) {
		if ($initvalue == '')
			$this->title=$this->first=$this->middle=$this->last=$this->generation='';
		else {
			try {
				$this->setFromSingle($initvalue);
			} catch (Exception $e) {
				if (!$bNoError)
					throw $e;	
			}	
		}		
	}
	/////////////////// customtype implementation:
	
	// returns an argument, ostensibly to be sent to a query for storage.
	// number of indices must be determined form getFieldNames
	public function getArgParam($index) {
		if ($index > 5)
			throw new data_exception("data_name stores only 5 parameters");
		$data=array($this->title,$this->first,$this->middle,$this->last,$this->generation);	
		return $data[$index];
	}
	
	// sets data for the custom type.  throws an exception if error occurred.
	// all data is set in vals indexed by number according to field names and types
	// returned below
	public function setDataArray($vals) {
		if (!is_array($vals) || sizeof($vals) != 5)
			throw new data_exception("Illegal array set into data_name");
		list($this->title,$this->first,$this->middle,$this->last,$this->generation)=$vals;	
	}
	
	public function getAllParams() {
		return array($this->title,$this->first,$this->middle,$this->last,$this->generation);
	}
	
	public function getParamCount() {return 5;}
		
	// returns an array of strings indexed sequentially of required parameters for this data type
	public function getFieldNames() {
		return array('title','first','middle','last','generation');
	}
	
	// returns an array of type strings (see predefined types in crud) corresponding to FieldNames
	public function getFieldTypes() {
		return array(crud::TYPE_STRING,crud::TYPE_STRING,crud::TYPE_STRING,crud::TYPE_STRING,crud::TYPE_STRING);
	}

	// returns a string
	public function output($separator=' ') {
		$o=$this->title;
		if ($o != '')
			$o .= $separator;
		$o .= $this->first;
		if ($this->first != '')
			$o .= $separator;
		$o .= $this->middle;
		if ($this->middle != '')
			$o .= $separator;
		$o .= $this->last;
		if ($this->generation != '')
			$o .= $separator;
		$o .= $this->generation;
		
		return $o;
	}

	////////////////////////////////// api interface functions:
	
	// returns True or with an array of field=>message
	public function setFromSingle($name,$bForce=False) {
		$this->title=$this->first=$this->middle=$this->last=$this->generation='';
		$name=trim($name);
		do {
			$name=str_replace('  ',' ',$name,&$count);
		} while ($count > 0);
		$pieces=explode(' ',$name);
		$ixfirst=0;
		$ixlast=sizeof($pieces)-1;
		
		// look for a title 
		$titletest=strtolower($pieces[0]);
		// strip off period if any
		if (substr($titletest,-1) == '.')
			$titletest=substr($titletest,0,-1);
		if (False !== array_search($titletest,array('mr','mrs','dr','rev','hon'))) {
			// found a title
			$this->title=$pieces[0];
			++$ixfirst;
		}
		
		// look for a generation
		$gentest=strtolower($pieces[$ixlast]);
		// strip off period if any
		if (substr($gentest,-1) == '.')
			$gentest=substr($gentest,0,-1);
		if (False !== array_search($gentest,array('ii','iii','iv','jr','sr'))) {
			// found a title
			$this->generation=$pieces[$ixlast];
			--$ixlast;
			// strip off comma if it exists
			if (substr($pieces[$ixlast],-1) == ',')
				$pieces[$ixlast]=substr($pieces[$ixlast],0,-1);
		}
		
		// make certain there is a first and last name at least
		if ($ixfirst >= $ixlast) {
			// keep whole name in last--that adds no spaces on output 
			$this->last=$name;
			// caller can ignore this
			if (!$bForce)
				throw new data_exception("need at least a first and last name");
		}	
		
		if ($ixfirst-$ixlast > 1) {
			// combine anything left over into middle name
			for ($ix=$ixfirst+1; $ix<$ixlast; ++$ix)
				$this->middle .= $pieces[$ix];
		}
		$this->first=$pieces[$ixfirst];
		$this->last=$pieces[$ixlast];	
	}
	
	public function verifyAndSet($name) {
		try {
			$this->setFromSingle($name);
			$retval=True;
		} catch (data_exception $e) {
			$retval=array('*name'=>$e->getMessage());
		}
		return $retval;	
	}
	
	const ABBREV_FIRSTINITIALLAST = 1;
	
	public function getAbbreviated($type) {
		if ($type == self::ABBREV_FIRSTINITIALLAST) {
			return "{$this->first[0]}. {$this->last}"; 
		} else
			throw new exception("Unknown abbreviation type");
	}
	
	// True if name matches, false if not
	public function equals($cn) {
		if ($cn instanceof data_name) {
			//infolog("dbg","comparing {$this->first}={$cn->first} && {$this->middle}={$cn->middle} && {$this->last}={$cn->last} && {$this->generation}={$cn->generation}");
			return ($this->first == $cn->first && $this->middle == $cn->middle && $this->last == $cn->last && $this->generation == $cn->generation); 
		} else
			throw new exception("argument for equals function not a data_name");
	}
	public function isComplete() {
		return ($this->first != '' && $this->last != ''); 
	}

// end of class
}
?>
