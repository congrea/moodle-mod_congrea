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
 * Prints a particular instance of congrea
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod_congrea
 * @copyright  2020 vidyamantra.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once(dirname(__FILE__) . '/lib.php');
require_once(dirname(__FILE__) . '/locallib.php');
require_once(dirname(__FILE__) . '/edit_form.php');

$id = optional_param('id', 0, PARAM_INT); // Course_module ID.
$n = optional_param('n', 0, PARAM_INT); // Congrea instance ID - it should be named as the first character of the module.
$update = optional_param('update', ' ', PARAM_CLEANHTML); // Session name.
$sessionname = optional_param('sessionname', '', PARAM_CLEANHTML);
if ($id) {
    $cm = get_coursemodule_from_id('congrea', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $congrea = $DB->get_record('congrea', array('id' => $cm->instance), '*', MUST_EXIST);
} else if ($n) {
    $congrea = $DB->get_record('congrea', array('id' => $n), '*', MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $congrea->course), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('congrea', $congrea->id, $course->id, false, MUST_EXIST);
} else {
    print_error(get_string('invalidcmidorinsid', 'congrea'));
}
require_login($course, true, $cm);
$context = context_module::instance($cm->id);

// Print the page header.
$PAGE->set_url('/mod/congrea/edit.php', array('id' => $cm->id, 'update' => $update));
$PAGE->set_title(format_string($congrea->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

$room = !empty($course->id) && !empty($cm->id) ? $course->id . '_' . $cm->id : 0;
$key = get_config('mod_congrea', 'cgapi');
$mform = new mod_congrea_edit_name($CFG->wwwroot . '/mod/congrea/edit.php?id=' . $cm->id . '&update=' . $update);

if ($mform->is_cancelled()) {
    // Do nothing.
    redirect(new moodle_url('/mod/congrea/view.php', array('id' => $cm->id, 'psession' => true)));
} else if ($fromform = $mform->get_data()) {
    $sessionname = $fromform->name;
    $postdata = json_encode(array('room' => $room, 'name' => $sessionname, 'session' => $update));
    $result = curl_request("https://api.congrea.net/backend/updaterecordingname", $postdata, $key);
    $sucess = json_decode($result);
    $returnurl = redirect(new moodle_url('/mod/congrea/view.php', array('id' => $cm->id, 'psession' => true)));
    if ($sucess->data == "success") {
        $OUTPUT->notification($returnurl, get_string('updated', '', $sessionname, 'notifysucess'));
    } else {
        $OUTPUT->notification($returnurl, get_string('notupdated', '', $sessionname));
    }
}
// Output starts here.
echo $OUTPUT->header();
echo $OUTPUT->heading($congrea->name);
$data = new stdClass;
$data->name = $sessionname;
$mform->set_data($data);
$mform->display();
// Finish the page.
echo $OUTPUT->footer();
