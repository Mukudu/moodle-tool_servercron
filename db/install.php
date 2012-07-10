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
 * This script is run at installation and sets up the plugin for use
 *
 * @package    local_servercron
 * @copyright  2012 Nottingham University
 * @author     Benjamin Ellis <benjamin.c.ellis@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @throws $moodle_exception
 */

/**
 * xmldb_local_servercron_install
 * Code called directly when the plugin is installed for the 1st time
 * checks for installation on windows and throws exception via print_error
 *
 * @throws moodleexection.
 * @return null
 */
function xmldb_local_servercron_install() {
    global $DB;

    //check that we are on a suitable OS
    if (stripos(php_uname('s'), 'windows') !== false) {              //no windows
        print_error('wrong_os', 'local_servercron', new moodle_url('/'), php_uname('s'));
    }
}

/* ?> */
