<?php if (FILEGEN != 1) die;
// output an exception report for filegen
while (ob_end_clean())
	;	// discard any output buffers and turn them off
if ($qq['production']) {
	$message=<<<EOT
Exception occurred in installation {$qq['dbname']}
Requesting Address: {$_SERVER['REMOTE_ADDR']}
Requesting User Agent: {$_SERVER['HTTP_USER_AGENT']}
Request URI: {$_SERVER['REQUEST_URI']}
------------------
{$e->getMessage()} (code={$e->getCode()})
------------------
line {$e->getLine()} in file {$e->getFile()}  
{$e->getTraceAsString()}
EOT
;
	mail('edgiese@gmail.com',"Web site exception for {$qq['project']}",$message,$qq['websitemailfrom']);				
	infolog("exception",$message);
	header('HTTP/1.1 500 Internal Server Error');
	readfile("m/exception-top.htm");
	readfile("m/exception-prod-btm.htm");
} else {
	// if normal page, output exception to browser.  otherwise, to log
	if ($qq['tracefile'] == 'm/tracefile.txt') {
		readfile("m/exception-top.htm");
		echo "<strong>Exception occurred:</strong><br />";
		echo '<table border="1">';
		echo "<tr><td>Message</td><td>{$e->getMessage()}</td></tr>";
		echo "<tr><td>Code</td><td>{$e->getCode()}</td></tr>";
		echo "<tr><td>File and Line</td><td>{$e->getFile()} Line {$e->getLine()}</td></tr>";
		$trace=str_ireplace("\n","<br />",$e->getTraceAsString());
		echo "<tr><td>Stack Trace</td><td>{$trace}</td></tr>";
		echo "</table></body></html>";
	} else {
		$message=<<<EOT
Exception occurred in installation {$qq['dbname']}
{$e->getMessage()} (code={$e->getCode()})
line {$e->getLine()} in file {$e->getFile()}  
{$e->getTraceAsString()}
EOT
;
		infolog("exception",$message);
	}
	if (is_object($qqs))
		$qqs->logException($e);
}
?>
