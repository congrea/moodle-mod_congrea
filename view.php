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
 * @package    mod_congrea
 * @copyright  2014 Pinky Sharma
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once(dirname(__FILE__) . '/lib.php');
require_once(dirname(__FILE__) . '/locallib.php');

$id = optional_param('id', 0, PARAM_INT); // Course_module ID.
$n = optional_param('n', 0, PARAM_INT);  // Congrea instance ID - it should be named as the first character of the module.
$delete = optional_param('delete', 0, PARAM_INT);
$confirm = optional_param('confirm', '', PARAM_ALPHANUM);   // Md5 confirmation hash.

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
$context = context_module::instance($cm->id);

// Print the page header.
$PAGE->set_url('/mod/congrea/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($congrea->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

echo '<link rel="chrome-webstore-item" href="https://chrome.google.com/webstore/detail/ijhofagnokdeoghaohcekchijfeffbjl">';
// Event log.
$event = \mod_congrea\event\course_module_viewed::create(array(
            'objectid' => $congrea->id,
            'context' => $context,
        ));
$event->add_record_snapshot('course_modules', $cm);
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('congrea', $congrea);
$event->trigger();

// Mark viewed by user (if required).
$completion = new completion_info($course);
$completion->set_module_viewed($cm);

// Output starts here.
$strdelete = get_string('delete');
$strplay = get_string('play', 'congrea');
$returnurl = new moodle_url('/mod/congrea/view.php', array('id' => $cm->id));

$recordings = $DB->get_records('congrea_files', array('vcid' => $congrea->id), 'timecreated DESC');

// Delete a selected recording, after confirmation.
if ($delete and confirm_sesskey()) {
    require_capability('mod/congrea:recordingdelete', $context);
    $record = $DB->get_record('congrea_files', array('id' => $delete), '*', MUST_EXIST);
    if ($confirm != md5($delete)) {
        echo $OUTPUT->header();
        echo $OUTPUT->heading($strdelete . " " . $congrea->name);
        $optionsyes = array('delete' => $delete, 'confirm' => md5($delete), 'sesskey' => sesskey());
        echo $OUTPUT->confirm(get_string('deleterecordingfile', 'mod_congrea', $record->vcsessionname),
                            new moodle_url($returnurl, $optionsyes), $returnurl);
        echo $OUTPUT->footer();
        die;
    } else if (data_submitted()) {
        $fs = get_file_storage();
        $totalfiles = $record->numoffiles;
        for ($i = 1; $i <= $totalfiles; $i++) {
            $filename = $filename = "vc." . $i;
            $file = $fs->get_file($context->id, 'mod_congrea', 'congrea_rec',
                                $record->vcid, "/$record->vcsessionkey/", $filename);
            if ($file) {
                $sucess = $file->delete();
            }
        }
        if ($sucess) {
            $DB->delete_records('congrea_files', array('id' => $record->id));
            \core\session\manager::gc(); // Remove stale sessions.
            redirect($returnurl);
        } else {
            \core\session\manager::gc(); // Remove stale sessions.
            echo $OUTPUT->notification($returnurl, get_string('deletednot', '', $record->vcsessionname));
        }
    }
}

echo $OUTPUT->header();
echo $OUTPUT->heading($congrea->name);
// Validate https.
$url = parse_url($CFG->wwwroot);
if ($url['scheme'] !== 'https') {
    echo html_writer::tag('div', get_string('httpserror', 'congrea'), array('class' => 'alert alert-error'));
}
// Get audio status.
$audiostatus = $congrea->audio;
$videostatus = $congrea->video;
// Get congrea api key and Secret key from congrea setting.
$a = $CFG->wwwroot . "/admin/settings.php?section=modsettingcongrea";
$role = 's'; // Default role.
if (has_capability('mod/congrea:addinstance', $context) &&
        ($USER->id == $congrea->moderatorid)) {
    $role = 't';
}
if (!empty($cgapi = get_config('mod_congrea', 'cgapi')) && !empty($cgsecret = get_config('mod_congrea', 'cgsecretpassword'))) {
    $cgcolor = get_config('mod_congrea', 'colorpicker');
    if (strlen($cgsecret) >= 64 && strlen($cgapi) > 32) {
        require_once('auth.php');
    } else {
        echo $OUTPUT->notification(get_string('wrongkey', 'congrea', $a));
        echo $OUTPUT->footer();
        exit();
    }
} else {
    echo $OUTPUT->notification(get_string('notsavekey', 'congrea', $a));
    echo $OUTPUT->footer();
    exit();
}

$a = new stdClass();
$a->open = userdate($congrea->opentime);
$a->close = userdate($congrea->closetime);
$user = $DB->get_record('user', array('id' => $congrea->moderatorid));

$classname = 'wrapper-button';
if (($congrea->closetime > time() && $congrea->opentime <= time())) {
    $classname .= ' online';
}
echo html_writer::start_tag('div', array('class' => $classname));

echo html_writer::tag('div', get_string('congreatiming', 'mod_congrea', $a));
if (!empty($congrea->moderatorid)) {
    echo html_writer::tag('div', get_string('teachername', 'mod_congrea', $user));
} else {
    echo html_writer::tag('div', 'Moderator : None');
}

// Conditions to show the intro can change to look for own settings or whatever.
if ($congrea->intro) {
    echo $OUTPUT->box(format_module_intro('congrea', $congrea, $cm->id), 'generalbox mod_introbox', 'congreaintro');
}

echo html_writer::empty_tag('br');

// Serve online at vidya.io.
$url = "https://live.congrea.net"; // Online url.
$info = false; // Debugging off.
if ($USER->picture) {
    $userpicture = moodle_url::make_pluginfile_url(context_user::instance($USER->id)->id, 'user', 'icon', null, '/', 'f2');
    $userpicturesrc = $userpicture->out(false);
} else {
    $userpicturesrc = 'noimage';
}
$fromcms = true; // Identify congrea is from cms.
$upload = $CFG->wwwroot . "/mod/congrea/webapi.php?cmid=" . $cm->id . "&methodname=record_file_save";
$webapi = $CFG->wwwroot . "/mod/congrea/webapi.php?cmid=" . $cm->id;
$down = $CFG->wwwroot . "/mod/congrea/play_recording.php?cmid=$cm->id";
$room = !empty($course->id) && !empty($cm->id) ? $course->id . '_' . $cm->id : 0;
$PAGE->requires->js_call_amd('mod_congrea/congrea', 'congreaOnlinePopup');
$PAGE->requires->js_call_amd('mod_congrea/congrea', 'congreaPlayRecording');
if ($CFG->debug == 32767 && $CFG->debugdisplay == 1) {
    $info = true;
}
// Check congrea is open.
if ($congrea->closetime > time() && $congrea->opentime <= time()) {
    $murl = parse_url($CFG->wwwroot);
    if ($murl['scheme'] == 'https') {
        $sendmurl = $CFG->wwwroot;
    } else {
        $sendmurl = str_replace("http://", "https://", $CFG->wwwroot);
    }
    // Todo this should be changed with actual server path.
    $form = congrea_online_server($url, $authusername, $authpassword,
                                    $role, $rid, $room, $upload,
                                    $down, $info, $cgcolor, $webapi,
                                    $userpicturesrc, $fromcms, $licensekey, $audiostatus, $videostatus);
    echo $form;
} else {
    // Congrea closed.
    echo $OUTPUT->heading(get_string('sessionclosed', 'congrea'));
}
// Upload congrea recording.
echo html_writer::end_tag('div');
echo html_writer::start_tag('div', array('class' => 'wrapper-record-list'));
if (has_capability('mod/congrea:recordingupload', $context)) {
    echo html_writer::start_tag('div', array('class' => 'no-overflow'));
    echo $OUTPUT->single_button(new moodle_url('/mod/congrea/upload.php', array('id' => $id)),
                                            get_string('uploadrecordedfile', 'congrea'), 'get');
    echo html_writer::end_tag('div');
}
// Display list of recorded files.
$table = new html_table();
$table->head = array('Filename', 'Time created', 'Action', "");
$table->colclasses = array('centeralign', 'centeralign');
$table->attributes['class'] = 'admintable generaltable';
$table->id = "recorded_data";
foreach ($recordings as $record) {
    $buttons = array();
    $lastcolumn = '';
    $row = array();
    $row[] = $record->vcsessionname . ' ' . mod_congrea_module_get_rename_action($cm, $record);
    $row[] = userdate($record->timecreated);
    $vcsid = $record->id;
    if (has_capability('mod/congrea:playrecording', $context)) {
        $buttons[] = congrea_online_server_play($url, $authusername, $authpassword, $role,
                                                $rid, $room, $upload, $down,
                                                $info, $cgcolor, $webapi,
                                                $userpicturesrc, $licensekey, $id, $vcsid);
    }
    // Delete button.
    if (has_capability('mod/congrea:recordingdelete', $context) || ($record->userid == $USER->id)) {
        if ($CFG->version < 2017051500) { // Compare to moodle33 vesion.
            $imageurl = $OUTPUT->pix_url('t/delete'); // Only support below moodle33 version.
        } else {
            $imageurl = $OUTPUT->image_url('t/delete'); // Support moodle33 above.
        }
        $buttons[] = html_writer::link(new moodle_url($returnurl, array('delete' => $record->id, 'sesskey' => sesskey())),
                        html_writer::empty_tag('img', array('src' => $imageurl,
                        'alt' => $strdelete, 'class' => 'iconsmall')), array('title' => $strdelete));
    }
    $row[] = implode(' ', $buttons);
    $row[] = $lastcolumn;
    $table->data[] = $row;
}

if (!empty($table->data)) {
    echo html_writer::start_tag('div', array('class' => 'no-overflow'));
    echo html_writer::table($table);
    echo html_writer::end_tag('div');
}
echo html_writer::tag('div', "", array('class' => 'clear'));
echo html_writer::end_tag('div');

// Finish the page.
echo $OUTPUT->footer();
