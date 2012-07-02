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
 * Server Cron - installation script
 *
 * Plugin to manage the http cron jobs for moodle
 * This script is run at installation and sets up the plugin fo use
 *
 * @package    local_servercron
 * @copyright  2012 Nottingham University
 * @author     Benjamin Ellis - benjamin.ellis@nottingham.ac.uk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @throws $moodle_exception
 */


/**
 * xmldb_local_servercron_install
 * Code called directly when the plugin is installed for the 1st time
 * checks for insttaltion on windows and throws exception
 *
 * @throws moodleexection.
 * @return null
 */
function xmldb_local_servercron_install() {
    global $DB;

    //check that we are on a suitable OS
    if (stripos(php_uname('s'), 'windows') !== false) {              //no windows
        throw new moodle_exception(get_string('wrong_os', 'local_servercron', php_uname('s')), 'local_servercron');
        //die(get_string('wrong_os', 'local_servercron', php_uname('s')));  //vicious way to do this no menus to continue

        //maybe drop the tables??
    }

    // read any existing crons and add to database
    $cmd = exec('which crontab') . ' -l';
    exec($cmd, $cronlines, $result);
    foreach ($cronlines as $cronline) {
        if (!(preg_match('/^#/', $cronline))) { //if not a comment
            $cronline = str_replace('*', '-1', $cronline);
            $xdata = preg_split('/\s+/', $cronline);
            $adata['commandline'] = implode(' ', array_slice($xdata, 5));
            //would have though there was a quicker way to do this - too manyhours spent already and KISS
            $adata['minute'] = $xdata[0];
            $adata['hour'] = $xdata[1];
            $adata['day'] = $xdata[2];
            $adata['month'] = $xdata[3];
            $adata['wday'] = $xdata[4];
            //, $adata['commandline']) = $xdata[5];
            //turn into an object
            $data = (object) $adata;
            //write to database
            if (!$DB->insert_record('local_servercron', $data)) {
                throw new moodle_exception('No Good database', 'local_servercron');
                break;
            }
        }
    }
}

/* ?> */
