<?php

class i8Database {
	/** @var \PDO $instance */
	private static $instance = null;

	public static function get(): \PDO {
		if (self::$instance === null) {
			self::$instance = new \PDO($_ENV['DATABASE_DSN']);
			self::$instance->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
			self::$instance->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
		}

		return self::$instance;
	}
}