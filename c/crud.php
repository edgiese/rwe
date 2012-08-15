<?php if (FILEGEN != 1) die;
/* class file definition */

/* the main class for CRUD operations */
class crud {
	private $db;				// database access object
	private $defaultModule;		// default module name to use if missing in query id
	private $c_queries;			// cached create queries
	private $r_queries;			// cached read queries
	private $d_queries;			// cached delete/non-data driven queries
	private $objtypes;			// array of object types
	private $files;				// array of arrays of file lines, indexed by module name
	
	// atomic PHP types stored in database
	const TYPE_UNDEFINED = 0;
	const TYPE_BOOL = 1;
	const TYPE_INT = 2;
	const TYPE_FLOAT = 3;
	const TYPE_STRING = 4;
	const TYPE_DATE = 5;
	
	function __construct($db) {
		$this->db=$db;
		$this->defaultModule="global";
		$this->c_queries=array(); 
		$this->r_queries=array(); 
		$this->u_queries=array(); 
		$this->d_queries=array();
		$this->files=array();
		
		// php object types:
		// the "right" way to do this would be to read these in from
		// the data objects somethow.  for now, let's just hardcode the info
		$this->objtypes=array(
			"name"=>array("title"=>crud::TYPE_STRING,"first"=>crud::TYPE_STRING,"middle"=>crud::TYPE_STRING,"last"=>crud::TYPE_STRING,"generation"=>crud::TYPE_STRING),
			"email"=>array("1"=>crud::TYPE_STRING),

			// last line (no comma)
			"tester"=>array("pointer"=>crud::TYPE_INT,"message"=>crud::TYPE_STRING)
		); // objtypes array
	}

	public function getObjectTypes() {return $this->objtypes;}
		
	public function beginTransaction($bEssential=False) {}
	public function commit() {}
		
	public function setDefaultModule($name) {$this->defaultModule=$name;}
		
	// adds module name if necessary to fill out a query ID
	private function processID($QID,$type,$cache) {
		if (False === ($i=strpos($QID,"::"))) {
			$module=$this->defaultModule;
			$query=$QID;
			$QID=$this->defaultModule."::".$QID;
		} else {
			$module=substr($QID,0,$i);
			$query=substr($QID,$i+2);
		}	
		
		if (isset($cache[$QID]))
			return $cache[$QID];
		
		if (!isset($this->files[$module])) {
			$filename="c/$module.sql";
			if (False === ($this->files[$module]=file($filename,FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES)))
				throw new exception("Could not read query module $filename");
			// mark end of file so last query will read correctly	
			$this->files[$module][] = "--: End of File";
		}
		$query=new crudquery($this->db,$module,$query,$type,$this->files[$module],$this->objtypes);
		$cache[$QID]=$query;
				
		return $query;	
	}
	
	// does an insertion query and returns the id of the newly inserted item
	public function insert($QID) {
		$query=$this->processID($QID,"C",&$this->c_queries);
		$query->execute(func_get_args());
		return $this->db->lastInsertId();
	}
	
	// does a query that doesn't return any values (general type--"D")
	public function act($QID) {
		$query=$this->processID($QID,"D",&$this->d_queries);
		$query->execute(func_get_args());
	}
	
	// does a query and returns a single value or throws an exception
	public function getValue($QID) {
		$query=$this->processID($QID,"R",&$this->r_queries);
		$retval=$query->execute(func_get_args(),1,True,1);
		if ($retval === False)
			throw new exception("getvalue erroneously returned zero rows:  $QID");
		return $retval;
	}
	
	// multiple columns returned in individual array elements.  maxrows=0 means use default
	public function getRows($QID,$maxrows) {
		$query=$this->processID($QID,"R",&$this->r_queries);
		return $query->execute(func_get_args(),2,False,$maxrows);
	}
	// multiple columns returned in individual array elements.  maxrows=0 means use default
	public function getCols($QID,$maxrows) {
		$query=$this->processID($QID,"R",&$this->r_queries);
		return $query->execute(func_get_args(),2,True,$maxrows);
	}
	
	// update queries are not cached but custom made per request
	// update arguments are passed in an array of field names
	public function updateRow($QID,$updatedfields) {
		$query=$this->processID($QID,"U",&$this->u_queries);
		$query->prepareUpdate($this->db,$updatedfields);
		$retval=$query->execute(func_get_args(),2,False,0);
		$query->cleanupUpdate();
		return $retval;
	}
	
	

} /* end of CRUD class definition */ ?>
