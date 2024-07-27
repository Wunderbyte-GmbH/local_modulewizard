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
 * Module Wizard external functions and service definitions.
 *
 * @package local_modulewizard
 * @category external
 * @copyright 2021 Wunderbyte GmbH (info@wunderbyte.at)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since Moodle 3.1
 */

defined('MOODLE_INTERNAL') || die();

$services = [
        'Wunderbyte ModuleWizard external' => [
                'functions' => [
                        'local_modulewizard_copy_module',
                ],
                'restrictedusers' => 1,
                'shortname' => 'local_modulewizard_external',
                'enabled' => 1,
        ],
];

$functions = [
        'local_modulewizard_copy_module' => [
                'classname' => 'local_modulewizard\external\copy_module',
                'description' => 'Copies a module to a new place',
                'type' => 'write',
                'ajax' => true,
                'capabilities' => 'local/modulewizard:copymodule',
                'services' => [
                        'local_modulewizard_external',
                ],
        ],
        'local_modulewizard_sync_module' => [
                'classname' => 'local_modulewizard\external\sync_module',
                'description' => 'Syncs a module with target',
                'type' => 'write',
                'ajax' => true,
                'capabilities' => 'local/modulewizard:copymodule',
                'services' => [
                        'local_modulewizard_external',
                ],
        ],
        'local_modulewizard_update_module' => [
                'classname' => 'local_modulewizard\external\update_module',
                'description' => 'Update a module with new information',
                'type' => 'write',
                'ajax' => true,
                'capabilities' => 'local/modulewizard:copymodule',
                'services' => [
                        'local_modulewizard_external',
                ],
        ],
        'local_modulewizard_delete_module' => [
                'classname' => 'local_modulewizard\external\delete_module',
                'description' => 'Deletes a module from a certain place.',
                'type' => 'write',
                'ajax' => true,
                'capabilities' => 'local/modulewizard:copymodule',
                'services' => [
                        'local_modulewizard_external',
                ],
        ],
        'local_modulewizard_create_course' => [
                'classname' => 'local_modulewizard\external\create_course',
                'description' => 'Copies a course by id.',
                'type' => 'write',
                'ajax' => true,
                'capabilities' => 'local/modulewizard:copymodule',
                'services' => [
                        'local_modulewizard_external',
                ],
        ],
];
