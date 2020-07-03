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
 * Form to edit uploaded file name
 *
 * It uses the standard core Moodle formslib. For more info about them, please
 * visit: http://docs.moodle.org/en/Development:lib/formslib.php
 *
 * @package    mod_congrea
 * @copyright  2020 vidyamantra.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');
require_once($CFG->dirroot . '/mod/congrea/locallib.php');

/**
 * File update name form
 *
 * @copyright  2020 vidyamantra.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or late
 */
class mod_congrea_session_form extends moodleform {

    /**
     * Defines forms elements
     */
    public function definition() {
        global $DB;
        $mform = $this->_form;
        $id = $this->_customdata['id'];
        $edit = $this->_customdata['edit'];
        $action = $this->_customdata['action'];
        $conflictstatus = $this->_customdata['conflictstatus'];
        $mform->setType('sessionsettings', PARAM_INT);
        $mform->addElement('hidden', 'id', $id);
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'edit', $edit);
        $mform->setType('edit', PARAM_INT);
        $mform->addElement('hidden', 'action', $action);
        $mform->setType('action', PARAM_CLEANHTML);
        $mform->addElement('hidden', 'conflictstatus', $conflictstatus);
        $mform->setType('conflictstatus', PARAM_CLEANHTML);
        if (!$edit) {
            $mform->addElement('header', 'sessionsheader', get_string('sessionsettings', 'mod_congrea'));
            $mform->addElement('date_time_selector', 'fromsessiondate', get_string('fromsessiondate', 'congrea'));
            $mform->addHelpButton('fromsessiondate', 'fromsessiondate', 'congrea');
            $mform->setType('fromsessiondate', PARAM_CLEANHTML);
        } else {
            $record = $DB->get_record('event', array('id' => $edit));
            $startdate = $record->timestart;
            $attributes = array(
            'optional'  => $record->timestart
            );
            $mform->addElement('hidden', 'fromsessiondate', get_string('fromsessiondate', 'congrea'), $attributes);
            $mform->setType('fromsessiondate', PARAM_CLEANHTML);
        }
        $mform->setType('timeduration', PARAM_INT);
        $durationfield = array();
        $durationfield[] =& $mform->createElement('text', 'timeduration', '', array('size' => 4));
        $durationfield[] =& $mform->createElement('static', 'repeattext', '', get_string('mins', 'congrea'));
        $mform->addGroup($durationfield, 'timeduration', get_string('timeduration', 'congrea'), array(' '), false);
        $mform->addHelpButton('timeduration', 'timeduration', 'congrea');
        $mform->addRule('timeduration', get_string('blankduration', 'congrea'), 'required', null, 'client');
        $mform->addRule('timeduration', get_string('blankduration', 'congrea'), 'numeric', null, 'client');
        // Select teacher.
        $teacheroptions = congrea_course_teacher_list($id);
        $mform->addElement('select', 'moderatorid', get_string('selectteacher', 'congrea'), $teacheroptions);
        $mform->addHelpButton('moderatorid', 'selectteacher', 'congrea');
        // Repeat.
        $mform->addElement('advcheckbox', 'addmultiple', '',
        get_string('repeatevent', 'calendar'),
        array('group' => 1), array(0, 1));
        $mform->disabledIf('addmultiple', 'timeduration', 'eq', 0);
        $mform->disabledIf('repeattext', 'timeduration', 'eq', 0);
        $week = array(2 => 2, 3, 4, 5, 6, 7, 8, 9, 10);
        $weeks = array();
        $weeks[] = $mform->createElement('select', 'week', '', $week, false, true);
        $weeks[] = $mform->createElement('static', 'weekdesc', '', get_string('sessions', 'congrea'));
        $mform->addGroup($weeks, 'weeks', get_string('repeatweeksl', 'calendar'), '', false);
        $mform->hideIf('weeks', 'timeduration', 'eq', 0);
        $mform->hideIf('weeks', 'addmultiple', 'eq', 0);
        if (!empty($conflictstatus)) {
            $sortedconflicts = array();
            foreach ($conflictstatus as $value) {
                $sortedconflicts[serialize($value)] = $value;
            }
            $conflictstatus = array_values($sortedconflicts);
            $mform->addElement('html', '<div class="alert alert-danger">' . get_string('timeclashed', 'congrea') . '</div>');
            $mform->addElement('html', '<div class="overflow"><table class="generaltable"><tr><th>' .
            get_string('scheduleid', 'congrea') . '</th><th>' . get_string('dateandtime', 'congrea') .
            '</th><th>' . get_string('timedur', 'congrea') . '</th><th>' . get_string('teacher', 'congrea') .
            '</th><th>' . get_string('repeatstatus', 'congrea') . '</th></tr>');
            $count = 0;
            foreach ($conflictstatus as $conflictedevent) {
                $schedule = userdate($conflictedevent->starttime);
                $day = date('D', $conflictedevent->starttime);
                $duration = $conflictedevent->endtime - $conflictedevent->starttime;
                if ($conflictedevent->repeatid == 0) {
                    $mform->addElement('html', '<tr><td>#' . $conflictedevent->id . '</td><td>' . $schedule .
                    '</td><td>' . sectohour($duration) . '</td><td>' . $conflictedevent->presenter .
                    '</td><td> - </td></tr>');
                    $count++;
                } else {
                    if ($conflictedevent->repeatid == $conflictedevent->id) {
                        $mform->addElement('html', '<tr><td>#' . $conflictedevent->id . '</td><td>' . $schedule .
                        '</td><td>' . sectohour($duration) . '</td><td>' . $conflictedevent->presenter .
                        '</td><td>' . (int)$conflictedevent->description .
                        get_string('weeksevery', 'congrea') . $day . '</td></tr>');
                        $count++;
                    } else {
                        $mform->addElement('html', '<tr><td>#' . $conflictedevent->repeatid . '</td><td>' . $schedule .
                        '</td><td>' . sectohour($duration) . '</td><td>' . $conflictedevent->presenter .
                        '</td><td>' . (int)$conflictedevent->description .
                        get_string('weeksevery', 'congrea') . $day . '</td></tr>');
                        $count++;
                    }
                }
            }
            $mform->addElement('html', '</table></div>');
            $mform->addElement('html', '<h5 class="overflow">' . get_string('totalconflicts', 'congrea') . $count . '</h5>');
        }
        $this->add_action_buttons();
    }
    /**
     * Validate this form.
     * @param array $data submitted data
     * @param array $files not used
     * @return array errors
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        $durationinminutes = $data['timeduration'];
        $currentdate = time();
        if ($data['fromsessiondate'] < $currentdate - 600) {
            $errors['fromsessiondate'] = get_string('esessiondate', 'congrea');
        }
        if (($durationinminutes != 0) || ($durationinminutes != '')) {
            if ((($durationinminutes >= 1) && ($durationinminutes < 10)) || ($durationinminutes > 1439 )) {
                $errors['timeduration'] = get_string('errortimeduration', 'congrea');
            }
        }
        if (empty($data['moderatorid'])) {
            $errors['moderatorid'] = get_string('enrolteacher', 'congrea');
        }
        return $errors;
    }
}