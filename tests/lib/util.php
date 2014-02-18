<?php
/**
 * Copyright (c) 2012 Lukas Reschke <lukas@statuscode.ch>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

class Test_Util extends PHPUnit_Framework_TestCase {
	public function testGetVersion() {
		$version = \OC_Util::getVersion();
		$this->assertTrue(is_array($version));
		foreach ($version as $num) {
			$this->assertTrue(is_int($num));
		}
	}

	public function testGetVersionString() {
		$version = \OC_Util::getVersionString();
		$this->assertTrue(is_string($version));
	}

	public function testGetEditionString() {
		$edition = \OC_Util::getEditionString();
		$this->assertTrue(is_string($edition));
	}

	function testFormatDate() {
		date_default_timezone_set("UTC");

		$result = OC_Util::formatDate(1350129205);
		$expected = 'October 13, 2012 11:53';
		$this->assertEquals($expected, $result);

		$result = OC_Util::formatDate(1102831200, true);
		$expected = 'December 12, 2004';
		$this->assertEquals($expected, $result);
	}

	function testCallRegister() {
		$result = strlen(OC_Util::callRegister());
		$this->assertEquals(20, $result);
	}

	function testSanitizeHTML() {
		$badString = "<script>alert('Hacked!');</script>";
		$result = OC_Util::sanitizeHTML($badString);
		$this->assertEquals("&lt;script&gt;alert(&#039;Hacked!&#039;);&lt;/script&gt;", $result);

		$goodString = "This is an harmless string.";
		$result = OC_Util::sanitizeHTML($goodString);
		$this->assertEquals("This is an harmless string.", $result);
	}
	
	function testEncodePath(){
		$component = '/§#@test%&^ä/-child';
		$result = OC_Util::encodePath($component);
		$this->assertEquals("/%C2%A7%23%40test%25%26%5E%C3%A4/-child", $result);
	}

	public function testFileInfoLoaded() {
		$expected = function_exists('finfo_open');
		$this->assertEquals($expected, \OC_Util::fileInfoLoaded());
	}

	public function testIsInternetConnectionEnabled() {
		\OC_Config::setValue("has_internet_connection", false);
		$this->assertFalse(\OC_Util::isInternetConnectionEnabled());

		\OC_Config::setValue("has_internet_connection", true);
		$this->assertTrue(\OC_Util::isInternetConnectionEnabled());
	}

	function testGenerateRandomBytes() {
		$result = strlen(OC_Util::generateRandomBytes(59));
		$this->assertEquals(59, $result);
	}

	function testGetDefaultEmailAddress() {
		$email = \OCP\Util::getDefaultEmailAddress("no-reply");
		$this->assertEquals('no-reply@localhost.localdomain', $email);
	}

	function testGetDefaultEmailAddressFromConfig() {
		OC_Config::setValue('mail_domain', 'example.com');
		$email = \OCP\Util::getDefaultEmailAddress("no-reply");
		$this->assertEquals('no-reply@example.com', $email);
		OC_Config::deleteKey('mail_domain');
	}

	function testGetConfiguredEmailAddressFromConfig() {
		OC_Config::setValue('mail_domain', 'example.com');
		OC_Config::setValue('mail_from_address', 'owncloud');
		$email = \OCP\Util::getDefaultEmailAddress("no-reply");
		$this->assertEquals('owncloud@example.com', $email);
		OC_Config::deleteKey('mail_domain');
		OC_Config::deleteKey('mail_from_address');
	}

	function testGetInstanceIdGeneratesValidId() {
		OC_Config::deleteKey('instanceid');
		$this->assertStringStartsWith('oc', OC_Util::getInstanceId());
	}

	/**
	 * Tests that the home storage is not wrapped when no quota exists.
	 */
	function testHomeStorageWrapperWithoutQuota() {
		$user1 = uniqid();
		\OC_User::createUser($user1, 'test');
		OC_Preferences::setValue($user1, 'files', 'quota', 'none');
		\OC_User::setUserId($user1);

		\OC_Util::setupFS($user1);

		$userMount = \OC\Files\Filesystem::getMountManager()->find('/' . $user1 . '/');
		$this->assertNotNull($userMount);
		$this->assertNotInstanceOf('\OC\Files\Storage\Wrapper\Quota', $userMount->getStorage());

		// clean up
		\OC_User::setUserId('');
		\OC_User::deleteUser($user1);
		OC_Preferences::deleteUser($user1);
		\OC_Util::tearDownFS();
	}

	/**
	 * Tests that the home storage is not wrapped when no quota exists.
	 */
	function testHomeStorageWrapperWithQuota() {
		$user1 = uniqid();
		\OC_User::createUser($user1, 'test');
		OC_Preferences::setValue($user1, 'files', 'quota', '1024');
		\OC_User::setUserId($user1);

		\OC_Util::setupFS($user1);

		$userMount = \OC\Files\Filesystem::getMountManager()->find('/' . $user1 . '/');
		$this->assertNotNull($userMount);
		$this->assertInstanceOf('\OC\Files\Storage\Wrapper\Quota', $userMount->getStorage());

		// ensure that root wasn't wrapped
		$rootMount = \OC\Files\Filesystem::getMountManager()->find('/');
		$this->assertNotNull($rootMount);
		$this->assertNotInstanceOf('\OC\Files\Storage\Wrapper\Quota', $rootMount->getStorage());

		// clean up
		\OC_User::setUserId('');
		\OC_User::deleteUser($user1);
		OC_Preferences::deleteUser($user1);
		\OC_Util::tearDownFS();
	}

	/**
	 * @dataProvider naturalSortDataProvider
	 */
	public function testNaturalSortCompare($array, $sorted)
	{
		usort($array, array('\OCP\Util', 'naturalSortCompare'));
		$this->assertEquals($sorted, $array);
	}

	/**
	 * Data provider for natural sort.
	 * Must provide the same result as in core/js/tests/specs/coreSpec.js
	 * @return array test cases
	 */
	public function naturalSortDataProvider()
	{
		return array(
			// different casing
			array(
				// unsorted
				array(
					'aaa',
					'bbb',
					'BBB',
					'AAA'
				),
				// sorted
				array(
					'aaa',
					'AAA',
					'bbb',
					'BBB'
				)
			),
			// numbers
			array(
				// unsorted
				array(
					'124.txt',
					'abc1',
					'123.txt',
					'abc',
					'abc2',
					'def (2).txt',
					'ghi 10.txt',
					'abc12',
					'def.txt',
					'def (1).txt',
					'ghi 2.txt',
					'def (10).txt',
					'abc10',
					'def (12).txt',
					'z',
					'ghi.txt',
					'za',
					'ghi 1.txt',
					'ghi 12.txt',
					'zz',
				),
				// sorted
				array(
					'123.txt',
					'124.txt',
					'abc',
					'abc1',
					'abc2',
					'abc10',
					'abc12',
					'def.txt',
					'def (1).txt',
					'def (2).txt',
					'def (10).txt',
					'def (12).txt',
					'ghi.txt',
					'ghi 1.txt',
					'ghi 2.txt',
					'ghi 10.txt',
					'ghi 12.txt',
					'z',
					'za',
					'zz',
				)
			),
			// chinese characters
			array(
				// unsorted
				array(
					'十.txt',
					'一.txt',
					'二.txt',
					'十 2.txt',
					'三.txt',
					'四.txt',
					'abc.txt',
					'五.txt',
					'七.txt',
					'八.txt',
					'九.txt',
					'六.txt',
					'十一.txt',
					'波.txt',
					'破.txt',
					'莫.txt',
					'啊.txt',
					'123.txt',
				),
				// sorted
				array(
					'123.txt',
					'abc.txt',
					'一.txt',
					'七.txt',
					'三.txt',
					'九.txt',
					'二.txt',
					'五.txt',
					'八.txt',
					'六.txt',
					'十.txt',
					'十 2.txt',
					'十一.txt',
					'啊.txt',
					'四.txt',
					'波.txt',
					'破.txt',
					'莫.txt',
				)
			),
			// with umlauts
			array(
				// unsorted
				array(
					'öh.txt',
					'Äh.txt',
					'oh.txt',
					'Üh 2.txt',
					'Üh.txt',
					'ah.txt',
					'Öh.txt',
					'uh.txt',
					'üh.txt',
					'äh.txt',
				),
				// sorted
				array(
					'ah.txt',
					'äh.txt',
					'Äh.txt',
					'oh.txt',
					'öh.txt',
					'Öh.txt',
					'uh.txt',
					'üh.txt',
					'Üh.txt',
					'Üh 2.txt',
				)
			),
		);
	}

	/**
	 * @dataProvider baseNameProvider
	 */
	public function testBaseName($expected, $file)
	{
		$base = \OC_Util::basename($file);
		$this->assertEquals($expected, $base);
	}

	public function baseNameProvider()
	{
		return array(
			array('public_html', '/home/user/public_html/'),
			array('public_html', '/home/user/public_html'),
			array('', '/'),
			array('public_html', 'public_html'),
			array('442aa682de2a64db1e010f50e60fd9c9', 'local::C:\Users\ADMINI~1\AppData\Local\Temp\2/442aa682de2a64db1e010f50e60fd9c9/')
		);
	}
}
