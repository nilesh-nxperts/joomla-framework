<?php
/**
 * @copyright  Copyright (C) 2005 - 2013 Open Source Matters. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Joomla\Database\Tests;

use Joomla\Factory;

/**
 * Abstract test case class for Oracle database testing.
 *
 * @since  1.0
 */
abstract class DatabaseOracleCase extends DatabaseCase
{
	/**
	 * @var    \Joomla\Database\Oracle\OracleDriver  The active database driver being used for the tests.
	 * @since  1.0
	 */
	protected static $driver;

	/**
	 * @var    array  The database driver options for the connection.
	 * @since  1.0
	 */
	private static $_options = array('driver' => 'oracle');

	/**
	 * @var    \Joomla\Database\Oracle\OracleDriver  The saved database driver to be restored after these tests.
	 * @since  1.0
	 */
	private static $_stash;

	/**
	 * This method is called before the first test of this test class is run.
	 *
	 * An example DSN would be: dbname=//localhost:1521/joomla_ut;charset=AL32UTF8;user=utuser;pass=ut1234
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public static function setUpBeforeClass()
	{
		// First let's look to see if we have a DSN defined or in the environment variables.
		if (defined('JTEST_DATABASE_ORACLE_DSN') || getenv('JTEST_DATABASE_ORACLE_DSN'))
		{
			$dsn = defined('JTEST_DATABASE_ORACLE_DSN') ? JTEST_DATABASE_ORACLE_DSN : getenv('JTEST_DATABASE_ORACLE_DSN');
		}
		else
		{
			return;
		}

		// First let's trim the oci: part off the front of the DSN if it exists.
		if (strpos($dsn, 'oci:') === 0)
		{
			$dsn = substr($dsn, 4);
		}

		// Split the DSN into its parts over semicolons.
		$parts = explode(';', $dsn);

		// Parse each part and populate the options array.
		foreach ($parts as $part)
		{
			list ($k, $v) = explode('=', $part, 2);

			switch ($k)
			{
				case 'charset':
					self::$_options['charset'] = $v;
					break;
				case 'dbname':
					$components = parse_url($v);
					self::$_options['host'] = $components['host'];
					self::$_options['port'] = $components['port'];
					self::$_options['database'] = ltrim($components['path'], '/');
					break;
				case 'user':
					self::$_options['user'] = $v;
					break;
				case 'pass':
					self::$_options['password'] = $v;
					break;
				case 'dbschema':
					self::$_options['schema'] = $v;
					break;
				case 'prefix':
					self::$_options['prefix'] = $v;
					break;
			}
		}

		// Ensure some defaults.
		self::$_options['charset'] = isset(self::$_options['charset']) ? self::$_options['charset'] : 'AL32UTF8';
		self::$_options['port'] = isset(self::$_options['port']) ? self::$_options['port'] : 1521;

		try
		{
			// Attempt to instantiate the driver.
			self::$driver = \Joomla\Database\DatabaseDriver::getInstance(self::$_options);
		}
		catch (\RuntimeException $e)
		{
			self::$driver = null;
		}

		// If for some reason an exception object was returned set our database object to null.
		if (self::$driver instanceof \Exception)
		{
			self::$driver = null;
		}

		// Setup the factory pointer for the driver and stash the old one.
		self::$_stash = Factory::$database;
		Factory::$database = self::$driver;
	}

	/**
	 * This method is called after the last test of this test class is run.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	public static function tearDownAfterClass()
	{
		Factory::$database = self::$_stash;
		self::$driver = null;
	}

	/**
	 * Returns the default database connection for running the tests.
	 *
	 * @return  \PHPUnit_Extensions_Database_DB_DefaultDatabaseConnection
	 *
	 * @since   1.0
	 */
	protected function getConnection()
	{
		// Compile the connection DSN.
		$dsn = 'oci:dbname=//' . self::$_options['host'] . ':' . self::$_options['port'] . '/' . self::$_options['database'];
		$dsn .= ';charset=' . self::$_options['charset'];

		// Create the PDO object from the DSN and options.
		$pdo = new \PDO($dsn, self::$_options['user'], self::$_options['password']);

		return $this->createDefaultDBConnection($pdo, self::$_options['database']);
	}
}
