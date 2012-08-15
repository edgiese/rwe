<?php if (FILEGEN != 1) die;
 
/* class file definition */
// supporting class for statement processing & storage
class crudquery {
	private $name;		// query name without module attached
	private $module;	// module name
	private $type;		// C, R, U, D
	private $comment;	// comment (used for development only)
	private $query;		// text of query
	private $maxrows;	// "hard" max for rows returned; 0 means nothing allowed, -1 means no check
	private $stmt;
	private $args;		// array of (name,type) for all arguments
	private $vals;		// array of (name,type) for all data

	// reads an array of names and types from an input string
	private function parseArgs($in,$objtypes) {
		$retval=array();
		
		// form is pieces:  name:type,name:type, etc. a type is numeric or obj. name
		if (strlen($in) > 0) {
			$pieces=explode(",",$in);
			foreach ($pieces as $piece) {
				$name=strtok($piece,':');
				$type=strtok(':');
				if (is_numeric($type))
					$type=(int)$type;
				else {
					$type=$this->addObjArgs($type,$objtypes);
				}
				$retval[]=array($name,$type);	
			}
		}
		return $retval;
	}
	
	// recursive function to build object type list
	private function addObjArgs($type,$objtypes) {
		// first element in the array is the object name.
		$retval=array($type);
		$bFound=False;
		foreach ($objtypes as $objname=>$objtypearray) {
			if ($objname == $type) {
				$bFound=True;
				foreach ($objtypearray as $type) {
					if (is_int($type))
						$retval[]=$type;
					else {
						$retval[]=$this->addObjArgs($type,$objtypes);
					}	
				}
				break;
			} // found name 
		} // looking for name
		if (!$bFound)
			throw new exception ("unknown object name $type (query: {$this->module}::{$this->name})");
		return $retval;
	}
	
	public function __construct($pdo,$module,$name,$type,$lines,$objtypes) {
		$this->stmt=Null;
		$this->args=array();
		$this->data=array();

		$this->module=$module;
		$this->name=$name;
		$this->type=$type;

		$query="";
		$args="";
		$vals="";
		$maxrows=-1;
		$bCollecting=False;
		$nametest="--:$name:";
		foreach ($lines as $line) {
			$ln=trim($line);
			$first2=substr($ln,0,2);
			$first3=substr($ln,0,3);
			
			if (!$bCollecting) {
				// check for the query we're searching for
				if (0 === strpos($ln,$nametest)) {
					// check for type
					if ($type == substr($ln,strlen($nametest),1)) {
						// found a match!
						$bCollecting=True;
						continue;
					} 
				}
				// keep looking until name and type match
				continue;
			}
			
			// end of query is at end of file or another section, which shouldn't appear here
			if ($first3 == "--:" || $first3 == "--!")
				break;
			
			// input arguments
			if ($first3 == "-->") {
				$args=substr($ln,3);
				continue;	
			}
			
			// outputs 	
			if ($first3 == "--<") {
				if ($type != "R")
					throw new exception("outputs only allowed for R type queries");	
				$vals=substr($ln,3);
				if (($bpos=strpos($vals,"|")) !== False) {
					$maxrows=(int)substr($vals,$bpos+1);
					$vals=substr($vals,0,$bpos);
				}
				continue;	
			}

			// comments
			if ($first2 == "--")
				continue;
			if ($query != "")
				$query .= " ";	// newlines are space
			$query .= $ln;			
		}
		if (!$bCollecting)
			throw new exception("Query name $name of type $type not found in module $module");
		
		$this->maxrows= ($type == "R") ? $maxrows : 0;
		$this->query=$query;		
		// parse the query string for comment, value, and query data
		$this->args=$this->parseArgs($args,$objtypes);		
		$this->vals=$this->parseArgs($vals,$objtypes);		
		
		// now parse the statement
		if  ($type != "U")
			$this->stmt=$pdo->prepare($this->query);
		else
			$this->stmt=Null;		
	}
	
