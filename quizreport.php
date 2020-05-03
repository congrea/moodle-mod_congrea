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
 * Display Quiz Report
 *
 * @package    mod_congrea
 * @copyright  2020 vidyamantra.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once(dirname(__FILE__) . '/lib.php');

$id = optional_param('cmid', 0, PARAM_INT);
$quizid = optional_param('quizid', 0, PARAM_INT);
$mquizid = optional_param('mquizid', 0, PARAM_INT);
if ($id) {
    $cm = get_coursemodule_from_id('congrea', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $congrea = $DB->get_record('congrea', array('id' => $cm->instance), '*', MUST_EXIST);
} else {
    print_error(get_string('invalidcmidorinsid', 'congrea'));
}
require_login($course, true, $cm);

$context = context_module::instance($cm->id);
$PAGE->set_url('/mod/congrea/quizreport.php', array('cmid' => $cm->id, 'quizid' => $quizid, 'mquizid' => $mquizid));
$PAGE->set_title(format_string($congrea->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->navbar->add('Quiz overview', new moodle_url('/mod/congrea/quizoverview.php?cmid=' . $cm->id));
$PAGE->set_context($context);
echo $OUTPUT->header();
if (!empty($quizid)&& !empty($mquizid)) {
    $quizname = $DB->get_field('quiz', 'name', array('id' => $mquizid));
    echo $OUTPUT->heading("$quizname-Report");
    $sql = "SELECT cqg.*, u.firstname, u.lastname, u.email
                FROM {congrea_quiz_grade} cqg
                INNER JOIN
                    {user} u
                ON cqg.userid = u.id where congreaquiz = '" . $quizid . "'";
    $userdata = $DB->get_records_sql($sql);
    if (!empty($userdata)) {
        $table = new html_table();
        $table->head = array(get_string('username', 'congrea'), get_string('email', 'congrea'),
        get_string('timetaken', 'congrea'), get_string('grade', 'congrea'),
        get_string('qattempted', 'congrea'), get_string('correct', 'congrea'),
        get_string('timetaken', 'congrea'));
        foreach ($userdata as $userinfo) {
            $username = $userinfo->firstname . ' ' . $userinfo->lastname;
            $email = $userinfo->email;
            $timetaken = gmdate("H:i:s", $userinfo->timetaken);
            $grade = $userinfo->grade.'%';
            $qattempted = $userinfo->questionattempted;
            $correct = $userinfo->currectanswer;
            $time = userdate($userinfo->timecreated);
            $table->data[] = array($username, $email, $timetaken, $grade, $qattempted, $correct, $time);
        }
        if (!empty($table)) {
            echo html_writer::table($table);
        }
    } else {
        echo $OUTPUT->notification(get_string('noquizattempt', 'congrea'));
    }
}
echo $OUTPUT->footer();
