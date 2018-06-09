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

/***************************** Congrea Files *****************************************/
defined('MOODLE_INTERNAL') || die();

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
        exit; // Course module ID missing.
    }
    require_login($course, true, $cm);
    $context = context_module::instance($cm->id);
    $basefilepath = $CFG->dataroot . "/congrea"; // Place to save recording files.
    if (has_capability('mod/congrea:dorecording', $context)) {
        if ($data) {
            $filepath = $basefilepath . "/" . $course->id . "/" . $congrea->id . "/" . $vmsession;
            // Create folder if not exist.
            if (!file_exists($filepath)) {
                mkdir($filepath, 0777, true);
            }
            $filename = "vc." . $filenum;
            if (file_put_contents($filepath . '/' . $filename, $data) != false) {
                // Save file record in database.
                if ($filenum > 1) {
                    // Update record.
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
                    $DB->insert_record('congrea_files', $vcfile);
                }
                echo "done";
            } else {
                echo 'VCE5'; // Unable to record data.
            }
        } else {
            echo 'VCE4'; // No data for recording.
        }
    } else {
        echo 'VCE2'; // Permission denied.
    }
}

/***********************Congrea Poll*******************/

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
        $responsearray = array();
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
                    $responsearray[] = $options;
                }
            }
            $obj->qid = $questionid;
            $obj->createdby = $question->createdby;
            $obj->question = $question->description;
            $obj->createdby = $question->createdby;
            $obj->category = $question->category;
            $obj->options = $responsearray;
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
            if ($temp == 1) {
                echo $temp;
            } else {
                echo get_string('deletepolloption', 'congrea'); // Delete operation is Unsucessfull.
            }
        }
    } else {
        echo get_string('polloptiondeletefailed', 'congrea'); // Delete is Failed,Try again.
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
        $responsearray = array();
        $category = json_decode($postdata['category']);
        $userid = json_decode($postdata['user']);
        $questiondata = $DB->get_records('congrea_poll_question', array('category' => $category));
        if ($questiondata) {
            foreach ($questiondata as $data) {
                $username = $DB->get_field('user', 'username', array('id' => $data->createdby));
                $result = $DB->record_exists('congrea_poll_attempts', array('qid' => $data->id));
                $sql = "SELECT id, options from {congrea_poll_question_option} where qid = $data->id";
                $optiondata = $DB->get_records_sql($sql);
                $polllist = array('questionid' => $data->id,
                                'category' => $data->category,
                                'createdby' => $data->createdby,
                                'questiontext' => $data->description,
                                'options' => $optiondata,
                                'creatorname' => $username,
                                'isPublished' => $result);
                $responsearray[] = $polllist;
            }
        }
        $admins = get_admins(); // Check user is site admin.
        if (!empty($admins) && !empty($admins[$userid]->id)) {
            if ($admins[$userid]->id == $userid) {
                $responsearray[] = "true";
            } else {
                $responsearray[] = "false";
            }
        } else {
            $responsearray[] = "false";
        }
        echo json_encode($responsearray);
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
             // Ensures which type of poll(site or course) will be deleted.
            $category = $DB->get_field('congrea_poll_question', 'category', array('id' => "$id"));
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
        $responsearray = array();
        $obj = new stdClass();
        $data = json_decode($postdata['editdata']);
        $category = $DB->get_field('congrea_poll_question', 'category', array('id' => "$data->questionid"));
        $quesiontext = $DB->execute("UPDATE {congrea_poll_question} "
                . "SET description = '" . $data->question . "' WHERE id = '" . $data->questionid . "'");
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
                $responsearray[] = $newoptions;
            }
        }
        $obj->qid = $data->questionid;
        $obj->question = $data->question;
        $obj->createdby = $data->createdby;
        $obj->category = $category;
        $obj->options = $responsearray;
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
                    $userpicture = moodle_url::make_pluginfile_url(context_user::instance($userdata->id)->id,
                                                                'user', 'icon', null, '/', 'f2');
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

