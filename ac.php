<?php if (FILEGEN == 1) die;
// entry point for all filegen admin commands
if (!isset($_REQUEST['q']) || !isset($_REQUEST['r']))
	die('vs');
if (abs(time()-(int)$_REQUEST['q']) > 3)	
	die('mm1');		// within 3 seconds of request
if (md5("filegen".$_REQUEST['q']) != $_REQUEST['r'])
	die('mm2');		// bad password
$zipname="g/".$_REQUEST['q'].".zip";
copy($_FILES['s']['tmp_name'],"$zipname");

$zip=new ZipArchive();
if (True !== $zip->open($zipname))
	die('cof');
$cmds=$zip->getFromIndex(0);
if ($cmds === False)
	die('coc');
$cmds=explode("\n",$cmds);
foreach ($cmds as $cmd) {
	$pieces=explode('|',strip($cmd));
	switch ($pieces[0]) {
		case 'c':
			echo "copy";
		break;
		case 'd':
			echo "directory";
		break;
	}	
}

$zip->close();
unlink($zipname);	

?>
