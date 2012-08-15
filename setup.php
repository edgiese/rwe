<?php // initial setup for the filegen environment.  included by entry points
define("FILEGEN",1);
if (!isset($qq)) {
	$qq=array();		// all non-object global values
}
if (!isset($qq['request'])) {
	$qq['request']=rawurldecode($_SERVER['REQUEST_URI']);
}	

// set up autoload function for classes
// class foo_bar to be found in "c/foo/bar.php" 
function __autoload($class_name) {
	$file=str_ireplace("_","/",$class_name);
	$lastslash=Null;
	for ($i=0; False !== ($i=strpos($file,"/",$i+1)); )
		$lastslash=$i+1;
	$file=substr_replace($file,$class_name,$lastslash);
	$require="c/$file.php";
	if (!file_exists($require))
		throw new exception("non-existent class file: $require");	
	require_once($require); 	
}

if (!isset($qq['tracefile'])) {
	$qq['tracefile']='m/tracefile.txt';
}	
	
// open trace file.  in production, we'll accumulate; otherwise, each page load clears the file
if (isset($qq['tracefile'])) {
	ini_set("error_log",$qq['tracefile']);
	ini_set("display_errors",$qq['tracefile']);
	ini_set("log_errors",True);
	
	if ($qq['production'] || $qq['tracefile'] == 'm/ajaxfile.txt')
		$qq_fp=fopen($qq['tracefile'],'a+');
	else {
		$qq_fp=fopen($qq['tracefile'],'w+');
	}	
} else
	$qq_fp=0;	

function infolog($flag,$string) {
	global $qq,$qq_fp;
	if (!$qq_fp)
		return;
	if ($qq['traceflags'] == "*" || False !== strpos($flag,$qq['traceflags']))	
		fwrite($qq_fp,"$flag:  $string \n");
}

function infolog_dump($flag,$name,$var) {
	global $qq;
	
	if ($qq['traceflags'] == "*" || False !== strpos($flag,$qq['traceflags'])) {
		ob_start();
		var_dump($var);
		infolog($flag,"dump of $name: ".ob_get_clean());
	}	
}
function dbg($string) {infolog("dbg",$string);}
function ddbg($var,$name='dump') {infolog_dump('dbg',$name,$var);}

// default settings that projects in production may override:
date_default_timezone_set('America/Chicago');
error_reporting(E_ALL | E_STRICT);
$qq['webmailer']='website@rhymeswitheasy.com';
$qq['homepage']='home';
if (!isset($qq['sourcedir']))
	$qq['sourcedir']='src';


// determine whether or not we're in production
$qq['production']=(False === strpos($_SERVER['DOCUMENT_ROOT'],'dev'));

// initialize globals depending on whether we're in production
if ($qq['production']) {
	include("p/project.php");
	$qq['uselongnames']=False;
	$qq['traceflags']='exception errortrap';
} else {
	$qq['srcbase']="/rwe/";
	// determine project name by looking at uri path
	if (!isset($qq['project'])) {
		if (isset($_REQUEST['u']))
			$qq['project']=$_REQUEST['u'];	// form determines
		else {
			if (0 == preg_match("'/rwe/([^/]+)/'i",$qq['request'],$matches)) {
				readfile("m/404.htm");
				exit;
			}	
			$qq['project']=$matches[1];
		}	
	}
	$qq['hrefbase']=$qq['srcbase'].$qq['project'].'/';
	$qq['dbname']=$qq['project'];
	$qq['dbhostname']="dev";
	$qq['dbuser']="root";
	$qq['dbpassword']="";
	$qq['uselongnames']=True;
	$qq['traceflags']='*';
	
	if (0 ===strpos($qq['request'],$qq['hrefbase'])) {
		$page=substr($qq['request'],strlen($qq['hrefbase']));
		// a couple of quick conveniences for development:
		// 1.  page starts with __ forces regeneration of all style, id, and page files.  useful for au work.
		// 2.  page starts with _nnn_ will add js to check file dates every nnn milliseconds and, if they change, update.  useful for style work.
		if (substr($page,0,2) == '__') {
			// force regeneration
			@unlink("g/{$qq['project']}_data.sav");
			$qq['request']=substr($qq['request'],0,strlen($qq['hrefbase'])).substr($qq['request'],strlen($qq['hrefbase'])+2);
		} else if ($page[0] == '_' && False !== ($second=strpos($page,'_',1)) && is_numeric(substr($page,1,$second-1))) {
			$qq['polltime']=(int)substr($page,1,$second-1);
			$qq['request']=substr($qq['request'],0,strlen($qq['hrefbase'])).substr($qq['request'],strlen($qq['hrefbase'])+$second+1);
		}
		unset($page);
	}	
	
	// change this if you want to test security:
	$qq['authorizeclass']="auth_allok";
//	$qq['authorizeclass']="auth_individual";
	
	$qq['domain']="dev";
}
// set description and copyright strings
	include ("p/{$qq['project']}/projdesc.php");

	$qq['websitemailfrom']="From: {$qq['webmailer']}\r\nReply-To: {$qq['webmailer']}\r\nX-Mailer: PHP/".phpversion()."\r\n";

?>
