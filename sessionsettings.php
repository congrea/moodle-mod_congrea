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
    print_error(get_string('invalidcmidorinsid', 'congrea'));
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
        $events = $DB->get_records('event', array('repeatid' => $delete));
        if (!empty($events)) {
            foreach ($events as $event) {
                if (($event->timestart + $event->timeduration) < time()) {
                    $dataupdate = new stdClass();
                    $dataupdate->id = $event->id;
                    $dataupdate->repeatid = 0;
                    $DB->update_record('event', $dataupdate);
                } else {
                    $DB->delete_records('event', array('modulename' => 'congrea', 'repeatid' => $delete));
                }
            }
        } else {
            $DB->delete_records('event', array('id' => $delete));
        }
    }
} // End Delete Sessions.

$mform = new mod_congrea_session_form(null, array('id' => $id, 'sessionsettings' => $sessionsettings,
'edit' => $edit, 'action' => $action));

$sessionlist = $DB->get_records('event', array('modulename' => 'congrea', 'courseid' => $course->id, 'instance' => $congrea->id));
usort($sessionlist, "compare_dates_scheduled_list");
$currenttime = time();
if (!empty($sessionlist)) {
    foreach ($sessionlist as $dummysession) {
        if ($dummysession->timeduration == 0) {
            $infinitesessions[] = $dummysession; // Collecting Infinite sessions.
        } else {
            $timestart = ($dummysession->timestart + $dummysession->timeduration);
            if (($timestart < $currenttime) && ($dummysession->repeatid == 0)) { // Past sessions.
                $pastsessions[] = $dummysession;
                continue;
            }
            $timedsessions[] = $dummysession; // Collecting Timed sessions.
        }
    }
}
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
        $data->repeadid = 0;
    } else {
        $durationinminutes = $fromform->timeduration;
        $timeduration = $durationinminutes * 60;
        $data->timeduration = $durationinminutes * 60;
        $endtime = $data->timestart + $data->timeduration;
    }
    if (!empty($fromform->addmultiple)) {
        $startdate = date('Y-m-d', $data->timestart);
        $days = date('D', strtotime($startdate));
        $data->description = $fromform->week . get_string('repeatedweeks', 'congrea') . $days;
    } else { // Single Event.
        $data->repeatid = 0;
        $data->description = '-';
    }
    $presenter = $fromform->moderatorid;
    $congreaid = $congrea->id;

    if ($action == 'addsession') {
        if (has_capability('mod/congrea:managesession', $context) &&
        has_capability('moodle/calendar:manageentries', $coursecontext)) {
            if (!empty($timedsessions)) {
                if ($fromform->timeduration == 0) {
                    \core\notification::error(get_string('onlysingleinfinite', 'congrea'));
                } else {
                    $eventobject = calendar_event::create($data);
                    $dataid = $eventobject->id; // TODO: -using api return id.
                }
            } else {
                if (!empty($infinitesessions)) {
                    \core\notification::info(get_string('onlysingleinfinite', 'congrea'));
                } else {
                    $eventobject = calendar_event::create($data);
                    $dataid = $eventobject->id; // TODO: -using api return id.
                }
            }
        } else {
            \core\notification::warning(get_string('notcapabletocreateevent', 'congrea'));
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
            if (!empty($timedsessions)) {
                if ($fromform->timeduration != 0) {
                    $eventobject = calendar_event::create($data);
                    $dataid = $eventobject->id; // TODO: -using api return id.
                    if (!empty($fromform->addmultiple)) {
                        $eventobject = calendar_event::create($data);
                        $dataid = $eventobject->id; // TODO: -using api return id.
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
                } else {
                    \core\notification::error(get_string('onlysingleinfinite', 'congrea'));
                }
            } else {
                $eventobject = calendar_event::create($data);
                $dataid = $eventobject->id; // TODO: -using api return id.
                if (!empty($fromform->addmultiple)) {
                    $eventobject = calendar_event::create($data);
                    $dataid = $eventobject->id; // TODO: -using api return id.
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
                $DB->delete_records('event', array('modulename' => 'congrea', 'id' => $edit));
            }
        } else {
            \core\notification::warning(get_string('notcapabletocreateevent', 'congrea'));
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
    if (empty($infinitesessions)) { // then for timedsessions will not work. Should work ok
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
    }
} else {
    \core\notification::warning(get_string('notcapabletocreateevent', 'congrea'));
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
            $row[] = get_string('editingsession', 'congrea') . userdate($record->timestart);
            $timeduration = $record->timeduration;
        } else {
            $row[] = get_string('editingsession', 'congrea') . userdate($record->timestart) .
            get_string('to', 'congrea') .
            userdate(($record->timestart + $record->timeduration), '%I:%M %p');
            $timeduration = $record->timeduration;
        }
        $table->data[] = $row;
        echo html_writer::table($table);
        echo html_writer::end_tag('div');
        if ($record->timeduration == 0) {
            $record = $DB->get_record('event', array('id' => $edit));
            $formdata = new stdClass;
            $formdata->fromsessiondate = $record->timestart;
            $formdata->timeduration = $record->timeduration;
            $formdata->addmultiple = 0;
            $formdata->moderatorid = $record->userid;
            $mform->set_data($formdata);
            $ch = $mdata = $mform->get_data();
        } else {
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
                        $formdata->addmultiple = 1;
                        $formdata->week = $weeks;
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
        }
    }
} // End if $edit.

if ($action == 'addsession' || $edit ) {
    if (has_capability('mod/congrea:managesession', $context) && has_capability('moodle/calendar:manageentries', $coursecontext)) {
        echo $OUTPUT->box_start();
        $mform->display();
        echo $OUTPUT->box_end();
        // Add a notification of some kind.
        \core\notification::info(get_string('informationtocreatesession', 'congrea'));
    } else {
        // Add a notification of some kind.
        \core\notification::warning(get_string('notcapabletocreateevent', 'congrea'));
    }
}

// Display schedule table.
echo $OUTPUT->heading(get_string('headingschedules', 'congrea'));
if (has_capability('mod/congrea:managesession', $context) && has_capability('moodle/calendar:manageentries', $coursecontext)) {
    $table = new html_table();
    $table->head = array(get_string('datetimelist', 'congrea'), get_string('sessduration', 'congrea'),
    get_string('teacher', 'congrea'), get_string('repeatstatus', 'congrea'),
    get_string('action', 'congrea'));
    if (!empty($infinitesessions)) {
        foreach ($infinitesessions as $infinitesession) {
            $buttons = array();
            $row = array();
            $row[] = userdate($infinitesession->timestart);
            $row[] = get_string('infinitesession', 'congrea');
            $moderatorid = $DB->get_record('user', array('id' => $infinitesession->userid));
            if (!empty($moderatorid)) {
                $username = $moderatorid->firstname . ' ' . $moderatorid->lastname; // Todo-for function.
            } else {
                $username = get_string('nouser', 'mod_congrea');
            }
            $row[] = $username;
            $row[] = $infinitesession->description;
            $buttons[] = html_writer::link(
                new moodle_url(
                    '/mod/congrea/sessionsettings.php',
                    array('id' => $cm->id, 'edit' => $infinitesession->id, 'sessionsettings' => $sessionsettings)
                ),
                get_string('editbtn', 'congrea'),
                array('class' => 'actionlink exportpage')
            );
            $buttons[] = html_writer::link(
                new moodle_url(
                    '/mod/congrea/sessionsettings.php',
                    array('id' => $cm->id, 'delete' => $infinitesession->id, 'sessionsettings' => $sessionsettings)
                ),
                get_string('deletebtn', 'congrea'),
                array('class' => 'actionlink exportpage')
            );
            $row[] = implode(' ', $buttons);
            $table->data[] = $row;
        }
    }
    if (!empty($timedsessions)) {
        foreach ($timedsessions as $list) {
            if (($list->id == $list->repeatid) || ($list->repeatid == 0)) {
                $buttons = array();
                $row = array();
                $row[] = userdate($list->timestart);
                $timestart = ($list->timestart + $list->timeduration);
                if (($timestart < $currenttime) && ($list->repeatid == 0)) { // Past sessions.
                    $pastsessions[] = $list;
                    continue;
                }
                if ($list->timeduration > 86400) {
                    $row[] = get_string('legacysession', 'congrea');
                    $row[] = userdate($list->timeduration);
                } else if ($list->timeduration != 0) {
                    $row[] = ($list->timeduration / 60) . ' ' . 'mins';
                }
                $moderatorid = $DB->get_record('user', array('id' => $list->userid));
                if (!empty($moderatorid)) {
                    $username = $moderatorid->firstname . ' ' . $moderatorid->lastname; // Todo-for function.
                } else {
                    $username = get_string('nouser', 'mod_congrea');
                }
                $row[] = $username;
                $row[] = $list->description;
                if ($list->timeduration < 86400) {
                    $buttons[] = html_writer::link(
                            new moodle_url(
                                '/mod/congrea/sessionsettings.php',
                                array('id' => $cm->id, 'edit' => $list->id, 'sessionsettings' => $sessionsettings)
                            ),
                            get_string('editbtn', 'congrea'),
                            array('class' => 'actionlink exportpage')
                    );
                }
                $buttons[] = html_writer::link(
                    new moodle_url(
                        '/mod/congrea/sessionsettings.php',
                        array('id' => $cm->id, 'delete' => $list->id, 'sessionsettings' => $sessionsettings)
                    ),
                    get_string('deletebtn', 'congrea'),
                    array('class' => 'actionlink exportpage')
                );
                $row[] = implode(' ', $buttons);
                $table->data[] = $row;
            }
        }
    }
    if (!empty($table->data)) {
        echo html_writer::start_tag('div', array('class'    => 'no-overflow'));
        echo html_writer::table($table);
        echo html_writer::start_tag('br');
        echo html_writer::end_tag('div');
    } else {
        echo $OUTPUT->notification(get_string('nosession',  'mod_congrea'));
    }
} else {
    \core\notification::warning(get_string('notcapabletoviewschedules', 'congrea'));
}
// Finish the page.
echo $OUTPUT->footer();