<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

require_once dirname(__FILE__) . '/PlatformTestBase.php';
require_once dirname(__FILE__) . '/../../../../generator/lib/platform/DefaultPlatform.php';
require_once dirname(__FILE__) . '/../../../../generator/lib/model/Column.php';

/**
 *
 * @package    generator.platform 
 */
class DefaultPlatformTest extends PlatformTestBase
{
	/**
	 * Platform object.
	 *
	 * @var        Platform
	 */
	protected static $platform;
	
	/**
	 * Get the Platform object for this class
	 *
	 * @return     Platform
	 */
	protected function getPlatform()
	{
		if (null === self::$platform) {
			self::$platform = new DefaultPlatform();
		}
		return self::$platform;
	}

	public function testQuote()
	{
		$p = $this->getPlatform();

		$unquoted = "Nice";
		$quoted = $p->quote($unquoted);

		$this->assertEquals("'$unquoted'", $quoted);


		$unquoted = "Naughty ' string";
		$quoted = $p->quote($unquoted);
		$expected = "'Naughty '' string'";
		$this->assertEquals($expected, $quoted);
	}

}
