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
 * This file replaces the legacy STATEMENTS section in db/install.xml.
 *
 * @package    mod_congrea
 * @copyright  2014 Pinky Sharma
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Post installation procedure
 *
 * @see upgrade_plugins_modules()
 */
function xmldb_congrea_install() {
    global $DB, $CFG;

    require_once($CFG->dirroot . '/mod/congrea/locallib.php');
    $dbman = $DB->get_manager(); 
    // Removed the 'moderatorid' column from 'congrea'.
    $table = new xmldb_table('congrea');
    $field = new xmldb_field('moderatorid');
    if ($dbman->field_exists($table, $field)) {
        $dbman->drop_field($table, $field);
    }
    $field = new xmldb_field('opentime');
    if ($dbman->field_exists($table, $field)) {
        $dbman->drop_field($table, $field);
    }
    $field = new xmldb_field('closetime');
    if ($dbman->field_exists($table, $field)) {
        $dbman->drop_field($table, $field);
    }
    $field = new xmldb_field('raisehand');
    if ($dbman->field_exists($table, $field)) {
        $dbman->drop_field($table, $field);
    }
    // Add Bookmark & Notes field Default 1.
    $field = new xmldb_field('qamarknotes', XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, null, 1, 'timemodified');
    if (!$dbman->field_exists($table, $field)) {
        $dbman->add_field($table, $field);
    }
    // Add Question & Answer field Default 1.
    $field = new xmldb_field('askquestion', XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, null, 1, 'qamarknotes');
    if (!$dbman->field_exists($table, $field)) {
        $dbman->add_field($table, $field);
    }
    // Add Answer field Default 1.
    $field = new xmldb_field('qaanswer', XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, null, 1, 'askquestion');
    if (!$dbman->field_exists($table, $field)) {
        $dbman->add_field($table, $field);
    }
    // Add Comment field Default 1.
    $field = new xmldb_field('qacomment', XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, null, 1, 'qaanswer');
    if (!$dbman->field_exists($table, $field)) {
        $dbman->add_field($table, $field);
    }
    // Add Voting field Default 1.
    $field = new xmldb_field('qaupvote', XMLDB_TYPE_INTEGER, 10, XMLDB_UNSIGNED, XMLDB_NOTNULL, null, 1, 'qacomment');
    if (!$dbman->field_exists($table, $field)) {
        $dbman->add_field($table, $field);
    }
}

/**
 * Post installation recovery procedure
 *
 * @see upgrade_plugins_modules()
 */
function xmldb_congrea_install_recovery() {
}
