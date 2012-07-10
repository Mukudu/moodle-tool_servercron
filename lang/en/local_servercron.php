<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Plugin to manage the http cron jobs for moodle - english language file
 *
 * @package    local_servercron
 * @copyright  2012 Nottingham University
 * @author     Benjamin Ellis <benjamin.c.ellis@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Server Cron Jobs';
$string['pluginname_desc'] = "Plugin to manage the webserver's cron jobs for Moodle.";

$string['wrong_os'] = 'This plugin does not work with your Operating System ({$a})';

$string['valueoutsiderange'] = 'Value is not in allowable range';
$string['valuenotnumber'] = 'Value does not appear to be a number';

$string['croninstallfail'] = 'Cron Job Update failed';
$string['tmpfilefail'] = 'Temp File could not be opened';
$string['tmpfilecreatefail'] = 'Cannot create a temporary file';
$string['bkupfilefail'] = 'Cannot make backup file';
$string['nocronlines'] = 'Failed to get current cron lines';

$string['file_warning'] = 'This cron file is being maintained by Moodle - do not edit manually';

$string['servercronpagetitle'] = 'Server Cron Job Management';
$string['servercronpagecrumb'] = 'Server Crons';
$string['exitingcrontitle'] = 'Exisiting Cron Jobs';
$string['newcronstitle'] = 'Add A New Cron Job';
$string['editcronstitle'] = 'Edit Server Cron Job';
$string['noexistingcrons'] = 'No existing cron jobs found';

$string['editactivepositive'] = 'Make Active';
$string['editactivenegative'] = 'Make Inactive';
$string['editcronjob'] = 'Edit';
$string['deletecronjob'] = 'Delete';

$string['timingsprompt'] = 'Timing';
$string['commandprompt'] = 'Command Line';
$string['actionsprompt'] = 'Actions';
$string['wdayprompt'] = 'Day of Week';
$string['hourprompt'] = 'Hour';
$string['monthprompt'] = 'Month';
$string['minuteprompt'] = 'Minute';
$string['dayprompt'] = 'Day';
$string['activeprompt'] = 'Active?';

$string['commandprompt'] = 'Command to Run';
$string['commandlinerror'] = 'The command line appears incorrect - it should be a path to a script';
$string['cronjobsave'] = 'Save';
$string['croneditcancel'] = 'Cancel';
$string['cronjobreset'] = 'Reset';

$string['croniderror'] = 'Cron job id must be specified';
$string['cronsaveerror'] = 'There was an error enabling the cron - please check and retry or view server logs for details';

/* ?> */