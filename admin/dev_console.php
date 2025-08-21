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
 * TODO describe file dev_console
 *
 * @package    local_evento
 * @copyright  2025 YOUR NAME <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');

require_login();
require_capability('moodle/site:config', context_system::instance());

$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('admin');
$PAGE->set_title('Evento V2 Development Console');
$PAGE->set_heading('Evento V2 Development Console');
$PAGE->set_url('/local/evento/admin/dev_console.php');

echo $OUTPUT->header();

// Configuration check
$config_status = [];
$config_status['soap_location'] = get_config('local_evento', 'soap_location') ?: 'NOT SET';
$config_status['soap_wsdl'] = get_config('local_evento', 'soap_wsdl') ?: 'NOT SET';
$config_status['soap_username'] = get_config('local_evento', 'soap_username') ? 'SET' : 'NOT SET';
$config_status['soap_password'] = get_config('local_evento', 'soap_password') ? 'SET' : 'NOT SET';
$config_status['dev_logging'] = get_config('local_evento', 'dev_logging_enabled') ? 'ENABLED' : 'DISABLED';

echo '<div class="card mb-3">';
echo '<div class="card-header"><h5>Configuration Status</h5></div>';
echo '<div class="card-body">';
echo '<table class="table table-sm">';
foreach ($config_status as $key => $value) {
    $class = ($value === 'NOT SET') ? 'text-danger' : 'text-success';
    echo "<tr><td>{$key}</td><td class='{$class}'>{$value}</td></tr>";
}
echo '</table>';
echo '<a href="' . $CFG->wwwroot . '/admin/settings.php?section=local_evento" class="btn btn-sm btn-primary">Configure Settings</a>';
echo '</div></div>';

// Service availability test
echo '<div class="card mb-3">';
echo '<div class="card-header"><h5>Service Availability</h5></div>';
echo '<div class="card-body">';

try {
    $service = new \local_evento\service\soap_evento_service();
    $available = $service->is_service_available();
    
    if ($available) {
        echo '<div class="alert alert-success">✅ Evento SOAP Service is available!</div>';
    } else {
        echo '<div class="alert alert-warning">⚠️ Evento SOAP Service is not responding</div>';
    }
} catch (\Exception $e) {
    echo '<div class="alert alert-danger">❌ Service Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
}

echo '</div></div>';

// Test Form
echo '<div class="card mb-3">';
echo '<div class="card-header"><h5>Test Evento Service</h5></div>';
echo '<div class="card-body">';

echo '<form method="post">';
echo '<div class="row">';
echo '<div class="col-md-8">';
echo '<label for="anlassnummer" class="form-label">Test Anlassnummer:</label>';
echo '<input type="text" name="anlassnummer" id="anlassnummer" value="' . ($_POST['anlassnummer'] ?? 'mod.bspEA.HS24_BSC.001') . '" class="form-control" placeholder="mod.courseCode.HS24_PROG.001" />';
echo '<small class="form-text text-muted">Format: mod.{courseCode}.{semester}_{program}.{instance}</small>';
echo '</div>';
echo '<div class="col-md-4">';
echo '<label class="form-label">&nbsp;</label><br>';
echo '<button type="submit" name="action" value="parse" class="btn btn-outline-primary me-2">Parse Only</button>';
echo '<button type="submit" name="action" value="course" class="btn btn-primary me-2">Get Course</button>';
echo '<button type="submit" name="action" value="enrollments" class="btn btn-success">Get Enrollments</button>';
echo '</div>';
echo '</div>';
echo '</form>';

