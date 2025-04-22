Path | What it does
composer.json | Declares dev‑only test tools.Runtime section deliberately empty for now.
composer.lock | Frozen dependency versions (commit this).
vendor/ | Runtime libraries only.Dev‑only libs are installed locally/CI but excluded from release ZIP.
src/ | Your PSR‑4 application code (Fhgr\\MoodleLocalEvento\\).
tests/bootstrap.php | Loads the plugin‑local autoloader and configures PHP‑VCR.
tests/phpunit.xml | PHPUnit config that registers the VCR TestListener.
tests/fixtures/cassettes/ | YAML cassettes recorded by PHP‑VCR (one per remote scenario).
.gitattributes | Ensures dev‑only libs are left out of the Moodle release ZIP.


Task | Command
Install everything (dev box / CI) | composer install --dev
Run tests offline (replay only) | vendor/bin/phpunit -c tests
Record/refresh a cassette | VCR_MODE=record vendor/bin/phpunit --filter SomeTest(re‐records only the selected test method)
Verify no tests hit the real network | VCR_MODE=none vendor/bin/phpunit
Package for Moodle.org | git archive --format=zip --output evento.zip HEAD


/**
 * @covers \Fhgr\MoodleLocalEvento\EventoClient::list_anlass
 * @vcr evento_list_anlass      // cassette name (auto‑loaded)
 */
public function test_list_anlass_maps_response(): void {
    $soap   = new \SoapClient(get_config('local_evento', 'wsdl'));
    $client = new EventoClient($soap);

    $out = $client->list_anlass(['idAnlass' => 42]);

    $this->assertSame('Bachelor Thesis', $out[0]->title);
}
The @vcr annotation switches PHP‑VCR on, inserts the cassette, and ejects it automatically thanks to the TestListener configured in tests/phpunit.xml.


The @vcr annotation switches PHP‑VCR on, inserts the cassette, and ejects it automatically thanks to the TestListener configured in tests/phpunit.xml.


    One cassette per logical scenario (evento_list_anlass.yml,
    evento_auth_error.yml, …).

    Anonymise any personal data directly in the YAML.

    Re‑record only when the Evento API version changes.


Symptom | Likely cause / fix
Unexpected http request during CI | Forgot to commit the new cassette or ran tests without VCR_MODE=record.
Class 'VCR\VCR' not found | Autoloader not included – check tests/bootstrap.php path.
Flaky tests when network is down | A test misses the @vcr annotation or manual turnOn().