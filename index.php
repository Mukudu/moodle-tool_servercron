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
 * Server Cron main script file
 *
 * Plugin to manage the http cron jobs for Moodle
 *
 * @package    tool_servercron
 * @copyright  2012 Nottingham University
 * @author     Benjamin Ellis <benjamin.c.ellis@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @throws $moodle_exception via print_error
 */

require('../../../config.php');            //this works everytime - the one in the coding guide does not if moodle is not in the root
//require(dirname(dirname(dirname(__FILE__))) . '/config.php');

//check that we are on a suitable OS
if (stripos(php_uname('s'), 'windows') !== false) {              //no windows
    print_error('wrong_os', 'tool_servercron', new moodle_url('/'), php_uname('s'));
}

require_once('servercron_form.php');

require_login();
require_capability('moodle/site:config', get_context_instance(CONTEXT_SYSTEM));

$PAGE->set_context(get_context_instance(CONTEXT_SYSTEM));
$PAGE->set_url(new moodle_url('/admin/tool/servercron/index.php'));
$PAGE->set_heading($SITE->fullname);
$PAGE->set_pagelayout('admin');

$PAGE->set_title(get_string('servercronpagetitle', 'tool_servercron'));
$PAGE->navbar->add(get_string('servercronpagecrumb', 'tool_servercron'));

$paramerror = '';

$cronjobid = optional_param('cronjobid', 0, PARAM_INT);
$theaction = optional_param('action', '', PARAM_ALPHA);     //have we been called??

// script parameters - stuff saved to $formdata is used by the admin form
$formdata = array();
$formdata['minutes'] = servercron_getminutes();
$formdata['hours'] = servercron_gethours();
$formdata['days'] = servercron_getdays();
$formdata['months'] = servercron_getmonths();
$formdata['wdays'] = servercron_getweekdays();

//get the existing records ready for editing
$formdata['existingrecs'] = array();
$linectr = 0;
$cmd = exec('which crontab') . ' -l';
exec($cmd, $cronlines, $result);

if ($result !== 0) {          //we have an error - 0 = success anything else is error
    print_error('nocronlines', 'tool_servercron', $PAGE->url->out(), null, $result);       //this will throw exception
} else {
    foreach ($cronlines as $cronline) {
        if (!(preg_match('/^#/', $cronline))) { //if not a comment
            $linectr++;
            $xdata = preg_split('/\s+/', $cronline);
            $adata['id'] = $linectr;
            //would have though there was a quicker way to do this - too manyhours spent already and KISS
            $adata['minute'] = $xdata[0];
            $adata['hour'] = $xdata[1];
            $adata['day'] = $xdata[2];
            $adata['month'] = $xdata[3];
            $adata['wday'] = $xdata[4];
            $adata['commandline'] = implode(' ', array_slice($xdata, 5));       //take the rest of the line
            //turn into an object
            $formdata['existingrecs'][] = (object) $adata;
        }
    }
}

