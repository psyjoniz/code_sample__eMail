<?php

/**
 * THIS IS A COPY OF eMail.class.php FROM psy-core ( http://psy-core.com ), IS PRIVATE INTELLECTUAL PROPERTY
 * AND ONLY HERE AS A CODE SAMPLE.  YOU MAY NOT USE THIS CLASS WITHOUT PERMISSION.
 */

require_once('psy-core/base/BaseCommon.class.php');

//NICE TO HAVE: SMTP relayers status cache - one thing i would like to do is when specifying to use an alternate smtp server, check to see if we have validated the server yet.  validation would include shooting a quick small email from here to a listened to email address (maybe just all email addresses, who cares) and store the results (true | false) in a validation file - then if we try to validate again we can check for the file first - problem becomes when do we refresh those files?  timeframe of a week or so?  so once a week all those email servers should be checked again?  how long should a false stick around?  a month or so?  what time passes before we decide it IS a false?

/**
 * The eMail class is meant to construct all parts of an email and submit a request for handling to an SMTP server.
 * History<br />
 * 2006.9.25 - psyjoniz - initial revision (happy birthday to me)<br />
 * * this version (0.00 alpha) is responsible for getting a bare-bones email functioning.  the socket-based transaction utilized in the original psyjoniz.com code base is being re-written into here (8 years with little need of fix or update; diggin it).  let's hope SMTP doesn't start talking really strange any time soon (doubtful).<br />
 * 2006.11.28 - psyjoniz - we have image interception<br />
 * * this version (0.01 alpha) is responsible for being able to send a MIME compatable eMail plus intercept images out of html for absorption into what is being sent to the recipient.  Lots of updates have weakened the socket based connection and the queuing isn't past design so mail is the only transmission method;  time to move forward with attachments in the next version<br />
 * 2006.12.3 - psyjoniz - attachments are done<br />
 * * this version (0.00 beta) is responsible for being able to construct and send a MIME compatable eMail including images (intecepted from HTML) within the eMail itself and also including attachments within the eMail itself.  Please note that socket connections are not working properly and mail is the preferred method of transmission now (if/when i ever see a need for socket based i'll get it working but until further notice socket based connections will not be built in).<br />
 * 2007.6.28 - psyjoniz - got the sockets working, kindof.  but for some resaon the body isn't being included.  thats kindof a problem ;)
 * 2007.7.6 - psyjoniz - finished the socket connection with all parts of email working properly; extending BaseCommon
 * * this version (1) is the first production version capable of sending with mail() or direct-to-socket methodologies and extends BaseCommon
 * <hr />
 * <b>Examples</b><br />
 * 1) sending a very simple eMail in one shot<br />
 * if(false !== $eMail_obj = new eMail('jesse@psy-core.com', 'geoff@plan8studios.com', 'hey, whats up?', 'this is a test email!! -geoff', true)) { echo('email should have sent and returned true'); }<br />
 * 2) setting up the object then manually building the eMails parts and telling the object to send<br />
 * $eMail =& Core::getObj('eMail');<br />
 * $eMail->setTo('jesse@psy-core.com', 'Jesse Quattlebaum'); //can be called setTo('email');<br />
 * $eMail->addRecipient('psyjoniz@gmail.com',    'CC',  'Jesse Quattlebaum'); //can be called addRecipient('email', 'rcp_type') or just addRecipient('email')<br />
 * $eMail->addRecipient('psyjoniz@psyjoniz.com', 'BCC', 'Jesse Quattlebaum');<br />
 * $eMail->setFrom('AutoMailer@psy-core.com', 'psy-core AutoMailer');<br />
 * $eMail->setSubject('This is a test email.');<br />
 * $eMail->setBody('a test of html, image extraction and embedding those images: <img src="http://www.psyjoniz.com/.images/photography_icon.gif"> (should be a camera icon)');<br />
 * $eMail->addAttachment('http://www.psyjoniz.com/.images/NLightEnd_finished.jpg');<br />
 * $eMail->send();<br />
 * 3) sending multiple eMails with one object first by manually building the eMail then by using the start function to kick off a simplistic second eMail<br />
 * $eMail =& Core::getObj('eMail');<br />
 * $eMail->setTo('jesse@psy-core.com', 'Jesse Quattlebaum'); //can be called setTo('email');<br />
 * $eMail->addRecipient('psyjoniz@gmail.com',    'CC',  'Jesse Quattlebaum'); //can be called addRecipient('email', 'rcp_type') or just addRecipient('email')<br />
 * $eMail->addRecipient('psyjoniz@psyjoniz.com', 'BCC', 'Jesse Quattlebaum');<br />
 * $eMail->setFrom('AutoMailer@psy-core.com', 'psy-core AutoMailer');<br />
 * $eMail->setSubject('This is a test email.');<br />
 * $eMail->setBody('a test of html, image extraction and embedding those images: <img src="http://www.psyjoniz.com/.images/photography_icon.gif"> (should be a camera icon)');<br />
 * $eMail->addAttachment('http://www.psyjoniz.com/.images/NLightEnd_finished.jpg');<br />
 * $eMail->send();<br />
 * $eMail->start('to_email@wherever.com', 'from_email@somewhere.net', 'the subject', 'and body of email');<br />
 * $eMail->addAddachment('/file/on/server');<br />
 * $eMail->send();<br />
 * @package psy-core
 * @author psyjoniz (jesse@streamliningit.com)
 * @version 1
 */
