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
 * Display Poll Report
 *
 * @package    mod_congrea
 * @copyright  2020 vidyamantra.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once(dirname(__FILE__) . '/lib.php');

$id = optional_param('cmid', 0, PARAM_INT);
$questionid = optional_param('questionid', 0, PARAM_INT);
if ($id) {
    $cm = get_coursemodule_from_id('congrea', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $congrea = $DB->get_record('congrea', array('id' => $cm->instance), '*', MUST_EXIST);
} else {
    print_error(get_string('invalidcmidorinsid', 'congrea'));
}
require_login($course, true, $cm);

$context = context_module::instance($cm->id);
$PAGE->set_url('/mod/congrea/pollreport.php', array('cmid' => $cm->id, 'questionid' => $questionid));
$PAGE->set_title(format_string($congrea->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->navbar->add('Poll overview', new moodle_url('/mod/congrea/polloverview.php?cmid=' . $cm->id));
$PAGE->set_context($context);
echo $OUTPUT->header();
if ($questionid) {
    $questionname = $DB->get_field('congrea_poll', 'pollquestion', array('id' => $questionid));
    echo $OUTPUT->heading(get_string('pollquestionis', 'congrea') . $questionname);
    $sql = "SELECT id, options from {congrea_poll_question_option} where qid = $questionid";
    $optiondata = $DB->get_records_sql($sql);
    foreach ($optiondata as $data) {
        $sql = "SELECT count(userid) from {congrea_poll_attempts} where optionid = ?";
        $userid = $DB->count_records_sql($sql, array('optionid' => $data->id));
        if ($userid > 0) {
            $graphdata[] = array($data->options, $userid);
        }
    }
} else {
    echo $OUTPUT->notification(get_string('noreport', 'congrea'));
}

if (!empty($graphdata)) {
    $title = array('0' => array('option', 'percentage'));
    $finalgraphdata = array_merge($title, $graphdata);
    $jsondata = json_encode($finalgraphdata);
    echo '<script>';
    echo "var myvalue = " . $jsondata . ';';
    echo '</script>';
    ?>
    <html>
        <head>
            <link type="text/css" href="congrea/styles.css">
            <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
            <script type="text/javascript">

                google.charts.load('current', {'packages': ['corechart']});
                google.charts.setOnLoadCallback(drawChart);
                function drawChart() {

                    var data = google.visualization.arrayToDataTable(myvalue);

                    var options = {
                        title: 'Voted So Far'
                    };

                    var chart = new google.visualization.PieChart(document.getElementById('piechart'));

                    chart.draw(data, options);
                }
            </script>
        </head>
        <body>
            <div id="piechart" style="width: 900px; height: 500px;"></div>
        </body>
    </html>

<?php
}
if (!empty($questionid) && !empty($optiondata)) {
    $sql = "SELECT cpa.*, u.firstname, u.lastname, u.email
                FROM {congrea_poll_attempts} cpa
                INNER JOIN
                    {user} u
                ON cpa.userid = u.id where qid = '" . $questionid . "'";
    $userdata = $DB->get_records_sql($sql);
    if (!empty($userdata)) {
        $table = new html_table();
        $table->head = array(get_string('username', 'congrea'), get_string('email', 'congrea'), get_string('options', 'congrea'));
        foreach ($userdata as $userinfo) {
            $username = $userinfo->firstname . ' ' . $userinfo->lastname;
            $email = $userinfo->email;
            if (!empty($optiondata[$userinfo->optionid]->options)) {
                $option = $optiondata[$userinfo->optionid]->options;
                $table->data[] = array($username, $email, $option);
            }
        }
        if (!empty($table)) {
            echo html_writer::table($table);
        }
    } else {
        echo $OUTPUT->notification(get_string('noattempt', 'congrea'));
    }
}
echo $OUTPUT->footer();