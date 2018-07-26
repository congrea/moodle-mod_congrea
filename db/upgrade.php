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
 * This file keeps track of upgrades to the congrea module
 *
 * Sometimes, changes between versions involve alterations to database
 * structures and other major things that may break installations. The upgrade
 * function in this file will attempt to perform all the necessary actions to
 * upgrade your older installation to the current version. If there's something
 * it cannot do itself, it will tell you what you need to do.  The commands in
 * here will all be database-neutral, using the functions defined in DLL libraries.
 *
 * @package    mod_congrea
 * @copyright  2018 Pinky Sharma
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/**
 * Execute congrea upgrade from the given old version
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_congrea_upgrade($oldversion) {
    global $DB, $CFG;

    require_once($CFG->libdir . '/db/upgradelib.php');

    $dbman = $DB->get_manager(); // Loads ddl manager and xmldb classes.

    /*
     * And upgrade begins here. For each one, you'll need one
     * block of code similar to the next one. Please, delete
     * this comment lines once this file start handling proper
     * upgrade code.
     *
     * if ($oldversion < YYYYMMDD00) { //New version in version.php
     * }
     *
     * Lines below (this included)  MUST BE DELETED once you get the first version
     * of your module ready to be installed. They are here only
     * for demonstrative purposes and to show how the congrea
     * iself has been upgraded.
     *
     * For each upgrade block, the file congrea/version.php
     * needs to be updated . Such change allows Moodle to know
     * that this file has to be processed.
     *
     * To know more about how to write correct DB upgrade scripts it's
     * highly recommended to read information available at:
     *   http://docs.moodle.org/en/Development:XMLDB_Documentation
     * and to play with the XMLDB Editor (in the admin menu) and its
     * PHP generation posibilities.
     *
     * First installation, no update
     */

    /*
     * Finally, return of upgrade result (true, all went good) to Moodle.
     */
    if ($oldversion < 2018060200) {
        $table = new xmldb_table('congrea_poll_question');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
        $table->add_field('name', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('description', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('category', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, 0, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, 0, null, null);
        $table->add_field('createdby', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, 0, null, null);
        $table->add_field('cmid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, 0, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        $table = new xmldb_table('congrea_poll');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('instanceid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('sessionid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('qid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        $table = new xmldb_table('congrea_poll_question_option');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
        $table->add_field('qid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('options', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        $table = new xmldb_table('congrea_poll_attempts');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('qid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('optionid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        $table = new xmldb_table('congrea_quiz');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
        $table->add_field('congreaid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, 0, null);
        $table->add_field('quizid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, 0, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        $table = new xmldb_table('congrea_quiz_grade');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
        $table->add_field('congreaquiz', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, 0, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, 0, null);
        $table->add_field('grade', XMLDB_TYPE_NUMBER, '10, 2', null, null, null, null);
        $table->add_field('timetaken', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, 0, null);
        $table->add_field('questionattempted', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, 0, null);
        $table->add_field('currectanswer', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, 0, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, 0, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }
        upgrade_mod_savepoint(true, 2018060200, 'congrea');
    }

    if ($oldversion < 2018072600) {
        $table = new xmldb_table('congrea_poll');
        $field = new xmldb_field('qid');
        if ($dbman->field_exists($table, $field)) {
            $field->set_attributes(XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
            $dbman->rename_field($table, $field, 'pollquestion');
        }
        $field = new xmldb_field('createdby', XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, 'pollquestion');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        // Change default value.
        $field = new xmldb_field('sessionid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, 0, null);
        if ($dbman->field_exists($table, $field)) {
            $dbman->change_field_default($table, $field);
        }
        $table = new xmldb_table('congrea_poll_question');
        if ($dbman->table_exists($table)) {
            $poll = $DB->get_records('congrea_poll_question');
            if (!empty($poll)) {
                foreach ($poll as $data) {
                    $congreapoll = new stdClass();
                    $cm = get_coursemodule_from_id('congrea', $data->cmid, 0, false);
                    if (!empty($cm)) {
                        if ($data->category) {  // Poll category.
                            $congreapoll->courseid = $cm->course;
                        } else {
                            $congreapoll->courseid = 0;
                        }
                        $congreapoll->instanceid = $cm->instance;
                        $congreapoll->pollquestion = $data->description;
                        $congreapoll->createdby = $data->createdby;
                        $congreapoll->timecreated = $data->timecreated;
                        if ($pollid = $DB->insert_record('congrea_poll', $congreapoll)) {
                            $DB->execute("UPDATE {congrea_poll_question_option} "
                                    . "SET qid = '" . $pollid . "' WHERE qid = '" . $data->id . "'");
                            $DB->execute("UPDATE {congrea_poll_attempts} "
                                    . "SET qid = '" . $pollid . "' WHERE qid = '" . $data->id . "'");
                        }
                    }
                }
            }
            $dbman->drop_table($table);
        }
        upgrade_mod_savepoint(true, 2018072600, 'congrea');
    }
    return true;
}