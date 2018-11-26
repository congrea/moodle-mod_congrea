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
 * Upload and save recorded file of congrea session
 *
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
$confirm = optional_param('confirm', '', PARAM_ALPHANUM); // Md5 confirmation hash.

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
require_once('upload_form.php');

$submiturl = new moodle_url('/mod/congrea/upload.php', array('id' => $cm->id));
$mform = new mod_congrea_upload_file($submiturl, $cm, $congrea, $context);

if ($mform->is_cancelled()) {
    redirect($CFG->wwwroot . '/mod/congrea/view.php?id=' . $cm->id);
}
if ($fromform = $mform->get_data()) {
    $vcsession = mod_congrea_generaterandomstring();
    $name = $mform->get_new_filename('userfile');
    // Object to save file info in db.
    $vcfile = new stdClass();
    $vcfile->courseid = $course->id;
    $vcfile->vcid = $congrea->id;
    $vcfile->userid = $USER->id;
    $vcfile->vcsessionkey = $vcsession;
    if (empty($fromform->name)) {
        $vcfile->vcsessionname = 'vc-' . $course->shortname . '-' . $congrea->name . $cm->id . '-' . date("Ymd") . '-' . date('Hi');
    } else {
        $vcfile->vcsessionname = $fromform->name;
    }
    $vcfile->numoffiles = 1;
    $vcfile->timecreated = time();
    $content = $mform->get_file_content('userfile');
    $decodedata = json_decode($content);
    $filelength = count($decodedata);
    if ($filelength > 1) {
        // Break larage file in multiple files.
        $filenum = 1;
        for ($i = 0; $i < $filelength; $i++) {
            if (array_key_exists('rdata', $decodedata[$i])) {
                $filename = "vc." . $filenum;
                $newcunk = json_encode($decodedata[$i]);
                if ($newcunk) {
                    $fs = get_file_storage();
                    // Prepare file record object.
                    $component = 'mod_congrea';
                    $filearea = 'congrea_rec';
                    $filepath = "/$vcsession/";
                    $fileinfo = array(
                        'contextid' => $context->id,
                        'component' => $component,
                        'filearea' => $filearea,
                        'itemid' => $congrea->id,
                        'filepath' => $filepath,
                        'filename' => $filename,
                        'mimetype' => 'application/vcp',
                        'source' => $vcsession,
                        'userid' => $USER->id);
                    if ($existing = $fs->get_file($context->id, 'mod_congrea', 'congrea_rec',
                        $cm->instance, $filepath, $filename)) {
                        if ($existing) {
                            $unsuccess = array('status' => '0', 'code' => 200, 'message' => 'duplicate');
                            echo json_encode($unsuccess);
                            return false;
                        }
                    }
                    $sucess = $fs->create_file_from_string($fileinfo, $newcunk);
                    if ($filenum > 1) {
                        // Update file count.
                        $vcfile = $DB->get_record('congrea_files', array('vcid' => $congrea->id, 'vcsessionkey' => $vcsession));
                        $vcfile->numoffiles = $filenum;
                        $DB->update_record('congrea_files', $vcfile);
                    } else {
                        $DB->insert_record('congrea_files', $vcfile);
                    }
                    $filenum++;
                } else {
                    print_error('Error occurred during file upload.');
                }
            }
        }
    } else {
        print_error('Error occurred during file upload.');
    }
    redirect(new moodle_url('/mod/congrea/view.php', array('id' => $cm->id)));
}
// Output starts here.
echo $OUTPUT->header();
echo $OUTPUT->heading($congrea->name);
$mform->display();
// Finish the page.
echo $OUTPUT->footer();
