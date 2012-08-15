<?php if (FILEGEN != 1) die;
// helper for mailing
class mailer {
////////////////////////////////////////////////
// short message from default mailer
static public function quickMail($to,$subject,$msg,bLog=True) {
	$mailfrom="From: {".qq('webmailer')."\r\nReply-To: ".qq('webmailer')."\r\nX-Mailer: PHP/".phpversion()."\r\n";
	if ($bLog)
		infolog("email","Mail to: $to  Subject: $subject  Message: $msg");
	mail($to,wordwrap($msg,70),$mailfrom);
}


// end of mailer class ///////////////////////////
} ?>