if ($theaction) {
    // if we have an action - it would be a good idea to back the current crons - juts in case
    // crons can be restored from that backup manually
    if ($fh = fopen($CFG->dataroot . '/cron.backup', 'wb')) {
        //this could be empty
        foreach ($formdata['existingrecs'] as $bup) {
            $bup = (array) $bup;
            fwrite($fh, implode("\t", array_values($bup)). "\n");
        }
        fclose($fh);
    } else {
        //$noerror = servercron_error(get_string('bkupfilefail', 'tool_servercron') . " - $CFG->dataroot");
        print_error('bkupfilefail', 'tool_servercron', $PAGE->url->out(), null, $result);      //another possibe exception
    }

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
            $paramerror = get_string('croniderror', 'tool_servercron');
        }
    } else if ($theaction == 'save') {             // save details
        //if saving
        if (optional_param('save', '', PARAM_TEXT)) {
            $data = new StdClass();

            //required data - this is order specific
            $data->minute =  required_param('minute', PARAM_TEXT);
            $data->hour =  required_param('hour', PARAM_TEXT);
            $data->day = required_param('day', PARAM_TEXT);
            $data->month = required_param('month', PARAM_TEXT);
            $data->wday = required_param('wday', PARAM_TEXT);
            $data->commandline = required_param('commandline', PARAM_TEXT);

            //verify the timings data that has been submitted
            if (($ret = servercron_checktimeinput($data->minute, 0, 59, PARAM_INT)) !== true) {
                $paramerror .= get_string('minuteprompt', 'tool_servercron') .': ' . $ret;
            } else {
                $data->minute = implode(', ', $data->minute);
            }

            if (($ret = servercron_checktimeinput($data->hour, 0, 23, PARAM_INT)) !== true) {
                $paramerror .= get_string('hourprompt', 'tool_servercron') .': ' . $ret;
            } else {
                $data->hour = implode(', ', $data->hour);
            }

            if (($ret = servercron_checktimeinput($data->day, 1, 31, PARAM_INT)) !== true) {
                $paramerror .= get_string('dayprompt', 'tool_servercron') .': ' . $ret;
            } else {
                $data->day = implode(', ', $data->day);
            }

            if (($ret = servercron_checktimeinput($data->month, 1, 12, PARAM_INT)) !== true) {
                $paramerror .= get_string('monthprompt', 'tool_servercron') .': ' . $ret;
            } else {
                $data->month = implode(', ', $data->month);
            }

            if (($ret = servercron_checktimeinput($data->wday, 0, 6, PARAM_INT)) !== true) {
                $paramerror .= get_string('wdayprompt', 'tool_servercron') .': ' . $ret;
            } else {
                $data->wday = implode(', ', $data->wday);
            }

            if ($cronjobid) {
                foreach ($formdata['existingrecs'] as $rec) {
                    if ($rec->id == $cronjobid) {
                        $rec = $data;           //replace the current record
                        break;
                    }
                }
            } else { //else this is a new record and will go to the bottom of the file
                $formdata['existingrecs'][] = $data;
            }

            //save to database or complain about error
            if (!$paramerror) {
                if (!servercron_savecron($formdata['existingrecs'])) {           //attempt to install the cron jobs
                    $paramerror = get_string('cronsaveerror', 'tool_servercron');
                }
            }
        }   //otherwise edit was cancelled

    } else if ($theaction == 'delete') {
        if ($cronjobid) {
            //$DB->delete_records('tool_servercron', array('id' => $cronjobid));
            $newrecs = array();
            foreach ($formdata['existingrecs'] as $rec) {
                if ($rec->id != $cronjobid) {
                    //unset($rec);         //delete it  this does not work
                    //$rec = null;         //nor does this
                    //break;
                    $newrecs[] = $rec;
                }
            }
            if (!servercron_savecron($newrecs)) {           //attempt to install the cron jobs
                $paramerror = get_string('cronsaveerror', 'tool_servercron');
            }
        } else {
            $paramerror = get_string('croniderror', 'tool_servercron');
        }
    }

    // browser is remembering inputs and so we have to redirect to clear the form fields and the query strings
    if (!$paramerror && ($theaction != 'edit')) {
        redirect($PAGE->url);
    } else {  //else we go back to the form and display the error
        $formdata['error'] = $paramerror;
    }
}

//the rest of this script runs only if no action parameter has been specified.....
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
echo $OUTPUT->heading(get_string('servercronpagetitle', 'tool_servercron'), 2, 'main');

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
 * @param array $newcrons - the new cron jobs required
 * @return bool $noerror - false on error
 */
function servercron_savecron(array $newcrons) {
    global $CFG;
    $noerror = true;

    //find a temporary writeable directory for our cronfile
    if ($temp_file = tempnam(sys_get_temp_dir(), 'moodlecron')) {
        if ($cronfile = fopen($temp_file, 'wb')) {

            if (count($newcrons)) {
                fwrite($cronfile, '#' . get_string('file_warning', 'tool_servercron') . "\n");  //write warning notice
            }

            foreach ($newcrons as $cronjob) {
                if (isset($cronjob->id)) {          //cron file won't like this
                    unset($cronjob->id);
                }
                $cronjob = (array) $cronjob;
                $cronline = implode("\t", array_values($cronjob));
                $cronline = str_replace(-1, '*', $cronline);            //make all -1 into *s
                fwrite($cronfile, "$cronline\n");
            }
            fclose($cronfile);

            $cmd = exec('which crontab') . " $temp_file" . ' 2>&1';         //direct STDERR to STDOUT so we can trace
            exec($cmd, $theoutput, $result);

            if ($result) {          //there is an error condition - 0 is successful completion unless its perl ;)
                $noerror = servercron_error(get_string('croninstallfail', 'tool_servercron') . ' - ' . $theoutput);
            }
            unlink($temp_file);

        } else {
            $noerror = servercron_error(get_string('tmpfilefail', 'tool_servercron'));
        }
    } else {
        $noerror = servercron_error(get_string('tmpfilecreatefail', 'tool_servercron'));
    }

    return $noerror;
}


/**
 * function to write debugging information and set $noerror to false
 *
 * @param string $str the error message
 * @return bool always false to set $noerror in calling routine
 *
 */
function servercron_error(string $str) {
    debugging($str, DEBUG_DEVELOPER);
    return false;
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
                        $errstr = get_string('valueoutsiderange', 'tool_servercron');
                        break;
                    }
                } else {
                    $errstr = get_string('valuenotnumber', 'tool_servercron');
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