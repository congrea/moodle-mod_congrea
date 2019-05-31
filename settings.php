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
    $settings->add(new admin_setting_configcolourpicker('mod_congrea/colorpicker',
            get_string('colorpicker', 'congrea'), get_string('colorpickerd', 'congrea'), '#021317', $previewconfig));
    $settings->add(new admin_setting_heading('mod_congrea/general_settings', get_string('generalsection', 'congrea'), ''));
    // Congrea allowoverride default on.
    $settings->add(new admin_setting_configcheckbox('mod_congrea/allowoverride', get_string('cgallowoverride', 'mod_congrea'),
                                                      get_string('cgallowoverride_help', 'mod_congrea'), 1));
    // Congrea disableattendeeav default off.
    $settings->add(new admin_setting_configcheckbox('mod_congrea/disableattendeeav', get_string('disableattendeeav', 'mod_congrea'),
                                                      get_string('disableattendeeav_help', 'mod_congrea'), 0));
    // Congrea disableattendeepc default off.
    $settings->add(new admin_setting_configcheckbox('mod_congrea/disableattendeepc', get_string('disableattendeepc', 'mod_congrea'),
                                                      get_string('disableattendeepc_help', 'mod_congrea'), 0));
    // Congrea disableattendeepc default off.
    $settings->add(new admin_setting_configcheckbox('mod_congrea/disableattendeegc', get_string('disableattendeegc', 'mod_congrea'),
                                                      get_string('disableattendeegc_help', 'mod_congrea'), 0));
    // Congrea disableraisehand default on.
    $settings->add(new admin_setting_configcheckbox('mod_congrea/disableraisehand', get_string('disableraisehand', 'mod_congrea'),
                                                      get_string('disableraisehand_help', 'mod_congrea'), 1));
    // Congrea disableuserlist default on.
    $settings->add(new admin_setting_configcheckbox('mod_congrea/disableuserlist', get_string('disableuserlist', 'mod_congrea'),
                                                      get_string('disableuserlist_help', 'mod_congrea'), 1));
    // Recordings Section.
    $settings->add(new admin_setting_heading('mod_congrea/recording_header', get_string('recordingsection', 'congrea'), ''));
    // Congrea recording default off.
    $settings->add(new admin_setting_configcheckbox('mod_congrea/enablerecording', get_string('cgrecording', 'congrea'),
                                                        get_string('cgrecordingd', 'congrea'), 0));
    // Congrea recAllowpresentorAVcontrol default on.
    $settings->add(new admin_setting_configcheckbox('mod_congrea/recAllowpresentorAVcontrol',
    get_string('recAllowpresentorAVcontrol', 'congrea'), get_string('recAllowpresentorAVcontrol_help', 'congrea'), 1));
    // Congrea recShowPresentorRecordingStatus default on.
    $settings->add(new admin_setting_configcheckbox('mod_congrea/recShowPresentorRecordingStatus',
    get_string('recShowPresentorRecordingStatus', 'congrea'), get_string('recShowPresentorRecordingStatus_help', 'congrea'), 1));
    // Congrea recDisableAttendeeAV default off.
    $settings->add(new admin_setting_configcheckbox('mod_congrea/recDisableAttendeeAV',
    get_string('recDisableAttendeeAV', 'congrea'), get_string('recDisableAttendeeAV_help', 'congrea'), 0));
    // Congrea recAllowattendeeAVcontrol default off.
    $settings->add(new admin_setting_configcheckbox('mod_congrea/recAllowattendeeAVcontrol',
    get_string('recAllowattendeeAVcontrol', 'congrea'), get_string('recAllowattendeeAVcontrol_help', 'congrea'), 0));
    // Congrea recAllowattendeeAVcontrol default off.
    $settings->add(new admin_setting_configcheckbox('mod_congrea/showAttendeeRecordingStatus',
    get_string('showAttendeeRecordingStatus', 'congrea'), get_string('showAttendeeRecordingStatus_help', 'congrea'), 0));
    // Congrea trimRecordings default on.
    $settings->add(new admin_setting_configcheckbox('mod_congrea/trimRecordings', get_string('trimRecordings', 'congrea'),
                                                        get_string('trimRecordings_help', 'congrea'), 1));
}