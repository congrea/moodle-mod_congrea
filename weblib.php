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
 * Congrea module internal API,
 * serving for virtual class
 *
 * @package   mod_congrea
 * @copyright 2017 Suman Bogati, Ravi Kumar
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
//////// Congrea Video Sharing ///////////

/**
 * Save videos in to moodle DB
 * serving for virtual class
 *
 * @param array $valparams
 * @return json to ensures videos sucessfully uploaded in moodle DB
 */
function file_save($valparams) { // To do-for function name
    global $CFG, $DB;
    if (!empty($valparams)) {
        if (!empty($valparams[0]['qqfile'])) { // File is Video Type
            list($file, $cmid, $userid) = $valparams;
            if (!empty($file) && !empty($cmid) && !empty($userid)) {
                if (!$cm = get_coursemodule_from_id('congrea', $cmid)) {
                    print_error('Course Module ID was incorrect');
                }
                $context = context_module::instance($cm->id);
                $the_content_type = $file['qqfile']['type'];
                $video = strstr($the_content_type, '/', true);
                if ($file['qqfile']['size'] > 0 && $file['qqfile']['error'] == 0 && $video == 'video') {
                    $fs = get_file_storage();
                    $file_record = array(
                        'contextid' => $context->id, // ID of context.
                        'component' => 'mod_congrea', // usually = table name.
                        'filearea' => 'video', // usually = table name.
                        'itemid' => $cm->instance, // usually = ID of row in table.
                        'filepath' => '/', // any path beginning and ending in.
                        'filename' => basename($file['qqfile']['name']), // any filename
                        'status' => 1,
                        'timecreated' => time(),
                        'timemodified' => time(),
                        'userid' => $userid);
                    if ($existing = $fs->get_file($context->id, 'mod_congrea', 'video', $cm->instance, '/', basename($file['qqfile']['name']))) {
                        if ($existing) {
                            $unsuccess = array('status' => '0', 'code' => 200, 'message' => 'duplicate');
                            echo json_encode($unsuccess);
                            return false;
                        }
                    }
                    $uservideo = $fs->create_file_from_pathname($file_record, $file['qqfile']['tmp_name']); // Save user source file into file api of moodle.
                    if (!empty($uservideo)) {
                        $obj = new stdClass();
                        $obj->congreaid = $cm->instance;
                        $obj->userid = $userid;
                        $obj->resource = $uservideo->get_id();
                        $obj->type = 'video';
                        $obj->status = 1;
                        $obj->timecreated = time();
                        $uservideoid = $DB->insert_record('congrea_mediafiles', $obj);
                        if (!empty($uservideoid)) {
                            $suceess = array('status' => '1', 'message' => 'success', 'resultdata' => array('id' => $uservideoid), 'code' => 100);
                            echo json_encode($suceess);
                        }
                    } else {
                        $unsuccess = array('status' => '0', 'code' => 200, 'message' => 'Failed');
                        echo json_encode($unsuccess);
                    }
                } else {
                    $unsuccess = array('status' => '0', 'code' => 200, 'message' => 'Failed');
                    echo json_encode($unsuccess);
                }
            }
        } else { // Save videos URl.
            list($path, $cmid, $userid) = $valparams;
            if (!empty($path) && !empty($cmid) && !empty($userid)) {
                if (!$cm = get_coursemodule_from_id('congrea', $cmid)) {
                    print_error('Course Module ID was incorrect');
                }
                $obj = new stdClass();
                $obj->congreaid = $cm->instance;
                $obj->userid = $userid;
                $obj->resource = $path['video'];
                $obj->type = $path['type'];
                $obj->status = 1;
                $obj->timecreated = time();
                $sucess = $DB->insert_record('congrea_mediafiles', $obj);
                if (!empty($sucess)) {
                    $suceess = array('status' => '1', 'message' => 'success', 'resultdata' => array('id' => $sucess), 'code' => 100);
                    echo json_encode($suceess);
                } else {
                    $unsuccess = array('status' => '0', 'code' => 200, 'message' => 'Failed');
                    echo json_encode($unsuccess);
                }
            }
        }
    } else {
        $unsuccess = array('status' => '0', 'code' => 200, 'message' => 'Failed');
        echo json_encode($unsuccess);
    }
}

/**
 * Retrieve congrea videos
 * serving for virtual class
 *
 * @param array $valparams
 * @return json
 */
function congrea_retrieve_video($valparams) {
    global $CFG, $DB;
    list($cmid, $userid) = $valparams;
    if (!empty($cmid) && !empty($userid)) {
        if (!$cm = get_coursemodule_from_id('congrea', $cmid)) {
            print_error('Course Module ID was incorrect');
        }
        $records = $DB->get_records('congrea_mediafiles', array('congreaid' => $cm->instance));
        if (!empty($records)) {
            foreach ($records as $record) {
                if ($record->type == 'video') { // For videos which are save in moodle file api.
                    $filedata = $DB->get_records('files', array('id' => "$record->resource"));
                    if (!empty($filedata)) {
                        if ($filedata[$record->resource]->filename != "." && $filedata[$record->resource]->filename != "..") {
                            $contextid = $filedata[$record->resource]->contextid;
                            $itemid = $filedata[$record->resource]->itemid;
                            $filename = $filedata[$record->resource]->filename;
                            $contentpath = "$CFG->wwwroot/pluginfile.php/$contextid/mod_congrea/video/$itemid/$filename";
                            if (!empty($contentpath)) {
                                $videodata = new stdClass();
                                $videodata->id = $record->id;
                                $videodata->title = $filedata[$record->resource]->filename;
                                $videodata->status = $record->status;
                                $videodata->type = $record->type;
                                $videodata->content_path = $contentpath;
                                $videolist[] = $videodata;
                            }
                        }
                    }
                }else if($record->type !=='ppt'){ // For Url such as Youtube etc.
                    $videosurl = new stdClass();
                    $videosurl->id = $record->id;
                    $videosurl->title = $record->resource;
                    $videosurl->status = $record->status;
                    $videosurl->type = $record->type;
                    $videosurl->content_path = $record->resource;
                    $videolist[] = $videosurl;
                }
            }
            if (!empty($videolist)) {
                //print_r($videolist);
                echo json_encode($videolist);
            } else {
                $unsuccess = array('status' => '0', 'code' => 108, 'message' => 'noVideo');
                echo json_encode($unsuccess);
            }
        } else {
            $unsuccess = array('status' => '0', 'code' => 108, 'message' => 'noVideo');
            echo json_encode($unsuccess);
        }
    }
}

/**
 * Delete videos,docs and change status of videos,docs
 * serving for virtual class
 *
 * @param array $valparams
 * @return  json to ensures videos delete or status sucessfully perform.
 */

