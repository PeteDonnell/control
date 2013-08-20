<?php
/**
 * CoNtRol reaction network file import
 *
 * Imports an uploaded text file describing the reaction
 * network, and attempts to analyse it.
 *
 * @author     Pete Donnell <pete dot donnell at port dot ac dot uk>
 * @copyright  University of Portsmouth, Kitson Consulting Limited 2012-2013
 * @license    https://gnu.org/licenses/gpl-3.0-standalone.html
 * @created    10/10/2012
 * @modified   07/08/2013
 */

require_once('../includes/config.php');
require_once('../includes/classes.php');
require_once('../includes/session.php');
require_once('../includes/functions.php');

$errors = array();
$mimetype = '';

if(isset($_FILES) and count($_FILES) and isset($_FILES['upload_network_file_input']) and count($_FILES['upload_network_file_input']) and isset($_POST['csrf_token']) and $_POST['csrf_token'] === $_SESSION['csrf_token'])
{
	switch($_FILES['upload_network_file_input']['error'])
	{
		case UPLOAD_ERR_OK:
			//$finfo->close();
			break;
		case UPLOAD_ERR_INI_SIZE:
			// fall through
		case UPLOAD_ERR_FORM_SIZE:
			$errors[] = 'File too large';
			break;
		case UPLOAD_ERR_PARTIAL:
			$errors[] = 'File only partially uploaded';
			break;
		case UPLOAD_ERR_NO_FILE:
			$errors[] = 'No file uploaded';
			break;
		case UPLOAD_ERR_NO_TMP_DIR:
			$errors[] = 'Temporary folder missing';
			break;
		case UPLOAD_ERR_CANT_WRITE:
			$errors[] = 'Failed to write file to disk';
			break;
		case UPLOAD_ERR_EXTENSION:
			$errors[] = 'Extension prevented file upload';
			break;
		default: // an unknown error occurred
			$errors[] = 'Unknown error occurred';
			break;
	}
}
else $errors[] = 'No file uploaded';
if(!(isset($_POST['upload_network_file_format']) and $_POST['upload_network_file_format'])) $errors[] = 'File format not specified';

