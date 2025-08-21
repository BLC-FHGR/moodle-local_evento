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
 * TODO describe file settings
 *
 * @package    local_evento
 * @copyright  2025 YOUR NAME <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_evento', get_string('pluginname', 'local_evento'));
    $ADMIN->add('localplugins', $settings);

    // Development Console Link
    if (get_config('local_evento', 'dev_logging_enabled')) {
        $settings->add(new admin_setting_heading(
            'local_evento_dev_console', 
            get_string('dev_console', 'local_evento'),
            '<a href="' . $CFG->wwwroot . '/local/evento/admin/dev_console.php" class="btn btn-primary">' .
            get_string('open_dev_console', 'local_evento') . '</a>'
        ));
    }

    // SOAP Configuration
    $settings->add(new admin_setting_heading(
        'local_evento_soap', 
        get_string('soap_settings', 'local_evento'),
        get_string('soap_settings_desc', 'local_evento')
    ));

    $settings->add(new admin_setting_configtext(
        'local_evento/soap_location',
        get_string('soap_location', 'local_evento'),
        get_string('soap_location_desc', 'local_evento'),
        'https://ws.fh-htwchur.ch/eventowsblc/services/EventoWebservice',
        PARAM_URL
    ));

    $settings->add(new admin_setting_configtext(
        'local_evento/soap_wsdl',
        get_string('soap_wsdl', 'local_evento'),
        get_string('soap_wsdl_desc', 'local_evento'),
        'evento_webservice_v1_1.wsdl',
        PARAM_TEXT
    ));

    $settings->add(new admin_setting_configtext(
        'local_evento/soap_username',
        get_string('soap_username', 'local_evento'),
        get_string('soap_username_desc', 'local_evento'),
        '',
        PARAM_TEXT
    ));

    $settings->add(new admin_setting_configpasswordunmask(
        'local_evento/soap_password',
        get_string('soap_password', 'local_evento'),
        get_string('soap_password_desc', 'local_evento'),
        ''
    ));

    // Development Settings
    $settings->add(new admin_setting_heading(
        'local_evento_dev', 
        get_string('dev_settings', 'local_evento'),
        get_string('dev_settings_desc', 'local_evento')
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_evento/dev_logging_enabled',
        get_string('dev_logging', 'local_evento'),
        get_string('dev_logging_desc', 'local_evento'),
        0
    ));

    $settings->add(new admin_setting_configtext(
        'local_evento/soap_timeout',
        get_string('soap_timeout', 'local_evento'),
        get_string('soap_timeout_desc', 'local_evento'),
        30,
        PARAM_INT
    ));
}
