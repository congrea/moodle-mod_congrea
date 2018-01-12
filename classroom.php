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
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod_congrea
 * @copyright  2014 Pinky Sharma
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
header("access-control-allow-origin: *");
require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');
require_once('auth.php');
$id = optional_param('id', 0, PARAM_INT); // Course_module ID, or
$n = optional_param('n', 0, PARAM_INT);  // ... congrea instance ID - it should be named as the first character of the module.
$isplay = optional_param('play', 0, PARAM_INT);  // Play recording
$vcSid = optional_param('vcSid', 0, PARAM_INT); // virtual class session record id
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
// Theme color
$theme = $congrea->themecolor;
require_login($course, true, $cm);
$context = context_module::instance($cm->id);
/*
$event = \mod_congrea\event\course_module_viewed::create(array(
    'objectid' => $congrea->id,
    'context' => $PAGE->context,
));
$event->add_record_snapshot('course', $PAGE->course);
$event->add_record_snapshot('congrea', $congrea);
$event->trigger();
*/
$suggestion = 'low';
$latency = 'slow';
$quality = 'low';
// Print the page header.
$PAGE->set_url('/mod/congrea/classroom.php', array('id' => $cm->id));
$PAGE->set_popup_notification_allowed(false); // No popup notifications in virtual classroom.
$PAGE->set_title(format_string($congrea->name));
$PAGE->set_context($context);
$PAGE->set_pagelayout('popup');
$PAGE->requires->jquery(true);
$PAGE->requires->jquery_plugin('ui');
$PAGE->requires->jquery_plugin('ui-css');
$PAGE->requires->css(new moodle_url($CFG->wwwroot . '/mod/congrea/bundle/virtualclass/codemirror/lib/codemirror.css'));
$PAGE->requires->css(new moodle_url($CFG->wwwroot . '/mod/congrea/bundle/virtualclass/bundle/jquery/css/base/'.$theme.'_jquery-ui.css'));
$PAGE->requires->css(new moodle_url($CFG->wwwroot . '/mod/congrea/bundle/virtualclass/css/modules/pbar.css'));
$PAGE->requires->css(new moodle_url($CFG->wwwroot . '/mod/congrea/bundle/virtualclass/css/modules/progress.css'));
$PAGE->requires->css(new moodle_url($CFG->wwwroot . '/mod/congrea/bundle/virtualclass/css/modules/custom.css'));
$PAGE->requires->css(new moodle_url($CFG->wwwroot . '/mod/congrea/bundle/virtualclass/SlickQuiz/css/slickQuiz.css'));
 $PAGE->requires->css(new moodle_url($CFG->wwwroot . '/mod/congrea/bundle/virtualclass/css/modules/dashboard.css'));
$PAGE->requires->css(new moodle_url($CFG->wwwroot . '/mod/congrea/bundle/virtualclass/css/modules/dbPpt.css'));
$PAGE->requires->css(new moodle_url($CFG->wwwroot . '/mod/congrea/bundle/virtualclass/css/modules/dbVideo.css'));
// Chrome extension for desktop sharing.
echo '<link rel="chrome-webstore-item" href="https://chrome.google.com/webstore/detail/ijhofagnokdeoghaohcekchijfeffbjl">';
// Mark viewed by user (if required).
//$completion = new completion_info($course);
//$completion->set_module_viewed($cm);
// Checking moodle deugger is unable or disable.
$info = 0;
if ($CFG->debug == 32767 && $CFG->debugdisplay == 1) {
    $info = 1;
}
$fontface = "";
echo "<style type='text/css'>";
    $fontface .= "@font-face {";
        $fontface .= "font-family: 'icomoon';\n";
        $fontface .= "src:url('".$CFG->wwwroot ."/mod/congrea/bundle/virtualclass/fonts/icomoon.eot?-jjdyd0');\n";
        $fontface .= "src:url('".$CFG->wwwroot ."/mod/congrea/bundle/virtualclass/fonts/icomoon.eot?#iefix-jjdyd0') format('embedded-opentype'), url('".$CFG->wwwroot ."/mod/congrea/bundle/virtualclass/fonts/icomoon.woff?-jjdyd0') format('woff'), url('".$CFG->wwwroot ."/mod/congrea/bundle/virtualclass/fonts/icomoon.ttf?-jjdyd0') format('truetype'), url('".$CFG->wwwroot ."/mod/congrea/bundle/virtualclass/fonts/icomoon.svg?-jjdyd0#icomoon') format('svg');\n";
        $fontface .= "font-weight: normal;\n";
        $fontface .= "font-style: normal;\n";
    $fontface .= "}\n";
    echo  $fontface;