if(!count($errors))
{
	unset($_SESSION['errors']);
	$_SESSION['upload_file_format'] = $_POST['upload_network_file_format'];
	$reaction_network = new ReactionNetwork();
	$fhandle = fopen($_FILES['upload_network_file_input']['tmp_name'], 'r');
	switch($_POST['upload_network_file_format'])
	{
		case 'stoichiometry':
			if(check_file_format($_FILES['upload_network_file_input']['tmp_name'], 'text/plain'))
			{
				$matrix = array();
				$_SESSION['errors'][] = 'Warning: You uploaded a stoichiometry file. The output below will not be correct if any reactants appear on both sides of a reaction.';
				while(!feof($fhandle))
				{
					$row = trim(preg_replace('/\s+/', ' ', fgets($fhandle)));
					if($row and strpos($row, '#') !== 0) $matrix[] = explode(' ', $row);
				}
				if(!$reaction_network->parseStoichiometry($matrix)) $_SESSION['errors'][] = 'An error was detected in the stoichiometry file. Please check that the output below is as expected.';
			}
			break;
			
		case 'source_target':
			if(check_file_format($_FILES['upload_network_file_input']['tmp_name'], 'text/plain'))
			{
				$sourceMatrix = array();
				$targetMatrix = array();
				$row = '';
				while (!feof($fhandle) and mb_strtoupper(trim($row)) !== 'S MATRIX')
				{
					$row = fgets($fhandle);
					//error_log($row."\n",3,'/var/tmp/crn.log');
				}
	
				while(!feof($fhandle) and mb_strtoupper($row) !== 'T MATRIX')
				{
					$row = trim(preg_replace('/\s+/', ' ', fgets($fhandle)));
					if($row and strpos($row, '#') !== 0 and mb_strtoupper($row)!=='T MATRIX') $sourceMatrix[] = explode(' ', $row);
					//error_log($row."\n",3,'/var/tmp/crn.log');
				}
				while(!feof($fhandle))
				{
					$row = trim(preg_replace('/\s+/', ' ', fgets($fhandle)));
					if($row and strpos($row, '#') !== 0) $targetMatrix[] = explode(' ', $row);
					//error_log($row."\n",3,'/var/tmp/crn.log');
				}
				if(!$reaction_network->parseSourceTargetStoichiometry($sourceMatrix, $targetMatrix))
				{
					$_SESSION['errors'][] = 'An error was detected in the stoichiometry file. Please check that the output below is as expected.';
					//error_log(print_r($sourceMatrix, true), 3, '/var/tmp/crn.log');
					//error_log(print_r($targetMatrix, true), 3, '/var/tmp/crn.log');
				}
			}
			break;

		case 'sv':
			if(check_file_format($_FILES['upload_network_file_input']['tmp_name'], 'text/plain'))
			{
				$file = array();
				while (!feof($fhandle))
				{
					$row = trim(fgets($fhandle));
					if ($row and strpos($row, '#') !== 0)
					{
						// TODO: Implement				
					}			
				}
			}
			break;
			
		case 'feinberg1':
			if(check_file_format($_FILES['upload_network_file_input']['tmp_name'], 'text/plain'))
			{
				$file = array();
				while (!feof($fhandle))
				{
					$row = trim(fgets($fhandle));
					if ($row and strpos($row, '#') !== 0)
					{
						// TODO: Implement				
					}			
				}
			}
			break;
			
		case 'feinberg2':
			if(check_file_format($_FILES['upload_network_file_input']['tmp_name'], 'text/plain'))
			{
				$file = array();
				while (!feof($fhandle))
				{
					$row = trim(fgets($fhandle));
					if ($row and strpos($row, '#') !== 0)
					{
						// TODO: Implement				
					}			
				}
			}
			break;

		case 'stv':
			if(check_file_format($_FILES['upload_network_file_input']['tmp_name'], 'text/plain'))
			{
				$file = array();
				while (!feof($fhandle))
				{
					$row = trim(fgets($fhandle));
					if($row and strpos($row, '#') !== 0)
					{
						//TO DO: Implement this import
					}
				}
			}
			break;
			
		case 'sauro':
			if(check_file_format($_FILES['upload_network_file_input']['tmp_name'], 'text/plain'))
			{
				$row = trim(preg_replace('/\s+/', ' ', fgets($fhandle)));
				while(!feof($fhandle))
				{
					if($row and strpos($row, '#') !== 0) break;
					else $row = trim(preg_replace('/\s+/', ' ', fgets($fhandle)));
				}
				if(!$reaction_network->parseSauro($row))
				{
					$_SESSION['errors'][] = 'An error was detected in the sauro file. Please check that the output below is as expected.';
					//error_log(print_r($sourceMatrix, true), 3, '/var/tmp/crn.log');
					//error_log(print_r($targetMatrix, true), 3, '/var/tmp/crn.log');
				}
			}
			break;
			
		case 'sbml':
			if(check_file_format($_FILES['upload_network_file_input']['tmp_name'], 'application/xml'))
			{
				if (!$reaction_network->parseSBML($_FILES['upload_network_file_input']['tmp_name']))
				{
					$_SESSION['errors'][] = 'An error occurred while parsing the SBML file. Please check that the output below is as expected.';
				}
			}
			break;
			
		default: // assume 'human' if unsure
			if(check_file_format($_FILES['upload_network_file_input']['tmp_name'], 'text/plain'))
			{
				$error = false;
				while(!feof($fhandle))
				{
					$reactionString = fgets($fhandle);
					if($reactionString and strpos($reactionString, '#') !== 0)
					{
						$newReaction = Reaction::parseReaction($reactionString);
						if($newReaction) $reaction_network->addReaction($newReaction);
						elseif(!$error)
						{
							$_SESSION['errors'][] = 'An error occurred while adding a reaction from the file. Please check that the output below is as expected.';
							$error = true;
						}
					}
				}
			}
			break;
	}
	fclose($fhandle);
	$_SESSION['reaction_network'] = $reaction_network;
}

if(CRNDEBUG)
{
	echo '<pre>$_FILES:', CLIENT_LINE_ENDING;
	print_r($_FILES);
	echo CLIENT_LINE_ENDING, CLIENT_LINE_ENDING, '$errors:', CLIENT_LINE_ENDING;
	print_r($errors);
	echo CLIENT_LINE_ENDING, CLIENT_LINE_ENDING, '$mimetype:', CLIENT_LINE_ENDING;
	echo $mimetype;
	echo CLIENT_LINE_ENDING, CLIENT_LINE_ENDING, '$_SESSION:', CLIENT_LINE_ENDING;
	print_r($_SESSION);
	echo CLIENT_LINE_ENDING, '</pre>';
}
else
{
	header('Location: '.SITE_URL);
}
