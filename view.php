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

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');
require_once(dirname(__FILE__).'/locallib.php');

$id = optional_param('id', 0, PARAM_INT); // Course_module ID, or
$n  = optional_param('n', 0, PARAM_INT);  // ... Congrea instance ID - it should be named as the first character of the module.
$delete       = optional_param('delete', 0, PARAM_INT);
$confirm      = optional_param('confirm', '', PARAM_ALPHANUM);   //md5 confirmation hash

if ($id) {
    $cm         = get_coursemodule_from_id('congrea', $id, 0, false, MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $congrea  = $DB->get_record('congrea', array('id' => $cm->instance), '*', MUST_EXIST);
} else if ($n) {
    $congrea  = $DB->get_record('congrea', array('id' => $n), '*', MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $congrea->course), '*', MUST_EXIST);
    $cm         = get_coursemodule_from_instance('congrea', $congrea->id, $course->id, false, MUST_EXIST);
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
$PAGE->requires->js('/mod/congrea/chrome_extension_check.js');

// Event log
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

$recordings = $DB->get_records('congrea_files', array('vcid' => $congrea->id),'timecreated DESC');

// Delete a selected recording, after confirmation
if ($delete and confirm_sesskey()) {
    require_capability('mod/congrea:recordingdelete', $context);
    //require_capability('mod/congrea:addinstance', $context);
    $record = $DB->get_record('congrea_files', array('id'=>$delete), '*', MUST_EXIST);

    if ($confirm != md5($delete)) {
        echo $OUTPUT->header();

        echo $OUTPUT->heading($strdelete." ". $congrea->name);
        $optionsyes = array('delete'=>$delete, 'confirm'=>md5($delete), 'sesskey'=>sesskey());
        echo $OUTPUT->confirm(get_string('deletecheckfull', '', "'$record->vcsessionname'"), new moodle_url($returnurl, $optionsyes), $returnurl);
        echo $OUTPUT->footer();
        die;
    } else if (data_submitted()) {
        $filepath = $CFG->dataroot."/congrea/".$record->courseid."/".$record->vcid."/".$record->vcsessionkey;

        if (mod_congrea_deleteAll($filepath)) {
            $DB->delete_records('congrea_files', array('id'=> $record->id));
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

// If vidya.io API key missing.
if (!$licen = get_config('local_getkey', 'keyvalue')) {
    $url = new moodle_url('/local/getkey/index.php');
    echo $OUTPUT->notification(get_string('notsavekey', 'congrea', $url->out(false)));
    echo $OUTPUT->footer();
    exit();
} else {
    require_once('auth.php');
}

$a = new stdClass();
$a->open = userdate($congrea->opentime);
$a->close = userdate($congrea->closetime);
$user = $DB->get_record('user', array('id' => $congrea->moderatorid));
 
$class_name = 'wrapper-button';
if (($congrea->closetime > time() && $congrea->opentime <= time()) &&  (!get_config('mod_congrea', 'serve'))){
    $class_name .=  ' online';
}
echo html_writer::start_tag('div', array('class'=> $class_name));

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
echo "<br/ >";

echo html_writer::script('', $CFG->wwwroot.'/mod/congrea/popup.js');
$popupname = 'congreapopup';
$popupwidth = 'window.screen.width';
$popupheight = 'window.screen.height';
$popupoptions = "toolbar=no,location=no,menubar=no,copyhistory=no,status=no,directories=no,scrollbars=yes,resizable=yes";

$themecolor = $congrea->themecolor;
$audio = $congrea->audio;
$pushtotalk = $congrea->pushtotalk;
$anyonepresenter = empty($congrea->moderatorid) ? 1 : 0;
// Check congrea is open.
if ($congrea->closetime > time() && $congrea->opentime <= time()) {
    $room = $course->id . "_" . $cm->id;

    //if ($CFG->congrea_serve) {
    if (get_config('mod_congrea', 'serve')) {
        // Serve local files.
        $url = new moodle_url($CFG->wwwroot.'/mod/congrea/classroom.php', array('id' => $id));
        $vcpopup = js_writer::function_call('congrea_openpopup', Array($url->out(false),
                                                       $popupname, $popupoptions,
                                                       $popupwidth, $popupheight));

        echo html_writer::start_tag('button', array('value' => get_string('joinroom', 'congrea'),
                     'id' => 'vc', 'class' => 'vcbutton',  'onclick' => $vcpopup));

        echo get_string('joinroom', 'congrea');
        echo html_writer::end_tag('button');
        echo html_writer::start_tag('div', array('class'=>'clear'));
        echo html_writer::end_tag('div');
    } else {
        global $USER;
        // Serve online at vidya.io.
        $url = "https://l.vidya.io";  // Online url
        $role = 's'; // Default role.
        $info = false; // Debugging off.

        $murl = parse_url($CFG->wwwroot);
        if($murl['scheme'] == 'https'){
            $sendmurl = $CFG->wwwroot;
        } else {
            $sendmurl = str_replace("http://", "https://", $CFG->wwwroot);
        }
        $mysession = session_id();
        //$upload = $sendmurl ."/mod/congrea/recording.php?cmid=$cm->id&key=$mysession";
        // Todo this should be changed with actual server path
        
       // $upload = "https://local.vidya.io/transfer.php?cmid=".$cm->id."&key=$mysession&mdroot=".htmlspecialchars($CFG->wwwroot);

          $upload = $CFG->wwwroot."/mod/congrea/webapi.php?cmid=".$cm->id."&key=$mysession&methodname=record_file_save";
        
        
       // $upload = "https://l.vidya.io/transfer.php?cmid=".$cm->id."&key=$mysession&mdroot=".htmlspecialchars($CFG->wwwroot);
        $down = $CFG->wwwroot ."/mod/congrea/play_recording.php?cmid=$cm->id";

        if (has_capability('mod/congrea:addinstance', $context)) {
            if ($USER->id == $congrea->moderatorid) {
                $role = 't';
            }
        }
        if ($CFG->debug == 32767 && $CFG->debugdisplay == 1) {
            $info = true;
        }
        $form = congrea_online_server($url, $authusername, $authpassword, $role, $rid, $room,
                    $popupoptions, $popupwidth, $popupheight, $upload, $down, $info, $anyonepresenter, $audio, $pushtotalk, $themecolor);
        echo $form; 
    }
} else {
    // congrea closed.
    echo $OUTPUT->heading(get_string('sessionclosed', 'congrea'));
}
echo html_writer::end_tag('div'); 
echo html_writer::start_tag('div', array('class'=>'wrapper-record-list'));
//if (has_capability('mod/congrea:addinstance', $context)) {
if (has_capability('mod/congrea:recordingupload', $context)) {
    echo html_writer::start_tag('div', array('class'=>'no-overflow'));
    echo $OUTPUT->single_button(new moodle_url('/mod/congrea/upload.php', array('id' => $id)), get_string('uploadrecordedfile','congrea'), 'get');
    echo html_writer::end_tag('div');
}
//display list of recorded files

$table = new html_table();
$table->head = array ();
$table->colclasses = array();
$table->head[] = 'Filename';
$table->attributes['class'] = 'admintable generaltable';
$table->head[] = 'Time Created';
$table->head[] = get_string('action');
$table->colclasses[] = 'centeralign';
$table->head[] = "";
$table->colclasses[] = 'centeralign';

$table->id = "recorded_data";

foreach ($recordings as $record){
    $buttons = array();
    $lastcolumn = '';
    $row = array ();
    $row[] = $record->vcsessionname. ' ' . mod_congrea_module_get_rename_action($cm, $record);
    $row[] = userdate($record->timecreated);    

    $playurl = new moodle_url($CFG->wwwroot.'/mod/congrea/classroom.php', array('id' => $id, 'vcSid' =>$record->id, 'play' =>1));
    $playpopup = js_writer::function_call('congrea_openpopup', Array($playurl->out(false),
                                                   $popupname, $popupoptions,
                                                   $popupwidth, $popupheight));
    // play button
    if (has_capability('mod/congrea:playrecording', $context)) {    
       $buttons[] = html_writer::empty_tag('img', array('src' => $OUTPUT->pix_url('e/insert_edit_video'), 'alt' => $strplay, 'class'=>'iconsmall hand', 'onclick' => $playpopup));
    }

    // delete button
    if (has_capability('mod/congrea:recordingdelete', $context) || ($record->userid == $USER->id)) {
       $buttons[] = html_writer::link(new moodle_url($returnurl, array('delete'=>$record->id, 'sesskey'=>sesskey())), html_writer::empty_tag('img', array('src'=>$OUTPUT->pix_url('t/delete'), 'alt'=>$strdelete, 'class'=>'iconsmall')), array('title'=>$strdelete));
    }

    $row[] = implode(' ', $buttons);
    $row[] = $lastcolumn;
    $table->data[] = $row;
}

if (!empty($table->data)) {
    echo html_writer::start_tag('div', array('class'=>'no-overflow'));
    echo html_writer::table($table);
    echo html_writer::end_tag('div');
    //echo $OUTPUT->paging_bar($usercount, $page, $perpage, $baseurl);
}
        echo html_writer::start_tag('div', array('class'=>'clear'));
        echo html_writer::end_tag('div');
echo html_writer::end_tag('div');
// Finish the page.
echo $OUTPUT->footer();