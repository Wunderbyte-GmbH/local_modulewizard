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

use core\event\course_section_updated;
use moodle_exception;
use stdClass;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once("$CFG->dirroot/course/lib.php");
require_once("$CFG->dirroot/course/modlib.php");

require_once("$CFG->dirroot/lib/gradelib.php");
// require_once("$CFG->dirroot/lib/grade/grade_item.php");
// require_once("$CFG->dirroot/lib/grade/grade_object.php");
// require_once("$CFG->dirroot/grade/report/overview/tests/externallib_test.php");
require_once("$CFG->dirroot/lib/grade/constants.php");
// require_once("$CFG->dirroot/lib/phpunit/classes/advanced_testcase.php");
// require_once("$CFG->dirroot/mod/lib.php");

/**
 * Class modulewizard
 *
 * @package local_modulewizard
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
     * @param int $targetcmid
     * @return bool
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public static function sync_module(
        object $sourcecm,
        $targetcmid,
        ) {

        global $DB, $CFG, $COURSE;

        // Get the source course.
        $course = $DB->get_record('course', array('id'=>$sourcecm->course), '*', MUST_EXIST);

        list($sourcecm, $context, $module, $sourcedata, $cw) = get_moduleinfo_data($sourcecm, $course);

        list ($targetcourse, $targetcm) = get_course_and_cm_from_cmid($targetcmid);

        // We need the cm as stdclass.
        $targetcm = get_coursemodule_from_id($sourcecm->modname, $targetcmid);

        // We need to swith COURSE to targetcourse.

        $COURSE = $targetcourse;

        $data = clone($sourcedata);

        $data->id = "$targetcm->instance";
        $data->instance = "$targetcm->instance";
        $data->update = "$targetcmid";
        $data->course = "$targetcm->course";
        $data->coursemodule = "$targetcmid";

        //unset($data->visible);

        $modmoodleform = "$CFG->dirroot/mod/$module->name/mod_form.php";
        if (file_exists($modmoodleform)) {
            require_once($modmoodleform);
        } else {
            throw new \moodle_exception('noformdesc');
        }

        $mformclassname = 'mod_'.$module->name.'_mod_form';

        $data->availabilityconditionsjson = json_encode(\core_availability\tree::get_root_json(array()));

        $mformclassname::mock_submit((array)$data);

        $mform = new $mformclassname($data, $targetcm->section, $targetcm, $targetcourse);
        $mform->set_data($data);

        $fromform = $mform->get_data();

        list($cm, $fromform) = update_moduleinfo($targetcm, $fromform, $targetcourse, $mform);

        return true;
    }

    /**
     * Generic function to update module.
     *
     * @param object $sourcecm
     * @param [type] $paramsarray
     * @return void
     */
    public static function update_module(
        object $sourcecm,
        $paramsarray
        ) {

        global $DB, $CFG, $USER;

        // Check the course exists.
        $course = $DB->get_record('course', array('id'=>$sourcecm->course), '*', MUST_EXIST);

        list($cm, $context, $module, $data, $cw) = get_moduleinfo_data($sourcecm, $course);


        // Here we override all the keys in data.

        foreach ($paramsarray as $param) {

            $key = $param['keyname'];

            // First we need to check if it's an editor field:
            if (isset($data->{$key . 'editor'})
                && isset($data->{$key . 'editor'}['text'])) {

                $data->{$key . 'editor'}['text'] = $param['value'];

            } else if (isset($data->{$key})) {
                $data->{$key} = $param['value'];
            } else {
                throw new moodle_exception('keydoesnotexist', 'local_modulewizard', null, null, "key $key does not exist.");
            }
        }

        $data->update = $sourcecm->id;

        $modmoodleform = "$CFG->dirroot/mod/$module->name/mod_form.php";
        if (file_exists($modmoodleform)) {
            require_once($modmoodleform);
        } else {
            throw new \moodle_exception('noformdesc');
        }

        $mformclassname = 'mod_'.$module->name.'_mod_form';

        $data->availabilityconditionsjson = json_encode(\core_availability\tree::get_root_json(array()));

        $mformclassname::mock_submit((array)$data);

        $mform = new $mformclassname($data, $cw->section, $cm, $course);
        $mform->set_data($data);

        $fromform = $mform->get_data();

        list($cm, $fromform) = update_moduleinfo($cm, $fromform, $course, $mform);

        return true;
    }

    /**
     * Function to delete module or modules identified by different parameters.
     * @param $targetmodulename
     * @param null $targetidnumber
     * @param null $targetcourseidnumber
     * @param null $targetcourseshortname
     * @param null $targetsectionname
     * @param null $targetslot
     * @param null $shortname
     * @param false $deleteall
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public static function delete_module(
            $targetmodulename,
            $targetidnumber = null,
            $targetcourseidnumber = null,
            $targetcourseshortname = null,
            $targetsectionname = null,
            $targetslot = null,
            $deleteall = false
            ) {

        global $DB;

        if ($targetidnumber) {
            $sql = "SELECT *
                FROM {course_modules} cm
                INNER JOIN {modules} m
                ON m.name=:modulename
                WHERE cm.idnumber=:idnumber";
            $params = array('modulename' => $targetmodulename,
                    'idnumber' => $targetidnumber);

            if ($cmtodelete = $DB->get_records_sql($sql, $params)) {
                course_delete_module($cmtodelete->id);
                return true;
            } else {
                throw new \moodle_exception('targetidnumbernotfound',
                        'local_modulewizard',
                        null,
                        null,
                        'No module with the given idnumber was found.');
            }
        }

        if (!$courseid = self::return_courseid($targetcourseidnumber, $targetcourseshortname)) {
            return false;
        }

        // Return all matchin cms in a given course.
        $cmstodelete = self::get_cms_from_course($courseid, $targetmodulename, $targetsectionname, $targetslot);

        if (count($cmstodelete) == 1) {
            $cm = reset($cmstodelete);
            course_delete_module($cm->id);
            return true;
        } else if (count($cmstodelete) > 1) {
            if ($deleteall) {
                foreach ($cmstodelete as $item) {
                    course_delete_module($item->id);
                }
                return true;
            } else {
                throw new \moodle_exception('tomanymodulesfound',
                        'local_modulewizard',
                        null,
                        null,
                        'More than one module would be deleted with this setting.
                            If you want to proceed, set deleteall param to 1');
            }
        } else {
            throw new \moodle_exception('nomodulefound',
                    'local_modulewizard',
                    null,
                    null,
                    'No module was found with your setting. please check again');
        }
    }

    /**
     * Function to copy course and create new course from template.
     *
     * @param integer $sourcecourseid
     * @param string $newcourseshortname
     * @param string|null $newcoursename
     * @return void
     */
    public static function create_course(
        int $sourcecourseid,
        string $newcourseshortname,
        $newcoursename = null
        ) {

        global $DB, $CFG, $USER;

        // This we need to run generator below.
        require_once($CFG->dirroot . '/lib/phpunit/classes/util.php');

        if (!$sourcecourse = get_course($sourcecourseid)) {
            throw new \moodle_exception('coursenotfound',
                    'local_modulewizard',
                    null,
                    null,
                    'Couldn\'t find the source course');
        }

        $sourcecourse->shortname = $newcourseshortname;
        $sourcecourse->fullname = $newcoursename ? $newcoursename : $newcourseshortname;

        $generator = \testing_util::get_data_generator();

        if (!$record = $generator->create_course($sourcecourse)) {
            throw new \moodle_exception('creationfailed',
                    'local_modulewizard',
                    null,
                    null,
                    'Something went wrong during the creation of the module.');
        }

        return true;
    }

    /**
     * The function add_moduleinfo() expects some further information, which we add here.
     * @param \stdClass $sourcecm
     * @param \stdClass $sourcemodule
     * @return \stdClass
     */
    private static function prepare_modinfo(\stdClass $sourcecm, \stdClass $sourcemodule) :\stdClass {
        global $DB;

        $sourcecm->modulename = $sourcecm->modname;

        // Make sure we don't miss any of the keys for the module creation.
        foreach ($sourcemodule as $key => $value) {
            if (!isset($sourcecm->$key)) {
                $sourcecm->{$key} = $value;
            }
        }

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
    private static function return_section($targetsectionname, $courseid): object {

        global $DB;

        $sql = "SELECT cs.*
                FROM {course_sections} cs";

        $where = "
                WHERE cs.course =:courseid1";

        $params = array('courseid1' => $courseid);

        if (empty($targetsectionname) || $targetsectionname === "last") {
            $where .= "
                AND cs.section = (SELECT MAX(section)
                FROM {course_sections}
                WHERE course=:courseid2)";
            $params['courseid2'] = $courseid;
        } else if ($targetsectionname === 'top') {
            $where .= "
                AND cs.section=0";
        } else {
            $where .= "
                AND cs.name=:sectionname";
            $params['sectionname'] = $targetsectionname;
        }

        $sql .= $where;

        if ($result = $DB->get_record_sql($sql, $params)) {
            return $result;
        } else {
            throw new \moodle_exception('sectionnotfound',
                    'local_modulewizard',
                    null,
                    null,
                    'The specified section '. $targetsectionname .' was not found');
        }
    }

    /**
     * Verify if the parameters and return the corresponding courseid.
     * @param string|null $targetcourseidnumber
     * @param string|null $targetcourseshortname
     * @return false|mixed
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    private static function return_courseid(string $targetcourseidnumber = null,
            string $targetcourseshortname = null) {

        global $DB;

        // Courses can either be identified by targetcourseidnumber or by targetcourseshortname - not both.
        // So throw an error if both are provided.
        if ($targetcourseidnumber && $targetcourseshortname) {
            throw new \moodle_exception('toomanyparams',
                    'local_modulewizard',
                    null,
                    null,
                    'Target courses can be identified either by targetcourseidnumber or by targetcourseshortname. ' .
                    'You cannot provide both.'
            );
        }

        // Also throw an error, if both are missing.
        if (!$targetcourseidnumber && !$targetcourseshortname) {
            throw new \moodle_exception('notenoughparams',
                    'local_modulewizard',
                    null,
                    null,
                    'Target courses need to be identified either by targetcourseidnumber or by targetcourseshortname. ' .
                    'You need to provide one of them (not both).'
            );
        }

        // Identification via targetcourseidnumber.
        if ($targetcourseidnumber) {
            // Throw error if we can't retrieve the courseid.
            if (!$courseid = $DB->get_field('course', 'id', array('idnumber' => $targetcourseidnumber))) {
                throw new \moodle_exception('coursenotfound',
                        'local_modulewizard',
                        null,
                        null,
                        'The specified target course idnumber '. $targetcourseidnumber .' was not found');
            }
        }

        // Or identification via targetcourseshortname.
        if ($targetcourseshortname) {
            // Throw error if we can't retrieve the courseid.
            if (!$courseid = $DB->get_field('course', 'id', array('shortname' => $targetcourseshortname))) {
                throw new \moodle_exception('coursenotfound',
                        'local_modulewizard',
                        null,
                        null,
                        'The specified target course shortname '. $targetcourseshortname .' was not found');
            }
        }
        return $courseid;
    }

    /**
     * Return cm objects from course.
     * @param int $courseid
     * @param string|null $modname
     * @param string|null $sectionname
     * @param bool $onlyvisible
     * @return array
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    private static function get_cms_from_course(int $courseid,
            string $modname = null,
            string $sectionname = null,
            int $targetslot = null,
            bool $onlyvisible = false) {
        global $DB;

        $sql = "SELECT cm.*, m.name as modname
                  FROM {modules} m, {course_modules} cm";

        $where = "
        WHERE cm.course =:courseid
        AND cm.module = m.id";

        $params = array('courseid' => $courseid);
        // If there is a modname, we adapt sql.
        if ($modname) {
            $where .= "
            AND m.name =:modname";
            $params['modname'] = $modname;
        }
        // If we only want to return visible cms.
        if ($onlyvisible) {
            $where .= "
            AND m.visible = 1";
        }

        // Join the sql parts.
        $sql .= $where;

        $cmsincourse = $DB->get_records_sql($sql, $params);

        // As sequences are stored in DB as a comma separated string, there is no good way to filter this via sql.
        if (!$sectionname) {
            // If we don't want to filter for section, we return all cms in course.
            return $cmsincourse;
        } else {
            if ($sectionsequence = self::return_section_sequence($courseid, $sectionname)) {

                // Get a list of all cm ids in this section.
                $cmsinsection = explode(',', $sectionsequence);

                // If we also have a slot defined, we reduce the array accordingly.
                if ($targetslot !== null) {
                    $cmsinsection = self::return_cm_in_section_slot($cmsinsection, $targetslot);
                }

                // Prepare $result.
                $result = [];
                // Check which of the $cmsincourse are also part of this section.
                foreach ($cmsincourse as $cmincourse) {
                    if (in_array($cmincourse->id, $cmsinsection)) {
                        $result[] = $cmincourse;
                    }
                }
                return $result;
            } else {
                throw new \moodle_exception('sectionnotfound',
                        'local_modulewizard',
                        null,
                        null,
                        'The specified section '. $sectionname .' was not found');
            }
        }
    }

    /**
     * Function receives an array of ints with cmids in a given section.
     * This information is not very relyable to calcualte actualy wanted slot, because sometimes cms are deleted...
     * ... and sequence information in course section is not updated properly, eg deletion in progress etc.
     * Therefore, we don't just return the cmid by key, but we verify for every cm if it is actually in the section.
     * @param array $cmsinsection
     * @param int $slot
     * @return array|null
     * @throws \dml_exception
     */
    private static function return_cm_in_section_slot(array $cmsinsection, int $slot) {
        global $DB;

        $stillexistingcmids = [];

        foreach ($cmsinsection as $cmid) {
            if ($DB->record_exists('course_modules', array("id" => $cmid, 'deletioninprogress' => 0))) {
                $stillexistingcmids[] = $cmid;
            }
        }

        if ($slot == -1) {
            return array_pop($stillexistingcmids);
        } else if (isset($stillexistingcmids[$slot])) {
            return [$stillexistingcmids[$slot]];
        } else {
            return null;
        }
    }

    /**
     * Return section sequence by sectionname & courseid.
     * This uses the return_sectionid function for cohesion.
     * @param $courseid
     * @param $sectionname
     * @return false|mixed|null
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    private static function return_section_sequence($courseid, $sectionname) {
        global $DB;
        if ($section = self::return_section($sectionname, $courseid)) {
            return $section->sequence;
        } else {
            return null;
        }
    }
}
