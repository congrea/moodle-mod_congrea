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
    switch($feature) {
        case FEATURE_MOD_INTRO: return true;
        case FEATURE_SHOW_DESCRIPTION: return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS: return true;
        case FEATURE_GRADE_HAS_GRADE:         return false;
        case FEATURE_GRADE_OUTCOMES:          return false;
        case FEATURE_BACKUP_MOODLE2:          return true;
        case FEATURE_GROUPS:                  return true;
        case FEATURE_GROUPINGS:               return true;
        case FEATURE_GROUPMEMBERSONLY:        return true;
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
 * @param object $congrea An object from the form in mod_form.php
 * @param mod_congrea_mod_form $mform
 * @return int The id of the newly inserted congrea record
 */
function congrea_add_instance(stdClass $congrea, mod_congrea_mod_form $mform = null) {
    global $DB;

    $congrea->timecreated = time();
    $vclass = $DB->insert_record('congrea', $congrea);
    $congrea->id = $vclass;
    mod_congrea_update_calendar($congrea);
    return $vclass;

}

/**
 * Updates an instance of the congrea in the database
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @param object $congrea An object from the form in mod_form.php
 * @param mod_congrea_mod_form $mform
 * @return boolean Success/Fail
 */
function congrea_update_instance(stdClass $congrea, mod_congrea_mod_form $mform = null) {
    global $DB;
    if(!empty($congrea->anyonepresenter)) {
        $congrea->moderatorid = 0;
    }
    $congrea->timemodified = time();
    $congrea->id = $congrea->instance;

    $status = $DB->update_record('congrea', $congrea);
    mod_congrea_update_calendar($congrea);
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
    global $DB, $CFG;

    if (! $congrea = $DB->get_record('congrea', array('id' => $id))) {
        return false;
    }
    // Delete any dependent records here.
    if ($congreafiles = $DB->get_records('congrea_files', array('vcid' => $congrea->id))) {
        $filepath = "{$CFG->dataroot}/congrea/{$congrea->course}/{$congrea->id}/";

        foreach($congreafiles as $cfile) {
            $vcsession = $cfile->vcsessionkey;
            $dir = $filepath.$vcsession;
            // Delete recorded files
            congrea_deleteDirectory($filepath);
        }
        $DB->delete_records('congrea_files', array('vcid' => $congrea->id));
    }
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
 * @return stdClass|null
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
 * @param stdClass $congrea instance object with extra cmidnumber and modname property
 * @param mixed optional array/object of grade(s); 'reset' means reset grades in gradebook
 * @return void
 */
function congrea_grade_item_update(stdClass $congrea, $grades=null) {
    return false;
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');

    /* @example */
    $item = array();
    $item['itemname'] = clean_param($congrea->name, PARAM_NOTAGS);
    $item['gradetype'] = GRADE_TYPE_VALUE;
    $item['grademax']  = $congrea->grade;
    $item['grademin']  = 0;

    grade_update('mod/congrea', $congrea->course, 'mod', 'congrea', $congrea->id, 0, null, $item);
}

/**
 * Update congrea grades in the gradebook
 *
 * Needed by grade_update_mod_grades() in lib/gradelib.php
 *
 * @param stdClass $congrea instance object with extra cmidnumber and modname property
 * @param int $userid update grade of specific user only, 0 means all participants
 * @return void
 */
function congrea_update_grades(stdClass $congrea, $userid = 0) {
    global $CFG, $DB;
    require_once($CFG->libdir.'/gradelib.php');

    $grades = array(); // Populate array of grade objects indexed by userid. @example .

    grade_update('mod/congrea', $congrea->course, 'mod', 'congrea', $congrea->id, 0, $grades);
}

/**
 * File API                                                                   //
 */

/**
 * Returns the lists of all browsable file areas within the given module context
 *
 * The file area 'intro' for the activity introduction field is added automatically
 * by {@link file_browser::get_file_info_context_module()}
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
 * Serves the files from the congrea file areas
 *
 * @package mod_congrea
 * @category files
 *
 * @param stdClass $course the course object
 * @param stdClass $cm the course module object
 * @param stdClass $context the congrea's context
 * @param string $filearea the name of the file area
 * @param array $args extra arguments (itemid, path)
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 */
function congrea_pluginfile($course, $cm, $context, $filearea, array $args, $forcedownload, array $options=array()) {
    global $DB, $CFG;

    if ($context->contextlevel != CONTEXT_MODULE) {
        send_file_not_found();
    }

    require_login($course, true, $cm);

    send_file_not_found();
}

/**
 * Delete recorded congrea file form storage
 *
 * @param string $dir path of directory where file store
 */
function congrea_deleteDirectory($dir) {
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
        if (!congrea_deleteDirectory($dir . "/" . $item)) {
            chmod($dir . "/" . $item, 0777);
            if (!congrea_deleteDirectory($dir . "/" . $item)) {
                return false;
            }
        };
    }
    return rmdir($dir);
}