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
 * @copyright  2014 Pinky Sharma
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
    global $DB;

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
     
    if ($oldversion < 2015072402) {

        // Define table congrea_files to virtualclass.
        $table = new xmldb_table('virtualclass_files');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
        $table->add_field('courseid', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, 0, null);
		$table->add_field('vcid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0, null);
		$table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0, null);
		$table->add_field('vcsessionkey', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null, null);
		$table->add_field('vcsessionname', XMLDB_TYPE_CHAR, '225', null, null, null, null, null);
		$table->add_field('numoffiles', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0, null);
		$table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, 0, null);		
	 
		$table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'), null, null);

        // Conditionally launch add table virtualclass_files.
        if (!$dbman->table_exists($table)) {
           $dbman->create_table($table);
       	}

        // Virtualclass savepoint reached.
        upgrade_mod_savepoint(true, 2015072402, 'virtualclass');
    }

    if ($oldversion < 2015103000) {
        $table = new xmldb_table('virtualclass');
        $field = new xmldb_field('themecolor', XMLDB_TYPE_CHAR, '225', null, null, null, '0', 'closetime');
        
        // Conditionally launch add field themecolor.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $table = new xmldb_table('virtualclass');
        $field = new xmldb_field('audio', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'themecolor');

        // Conditionally launch add field audio.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        
        $table = new xmldb_table('virtualclass');
        $field = new xmldb_field('pushtotalk', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'audio');
        
        // Conditionally launch add field pushtotalk.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_mod_savepoint(true, 2015103000, 'virtualclass');
        
    }


    return true;
}

/*
ALTER TABLE  `mdl_virtualclass` ADD  `themecolor` VARCHAR( 225 ) NULL DEFAULT NULL AFTER  `closetime` ,
ADD  `audio` BIGINT( 10 ) NOT NULL DEFAULT  '0' AFTER  `themecolor` ,
ADD  `pushtotalk` BIGINT( 10 ) NOT NULL DEFAULT  '0' AFTER  `audio` ;
*/