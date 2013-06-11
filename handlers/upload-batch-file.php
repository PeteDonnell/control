<?php
/**
 * CoNtRol reaction network batch file import
 *
 * Imports an uploaded archive file describing a set of reaction
 * networks, and stores them for later analysis.
 *
 * @author     Pete Donnell <pete dot donnell at port dot ac dot uk>
 * @copyright  University of Portsmouth, Kitson Consulting Limited 2012-2013
 * @license    https://gnu.org/licenses/gpl-3.0-standalone.html
 * @created    11/04/2013
 * @modified   11/06/2013
 */

require_once('../includes/config.php');
require_once('../includes/classes.php');
require_once('../includes/session.php');
//die(print_r($_POST,true).print_r($_FILES,true));

$_SESSION['errors'] = array();
$mimetype = '';
$filename = '';

if(isset($_FILES) and count($_FILES) and isset($_FILES['upload_batch_file_input']) and count($_FILES['upload_batch_file_input']) and isset($_POST['csrf_token']) and $_POST['csrf_token'] === $_SESSION['csrf_token'])
{
	switch($_FILES['upload_batch_file_input']['error'])
	{
		case UPLOAD_ERR_OK:
			$finfo = new finfo(FILEINFO_MIME_TYPE);
			if($finfo)
			{
				$mimetype = $finfo->file($_FILES['upload_batch_file_input']['tmp_name']);
				$allowed_mimetypes = array();
				foreach($supported_batch_file_types as $supported_batch_file_type) $allowed_mimetypes[] = $supported_batch_file_type['mimetype'];
				if(!in_array($mimetype, $allowed_mimetypes)) $_SESSION['errors'][] = 'Batch file format '.$mimetype.' not supported.';
				else
				{
					$filename = TEMP_FILE_DIR.end(explode('/', $_FILES['upload_batch_file_input']['tmp_name']));
					move_uploaded_file($_FILES['upload_batch_file_input']['tmp_name'], $filename);
				}
			}
			else
			{
				// Throw an exception?
				$_SESSION['errors'][] = 'Failed to open fileinfo database';
			}
			break;
		case UPLOAD_ERR_INI_SIZE:
			// fall through
		case UPLOAD_ERR_FORM_SIZE:
			$_SESSION['errors'][] = 'File too large';
			break;
		case UPLOAD_ERR_PARTIAL:
			$_SESSION['errors'][] = 'File only partially uploaded';
			break;
		case UPLOAD_ERR_NO_FILE:
			$_SESSION['errors'][] = 'No file uploaded';
			break;
		case UPLOAD_ERR_NO_TMP_DIR:
			$_SESSION['errors'][] = 'Temporary folder missing';
			break;
		case UPLOAD_ERR_CANT_WRITE:
			$_SESSION['errors'][] = 'Failed to write file to disk';
			break;
		case UPLOAD_ERR_EXTENSION:
			$_SESSION['errors'][] = 'Extension prevented file upload';
			break;
		default: // an unknown error occurred
			$_SESSION['errors'][] = 'Unknown error occurred';
			break;
	}
}
else $_SESSION['errors'][] = 'No file uploaded';

// Check that the file format was specified
if(!(isset($_POST['upload_batch_file_format']) and $_POST['upload_batch_file_format'])) $_SESSION['errors'][] = 'File format not specified';
else $_SESSION['upload_file_format'] = $_POST['upload_batch_file_format'];