class eMail extends BaseCommon {
	
	//BEGIN VARS ESTABLISHMENT
	//Standard var's
	private $debug                 = false;
	var     $Disruption            = false;
	//Var's for this class
	private $message_id            = false;       //Ends up a unique id when an eMail is started
	private $date                  = false;
	private $smtp_headers          = array();
	private $smtp_server           = 'localhost'; //SMTP server willing to transport mail for us
	private $smtp_sock             = false;       //Private socket connection used to talk with SMTP server
	private $smtp_port             = '25';      //Tunnel to hermes
	private $mime_psycore_version  = '0.00 alpha';
	private $email_headers         = false;
	private $email_to_name         = false; //name
	private $email_to_email        = false; //email
	private $email_to              = false; //"name" <email@domain.ext>
	private $from_name             = false;
	private $from_email            = false;
	private $reply_to_name         = false;
	private $reply_to_email        = false;
	private $subject               = false;
	private $raw_email_body        = false; //storage of the un-edited version of the body of the email
	private $email_body            = false; //what the raw_email_body becomes when we are done getting images and attachments and have added them in
	private $template              = false;
	private $rcpt_to               = array(); 
	private $cc                    = array(); 
	private $bcc                   = array();
	private $images_search_pattern = '/<[^>]*img[^>]*src=([\'"]?)([^>\\1]+)\\1([^>]*)>/i';
	private $img_parts             = array(); 
	private $attach_parts          = array();
	private $trans_type            = 'mail'; //[mail=mail();|socket=direct socket connection]
	private $supply_alternative    = true; //not sure what this is for - remove if you don't find a use by 10.15.2006 - psy
	private $priority              = '3';
	private $mspriority            = 'Normal';
	private $psycore_priority      = '3'; //Hey, it's a status thing.  I just wants it.  (Will probably tie into incoming email handling at some point)
	private $mime_boundary         = "__________psy-core_MIME_BOUNDARY__________";
	private $html_charset          = 'ISO-8859-1';
	private $text_charset          = 'ISO-8859-1';
	private $file_types            = array(
					'zip'     => 'archive/zip',
					'jpg'     => 'image/jpeg',
					'jpeg'    => 'image/jpeg',
					'jpe'     => 'image/jpeg',
					'gif'     => 'image/gif',
					'bmp'     => 'image/bmp',
					'swf'     => 'application/x-shockwave-flash',
					'tiff'    => 'image/tiff',
					'tif'     => 'image/tiff',
					'png'     => 'image/png',
					'zip'     => 'archive/zip',
					'gz'      => 'archive/gzip',
					'gzip'    => 'archive/gzip',
					'tar'     => 'archive/tar',
					'tgz'     => 'archive/tgzip',
					'lha'     => 'archive/lha',
					'arj'     => 'archive/arj',
					'doc'     => 'application/msword',
					'xls'     => 'application/msexcel',
					'pdf'     => 'application/pdf',
					'docx'    => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
					'unknown' => 'unknown/unknown',
					''        => 'unknown/unknown',
	);
	private $image_types = array(
					'jpg'     => 'image/jpeg',
					'jpeg'    => 'image/jpeg',
					'jpe'     => 'image/jpeg',
					'gif'     => 'image/gif',
					'bmp'     => 'image/bmp',
					'swf'     => 'application/x-shockwave-flash',
					'tiff'    => 'image/tiff',
					'tif'     => 'image/tiff',
					'png'     => 'image/png',
	);
	//END VARS ESTABLISHMENT
					
	/**
	 * Constructor for eMail.  The inputs allow us to quickly send an email, set up to perform a slightly more complicated email (ie: including names -and- email addresses) or set up to shoot off multiple versions of a single email.
	 * History<br />
	 * 2006.11.28 - psyjoniz - lets do this<br />
	 * @author psyjoniz (jesse@psy-core.com)
	 * @param string $to
	 * @param string $from
	 * @param string $subject
	 * @param string $body
	 * @param bool $send
	 * @return bool [true|false]
	 * @since Version 0.00 alpha
	 * @version 0.00 alpha
	 */
	function __construct($to = false, $from = false, $subject = false, $body = false, $send = false) { //BEGIN __construct
		
		$this->Disruption =& Core::getObj('Disruption');

		$this->start($to, $from, $subject, $body);

		if($send) {
			if(!$this->send()) { return false; }
		}
		
		return true;

	} //END __construct
	
