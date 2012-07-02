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
 * Server Cron
 *
 * Plugin to manage the http cron jobs for moodle
 *
 * @package    local_servercron
 * @copyright  2012 Nottingham University
 * @author     Benjamin Ellis - benjamin.ellis@nottingham.ac.uk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @throws $moodle_exception
 */

require('../../config.php');            //this works everytime - the one in the coding guide does not if moodle is not in the root
//require(dirname(dirname(__FILE__)) . '/config.php');

//check that we are on a suitable OS
if (stripos(php_uname('s'), 'windows') !== false) {              //no windows
    throw new moodle_exception(get_string('wrong_os', 'local_servercron', php_uname('s')), 'local_servercron');
}

require_once('servercron_form.php');

require_login();
require_capability('moodle/site:config', get_context_instance(CONTEXT_SYSTEM));

$PAGE->set_context(get_context_instance(CONTEXT_SYSTEM));
$PAGE->set_url(new moodle_url('/local/servercron/index.php'));
$PAGE->set_heading($SITE->fullname);
$PAGE->set_pagelayout('admin');

$PAGE->set_title(get_string('servercronpagetitle', 'local_servercron'));
$PAGE->navbar->add(get_string('servercronpagecrumb', 'local_servercron'));

$paramerror = '';

$cronjobid = optional_param('cronjobid', 0, PARAM_INT);
$theaction = optional_param('action', '', PARAM_ALPHA);     //have we been called??

// script parameters - stuff saved to $formdata is used by the admin form
$formdata = array();
//the rest of this script runs only if no action parameter has been specified.....
$formdata['minutes'] = servercron_getminutes();
$formdata['hours'] = servercron_gethours();
$formdata['days'] = servercron_getdays();
$formdata['months'] = servercron_getmonths();
$formdata['wdays'] = servercron_getweekdays();

//get the existing records ready for editing
$formdata['existingrecs'] = $DB->get_records('local_servercron');

if ($theaction) {
    if ($theaction == 'edit') {
        //fill in the form with
        if ($cronjobid) {
            $editinglines = array();            //replacement existing records
            foreach ($formdata['existingrecs'] as $rec) {
                if ($rec->id == $cronjobid) {
                    $preformdata = (array) $rec; //cast the StdObj as an array
                    unset($preformdata['id']);      //delete the id field and replace with cronjobid
                    $preformdata['cronjobid'] = $cronjobid;
                    //now loop through and change the timings fields to arrays if requires
                    foreach ($preformdata as $fldname => $fldval) {
                        if ($fldname != 'cronjobid' || $fldname != 'commandline' || $fldname != 'active') {
                            //change to an array if required
                            if ($valarray = explode(', ', $fldval)) {
                                $fldval = $valarray;
                            }
                        }
                    }

                    //combine the 2 arrays formdata and preformdata
                    $formdata = array_merge($formdata, $preformdata);

                } else {
                    $editinglines[] = $rec;
                }

            }
            $formdata['existingrecs'] = $editinglines;

        } else {
            $paramerror = get_string('croniderror', 'local_servercron');
        }
    } else if ($theaction == 'save') {             // save details
        //if saving
        if (optional_param('save', '', PARAM_TEXT)) {
            $data = new StdClass();

            $data->active = 1;          //always active by default
            if ($cronjobid) {
                $data->id = $cronjobid;
            }

            //required data
            $data->commandline = required_param('commandline', PARAM_TEXT);
            $data->minute =  required_param('minute', PARAM_TEXT);
            $data->hour =  required_param('hour', PARAM_TEXT);
            $data->day = required_param('day', PARAM_TEXT);
            $data->month = required_param('month', PARAM_TEXT);
            $data->wday = required_param('wday', PARAM_TEXT);


            //verify the timings data that has been submitted
            if (($ret = servercron_checktimeinput($data->minute, 0, 59, PARAM_INT)) !== true) {
                $paramerror .= get_string('minuteprompt', 'local_servercron') .': ' . $ret;
            } else {
                $data->minute = implode(', ', $data->minute);
            }

            if (($ret = servercron_checktimeinput($data->hour, 0, 23, PARAM_INT)) !== true) {
                $paramerror .= get_string('hourprompt', 'local_servercron') .': ' . $ret;
            } else {
                $data->hour = implode(', ', $data->hour);
            }

            if (($ret = servercron_checktimeinput($data->day, 1, 31, PARAM_INT)) !== true) {
                $paramerror .= get_string('dayprompt', 'local_servercron') .': ' . $ret;
            } else {
                $data->day = implode(', ', $data->day);
            }

            if (($ret = servercron_checktimeinput($data->month, 1, 12, PARAM_INT)) !== true) {
                $paramerror .= get_string('monthprompt', 'local_servercron') .': ' . $ret;
            } else {
                $data->month = implode(', ', $data->month);
            }

            if (($ret = servercron_checktimeinput($data->wday, 0, 6, PARAM_INT)) !== true) {
                $paramerror .= get_string('wdayprompt', 'local_servercron') .': ' . $ret;
            } else {
                $data->wday = implode(', ', $data->wday);
            }


            //save to database or complain about error
            if (!$paramerror) {
                //save in database
                if ($cronjobid) {
                    $DB->update_record('local_servercron', $data);
                } else {
                    $DB->insert_record('local_servercron', $data);
                }
                if (!servercron_savecron()) {           //attempt to install the cron jobs
                    $paramerror = get_string('cronsaveerror', 'local_servercron');
                }
            }
        }   //otherwise edit was cancelled

    } else if ($theaction == 'delete') {
        if ($cronjobid) {
            $DB->delete_records('local_servercron', array('id'=>$cronjobid));
            if (!servercron_savecron()) {           //attempt to install the cron jobs
                $paramerror = get_string('cronsaveerror', 'local_servercron');
            }
        } else {
            $paramerror = get_string('croniderror', 'local_servercron');
        }
    } else if ($theaction == 'changestat') {
        if ($cronjobid) {
            $data = new StdClass();
            $data->id = $cronjobid;
            $data->active = required_param('status', PARAM_INT);            //only required in this instance
            $data->active = $data->active ? 0 : 1;
            $DB->update_record('local_servercron', $data);
            if (!servercron_savecron()) {           //attempt to install the cron jobs
                $paramerror = get_string('cronsaveerror', 'local_servercron');
            }
        } else {
            $paramerror = get_string('croniderror', 'local_servercron');
        }
    }
    //$paramerror = 'Get Lost, Ben';

    // browser is remembering inputs and so we have to redirect to clear the form fields and the query strings
    if (!$paramerror && ($theaction != 'edit')) {
        redirect($PAGE->url);
    } else {  //else we go back to the form and display the error
        $formdata['error'] = $paramerror;
    }

}

