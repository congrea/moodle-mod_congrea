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
 * Settings used by the congrea module
 *
 * @package mod_congrea
 * @copyright  2014 Pinky Sharma
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or late
 **/

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    $defaultrun = array();
    $defaultrun[0] = get_string("liverun", "congrea");
    $defaultrun[1] = get_string("localrun", "congrea");//mod_congrea/settingname 
    $settings->add(new admin_setting_configselect('mod_congrea/serve', get_string('wheretoruncongrea', 'congrea'),
            get_string('configactiontolocalrun', 'congrea'), 0, $defaultrun));
}
