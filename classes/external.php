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
 * Moolde external API
 *
 * @package local_modulewizard
 * @category external
 * @copyright 2021 Wunderbyte Gmbh <info@wunderbyte.at>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Class local_modulewizard_external
 */
class local_modulewizard_external extends external_api {

    /**
     * Function to actually copy a module to a new course and course section.
     * @param int $sourcecmid
     * @param string $sourcemodulename
     * @param string $targetcourseidnumber
     * @param null|string $targetsectionname
     * @param null|int $targetslot
     * @param null|string $idnumber
     * @return int[]
     * @throws coding_exception
     * @throws invalid_parameter_exception
     * @throws moodle_exception
     * @throws restricted_context_exception
     */
    public static function copy_module(
            int $sourcecmid,
            string $sourcemodulename,
            string $targetcourseidnumber,
            $targetsectionname = null,
            $targetslot = null,
            $idnumber = null) {

        global $DB;

        $params = array(
                'sourcecmid' => $sourcecmid,
                'sourcemodulename' => $sourcemodulename,
                'targetcourseidnumber' => $targetcourseidnumber,
                'targetsectionname' => $targetsectionname,
                'targetslot' => $targetslot,
                'idnumber' => $idnumber
        );

        $params = self::validate_parameters(self::copy_module_parameters(), $params);

        // First find out if the module name exists at all.

        if (!$DB->record_exists('modules', array('name' => $params['sourcemodulename']))) {
            throw new moodle_exception('invalidcoursemodulename', 'local_modulewizard', null, null,
                    "Invalid source module name " . $params['sourcemodulename']);
        }

        // Now security checks.
        if (!$cm = get_coursemodule_from_id($params['sourcemodulename'], $params['sourcecmid'])) {
            throw new moodle_exception('invalidcoursemodule ' . $params['sourcecmid'], 'local_modulewizard', null, null,
                    "Invalid source module" . $params['sourcecmid'] . ' ' . $params['sourcemodulename']);
        }
        $context = context_module::instance($cm->id);
        self::validate_context($context);

        // We try to copy the module to the target.
        if (local_modulewizard\modulewizard::copy_module($cm, $targetcourseidnumber, $targetsectionname, $targetslot, $idnumber)) {
            $success = 1;
        } else {
            $success = 0;
        }

        return ['status' => $success];
    }

    /**
     * Defines the parameters for copy_module.
     * @return external_function_parameters
     */
    public static function copy_module_parameters() {
        return new external_function_parameters(array(
                'sourcecmid' => new external_value(PARAM_INT, 'The cmid of the module to copy.'),
                'sourcemodulename' => new external_value(PARAM_RAW, 'The module type of the module to copy (eg. quiz or mooduell)'),
                'targetcourseidnumber' => new external_value(PARAM_RAW, 'The course to copy to, identified by the value in the idnumber column in the course table.'),
                'targetsectionname' => new external_value(PARAM_RAW, 'The section name, identified by the name column in the course_sections table. "top" is for section 0.', VALUE_DEFAULT, null),
                'targetslot' => new external_value(PARAM_INT, 'The slot for the new activity, where 0 is the top place in the activity. -1 is last.', VALUE_DEFAULT, null),
                'idnumber' => new external_value(PARAM_RAW, 'To set the idnumber of the new activity.', VALUE_DEFAULT, null)
        ));
    }

    /**
     * Defines the return values for copy_module.
     * @return external_single_structure
     */
    public static function copy_module_returns() {
        return new external_single_structure(array(
                        'status' => new external_value(PARAM_INT, 'status')
                )
        );
    }
}
