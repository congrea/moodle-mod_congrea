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
 * Library of interface functions and constants for module congrea
 *
 * All the core Moodle functions, neeeded to allow the module to work
 * integrated in Moodle should be placed here.
 * All the congrea specific functions, needed to implement all the module
 * logic, should go to locallib.php. This will help to save some memory when
 * Moodle is performing actions across all modules.
 *
 * @package    mod_congrea
 * @copyright  2014 Pinky Sharma
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/**
 * Moodle core API
 */

/**
 * Returns the information on whether the module supports a feature
 *
 * @see plugin_supports() in lib/moodlelib.php
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed true if the feature is supported, null if unknown
 */
function congrea_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        case FEATURE_GRADE_HAS_GRADE:
            return false;
        case FEATURE_GRADE_OUTCOMES:
            return false;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_GROUPS:
            return true;
        case FEATURE_GROUPINGS:
            return true;
        case FEATURE_GROUPMEMBERSONLY:
            return true;
        default:
            return null;
    }
}

/**
 * Saves a new instance of the congrea into the database
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param object $congrea
 * @return int The id of the newly inserted congrea record
 */
function congrea_add_instance($congrea) {
    global $DB;
    $congrea->timecreated = time();
    if (!empty($congrea->recallowpresentoravcontrol)) {
        $congrea->showpresentorrecordingstatus = 1;
    }
    if (empty($congrea->attendeerecording)) {
        $congrea->recattendeeav = 0;
        $congrea->recallowattendeeavcontrol = 0;
        $congrea->showattendeerecordingstatus = 0;
    }
    if (empty($congrea->recattendeeav)) {
        $congrea->recallowattendeeavcontrol = 0;
    }
    if (!empty($congrea->recallowattendeeavcontrol)) {
        $congrea->showattendeerecordingstatus = 1;
    }
    $vclass = $DB->insert_record('congrea', $congrea);
    $congrea->id = $vclass;
    return $vclass;
}

/**
 * This function extends the settings navigation block for the site.
 *
 * It is safe to rely on PAGE here as we will only ever be within the module
 * context when this is called
 *
 * @param object $settings
 * @param object $congreanode
 */
function congrea_extend_settings_navigation($settings, $congreanode) {
    global $PAGE;

    if (has_capability('mod/congrea:pollreport', $PAGE->cm->context)) {
        $url = new moodle_url('/mod/congrea/polloverview.php', array('cmid' => $PAGE->cm->id));
        $congreanode->add(get_string('pollreport', 'congrea'), $url);
    }
    if (has_capability('mod/congrea:quizreport', $PAGE->cm->context)) {
        $url = new moodle_url('/mod/congrea/quizoverview.php', array('cmid' => $PAGE->cm->id));
        $congreanode->add(get_string('quizreport', 'congrea'), $url);
    }
}

/**
 * Updates an instance of the congrea in the database
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @param object $congrea An object from the form in mod_form.php
 * @param object $mform
 * @return boolean Success/Fail
 */
function congrea_update_instance($congrea, $mform = null) {
    global $DB;
    $congrea->timemodified = time();
    $congrea->id = $congrea->instance;
    if (!empty($congrea->recallowpresentoravcontrol)) {
        $congrea->showpresentorrecordingstatus = 1;
    }
    if (empty($congrea->attendeerecording)) {
        $congrea->recattendeeav = 0;
        $congrea->recallowattendeeavcontrol = 0;
        $congrea->showattendeerecordingstatus = 0;
    }
    if (empty($congrea->recattendeeav)) {
        $congrea->recallowattendeeavcontrol = 0;
    }
    if (!empty($congrea->recallowattendeeavcontrol)) {
        $congrea->showattendeerecordingstatus = 1;
    }
    $status = $DB->update_record('congrea', $congrea);
    return $status;
}

