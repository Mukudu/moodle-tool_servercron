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
 * Server Cron Version file
 *
 * Plugin to manage the http cron jobs for moodle
 *
 * @package    tool_servercron
 * @copyright  2012 Nottingham University
 * @author     Benjamin Ellis <benjamin.c.ellis@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->component = 'tool_servercron';
$plugin->version = 2012042705;          //removed database - direct cron manipulation
$plugin->requires = 2011070100; // (Moodle 2.1 = 2011070100; Moodle 2.2 = 2011120100)

/* ?> */