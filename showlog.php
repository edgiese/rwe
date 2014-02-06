<?php // utility to create a filegen database

define("FILEGEN",1);
// set up autoload function for classes
// class foo_bar to be found in "c/foo/bar.php" 
function __autoload($class_name) {
	$file=str_ireplace("_","/",$class_name);
	for ($i=0; False !== ($i=strpos($file,"/",$i+1)); )
		$lastslash=$i+1;
	$file=substr_replace($file,$class_name,$lastslash);	
	require_once("c/$file.php"); 	
}

echo <<<EOT
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html><head><meta http-equiv="content-type" content="text/html; charset=windows-1250">
<title>Log File</title>

<style type="text/css">
body {font-family: courier new;font-size:12px;}
body {background-color: #88cc88; }
div.section {background-color: #F0FFE0; margin: 10px 10px 10px 10px; padding: 5px 5px 5px 5px;}
div.section h1 {font-size: 16px;}
div.section pre {padding 0; margin 0;}
</style></head><body>
EOT;

echo '<div class="section"><h1>Main Trace</h1><pre>';
$lines=file("m/tracefile.txt");
foreach ($lines as $line)
	echo htmlspecialchars($line);	
echo '</pre></div>';

echo '<div class="section"><h1>Form File Trace</h1><pre>';
$lines=file("m/formfile.txt");
foreach ($lines as $line)
	echo htmlspecialchars($line);	
echo '</pre></div>';

echo '<div class="section"><h1>Ajax File Trace</h1><pre>';
$lines=file("m/ajaxfile.txt");
foreach ($lines as $line)
	echo htmlspecialchars($line);	
echo '</pre></div>';

echo '<div class="section"><h1>Edit File Trace</h1><pre>';
$lines=file("m/editfile.txt");
foreach ($lines as $line)
	echo htmlspecialchars($line);	
echo '</pre></div>';

echo "</body></html>";	

?>
