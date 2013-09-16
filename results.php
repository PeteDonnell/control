<?php
/**
 * CoNtRol results page
 *
 * This is the results page for CoNtRol
 *
 * @author     Pete Donnell <pete dot donnell at port dot ac dot uk>
 * @copyright  University of Portsmouth, Kitson Consulting Limited 2012-2013
 * @license    https://gnu.org/licenses/gpl-3.0-standalone.html
 * @created    01/10/2012
 * @modified   30/05/2013
 */

require_once('includes/header.php');

if(!isset($_SESSION['test_output'])) die('No test results found.');

echo '				<div id="results">
						<h2>Test Results</h2>
						<p>Jump to test:', PHP_EOL;

foreach($_SESSION['tests'] as $testname => $test)
{
	if($test)
	{
		foreach($_SESSION['standard_tests'] as &$standardTest)
		if ($testname === $standardTest->getShortName())
		{
			echo '							<a href="', $_SERVER['PHP_SELF'], '#test_', $standardTest->getShortName(), '" title="jump to results for ', sanitise($standardTest->getLongName()), '">', sanitise($standardTest->getLongName()), "</a>\n" ;
		}
	}
}
?>
							<span class="align_right"><a href=".">Back to main</a></span>
						</p>
						<div>
							<h3>Reaction Network Tested:</h3>
							<p>
<?php
echo $_SESSION['reaction_network']->exportAsHTML();
echo "							</p>
						</div><!-- reaction_network -->\n";
$currentTest = 0;
foreach($_SESSION['test_output'] as $name => $result)
{
	++$currentTest;
	echo '						<div id="test_', $name, '">', PHP_EOL;
	foreach($_SESSION['standard_tests'] as &$standardTest)
	if ($name === $standardTest->getShortName())
	{
		echo '							<h3>Test ', $currentTest, ': ', sanitise($standardTest->getLongName()), "</h3>\n" ;
		echo '<p>', $standardTest->getDescription(), "</p>\n";
	}
	echo "							<h4>Results:</h4>\n";
	if(trim($result)) echo "<pre>$result</pre>\n						</div>\n";
	else echo "							<pre>No results available, probably due to test timeout.</pre>\n						</div>\n";
}
?>
					<p id="results_actions_buttons">							
						<a class="button fancybox<?php if(!isset($_SESSION['reaction_network']) or !$_SESSION['reaction_network']->getNumberOfReactions()) echo ' disabled'; ?>" href="#missing_java_warning_holder" id="dsr_graph_button" title="Generate and display the DSR graph for the current CRN (note: requires Java)">View CRN DSR Graph</a>
						<a class="button fancybox<?php if(!isset($_SESSION['reaction_network']) or !$_SESSION['reaction_network']->getNumberOfReactions()) echo ' disabled'; ?>" href="#email_results_form" id="email_results_form_button" title="Receive the test results for the current CRN via email">Email results</a>
					</p>
				</div><!-- results -->
				<div id="popup_hider">
					<div id="missing_java_warning_holder">
<?php
if(strpos($_SERVER['HTTP_USER_AGENT'], 'Android') !== FALSE or strpos($_SERVER['HTTP_USER_AGENT'], 'iOS') !== FALSE ) echo "						<p>The DSR graph requires Java to view, which is not available on your system.</p>\n";
else echo '						<p>The DSR graph requires Java to view, which is not installed on your system. Please <a href="http://java.com/">download Java</a> to enable this functionality.</p>', PHP_EOL;
?>
					</div><!-- missing_java_warning_holder -->
					<form id="email_results_form" action="handlers/mail-results.php" method="post" enctype="multipart/form-data" class="left_centred">
						<p>
							<input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>" />
							<label for="results_email">Email address for results:</label>
							<input type="text" id="results_email" name="email" size="48" <?php if(isset($_SESSION['email'])) echo 'value="', sanitise($_SESSION['email']), '" '; ?>/><br />
							<span id="email_results_error">&nbsp;</span>
						</p>
						<p>
							<button class="button disabled" id="email_results_button" type="submit" disabled="disabled">Send results</button>
						</p>
					</form><!-- email_results_form -->
				</div><!-- popup_hider -->
<?php
require_once('includes/footer.php');
