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
 * Install script
 *
 * @package   local_courselog
 * @copyright 2018 Darko Miletic
 * @author    Darko Miletic <darko.miletic@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL
 */

namespace local_courselog;

use core_plugin_manager;
use dml_exception;
use tool_log\log\manager;
use logstore_database\log\store;
use Exception;

defined('MOODLE_INTERNAL') || die();

/**
 * Class plugin
 * @package local_courselog
 */
abstract class plugin {

    const COMPONENT  = 'local_courselog';
    const ENABLE     = 'enable';
    const FULLENABLE = 'local_courselog/enable';
    const CAPABILITY = 'local/courselog:resetlogs';

    /**
     *
     * @param  int $courseid
     * @throws dml_exception
     */
    public static function deletelog($courseid) {
        global $DB;

        if (empty(get_config(self::COMPONENT, self::ENABLE))) {
            return;
        }

        $elements = [
            'logstore_legacy'   => ['dbtable' => 'log', 'param' => 'course'  ],
            'logstore_standard' => ['dbtable' => 'logstore_standard_log', 'param' => 'courseid'],
        ];
        $logstores = core_plugin_manager::instance()->get_plugins_of_type('logstore');
        foreach ($logstores as $log) {
            if ($log->is_enabled()) {
                if (isset($elements[$log->component])) {
                    $DB->delete_records(
                        $elements[$log->component]['dbtable'],
                        [$elements[$log->component]['param'] => $courseid]
                    );
                }
            }
        }
        if (!empty($logstores['database'])) {
            if ($logstores['database']->is_enabled()) {
                $dbtable = get_config($logstores['database']->component, 'dbtable');
                if (!empty($dbtable)) {
                    try {
                        $manager = new manager();
                        $store = new store($manager);
                        $extdb = $store->get_extdb();
                        if ($extdb and $extdb->get_manager()->table_exists($dbtable)) {
                            $extdb->delete_records($dbtable, ['courseid' => $courseid]);
                            $extdb->dispose();
                        }
                    } catch (Exception $e) {
                        ($e);
                    }
                }
            }
        }
    }

}