	function setDebug($tf) {
		if(false === $tf || true === $tf) {
			$this->debug = $tf;
		} else {
			$this->debug = false;
		}
	}

	/* 2006.11.28 - psyjoniz */
	function start($to = false, $from = false, $subject = false, $body = false) {
		$this->date = date('r');
		if(trim($to) != '') {      $this->setTo($to);           unset($to);      }
		if(trim($from) != '') {    $this->setFrom($from);       unset($from);    }
		if(trim($subject) != '') { $this->setSubject($subject); unset($subject); }
		if(trim($body) != '') {    $this->setBody($body);       unset($body);    }
	}

	//////////////////////////////////////////////////////////
	//SMTP HEADERS
	/* 2006.11.28 - psyjoniz */
	function buildSMTPHeaders() {
		// ONLY USED FOR EXTENDED SMTP FUNCTIONALITY // $this->smtp_headers[] = array('send' => 'EHLO ' .       $this->smtp_server, 'expect' => '250');
		$this->smtp_headers[] = array('send' => 'HELO ' .       $this->smtp_server,    'expect' => '250');
		$this->smtp_headers[] = array('send' => 'MAIL From: ' . $this->from_email,     'expect' => '250');
		$this->smtp_headers[] = array('send' => 'RCPT To: '   . $this->email_to_email, 'expect' => '250');
		$cc_count = count($this->cc);
		for($x = 0; $x < $cc_count; $x++) {
			$this->smtp_headers[] = array('send' => 'RCPT To: ' . $this->cc[$x]['email'], 'expect' => '250');
		}
		$bcc_count = count($this->bcc);
		for($x = 0; $x < $bcc_count; $x++) {
			$this->smtp_headers[] = array('send' => 'RCPT To: ' . $this->bcc[$x]['email'], 'expect' => '250');
		}
	}
	//SMTP HEADERS
	//////////////////////////////////////////////////////////

	//////////////////////////////////////////////////////////
	//MAIL HEADERS
	/* 2006.11.28 - psyjoniz */
	function setPriority($priority = '3') {
		$this->priority = $priority;
		return true;
	}
	
	/* 2006.11.28 - psyjoniz */
	function setMSPriority($priority = 'Normal') {
		$this->mspriority = $priority;
		return true;
	}
	
	/* 2006.11.28 - psyjoniz */
	function setpsyCorePriority($priority = '3') {
		$this->psycore_priority = $priority;
		return true;
	}
	
	/* 2006.11.28 - psyjoniz */
	function setTextCharset($charset = 'ISO-8859-1') {
		$this->text_charset = $charset;
		return true;
	}
	
	/* 2006.11.28 - psyjoniz */
	function setHTMLCharset($charset = 'ISO-8859-1') {
		$this->html_charset = $charset;
		return true;
	}
	
	/* 2006.11.28 - psyjoniz */
	function buildMailHeaders() {
		if(!$this->reply_to_name || !$this->reply_to_email) {
			$this->setReplyTo($this->from_email, $this->from_name);
		}
		$this->email_headers  = "";
		$this->email_headers .= "MIME-Version: 1.0\n";
		$this->email_headers .= "X-Mailer: 'psy-core::eMail <http://psy-core.com>'\n";
		$this->email_headers .= "X-Priority: " . $this->priority . "\n";
		$this->email_headers .= "X-MSMail-Priority: " . $this->mspriority . "\n";
		$this->email_headers .= "X-psy-core-Priority: " . $this->psycore_priority . "\n";
		$this->email_headers .= "X-Mime-psy-core: Created by Mime-psy-core v" . $this->mime_psycore_version . "\n";
		$this->email_headers .= "From: \"" . $this->from_name . "\" <" . $this->from_email . ">\n";
		$this->email_headers .= "Reply-To: \"" . $this->reply_to_name . "\" <" . $this->reply_to_email . ">\n";
		$this->email_headers .= "To: " . $this->email_to . "\n";
		$rcpt_to_count = count($this->rcpt_to);
		if($rcpt_to_count > 0) {
			for($x = 0; $x < $rcpt_to_count; $x++) {
				$this->email_headers .= "Rcpt-To: \"" . $this->rcpt_to[$x]['name'] . "\" <" . $this->rcpt_to[$x]['email'] . ">\n";
			}
		}
		$cc_count = count($this->cc);
		if($cc_count > 0) {
			for($x = 0; $x < $cc_count; $x++) {
				$this->email_headers .= "Cc: \"" . $this->cc[$x]['name'] . "\" <" . $this->cc[$x]['email'] . ">\n";
			}
		}
		$this->email_headers .= "Subject: " . $this->subject . "\n";
		$this->email_headers .= "Date: " . $this->date . "\n";
		$this->email_headers .= "Message-ID: <" . $this->message_id . "@psy-core>\n";
		$this->email_headers .= "Content-Type: multipart/mixed; boundary=\"" . $this->mime_boundary . "\"\n";
		$this->email_headers .= "Content-Transfer-Encoding: 7bit\n";
		return true;
	}
	//MAIL HEADERS
	//////////////////////////////////////////////////////////

