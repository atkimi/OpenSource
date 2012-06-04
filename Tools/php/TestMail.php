<?php
//
//
//  _____________________
//
//  E N T R Y   P O I N T
//  _____________________
//
// Test for Dispatching a Mail Message (using local mail (postfix) server 
//
$MailSubject="PHP Mail Test";
$MailRecipient="mike.atkinson@nelr.co.uk";
$EmailAddress="mike.atkinson@quitenear.me";
$MailMessage = <<<Az
Hello Mike
This is a test mail with an attachment

Az;
if( $argc == 2) {$AttachmentFile=$argv[1];} else { $AttachmentFile="";}
$Date=strftime("%a, %e %b %Y %R %z", time()); 
$HtmlMailMessage=nl2br($MailMessage);
$PhpVersion=phpversion();
//
// Constuct Headers and Body for an attachmrnt
//
$FullMailMessage= <<<Az
<html>
<head>
  <title>Message from the North East London Ramblers</title>
</head>
<body style=\"font-family:Verdana, Verdana, Geneva, sans-serif; font-size:14px; color:#333;\">\n
$HtmlMailMessage<br /> 
<br />
Full details of our Events may be found <a href="http://www.nelr.co.uk/">Here</a><br /><br />
Any Tube disruptions this weekend can be found <a href="http://www.tfl.gov.uk/tfl/livetravelnews/realtime/tube/tube-all-weekend.html">Here</a><br />
<br />
<hr />
<br />
If you no longer wish to receive these emails please click <a href="mailto:mike.atkinson@nelr.co.uk?subject=Remove%20Me">here</a><br />
</body>
</html>

Az;
$Headers= <<<Az
MIME-Version: 1.0
Content-type: text/html; charset=iso-8859-1
From: $EmailAddress 
X-Mailer: PHP/$PhpVersion
 
Az;
//
if ( file_exists($AttachmentFile))
{
	$Boundary = '_---=' . md5( uniqid ( rand() ) );
	$Multipart="Content-Type: multipart/mixed; boundary=\"" . $Boundary . "\"";
	$Boundary="--" .$Boundary;
	$BaseFilename=basename($AttachmentFile);
	$TempArray=explode(".",$BaseFilename);
	if ( ($aCount=count($TempArray)) == 1 )
	{
		$Extension="txt";
	}
	else
	{
		$Extension=$TempArray[$aCount-1];

	}
	$Multi="This is a multi-part message in MIME format.";

	$FileContents=file_get_contents($AttachmentFile);
	$EncodedFileContents=chunk_split(base64_encode($FileContents));
	$EncodedContents = <<<Az

$Boundary
Content-Transfer-Encoding: base64
Content-Type: application/$Extension; name="$BaseFilename"

$EncodedFileContents

$Boundary

Az;
//
// Extend the Header and Body for a multipart message ...
//
	$Headers = <<<Az
$Multipart
$Headers
$Multi

$Boundary
Content-type: text/html; charset=iso-8859-1
Az;
	$FullMailMessage .= <<<Az

$EncodedContents

Az;
}
if ( mail  ( $MailRecipient , $MailSubject  , $FullMailMessage , $Headers) == TRUE )
{
	print("\nMail sent to Recipient $MailRecipient OK\n");
}
else
{
	print("\nFailed to send mail to $MailRecipient\n");
}
exit(0);
?>