/**
 * Removes an instance of the congrea from the database
 *
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 */
function congrea_delete_instance($id) {
    global $DB, $CFG, $COURSE;
    if (!$congrea = $DB->get_record('congrea', array('id' => $id))) {
        return false;
    }
    if ($poll = $DB->get_records('congrea_poll', array('instanceid' => $congrea->id))) {
        foreach ($poll as $polldata) {
            $DB->delete_records('congrea_poll_attempts', array('qid' => $polldata->id));
            if (!$DB->delete_records('congrea_poll_question_option', array('qid' => $polldata->id))) {
                return false;
            }
        }
        $DB->delete_records('congrea_poll', array('instanceid' => $congrea->id));
    }
    if ($quiz = $DB->get_records('congrea_quiz', array('congreaid' => $congrea->id))) {
        foreach ($quiz as $quizdata) {
            if (!$DB->delete_records('congrea_quiz_grade', array('congreaquiz' => $quizdata->id))) {
                return false;
            }
        }
        $DB->delete_records('congrea_quiz', array('congreaid' => $congrea->id));
    }
    // TODO: Currently all events are deleted, past events should not get deleted.
    $DB->delete_records('event', array('modulename' => 'congrea', 'instance' => $congrea->id));
    $DB->delete_records('congrea', array('id' => $congrea->id));
    return true;
}

/**
 * Returns a small object with summary information about what a
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 * $return->time = the time they did it
 * $return->info = a short text description
 *
 * @param object $course
 * @param object $user
 * @param object $mod
 * @param object $congrea
 * @return object|null
 */
function congrea_user_outline($course, $user, $mod, $congrea) {

    $return = new stdClass();
    $return->time = 0;
    $return->info = '';
    return $return;
}

/**
 * Creates or updates grade item for the give congrea instance
 *
 * Needed by grade_update_mod_grades() in lib/gradelib.php
 *
 * @param object $congrea
 * @param array|object $grades
 * @return void
 */
function congrea_grade_item_update($congrea, $grades = null) {
    return false;
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');
    $item = array();
    $item['itemname'] = clean_param($congrea->name, PARAM_NOTAGS);
    $item['gradetype'] = GRADE_TYPE_VALUE;
    $item['grademax'] = $congrea->grade;
    $item['grademin'] = 0;
    grade_update('mod/congrea', $congrea->course, 'mod', 'congrea', $congrea->id, 0, null, $item);
}

/**
 * Update congrea grades in the gradebook
 * Needed by grade_update_mod_grades() in lib/gradelib.php
 *
 * @param stdClass $congrea instance object with extra cmidnumber and modname property
 * @param int $userid update grade of specific user only, 0 means all participants
 * @return void
 */
function congrea_update_grades(stdClass $congrea, $userid = 0) {
    global $CFG, $DB;
    require_once($CFG->libdir . '/gradelib.php');

    $grades = array(); // Populate array of grade objects indexed by userid.

    grade_update('mod/congrea', $congrea->course, 'mod', 'congrea', $congrea->id, 0, $grades);
}

/**
 * Returns the lists of all browsable file areas within the given module context
 *
 * The file area 'intro' for the activity introduction field is added automatically
 * by {link file_browser::get_file_info_context_module()}
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @return array of [(string)filearea] => (string)description
 */
function congrea_get_file_areas($course, $cm, $context) {
    return array();
}

/**
 * File browsing support for congrea file areas
 *
 * @package mod_congrea
 * @category files
 *
 * @param file_browser $browser
 * @param array $areas
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @param string $filearea
 * @param int $itemid
 * @param string $filepath
 * @param string $filename
 * @return file_info instance or null if not found
 */
function congrea_get_file_info($browser, $areas, $course, $cm, $context, $filearea, $itemid, $filepath, $filename) {
    return null;
}

/**
 * Delete recorded congrea file form storage
 *
 * @param string $dir path of directory where file store
 */
function congrea_deletedirectory($dir) {
    if (!file_exists($dir)) {
        return true;
    }
    if (!is_dir($dir) || is_link($dir)) {
        return unlink($dir);
    }
    foreach (scandir($dir) as $item) {
        if ($item == '.' || $item == '..') {
            continue;
        }
        if (!congrea_deletedirectory($dir . "/" . $item)) {
            chmod($dir . "/" . $item, 0777);
            if (!congrea_deletedirectory($dir . "/" . $item)) {
                return false;
            }
        };
    }
    return rmdir($dir);
}