	//////////////////////////////////////////////////////////
	//TO
	/* 2006.11.28 - psyjoniz */
	function setTo($to_email, $to_name = false) { 
		if(!$to_name) { 
			if(!$this->setToName($to_email)) { return false; } 
		} else { 
			if(!$this->setToName($to_name)) { return false; }
		} 
		if(!$this->setToEmail($to_email)) { return false; }
		$this->email_to = '"' . $this->email_to_name . '" <' . $this->email_to_email . '>';
		return true; 
	}
	
	/* 2006.11.28 - psyjoniz */
	function setToName($to_name) {
		if(Core::usable($to_name)) {
			$this->email_to_name = $to_name;
		} else {
			return false;
		}
		return true;
	}

	/* 2006.11.28 - psyjoniz */
	function setToEmail($to_email) {
		if(Core::usable($to_email)) {
			$this->email_to_email = $to_email;
		} else {
			return false;
		}
		return true;
	}
	//TO
	//////////////////////////////////////////////////////////

	//////////////////////////////////////////////////////////
	//FROM
	/* 2006.11.28 - psyjoniz */
	function setFrom($from_email, $from_name = false) { 
		if(!$from_name) { 
			if(!$this->setFromName($from_email)) { return false; } 
		} else { 
			if(!$this->setFromName($from_name)) { return false; }
		} 
		if(!$this->setFromEmail($from_email)) { return false; } 
		return true; 
	}
	
	/* 2006.11.28 - psyjoniz */
	function setFromName($from_name) {
		if(Core::usable($from_name)) {
			$this->from_name = $from_name;
		} else {
			return false;
		}
		return true;
	}

	/* 2006.11.28 - psyjoniz */
	function setFromEmail($from_email) {
		if(Core::usable($from_email)) {
			$this->from_email = $from_email;
		} else {
			return false;
		}
		return true;
	}
	//FROM
	//////////////////////////////////////////////////////////

	//////////////////////////////////////////////////////////
	//REPLYTO
	/* 2006.11.28 - psyjoniz */
	function setReplyTo($reply_to_email, $reply_to_name = false) {
		$this->reply_to_email = $reply_to_email;
		if(!$reply_to_name) {
			$this->reply_to_name = $this->reply_to_email;
		} else {
			$this->reply_to_name = $reply_to_name;
		}
	}
	//REPLYTO
	//////////////////////////////////////////////////////////

	//////////////////////////////////////////////////////////
	//SUBJECT
	/* 2006.11.28 - psyjoniz */
	function setSubject($subject) { 
		$this->subject = $subject; 
		return true; 
	}
	//SUBJECT
	//////////////////////////////////////////////////////////

	//////////////////////////////////////////////////////////
	//BODY
	/* 2006.11.28 - psyjoniz */
	function setBody($body, $type = 'string') {
		if($this->debug) {
			//Debug::backTrace();
			echo('Adding Body to eMail<br />');
			echo('<pre>' . htmlspecialchars($body) . '</pre>');
		}
		//type might be a template to be included one day, who knows
		$this->raw_email_body = stripslashes($body);
		return true; 
	}
	
	/* 2006.11.28 - psyjoniz */
	function addToBody($content) {
		$this->raw_email_body .= $content;
		return $true;
	}
	