/********************** Quiz functions*******************/

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
    $quizes = $DB->get_records('quiz', array('course' => $cm->course), null,
                            'id, name, course, timelimit, preferredbehaviour, questionsperpage');
    if ($quizes) {
        foreach ($quizes as $data) {
            $questiontype = congrea_question_type($data->id); // Check quiz question type is multichoce or not.
            if ($questiontype) {
                $quizcm = get_coursemodule_from_instance('quiz', $data->id, $data->course, false, MUST_EXIST);
                if ($quizcm->id) {
                    $quizstatus = $DB->get_field('course_modules', 'deletioninprogress', array('id' => $quizcm->id,
                                                                                        'instance' => $data->id,
                                                                                        'course' => $data->course));
                    $quizdata[$data->id] = (object) array('id' => $data->id,
                                                'name' => $data->name,
                                                'timelimit' => $data->timelimit,
                                                'preferredbehaviour' => $data->preferredbehaviour,
                                                'questionsperpage' => $data->questionsperpage,
                                                'quizstatus' => $quizstatus);
                } else {
                    echo json_encode(array('status' => 0, 'message' => 'Quiz not found'));
                }
            }
        }
    } else {
        echo json_encode(array('status' => 0, 'message' => 'Quiz not found'));
    }
    if ($quizdata) {
        echo(json_encode($quizdata));
    } else {
        echo json_encode(array('status' => 0, 'message' => 'Quiz not found'));
    }
}

/**
 * function to get quiz question type
 * @param array $quizid
 * @return boolean
 */
function congrea_question_type($quizid, $type = 'multichoice') {
    global $DB;
    $sql = "SELECT qs.questionid, q.qtype
                FROM {quiz_slots} qs
                INNER JOIN
                    {question} q
                ON qs.questionid = q.id where quizid = '" . $quizid . "'";
    $questions = $DB->get_records_sql($sql);
    if (!empty($questions)) {
        foreach ($questions as $questiondata) {
            if ($questiondata->qtype == $type) { // Only support multichoice type question.
                return true;
            }
        }
        return false;
    }
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
        // Quiz not linked with congrea.
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
    $conquizid = $DB->get_field('congrea_quiz', 'id', array('congreaid' => $cm->instance, 'quizid' => $postdata['qzid']));
    if ($conquizid) {
        // Save grade.
        $data = new stdClass();
        $data->congreaquiz = $conquizid;
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
                                                            'component' => $relativepath[2],
                                                            'filearea' => $relativepath[3],
                                                            'itemid' => $relativepath[4],
                                                            'filename' => $relativepath[5]));

    if (!$file = $fs->get_file_by_hash($hashpath) or $file->is_directory()) {
        send_file_not_found();
    }
    send_stored_file($file, 0, 0, $forcedownload, $options);
}

function congrea_file_rewrite_pluginfile_urls($text, $file,
        $contextid, $component, $filearea, $itemid, $filename, array $options = null) {
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
            $contents = congrea_file_rewrite_pluginfile_urls($text,
                                                            $f,
                                                            $questiondata->contextid,
                                                            $component,
                                                            $filearea,
                                                            $itemid,
                                                            $filename);
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
        echo 'invalid data';
        exit;
    }
    $quizid = $postdata['qid'];
    $cm = get_coursemodule_from_id('congrea', $postdata['cmid'], 0, false, MUST_EXIST);

    if (!$qzcm = get_coursemodule_from_instance('quiz', $quizid, $cm->course)) {
        echo 'invalidcoursemodule';
        exit;
    }

    require_once($CFG->dirroot . '/mod/quiz/locallib.php');
    $quizobj = quiz::create($qzcm->instance, $postdata['user']);

    if (!$quizobj->has_questions()) {
        echo 'No question in this quiz';
        exit;
    }
    $quizgrade = $DB->get_field('quiz', 'grade', array('id' => $quizid, 'course' => $cm->course));

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
                    $answer = congrea_formate_text($cm->id,
                                                $questiondata,
                                                $ans->answer,
                                                $ans->answerformat,
                                                'question', 'answer',
                                                $ans->id);
                    $options[] = array("option" => $answer, "correct" => $correct);
                }
                $questiontext = congrea_formate_text($cm->id, $questiondata, $questiondata->questiontext,
                                $questiondata->questiontextformat, 'question', 'questiontext', $questiondata->id);
                $questions[] = array("q" => $questiontext, "a" => $options,
                    "qid" => $questiondata->id,
                    "correct" => !empty($questiondata->options->correctfeedback) ? $questiondata->options->correctfeedback : "Your answer is correct.",
                    "incorrect" => !empty($questiondata->options->incorrectfeedback) ? $questiondata->options->incorrectfeedback : "Your answer is incorrect.",
                    "select_any" => $selectany,
                    "force_checkbox" => $forcecheckbox);
            }
        }
        $qjson = array("info" => $info, "questions" => $questions);
        $quizjson = json_encode($qjson);
    }
    echo $quizjson;
}
