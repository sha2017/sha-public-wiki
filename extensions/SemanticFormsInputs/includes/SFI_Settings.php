<?php
/**
 * Settings for the Semantic Forms Inputs extension.
 *
 * @author Stephan Gambke
 *
 * To change the default settings you can uncomment (or copy) the
 * examples here and adjust them to your needs. You may as well
 * include them in your LocalSettings.php.
 */

if ( !defined( 'SFI_VERSION' ) ) {
	die( 'This file is part of the SemanticFormsInputs extension, it is not a valid entry point.' );
}

##
# This is the path to your installation of Semantic Forms Inputs as
# seen from the web. No final slash.
#
$sfigSettings->scriptPath = $wgScriptPath . '/extensions/SemanticFormsInputs';

## Time Picker Settings

##
# This is the first selectable time (format hh:mm)
# Sample value: '00:00'
#
$sfigSettings->timePickerMinTime = null;

##
# This is the last selectable time (format hh:mm)
# Sample value: '24:00'
#
$sfigSettings->timePickerMaxTime = null;

##
# This determines if the input field shall be disabled. The user can
# only set the time via the timepicker in this case.
#
$sfigSettings->timePickerDisableInputField = false;

##
# This determines if a reset button shall be shown. This is the only
# way to erase the input field if it is disabled for direct input.
#
$sfigSettings->timePickerShowResetButton = false;


##
# This determines if a reset button shall be shown. This is the only
# way to erase the input field if it is disabled for direct input.
#
$sfigSettings->datetimePickerShowResetButton = false;

##
# This determines if the input field shall be disabled. The user can
# only set the value via the menu in this case.
#
$sfigSettings->menuSelectDisableInputField = false;

