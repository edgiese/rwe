<?php if (FILEGEN != 1) die;
// interface definition for all custom data types
class data_address implements data_customtype {
	private $line1='';
	private $line2='';
	private $city='';
	private $state='';
	private $zip='';
	private $country='';
	private $bUSAonly='';
	
	function __construct($bUSAonly) {
		if (!$bUSAonly)
			throw new exception('international addresses not implemented yet');
		else
			$this->country='USA';	
		$this->bUSAonly=$bUSAonly;
	}

	/////////////////// customtype implementation:
	
	// returns an argument, ostensibly to be sent to a query for storage.
	// number of indices must be determined form getFieldNames
	public function getArgParam($index) {
		if ($index != 0)
			throw new exception("data_address stores only one parameter");
		return $this->args[$index];
	}
	
	// sets data for the custom type.  throws an exception if error occurred.
	// all data is set in vals indexed by number according to field names and types
	// returned below
	public function setDataArray($vals) {
		if (!is_array($vals) || sizeof($vals) != 1)
			throw new exception("Illegal array set into data_address");
		$as=explode('|',$vals[0]);
		$this->country=$as[0];
		if ($this->country == 'USA') {
			if (sizeof($as) != 6)
				throw new exception("badly formatted us address:  {$vals[0]}");
			list($this->country,$this->line1,$this->line2,$this->city,$this->state,$this->zip)=$as;	
		} else {
			throw new exception('international addresses not implemented yet');
		}	
	}
	
	public function getAllParams() {
		return array("{$this->country}|{$this->line1}|{$this->line2}|{$this->city}|{$this->state}|{$this->zip}");
	}
	
	public function getParamCount() {return 1;}
		
	// returns an array of strings indexed sequentially of required parameters for this data type
	public function getFieldNames() {
		return array ("codedaddr");
	}
	
	// returns an array of type strings (see predefined types in crud) corresponding to FieldNames
	public function getFieldTypes() {
		return array(crud::TYPE_STRING);
	}
	
	public function output($delimeter='<br />') {
		$o='';
		if ($this->country == 'USA') {
			if ($this->line1 != '')
				$o .= $this->line1.$delimeter;
			if ($this->line2 != '')
				$o .= $this->line2.$delimeter;
			$o .= $this->city.', '.$this->state.'  '.$this->zip;	
		} else {
			throw new exception('international addresses not implemented yet');
		}
		return $o;	
	}

	////////////////////////////////// api interface functions:
	
	static function getStates($bLong=False) {
		if ($bLong)
		return array('AL'=>'Alabama (AL)','AK'=>'Alaska (AK)','AZ'=>'Arizona (AZ)','AR'=>'Arkansas (AR)','CA'=>'California (CA)','CO'=>'Colorado (CO)',
			'CT'=>'Connecticut (CT)','DE'=>'Delaware (DE)','FL'=>'Florida (FL)','GA'=>'Georgia (GA)','HI'=>'Hawaii (HI)','ID'=>'Idaho (ID)',
			'IL'=>'Illinois (IL)','IN'=>'Indiana (IN)','IA'=>'Iowa (IA)','KS'=>'Kansas (KS)','KY'=>'Kentucky (KY)','LA'=>'Louisiana (LA)',
			'ME'=>'Maine (ME)','MD'=>'Maryland (MD)','MA'=>'Massachusetts (MA)','MI'=>'Michigan (MI)','MN'=>'Minnesota (MN)','MS'=>'Mississippi (MS)',
			'MO'=>'Missouri (MO)','MT'=>'Montana (MT)','NE'=>'Nebraska (NE)','NV'=>'Nevada (NV)','NH'=>'New Hampshire (NH)','NJ'=>'New Jersey (NJ)',
			'NM'=>'New Mexico (NM)','NY'=>'New York (NY)','NC'=>'North Carolina (NC)','ND'=>'North Dakota (ND)','OH'=>'Ohio (OH)','OK'=>'Oklahoma (OK)',
			'OR'=>'Oregon (OR)','PA'=>'Pennsylvania (PA)','RI'=>'Rhode Island (RI)','SC'=>'South Carolina (SC)','SD'=>'South Dakota (SD)','TN'=>'Tennessee (TN)',
			'TX'=>'Texas (TX)','UT'=>'Utah (UT)','VT'=>'Vermont (VT)','VA'=>'Virginia (VA)','WA'=>'Washington (WA)','WV'=>'West Virginia (WV)',
			'WI'=>'Wisconsin (WI)','WY'=>'Wyoming (WY)');
		else
			return array('AL','AK','AZ','AR','CA','CO','CT','DE','FL','GA','HI','ID','IL','IN','IA','KS','KY','LA','ME','MD','MA','MI','MN','MS',
			'MO','MT','NE','NV','NH','NJ','NM','NY','NC','ND','OH','OK','OR','PA','RI','SC','SD','TN','TX','UT','VT','VA','WA','WV','WI','WY');
	}
	
	// returns True or with an array of field=>message
	public function verifyAndSetAddress($a,$bRequired) {
		if ($a['country'] != 'USA')
			throw new exception("internation addresses not supported yet");
		$this->country='USA';		
		$this->line1 = isset($a['line1']) ? $a['line1'] : ''; 	
		$this->line2 = isset($a['line2']) ? $a['line2'] : ''; 	
		$this->city = isset($a['city']) ? $a['city'] : ''; 	
		$this->state = isset($a['state2']) ? $a['state2'] : ''; 	
		$this->zip = isset($a['zip']) ? $a['zip'] : ''; 	
			
		if ($this->line1 == '' && $this->line2 == '') {
			if ($bRequired || $this->city != '' || $this->zip != '')
				return (array('*line1'=>'You must provide an address'));
			else {
				$this->city=$this->state=$this->zip='';
				return True;		// OK -- just no address
			}		
		}
		
		$retval=array();
		if ($this->city == '')
			$retval['*city']='You must provide a city';
		if ($this->zip == '')
			$retval['*zip']='You must provide a ZIP code';
		else {
			$nmatches=preg_match('/^[\\d]{5}(-[\\d]{4})??$/',$this->zip);
			if (0 == $nmatches)
				$retval['*zip']="Invalid ZIP code";
		}	
		return (sizeof($retval) != 0) ? $retval : True;
	}

	
}
?>