if (!isset($formdata['cronjobid'])) {       //handle new crons
    $formdata['cronjobid'] = 0;
}
//set up the command line if required
if (! isset($formdata['commandline']) ) {
    $php_bin = exec('which php');         // not very nice but works!!!
    $formdata['commandline'] = $php_bin . ' ' .$CFG->dirroot . '/';
}

//create the form
$wmform = new servercron_form(null, $formdata);

//display the resulting page

// header
echo $OUTPUT->header();

// content
echo $OUTPUT->heading(get_string('servercronpagetitle', 'local_servercron'), 2, 'main');

$wmform->display();

// and the footer
echo $OUTPUT->footer();

/**********************************************************************************************************************************/
/*
    Functions start here
*/
/**********************************************************************************************************************************/

/**
 * function to actually create the cron - this does not work on Windows!!!!
 *
 * @throws moodleexection.
 * @return bool $noerror - false on error
 */
function servercron_savecron() {
    global $DB;         //why? why???
    $noerror = true;

    //find a temporary writeable directory for our cronfile
    if ($temp_file = tempnam(sys_get_temp_dir(), 'moodlecron')) {
        if ($cronfile = fopen($temp_file, 'wb')) {
            //error_log("++++++++ Temp file is $temp_file");
            $activecrons = $DB->get_records('local_servercron', array('active'=>1), null,
                    'minute, hour, day, month, wday, commandline');     //don't care about order etc

            if (count($activecrons)) {
                fwrite($cronfile, '#' . get_string('file_warning', 'local_servercron') . "\n");  //write warning notice
            }

            foreach ($activecrons as $cronjob) {
                $cronjob = (array) $cronjob;
                $cronline = implode("\t", array_values($cronjob));
                $cronline = str_replace(-1, '*', $cronline);
                fwrite($cronfile, "$cronline\n");
            }
            fclose($cronfile);

            $cmd = exec('which crontab') . " $temp_file" . ' 2>&1';         //direct STDERR to STDOUT so we can trace
            exec($cmd, $theoutput, $result);

            if ($result) {          //there is an error condition - 0 is successful completion unless its perl ;)
                $noerror = false;
                //error_log('Cron Job Update failed - ' . print_r($theoutput, true));
            }
            unlink($temp_file);

        } else {
            //error_log("File $tempfile not opened");
            $noerror = false;
        }
    } else {
        //error_log("Cannot open a temporary file");
        $noerror =  false;
    }

    return $noerror;
}

/**
 * function to check the timing inputs (only -1 or integers allowed)
 *
 * @param array $tarray List of selected values REQ
 * @param int $min value allowed REQ
 * @param int $max value allowed REQ
 * @param constant $type constant moodle type for parameters e.g PARAM_INT, PARAM_URL etc REQ
 * @return string || bool $errstr on error - true otherwise
 *
 */