	/* 2006.11.28 - psyjoniz */
	function buildBody() {
		// Intercept images from raw eMail body and store to img_parts
		$this->findImages();
		$this->processImages();
		$this->email_body = "This is a Multipart MIME encoded eMail generated by psy-core.  If you are seeing this, that means your email client does not reckognize MIME format.  Consider upgrading :)\n\nHere is the original message reduced to text:\n\n";
		//put up a reduced-to-text version
		$this->email_body .= trim(strip_tags($this->raw_email_body)) . "\n\n";
		//put up the html version replacing the img references found
		$this->email_body .= "--" . $this->mime_boundary . "\n";
		$this->email_body .= "Content-Type: text/html; charset=" . $this->html_charset . "\n";
		$this->email_body .= "Content-Transfer-Encoding: 7bit\n\n";
		$this->email_body .= $this->replaceImages($this->raw_email_body);
		//add images from img_parts grabbing the data for each one
		foreach($this->img_parts as $img_name => $img_name_arr) {
			//echo('img_name: ' . $img_name . '<br />');
			$this->email_body .= "\n\n--" . $this->mime_boundary . "\n";
			$this->email_body .= "Content-Type: " . $this->image_types[$this->img_parts[$img_name]['type']] . "; name=\"" . $img_name . "\"\n";
			$this->email_body .= "Content-Transfer-Encoding: base64\n";
			$this->email_body .= "Content-ID: <" . $this->img_parts[$img_name]['cid'] . ">\n";
			$this->email_body .= "Content-Disposition: inline;\n";
			$this->email_body .= "\n";
			$this->email_body .= $this->encodeData($this->getFileData($this->img_parts[$img_name]['location']));
		}
		//add attachments from attach_parts grabbing the data for each one
		foreach($this->attach_parts as $attach_name => $attach_name_arr) {
			//echo('attach_name: ' . $attach_name . '<br />');
			$this->email_body .= "\n\n--" . $this->mime_boundary . "\n";
			$this->email_body .= "Content-Type: " . $this->file_types[$this->attach_parts[$attach_name]['type']] . "; name=\"" . $attach_name . "\"\n";
			$this->email_body .= "Content-Transfer-Encoding: base64\n";
			$this->email_body .= "Content-Disposition: attachment;\n";
			$this->email_body .= "\n";
			$this->email_body .= $this->encodeData($this->getFileData($this->attach_parts[$attach_name]['location']));
		}
		//close out the email
		$this->email_body .= "\n";
		$this->email_body .= "--" . $this->mime_boundary . "--\n\n";
	}
	//BODY
	//////////////////////////////////////////////////////////

	//////////////////////////////////////////////////////////
	//DATA HANDLING
	//(these functions prolly need to be abstracted - psy)
	/* 2006.11.28 - psyjoniz */
	function getFileData($location) {
		$Config =& Core::getObj('Config');
		$location = trim($location);
		if($location[0] == '/') {
			if(!file_exists($location)) {
				//dirty fix - 2007.7.5 - psyjoniz
				$location = 'http://' . $Config->get('site.url') . $location;
			}
		}
		//echo('location: ' . $location . '<br />');
		$data = '';
		if(Core::stringContains($location, 'https://')) { //use curl
			if($fh = curl_init($location)) {
				if(false === curl_errno($fh)) {
					ob_start();
					curl_exec($fh);
					$data = ob_get_contents();
					ob_end_clean();
				} else {
					//bad stuff
					$this->Disruption->add(WARNING, 'curl reported errors: ' . curl_errors($fh));
					return false;
				}
			} else {
				//bad stuff
				$this->Disruption->add(WARNING, 'Could not get data for location \'' . $location . '\'');
				return false;
			}
		} else { //use fopen
			if($fh = fopen($location, 'rb')) { //because, really, it should work just fine with http, ftp or any other resources (even one's requiring authentication if keys are setup properly)
				while(!feof($fh)) {
					$data .= fgetc($fh);
				}
				fclose($fh);
			} else {
				$this->Disruption->add(WARNING, 'Could not get data for location \'' . $location . '\'.');
				return false;
			}
		}
		return $data; 
	} 

	/* 2006.11.28 - psyjoniz */
	function encodeData($data, $type = 'base64') {
		switch($type) {
			case 'base64' : default :
				return chunk_split(base64_encode($data), 68, "\n");
				break;
		}
		return false;
	} 
	//DATA HANDLING
	//////////////////////////////////////////////////////////

	//////////////////////////////////////////////////////////
	//IMAGES HANDLING
	/* 2006.11.28 - psyjoniz */
	function findImages() {
		preg_match_all($this->images_search_pattern, $this->raw_email_body, $this->images_search_matches);
	}

	/* 2006.11.28 - psyjoniz */
	function addImagePart($location, $cid = 'GENERATE') {
		$location = trim($location);
		//find type
		$type = Path::getFileType($location);
		//find name
		$name = Path::getFileName($location);
		if(is_array($this->img_parts[$name['fullname']])) {
			$cid = $this->img_parts[$name['fullname']]['cid']; //this means we aren't adding images we've already added ie: shim.gif showing up multiple times in a layout
		} else {
			//prepare the cid
			if($cid == 'GENERATE') { 
				$cid = uniqid(time()); 
			}
			$this->img_parts[$name['fullname']] = array(
				'cid' => $cid,
				'location' => $location,
				'type' => $type['type']
			);
		}
		return $cid; 
	}

	/* 2006.11.28 - psyjoniz */
	function processImages() {
		//take $this->images_search_matches and populate what you can out of it into img_parts
		//echo('this->images_search_matches:<pre>' . htmlentities(print_r($this->images_search_matches, true)) . '<hr>');
		//echo "</pre>";
		$regex_results =& $this->images_search_matches[2];
		$matches_count = count($regex_results);
		for($x = 0; $x < $matches_count; $x++) {
			$this->addImagePart($regex_results[$x]);
		}
		//echo('img_parts:<pre>' . print_r($this->img_parts, true) . '</pre>');
	}