if ($_POST['anlassnummer'] ?? false) {
    $anlassnummer = $_POST['anlassnummer'];
    $action = $_POST['action'] ?? 'course';
    
    echo '<hr><h6>Results:</h6>';
    
    try {
        if ($action === 'parse') {
            // Test parsing only
            $parser = new \local_evento\parser\anlassnummer_parser();
            $parsed = $parser->parse($anlassnummer);
            
            echo '<div class="alert alert-success">';
            echo '<strong>✅ Parsing Successful</strong><br>';
            echo '<strong>Original:</strong> ' . htmlspecialchars($parsed->get_original()) . '<br>';
            echo '<strong>Course Code:</strong> ' . htmlspecialchars($parsed->get_course_code()) . '<br>';
            echo '<strong>Study Program:</strong> ' . htmlspecialchars($parsed->get_study_program()) . '<br>';
            echo '<strong>Semester:</strong> ' . htmlspecialchars($parsed->get_semester()) . '<br>';
            echo '<strong>Instance:</strong> ' . htmlspecialchars($parsed->get_instance_number()) . '<br>';
            echo '</div>';
            
        } else {
            // Test full service
            $service = new \local_evento\service\soap_evento_service();
            
            if ($action === 'course') {
                $course_info = $service->get_course_info($anlassnummer);
                
                echo '<div class="alert alert-success">';
                echo '<strong>✅ Course Info Retrieved</strong><br>';
                echo '<strong>Title:</strong> ' . htmlspecialchars($course_info->get_title()) . '<br>';
                echo '<strong>Code:</strong> ' . htmlspecialchars($course_info->get_code()) . '<br>';
                echo '<strong>Program:</strong> ' . htmlspecialchars($course_info->get_study_program()) . '<br>';
                echo '<strong>Semester:</strong> ' . htmlspecialchars($course_info->get_semester_type() . $course_info->get_year()) . '<br>';
                echo '<strong>Start Date:</strong> ' . ($course_info->get_start_date() ? $course_info->get_start_date()->format('Y-m-d') : 'Not set') . '<br>';
                echo '<strong>End Date:</strong> ' . ($course_info->get_end_date() ? $course_info->get_end_date()->format('Y-m-d') : 'Not set') . '<br>';
                echo '<strong>Status:</strong> ' . htmlspecialchars($course_info->get_status()) . '<br>';
                echo '</div>';
                
                // Also show raw data if dev logging is enabled
                if (get_config('local_evento', 'dev_logging_enabled')) {
                    echo '<details class="mt-2">';
                    echo '<summary>Raw Data (Debug)</summary>';
                    echo '<pre>' . htmlspecialchars(json_encode($course_info, JSON_PRETTY_PRINT)) . '</pre>';
                    echo '</details>';
                }
                
            } elseif ($action === 'enrollments') {
                $enrollments = $service->get_enrollments($anlassnummer);
                
                echo '<div class="alert alert-success">';
                echo '<strong>✅ Enrollments Retrieved</strong><br>';
                echo '<strong>Total Count:</strong> ' . $enrollments->get_total_count() . '<br>';
                echo '<strong>Unique Persons:</strong> ' . count($enrollments->get_person_ids()) . '<br>';
                echo '</div>';
                
                if ($enrollments->get_total_count() > 0) {
                    echo '<table class="table table-sm mt-2">';
                    echo '<thead><tr><th>Person ID</th><th>Status</th><th>Role</th><th>Date</th></tr></thead>';
                    echo '<tbody>';
                    foreach (array_slice($enrollments->get_enrollments(), 0, 10) as $enrollment) {
                        echo '<tr>';
                        echo '<td>' . $enrollment->get_person_id() . '</td>';
                        echo '<td>' . htmlspecialchars($enrollment->get_status()) . '</td>';
                        echo '<td>' . htmlspecialchars($enrollment->get_role()) . '</td>';
                        echo '<td>' . ($enrollment->get_enrollment_date() ? $enrollment->get_enrollment_date()->format('Y-m-d') : 'N/A') . '</td>';
                        echo '</tr>';
                    }
                    echo '</tbody></table>';
                    
                    if ($enrollments->get_total_count() > 10) {
                        echo '<small class="text-muted">Showing first 10 of ' . $enrollments->get_total_count() . ' enrollments</small>';
                    }
                }
            }
        }
        
    } catch (\local_evento\exception\parsing_exception $e) {
        echo '<div class="alert alert-danger">';
        echo '<strong>❌ Parsing Error:</strong> ' . htmlspecialchars($e->getMessage());
        echo '</div>';
        
    } catch (\local_evento\exception\evento_service_exception $e) {
        echo '<div class="alert alert-danger">';
        echo '<strong>❌ Service Error:</strong> ' . htmlspecialchars($e->getMessage());
        if ($e->getPrevious()) {
            echo '<br><strong>Details:</strong> ' . htmlspecialchars($e->getPrevious()->getMessage());
        }
        echo '</div>';
        
    } catch (\Exception $e) {
        echo '<div class="alert alert-danger">';
        echo '<strong>❌ Unexpected Error:</strong> ' . htmlspecialchars($e->getMessage());
        echo '<br><strong>File:</strong> ' . $e->getFile() . ':' . $e->getLine();
        echo '</div>';
    }
}

echo '</div></div>';

// Show recent logs if dev logging is enabled
if (get_config('local_evento', 'dev_logging_enabled')) {
    echo '<div class="card mb-3">';
    echo '<div class="card-header"><h5>Development Logging</h5></div>';
    echo '<div class="card-body">';
    echo '<div class="alert alert-info">';
    echo '<strong>Development logging is enabled.</strong> Check your error logs or debugging output for detailed API request/response information.';
    echo '</div>';
    echo '</div></div>';
}

echo $OUTPUT->footer();