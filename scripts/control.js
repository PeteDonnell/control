/**
 * Main CoNtRol JavaScript file
 *
 * Written in PHP to simplify colours, etc.
 *
 * @author     Pete Donnell <pete dot donnell at port dot ac dot uk>
 * @copyright  University of Portsmouth 2012
 * @license    https://gnu.org/licenses/gpl-3.0-standalone.html
 * @created    01/10/2012
 * @modified   10/10/2012
 */

/**
 * Adds a row to the reaction input form
 */
function addReaction()
{
	$('#reaction_input_submit_buttons').before('<fieldset class="reaction_input_row"> <input type="text" size="32" maxlength="128" class="reaction_left_hand_side" name="reaction_left_hand_side[]" /> <select class="reaction_direction" name="reaction_direction[]"><option value="left">&larr;</option><option value="both" selected="selected">&#x21cc;</option><option value="right">&rarr;</option></select> <input type="text" size="32" maxlength="128" class="reaction_right_hand_side" name="reaction_right_hand_side[]" /> </fieldset>');

	$('select.reaction_direction').each(function()
	{
		$(this).change(function()
		{
			enableButtons();
		});
	});

	$('.reaction_left_hand_side').each(function()
	{
		$(this).keyup(function()
		{
			enableButtons();
		});
	});

	$('.reaction_right_hand_side').each(function()
	{
		$(this).keyup(function()
		{
			enableButtons();
		});
	});
}

/**
 * Removes a row from the reaction input form
 *
 * N.B. This function does NOT check whether there is only one reaction left.
 * Consequently, calling it when there is only one reaction left will result
 * no reactions being left. Calling it again may trigger a JavaScript error
 * in the user's browser.
 */
function removeReaction()
{
	$('#reaction_input_form fieldset').filter(':last').remove();
}

/**
 * Resets all reactions in the input form
 *
 * Does not change the number of reactions in the input form, just clears
 * each reaction's LHS and RHS, and sets the direction to reversible. It
 * also disables the various reaction network processing buttons.
 */
function resetReactions()
{
	$('#reaction_input_form fieldset input').val('');
	$('#reaction_input_form fieldset select option[value=both]').attr('selected', true);
	disableButtons();
}

/*
 * Disables reaction reset, DSR graph and analysis buttons
 */
function disableButtons()
{
	$('#reset_reaction_button').addClass('disabled');
	$('#dsr_graph_button').addClass('disabled');
	$('#process_network_button').addClass('disabled');
	$('#download_network_file_button').addClass('disabled');
}

/*
 * Enables reaction reset, DSR graph and analysis buttons
 */
function enableButtons()
{
	// Remove reaction button doesn't need to be enabled, as it is automatically enabled/disabled based on the number of reactions
	//if($('#reaction_input_form > fieldset').length > 1) $('#remove_reaction_button').removeClass('disabled');
	$('#reset_reaction_button').removeClass('disabled');
	$('#dsr_graph_button').removeClass('disabled');
	$('#process_network_button').removeClass('disabled');
	$('#download_network_file_button').removeClass('disabled');
	$('#download_network_file_button').removeAttr('disabled');
}

function testSSDonly()
{
	var url = 'handlers/ssd-test.php';
	$.get(url, null, function(returndata) {showTestOutput(returndata);});
}

function showTestOutput(output)
{
	$('#calculation_output_holder').append(output);
}

function resetPopup()
{
	$('#calculation_output_holder').html('<p>Processing...<span class="blink">_</span></p>');
}

var validNetwork=true;
function saveNetwork()
{
	validNetwork=true;
	var url = 'handlers/process-network.php';
	var reactionsLeftHandSide = new Array();
	var temp = $('.reaction_left_hand_side');
	for (i=0;i<temp.length;++i) reactionsLeftHandSide.push(temp.val()); 		
  var reactionsRightHandSide = new Array();
	temp = $('.reaction_right_hand_side');
	for (i=0;i<temp.length;++i) reactionsRightHandSide.push(temp.val()); 	
  var reactionsDirection = new Array();
	temp = $('.reaction_direction :selected');
	for (i=0;i<temp.length;++i) reactionsDirection.push(temp.val()); 	
	$.post(url, {'reaction_left_hand_side[]':reactionsLeftHandSide, 'reaction_right_hand_side[]':reactionsRightHandSide, 'reaction_direction[]':reactionsDirection}, function(returndata) {if (returndata.length) {showTestOutput('<p>' + returndata + '</p>');validNetwork=false;}});
	return validNetwork;
}

$(document).ready(function()
{
	
	$('#add_reaction_button').click(function()
	{
		addReaction();
		$('#remove_reaction_button').removeClass('disabled');
		//$(this).preventDefault();
		return false;
	});

	$('#remove_reaction_button').click(function()
	{
		if(!$(this).hasClass('disabled'))
		{
			if($('#reaction_input_form > fieldset').length > 1) removeReaction();
			if($('#reaction_input_form > fieldset').length == 1) $(this).addClass('disabled');
			else $(this).removeClass('disabled');
		}
		return false;
	});

	$('#reset_reaction_button').click(function()
	{
		if(!$(this).hasClass('disabled')) resetReactions();
		return false;
	});

	$('ul.tabs').each(function()
	{
		// For each set of tabs, we want to keep track of
		// which tab is active and its associated content
		var active, content, links = $(this).find('a');

		// If the location.hash matches one of the links, use that as the active tab.
		// If no match is found, use the first link as the initial active tab.
		active = $(links.filter('[href="' + location.hash + '"]')[0] || links[0]);
		active.addClass('active');
		content = $(active.attr('href'));

		// Hide the remaining content
		links.not(active).each(function ()
		{
			$($(this).attr('href')).hide();
		});

		// Bind the click event handler
		$(this).on('click', 'a', function(e)
		{
			// Make the old tab inactive.
			active.removeClass('active');
			content.hide();

			// Update the variables with the new link and content
			active = $(this);
			content = $($(this).attr('href'));

			// Make the tab active.
			active.addClass('active');
			content.show();

			// Prevent the anchor's default click action
			e.preventDefault();
		});
	});

	$('select.reaction_direction').each(function()
	{
		$(this).change(function()
		{
			enableButtons();
		});
	});

	$('.reaction_left_hand_side').each(function()
	{
		$(this).keyup(function()
		{
			enableButtons();
		});
	});

	$('.reaction_right_hand_side').each(function()
	{
		$(this).keyup(function()
		{
			enableButtons();
		});
	});

	$('#download_network_file_button').click(function(e)
	{
		if($(this).hasClass('disabled')) e.preventDefault();
	});

	$('#upload_network_file_input').change(function()
	{
		$('#upload_network_file_button').removeClass('disabled');
		$('#upload_network_file_button').removeAttr('disabled');
	});

	$('.fancybox').fancybox({autoDimensions: false, width: 1000, height: 700});
	$('#process_network_button').click(function()
	{
		if(!$(this).hasClass('disabled')) 
		{		
			resetPopup();
			if (saveNetwork())
			{				
				testSSDonly();
			}
		}
		return false;
	});	
	$('#dsr_graph_button').click(function(e)
	{
		e.preventDefault();
		$('#dsr_graph_applet_holder').css('left', '50%');
	});
  $('#dsr_graph_close_button').click(function(e)
	{
		e.preventDefault();
		$('#dsr_graph_applet_holder').css('left', '-10000px');
	});
});