	private function addSimpleArg($args,$argoffset,$exeArgs,$name,$type) {
		if (isset($exeArgs[$name]))
			throw new exception("Duplicated argument ($name) passed to query {$this->module}::{$this->name}");
		if ($argoffset < 0) {
			if (!isset($args[$name]))
				throw new exception("Missing argument $name in query {$this->module}::{$this->name}");
			$newarg=$args[$name];	
		} else {
			if ($argoffset > sizeof($args)-1)
				throw new exception("Too few arguments passed to query {$this->module}::{$this->name}");
			$newarg=$args[$argoffset++];
		}	
			
		switch ($type) {
			case crud::TYPE_BOOL:
				if (!is_bool($newarg)) {
					$type=gettype($newarg);
					throw new exception("Type mismatch $name; expected bool, got $type.  Query {$this->module}::{$this->name}");
				}
			break;
			
			case crud::TYPE_INT:
				if (!is_int($newarg)) {
					$type=gettype($newarg);
					throw new exception("Type mismatch $name; expected int, got $type.  Query {$this->module}::{$this->name}");
				}
			break;
			
			case crud::TYPE_FLOAT:
				if (!is_float($newarg)) {
					$type=gettype($newarg);
					throw new exception("Type mismatch $name; expected float, got $type.  Query {$this->module}::{$this->name}");
				}
			break;
			
			case crud::TYPE_STRING:
				if (!is_string($newarg)) {
					$type=gettype($newarg);
					throw new exception("Type mismatch $name; expected string, got $type.  Query {$this->module}::{$this->name}");
				}
			break;
			
			case crud::TYPE_DATE:
				if (!($newarg instanceof DateTime)) {
					$type=gettype($newarg);
					throw new exception("Type mismatch $name; expected date, got $type.  Query {$this->module}::{$this->name}");
				}
				// date actually stored as a string:
				$newarg=$newarg->format("Y-m-d");
			break;
			
			default:
				throw new exception("Illegal argument type $type specified for $name.  Query {$this->module}::{$this->name}");
			break;
		}
		$exeArgs[$name]=$newarg;
	}
	
	private function addObjArg($args,$argoffset,$exeArgs,$name,$list) {
		$prefix=$name."_";
		$ix=0;
		foreach ($list as $arg) {
			if (is_string($arg)) {
				// first element of array is object name.  set up for data transfer
				$objtype="data_".$list[0];
				if ($argoffset < 0) {
					if (!isset($args[$name]))
						throw new exception("Missing object argument $name in query {$this->module}::{$this->name}");
					$newarg=$args[$name];	
				} else {
					if ($argoffset > sizeof($args)-1)
						throw new exception("Too few arguments passed to query {$this->module}::{$this->name}");
					$newarg=$args[$argoffset++];
				}
				if (!($newarg instanceof $objtype)) {
					$type=gettype($newarg);
					throw new exception("Type mismatch $name; expected $objtype, got $type.  Query {$this->module}::{$this->name}");
				}
				// build an argument array for this object
				$myargs=$newarg->getAllParams();
				$myargoffset=0;	
			} else {
				++$ix;	
				$name=$prefix.$ix;
				if (is_int($arg)) {
					$this->addSimpleArg($myargs,&$myargoffset,&$exeArgs,$name,$arg);
				} else {
					$this->addObjArg($myargs,&$myargoffset,&$exeArgs,$name,$arg);				
				}
			} // if ordinary type list entry	
		}
	}
	
	private function getData($qdata,$qdix,$name,$type) {
		if (is_int($type)) {
			// simple value.  return it.
			if ($qdix > sizeof($qdata)-1) {
				throw new exception("not enough returned data columns for specified data returns in query {$this->module}::{$this->name}");
			}
			$newval=$qdata[$qdix++];
			switch ($type) {
				case crud::TYPE_BOOL:
					$newval=(bool)$newval;
				break;
				
				case crud::TYPE_INT:
					$newval=(int)$newval;
				break;
				
				case crud::TYPE_FLOAT:
					$newval=(float)$newval;
				break;
				
				case crud::TYPE_STRING:
					$newval=(string)$newval;
				break;
				
				case crud::TYPE_DATE:
					$newval=new DateTime($newval);
				break;
				
				default:
					throw new exception("Illegal data type $type specified for $name.  Query {$this->module}::{$this->name}");
				break;
			}
		} else {
			// object type.
			$newval="data_".$type[0];
			$newval=new $newval();
			$nFields=$newval->getParamCount();
			$params=array();
			for ($i=0; $i<$nFields; ++$i)
				$params[$i]=$this->getData($qdata,&$qdix,$name,$type[1+$i]);
			$newval->setDataArray($params);	
		}
		return $newval;
	}
	
