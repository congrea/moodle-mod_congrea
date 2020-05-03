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
 * Congrea module internal API.
 *
 *
 * @package   mod_congrea
 * @copyright 2020 vidyamantra.com
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/* * *********************Congrea Poll****************** */

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
        $cm = get_coursemodule_from_id('congrea', $cmid, 0, false, MUST_EXIST);
        $datatosave = json_decode($postdata['dataToSave']);
        if (!empty($datatosave) && !empty($cm)) {
            $question = new stdClass();
            if (!empty($datatosave->category)) { // Poll is course level.
                $question->courseid = $cm->course;
            } else { // Poll is site level.
                $question->courseid = 0;
            }
            $question->instanceid = $cm->instance;
            $question->pollquestion = $datatosave->question;
            $question->createdby = $userid;
            $question->timecreated = time();
            $questionid = $DB->insert_record('congrea_poll', $question);
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
            $obj->question = $question->pollquestion;
            $obj->createdby = $question->createdby;
            $obj->category = $datatosave->category; // To do.
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
        $pollcategory = json_decode($postdata['category']);
        if ($pollcategory) { // Check not zero.
            $cm = get_coursemodule_from_id('congrea', $pollcategory, 0, false, MUST_EXIST);
            $category = $cm->course;
        } else {
            $category = 0;
        }
        $userid = json_decode($postdata['user']);
        $questiondata = $DB->get_records('congrea_poll', array('courseid' => $category));
        if ($questiondata) {
            foreach ($questiondata as $data) {
                $userdeatils = $DB->get_record('user', array('id' => $data->createdby));
                if (!empty($userdeatils)) {
                    $userfullname = $userdeatils->firstname . ' ' . $userdeatils->lastname; // Todo-for function.
                    $username = $userdeatils->username;
                } else {
                    $userfullname = get_string('nouser', 'mod_congrea');
                    $username = get_string('nouser', 'mod_congrea');
                }
                $result = $DB->record_exists('congrea_poll_attempts', array('qid' => $data->id));
                $sql = "SELECT id, options from {congrea_poll_question_option} where qid = $data->id";
                $optiondata = $DB->get_records_sql($sql);
                if ($data->courseid) { // Category not zero.
                    $getcm = get_coursemodule_from_instance('congrea', $data->instanceid, $data->courseid, false, MUST_EXIST);
                    $datacategory = $getcm->id;
                } else {
                    $datacategory = 0;
                }
                $polllist = array(
                    'questionid' => $data->id,
                    'category' => $datacategory,
                    'createdby' => $data->createdby,
                    'questiontext' => $data->pollquestion,
                    'options' => $optiondata,
                    'creatorname' => $username,
                    'creatorfname' => $userfullname,
                    'isPublished' => $result
                );
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
        echo get_string('pollretrievefail', 'congrea');
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
            $pollcategory = $DB->get_record_sql("SELECT courseid, instanceid FROM {congrea_poll} WHERE id = $id");
            if ($pollcategory->courseid) { // Category is not zero.
                $cm = get_coursemodule_from_instance(
                        'congrea', $pollcategory->instanceid, $pollcategory->courseid, false, MUST_EXIST
                );
                $category = $cm->id;
            } else {
                $category = 0;
            }
            $delresult = $DB->delete_records('congrea_poll_attempts', array('qid' => "$id"));
            $deloptions = $DB->delete_records('congrea_poll_question_option', array('qid' => "$id"));
            if ($deloptions) {
                $DB->delete_records('congrea_poll', array('id' => "$id"));
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
        $pollcategory = $DB->get_record_sql("SELECT courseid, instanceid FROM {congrea_poll} WHERE id = $data->questionid");
        if ($pollcategory->courseid) { // Category is not zero.
            $cm = get_coursemodule_from_instance('congrea', $pollcategory->instanceid, $pollcategory->courseid, false, MUST_EXIST);
            $category = $cm->id;
        } else {
            $category = 0;
        }
        $quesiontext = $DB->execute("UPDATE {congrea_poll} "
                . "SET pollquestion = '" . $data->question . "' WHERE id = '" . $data->questionid . "'");
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
            $pollcategory = $DB->get_record_sql("SELECT courseid, instanceid FROM {congrea_poll} WHERE id = $data->qid");
            if ($pollcategory->courseid) { // Category is not zero.
                $cm = get_coursemodule_from_instance(
                        'congrea', $pollcategory->instanceid, $pollcategory->courseid, false, MUST_EXIST
                );
                $category = $cm->id;
            } else {
                $category = 0;
            }
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
 * Returns list of users enrolled into course
 * serving for virtual class
 *
 * @param int $data
 * @return array of user records
 */
function congrea_get_enrolled_users($data) {
    global $DB, $OUTPUT, $CFG;
    if (!empty($data)) {
        list($cmid) = $data;
        if (!$cm = get_coursemodule_from_id('congrea', $cmid)) {
            print_error(get_string('incorrectcmid', 'congrea'));
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
                    if ($userdata->picture) { // Check user picture is available or not.
                        $userpicture = moodle_url::make_pluginfile_url(
                                        context_user::instance($userdata->id)->id, 'user', 'icon', null, '/', 'f2'
                        );
                        $src = $userpicture->out(false);
                    } else {
                        $src = 'noimage';
                    }
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

/* * ******************** Quiz functions****************** */

/**
 * Get all quizes with the details (timelimit, ques per page)
 * as an array of object for a specific course.
 *
 * @param array $valparams
 * @return json quizes as an array of object
 */
function congrea_quiz($valparams) {
    global $DB, $CFG;
    list($postdata) = $valparams;
    $cm = get_coursemodule_from_id('congrea', $postdata['cmid'], 0, false, MUST_EXIST);
    $quizes = $DB->get_records(
            'quiz', array('course' => $cm->course), null, 'id, name, course, timelimit, preferredbehaviour, questionsperpage'
    );
    if ($quizes) {
        foreach ($quizes as $data) {
            $questiontype = congrea_question_type($data->id); // Check quiz question type is multichoce or not.
            if ($questiontype) {
                $quizcm = get_coursemodule_from_instance('quiz', $data->id, $data->course, false, MUST_EXIST);
                if ($quizcm->id && $quizcm->visible) {
                    $quizstatus = 0;
                    if ($CFG->version >= 2016120500) { // Compare with moodle32 version.
                        $quizstatus = $DB->get_field(
                                'course_modules', 'deletioninprogress',
                                array('id' => $quizcm->id, 'instance' => $data->id, 'course' => $data->course)
                        );
                    }
                    $quizdata[$data->id] = (object) array(
                                'id' => $data->id,
                                'name' => $data->name,
                                'timelimit' => $data->timelimit,
                                'preferredbehaviour' => $data->preferredbehaviour,
                                'questionsperpage' => $data->questionsperpage,
                                'quizstatus' => $quizstatus
                    );
                }
            }
        }
        if (!empty($quizdata)) {
            echo (json_encode($quizdata));
        } else {
            echo json_encode(array('status' => 0, 'message' => 'Quiz not found'));
        }
    } else {
        echo json_encode(array('status' => 0, 'message' => 'Quiz not found'));
    }
}

/**
 * function to get quiz question type
 * serving for virtual class
 *
 * @param array $quizid
 * @param string $type
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
 * Attach a quiz with congrea activity
 * serving for virtual class
 *
 * @param array $valparams
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
 * function to save quiz grade in table
 * serving for virtual class
 *
 * @param array $valparams
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
            echo get_string('gradenotsaved', 'congrea');
        }
    }
}

/**
 * function to get file path
 * serving for virtual class
 *
 * @param string $args
 * @param bool $forcedownload
 * @param array $options
 * @return bool false if file not found, does not return if found - justsend the file
 */
function congrea_file_path($args, $forcedownload, $options) {
    global $DB;
    $options = array('preview' => $options);
    $fs = get_file_storage();
    $relativepath = explode('/', $args);
    $hashpath = $DB->get_field('files', 'pathnamehash', array(
        "contextid" => $relativepath[1],
        'component' => $relativepath[2],
        'filearea' => $relativepath[3],
        'itemid' => $relativepath[4],
        'filename' => $relativepath[5]
    ));

    if (!$file = $fs->get_file_by_hash($hashpath) or $file->is_directory()) {
        send_file_not_found();
    }
    send_stored_file($file, 0, 0, $forcedownload, $options);
}

/**
 * Convert encoded URLs in $text from the @@PLUGINFILE@@/... form to an actual URL.
 * serving for virtual class
 *
 * @param string $text The content that may contain ULRs in need of rewriting.
 * @param string $file The script that should be used to serve these files. pluginfile.php, draftfile.php, etc.
 * @param int $contextid This parameter and the next two identify the file area to use.
 * @param string $component
 * @param string $filearea helps identify the file area.
 * @param int $itemid helps identify the file area.
 * @param string $filename helps identify the filename
 * @param array $options text and file options ('forcehttps'=>false), use reverse = true to reverse the behaviour of the function.
 * @return string the processed text.
 */
function congrea_file_rewrite_pluginfile_urls(
$text, $file, $contextid, $component, $filearea, $itemid, $filename, array $options = null
) {
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

/**
 * function to formate text
 * serving for virtual class
 *
 * @param int $cmid
 * @param object $questiondata
 * @param string $text
 * @param string $formate
 * @param string $component
 * @param string $filearea helps identify the file area.
 * @param int $itemid helps identify the file area.
 * @return string
 */
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
            $contents = congrea_file_rewrite_pluginfile_urls(
                    $text, $f, $questiondata->contextid, $component, $filearea, $itemid, $filename
            );
            return congrea_make_html_inline($contents);
        } else {
            return congrea_make_html_inline($text);
        }
    } else {
        return '';
    }
}

/**
 * function to convert text in inline
 * serving for virtual class
 *
 * @param string $html
 * @return string
 */
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
        echo get_string('invaliddata', 'congrea');
        exit;
    }
    $quizid = $postdata['qid'];
    $cm = get_coursemodule_from_id('congrea', $postdata['cmid'], 0, false, MUST_EXIST);

    if (!$qzcm = get_coursemodule_from_instance('quiz', $quizid, $cm->course)) {
        echo get_string('invalidcoursemodule', 'congrea');
        exit;
    }

    require_once($CFG->dirroot . '/mod/quiz/locallib.php');
    $quizobj = quiz::create($qzcm->instance, $postdata['user']);

    if (!$quizobj->has_questions()) {
        echo get_string('noquestionsinquiz', 'congrea');
        exit;
    }
    $quizgrade = $DB->get_field('quiz', 'grade', array('id' => $quizid, 'course' => $cm->course));

    $quizjson = array();
    $questions = array();
    $context = context_module::instance($cm->id);
    if (empty($quizjson)) {
        $quizobj->preload_questions();
        $quizobj->load_questions();
        $info = array(
            "quiz" => $quizid, "name" => "",
            "main" => "", "results" => $quizgrade
        );
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
                    $answer = congrea_formate_text(
                            $cm->id, $questiondata, $ans->answer, $ans->answerformat, 'question', 'answer', $ans->id
                    );
                    $options[] = array("option" => $answer, "correct" => $correct);
                }
                $questiontext = congrea_formate_text(
                        $cm->id, $questiondata, $questiondata->questiontext,
                        $questiondata->questiontextformat, 'question', 'questiontext', $questiondata->id
                );
                $questions[] = array(
                    "q" => $questiontext, "a" => $options,
                    "qid" => $questiondata->id,
                    "correct" => !empty($questiondata->options->correctfeedback) ?
                    $questiondata->options->correctfeedback : "Your answer is correct.",
                    "incorrect" => !empty($questiondata->options->incorrectfeedback) ?
                    $questiondata->options->incorrectfeedback : "Your answer is incorrect.",
                    "select_any" => $selectany,
                    "force_checkbox" => $forcecheckbox
                );
            }
        }
        $qjson = array("info" => $info, "questions" => $questions);
        $quizjson = json_encode($qjson);
    }
    echo $quizjson;
}
