<?php
/**
 * CoNtRol reaction network results mailer
 *
 * Sends completed results to the specified email address.
 *
 * @author     Pete Donnell <pete-dot-donnell-at-port-dot-ac-dot-uk>
 * @copyright  2012-2014 University of Portsmouth & Kitson Consulting Limited
 * @license    https://gnu.org/licenses/gpl-3.0-standalone.html GPLv3 or later
 * @see        https://reaction-networks.net/control/documentation/
 * @package    CoNtRol
 * @created    08/05/2013
 * @modified   29/04/2014
 */

/**
 * Standard include
 */
require_once('../includes/config.php');

/**
 * Standard include
 */
require_once('../includes/classes.php');

/**
 * Standard include
 */
require_once('../includes/functions.php');

/**
 * Standard include
 */
require_once('../includes/session.php');

/**
 * Standard include
 */
require_once('../includes/standard-tests.php');

/**
 * Standard include
 */
require_once('../includes/version.php');

if(isset($_POST['csrf_token']) and $_POST['csrf_token'] === $_SESSION['csrf_token'])
{
	// Check if a valid email address was submitted
	$valid = true;
	if(isset($_POST['email']) and trim($_POST['email']) and !strstr($_POST['email'], "\n"))
	{
		$sender_address = trim($_POST['email']);
		if(!strpos($sender_address, '@')) $valid = false;
		else
		{
			list($sender_username, $sender_domain) = explode('@', $sender_address);
			if(!(strpos($sender_domain, '.'))) $valid = false;
			elseif(function_exists('checkdnsrr'))
			{
				if(!(checkdnsrr($sender_domain.'.', 'MX') or checkdnsrr($sender_domain.'.', 'A'))) $valid = false;
			}
		}
	}
	else $valid = false;
	if($valid) $_SESSION['email'] = $_POST['email'];
	else die('Invalid email address');

	$mail = "<h1>CoNtRol Output</h1>\r\n";
	$mail .= "==============\r\n\r\n";
	$mail .= "<p style=\"margin-left:16px;font-style:italic;\">Version: ".CONTROL_VERSION."<br />\r\n";

	$mail .= "Tests enabled:";
	foreach($_SESSION['test_output'] as $test => $result) $mail .= " $test";

	$mail .= "<br />\r\nDetailed test output: ";
	$detailed_output = false;
	if(isset($_SESSION['detailed_output']) and $_SESSION['detailed_output'] == 1)
	{
		$detailed_output = true;
		$mail .= 'True';
	}
	else $mail .= 'False';

	$mail .= "<br />\r\nMass action only: ";
	$mass_action_only = false;
	if(isset($_SESSION['mass_action_only']) and $_SESSION['mass_action_only'] == true) $mail .= "True";
	else $mail .= "False";

	$mail .= "<br />\r\nDownload request time: ".date('Y-m-d H:i:s')."</p>\r\n\r\n";

	$mail .= "\r\n<h2>Reaction network:</h2>\r\n<pre style=\"font-weight:bolder;font-size:150%;margin-left:16px;\">\r\n";
	$mail .= $_SESSION['reaction_network']->exportReactionNetworkEquations("\r\n");
	$mail .= "</pre>\r\n\r\n\r\n";

	foreach($_SESSION['test_output'] as $test => $result)
	{
		$mail .= "\r\n<h3>TEST: ".$test."</h3>\r\n\r\n";
		$mail .= '<pre style="margin-left:16px;white-space:pre-wrap;">';
		if(trim($result)) $mail .= "\r\n$result";
		else $mail .= "\r\nNo results available, probably due to test timeout.";
		$mail .= "\r\n</pre>";
		$mail .= "\r\n\r\n### END OF TEST: ".$test." ###\r\n\r\n\r\n\r\n";
	}

	$mail .= "\r\n<p>-- <br />\r\nThis message was automatically generated by <a href=\"".SITE_URL."\">CoNtRol</a>. It was sent to you at the request of someone at IP address ".$_SERVER['REMOTE_ADDR'].". If this was not you, please delete this email. Queries should be addressed to ".ADMIN_EMAIL.".</p>\r\n";

	// Set email headers.
	$admin_email_split = explode('@', ADMIN_EMAIL);
	$boundary = hash("sha256", uniqid(time()));
	$extra_headers =  "From: CoNtRol <".ADMIN_EMAIL.">\r\n";
	$extra_headers .= "MIME-Version: 1.0\r\n";
	$extra_headers .= "Content-Type: multipart/alternative;\r\n boundary=\"$boundary\"\r\n";
	$extra_headers .= "Message-ID: <".time().'-'.substr(hash('sha512', ADMIN_EMAIL.$_POST['email']), -10).'@'.end($admin_email_split).">\r\n";
	$extra_headers .= 'X-Originating-IP: ['.$_SERVER['REMOTE_ADDR']."]\r\n";
	$sendmail_params = '-f'.ADMIN_EMAIL;

	// Create plain text version of email
	$body = "--$boundary\r\n";
	$body .= "Content-Type: text/plain; charset=utf-8;\r\n format=flowed\r\n";
	$body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
	// Remove HTML tags and replace links with bare URLs
	$plain_text_search = array('<br />', '<h1>', '<h2>', '<h3>', '<p>', '<pre>', '<span>', '</h1>', '</h2>', '</h3>', '</p>', '</pre>', '</span>');
	$plain_text_replace = array('', '', '## ', '### ', '', '', '', '', ' ##', ' ###', '', '', '');
	$body .= str_replace($plain_text_search, $plain_text_replace, preg_replace('@( style=")(.*?)(")@', '', convert_links_to_plain_text($mail)));

	// Create HTML version of email
	$body .= "\r\n\r\n--$boundary\r\n";
	$body .= "Content-Type: text/html; charset=utf-8;\r\n";
	$body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
	// Set HTML headers
	$body .= '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">'."\r\n".'<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">'."\r\n<head>\r\n".'<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />'."\r\n<title>CoNtRol output</title>\r\n</head>\r\n<body>\r\n";
	// Remove problematic plain text code and replace admin email with link
	$html_search = array('<-->', '<--', '-->', "\r\n==============\r\n\r\n");
//	$html_replace = array('&lt;--&gt;', '&lt;--', '--&gt;', "\r\n");
	$html_replace = array('&#8652;', '&larr;', '&rarr;', "\r\n");
	$body .= str_replace(ADMIN_EMAIL, '<a href="mailto:'.ADMIN_EMAIL.'">'.ADMIN_EMAIL.'</a>', str_replace($html_search, $html_replace, preg_replace('@(\#\#\# END OF TEST: )(.*?)( \#\#\#)@', '', $mail)));
	// Close HTML
	$body .= "</body>\r\n</html>\r\n\r\n--$boundary--\r\n";

	if(!mail('<'.trim($_POST['email']).'>', 'CoNtRol Results', $body, $extra_headers, $sendmail_params)) die('Failed to send email');
	else die('Mail sent successfully');
}
else die('CSRF attempt detected');