	/* 2006.11.28 - psyjoniz */
	function replaceImages($body) {
		$images_found = count($this->images_search_matches[0]);
		if(false !== $images_found) {
			for($x = 0; $x < $images_found; $x++) {
				$whole_tag = $this->images_search_matches[0][$x];
				$src = $this->images_search_matches[2][$x];
				$fn = Path::getFileName($src);
				$cid = $this->img_parts[$fn['fullname']]['cid'];
				$whole_tag_replacement = str_replace($src, 'cid:' . $cid, $whole_tag);
				//echo('whole_tag: ' . htmlentities($whole_tag) . '<br />');
				//echo('src: ' . $src . '<br />');
				//echo('cid: ' . $cid . '<br />');
				//echo('whole_tag_replacement: ' . htmlentities($whole_tag_replacement) . '<br />');
				$body = str_replace($whole_tag, $whole_tag_replacement, $body);
			}
		} //no images found; oh well
		//echo('body:<pre>' . htmlentities($body) . '</pre>');
		return $body;
	}

	/* 2006.11.28 - psyjoniz */
	function addImageData($name) {
		if($this->img_parts[$name]) {
			$this->img_parts[$name]['data'] = '';
			//prepare the data
			$data = getFileData($this->img_parts[$name]['location']);
			if(false === $data) {
				return false;
			} else {
				$data = encodeData($data);
			}
			$this->img_parts[$name]['data'] = $data;
		}
	}
	//IMAGES HANDLING
	//////////////////////////////////////////////////////////

	//////////////////////////////////////////////////////////
	//ATTACHMENTS HANDLING
	function addAttachment($location) {
		return $this->addAttachmentPart($location);
	}

	/* 2006.11.28 - psyjoniz */
	function addAttachmentPart($location) {
		$location = trim($location);
		//find type
		$type = Path::getFileType($location);
		//find name
		$name = Path::getFileName($location);
		if(is_array($this->attach_parts[$name['fullname']])) {
			//nothing, we already have it as an attachment
		} else {
			$this->attach_parts[$name['fullname']] = array(
				'location' => $location,
				'type' => $type['type']
			);
		}
		return true; 
	}

	function addAttachmentData($name) {
		if($this->attach_parts[$name]) {
			$this->attach_parts[$name]['data'] = '';
			//prepare the data
			$data = getFileData($this->attach_parts[$name]['location']);
			if(false === $data) {
				return false;
			} else {
				$data = encodeData($data);
			}
			$this->attach_parts[$name]['data'] = $data;
		}
	}
	//ATTACHMENTS HANDLING
	//////////////////////////////////////////////////////////

	/* 2006.11.28 - psyjoniz */
	function addRecipient($email, $type = 'RCPTTO', $name = false) {
		if(false === $name) { 

			$name = $email; 

		} 

		switch($type) { 

			case 'CC' :               $this->cc[]      = array('email' => $email, 'name' => $name, 'to' => '"' . $name . '" <' . $email . '>'); break; 
			case 'BCC' :              $this->bcc[]     = array('email' => $email, 'name' => $name, 'to' => '"' . $name . '" <' . $email . '>'); break; 
			case 'RCPTTO' : default : $this->rcpt_to[] = array('email' => $email, 'name' => $name, 'to' => '"' . $name . '" <' . $email . '>'); break; 

		}

		return true; 

	}
	
	/* 2006.11.28 - psyjoniz */
	function setTransType($type) {
		if($type == 'mail' || $type == 'socket') {
			$this->trans_type = $type;
			return true;
		} else {
			return false;
		}
	}

