<?php
/**
 * CoNtRol reaction network test handler
 *
 * For the current reaction network, runs each enabled test in turn.
 *
 * @author     Pete Donnell <pete dot donnell at port dot ac dot uk>
 * @copyright  University of Portsmouth, Kitson Consulting Limited 2012-13
 * @license    https://gnu.org/licenses/gpl-3.0-standalone.html
 * @created    08/10/2012
 * @modified   28/05/2013
 */

require_once('../includes/config.php');
require_once('../includes/classes.php');
require_once('../includes/functions.php');
require_once('../includes/session.php');
require_once('../includes/standard-tests.php');

/**
 * Warn on timeout
 */
function timeout_error_message()
{
	$last_error = error_get_last();
	if(connection_status() === CONNECTION_TIMEOUT) echo '<p>Test ',$_SESSION['current_test'],' of ',$_SESSION['number_of_tests'], ' failed due to timeout</p>';
	elseif($last_error === E_ERROR) echo '<p>Test ',$_SESSION['current_test'],' of ',$_SESSION['number_of_tests'], ' failed due to error '.$last_error['message'].'</p>';
}

register_shutdown_function('timeout_error_message');

if(isset($_SESSION['reaction_network']) and isset($_POST['csrf_token']) and $_POST['csrf_token'] === $_SESSION['csrf_token'])
{
	$currentTest = null;

	for($i = 0; $i < count($_SESSION['standard_tests']); ++$i)
	{
		if($_SESSION['standard_tests'][$i]->getIsEnabled())
		{
			$_SESSION['standard_tests'][$i]->disableTest();
			$currentTest = $_SESSION['standard_tests'][$i];
			++$_SESSION['current_test'];
			break;
		}
	}

	if($currentTest)
	{
		$extension = '';
		$temp = '';

		// Need to split this into net stoichiometry versus source/target stoichiometry?
		// How best to treat reversible vs irreversible reactions in stoichiometry case?
		if(in_array('stoichiometry', $currentTest->getInputFileFormats())) $extension = '.sto';
		if(in_array('stoichiometry+V', $currentTest->getInputFileFormats())) $extension = '.s+v';
		if(in_array('S+T+V', $currentTest->getInputFileFormats())) $extension = '.stv';
		if(in_array('human', $currentTest->getInputFileFormats())) $extension = '.hmn';

		if(!$extension) $temp = 'This test does not support any valid file formats. Test aborted.';
		else
		{
			$filename = $_SESSION['tempfile'].$extension;
			$exec_string = 'cd '.BINARY_FILE_DIR.' && '.NICENESS.'./'.$currentTest->getExecutableName();
			$output = array();
			$returnValue = 0;
		    //  $exec_string = NICENESS.$binary;
			if(isset($_SESSION['mass_action_only']) and $_SESSION['mass_action_only'])
			{
				if($currentTest->supportsMassAction()) $exec_string .= ' --mass-action-only';
				else $temp = "WARNING: you requested testing mass-action kinetics only, but this test always tests general kinetics.\n";
			}
			else
			{
				if(!$currentTest->supportsGeneralKinetics()) $temp = "WARNING: you requested testing general kinetics, but this test only supports mass-action kinetics.\n";
			}
			$exec_string .= ' '.$filename;
			if(isset($_SESSION['detailed_output']) and $_SESSION['detailed_output']) $exec_string .= ' 2>&1';
			exec($exec_string, $output, $returnValue);
			foreach($output as $line) $temp .= "\n$line";
		}

		$_SESSION['test_output'][$currentTest->getShortName()] = $temp;
		echo '<p>Completed test ',$_SESSION['current_test'],' of ',$_SESSION['number_of_tests'], '.</p>';
	}

	else
	{
		// Re-enable tests
		//for($i = 0; $i < count($_SESSION['standard_tests']); ++$i)
		//	{
			//$_SESSION['standard_tests'][$i]->enableTest();
		//}
		// Delete temporary files
		array_map('unlink', glob($_SESSION['tempfile'].'*'));
		echo '<p>All tests completed. Redirecting to results.</p>';
	}
}
else
{
	//error_log('CSRF failed in process-tests'.PHP_EOL, 3, '/var/tmp/crn.log');
	die('<p>Error: CSRF detected or CRN not set up.</p>');
}
