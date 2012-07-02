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
 * Server Cron Jobs Settings Page
 *
 * add a menu option to manage Server Cron Jobs Settings Page - Administartion->Server
 *
 * @package    local_servercron
 * @copyright  2012 Nottingham University
 * @author     Benjamin Ellis - benjamin.ellis@nottingham.ac.uk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) { // needs this condition or there is error on login page
    if (!$ADMIN->add('server', new admin_externalpage('local_servercron',
            get_string('pluginname', 'local_servercron'),
            new moodle_url($CFG->wwwroot.'/local/servercron/index.php')))) {
        debugging('Failed to add menu Item', DEBUG_DEVELOPER);
    }
}

/* ?> */