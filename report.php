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
 * Upload and save recorded file of congrea session
 *
 *
 * @package    mod_congrea
 * @copyright  2014 Pinky Sharma
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once(dirname(__FILE__) . '/lib.php');
require_once(dirname(__FILE__) . '/locallib.php');
//require_once(dirname(__FILE__) . '/view.php');

$id = optional_param('cmid', 0, PARAM_INT); // Course_module ID.
$sessionid = optional_param('session', '',  PARAM_CLEANHTML);   // Md5 confirmation hash.
$sessionname = optional_param('sessionname', '',  PARAM_CLEANHTML);   // Md5 confirmation hash.
if ($id) {
    $cm = get_coursemodule_from_id('congrea', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $congrea = $DB->get_record('congrea', array('id' => $cm->instance), '*', MUST_EXIST);
} else {
    print_error('You must specify a course_module ID or an instance ID');
}

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
// Print the page header.
$PAGE->set_url('/mod/congrea/report.php', array('cmid' => $id, 'session'=> $sessionid, 'sessionname' => $sessionname));
$PAGE->set_title(format_string($congrea->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);
$authusername = substr(str_shuffle(md5(microtime())), 0, 20);
$authpassword = substr(str_shuffle(md5(microtime())), 0, 20);
$key = get_config('mod_congrea', 'cgapi');
$secret = get_config('mod_congrea', 'cgsecretpassword');
$room = !empty($course->id) && !empty($cm->id) ? $course->id . '_' . $cm->id : 0;
$apiurl = 'https://api.congrea.net/t/analytics/attendance';
// Output starts here.
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('sessionareport', 'mod_congrea'));
$data = attendence_curl_request($apiurl, $sessionid, $key, $authpassword, $authusername, $room);
$attendencestatus = json_decode($data);
echo '<pre>'; print_r($attendencestatus);
// Finish the page.
echo $OUTPUT->footer();
