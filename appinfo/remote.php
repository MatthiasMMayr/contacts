<?php
/**
 * ownCloud - Addressbook
 *
 * @author Jakob Sack
 * @author Thomas Tanghus
 * @copyright 2011 Jakob Sack mail@jakobsack.de
 * @copyright 2011-2014 Thomas Tanghus (thomas@tanghus.net)
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

if (!\OC::$server->getAppManager()->isInstalled('contacts')) {
	throw new Exception('App not installed: contacts');
}

if (substr(OCP\Util::getRequestUri(), 0, strlen(OC_App::getAppWebPath('contacts').'/carddav.php'))
	=== OC_App::getAppWebPath('contacts').'/carddav.php'
) {
	$baseuri = OC_App::getAppWebPath('contacts').'/carddav.php';
}

// only need authentication apps
$RUNTIME_APPTYPES = array('authentication');
OC_App::loadApps($RUNTIME_APPTYPES);

// Backends
$authBackend = new \OC\Connector\Sabre\Auth();
$principalBackend = new \OC\Connector\Sabre\Principal(
	\OC::$server->getConfig(),
	\OC::$server->getUserManager()
);

$addressbookbackends = array();
$addressbookbackends[] = new OCA\Contacts\Backend\Database(\OCP\User::getUser());
$backends = array('local', 'shared');
if (\OCP\Config::getAppValue('contacts', 'backend_ldap', "false") === "true") {
	$backends[] = 'ldap';
}
$carddavBackend = new OCA\Contacts\CardDAV\Backend($backends);

// Root nodes
$principalCollection = new \Sabre\CalDAV\Principal\Collection($principalBackend);
$principalCollection->disableListing = true; // Disable listing

$addressBookRoot = new OCA\Contacts\CardDAV\AddressBookRoot($principalBackend, $carddavBackend);
$addressBookRoot->disableListing = true; // Disable listing

$nodes = array(
	$principalCollection,
	$addressBookRoot,
);

// Fire up server
$server = new \Sabre\DAV\Server($nodes);
$server->httpRequest->setUrl(\OC::$server->getRequest()->getRequestUri());
$server->setBaseUri($baseuri);
// Add plugins
$server->addPlugin(new \OC\Connector\Sabre\MaintenancePlugin());
$server->addPlugin(new \Sabre\DAV\Auth\Plugin($authBackend, 'ownCloud'));
$server->addPlugin(new OCA\Contacts\CardDAV\Plugin());
$server->addPlugin(new \Sabre\DAVACL\Plugin());
$server->addPlugin(new \Sabre\CardDAV\VCFExportPlugin());
$server->addPlugin(new \OC\Connector\Sabre\ExceptionLoggerPlugin('carddav', \OC::$server->getLogger()));
$server->addPlugin(new \OC\Connector\Sabre\AppEnabledPlugin(
	'contacts',
	OC::$server->getAppManager()
));


// And off we go!
$server->exec();
