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

$currenttime = time();
$infinitesession = $DB->get_record('event', array('instance' => $congrea->id, 'modulename' => 'congrea', 'timeduration' => 0));
$timedsessionssql = "SELECT * from {event}" .
" where instance = $congrea->id and modulename = 'congrea' and (timeduration != 0 and timeduration < 86400)";
$timedsessions = $DB->get_records_sql($timedsessionssql);
$legacysessionsql = "SELECT * from {event}" . " where instance = $congrea->id and modulename = 'congrea' and timeduration > 86400";
$legacysession = $DB->get_record_sql($legacysessionsql);
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
        if ($durationinminutes >= 86400) {
            $data->timeduration = 'Legacy session';
        } else {
            $data->timeduration = $durationinminutes * 60;
        }
        $endtime = $data->timestart + $data->timeduration;
    }
    if (!empty($fromform->addmultiple)) {
        $startdate = date('Y-m-d', $data->timestart);
        $data->description = $fromform->week . get_string('repeatedweeks', 'congrea');
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
                if (!empty($infinitesession)) {
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
    if (empty($infinitesession) && empty($legacysession)) {
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
            userdate(($record->timestart + $record->timeduration));
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
        $mform->display();
        \core\notification::info(get_string('informationtocreatesession', 'congrea'));
    } else {
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
    if (!empty($infinitesession)) {
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
    if (!empty($legacysession)) {
        $buttons = array();
        $row = array();
        $row[] = userdate($legacysession->timestart);
        $row[] = get_string('legacysession', 'congrea');
        $moderatorid = $DB->get_record('user', array('id' => $legacysession->userid));
        if (!empty($moderatorid)) {
            $username = $moderatorid->firstname . ' ' . $moderatorid->lastname; // Todo-for function.
        } else {
            $username = get_string('nouser', 'mod_congrea');
        }
        $row[] = $username;
        $row[] = userdate($legacysession->timestart + $legacysession->timeduration);
        $buttons[] = html_writer::link(
            new moodle_url(
                '/mod/congrea/sessionsettings.php',
                array('id' => $cm->id, 'delete' => $legacysession->id, 'sessionsettings' => $sessionsettings)
            ),
            get_string('deletebtn', 'congrea'),
            array('class' => 'actionlink exportpage')
        );
        $row[] = implode(' ', $buttons);
        $table->data[] = $row;
    }
    if (!empty($timedsessions)) {
        array_multisort(array_column($timedsessions, 'timestart'), SORT_DESC, $timedsessions);
        foreach ($timedsessions as $timedsession) {
            $timeend = ($timedsession->timestart + $timedsession->timeduration);
            if (($timeend < $currenttime) && ($timedsession->repeatid == 0)) { // Past sessions.
                $pastsessions = $timedsession;
                continue;
            }
            if (($timedsession->id == $timedsession->repeatid) || ($timedsession->repeatid == 0)) {
                $buttons = array();
                $row = array();
                $row[] = userdate($timedsession->timestart);
                if ($timedsession->timeduration != 0) {
                    $row[] = ($timedsession->timeduration / 60) . ' ' . get_string('mins', 'congrea');
                }
                $moderatorid = $DB->get_record('user', array('id' => $timedsession->userid));
                if (!empty($moderatorid)) {
                    $username = $moderatorid->firstname . ' ' . $moderatorid->lastname; // Todo-for function.
                } else {
                    $username = get_string('nouser', 'mod_congrea');
                }
                $row[] = $username;
                if ($timedsession->repeatid == 0) {
                    $row[] = '-';
                } else {
                    $days = date('D', ($timedsession->timestart));
                    $row[] = intval($timedsession->description) .
                    get_string('weeksevery', 'congrea') .
                    get_string(strtolower($days), 'calendar');
                }
                $buttons[] = html_writer::link(
                        new moodle_url(
                            '/mod/congrea/sessionsettings.php',
                            array('id' => $cm->id, 'edit' => $timedsession->id, 'sessionsettings' => $sessionsettings)
                        ),
                        get_string('editbtn', 'congrea'),
                        array('class' => 'actionlink exportpage')
                );
                $buttons[] = html_writer::link(
                    new moodle_url(
                        '/mod/congrea/sessionsettings.php',
                        array('id' => $cm->id, 'delete' => $timedsession->id, 'sessionsettings' => $sessionsettings)
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
        echo html_writer::start_tag('div', array('class' => 'no-overflow'));
        echo html_writer::table($table);
        echo html_writer::start_tag('br');
        echo html_writer::end_tag('div');
    } else {
        echo $OUTPUT->notification(get_string('nosession', 'mod_congrea'));
    }
} else {
    \core\notification::warning(get_string('notcapabletoviewschedules', 'congrea'));
}
// Finish the page.
echo $OUTPUT->footer();