function update_content($valparams) { // TO do - Improve function name.
    global $DB;
    if (!empty($valparams)) {
        list($postdata) = $valparams;
        if (!empty($postdata['lc_content_id'])) {
            $id = json_decode($postdata['lc_content_id']);
            if ($postdata['action'] == 'delete') { // Delete content
                if (!empty($id)) {
                    $fs = get_file_storage();
                    $records = $DB->get_records('files', array('id' => $id)); // Get records for delete related images.
                    if ($records) { // To do- In case of videos not need to check its related images.
                        $sql = "SELECT id, filename, contextid, itemid, filearea, filepath from {files} where source = ? && contextid  = ? && itemid = ?";
                        $imagedata = $DB->get_records_sql($sql, array('source' => $id, 'contextid' => $records[$id]->contextid, 'itemid' => $records[$id]->itemid));
                        if (!empty($imagedata)) {
                            delete_docs_images($imagedata); // Delete images related to Docs.
                        }
                        $fileinfo = array(// Delete docs or videos
                            'component' => 'mod_congrea',
                            'filearea' => $records[$id]->filearea, // usually = table name
                            'itemid' => $records[$id]->itemid, // usually = ID of row in table
                            'contextid' => $records[$id]->contextid, // ID of context
                            'filepath' => $records[$id]->filepath, // any path beginning and ending in /
                            'filename' => $records[$id]->filename); // any filename
                        // Get file
                        $file = $fs->get_file($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'], $fileinfo['itemid'], $fileinfo['filepath'], $fileinfo['filename']);
                        // Delete it if it exists
                        if ($file) {
                            $sucess = $file->delete();
                            if ($sucess == 1) {
                                $success = array('status' => '1', 'message' => 'Delete content type is Successfully', 'code' => 100,);
                                echo json_encode($success);
                            } else {
                                $unsuccess = array('status' => '0', 'code' => 200, 'message' => 'Failed');
                                echo json_encode($unsuccess);
                            }
                        }
                    }
                }
            }
        } else if (!empty($postdata['page_id'])) {
            $id = json_decode($postdata['page_id']);
            if ($postdata['action'] == 'delete') {
                $fs = get_file_storage();
                $records = $DB->get_records('files', array('id' => $id));
                $fileinfo = array(// Delete docs or videos
                    'component' => 'mod_congrea',
                    'filearea' => $records[$id]->filearea, // usually = table name
                    'itemid' => $records[$id]->itemid, // usually = ID of row in table
                    'contextid' => $records[$id]->contextid, // ID of context
                    'filepath' => $records[$id]->filepath, // any path beginning and ending in /
                    'filename' => $records[$id]->filename); // any filename
                // Get file
                $file = $fs->get_file($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'], $fileinfo['itemid'], $fileinfo['filepath'], $fileinfo['filename']);
                // Delete it if it exists
                if ($file) {
                    $sucess = $file->delete();
                    if ($sucess == 1) {
                        $success = array('status' => '1', 'message' => 'Delete content type is Successfully', 'code' => 100,);
                        echo json_encode($success);
                    } else {
                        $unsuccess = array('status' => '0', 'code' => 200, 'message' => 'Failed');
                        echo json_encode($unsuccess);
                    }
                }
            }
        }
        if ($postdata['action'] == 'status') { // update status
            if (!empty($id)) {
                $status = $DB->execute("UPDATE {files} SET status = '" . $postdata['status'] . "' WHERE id = '" . $id . "'");
                if ($status == 1) { //  Ensure Update is Sucessfull.
                    $success = array('status' => '1', 'message' => 'Status Changed Successfully', 'code' => 100,);
                    echo json_encode($success);
                } else {
                    $unsuccess = array('status' => '0', 'code' => 200, 'message' => 'Failed');
                    echo json_encode($unsuccess);
                }
            }
        }
    } else {
        $unsuccess = array('status' => '0', 'code' => 200, 'message' => 'Failed');
        echo json_encode($unsuccess);
    }
}

function update_content_video($valparams) { // TO do - Improve function name.
    global $DB;
    if (!empty($valparams)) {
        list($postdata) = $valparams;
        if (!empty($postdata['lc_content_id'])) {
            $id = json_decode($postdata['lc_content_id']);
            if ($postdata['action'] == 'delete') { // Delete content
                if (!empty($id)) {
                    $fileid = $DB->get_field('congrea_mediafiles', 'resource', array('id' => $id));
                    if (is_numeric($fileid)) {
                        delete_file_videos($fileid); // Delete videos which are save in moodle file api.
                    }
                    $sucess = $DB->delete_records('congrea_mediafiles', array('id' => $id));
                    if ($sucess == 1) {
                        $success = array('status' => '1', 'message' => 'Delete content type is Successfully', 'code' => 100,);
                        echo json_encode($success);
                    } else {
                        $unsuccess = array('status' => '0', 'code' => 200, 'message' => 'Failed');
                        echo json_encode($unsuccess);
                    }
                }
            }
        }
        if ($postdata['action'] == 'status') { // update status
            if (!empty($id)) {
                $status = $DB->execute("UPDATE {congrea_mediafiles} SET status = '" . $postdata['status'] . "' WHERE id = '" . $id . "'");
                if ($status == 1) { //  Ensure Update is Sucessfull.
                    $success = array('status' => '1', 'message' => 'Status Changed Successfully', 'code' => 100,);
                    echo json_encode($success);
                } else {
                    $unsuccess = array('status' => '0', 'code' => 200, 'message' => 'Failed');
                    echo json_encode($unsuccess);
                }
            }
        }
    } else {
        $unsuccess = array('status' => '0', 'code' => 200, 'message' => 'Failed');
        echo json_encode($unsuccess);
    }
}

/**
 * Delete videos which save in moodle file api.
 * serving for virtual class
 *
 * @param array of object  $records
 * @return  json
 */

function delete_file_videos($fileid) {
    global $DB;
    if (!empty($fileid)) {
        $records = $DB->get_record('files', array('id' => $fileid));
        if (!empty($records)) {
            $fs = get_file_storage();
            if ($records->filename != "." && $records->filename != "..") {
                $fileinfo = array(// Delete videos
                    'component' => 'mod_congrea',
                    'filearea' => $records->filearea, // usually = table name
                    'itemid' => $records->itemid, // usually = ID of row in table
                    'contextid' => $records->contextid, // ID of context
                    'filepath' => $records->filepath, // any path beginning and ending in /
                    'filename' => $records->filename); // any fil
                // Get file
                $file = $fs->get_file($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'], $fileinfo['itemid'], $fileinfo['filepath'], $fileinfo['filename']);
                // Delete it if it exists
                if ($file) {
                    $file->delete();
                } else {
                    $unsuccess = array('status' => '0', 'code' => 200, 'message' => 'Failed');
                    echo json_encode($unsuccess);
                }
            }
        }
    }
}

/**
 * Delete Doc's related images
 * serving for virtual class
 *
 * @param array of object  $docsimagedata
 * @return  json to ensures Doc's related images are deleted
 */
function delete_docs_images($docsimagedata) { // to do.
    if (!empty($docsimagedata)) {
        $fs = get_file_storage();
        foreach ($docsimagedata as $file) {
            if ($file->filename != "." && $file->filename != "..") {
                $fileinfo = array(
                    'component' => 'mod_congrea',
                    'filearea' => $file->filearea, // usually = table name
                    'itemid' => $file->itemid, // usually = ID of row in table
                    'contextid' => $file->contextid, // ID of context
                    'filepath' => $file->filepath, // any path beginning and ending in /
                    'filename' => $file->filename); // any filename
                // Get file
                $file = $fs->get_file($fileinfo['contextid'], $fileinfo['component'], $fileinfo['filearea'], $fileinfo['itemid'], $fileinfo['filepath'], $fileinfo['filename']);
                if ($file) {
                    $file->delete(); // Delete all related images of Notes.
                } else {
                    $unsuccess = array('status' => '0', 'code' => 200, 'message' => 'Failed');
                    echo json_encode($unsuccess);
                }
            }
        }
    }
}

//////// Congrea Files  ///////////

/**
 * Save recorded files
 * serving for virtual class
 *
 * @param array $valparams
 */
function record_file_save($valparams) {
    global $CFG, $DB;
    list($cmid, $userid, $filenum, $vmsession, $data) = $valparams;
    if ($cmid) {
        $cm = get_coursemodule_from_id('congrea', $cmid, 0, false, MUST_EXIST);
        $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
        $congrea = $DB->get_record('congrea', array('id' => $cm->instance), '*', MUST_EXIST);
    } else {
        echo 'VCE6';
        exit; //'Course module ID missing.';
    }
    require_login($course, true, $cm);
    $context = context_module::instance($cm->id);
    $basefilepath = $CFG->dataroot . "/congrea"; // Place to save recording files.
    if (has_capability('mod/congrea:dorecording', $context)) {
        if ($data) {
            $filepath = $basefilepath . "/" . $course->id . "/" . $congrea->id . "/" . $vmsession;
            // Create folder if not exist
            if (!file_exists($filepath)) {
                mkdir($filepath, 0777, true);
            }
            $filename = "vc." . $filenum;
            if (file_put_contents($filepath . '/' . $filename, $data) != false) {
                //save file record in database
                if ($filenum > 1) {
                    //update record
                    $vcfile = $DB->get_record('congrea_files', array('vcid' => $congrea->id, 'vcsessionkey' => $vmsession));
                    $vcfile->numoffiles = $filenum;
                    $DB->update_record('congrea_files', $vcfile);
                } else {
                    $vcfile = new stdClass();
                    $vcfile->courseid = $course->id;
                    $vcfile->vcid = $congrea->id;
                    $vcfile->userid = $userid;
                    $vcfile->vcsessionkey = $vmsession;
                    $vcfile->vcsessionname = 'vc-' . $course->shortname . '-' . $congrea->name . $cm->id . '-' . date("Ymd") . '-' . date('Hi');
                    $vcfile->numoffiles = $filenum;
                    $vcfile->timecreated = time();
                    //print_r($vcfile);exit;
                    $DB->insert_record('congrea_files', $vcfile);
                }
                echo "done";
            } else {
                echo 'VCE5'; //'Unable to record data.';exit;
            }
        } else {
            echo 'VCE4'; //'No data for recording.';
        }
    } else {
        echo 'VCE2'; //'Permission denied';
    }
}

//////// Congrea Poll  ///////////

/**
 * Save Congrea poll
 * serving for virtual class
 *
 * @param array $valparams
 * @return json for publishing poll
 */
function poll_save($valparams) {
    global $DB;
    if (!empty($valparams)) {
        $response_array = array();
        $obj = new stdClass();
        list($postdata, $cmid, $userid) = $valparams;
        $datatosave = json_decode($postdata['dataToSave']);
        if (!empty($datatosave)) {
            $question = new stdClass();
            $question->description = $datatosave->question;
            $question->name = 0;
            $question->timecreated = time();
            $question->createdby = $userid;
            $question->category = $datatosave->category;
            $question->cmid = $cmid;
            $questionid = $DB->insert_record('congrea_poll_question', $question);
            $username = $DB->get_field('user', 'username', array('id' => $userid));
            if ($questionid) {
                foreach ($datatosave->options as $optiondata) {
                    $options = new stdClass();
                    $options->options = $optiondata;
                    $options->qid = $questionid;
                    $id = $DB->insert_record('congrea_poll_question_option', $options);
                    $options->optid = $id;
                    $response_array[] = $options;
                }
            }
            $obj->qid = $questionid;
            $obj->createdby = $question->createdby;
            $obj->question = $question->description;
            $obj->createdby = $question->createdby;
            $obj->category = $question->category;
            $obj->options = $response_array;
            $obj->creatorname = $username;
            $obj->copied = $datatosave->copied;
            echo json_encode($obj);
        } else {
            echo get_string('nopolldata', 'congrea'); // No Data for Save.
        }
    } else {
        echo get_string('nopollsave', 'congrea'); // Unable to save poll.
    }
}

/**
 * Delete poll's option
 * serving for virtual class
 *
 * @param array $valparams
 * @return bool true if successful otherwise false
 */
function poll_option_drop($valparams) {
    global $DB;
    list($postdata) = $valparams;
    if (!empty($postdata)) {
        $id = json_decode($postdata['id']); // Get poll option id.
        if ($id) {
            $temp = $DB->delete_records('congrea_poll_question_option', array('id' => $id));
            if ($temp == 1) { // Ensures Delete operation is sucessfully Perform.
                echo $temp;
            } else {
                echo get_string('deletepolloption', 'congrea'); // Delete operation is Unsucessfull.
            }
        }
    } else {
        echo get_string('polloptiondeletefailed', 'congrea'); // Delete is Failed,Try again;
    }
}

/**
 * Retrieve congrea poll
 * serving for virtual class
 *
 * @param array $valparams
 * @return json
 */
function poll_data_retrieve($valparams) {
    global $DB;
    list($postdata) = $valparams;
    if (!empty($postdata)) {
        $response_array = array();
        $category = json_decode($postdata['category']);
        $questiondata = $DB->get_records('congrea_poll_question', array('category' => $category));
        if ($questiondata) {
            foreach ($questiondata as $data) {
                $username = $DB->get_field('user', 'username', array('id' => $data->createdby));
                $result = $DB->record_exists('congrea_poll_attempts', array('qid' => $data->id));
                $sql = "SELECT id, options from {congrea_poll_question_option} where qid = $data->id";
                $optiondata = $DB->get_records_sql($sql);
                $polllist = array('questionid' => $data->id, 'category' => $data->category, 'createdby' => $data->createdby, 'questiontext' => $data->description, 'options' => $optiondata, 'creatorname' => $username, 'isPublished' => $result);
                $response_array[] = $polllist;
            }
        }
        if (is_siteadmin()) {
            $response_array[] = "true";
        } else {
            $response_array[] = "false";
        }
        echo json_encode($response_array);
    } else {
        echo get_string('pollretrievefail', 'congrea'); // Unable to Retrieve Poll,Try again.
    }
}

/**
 * Delete Congrea poll
 * serving for virtual class
 *
 * @param array $valparams
 * @return int 0 ensures site poll otherwise course poll
 */
function poll_delete($valparams) {
    global $DB;
    if (!empty($valparams)) {
        list($postdata) = $valparams;
        $id = json_decode($postdata['qid']); // Get question id.
        if ($id) {
            $category = $DB->get_field('congrea_poll_question', 'category', array('id' => "$id")); // Ensures which type of poll(site or course) will be deleted.
            $delresult = $DB->delete_records('congrea_poll_attempts', array('qid' => "$id"));
            $deloptions = $DB->delete_records('congrea_poll_question_option', array('qid' => "$id"));
            if ($deloptions) {
                $DB->delete_records('congrea_poll_question', array('id' => "$id"));
            }
            echo $category;
        }
    } else {
        echo get_string('polldelete', 'congrea'); // Poll Delete is Unsucessfull, try again.
    }
}

/**
 * Update Congrea poll and add new options in existing poll.
 * serving for virtual class
 *
 * @param array $valparams
 * @return json
 */
function poll_update($valparams) {
    global $DB;
    list($postdata) = $valparams;
    if (!empty($postdata)) {
        $response_array = array();
        $obj = new stdClass();
        $data = json_decode($postdata['editdata']);
        $category = $DB->get_field('congrea_poll_question', 'category', array('id' => "$data->questionid"));
        $quesiontext = $DB->execute("UPDATE {congrea_poll_question} SET description = '" . $data->question . "' WHERE id = '" . $data->questionid . "'");
        if ($quesiontext) {
            foreach ($data->options as $key => $value) {
                $newoptions = new stdClass();
                if (is_numeric($key)) { // Ensures Question and options are old.
                    $DB->execute("UPDATE {congrea_poll_question_option} SET options = '" . $value . "' WHERE id = '" . $key . "'");
                    $newoptions->options = $value;
                    $newoptions->id = $key;
                    $newoptions->qid = $data->questionid;
                } else { // Add new Options.
                    $newoptions->options = $value;
                    $newoptions->qid = $data->questionid;
                    $optid = $DB->insert_record('congrea_poll_question_option', $newoptions);
                    $newoptions->id = $optid;
                }
                $response_array[] = $newoptions;
            }
        }
        $obj->qid = $data->questionid;
        $obj->question = $data->question;
        $obj->createdby = $data->createdby;
        $obj->category = $category;
        $obj->options = $response_array;
        echo json_encode($obj);
    } else {
        echo get_string('pollupdatefail', 'congrea'); // Poll Update is Unsucessfull, try again.
    }
}

/**
 * Save questions and options which is attempt by users
 * serving for virtual class
 *
 * @param array $valparams
 * @return int ensures which type of poll(course poll, site poll) is attemted by users, 0 specify site poll otherwise course poll.
 */
function poll_result($valparams) {
    global $DB;
    list($postdata) = $valparams;
    if (!empty($postdata)) {
        $data = json_decode($postdata['saveResult']);
        if ($data->qid) {
            $questionid = $data->qid;
            $category = $DB->get_field('congrea_poll_question', 'category', array('id' => "$data->qid"));
            if ($data->list) {
                foreach ($data->list as $optiondata) {
                    foreach ($optiondata as $userid => $optionid) {
                        if (is_numeric($userid) && is_numeric($optionid)) {
                            $attempt = new stdClass();
                            $attempt->userid = $userid;
                            $attempt->qid = $questionid;
                            $attempt->optionid = $optionid;
                            $DB->insert_record('congrea_poll_attempts', $attempt);
                        }
                    }
                }
                echo $category;
            }
        }
    } else {
        echo get_string('nopollresult', 'congrea'); // Unable to save poll result.
    }
}

//////// Congrea Document Sharing  ///////////

/**
 * This function convert office file to pdf and pdf to images.
 * serving for virtual class
 *
 * @package   mod_congrea
 * @copyright 2017 Ravi Kumar
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
function congrea_image_converter($valparams) {
    global $CFG, $OUTPUT;
    list($file, $cmid, $userid) = $valparams;
    if (!empty($file) && !empty($cmid) && !empty($userid)) {
        if (!$cm = get_coursemodule_from_id('congrea', $cmid)) {
            print_error('Course Module ID was incorrect');
        }
        $context = context_module::instance($cm->id);
        $ext = pathinfo(basename($file['qqfile']['name']), PATHINFO_EXTENSION); // Get file extension.
        $supported = congrea_is_format_supported_by_unoconv($ext); // Check file is supported by unoconv.
        if ($supported == 1) { // File is supported.
            $fs = get_file_storage();
            $file_record = array(
                'contextid' => $context->id, // ID of context.
                'component' => 'mod_congrea', // usually = table name.
                'filearea' => 'userdocument', // usually = table name.
                'itemid' => $cm->instance, // usually = ID of row in table.
                'filepath' => '/', // any path beginning and ending in.
                'filename' => basename($file['qqfile']['name']), // any filename
                'timecreated' => time(),
                'timemodified' => time(),
                'status' => true,
                'userid' => $userid);
            if ($existing = $fs->get_file($context->id, 'mod_congrea', 'userdocument', $cm->instance, '/', basename($file['qqfile']['name']))) {
                if ($existing) {
                    $unsuccess = array('status' => '0', 'code' => 200, 'message' => 'duplicate');
                    echo json_encode($unsuccess);
                    return false;
                }
            }
            $soucefile = $fs->create_file_from_pathname($file_record, $file['qqfile']['tmp_name']); // Save user source file into file api of moodle.
            if (!empty($soucefile)) {
                if ($getuserfile = $fs->get_file($file_record['contextid'], $file_record['component'], $file_record['filearea'], $file_record['itemid'], $file_record['filepath'], $file_record['filename'])) {
                    $pdfconversion = $fs->get_converted_document($getuserfile, 'pdf'); // convert user source file into pdf.
                    if (!empty($pdfconversion)) { // Pdf conversion is sucessfull.
                        $convertedpdfid = $pdfconversion->get_id();  // Get converted pdf file id
                        $pdffile = $pdfconversion->get_contenthash(); // Get contenthash of converted pdf for make path.
                    } else { // pdf conversion is unsuccessfull.
                        $unsuccess = array('status' => '0', 'code' => 200, 'message' => 'Pdf conversion is failed,Try again');
                        echo json_encode($unsuccess);
                        return false;
                    }
                }
            }
        } else {
            $unsuccess = array('status' => '0', 'code' => 200, 'message' => 'file are not supported by unoconv');
            echo json_encode($unsuccess);
            return false;
        }
        if (!empty($pdffile) && !empty($convertedpdfid)) { // Ensure pdf conversion is successfull.
            $uniqdir = "core_file/conversions/" . uniqid($convertedpdfid . "-", true);
            $newtmpfile = make_temp_directory($uniqdir); // Create tmp directory for store converted image.
            if (!empty($newtmpfile)) { // Tmp directory is created.
                $a = substr("$pdffile", 0, -38);
                $b = substr("$pdffile", 2, -36);
                $pdffilepath = "$CFG->dataroot/filedir/$a/$b/$pdffile"; // get converted pdf file path.
            }
            $quality = 90;
            $res = '300x300';
            exec("'gs' '-dNOPAUSE' '-sDEVICE=jpeg' '-dUseCIEColor' '-dTextAlphaBits=4' '-dGraphicsAlphaBits=4' '-o$newtmpfile/%03d.jpg' '-r$res' '-dJPEGQ=$quality' '$pdffilepath'", $output);
            if (touch($newtmpfile) == 1) { // pdf to image conversion is sucessfull.
                $convertedimagedir = $newtmpfile;
            } else {
                $unsuccess = array('status' => '0', 'code' => 200, 'message' => 'pdf to image conversion is failed, try again');
                echo json_encode($unsuccess);
                return false;
            }
            if (is_dir($convertedimagedir)) { // open tmp directory where images are saved
                if ($dh = opendir($convertedimagedir)) {
                    while (($file = readdir($dh)) !== false) {
                        if ($file != "." && $file != ".." && strtolower(substr($file, strrpos($file, '.') + 1)) == 'jpg') {
                            $images[] = $file; // collect all converted images from tmp directory.
                        }
                    }
                }
                closedir($dh);
            }
            if (!empty($images)) {
                sort($images); // Now images are in sequence.
                $path = '/' . $soucefile->get_filename() . '/';
                foreach ($images as $image) {
                    $fs = get_file_storage();
                    $images_record = array(
                        'contextid' => $context->id, // ID of context.
                        'component' => 'mod_congrea', // usually = table name.
                        'filearea' => 'documentimages', // usually = table name.
                        'itemid' => $cm->instance, // congrea id
                        'filepath' => $path, // any path beginning and ending in.
                        'filename' => $image, // any filename
                        'timecreated' => time(),
                        'timemodified' => time(),
                        'userid' => $userid,
                        'status' => true,
                        'source' => $soucefile->get_id(), // converted pdf id which ensures which pdf file converted into images.
                    );
                    if ($existing = $fs->get_file($images_record['contextid'], $images_record['component'], $images_record['filearea'], $images_record['itemid'], $images_record['filepath'], $images_record['filename'], $images_record['source'])) {
                        if ($existing) {
                            $unsuccess = array('status' => '0', 'code' => 200, 'message' => 'Unable to Save converted image, try again');
                            return false;
                        }
                    }
                    $success = $fs->create_file_from_pathname($images_record, "$convertedimagedir/$image"); // Save each images into moodle file api.
                    $imagesid[] = $success->get_id(); // ensures images are saved into  moodle Db.
                }
                if (!empty($success) && !empty($imagesid)) {
                    $senddaata = array('status' => '1', 'message' => 'success', 'resultdata' => (object) array('id' => $soucefile->get_id()), 'code' => 100);
                    echo json_encode($senddaata);
                    remove_dir($convertedimagedir); // After save files are in moodle api, tmp file is deleted.
                } else {
                    $unsuccess = array('status' => '0', 'code' => 200, 'message' => 'Failed');
                    echo json_encode($unsuccess);
                }
            }
        }
    }
}

/**
 * Retrieve all uploaded documents
 * serving for virtual class
 *
 * @param int $contextid
 * @param int $congreaid
 * @return json
 *
 */
function retrieve_docs($valparams) {
    global $CFG, $DB;
    list($cmid) = $valparams;
    if (!empty($cmid)) {
        if (!$cm = get_coursemodule_from_id('congrea', $cmid)) {
            print_error('Course Module ID was incorrect');
        }
        $context = context_module::instance($cm->id);
        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'mod_congrea', 'userdocument', $cm->instance);
        if (!empty($files)) {
            foreach ($files as $file) {
                if ($file->get_filename() != "." && $file->get_filename() != "..") {
                    $sql = "SELECT count(source) from {files} where contextid  = ? && itemid = ? && filearea = ? && filepath = ?";
                    $images = $DB->count_records_sql($sql, array('contextid' => $context->id, 'itemid' => $cm->instance, 'filearea' => 'documentimages', 'filepath' => '/' . $file->get_filename() . '/'));
                    $notes[] = array('id' => $file->get_id(), 'title' => $file->get_filename(), 'status' => $file->get_status(), 'pagecount' => $images);
                }
            }
            if (!empty($notes)) {
                $filedata = array('status' => 1, 'resultdata' => (object) array('NOTES' => $notes), 'code' => 100, 'message' => 'Success');
                echo json_encode($filedata);
            } else {
                $unsuccess = array('status' => '0', 'code' => 108, 'message' => 'Failed');
                echo json_encode($unsuccess);
            }
        } else {
            $unsuccess = array('status' => '0', 'code' => 108, 'message' => 'No any docs are available');
            echo json_encode($unsuccess);
        }
    } else {
        $unsuccess = array('status' => '0', 'code' => 108, 'message' => 'Invalid Access, Please login to access your account');
        echo json_encode($unsuccess);
    }
}

/**
 * Retrieve all document's images
 * serving for virtual class
 *
 * @param int $contextid
 * @param int $congreaid
 * @return json
 *
 */
function retrieve_all_notes($valparams) {
    global $CFG, $DB;
    list($cmid) = $valparams;
    if (!empty($cmid)) {
        if (!$cm = get_coursemodule_from_id('congrea', $cmid)) {
            print_error('Course Module ID was incorrect');
        }
        $context = context_module::instance($cm->id);
        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'mod_congrea', 'userdocument', $cm->instance);
        if (!empty($files)) {
            foreach ($files as $filedata) {
                if ($filedata->get_filename() != "." && $filedata->get_filename() != "..") {
                    $sourceid = $filedata->get_id(); // Source file id.
                    $sql = "SELECT filename, id, contextid, itemid, filepath, status, source from {files} where source = $sourceid && itemid = $cm->instance";
                    $fildata = $DB->get_records_sql($sql);
                    if (!empty($fildata)) {
                        foreach ($fildata as $file) {
                            if ($file->filename != "." && $file->filename != "..") {
                                $imageurl = file_encode_url($CFG->wwwroot . '/pluginfile.php', '/' . $file->contextid . '/mod_congrea/documentimages/' . $file->itemid . '/' . $file->filepath . '/' . $file->filename);
                                $content_path[] = array('id' => $file->id, 'content_id' => $file->itemid, 'lc_content_id' => $file->source, 'display_order' => 0, 'status' => $file->status, 'content_path' => $imageurl);
                            }
                        }
                    }
                }
            }
            if (!empty($content_path)) {
                $allimagedata = array('status' => 1, 'resultdata' => $content_path, 'code' => 100, 'message' => 'Success');
                echo json_encode($allimagedata);
            }
        } else {
            $unsuccess = array('status' => '0', 'code' => 108, 'message' => 'No any images are available');
            echo json_encode($unsuccess);
        }
    } else {
        $unsuccess = array('status' => '0', 'code' => 108, 'message' => 'Invalid Access, Please login to access your account');
        echo json_encode($unsuccess);
    }
}

/**
 * Verify the document format is supported.
 * serving for virtual class
 *
 * @param string $format The desired format - e.g. 'pdf'. Formats are specified by file extension.
 * @return bool - True if the format is supported for input.
 *
 */
function congrea_is_format_supported_by_unoconv($format) {
    global $CFG;
    if (!isset($unoconvformats)) {
        // Ask unoconv for it's list of supported document formats.
        $cmd = escapeshellcmd(trim($CFG->pathtounoconv)) . ' --show';
        $pipes = array();
        $pipesspec = array(2 => array('pipe', 'w'));
        $proc = proc_open($cmd, $pipesspec, $pipes);
        $programoutput = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        proc_close($proc);
        $matches = array();
        preg_match_all('/\[\.(.*)\]/', $programoutput, $matches);
        $unoconvformats = $matches[1];
        $unoconvformats = array_unique($unoconvformats);
    }
    $sanitized = trim(core_text::strtolower($format));
    return in_array($sanitized, $unoconvformats);
}

/**
 * Save page orders
 * serving for virtual class
 *
 * @param array $valparams
 * @return json to ensures page orders are sucessfully saved
 */
function congrea_page_order($valparams) {
    global $DB;
    if (!empty($valparams)) {
        list($postdata) = $valparams;
        if (empty($postdata['content_order']) && !empty($postdata['content_order_type']) && !empty($postdata['live_class_id'])) {
            $delete = $DB->delete_records('congrea_page_order', array('cmid' => $postdata['live_class_id'], 'contenttype' => $postdata['content_order_type']));
            if ($delete == 1) {
                $success = array('status' => '1', 'code' => 100, 'message' => 'order update Successfully');
                echo json_encode($success);
            } else {
                $unsuccess = array('status' => '0', 'code' => 200, 'message' => 'Failed');
                echo json_encode($unsuccess);
            }
        }
        if (!empty($postdata['content_order']) && !empty($postdata['content_order_type']) && !empty($postdata['live_class_id'])) {
            $exist = $DB->record_exists('congrea_page_order', array('cmid' => $postdata['live_class_id'], 'contenttype' => $postdata['content_order_type']));
            if (!$exist) {
                $obj = new stdClass();
                $obj->contenttype = $postdata['content_order_type'];
                $obj->cmid = $postdata['live_class_id'];
                $obj->orders = $postdata['content_order'];
                $obj->timecreated = time();
                $sucess = $DB->insert_record('congrea_page_order', $obj);
                if (!empty($sucess)) {
                    $success = array('status' => '1', 'code' => 100, 'message' => 'order save Successfully');
                    echo json_encode($success);
                } else {
                    $success = array('status' => '0', 'code' => 200, 'message' => 'Failed');
                    echo json_encode($success);
                }
            } else {
                $sucess = $DB->execute("UPDATE {congrea_page_order} SET orders = '" . $postdata['content_order'] . "' WHERE cmid = '" . $postdata['live_class_id'] . "' And contenttype = '" . $postdata['content_order_type'] . "'");
                if ($sucess == 1) {
                    $success = array('status' => '1', 'code' => 100, 'message' => 'order update Successfully');
                    echo json_encode($success);
                } else {
                    $unsuccess = array('status' => '0', 'code' => 200, 'message' => 'Failed');
                    echo json_encode($unsuccess);
                }
            }
        } else {
            $unsuccess = array('status' => '0', 'code' => 200, 'message' => 'Failed');
            echo json_encode($unsuccess);
        }
    } else {
        $unsuccess = array('status' => '0', 'code' => 200, 'message' => 'Failed');
        echo json_encode($unsuccess);
    }
}

/**
 * Retrieve page orders
 * serving for virtual class
 *
 * @param array $valparams
 * @return string
 */
function congrea_retrieve_page_order($valparams) {
    global $DB;
    if (!empty($valparams)) {
        list($postdata) = $valparams;
        if (!empty($postdata['content_order_type']) && !empty($postdata['live_class_id'])) {
            $orders = $DB->get_field('congrea_page_order', 'orders', array('cmid' => $postdata['live_class_id'], 'contenttype' => $postdata['content_order_type']));
            if ($orders) {
                echo json_encode($orders);
            } else {
                $unsuccess = array('status' => '0', 'code' => 200, 'message' => 'Failed');
                echo json_encode($unsuccess);
            }
        } else {
            $unsuccess = array('status' => '0', 'code' => 200, 'message' => 'Failed');
            echo json_encode($unsuccess);
        }
    } else {
        $unsuccess = array('status' => '0', 'code' => 200, 'message' => 'Failed');
        echo json_encode($unsuccess);
    }
}

/**
 * Returns list of users enrolled into course.
 *
 * @param int $data
 * @return array of user records
 */
function congrea_get_enrolled_users($data) {
    global $DB, $OUTPUT, $CFG;
    if (!empty($data)) {
        list($cmid) = $data;
        if (!$cm = get_coursemodule_from_id('congrea', $cmid)) {
            print_error('Course Module ID was incorrect');
        }
        $context = context_module::instance($cm->id);
        $withcapability = '';
        $groupid = 0;
        $userfields = "u.*";
        $orderby = null;
        $limitfrom = 0;
        $limitnum = 0;
        $onlyactive = false;
        list($esql, $params) = get_enrolled_sql($context, $withcapability, $groupid, $onlyactive);
        $sql = "SELECT $userfields
             FROM {user} u
             JOIN ($esql) je ON je.id = u.id
            WHERE u.deleted = 0";

        if ($orderby) {
            $sql = "$sql ORDER BY $orderby";
        } else {
            list($sort, $sortparams) = users_order_by_sql('u');
            $sql = "$sql ORDER BY $sort";
            $params = array_merge($params, $sortparams);
        }
        $list = $DB->get_records_sql($sql, $params, $limitfrom, $limitnum);
        if (!empty($list)) {
            foreach ($list as $userdata) {
                if ($userdata) {
                    $user = $userdata->id;
                    $name = $userdata->firstname . ' ' . $userdata->lastname;
                    $userpicture = moodle_url::make_pluginfile_url(context_user::instance($userdata->id)->id, 'user', 'icon', null, '/', 'f2');
                    $src = $userpicture->out(false);
                    $userlist[] = (object) array('userid' => $user, 'name' => $name, 'img' => $src, 'status' => 0);
                }
            }
            if (!empty($userlist)) {
                echo json_encode($userlist); // Return list of enrolled users.
            } else {
                $unsuccess = array('status' => '0', 'code' => 200, 'message' => 'Failed');
                echo json_encode($unsuccess);
            }
        } else {
            $unsuccess = array('status' => '0', 'code' => 200, 'message' => 'Failed');
            echo json_encode($unsuccess);
        }
    } else {
        $unsuccess = array('status' => '0', 'code' => 200, 'message' => 'Failed');
        echo json_encode($unsuccess);
    }
}

// Quiz functions

/**
 * Get all quizes with the details (timelimit, ques per page)
 * as an array of object for a specific course.
 *
 * @param array $postdata
 * @return json  quizes as an array of object
 */
function congrea_quiz($valparams) {
    global $DB;
    list($postdata) = $valparams;

    $cm = get_coursemodule_from_id('congrea', $postdata['cmid'], 0, false, MUST_EXIST);

    $quizes = $DB->get_records('quiz', array('course' => $cm->course), null, 'id, name, timelimit, preferredbehaviour, questionsperpage');

    if ($quizes) {
        echo( json_encode($quizes));
    } else {
        echo json_encode(array('status' => 0, 'message' => 'Quiz not found'));
    }
    //echo( json_encode($response_array));
}

/**
 * Attach a quiz with congrea activity.
 * @param array $postdata
 * @return boolean
 */
function congrea_add_quiz($valparams) {
    global $DB;
    list($postdata) = $valparams;
    $cm = get_coursemodule_from_id('congrea', $postdata['cmid'], 0, false, MUST_EXIST);
    if ($postdata['qzid']) {
        if ($DB->record_exists('congrea_quiz', array('congreaid' => $cm->instance, 'quizid' => $postdata['qzid']))) {
            return true;
        } else {
            $data = new stdClass();
            $data->congreaid = $cm->instance;
            $data->quizid = $postdata['qzid'];
            if ($DB->insert_record('congrea_quiz', $data)) {
                return true;
            }
        }
        // quiz not linked with congrea
    }
    return false;
}

/**
 * function to save quiz grade in table.
 * @param array $postdata
 * @return boolean
 */
function congrea_quiz_result($valparams) {
    global $DB;
    list($postdata) = $valparams;
    $cm = get_coursemodule_from_id('congrea', $postdata['cmid'], 0, false, MUST_EXIST);
    $con_quiz_id = $DB->get_field('congrea_quiz', 'id', array('congreaid' => $cm->instance, 'quizid' => $postdata['qzid']));
    if ($con_quiz_id) {
        //save grade
        $data = new stdClass();
        $data->congreaquiz = $con_quiz_id;
        $data->userid = $postdata['user'];
        $data->grade = $postdata['grade'];
        $data->timetaken = $postdata['timetaken'];
        $data->questionattempted = $postdata['qusattempted'];
        $data->currectanswer = $postdata['currectans'];
        $data->timecreated = time();
        if ($DB->insert_record('congrea_quiz_grade', $data)) {
            return true;
        } else {
            echo 'Grade not saved';
        }
    }
}

function congrea_file_path($args, $forcedownload, $options) {
    global $DB;
    $options = array('preview' => $options);
    $fs = get_file_storage();
    $relativepath = explode('/', $args);
    $hashpath = $DB->get_field('files', 'pathnamehash', array("contextid" => $relativepath[1],
        'component' => $relativepath[2], 'filearea' => $relativepath[3], 'itemid' => $relativepath[4], 'filename' => $relativepath[5]));

    if (!$file = $fs->get_file_by_hash($hashpath) or $file->is_directory()) {
        send_file_not_found();
    }
    send_stored_file($file, 0, 0, $forcedownload, $options);
}

function congrea_file_rewrite_pluginfile_urls($text, $file, $contextid, $component, $filearea, $itemid, $filename, array $options = null) {
    global $CFG;

    $options = (array) $options;
    if (!isset($options['forcehttps'])) {
        $options['forcehttps'] = false;
    }

    if (!$CFG->slasharguments) {
        $file = $file . '?file=';
    }

    $baseurl = "$CFG->wwwroot/$file/$contextid/$component/$filearea/";

    if ($itemid !== null) {
        $baseurl .= "$itemid/$filename";
    }

    if ($options['forcehttps']) {
        $baseurl = str_replace('http://', 'https://', $baseurl);
    }
    $replaceurl = "$CFG->wwwroot/$file/$contextid/$component/$filearea/$itemid/";
    return str_replace('@@PLUGINFILE@@/', $replaceurl, $text);
}

function congrea_formate_text($cmid, $questiondata, $text, $formate, $component, $filearea, $itemid) {
    global $PAGE, $DB;

    $context = context_module::instance($cmid);
    if (!empty($text)) {
        if (!isset($formate)) {
            $formate = FORMAT_HTML;
        }
        $pattern = '/src="@@PLUGINFILE@@\/(.*?)"/';
        preg_match($pattern, $text, $matches);
        if (!empty($matches)) {
            $filename = $matches[1];
            $f = 'mod/congrea/pluginfile.php';
            $contents = congrea_file_rewrite_pluginfile_urls($text, $f, $questiondata->contextid, $component, $filearea, $itemid, $filename);
            //print_r($contents);exit;
            return congrea_make_html_inline($contents);
        } else {
            return congrea_make_html_inline($text);
        }
    } else {
        return '';
    }
}

function congrea_make_html_inline($html) {
    $html = preg_replace('~\s*<p>\s*~u', '', $html);
    $html = preg_replace('~\s*</p>\s*~u', '<br />', $html);
    $html = preg_replace('~(<br\s*/?>)+$~u', '', $html);
    return trim($html);
}

/**
 * Get the quizjson object from given
 * quiz instance
 *
 * @param array $valparams array of quizid, cmid
 * @return quiz json
 */
function congrea_get_quizdata($valparams) {
    global $CFG, $DB;
    list($postdata) = $valparams;
    if (empty($postdata) || !is_array($postdata)) {
        print_r('invalid data');
        exit;
        //return array ("status" => 0, "message" =>'Invalid data');
    }

    $quizid = $postdata['qid'];
    $cm = get_coursemodule_from_id('congrea', $postdata['cmid'], 0, false, MUST_EXIST);

    if (!$qzcm = get_coursemodule_from_instance('quiz', $quizid, $cm->course)) {
        //print_error('invalidcoursemodule');
        print_r('invalidcoursemodule');
        exit;
        //return array ("status" => 0,"message" =>'Invalid course module');
    }

    require_once($CFG->dirroot . '/mod/quiz/locallib.php');
    $quizobj = quiz::create($qzcm->instance, $postdata['user']);

    if (!$quizobj->has_questions()) {
        print_r('No question in this quiz.');
        exit;
    }
    $quizgrade = $DB->get_field('quiz', 'grade', array('id' => $quizid, 'course' => $cm->course));
    //print_r($quizgrade);exit;
    $quizjson = array();
    $questions = array();
    $context = context_module::instance($cm->id);

    if (empty($quizjson)) {
        $quizobj->preload_questions();
        $quizobj->load_questions();

        $info = array("quiz" => $quizid, "name" => "",
            "main" => "", "results" => $quizgrade);
        foreach ($quizobj->get_questions() as $questiondata) {
            $options = array();
            $selectany = true;
            $forcecheckbox = false;
            if ($questiondata->qtype == 'multichoice') {
                foreach ($questiondata->options->answers as $ans) {
                    $correct = false;
                    // Get score if 100% answer correct if only one answer allowed.
                    $correct = $ans->fraction > 0.9 ? true : false;
                    if (!empty($questiondata->options->single) && $questiondata->options->single < 1) {
                        $selectany = false;
                        $forcecheckbox = true;
                        // Get score if all option selected in multiple answer.
                        $correct = $ans->fraction > 0 ? true : false;
                    }
                    $answer = congrea_formate_text($cm->id, $questiondata, $ans->answer, $ans->answerformat, 'question', 'answer', $ans->id);
                    $options[] = array("option" => $answer, "correct" => $correct);
                }
                /*
                  $questiontext = congrea_question_rewrite_question_preview_urls($questiondata->questiontext, $questiondata->id, $questiondata->contextid, 'question', 'questiontext', $questiondata->id,
                  $context->id, 'quiz_statistics');
                 */
                $questiontext = congrea_formate_text($cm->id, $questiondata, $questiondata->questiontext, $questiondata->questiontextformat, 'question', 'questiontext', $questiondata->id);
                $questions[] = array("q" => $questiontext, "a" => $options,
                    "qid" => $questiondata->id,
                    "correct" => !empty($questiondata->options->correctfeedback) ? $questiondata->options->correctfeedback : "Your answer is correct.",
                    "incorrect" => !empty($questiondata->options->incorrectfeedback) ? $questiondata->options->incorrectfeedback : "Your answer is incorrect.",
                    "select_any" => $selectany,
                    "force_checkbox" => $forcecheckbox);
            }
        }
        $qjson = array("info" => $info, "questions" => $questions);
        //$quizjson = addslashes(json_encode($qjson));
        $quizjson = json_encode($qjson);
    }
    echo $quizjson;
    //return $quizjson;
}

function ppt_save($valparams) { // To do-for function name
    global $CFG, $DB;
    if (!empty($valparams)) {
        list($postdata, $cmid, $userid) = $valparams;
        if (!empty($postdata) && !empty($cmid) && !empty($userid)) {
            if (!$cm = get_coursemodule_from_id('congrea', $cmid)) {
                print_error('Course Module ID was incorrect');
            }
            $obj = new stdClass();
            $obj->congreaid = $cm->instance;
            $obj->userid = $userid;
            $obj->resource = $postdata['content_path'];
            $obj->type = $postdata['type'];
            $obj->status = 1;
            $obj->timecreated = time();
            $sucess = $DB->insert_record('congrea_mediafiles', $obj);
            if (!empty($sucess)) {
                $suceess = array('status' => '1', 'message' => 'success', 'resultdata' => array('id' => $sucess), 'code' => 100);
                echo json_encode($suceess);
            } else {
                $unsuccess = array('status' => '0', 'code' => 200, 'message' => 'Failed');
                echo json_encode($unsuccess);
            }
        }
    } else {
        $unsuccess = array('status' => '0', 'code' => 200, 'message' => 'Failed');
        echo json_encode($unsuccess);
    }
}

function congrea_retrieve_ppt($valparams) {
    global $CFG, $DB;
    list($postdata, $cmid, $userid) = $valparams;
    if (!empty($postdata) && !empty($cmid) && !empty($userid)) {
        if (!$cm = get_coursemodule_from_id('congrea', $cmid)) {
            print_error('Course Module ID was incorrect');
        }
        $records = $DB->get_records('congrea_mediafiles', array('congreaid' => $cm->instance, 'type' => $postdata['type']));
        if (!empty($records)) {
            foreach ($records as $record) {
                if ($record->type == 'ppt') { // For videos which are save in moodle file api.
                    $ppt = new stdClass();
                    $ppt->id = $record->id;
                    $ppt->title = $record->resource;
                    $ppt->status = $record->status;
                    $ppt->type = $record->type;
                    $ppt->content_path = $record->resource;
                    $pptlist[] = $ppt;
                }
            }
        }
        if (!empty($pptlist)) {
            //print_r($videolist);
            echo json_encode($pptlist);
        } else {
            $unsuccess = array('status' => '0', 'code' => 108, 'message' => 'noPPt');
            echo json_encode($unsuccess);
        }
    } else {
        $unsuccess = array('status' => '0', 'code' => 108, 'message' => 'noPPt');
        echo json_encode($unsuccess);
    }
}

function update_ppt($valparams){
    global $CFG, $DB;
    list($postdata, $cmid, $userid) = $valparams;
        if (!empty($postdata) && !empty($cmid) && !empty($userid)) {
            if (!$cm = get_coursemodule_from_id('congrea', $cmid)) {
                print_error('Course Module ID was incorrect');
            }
            $records = $DB->get_records('congrea_mediafiles', array('congreaid' => $cm->instance, 'type' => $postdata['type']));
            if (!empty($records)) {
                foreach ($records as $record) {
                    if ($record->type == 'ppt') { // For videos which are save in moodle file api.
                        $ppt = new stdClass();
                        $ppt->id = $record->id;
                        $ppt->title = $record->resource;
                        $ppt->status = $record->status;
                        $ppt->type = $record->type;
                        $ppt->content_path = $record->resource;
                        $pptlist[] = $ppt;
                    }
                }
            }
            if (!empty($pptlist)) {
                //print_r($videolist);
                echo json_encode($pptlist);
            } else {
                $unsuccess = array('status' => '0', 'code' => 108, 'message' => 'noPPt');
                echo json_encode($unsuccess);
            }
        } else {
            $unsuccess = array('status' => '0', 'code' => 108, 'message' => 'noPPt');
            echo json_encode($unsuccess);
        }

}

