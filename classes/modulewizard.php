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
 * Plugin event observers are registered here.
 *
 * @package local_modulewizard
 * @copyright 2021 Wunderbyte Gmbh <info@wunderbyte.at>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_modulewizard;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once("$CFG->dirroot/course/lib.php");
require_once("$CFG->dirroot/course/modlib.php");

/**
 * Class mooduell
 *
 * @package mod_mooduell
 */
class modulewizard {

    /**
     * Not sure we need a constructor.
     */
    public function __construct() {

    }

    /**
     * Function to handle copy operations.
     * @param object $sourcecm
     * @param string $targetcourseidnumber
     * @param null|string $targetsectionname
     * @param null|int $targetslot
     * @param null|string $postfix
     * @return bool
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public static function copy_module(
            object $sourcecm,
            string $targetcourseidnumber,
            $targetsectionname = null,
            $targetslot = null,
            $postfix = null
            ) {

        global $DB, $CFG, $USER;

        // This we need to run generator below.
        require_once($CFG->dirroot . '/lib/phpunit/classes/util.php');

        // Throw error if we can't retrieve the courseid.
        if (!$targetcourseidnumber || (!$courseid = $DB->get_field('course', 'id', array('idnumber' => $targetcourseidnumber)))) {
            throw new \moodle_exception('coursenotfound',
                    'local_modulewizard',
                    null,
                    null,
                    'The specified idnumber '. $targetcourseidnumber .' was not found');
        }

        list($sourcecm, $context, $sourcemodule, $data, $cw) = can_update_moduleinfo($sourcecm);

        $sourcecm->course = $courseid;
        $sourcemodule = self::prepare_modinfo($sourcecm, $data);

        if ($postfix) {
            $sourcemodule->idnumber = $targetcourseidnumber . $postfix;
        }

        $sourcemodule->section = self::return_sectionid($targetsectionname, $courseid);

        $generator =  \testing_util::get_data_generator();
        if (!$record = $generator->create_module($sourcecm->modname, $sourcemodule)) {
            throw new \moodle_exception('creationfailed',
                    'local_modulewizard',
                    null,
                    null,
                    'Something went wrong during the creation of the module.');
        }


        // If we have a slot, we move the module
        if ($targetslot !== null) {
            $mod = get_coursemodule_from_id($sourcecm->modname, $record->cmid);
            $sectionrecord = $DB->get_record('course_sections', array('section' => $sourcemodule->section, 'course' => $courseid));
            $modarray = explode(',', $sectionrecord->sequence);
            if ($beforemodid = $modarray[$targetslot]) {
                moveto_module($mod, $sectionrecord, $beforemodid);
            }
        }
        return true;

    }

    /**
     * The function add_moduleinfo() expects some further information, which we add here.
     * @param \stdClass $sourcemodule
     * @param string $soucemodulename
     * @return \stdClass
     * @throws \dml_exception
     */
    private static function prepare_modinfo(\stdClass $sourcecm, \stdClass $sourcemodule) :\stdClass {
        global $DB;

        $sourcecm->modulename = $sourcecm->modname;

        // Make sure we don't miss any of the keys for the module creation.
        foreach ($sourcemodule as $key => $value) {
            if (!isset($sourcecm->$key)) {
                $sourcecm->$key = $value;
            }
        }

        // $sourcemodule->module = $DB->get_field('modules', 'id', array('name' => $soucemodulename));
        return $sourcecm;
    }

    /**
     * Function to return the right section id, based on the targetsectionname.
     * If there is non, we add to the last section, if that fails, we add to the first.
     * if targetsectionname is "top", we add to the first (section 0).
     * @param null|string $targetsectionname
     * @param int $courseid
     * @return int
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    private static function return_sectionid($targetsectionname, $courseid): int {

        global $DB;
        $sectionid = 0;
        // Throw error if the section can not be identified.
        if ($targetsectionname === 'top') {
            $sectionid = 0;
        } else if ($targetsectionname && (!$sectionid = $DB->get_field('course_sections', 'section', array('name' => $targetsectionname, 'course' => $courseid)))) {
            throw new \moodle_exception('sectionnotfound',
                    'local_modulewizard',
                    null,
                    null,
                    'The specified section '. $targetsectionname .' was not found');
        }

        // If we have no name for the section, we just add to the last section.
        // If we fail at retrieving it, we add to the first.
        if (!$targetsectionname) {
            $sql = '
            SELECT MAX(section)
            FROM {course_sections}
            WHERE course = :courseid2';
            if ($maxsectionid = $DB->get_field_sql($sql, array('courseid1' => $courseid, 'courseid2' => $courseid))) {
                $sectionid = $maxsectionid;
            }
        }
        return $sectionid;
    }
}
