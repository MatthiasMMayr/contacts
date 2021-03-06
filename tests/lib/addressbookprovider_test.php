<?php
/**
 * Copyright (c) 2013 Thomas Tanghus (thomas@tanghus.net)
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\Contacts;


class AddressBookProviderTest extends TestCase {

	/**
	* @var array
	*/
	protected $abinfo;
	/**
	* @var \OCA\Contacts\Addressbook
	*/
	protected $ab;
	/**
	* @var \OCA\Contacts\Backend\AbstractBackend
	*/
	protected $backend;

	/**
	 * @var \OCA\Contacts\AddressbookProvider
	 */
	private $provider;

	/**
	 * @var array
	 */
	private $contactIds = array();

	public function setUp() {
		parent::setUp();

		$this->backend = new Backend\Database($this->testUser);
		$this->abinfo = array('displayname' => uniqid('display_'));
		$this->ab = new AddressBook($this->backend, $this->abinfo);

		$this->provider = new AddressbookProvider($this->ab);

		$card = new \OCA\Contacts\VObject\VCard();
		$uid = substr(md5(rand().time()), 0, 10);
		$card->add('UID', $uid);
		$card->add('FN', 'Max Mustermann');
		$id = $this->ab->addChild($card);
		Utils\Properties::updateIndex($id, $card);
		$this->contactIds[] = $id;

		// Add extra contact
		$card = new \OCA\Contacts\VObject\VCard();
		$uid = substr(md5(rand().time()), 0, 10);
		$card->add('UID', $uid);
		$card->add('FN', 'Jan Janssens');
		$id = $this->ab->addChild($card);
		Utils\Properties::updateIndex($id, $card);
		$this->ab->deleteChild($id);
	}

	public function tearDown() {
		unset($this->backend);
		unset($this->ab);
		Utils\Properties::purgeIndexes($this->contactIds);

		parent::tearDown();
	}

	public function testSearch() {
		$result = $this->provider->search('',array('FN'), array());

		$this->assertTrue(is_array($result));
		$this->assertEquals(1, count($result));
		$this->assertEquals('Max Mustermann', $result[0]['FN']);
	}


}
