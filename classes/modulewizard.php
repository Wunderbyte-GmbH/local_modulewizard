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
     * Function to handle basic copy operations.
     * @param object $sourcecm
     * @param string $targetcourseidnumber
     * @param null|string $targetsectionname
     * @param null|int $targetslot
     */
    public static function copy_module(
            object $sourcecm,
            string $targetcourseidnumber,
            $targetsectionname = null,
            $targetslot = null
            ) {

        global $DB, $CFG, $USER;

        $warnings = [];
        // Throw error if we can't retrieve the courseid.
        if (!$targetcourseidnumber || (!$courseid = $DB->get_field('course', 'id', array('idnumber' => $targetcourseidnumber)))) {
            throw new \moodle_exception('coursenotfound',
                    'local_modulewizard',
                    null,
                    null,
                    'The specified idnumber '. $targetcourseidnumber .' was not found');
        }
        // Throw error if the section can not be identified.
        if ($targetsectionname && ($sectionrecord = $DB->get_record('course_sections', array('name' => $targetsectionname, 'course' => $courseid)))) {
            throw new \moodle_exception('sectionnotfound',
                    'local_modulewizard',
                    null,
                    null,
                    'The specified section '. $targetsectionname .' was not found');
        }

        $course = get_course($courseid);
        list($sourcecm, $context, $sourcemodule, $data, $cw) = can_update_moduleinfo($sourcecm);


        // If after the Error check we still have $null as section, we know that we just add to the last section in the course.
        if (!$targetsectionname) {
            // Add to the last secton.
        }
        //$sourcemodule = self::prepare_modinfo($sourcemodule, $sourcecm->modname);
        // Create the new module in the course.

        // $sourcecm = self::prepare_modinfo($sourcecm, $sourcemodule);
        // $newmodule = add_moduleinfo($sourcecm, $course);
        // Move the new module at the right place.
        // moveto_module()

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
}
