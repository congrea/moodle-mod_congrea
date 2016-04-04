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
 * Internal library of functions for module congrea
 *
 * All the congrea specific functions, needed to implement the module
 * logic, should go here. Never include this file from your lib.php!
 *
 * @package    mod_congrea
 * @copyright  2014 Pinky Sharma
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/*
 * Get list of teacher of current course
 *
 * @param null
 * @return object
 */
function congrea_course_teacher_list() {
    global $COURSE;

    $courseid = $COURSE->id;

    $context = context_course::instance($courseid);
    $heads = get_users_by_capability($context, 'moodle/course:update');

    $teachers = array();
    foreach ($heads as $head) {
        $teachers[$head->id] = fullname($head);
    }
    return $teachers;
}

/*
 * Create form and send sumbitted value to
 * given url and open in popup
 *
 * @param string $url congrea online url
 * @param string $authusername  authenticated user
 * @param string $authpassword  authentication password
 * @param string $role user role eight student or teacher
 * @param string $rid  user authenticated path
 * @param string $room  unique id
 * @param $popupoptions string
 * @param $popupwidth string
 * @param $popupheight string
 * @return string
 */
function congrea_online_server($url, $authusername, $authpassword, $role, $rid, $room,
            $popupoptions, $popupwidth, $popupheight, $upload, $down, $debug = false, 
            $anyonepresenter = 0, $audio = 0, $pushtotalk = 0, $themecolor = 'black') {
    global $USER;
    $form = html_writer::start_tag('form', array('id' => 'overrideform', 'action' => $url, 'method' => 'post',
        'onsubmit' => 'return congrea_online_popup(this)', 'data-popupoption' => $popupoptions,
        'data-popupwidth' => $popupwidth, 'data-popupheight' => $popupheight));
    $form .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()));
    $form .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'uid', 'value' => $USER->id));
    $form .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'name', 'value' => $USER->firstname.' '.$USER->lastname));
    $form .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'role', 'value' => $role));
    $form .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'room', 'value' => $room));
    $form .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'sid', 'value' => $USER->sesskey));
    $form .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'user', 'value' => $authusername));
    $form .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'pass', 'value' => $authpassword));
    $form .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'rid', 'value' => $rid));
    $form .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'upload', 'value' => $upload));
    $form .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'down', 'value' => $down));
    $form .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'debug', 'value' => $debug));
    $form .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'audio', 'value' => $audio));
    $form .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'themecolor', 'value' => $themecolor));
    $form .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'pushtotalk', 'value' => $pushtotalk));
    $form .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'anyonepresenter', 'value' => $anyonepresenter));
    $form .= html_writer::empty_tag('input', array('type' => 'submit', 'name' => 'submit', 'class' => 'vcbutton',
         'value' => get_string('joinroom', 'congrea')));
    $form .= html_writer::end_tag('form');
    return $form;
}

/**
 * Update the calendar entries for this congrea.
 *
 * @param object $congrea - Required to pass this in because it might
 *                          not exist in the database yet.
 * @return bool
 */

function mod_congrea_update_calendar($congrea) {
    global $DB, $CFG;
    require_once($CFG->dirroot.'/calendar/lib.php');

    if ($congrea->closetime && $congrea->closetime > time()) {
        $event = new stdClass();
        $params = array('modulename' => 'congrea', 'instance' => $congrea->id);
        $event->id = $DB->get_field('event', 'id', $params);
        $event->name = $congrea->name;
        $event->timestart = $congrea->opentime;

        // Convert the links to pluginfile. It is a bit hacky but at this stage the files
        // might not have been saved in the module area yet.
        $intro = $congrea->intro;
        if ($draftid = file_get_submitted_draft_itemid('introeditor')) {
            $intro = file_rewrite_urls_to_pluginfile($intro, $draftid);
        }

        // We need to remove the links to files as the calendar is not ready
        // to support module events with file areas.
        $intro = strip_pluginfile_content($intro);
        $event->description = array(
            'text' => $intro,
            'format' => $congrea->introformat
        );

        if ($event->id) {
            $calendarevent = calendar_event::load($event->id);
            $calendarevent->update($event);
        } else {
            unset($event->id);
            $event->courseid = $congrea->course;
            $event->groupid = 0;
            $event->userid = 0;
            $event->modulename = 'congrea';
            $event->instance = $congrea->id;
            $event->eventtype = 'open';
            $event->timeduration = 0;
            calendar_event::create($event);
        }
    } else {
        $DB->delete_records('event', array('modulename' => 'congrea', 'instance' => $congrea->id));
    }
}

/**
 * Delete recoded files with folder.
 *
 * @param string $directory - Path of folder where  
 * recording files of one session has been stored.
 * @return bool
 */

function mod_congrea_deleteAll($directory, $empty = false) {
    if(substr($directory,-1) == "/") {
        $directory = substr($directory,0,-1);
    }

    if(!file_exists($directory) || !is_dir($directory)) {
        return false;
    } elseif(!is_readable($directory)) {
        return false;
    } else {
        $directoryHandle = opendir($directory);

        while ($contents = readdir($directoryHandle)) {
            if($contents != '.' && $contents != '..') {
                $path = $directory . "/" . $contents;

                if(is_dir($path)) {
                    mod_congrea_deleteAll($path);
                } else {
                    unlink($path);
                }
            }
        }
        closedir($directoryHandle);
        if($empty == false) {
            if(!rmdir($directory)) {
                return false;
            }
        }
        return true;
    }
}

/**
 * Returns the rename action.
 *
 * @param cm_info $mod The module to produce editing buttons for
 * @param int $sr The section to link back to (used for creating the links)
 * @return The markup for the rename action, or an empty string if not available.
 */
function mod_congrea_module_get_rename_action($cm, $instance, $sr = null) {
    global $COURSE, $OUTPUT, $USER;

    static $str;
    static $baseurl;

    $modcontext = context_module::instance($cm->id);
    //$hasmanageactivities = has_capability('mod/congrea:recordingupload', $modcontext);
    $hasmanageactivities = has_capability('mod/congrea:addinstance', $modcontext);
    if (!isset($str)) {
        $str = get_strings(array('edittitle'));
    }

    if (!isset($baseurl)) {
        $baseurl = new moodle_url('edit.php', array('id' => $cm->id, 'sesskey' => sesskey()));
    }

    if ($sr !== null) {
        $baseurl->param('sr', $sr);
    }

    // AJAX edit title.
    /*
if ($mod->has_view() && $hasmanageactivities && course_ajax_enabled($COURSE) &&
                (($mod->course == $COURSE->id) || ($mod->course == SITEID))) {
*/
    if($hasmanageactivities || ($USER->id == $instance->userid)){
        // we will not display link if we are on some other-course page (where we should not see this module anyway)
        return html_writer::span(
            html_writer::link(
                new moodle_url($baseurl, array('update' => $instance->id)),
                $OUTPUT->pix_icon('t/editstring', '', 'moodle', array('class' => 'iconsmall visibleifjs', 'title' => '')),
                array(
                    'class' => 'editing_title',
                    'data-action' => 'edittitle',
                    'title' => $str->edittitle,
                )
            )
        );
    }
    return '';
}

/**
 * Generate random string of specified length
 *
 * @param int $length - length of random string  
 * @return bool
 */

function mod_congrea_generateRandomString($length = 11) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}