<?php
/**
 * CoNtRol download batch result zip
 *
 * Looks for the requested batch results and sends them to user if found
 *
 * @author     Pete Donnell <pete-dot-donnell-at-port-dot-ac-dot-uk>
 * @copyright  2012-2014 University of Portsmouth & Kitson Consulting Limited
 * @license    https://gnu.org/licenses/gpl-3.0-standalone.html GPLv3 or later
 * @see        https://reaction-networks.net/control/documentation/
 * @package    CoNtRol
 * @created    19/07/2013
 * @modified   29/04/2014
 */

/**
 * Standard include
 */
require_once('includes/config.php');

if (isset($_GET['filekey']) and $_GET['filekey'])
{
	try
	{
		$controldb = new PDO(DB_STRING, DB_USER, DB_PASS, $db_options);
	}
	catch(PDOException $exception)
	{
		die('Unable to open database. Error: '.$exception.'. Please contact the system administrator at '.str_replace('@', ' at ', str_replace('.', ' dot ', ADMIN_EMAIL)).'.');
	}

	$query = 'SELECT id, status FROM '.DB_PREFIX.'batch_jobs WHERE filekey=:filekey';
	$statement = $controldb->prepare($query);
	$statement->bindParam(':filekey', $_GET['filekey'], PDO::PARAM_STR);
	$statement->execute();
	$results = $statement->fetchAll(PDO::FETCH_ASSOC);
	$number_of_results = count($results);
	switch ($number_of_results)
	{
		case 0:
			require_once('includes/header.php');
			echo '			<div id="results">
						<h2>Error</h2>
						<p>The key you requested could not be found. Please email the site admin at ';
			echo str_replace('@', ' at ', str_replace('.', ' dot ', ADMIN_EMAIL));
			echo ' if you are sure you have requested a valid key. <a href=".">Back to main page</a>.</p>
				</div><!-- results -->', PHP_EOL;
			require_once('includes/footer.php');
			break;
		case 1:
			if ($results[0]['status'] > 2)
			{
				require_once('includes/header.php');
	 			echo '			<div id="results">
					<h2>Error</h2>
					<p>The file you requested is no longer available. Files are removed after download. If you believe the file should still be available, please email the site admin at ';
				echo str_replace('@', ' at ', str_replace('.', ' dot ', ADMIN_EMAIL));
				echo '. <a href=".">Back to main page</a>.</p>
			</div><!-- results -->', PHP_EOL;
				require_once('includes/footer.php');
			}
			else
			{
				if (file_exists(TEMP_FILE_DIR.'/'.$_GET['filekey'].'.zip'))
				{
					header('Content-Type: application/zip');
					header('Content-Disposition: Attachment; filename=control_output.zip');
					readfile(TEMP_FILE_DIR.'/'.$_GET['filekey'].'.zip');
					$query = 'UPDATE '.DB_PREFIX.'batch_jobs SET status = 3, update_timestamp = :timestamp WHERE id = :id';
					$statement = $controldb->prepare($query);
					$statement->bindValue(':timestamp', date('Y-m-d H:i:s'), PDO::PARAM_STR);
					$statement->bindParam(':id', $results[0]['id'], PDO::PARAM_INT);
					$statement->execute();
				}
			}
			break;
		default:
			require_once('includes/header.php');
			echo '			<div id="results">
						<h2>Error</h2>
						<p>Multiple keys were found. Please email the site admin at ';
			echo str_replace('@', ' at ', str_replace('.', ' dot ', ADMIN_EMAIL));
			echo ' to report this error. <a href=".">Back to main page</a>.</p>
				</div><!-- results -->', PHP_EOL;
			require_once('includes/footer.php');
			break;
	}
}
