# Evento
This plugin provides the access to the Evento SOAP webservice.

# Installation an Configuration
The plugin need the following settings:

* Location: The Webservcie URL of Evento
* WSDL-filename: Filename of the right wsdl-definition.
* URI: URI namespace (http://service.webservice.htwchur.ch)
* username: username of the basic authentification
* password: password of the basic authentification
* AD SID: SID prefix, which is not part of the shibboleth ID
* shibboleth ID suffix: Suffix to create the shibboleth ID 

Note that the setting "Location" always overrides the service address contained in the
WSDL file. The WSDL file only defines the contract, never the endpoint that is called.

## WSDL versions

The folder `wsdl` keeps every WSDL version that has been in use, so that a rollback is
possible by changing the setting "WSDL-filename" alone.

| File | Contents |
| --- | --- |
| `evento_webservice_v1_2.wsdl` | Current. Adds `getEventoModulBeschreibung` and `listEventoOE`, adds `EventoAnlass.anlass_IDAnlassModul` and `EventoBenutzer.benutzerIdPerson`, removes `EventoAnlass.anlass_IDAnlassStudiengang`, `EventoAdresse.adr_URL` and `EventoPerson.person_MWSTNr`. |
| `evento_webservice_v1_1.wsdl` | Previous version, rollback target. |
| `evento_webservice_v1.wsdl` | Initial version. |

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
