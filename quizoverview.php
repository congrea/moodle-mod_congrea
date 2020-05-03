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
 * Display Quiz Overview
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
$PAGE->set_url('/mod/congrea/quizoverview.php', array('cmid' => $cm->id));
$PAGE->set_title(format_string($congrea->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('quizheading', 'congrea'));
$sql = "SELECT q.id As mquizid, q.name, cq.id
                FROM {congrea_quiz} cq
                INNER JOIN
                    {quiz} q
                ON cq.quizid = q.id where congreaid = $cm->instance";
$quizdata = $DB->get_records_sql($sql);

if (!empty($quizdata)) {
    $table = new html_table();
    $table->head = array(get_string('quizname', 'congrea'), get_string('users', 'congrea'));
    foreach ($quizdata as $data) {
        $quizname = html_writer::link(new moodle_url('/mod/congrea/quizreport.php?cmid=' . $cm->id,
                            array('quizid' => $data->id, 'mquizid' => $data->mquizid)), $data->name);
        if ($data->id) {
            $sql = "SELECT count(userid) from {congrea_quiz_grade} where congreaquiz = ?";
            $totalusers = $DB->count_records_sql($sql, array('congreaquiz' => $data->id));
            if (!empty($totalusers)) {
                $users = $totalusers;
            } else {

                $users = 0;
            }
        }
        $table->data[] = array($quizname, $users);
    }
    if (!empty($table)) {
        echo html_writer::table($table);
    }
} else {
    echo $OUTPUT->notification(get_string('noquizreport', 'congrea'));
}
echo $OUTPUT->footer();