	public function execute($args,$argoffset=1,$bOutputCols=True,$maxrows=-1) {
		if ($maxrows < 0 && $this->maxrows >= 0)
			$maxrows=$this->maxrows;
		// in the case of a single row returned, column outputs degrade to row outputs	
		if ($maxrows == 1)
			$bOutputCols=False;	
			
		// process the input arguments
		if (isset($args[$argoffset]) && is_array($args[$argoffset])) {
			// named argument array passed in -- pass in kludge argument to addArg routines
			$args=$args[$argoffset];
			$argoffset=-1;
		}
		$exeArgs=array();	
		foreach ($this->args as $arg) {
			if (is_int($arg[1])) {
				// this is a simple argument
				$this->addSimpleArg($args,&$argoffset,&$exeArgs,$arg[0],$arg[1]);
			} else {
				// this is an object argument
				$this->addObjArg($args,&$argoffset,&$exeArgs,$arg[0],$arg[1]);
			}
		}
		
		// when we get here, we should have exactly exhausted all arguments
		if ($argoffset >= 0 && $argoffset < sizeof($args))
			throw new exception("Too many arguments ($argoffset) passed to query {$this->module}::{$this->name}");

		// now do the query.  there may be data or not.
		$this->stmt->execute($exeArgs);
		unset($exeArgs);
		
		if ($maxrows != 0) {
			// formulate return data.
			$nRows=0;
			// return is always an array.  if row-wise, it's an array of row arrays, done by row.
			// if col-wise, it's an array of arrays one per column, each of which has row elements
			$retval=array();
			$nCols=sizeof($this->vals);
			if ($bOutputCols) {
				// column wise.  set up empty column arrays 
				foreach ($this->vals as $col) {
					$retval[$col[0]]=array();
				}
			}
			// loop through all returned rows	
			while (False !==($qdata=$this->stmt->fetch(PDO::FETCH_NUM))) {
				$qdix=0;	// index into returned data
				if (!$bOutputCols)
					$newrow=array();		
				foreach ($this->vals as $val) {
					$name=$val[0];
					$newval=$this->getData($qdata,&$qdix,$name,$val[1]);
					if ($bOutputCols) {
						$retval[$name][]=$newval;
					} else
						$newrow[$name]=$newval;
				}
				if ($qdix < sizeof($qdata))
					throw new exception("too few data columns used in query query {$this->module}::{$this->name}");
				if (++$nRows > $maxrows && $maxrows > 0)
					throw new exception("too many rows (max: {$maxrows}) returned from query {$this->module}::{$this->name}");
				if (!$bOutputCols)
					$retval[]=$newrow;	
			}	
		} else {
			// there should be no data
			/*
			This seems to throw a general error, I'm not sure why.  The code would be better with this check
			in place, but we'll leave it out.
			
			if (False !== $this->stmt->fetch(PDO::FETCH_NUM))
				throw new exception("illegal data returned from query {$this->module}::{$this->name})");
			*/	
		}		
		$this->stmt->closeCursor();
		
		// return false in the case of no data
		if ($maxrows != 0 && $nRows == 0)
			return False;
		
		if (isset($retval)) {
			// in the case of maxrows == 1, present the single row (or scalar "columns") instead of an array of one
			if ($maxrows == 1)
				$retval=$retval[0];
			// if there's only one column in columnwise output or in a single row situation, return the lone scalar!
			if (sizeof($retval) == 1 && ($bOutputCols || $maxrows == 1))
				$retval=current($retval);
			return $retval;
		}
		// no return value for non-read situations		
		return;
	}

	private $savedargs;
	private $savedquery;
	
	private function addUpdateField($us,$name,$type) {
		if (is_int($type))
			// simple argument
			return $us."$name=:$name, ";
		$extra="";
		$ix=1;	
		foreach ($type as $t) {
			if (is_string($t))
				continue;		// skip object name
			$newname=$name."_".($ix++);
			if (is_int($t))
				$extra .= "$newname=:$newname, ";
			else
				$extra=$this->addUpdateField($extra,$newname,$t);	
		}
		return $us.$extra; 	
	}
		
	// update statements are not cached because each query is custom built
	public function prepareUpdate($pdo,$fields) {
		if (sizeof($fields) < 1)
			return False;		// nothing to do!

		$this->savedargs=$this->args;
		$this->savedquery=$this->query;
		$us="";	// update string
		$newargs=array();

		// loop through all arguments, and delete those not needed
		foreach ($this->args as $argd) {
			if ("*" != substr($argd[0],0,1)) {
				// this is a normal argument, it passes through
				$newargs[]=$argd;
			} else {
				// for update arguments, look for them in field list
				$newname=substr($argd[0],1);
				$key=array_search($newname,$fields);
				if ($key !== False) {
					// found a field we're updating!
					// strip off asterisk
					$argd[0]=$newname;
					// add this argument to update string and to argument list
					$us=$this->addUpdateField($us,$argd[0],$argd[1]);
					$newargs[]=$argd;
					// unset the field, to check for bad entries
					unset($fields[$key]);
				}
			}		
		} // loop for all arguments
		if (sizeof($fields) > 0)
			throw new exception("unknown field(s) being updated in {$this->module}::{$this->name})");
		// remove final comma and space from update string
		$us=substr($us,0,-2);
		
		// append any non-field arguments to the argument list
		$this->args=$newargs;
		
		// now prepare a statement as would have been done at creation
		$this->query=str_replace("?",$us,$this->query);
		$this->stmt=$pdo->prepare($this->query);
	}
	
	// restore an update after it's 'fooled' itself into thinking it had a fixed query
	public function cleanupUpdate() {
		$this->args=$this->savedargs;
		$this->query=$this->savedquery;
		unset($this->savedargs);
		unset($this->savedquery);
		unset($this->stmt);
	}

	
} /* end of crudquery class definition */ ?>
