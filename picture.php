<?php if (FILEGEN == 1) die;
// image display program

// set up class autoloader and trace functions.  sets $qq: 	
include('setup.php');

try {
	// sets up database access and $qq objects
	include('setupdb.php');
	
	// outputs the picture
	$bFound=False;
	if (isset($_REQUEST['i'])) {
		try {
			$img=new image($_REQUEST['i']);
			echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01//EN\" \"http://www.w3.org/TR/html4/strict.dtd\"><html>";
			// do the head
			echo "<head>";
			echo "<title>{$_REQUEST['t']}</title>";
			echo "<meta name=\"description\" content=\"Picture\" />";
			echo "<meta name=\"copyright\" content=\"{$qq['copyright']}\" />";
			echo '<meta http-equiv="Content-Type" content="text/xml; charset=windows-1252" />';
			echo '<meta http-equiv="imagetoolbar" content="no" />';
			echo '<meta name="MSSmartTagsPreventParsing" content="TRUE" />';
			echo '<meta http-equiv="EXPIRES" content="0">';
			echo '<meta http-equiv="CACHE-CONTROL" content="NO-CACHE">';
			echo '<meta name="robots" content="noindex, nofollow">';
			echo $img->getOutput();
			echo "</head></html>";
			$bFound=True;
		} catch (Exception $e) {
			if (!$qq['production'])
				include('exception.php');
			// nothing to do... fall through because image doesn't exist.  we'll print that out below
		} 	
	}
	if (!$bFound)
		readfile("m/404.htm");
	include('cleanupdb.php');			
} catch (Exception $e) {
	include('exception.php');
}
include('cleanup.php');
?>
