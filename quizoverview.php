<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Display Quiz Overview
 *
 * @package    mod_congrea
 * @copyright  2017 Ravi Kumar
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
    print_error('You must specify a course_module ID or an instance ID');
}

require_login($course, true, $cm);

$context = context_module::instance($cm->id);
$PAGE->set_url('/mod/congrea/quizoverview.php', array('cmid' => $cm->id));
$PAGE->set_title(format_string($congrea->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);
echo $OUTPUT->header();
echo $OUTPUT->heading('Congrea Quiz  Overview');
$sql = "SELECT q.id As mquizid, q.name, cq.id
                FROM {congrea_quiz} cq
                INNER JOIN
                    {quiz} q
                ON cq.quizid = q.id where congreaid = $cm->instance";
$quizdata = $DB->get_records_sql($sql);

if (!empty($quizdata)) {
    $table = new html_table();
    $table->head = array('Quizname', 'Users');
    foreach ($quizdata as $data) {
        $quizname = html_writer::link(new moodle_url('/mod/congrea/quizreport.php?cmid=' . $cm->id, array('quizid' => $data->id, 'mquizid' => $data->mquizid)), $data->name);
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
