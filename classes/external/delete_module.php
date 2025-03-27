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
 * This class contains a list of webservice functions related to the Shopping Cart Module by Wunderbyte.
 *
 * @package    local_modulewizard
 * @copyright  2024 Georg Maißer <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace local_modulewizard\external;

use core_component;
use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use local_modulewizard\modulewizard;
use moodle_exception;
use coding_exception;
use invalid_parameter_exception;
use restricted_context_exception;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

/**
 * External Service for module wizard
 *
 * @package   local_modulewizard
 * @copyright 2022 Wunderbyte GmbH {@link http://www.wunderbyte.at}
 * @author    Georg Maißer
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class delete_module extends external_api {

    /**
     * Describes the parameters for add_item_to_cart.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters(array(
            'targetmodulename' => new external_value(PARAM_RAW,
                    'The module type of the module to delete (eg. quiz or mooduell)'),
            'targetidnumber' => new external_value(PARAM_RAW,
                    'Most precise way to delete exactly one activity.',
                    VALUE_DEFAULT, null),
            'targetcourseidnumber' => new external_value(PARAM_RAW,
                    'The course where to find the activity to delete.
                    Identified by the value in the idnumber column in the course table.',
                    VALUE_DEFAULT, null),
            'targetcourseshortname' => new external_value(PARAM_RAW,
                    'The course where to find the activity to delete.
                    Identified by the value in the shortname column in the course table.',
                    VALUE_DEFAULT, null),
            'targetsectionname' => new external_value(PARAM_RAW,
                    'The section name, identified by the name column in the course_sections table. "top" is for section 0.',
                    VALUE_DEFAULT, null),
            'targetslot' => new external_value(PARAM_INT,
                    'The slot of the activity to delete, where 0 is the top place in the activity. -1 is last.',
                    VALUE_DEFAULT, null),
            'deleteall' => new external_value(PARAM_BOOL, 'TRUE to delete more than one instance.',
                    VALUE_DEFAULT, false),
        ));
    }

    /**
     * Function to sync a module to a new course and course section.
     * @param int $sourcecmid
     * @param string $sourcemodulename
     * @param null|string $targetcourseidnumber
     * @param null|string $targetcourseshortname
     * @param null|string $targetsectionname
     * @param null|int $targetslot
     * @param null|string $idnumber
     * @param null|string $shortname
     * @return int[]
     * @throws coding_exception
     * @throws invalid_parameter_exception
     * @throws moodle_exception
     * @throws restricted_context_exception
     */
    public static function execute(
        string $targetmodulename,
        $targetidnumber = null,
        $targetcourseidnumber = null,
        $targetcourseshortname = null,
        $targetsectionname = null,
        $targetslot = null,
        $deleteall = false) {

        global $DB;

        $params = array(
                'targetmodulename' => $targetmodulename,
                'targetcourseidnumber' => $targetcourseidnumber,
                'targetcourseshortname' => $targetcourseshortname,
                'targetsectionname' => $targetsectionname,
                'targetslot' => $targetslot,
                'targetidnumber' => $targetidnumber,
                'deleteall' => $deleteall,
        );

        $params = self::validate_parameters(self::execute_parameters(), $params);

        // First find out if the module name exists at all.
        if (!core_component::is_valid_plugin_name('mod', $params['targetmodulename'])) {
            throw new moodle_exception('invalidcoursemodulename', 'local_modulewizard', null, null,
                    "Invalid module name " . $params['sourcemodulename']);
        }

        // We try to delete the module.
        if (modulewizard::delete_module(
                $params['targetmodulename'],
                $params['targetidnumber'],
                $params['targetcourseidnumber'],
                $params['targetcourseshortname'],
                $params['targetsectionname'],
                $params['targetslot'],
                $params['deleteall'])) {
            $success = 1;
        } else {
            $success = 0;
        }

        return ['status' => $success];
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure(array(
            'status' => new external_value(PARAM_INT, 'status'),
            )
        );
    }
}