// Check if a valid email address was submitted
$valid = true;
if(isset($_POST['upload_batch_file_email']) and trim($_POST['upload_batch_file_email']) and !strstr($_POST['upload_batch_file_email'], "\n"))
{
	$sender_address = trim($_POST['upload_batch_file_email']);
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
if(!$valid) $_SESSION['errors'][] = 'Invalid email address';
else $_SESSION['email'] = $_POST['upload_batch_file_email'];

if(!count($_SESSION['errors']))
{
	switch($_POST['upload_batch_file_format'])
	{
		case 'stoichiometry':
			$file_format = 1;
			$_SESSION['errors'][] = 'Warning: You uploaded a net stoichiometry file. The output will not be correct if any reactants appear on both sides of a reaction.';
			break;
		case 'stoichiometry+V':
			$file_format = 2;
			$_SESSION['errors'][] = 'Warning: You uploaded a net stoichiometry file. The output will not be correct if any reactants appear on both sides of a reaction.';
			break;
		case 'SourceTarget':
			$file_format = 4;
			break;

		// TO DO: Set warning message for S/T/V file format.
		default: // assume 'human' if unsure
			$file_format = 0;
			break;
	}

	// Attempt to open the database and throw an exception if unable to do so
	try
	{
		$controldb = new PDO(DB_STRING, DB_USER, DB_PASS, $db_options);
	}
	catch(PDOException $exception)
	{
		die('Unable to open database. Error: '.$exception.'. Please contact the system administrator at '.str_replace('@', ' at ', str_replace('.', ' dot ', ADMIN_EMAIL)).'.');
	}

	$statement = $controldb->prepare('INSERT INTO '. DB_PREFIX. 'batch_jobs (filename, file_format, email, status, detailed_output, mass_action_only, tests_enabled, error_text, remote_ip, remote_user_agent, creation_timestamp, update_timestamp) VALUES (:filename, :file_format, :email, :status, :detailed_output, :mass_action_only, :tests_enabled, :error_text, :remote_ip, :remote_user_agent, :creation_timestamp, :update_timestamp)');
	if(isset($_SESSION['detailed_output']) and $_SESSION['detailed_output']) $detailed_output = 1;
	else $detailed_output = 0;
	if(isset($_SESSION['mass_action_only']) and $_SESSION['mass_action_only']) $mass_action_only = 1;
	else $mass_action_only = 0;
	$tests_enabled= '';
	foreach($_SESSION['tests'] as $testname => $test)
	{
		if($test)
		{
			foreach($_SESSION['standard_tests'] as &$standardTest)
			if($testname === $standardTest->getShortName()) $standardTest->enableTest();
		}
		else
		{
			foreach($_SESSION['standard_tests'] as &$standardTest)
			if($testname === $standardTest->getShortName()) $standardTest->disableTest();
		}
	}
	for($i = 0; $i < count($_SESSION['standard_tests']); ++$i)
	{
		if($_SESSION['standard_tests'][$i]->getIsEnabled())
		{
			if($tests_enabled) $tests_enabled .= ';';
			$tests_enabled .= $_SESSION['standard_tests'][$i]->getShortName();
		}
	}
	$statement->bindParam(':filename', $filename, PDO::PARAM_STR);
	$statement->bindParam(':file_format', $file_format, PDO::PARAM_INT);
	$statement->bindValue(':email', trim($_POST['upload_batch_file_email']), PDO::PARAM_STR);
	$statement->bindValue(':status', 0, PDO::PARAM_INT);
	$statement->bindParam(':detailed_output', $detailed_output, PDO::PARAM_INT);
	$statement->bindParam(':mass_action_only', $mass_action_only, PDO::PARAM_INT);
	$statement->bindParam(':tests_enabled', $tests_enabled, PDO::PARAM_STR);
	$statement->bindValue(':error_text', '', PDO::PARAM_STR);
	$statement->bindParam(':remote_ip', $_SERVER['REMOTE_ADDR'], PDO::PARAM_STR);
	$statement->bindParam(':remote_user_agent', $_SERVER['HTTP_USER_AGENT'], PDO::PARAM_STR);
	$statement->bindValue(':creation_timestamp', date('Y-m-d H:i:s'), PDO::PARAM_STR);
	$statement->bindValue(':update_timestamp', date('Y-m-d H:i:s'), PDO::PARAM_STR);
	if (!$statement->execute()) die(print_r($statement->errorInfo(),true));
	$controldb = null;
}
else // There were errors, so redirect the user back to the main page so they can see them.
{
	header('Location: '.SITE_URL);
	die();
}

if(CRNDEBUG)
{
	echo '<pre>$_FILES:', CLIENT_LINE_ENDING;
	print_r($_FILES);
	echo CLIENT_LINE_ENDING, CLIENT_LINE_ENDING, '$mimetype:', CLIENT_LINE_ENDING;
	echo $mimetype;
	echo CLIENT_LINE_ENDING, CLIENT_LINE_ENDING, '$_SESSION:', CLIENT_LINE_ENDING;
	print_r($_SESSION);
	echo CLIENT_LINE_ENDING, '</pre>';
}
else
{
	header('Location: '.SITE_URL.'acknowledgement.php');
}