	/* 2006.11.28 - psyjoniz */
	function send() {
		$this->message_id = uniqid(time());

		//--------------------------------------------------------------
		// build eMail headers
		//--------------------------------------------------------------
		$this->buildMailHeaders();

		//--------------------------------------------------------------
		// Build eMail body
		//--------------------------------------------------------------
		$this->buildBody();

		//--------------------------------------------------------------
		// Send it off
		// mail           = mail()
		// socket|default = socket connection direct to SMTP server
		//--------------------------------------------------------------
		//echo(str_replace('\n', '<br />', '<pre>====attempting to send:<br>====email_to:<br>' . htmlentities($this->email_to) . '<br>====subject:<br>' . htmlentities($this->subject) . '<br>====email_body:<br>' . htmlentities($this->email_body) . '<br>====email_headers:<br>' . htmlentities($this->email_headers) . '</pre>'));
		switch($this->trans_type) {
			case 'queue' :
				//not implemented, yet.
				//features should include:
				//- Schedulable eMail
				//- Prioritizable queuing system
				//- anything else?
				return false;
			break;
			case 'mail' :
				//ini_set(sendmail_from, $this->from_email);
				$tmp_ini_smtp = ini_get('SMTP');
				ini_set('SMTP', $this->smtp_server);
				//echo('trying mail for this->email_to: ' . $this->email_to . '<br />');
				//echo('this->subject : ' . $this->subject . '<br />');
				//echo('this->email_body : ' . $this->email_body . '<br />');
				//echo('this->email_headers : ' . $this->email_headers . '<br />');
				if(!mail($this->email_to_email, $this->subject, $this->email_body, $this->email_headers)) {
					$this->Disruption->add(ERROR, 'mail(' . $this->email_to . '): FAILED');
				} else {
					//echo('mail(' . $this->email_to . '): SUCCESSFUL<br />');
					//now kick off to rcpt_to, cc and bcc
					$dests = array('rcpt_to' => true, 'cc' => true, 'bcc' => true);
					//echo('dests:<pre>' . print_r($dests, true) . '</pre>' . chr(10));
					foreach($dests as $type => $type_arr) {
						//echo('type: ' . $type . '<br />' . chr(10));
						$count = count($this->$type);
						//echo('count: ' . $count . '<br />' . chr(10));
						if($count > 0) {
							//echo('trying to send some emails..<br />' . chr(10));
							for($x = 0; $x < $count; $x++) {
								$this_arr = $this->$type;
								//echo('this_arr:<pre>' . print_r($this_arr, true) . '</pre>' . chr(10));
								//echo('trying mail for this_arr[' . $x . '][to]: ' . $this_arr[$x]['to'] . '<br />');
								if(!mail($this_arr[$x]['email'], $this->subject, $this->email_body, $this->email_headers)) {
									$this->Disruption->add(ERROR, 'mail(' . $this_arr[$x]['to'] . '): FAILED');
								} else {
									//echo('mail(' . htmlentities($this_arr[$x]['to']) . '): SUCCESSFUL<br />');
								}
							}
						}
					}
				}
				ini_set('SMTP', $tmp_ini_smtp);
				//ini_restore(sendmail_from);
				//**************************************************************
			break;
			case 'socket' : default :
				//--------------------------------------------------------------
				// Build SMTP Headers
				//--------------------------------------------------------------
				$this->buildSMTPHeaders();

				//--------------------------------------------------------------
				// Open a socket to the SMTP server (storing err's locally for
				// storage into a possible Disruption)
				//--------------------------------------------------------------
				if($this->debug) { echo('Attempting to open a socket to ' . $this->smtp_server . ':' . $this->smtp_port . '<br>'); }
				$this->smtp_sock = fsockopen($this->smtp_server, $this->smtp_port, $errno, $errstr);
				if(!$this->smtp_sock) {
					$this->Disruption->add(ERROR, 'SMTP Socket failed: ' . $errno . ' - ' . $errstr);
					$this->cleanUp();
					return false;
				} else { if($this->debug) { echo('Socket opened successfully<br />'); } }

				//--------------------------------------------------------------
				// Hopefully we get a positive opening handshake
				//--------------------------------------------------------------
				if(true !== $this->getInitialResponseFromSocket('220')) { //220 is what we want - 221 is testing a false
					$this->Disruption->add(ERROR, 'failed');
					$this->cleanUp();
					return false;
				} else { if($this->debug) { echo('Got the response we were looking for<br />'); } }

				//--------------------------------------------------------------
				// Feed the smtp_headers in, checking for responses
				//--------------------------------------------------------------
				$smtp_headers_count = count($this->smtp_headers);
				for($m = 0; $m < $smtp_headers_count; $m++) {
					if(true !== $this->getResponseFromInputToSocket($this->smtp_headers[$m]['send'], $this->smtp_headers[$m]['expect'])) {
						$this->Disruption->add(ERROR, 'failed');
						$this->cleanUp();
						return false;
					} else { if($this->debug) { echo('Got the response we were looking for<br />'); } }
				}

				//--------------------------------------------------------------
				// Tell the SMTP server we are ready to dump the data (body of
				// the email)
				//--------------------------------------------------------------
				if(true !== $this->getResponseFromInputToSocket('data', '354')) {
					$this->Disruption->add(ERROR, 'eMail->getResponseFromInputToSocket() failed');
					$this->cleanUp();
					return false;
				} else { if($this->debug) { echo('Got the response we were looking for<br />'); } }
				//dump the email into sock (the multi-colored one with mini-socks for each of your toes)
				if(true !== $this->getResponseFromInputToSocket($this->email_headers . "\n" . $this->email_body . "\n\n.\n", '250')) {
					$this->Disruption->add(ERROR, 'eMail->getResponseFromInputToSocket() failed');
					$this->cleanUp();
					return false;
				} else { if($this->debug) { echo('Got the response we were looking for<br />'); } }
				//tell server to SEND
				if(true !== $this->getResponseFromInputToSocket('SEND', '250')) {
					$this->Disruption->add(NOTICE, 'SMTP and the SEND command:  A lot of servers dont implement this so not getting the response we are looking for is no big deal.  -psy');
					$this->cleanUp();
					// - only on real errors!! - return false;
				} else { if($this->debug) { echo('Got the response we were looking for<br />'); } }
				//wave goodbye
				if(true !== $this->getResponseFromInputToSocket('QUIT', '221')) {
					$this->Disruption->add(NOTICE, 'QUIT is a courtesy, if they dont like it who cares im closing connection anyhow.  -psy');
					$this->cleanUp();
					// - only on real errors!! - return false;
				} else { if($this->debug) { echo('Got the response we were looking for<br />'); } }
				//clean yo shaith!!!
				$this->cleanUp();
				//aiite mo'yatches, we OUT
				return true;
			break;
		}
		return true;
	}

