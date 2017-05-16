<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Display Poll Overview
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
$PAGE->set_url('/mod/congrea/polloverview.php', array('cmid' => $cm->id));
$PAGE->set_title(format_string($congrea->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);
echo $OUTPUT->header();
echo $OUTPUT->heading('Poll Overview');
$sql = "SELECT id, description, timecreated from {congrea_poll_question} where cmid = $id";
$questiondata = $DB->get_records_sql($sql);

if (!empty($questiondata)) {
    $table = new html_table();
    $table->head = array('Poll Questions', 'Users', 'Time');
    foreach ($questiondata as $data) {
        //$questionname = $data->description;
        $questionname = html_writer::link(new moodle_url('/mod/congrea/pollreport.php?cmid=' . $cm->id, array('questionid' => $data->id)), $data->description);
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
