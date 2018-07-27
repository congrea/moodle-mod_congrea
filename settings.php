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
 * */
defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_heading('mod_congrea/heading', get_string('congreaconfiguration', 'congrea'),
                                            get_string('congreaconfigurationd', 'congrea'), ''));
    // Api key and Secret key settings.
    $settings->add(new admin_setting_configtext('mod_congrea/cgapi', get_string('cgapi', 'congrea'),
                                           get_string('cgapid', 'congrea'), ''));
    $settings->add(new admin_setting_configpasswordunmask('mod_congrea/cgsecretpassword', get_string('cgsecret', 'congrea'),
                                                        get_string('cgsecretd', 'congrea'), ''));
    // Colourpicker Settings.
    $choices = array('#021317' => 'Black Pearl', '#003056' => 'Prussian Blue', '#424f9b' => 'Chambray',
            '#001e67' => 'Midnight Blue', '#692173' => 'Honey Flower', '#511030' => 'Heath', '#0066b0' => 'Endeavour');
    $settings->add(new admin_setting_configselect('mod_congrea/preset', get_string('preset', 'congrea'),
                                                get_string('presetd', 'congrea'), '#34404c', $choices));
    $PAGE->requires->js_call_amd('mod_congrea/congrea', 'presetColor');
    $previewconfig = null;
    $settings->add(new admin_setting_configcolourpicker('mod_congrea/colorpicker', get_string('colorpicker', 'congrea'),
                                                    get_string('colorpickerd', 'congrea'), '#021317', $previewconfig));
}