echo "</style>";
/*
<link rel="stylesheet" type="text/css" href= <?php echo $whiteboardpath."css/bootstrap/css/bootstrap.css" ?> />
<link rel="stylesheet" type="text/css" href= <?php echo $whiteboardpath."codemirror/lib/codemirror.css" ?> />
<link rel="stylesheet" type="text/css" href= <?php echo $whiteboardpath."bundle/jquery/css/base/".$theme."_jquery-ui.css" ?> />
<link   rel="stylesheet" type="text/css" href=<?php echo $whiteboardpath."poll/graphs/c3.css"  ?>>
<!-- Load d3.js and c3.js -->
<script src= <?php echo $whiteboardpath."poll/graphs/d3.js" ?> ></script>
<script src=<?php echo $whiteboardpath."poll/graphs/c3.min.js" ?>></script>
*/
// File included if debugging on
if($info) {
    $PAGE->requires->css(new moodle_url($CFG->wwwroot . '/mod/congrea/bundle/virtualclass/css/modules/styles.css'));
    $PAGE->requires->css(new moodle_url($CFG->wwwroot . '/mod/congrea/bundle/virtualclass/css/modules/popup.css'));
    $PAGE->requires->css(new moodle_url($CFG->wwwroot . '/mod/congrea/bundle/virtualclass/css/modules/vceditor.css'));
    $PAGE->requires->css(new moodle_url($CFG->wwwroot . '/mod/congrea/bundle/virtualclass/css/modules/document-share.css'));
    $PAGE->requires->css(new moodle_url($CFG->wwwroot . '/mod/congrea/bundle/virtualclass/css/modules/editor.css'));
    $PAGE->requires->css(new moodle_url($CFG->wwwroot . '/mod/congrea/bundle/virtualclass/css/modules/icon.css'));
    $PAGE->requires->css(new moodle_url($CFG->wwwroot . '/mod/congrea/bundle/virtualclass/css/modules/media.css'));
    $PAGE->requires->css(new moodle_url($CFG->wwwroot . '/mod/congrea/bundle/virtualclass/css/modules/poll.css'));
    $PAGE->requires->css(new moodle_url($CFG->wwwroot . '/mod/congrea/bundle/virtualclass/css/modules/quiz.css'));
    $PAGE->requires->css(new moodle_url($CFG->wwwroot . '/mod/congrea/bundle/virtualclass/css/modules/screenshare.css'));
    $PAGE->requires->css(new moodle_url($CFG->wwwroot . '/mod/congrea/bundle/virtualclass/css/modules/sharepresentation.css'));
    $PAGE->requires->css(new moodle_url($CFG->wwwroot . '/mod/congrea/bundle/virtualclass/css/modules/video.css'));
    $PAGE->requires->css(new moodle_url($CFG->wwwroot . '/mod/congrea/bundle/virtualclass/css/modules/whiteboard.css'));
    $PAGE->requires->css(new moodle_url($CFG->wwwroot . '/mod/congrea/bundle/virtualclass/css/modules/youtube.css'));
    $PAGE->requires->css(new moodle_url($CFG->wwwroot . '/mod/congrea/bundle/virtualclass/css/modules/jquery.ui.chatbox.css'));
    $PAGE->requires->css(new moodle_url($CFG->wwwroot . '/mod/congrea/bundle/virtualclass/css/modules/progress.css'));
    $PAGE->requires->css(new moodle_url($CFG->wwwroot . '/mod/congrea/bundle/virtualclass/css/modules/pbar.css'));
    $PAGE->requires->css(new moodle_url($CFG->wwwroot . '/mod/congrea/bundle/virtualclass/css/modules/dashboard.css'));
    $PAGE->requires->css(new moodle_url($CFG->wwwroot . '/mod/congrea/bundle/virtualclass/css/bootstrap/css/bootstrap.css'));
    $PAGE->requires->css(new moodle_url($CFG->wwwroot . '/mod/congrea/bundle/virtualclass/poll/graphs/c3.css'));
     $PAGE->requires->css(new moodle_url($CFG->wwwroot . '/mod/congrea/bundle/virtualclass/css/theme/'.$theme.'.css'));
} else {
    $PAGE->requires->css(new moodle_url($CFG->wwwroot . '/mod/congrea/bundle/virtualclass/css/'.$theme.'.min.css'));
}

$whiteboardpath = $CFG->wwwroot . "/mod/congrea/bundle/virtualclass/";
$sid = $USER->sesskey;
$r = 's'; // Default role.
$role  = 'student';
$cont_class = 'congrea ';
if (has_capability('mod/congrea:addinstance', $context)) {
    if ($USER->id == $congrea->moderatorid && !$isplay) {
        $r = 't';
        $role  = 'teacher orginalTeacher';
        $classes = "audioTool active";
        $dap = "true";
        $pressingimg = $whiteboardpath . "images/speakerpressingactive.png";
    }
}
$cont_class .= $role;
if(empty($congrea->moderatorid)) {
    $anyonepresenter = 1;
} else {
    $anyonepresenter = 0;
}
if($isplay){
	$cont_class .= " playMode";
}
// Push to talk
$cont_class .= $congrea->pushtotalk ? ' pt_enable' : ' pt_disable';
// Audio enable/disable
if($congrea->audio){
    $classes = "audioTool active";
    $dap = "true";
    $audio_tooltip =  get_string('disableAudio','congrea');
} else {
    $dap = "false";
    $classes = "audioTool deactive";
    $audio_tooltip =  get_string('enableAudio','congrea');
}
?>


		<script>
			virtualclassSetting = {};
			virtualclassSetting.dap = '<?php echo $dap; ?>';
			virtualclassSetting.classes = '<?php echo $classes; ?>';
			virtualclassSetting.audio_tooltip = '<?php echo $audio_tooltip; ?>';
		</script>