	//////////////////////////////////////////////////////////
	//SOCKET
	//(these functions really need to be abstracted out into
	//a class meant specifically for socket based
	//communications - psy)
	/* 2006.11.28 - psyjoniz */
	function getInitialResponseFromSocket($expectedresponse) {
		$Disruption =& Core::getObj('Disruption');
		$response = fgets($this->smtp_sock, (strlen($expectedresponse)+5));
		if($this->debug) {
			echo('<hr>BEGIN: getInitialResponseFromInputToSocket()<br />');
			echo('expected response: ' . $expectedresponse . '<br \>');
			echo('expected response length: ' . strlen($expectedresponse) . '<br \>');
			echo('response (+5): ' . $response . '<br \>');
		}
		$response = trim($response);
		$trailing_response = $this->getTrailingSocketResponse();
		for($x = 0; $x < strlen($expectedresponse); $x++) {
			$newresponse .= $response[$x];
		}
		$response = $newresponse;
		if($this->debug) {
			echo('actual response: ' . $response . '<br \>');
		}
		if($response == $expectedresponse) {
			if($this->debug) {
				echo('(' . $response . ') - continuing<br \>');
			}
		} else {
			$this->Disruption->add(ERROR, 'Initial SMTP response unexpected: ' . $response . ' (trailing response: ' . $trailing_response . ')');
			return false;
		}
		if($this->debug) {
			echo('trailing response: ' . $trailing_response . '<br \>');
			echo('END: getInitialResponseFromInputToSocket(%SOCKET%, \'' . $expectedresponse . '\')<hr />');
		}
		return true;
	}
	
	/* 2006.11.28 - psyjoniz */
	function getResponseFromInputToSocket($string, $expectedresponse) {
		if($this->debug) {
			echo("<hr>BEGIN: getResponseFromInputToSocket()<br />");
			echo("string sent: <pre>" . htmlspecialchars($string) . "</pre><br>");
			echo("expected response: " . $expectedresponse . "<br>");
			echo("expected response length: " . strlen($expectedresponse) . "<br>");
		}
		fputs($this->smtp_sock, $string . "\r\n");
		$response = fgets($this->smtp_sock, (strlen($expectedresponse)+5));
		$response = trim($response);
		for($x = 0; $x < strlen($expectedresponse); $x++) {
			$newresponse .= $response[$x];
		}
		$response = $newresponse;
		if($this->debug) {
			echo("actual response: " . $response . "<br>");
		}
		if($response == $expectedresponse) {
			if($this->debug) {
				echo("(" . $response . ") - continuing<br>");
			}
		} else {
			$this->Disruption->add(WARNING, 'Socket response to input (' . $string . ') unexpected: ' . $response . ' (trailing response: ' . $this->getTrailingSocketResponse() . ')');
			return false;
		}
		$trailingresponse = $this->getTrailingSocketResponse();
		if($this->debug) {
			echo("trailing response: " . $trailingresponse . "<br>");
			echo("END: getResponseFromInputToSocket('" . $string . "', '" . $expectedresponse . "')<hr>");
		}
		return true;
	}

	/* 2006.11.28 - psyjoniz */
	function getTrailingSocketResponse() {
		//echo('getting trailing<br>');
		$return = fgets($this->smtp_sock, 100000);
		return $return;
	}
	//SOCKET
	//////////////////////////////////////////////////////////

	/* 2006.11.28 - psyjoniz */
	function cleanUp() {
		//clean up all the parts of an eMail that we wouldn't want laying around should the object be used again
		$this->smtp_headers  = array();
		$this->email_headers = false;
		$this->to_name       = false;
		$this->to_email      = false;
		$this->from_name     = false;
		$this->from_email    = false;
		$this->subject       = false;
		$this->body          = false;
		$this->template      = false;
		$this->rcpt_to       = array(); 
		$this->cc            = array(); 
		$this->bcc           = array(); 
		$this->img_parts     = array(); 
		$this->attach_parts  = array();
		return true;
	}
	
}
// and so ends another thing that did not, in any way, resemble what i thought it would end up being when i began - psy 
