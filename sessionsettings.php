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
 * Schedule congrea sessions
 *
 * @package    mod_congrea
 * @copyright  2020 vidyamantra.com

 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(dirname(dirname(__FILE__)))) . '/config.php';
require_once(dirname(__FILE__)) . '/lib.php';
require_once(dirname(__FILE__)) . '/locallib.php';
require_once(dirname(__FILE__)) . '/session_form.php';

$id = optional_param('id', 0, PARAM_INT); // Course_module ID.
$n = optional_param('n', 0, PARAM_INT);  // Congrea instance ID - TODO: check if it should be c.
// Is it q for quiz it should be named as the first character of the module.
$sessionsettings = optional_param('sessionsettings', 0, PARAM_INT);
$edit = optional_param('edit', 0, PARAM_INT);
$action = optional_param('action', ' ', PARAM_CLEANHTML);
$delete = optional_param('delete', 0, PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_INT);

if ($id) {
    $cm = get_coursemodule_from_id('congrea', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $congrea = $DB->get_record('congrea', array('id' => $cm->instance), '*', MUST_EXIST);
} else if ($n) {
    $congrea = $DB->get_record('congrea', array('id' => $n), '*', MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $congrea->course), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('congrea', $congrea->id, $course->id, false, MUST_EXIST);
} else {
    print_error('You must specify a course_module ID or an instance ID');
}

require_login($course, true, $cm);
$coursecontext = context_course::instance($course->id);
$context = context_module::instance($cm->id);
$returnurl = new moodle_url('/mod/congrea/sessionsettings.php', array('id' => $cm->id, 'sessionsettings' => true));
$settingsreturnurl = new moodle_url('/mod/congrea/sessionsettings.php', array('id' => $cm->id, 'action' => 'addsession'));

// Print the page header.
$PAGE->set_url('/mod/congrea/sessionsettings.php', array('id' => $cm->id, 'sessionsettings' => $sessionsettings));
$PAGE->set_title(format_string($congrea->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

// Start Delete Sessions.
if ($delete) {
    require_login($course, false, $cm);
    $submiturl = new moodle_url('/mod/congrea/sessionsettings.php', array('id' => $cm->id, 'sessionsettings' => $sessionsettings));
    $returnurl = new moodle_url('/mod/congrea/sessionsettings.php', array('id' => $cm->id, 'sessionsettings' => true));
    if ($confirm != $delete) {
        echo $OUTPUT->header();
        echo $OUTPUT->heading(format_string($congrea->name));
        $optionsyes = array('delete' => $delete, 'confirm' => $delete, 'sesskey' => sesskey());
        echo $OUTPUT->confirm(
            get_string('deleteschedule', 'mod_congrea'),
            new moodle_url($submiturl, $optionsyes),
            $returnurl
        );
        echo $OUTPUT->footer();
        die;
    } else if (data_submitted()) {
        if (has_capability('mod/congrea:managesession', $context) &&
        has_capability('moodle/calendar:manageentries', $coursecontext)) {
            $event = $DB->get_records('event', array('repeatid' => $delete));
            if (!empty($event)) {
                $DB->delete_records('event', array('modulename' => 'congrea', 'repeatid' => $delete));
            } else {
                $DB->delete_records('event', array('id' => $delete));
            }
        } else {
            echo $OUTPUT->notification(get_string('notcapabletocreateevent', 'congrea'));
        }
    }
} // End Delete Sessions.

$mform = new mod_congrea_session_form(null, array('id' => $id, 'sessionsettings' => $sessionsettings,
'edit' => $edit, 'action' => $action));

if ($mform->is_cancelled()) {
    // Do nothing.
    redirect(new moodle_url('/mod/congrea/sessionsettings.php', array('id' => $cm->id, 'sessionsettings' => true)));
} else if ($fromform = $mform->get_data()) {
    $data = new stdClass();
    $data->name = $congrea->name;
    $data->timestart = $fromform->fromsessiondate;
    $data->courseid = $COURSE->id;
    $data->groupid = 0;
    $data->userid = $fromform->moderatorid;
    $data->modulename = 'congrea';
    $data->instance = $congrea->id;
    $data->eventtype = 'session start'; // TODO.
    if ($fromform->timeduration == 0) {
        $data->timeduration = 0;
    } else {
        $durationinminutes = $fromform->timeduration;
        $timeduration = $durationinminutes * 60;
        $data->timeduration = $durationinminutes * 60;
        $endtime = $data->timestart + $data->timeduration;
    }
    if (!empty($fromform->addmultiple)) {
        $startdate = date('Y-m-d', $data->timestart);
        $days = date('D', strtotime($startdate));
        $data->description = $fromform->week . ' weeks/ ' . $days;
    } else { // Single Event.
        $data->repeatid = 0;
        $data->description = '-';

    }
    $presenter = $fromform->moderatorid;
    $congreaid = $congrea->id;

    if ($action == 'addsession') {
        if (has_capability('mod/congrea:managesession', $context) &&
        has_capability('moodle/calendar:manageentries', $coursecontext)) {
            $eventobject = calendar_event::create($data);
            $dataid = $eventobject->id; // TODO: -using api return id.
        } else {
            echo $OUTPUT->notification(get_string('notcapabletocreateevent', 'congrea'));
        }
    }
    // Create multiple sessions.
    if (!empty($fromform->addmultiple) && !$edit) {
        if ($fromform->week > 0) {
            $dataobject = new stdClass();
            $dataobject->repeatid = $dataid;
            $dataobject->id = $dataid;
            $DB->update_record('event', $dataobject);
            $day = date('D', $fromform->fromsessiondate);
            $weeks = $fromform->week;
            $upcomingdates = repeat_date_list($fromform->fromsessiondate, $weeks);
            foreach ($upcomingdates as $startdate) {
                repeat_calendar($congrea, $data, $startdate, $presenter, $dataobject->id, $weeks);
            }
        }
    } // End create multiple sessions.
    // Update sessions.
    if ($edit && $fromform->submitbutton == "Save changes") {
        if (has_capability('mod/congrea:managesession', $context) &&
        has_capability('moodle/calendar:manageentries', $coursecontext)) {
            $eventobject = calendar_event::create($data);
            $dataid = $eventobject->id; // TODO: -using api return id.
            if (!empty($fromform->addmultiple)) {
                if ($fromform->week > 1) {
                    $dataobject = new stdClass();
                    $dataobject->repeatid = $dataid;
                    $dataobject->id = $dataid;
                    $DB->update_record('event', $dataobject);
                    $day = date('D', $fromform->fromsessiondate);
                    $weeks = $fromform->week;
                    $upcomingdates = repeat_date_list($fromform->fromsessiondate, $weeks);
                    foreach ($upcomingdates as $startdate) {
                        repeat_calendar($congrea, $data, $startdate, $presenter, $dataobject->id, $weeks);
                    }
                    $DB->delete_records('event', array('modulename' => 'congrea', 'repeatid' => $edit));
                }
            }
            // Single sessions.
            $DB->delete_records('event', array('modulename' => 'congrea', 'id' => $edit));
            $DB->delete_records('event', array('modulename' => 'congrea', 'repeatid' => $edit));
        } else {echo $OUTPUT->notification(get_string('notcapabletocreateevent', 'congrea'));
            
        }
    }
    redirect($returnurl);
} // Else if end, end reading data from form.

// Output starts here.
echo $OUTPUT->header();
echo $OUTPUT->heading($congrea->name);
if (!empty($sessionsettings)) {
    $currenttab = 'sessionsettings';
}
congrea_print_tabs($currenttab, $context, $cm, $congrea);

if (has_capability('mod/congrea:managesession', $context) && has_capability('moodle/calendar:manageentries', $coursecontext)) {
    $options = array();
    if ($sessionsettings && !$edit && !($action == 'addsession')) {
        echo $OUTPUT->single_button(
            $returnurl->out(
            true,
            array('action' => 'addsession', 'cmid' => $cm->id)
            ),
            get_string('addsessions', 'congrea'),
            'get',
            $options
        );
        echo html_writer::start_tag('br');
    }
} else {
    echo $OUTPUT->notification(get_string('notcapabletocreateevent', 'congrea'));
}
echo html_writer::start_tag('br');

// Editing an existing session.
if ($edit) {
    if (has_capability('mod/congrea:managesession', $context) && has_capability('moodle/calendar:manageentries', $coursecontext)) {
        echo html_writer::start_tag('div', array('class' => 'overflow'));
        $table = new html_table();
        $record = $DB->get_record('event', array('id' => $edit));
        $row = array();
        if ($record->timeduration == 0) {
            $row[] = 'Editing schedule: <strong>' . userdate($record->timestart) . '</strong>';
            $timeduration = $record->timeduration;
        } else {        
            $row[] = 'Editing schedule: <strong>' . userdate($record->timestart). ' to ' .
            userdate(($record->timestart + $record->timeduration / 60), '%I:%M %p') . '</strong>';
            $timeduration = $record->timeduration;
        }
        $table->data[] = $row;
        echo html_writer::table($table);
        echo html_writer::end_tag('div');
        $recordlist = $DB->get_records('event', array('repeatid' => $edit));
        if (!empty($recordlist)) {
            sort($recordlist);
            $weeks = count($recordlist);
            // Editing multiple sessions.
            foreach ($recordlist as $record) {
                if (($record->timestart + $record->timeduration) < time()) {
                    $record->repeatid = 0;
                    $record->description = '-';
                    $DB->update_record('event', $record);
                    $weeks--;
                    continue;
                } else {
                    $formdata = new stdClass;
                    $formdata->fromsessiondate = $record->timestart;
                    $formdata->timeduration = $record->timeduration / 60;
                    $formdata->week = $weeks;
                    $formdata->addmultiple = 1;
                    $formdata->moderatorid = $record->userid;
                    $mform->set_data($formdata);
                    $mdata = $mform->get_data();
                    break;
                }
            }
        } else {
            // Editing single session.    
            $record = $DB->get_record('event', array('id' => $edit));
            $formdata = new stdClass;
            $formdata->fromsessiondate = $record->timestart;
            $formdata->timeduration = $record->timeduration / 60;
            $formdata->week = intval($record->description);
            $formdata->addmultiple = 0;
            $formdata->week = 1;
            $formdata->moderatorid = $record->userid;
            $mform->set_data($formdata);
            $mdata = $mform->get_data();
        }
    } else {
        echo $OUTPUT->notification(get_string('notcapabletocreateevent', 'congrea'));
    }
} // end if $edit

if ($action == 'addsession' || $edit ) {
    if (has_capability('mod/congrea:managesession', $context) && has_capability('moodle/calendar:manageentries', $coursecontext)) {
            $mform->display();
    } else {
        echo $OUTPUT->notification(get_string('notcapabletocreateevent', 'congrea'));
    }
}

// Display schedule table.
echo $OUTPUT->heading('Schedules');
if (has_capability('mod/congrea:managesession', $context) && has_capability('moodle/calendar:manageentries', $coursecontext)) {
    $table = new html_table();
    $table->head = array('Date and time of first session', 'Session duration', 'Teacher', 'Repeat for', 'Action');
    $sessionlist = $DB->get_records('event',
    array('modulename' => 'congrea', 'courseid' => $course->id, 'instance' => $congrea->id));
    usort($sessionlist, "compare_dates_scheduled_list");
    $currenttime = time();
    if (!empty($sessionlist)) {
        foreach ($sessionlist as $dummysession) {
            if ($dummysession->timeduration == 0) {
                $infinitesessions = $dummysession; // Collecting Infinte sessions.
            }
        }
        foreach ($sessionlist as $dummysession) {
            $timestart = ($dummysession->timestart + $dummysession->timeduration);
            if (($timestart < $currenttime) && ($dummysession->repeatid == 0)) { // Past sessions.
                $pastsessions = $dummysession;
                continue;
            }
            $timedsessions = $dummysession;
        }
    }
    if(!empty($infinitesessions)) {
        $cmid = $cm->id;
        $buttons = array();
        $row = array();
        $row[] = userdate($infinitesessions->timestart);
        $row[] = get_string('infinitesession', 'congrea');
        $moderatorid = $DB->get_record('user', array('id' => $infinitesessions->userid));
        if (!empty($moderatorid)) {
            $username = $moderatorid->firstname . ' ' . $moderatorid->lastname; // Todo-for function.
        } else {
            $username = get_string('nouser', 'mod_congrea');
        }
        $row[] = $username;
        $row[] = $infinitesessions->description;
        $buttons[] = html_writer::link(
            new moodle_url(
            '/mod/congrea/sessionsettings.php',
            array('id' => $cmid, 'edit' => $infinitesessions->id, 'sessionsettings' => $sessionsettings)
            ),
            'Edit',
            array('class' => 'actionlink exportpage')
        );
        //}
        $buttons[] = html_writer::link(
            new moodle_url(
                '/mod/congrea/sessionsettings.php',
                array('id' => $cmid, 'delete' => $infinitesessions->id, 'sessionsettings' => $sessionsettings)
            ),
            'Delete',
            array('class' => 'actionlink exportpage')
        );
        $row[] = implode(' ', $buttons);
        $table->data[] = $row;
    }
    var_dump($timedsessions);
    
    if(!empty($timedsessions)) {
        if (($timedsessions->id == $timedsessions->repeatid) || ($timedsessions->repeatid == 0)) {
            $buttons = array();
            $row = array();
            $row[] = userdate($timedsessions->timestart);
            $row[] = ($timedsessions->timeduration / 60) . ' ' . 'mins';
            $moderatorid = $DB->get_record('user', array('id' => $timedsessions->userid));
            if (!empty($moderatorid)) {
                $username = $moderatorid->firstname . ' ' . $moderatorid->lastname; // Todo-for function.
            } else {
                $username = get_string('nouser', 'mod_congrea');
            }
            $row[] = $username;
            $row[] = $timedsessions->description;
            if ($timedsessions->timeduration < 86400) {
                $buttons[] = html_writer::link(
                    new moodle_url(
                    '/mod/congrea/sessionsettings.php',
                    array('id' => $cmid, 'edit' => $timedsessions->id, 'sessionsettings' => $sessionsettings)
                    ),
                    'Edit',
                    array('class' => 'actionlink exportpage')
                );
            }
            $buttons[] = html_writer::link(
                new moodle_url(
                    '/mod/congrea/sessionsettings.php',
                    array('id' => $cmid, 'delete' => $timedsessions->id, 'sessionsettings' => $sessionsettings)
                ),
                'Delete',
                array('class' => 'actionlink exportpage')
            );
            $row[] = implode(' ', $buttons);
            $table->data[] = $row;
        }
    }
}
if (!empty($table->data)) {
    echo html_writer::start_tag('div', array('class' => 'no-overflow'));
    echo html_writer::table($table);
    echo html_writer::start_tag('br');
    echo html_writer::end_tag('div');
} else {
    echo $OUTPUT->notification(get_string('nosession', 'mod_congrea'));
}
/* } else {
    echo $OUTPUT->notification(get_string('notcapabletoviewschedules', 'congrea'));
} */
// Finish the page.
echo $OUTPUT->footer();