<?php
// Output starts here.
echo $OUTPUT->header();
// Default image if webcam disable.
if ($USER->id) {
    $userpicture = moodle_url::make_pluginfile_url(context_user::instance($USER->id)->id, 'user', 'icon', null, '/', 'f2');
    $src = $userpicture->out(false);
} else {
    $src = 'bundle/virtualclass/images/quality-support.png';
}
$ts = ($USER->id == 3) ? true : false;
?> <script type="text/javascript">
    wbUser.virtualclassPlay = '<?php echo $isplay; ?>';
    wbUser.vcSid = '<?php echo $vcSid; ?>';
    wbUser.imageurl =  '<?php echo $src; ?>';
    wbUser.id =  '<?php echo $USER->id; ?>';
    wbUser.socketOn =  '<?php echo $info; ?>';
    wbUser.dataInfo =  0; //layout and all inofrmation is not validated since long time
    wbUser.room =  '<?php echo $course->id . "_" . $cm->id; ?>';
    wbUser.sid =  '<?php echo $sid; ?>';
    wbUser.role =  '<?php echo $r; ?>';
    wbUser.ts = '<?php  echo $ts; ?>';
    wbUser.lname =  '<?php echo $USER->lastname; ?>';
    wbUser.name =  '<?php echo $USER->firstname; ?>';
    wbUser.anyonepresenter =  '<?php echo $anyonepresenter ?>';
    window.whiteboardPath =  '<?php echo $whiteboardpath; ?>';
    window.importfilepath = "<?php echo $CFG->wwwroot."/mod/congrea/webapi.php?cmid=".$cm->id."&methodname=record_file_save"; ?>";
    window.webapi = "<?php echo $CFG->wwwroot."/mod/congrea/webapi.php?cmid=".$cm->id; ?>";
    window.exportfilepath = "<?php echo $CFG->wwwroot."/mod/congrea/play_recording.php?cmid=".$cm->id ?>";
    window.congCourse =  "<?php echo $cm->id ?>";
    if (!!window.Worker) {
        var sworker = new Worker("<?php echo $whiteboardpath."worker/screenworker.js" ?>");
        var sdworker = new Worker("<?php echo $whiteboardpath."worker/screendecode.js" ?>");
        var mvDataWorker = new Worker("<?php echo $whiteboardpath."worker/json-chunks.js" ?>");
        var dtConWorker = new Worker("<?php echo $whiteboardpath."worker/storage-array-base64-converter.js" ?>");
        var webpToPng = new Worker("<?php echo $whiteboardpath."worker/webptopng.js" ?>");

    }
</script>
<link href="https://vjs.zencdn.net/5.8.8/video-js.css" rel="stylesheet">
<script src="https://vjs.zencdn.net/5.8.8/video.js"></script>
  <!-- If you'd like to support IE8 -->
<script src="https://vjs.zencdn.net/ie8/1.1.2/videojs-ie8.min.js"></script>
<link href="<?php echo $whiteboardpath.'fileuploader/js/fine-uploader-gallery.css'; ?>" rel="stylesheet">

    <!-- Fine Uploader JS file
    ====================================================================== -->
<!-- <script src ="<?php //echo $whiteboardpath.'fileuploader/js/fine-uploader.js'; ?>"></script> -->
<script src ="<?php echo $whiteboardpath.'s3/s3.fine-uploader.js'; ?>"></script>




 <?php
if ($info) {
    require_once('bundle/virtualclass/build/js.debug.php');
    $PAGE->requires->js('/mod/congrea/bundle/virtualclass/css/bootstrap/js/bootstrap.js');
    //$PAGE->requires->js('/mod/congrea/bundle/virtualclass/poll/graphs/d3.js');
    $PAGE->requires->js('/mod/congrea/bundle/virtualclass/poll/graphs/c3.js');


} else {
    $PAGE->requires->js('/mod/congrea/bundle/virtualclass/bundle/io/build/iolib.min.js');
    $PAGE->requires->js('/mod/congrea/bundle/virtualclass/build/wb.min.js');
    $PAGE->requires->js('/mod/congrea/bundle/virtualclass/index.js');
}
?>
    <script type="text/template" id="qq-template-gallery">
<?php require_once('bundle/virtualclass/fine-upload.php'); ?>
    </script>
<div id="virtualclassCont" class="<?php echo $cont_class; ?>">

</div>
<?php
echo $OUTPUT->footer();
?>
