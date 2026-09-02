# Evento

This plugin provides the access to the Evento SOAP webservice of FH Graubuenden. It is
a pure encapsulation layer: it wraps the SOAP operations into PHP methods and does not
store anything and does not change anything in Moodle by itself.

Consumers of this plugin are `enrol_evento` and `local_eventocoursecreation`.

## Requirements

* The PHP `soap` extension.
* `version.php` declares Moodle 3.2 as the minimum. The current release is developed
  for Moodle 5.2.

## Installation and configuration

The plugin need the following settings:

* Location: The Webservice URL of Evento
* WSDL-filename: Filename of the right wsdl-definition.
* URI: URI namespace (http://service.webservice.htwchur.ch)
* username: username of the basic authentication
* password: password of the basic authentication
* AD SID: SID prefix, which is not part of the shibboleth ID
* shibboleth ID suffix: Suffix to create the shibboleth ID

Note that the setting "Location" always overrides the service address contained in the
WSDL file. The WSDL file only defines the contract, never the endpoint that is called.

All settings are shared by every consumer of this plugin. Pointing "Location" at a test
system therefore redirects the enrolment sync and the course creation as well. Use the
`--wslocation` option of the CLI scripts instead, see below.

## WSDL versions

The folder `wsdl` keeps every WSDL version that has been in use, so that a rollback is
possible by changing the setting "WSDL-filename" alone.

| File | Contents |
| --- | --- |
| `evento_webservice_v1_2.wsdl` | Current. Adds `getEventoModulBeschreibung` and `listEventoOE`, adds `EventoAnlass.anlass_IDAnlassModul` and `EventoBenutzer.benutzerIdPerson`, removes `EventoAnlass.anlass_IDAnlassStudiengang`, `EventoAdresse.adr_URL` and `EventoPerson.person_MWSTNr`. |
| `evento_webservice_v1_1.wsdl` | Previous version, rollback target. |
| `evento_webservice_v1.wsdl` | Initial version. |

None of the fields removed in version 1.2 is referenced by any plugin.

## API

Everything lives in `local_evento_evento_service`, which is autoloaded, so a
`require_once` is not needed. Pass a client to the constructor to inject a test double,
otherwise the client is built from the plugin settings.

```php
$service = new local_evento_evento_service();
$event = $service->get_event_by_number('mod.bspEA2.HS16_BS.001');
```

| Method | Returns |
| --- | --- |
| `init_call()` | `true` if the connection works, `false` otherwise |
| `get_event_by_number($number)` | `EventoAnlass`, an array of them, or null |
| `get_events_by_filter($eventoanlassfilter, $limitationfilter2)` | `EventoAnlass`, an array of them, or null |
| `get_modulbeschreibung_by_number($anlassnummer)` | `EventoModulBeschreibung` or null |
| `get_status_list($idanlasstyp = null)` | array of `EventoStatus` |
| `get_enrolments_by_eventid($eventid)` | array of `EventoPersonenAnmeldung` |
| `get_person_by_id($personid)` | `EventoPerson` or null |
| `get_ad_accounts_by_evento_personid($personid, $isactive, $isstudent)` | array of `ADAccount` |
| `get_student_ad_accounts($isactive)`, `get_lecturer_ad_accounts($isactive)`, `get_employee_ad_accounts($isactive)`, `get_all_ad_accounts($isactive)` | array of `ADAccount` |
| `sid_to_shibbolethid($sid)`, `shibbolethid_to_sid($shibbolethid)` | string |

Most operations may answer with a single object or with an array of them, depending on
how many records match. Wrap those in `local_evento_evento_service::to_array()` before
iterating. `get_modulbeschreibung_by_number()` is the exception: its response holds at
most one object by contract, so it never returns an array.

## Module descriptions

`getEventoModulBeschreibung` takes an event number and answers with at most one
description. The service picks the record itself, the selection rule is not part of the
contract. According to the service owner the description belongs to the main event, the
module, and not to the individual course run.

```php
$service = new local_evento_evento_service();

try {
    $modulbeschreibung = $service->get_modulbeschreibung_by_number($anlassnummer);
} catch (local_evento_service_exception $ex) {
    // The call failed. $ex->faultcode is the SOAP faultcode, or null when the failure
    // did not originate from a SoapFault. Retry later, do not treat this as "no data".
    return;
}

if (is_null($modulbeschreibung)) {
    // The call succeeded, the service knows no description for this event number.
    return;
}

$mb = local_evento_evento_service::normalize_modulbeschreibung($modulbeschreibung);
```

### Error model

The distinction matters for a sync, so it is deliberate and stable:

* **null** means "no data". Either the event number was empty or whitespace only, in
  which case no call is made at all, or the response carried no `return` element.
* **`local_evento_service_exception`** means "the call could not be completed", for
  example an unreachable service, wrong credentials or a SOAP fault. It exposes
  `faultcode`, `faultstring`, `operation` and `previousexception`. Every failure is
  also written to the Moodle debug log before the exception is raised.

Never treat an exception as "the description was deleted", or a sync will remove
content whenever the service has a hiccup.

### Normalized values

The getter hands over the raw SOAP object. `normalize_modulbeschreibung()` turns it into
comparable values, which is what a sync should store and compare:

| Property | Notes |
| --- | --- |
| `idanlass`, `idmb`, `idstatus` | int or null |
| `mbtext` | string or null, untouched, neither trimmed nor escaped |
| `mbversion` | float or null, the raw value |
| `mbversionscaled` | int or null, the version times 1000 |
| `mbversionstring` | string or null, for logs and display, e.g. `1.100` |
| `mbgueltigab` | int or null, unix timestamp |
| `mbgueltigabraw` | string or null, the literal `xs:dateTime` value |

`mbVersion` is an `xs:float`, so comparing it with `==` is unreliable, especially after
a roundtrip through the database. Store and compare **`mbversionscaled`**: as an integer
both equality and ordering stay exact.

Note that all six elements of `EventoModulBeschreibung` are optional and nillable, so
every property can legitimately be null.

## CLI tools

Both scripts accept `--wslocation` and `--wsdl` to query another endpoint or another
contract for that single call, without touching the shared plugin settings.

    # Read a module description and print it including the php data types.
    sudo -u www-data /usr/bin/php local/evento/cli/get_modulbeschreibung.php --help

    # Print the status list, to resolve a numeric status id into its name.
    sudo -u www-data /usr/bin/php local/evento/cli/list_status.php --help

`get_modulbeschreibung.php` additionally offers `--raw` to dump the SOAP request and
response XML, and `--compare` to read the event itself and check whether the description
refers to the event or to its module.

## Tests

The tests inject a test double as the SOAP client, so they never talk to Evento.

Run them from the Moodle root, the directory above `public`. It needs
`$CFG->phpunit_dataroot` and `$CFG->phpunit_prefix` in `config.php`, and the composer
dependencies installed.

    composer install
    php public/admin/tool/phpunit/cli/init.php
    vendor/bin/phpunit public/local/evento/tests/evento_service_test.php

## Open questions

These cannot be answered from the WSDL and are open until verified against the service:

* Which description the service picks when an event has several versions. It answers
  with at most one, and the rule is unknown. Related: whether a `mbGueltigAb` in the
  future is served at all.
* Whether `EventoModulBeschreibung.idStatus` uses the same value domain as
  `listEventoStatus`, and which id marks a released description. Use
  `cli/list_status.php --idstatus=<value>` to check.

## License

* Copyright (C) HTW Chur

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details:
http://www.gnu.org/copyleft/gpl.html
