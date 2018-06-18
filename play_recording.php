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
 * Play file recorded during congrea session
 *
 * @package   mod_congrea
 * @copyright 2015 Pinky Sharma
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
cors();
require_once(dirname(dirname(dirname(__FILE__))).'/config.php');

$filenum = required_param('prvfile', PARAM_INT);
$fid = required_param('fileBundelId', PARAM_INT);
$id = required_param('id', PARAM_INT); // Course module id.

if ($id) {
    $cm = get_coursemodule_from_id('congrea', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $congrea = $DB->get_record('congrea', array('id' => $cm->instance), '*', MUST_EXIST);
} else {
    print_error('You must specify a course_module ID or an instance ID');
}
$context = context_module::instance($cm->id);

$file = $DB->get_record('congrea_files', array('id' => $fid));
$filepath = $CFG->dataroot . "/congrea/" . $file->courseid . "/" . $file->vcid . "/" . $file->vcsessionkey . "/vc." . $filenum;
if (file_exists($filepath)) {
    $data = file_get_contents($filepath);
} else {
    $data = "VCE3"; // Filenotfound.
}

echo $data;

/**
 * The function is to check cors browser
 * serving for virtual class
 */
function cors() {
    // Allow from any origin.
    if (isset($_SERVER['HTTP_ORIGIN'])) {
        // Decide if the origin in $_SERVER['HTTP_ORIGIN'] is one.
        // you want to allow, and if so.
        header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Max-Age: 86400');    // Cache for 1 day.
    }
    // Access-Control headers are received during OPTIONS requests.
    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
        if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])) {
            // May also be using PUT, PATCH, HEAD etc.
            header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
        }
        if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) {
            header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
        }
        exit(0);
    }
}
