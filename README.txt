Code Sample : eMail

This is a rich featured email class with support to use PHP's mail() function
or talk directly with an SMTP server willing to do transport along with support
for HTML and encoding images/resources into the body of an email.

Examples of Use :

<?php

include_once('eMail.class.php');

//sending a very simple eMail in one shot
if(false !== $eMail_obj = new eMail('to@domain.com', 'from@domain.com', 'subject', 'body', true)) { echo('email should have sent and returned true'); }

//setting up the object then manually building the eMails' parts and telling the object to send
$eMail = new eMail();
$eMail->setTo('to@domain.com', 'To Name'); //can be called setTo('email');
$eMail->addRecipient('recipient1@domain.com', 'CC', 'Recipient 1 Name'); //can be called addRecipient('email', 'rcp_type') or just addRecipient('email')
$eMail->addRecipient('recipient2@domain.com', 'BCC', 'Recipient 2 Name');
$eMail->setFrom('from@domain.com', 'From Name');
$eMail->setSubject('This is a test email.');
$eMail->setBody('a test of html, image extraction and embedding those images: <img src="http://www.domain.com/path/to/a/funny.gif">');
$eMail->addAttachment('http://www.domain.com/path/to/a/relevant.doc');
$eMail->send();

//sending multiple eMails with one object first by manually building the eMail then by using the start function to kick off a simplistic second eMail
$eMail = new eMail();
$eMail->setTo('to@domain.com', 'To Name'); //can be called setTo('email');
$eMail->addRecipient('recipient1@domain.com', 'CC', 'Recipient 1 Name'); //can be called addRecipient('email', 'rcp_type') or just addRecipient('email')
$eMail->addRecipient('recipient2@domain.com', 'BCC', 'Recipient 2 Name');
$eMail->setFrom('from@domain.com', 'From Name');
$eMail->setSubject('This is a test email.');
$eMail->setBody('a test of html, image extraction and embedding those images: <img src="http://www.domain.com/path/to/a/funny.gif">');
$eMail->addAttachment('http://www.domain.com/path/to/a/relevant.doc');
$eMail->send();
$eMail->start('to@domain.com', 'from@domain.com', 'subject', 'body');
$eMail->addAddachment('/local/path/to/a/relevant.doc');
$eMail->send();