function servercron_checktimeinput($tarray, $min, $max, $type) {
    $errstr = '';

    //check for -1 and more options
    if (in_array(-1, $tarray) && (count($tarray) > 1)) {
        $errstr = 'Cannot mix * with other times';
    }

    //check the length
    if (count($tarray) >= ($max - $min - 1)) {
        $errstr = 'Appears that all possible values have been selected.';
    }

    if (!$errstr) {
        //check each parameter
        foreach ($tarray as $tval) {
            if ($tval != -1) {
                //check the value
                (int) $tval;
                if ($tval = clean_param($tval, $type)) {   //check our input
                    if (!($tval >= $min) && ($tval <= $max)) {
                        $errstr = 'Value is not allowable range';
                        break;
                    }
                } else {
                    $errstr = 'Value does not appear to be a number';
                    break;
                }
            }
        }
    }

    if ($errstr) {
        return $errstr;
    }
    return true;
}

/*
    dropdown fillers start here
*/

/**
 * function to return drop down values for minutes input
 *
 * @return array of values
 */
function servercron_getminutes() {
    return array(
        '-1' => 'Every minute',
        '0' => '00',
        '1' => '01',
        '2' => '02',
        '3' => '03',
        '4' => '04',
        '5' => '05',
        '6' => '06',
        '7' => '07',
        '8' => '08',
        '9' => '09',
        '10' => '10',
        '11' => '11',
        '12' => '12',
        '13' => '13',
        '14' => '14',
        '15' => '15',
        '16' => '16',
        '17' => '17',
        '18' => '18',
        '19' => '19',
        '20' => '20',
        '21' => '21',
        '22' => '22',
        '23' => '23',
        '24' => '24',
        '25' => '25',
        '26' => '26',
        '27' => '27',
        '28' => '28',
        '29' => '29',
        '30' => '30',
        '31' => '31',
        '32' => '32',
        '33' => '33',
        '34' => '34',
        '35' => '35',
        '36' => '36',
        '37' => '37',
        '38' => '38',
        '39' => '39',
        '40' => '40',
        '41' => '41',
        '42' => '42',
        '44' => '44',
        '44' => '44',
        '45' => '45',
        '46' => '46',
        '47' => '47',
        '48' => '48',
        '49' => '49',
        '50' => '50',
        '51' => '51',
        '52' => '52',
        '55' => '55',
        '55' => '55',
        '55' => '55',
        '56' => '56',
        '57' => '57',
        '58' => '58',
        '59' => '59',
        );
}

/**
 * function to return drop down values for hours input
 *
 * @return array of values
 */
function servercron_gethours() {
    return array(
        '-1' => 'Every hour',
        '0' => '00',
        '1' => '01',
        '2' => '02',
        '3' => '03',
        '4' => '04',
        '5' => '05',
        '6' => '06',
        '7' => '07',
        '8' => '08',
        '9' => '09',
        '10' => '10',
        '11' => '11',
        '12' => '12',
        '13' => '13',
        '14' => '14',
        '15' => '15',
        '16' => '16',
        '17' => '17',
        '18' => '18',
        '19' => '19',
        '20' => '20',
        '21' => '21',
        '22' => '22',
        '23' => '23',
    );
}

/**
 * function to return drop down values for days input
 *
 * @return array of values
 */
function servercron_getdays() {
    return array(
        '-1' => 'Every day',
        '1' => '01',
        '2' => '02',
        '3' => '03',
        '4' => '04',
        '5' => '05',
        '6' => '06',
        '7' => '07',
        '8' => '08',
        '9' => '09',
        '10' => '10',
        '11' => '11',
        '12' => '12',
        '13' => '13',
        '14' => '14',
        '15' => '15',
        '16' => '16',
        '17' => '17',
        '18' => '18',
        '19' => '19',
        '20' => '20',
        '21' => '21',
        '22' => '22',
        '23' => '23',
        '24' => '24',
        '25' => '25',
        '26' => '26',
        '27' => '27',
        '28' => '28',
        '29' => '29',
        '30' => '30',
        '31' => '31',
    );
}

/**
 * function to return drop down values for months input
 *
 * @return array of values
 */
function servercron_getmonths() {
    return array(
            '-1' => 'Every Month',
            '1' => 'January',
            '2' => 'February',
            '3' => 'March',
            '4' => 'April',
            '5' => 'May',
            '6' => 'June',
            '7' => 'July',
            '8' => 'Augest',
            '9' => 'September',
            '10' => 'October',
            '11' => 'November',
            '12' => 'December',
        );
}

/**
 * function to return drop down values for day of the week input
 *
 * @return array of values
 */
function servercron_getweekdays() {
    return array(
            '-1' => 'Every weekday',
            '0' => 'Sunday',
            '1' => 'Monday',
            '2' => 'Tuesday',
            '3' => 'Wednesday',
            '4' => 'Thursday',
            '5' => 'Friday',
            '6' => 'Saturday',
        );
}

/* ?> */