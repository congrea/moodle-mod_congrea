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
 * Defines the version of congrea
 *
 * This code fragment is called by moodle_needs_upgrading() and
 * /admin/index.php
 *
 * @package    mod_congrea
 * @copyright  2015 Pinky Sharma
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->version   = 2018092200; // The current module version (Date: YYYYMMDDXX).
$plugin->release = '3.0 (Build: 2018081100)';
$plugin->requires = 2015111600.00;      // Requires this Moodle version.(3.0+).
$plugin->cron = 0;               // Period for cron to check this module (secs).
$plugin->component = 'mod_congrea'; // To check on upgrade, that module sits in correct place.
$plugin->maturity = MATURITY_STABLE;
