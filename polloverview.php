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
 * Display Poll Overview
 *
 * @package    mod_congrea
 * @copyright  2020 vidyamantra.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once(dirname(__FILE__) . '/lib.php');
$id = optional_param('cmid', 0, PARAM_INT);
if ($id) {
    $cm = get_coursemodule_from_id('congrea', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $congrea = $DB->get_record('congrea', array('id' => $cm->instance), '*', MUST_EXIST);
} else {
    print_error(get_string('invalidcmidorinsid', 'congrea'));
}

require_login($course, true, $cm);

$context = context_module::instance($cm->id);
$PAGE->set_url('/mod/congrea/polloverview.php', array('cmid' => $cm->id));
$PAGE->set_title(format_string($congrea->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('polloverview', 'congrea'));
$sql = "SELECT id, pollquestion, timecreated from {congrea_poll} where instanceid = $cm->instance";
$questiondata = $DB->get_records_sql($sql);

if (!empty($questiondata)) {
    $table = new html_table();
    $table->head = array(get_string('pollquestions', 'congrea'),
    get_string('users', 'congrea'), get_string('timetaken', 'congrea'));
    foreach ($questiondata as $data) {
        $questionname = html_writer::link(new moodle_url('/mod/congrea/pollreport.php?cmid=' . $cm->id,
                                            array('questionid' => $data->id)), $data->pollquestion);
        if ($data->id) {
            $sql = "SELECT count(userid) from {congrea_poll_attempts} where qid = ?";
            $totalusers = $DB->count_records_sql($sql, array('qid' => $data->id));
            if (!empty($totalusers)) {
                $users = $totalusers;
            } else {

                $users = 0;
            }
        }
        $time = userdate($data->timecreated);
        $table->data[] = array($questionname, $users, $time);
    }
    if (!empty($table)) {
        echo html_writer::table($table);
    }
} else {
    echo $OUTPUT->notification(get_string('noreport', 'congrea'));
}
echo $OUTPUT->footer();