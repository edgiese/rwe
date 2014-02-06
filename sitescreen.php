<? // file to add screen-door security access to an entire site
// can be called directly (to process password) or from within setup.php (to print form)
if (FILEGEN == 1) {
	// print form

if (!isset($qq['screenmsg']))
	$qq['screenmsg']="<h1>This site is restricted</h2><p>You must enter a password to see the contents of this site.</p>";
echo <<<EOT
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html><head><meta http-equiv="content-type" content="text/html; charset=windows-1250">
<title>Site Restricted</title>
<style type="text/css">
p {margin-left: 20px}
body {background-color: rgb(220,150,170); margin: 20px; border: 10px solid rgb(240,170,190); padding: 20px; font-family: courier new;}
</style></head><body>
{$qq['screenmsg']}
<p><form action="{$qq['srcbase']}sitescreen.php" method="get"><input name="screen" type="password" size="12" maxchars="12" />&nbsp;&nbsp;&nbsp;<input type="submit" value="Submit" />
<input type="hidden" name="q" value="{$qq['request']}" /><input type="hidden" name="u" value="{$qq['project']}" />
</form></p>
</body></html>
EOT;
	
} else {
	// process form
	if (!isset($_REQUEST['q']) || !isset($_REQUEST['u']) || !isset($_REQUEST['screen']))
		die;
	$qq=array();
	$qq['project']=$_REQUEST['u'];	
	include ("setup.php");
	
	// this will set the password	
	$new_sitescreenpass=$_REQUEST['screen'];
	include ("setupdb.php");

	// transfer to original screen	
	header("location:{$_REQUEST['q']}");
	
	include('cleanupdb.php');			
	include('cleanup.php');
				
}

  
?>
