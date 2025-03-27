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

use context_module;
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
class sync_module extends external_api {

    /**
     * Describes the parameters for add_item_to_cart.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters(array(
            'sourcecmid' => new external_value(PARAM_INT,
                'The cmid of the module to copy.'),
            'sourcemodulename' => new external_value(PARAM_RAW,
                'The module type of the module to copy (eg. quiz or mooduell)'),
            'targetcmid' => new external_value(PARAM_INT,
                'The module to sync'),
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
            int $sourcecmid,
            string $sourcemodulename,
            $targetcmid = null,
            ) {

        global $DB;

        $params = array(
                'sourcecmid' => $sourcecmid,
                'sourcemodulename' => $sourcemodulename,
                'targetcmid' => $targetcmid,
        );

        $params = self::validate_parameters(self::execute_parameters(), $params);

        // First find out if the module name exists at all.
        if (!core_component::is_valid_plugin_name('mod', $params['sourcemodulename'])) {
            throw new moodle_exception('invalidcoursemodulename', 'local_modulewizard', null, null,
                    "Invalid source module name " . $params['sourcemodulename']);
        }

        // Now do some security checks.
        if (!$cm = get_coursemodule_from_id($params['sourcemodulename'], $params['sourcecmid'])) {
            throw new moodle_exception('invalidcoursemodule ' . $params['sourcecmid'], 'local_modulewizard', null, null,
                    "Invalid source module" . $params['sourcecmid'] . ' ' . $params['sourcemodulename']);
        }

        $context = context_module::instance($cm->id);
        self::validate_context($context);

        // We try to copy the module to the target.
        if (modulewizard::sync_module(
                $cm,
                $params['targetcmid'],
                )) {
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
