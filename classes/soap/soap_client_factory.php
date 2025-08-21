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

namespace local_evento\soap;

/**
 * Class soap_client_factory
 *
 * @package    local_evento
 * @copyright  2025 YOUR NAME <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class soap_client_factory {
    
    public static function create(): \SoapClient {
        global $CFG;
        
        $config = self::get_config();
        
        $wsdl_path = $CFG->dirroot . '/local/evento/wsdl/' . $config->soap_wsdl;
        
        if (!file_exists($wsdl_path)) {
            throw new evento_service_exception("WSDL file not found: {$wsdl_path}");
        }

        $options = [
            'location' => $config->soap_location,
            'uri' => 'http://service.webservice.htwchur.ch',
            'trace' => $config->dev_logging_enabled,
            'login' => $config->soap_username,
            'password' => $config->soap_password,
            'connection_timeout' => $config->soap_timeout,
            'cache_wsdl' => WSDL_CACHE_NONE, // Disable caching during development
            'soap_version' => SOAP_1_1,
            'exceptions' => true,
        ];

        try {
            return new \SoapClient($wsdl_path, $options);
        } catch (\SoapFault $e) {
            throw new evento_service_exception("Failed to create SOAP client: " . $e->getMessage(), $e);
        }
    }

    private static function get_config(): object {
        return (object) [
            'soap_location' => get_config('local_evento', 'soap_location') ?: '',
            'soap_wsdl' => get_config('local_evento', 'soap_wsdl') ?: 'evento_webservice_v1_1.wsdl',
            'soap_username' => get_config('local_evento', 'soap_username') ?: '',
            'soap_password' => get_config('local_evento', 'soap_password') ?: '',
            'soap_timeout' => get_config('local_evento', 'soap_timeout') ?: 30,
            'dev_logging_enabled' => get_config('local_evento', 'dev_logging_enabled') ?: false,
        ];
    }
}
