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
 * Evento plugin settings and presets
 *
 * @package    local_evento
 * @copyright  2017 HTW Chur Roger Barras
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {

    $settings = new admin_settingpage('local_evento', get_string('pluginname', 'local_evento'));
    $ADMIN->add('localplugins', $settings);
    // General Settings.
    $settings->add(new admin_setting_heading('local_evento_settings', '', get_string('pluginname_desc', 'local_evento')));
    $settings->add(new admin_setting_configtext('local_evento/wslocation',
        new lang_string('ws_location', 'local_evento'), '', 'https://ws.fh-htwchur.ch/eventowsblc/services/EventoWebservice', PARAM_URL));

    $settings->add(new admin_setting_configtext('local_evento/wswsdlfilename',
        new lang_string('ws_wsdlfilename', 'local_evento'), '', 'evento_webservice_v1_1.wsdl', PARAM_TEXT));

    $settings->add(new admin_setting_configtext('local_evento/wsuri',
        new lang_string('ws_uri', 'local_evento'), '', '', PARAM_TEXT));

    $settings->add(new admin_setting_configtext('local_evento/wsusername',
        new lang_string('ws_username', 'local_evento'), '', '', PARAM_TEXT));

    // admin_setting_configpasswordunmask
    $settings->add(new admin_setting_configpasswordunmask('local_evento/wspassword',
        new lang_string('ws_password', 'local_evento'), '', '', PARAM_TEXT));

    $settings->add(new admin_setting_configtext('local_evento/wstrace',
        new lang_string('ws_trace', 'local_evento'), new lang_string('ws_trace_desc', 'local_evento'), 0, PARAM_INT));

    $settings->add(new admin_setting_configtext('local_evento/adsidprefix',
        new lang_string('ad_sidprefix', 'local_evento'), new lang_string('ad_sidprefix_desc', 'local_evento'), 'S-1-5-21-', PARAM_TEXT));

    $settings->add(new admin_setting_configtext('local_evento/adshibbolethsuffix',
        new lang_string('ad_shibbolethsuffix', 'local_evento'), '', '@fh-htwchur.ch', PARAM_TEXT));

}
