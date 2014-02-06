<?php if (FILEGEN != 1) die;
class message {
////////////////////////////////////////// message class ///////////////////////
// hard coded messages:
const MISSINGCOOKIE=-1;
const TRANSACTIONFINISHED=-2;

static function redirectString($message,$originUri="") {
	global $qqi,$qqs;
	
	if (is_string($message)) {
		// convert message and title to data and save as a transaction
		$msgdata=$qqs->beginTransaction($originUri);
		$qqs->setTransactionData($msgdata,'creole',$message);
	} else
		$msgdata=$message;
	return $qqi->hrefPrep("form/0/$msgdata",True);
}

// returns an array of title,message,code
static function getMessageInfo($message) {
	global $qqs;
	
	switch($message) {
		case self::MISSINGCOOKIE:
			$retval="=You need to have cookies active to perform this function=\n\n";
			$retval .= "Cookies are a mechanism that your browser uses to track data.  If your browser is capable of tracking cookies, you should turn them on for this site.  Note that using 'session cookies' will work just fine and poses little to no privacy threat.\n";
		break;
		
		case self::TRANSACTIONFINISHED:
			$retval="=The transaction is finished=\n\n";
			$retval .= "You are attempting to resubmit a transaction that has already completed.  If you want to duplicate what you did earlier, you need to go back and start from the beginning.\n\n";
		break;
		
		default:
			if ($message <= 0)
				throw new exception("illegal message number: $message");
			$retval=$qqs->getTransactionData($message,'creole');	
		break;
	}
	return $retval;
}
////////////////////////////////////////// end of class definition /////////////
} ?>
