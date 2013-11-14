Code Sample : eMail

This is a full-featured email class with support to use PHP's mail() function
or talk directly with an SMTP server willing to do transport and full support
for HTML and encoding images/resources into the body of an email.

Examples of Use :

1) sending a very simple eMail in one shot :

if(false !== $eMail_obj = new eMail('jesse@psy-core.com', 'geoff@plan8studios.com', 'hey, whats up?', 'this is a test email!! -geoff', true)) { echo('email should have sent and returned true'); }

2) setting up the object then manually building the eMails parts and telling
the object to send :

$eMail =& Core::getObj('eMail');
$eMail->setTo('jesse@psy-core.com', 'Jesse Quattlebaum'); //can be called setTo('email');
$eMail->addRecipient('psyjoniz@gmail.com',    'CC',  'Jesse Quattlebaum'); //can be called addRecipient('email', 'rcp_type') or just addRecipient('email')
$eMail->addRecipient('psyjoniz@psyjoniz.com', 'BCC', 'Jesse Quattlebaum');
$eMail->setFrom('AutoMailer@psy-core.com', 'psy-core AutoMailer');
$eMail->setSubject('This is a test email.');
$eMail->setBody('a test of html, image extraction and embedding those images: <img src="http://www.psyjoniz.com/.images/photography_icon.gif"> (should be a camera icon)');
$eMail->addAttachment('http://www.psyjoniz.com/.images/NLightEnd_finished.jpg');
$eMail->send();

3) sending multiple eMails with one object first by manually building the
eMail then by using the start function to kick off a simplistic second eMail :

$eMail =& Core::getObj('eMail');
$eMail->setTo('jesse@psy-core.com', 'Jesse Quattlebaum'); //can be called setTo('email');
$eMail->addRecipient('psyjoniz@gmail.com',    'CC',  'Jesse Quattlebaum'); //can be called addRecipient('email', 'rcp_type') or just addRecipient('email')
$eMail->addRecipient('psyjoniz@psyjoniz.com', 'BCC', 'Jesse Quattlebaum');
$eMail->setFrom('AutoMailer@psy-core.com', 'psy-core AutoMailer');
$eMail->setSubject('This is a test email.');
$eMail->setBody('a test of html, image extraction and embedding those images: <img src="http://www.psyjoniz.com/.images/photography_icon.gif"> (should be a camera icon)');
$eMail->addAttachment('http://www.psyjoniz.com/.images/NLightEnd_finished.jpg');
$eMail->send();
$eMail->start('to_email@wherever.com', 'from_email@somewhere.net', 'the subject', 'and body of email');
$eMail->addAddachment('/file/on/server');
$eMail->send();
