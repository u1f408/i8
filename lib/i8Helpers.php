<?php
use Base32\Base32;

class i8Helpers {
	const SLUG_ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

	/**
	 * Convert an integer ID to a slug
	 *
	 * @param int $input The identifier to convert
	 * @return string The slug
	 */
	public static function slug_encode(int $input): string {
		$output = [];
		while ($input > 0) {
			$output[] = self::SLUG_ALPHABET[$input % strlen(self::SLUG_ALPHABET)];
			$input = intdiv($input, strlen(self::SLUG_ALPHABET));
		}

		while (count($output) < 2) {
			$output[] = '0';
		}

		return implode("", array_reverse($output));
	}

	/**
	 * Convert a slug to an integer ID
	 *
	 * @param string $slug The slug to convert
	 * @return int Identifier
	 */
	public static function slug_decode(string $slug): int {
		$output = 0;
		$slug = str_split(strtoupper(trim($slug)));

		$slug_indexes = array_map(function($part) {
			return strpos(self::SLUG_ALPHABET, $part);
		}, array_filter($slug, function($part) {
			return $part !== '0';
		}));

		foreach ($slug_indexes as $index) {
			$output = ($output * strlen(self::SLUG_ALPHABET)) + $index;
		}

		return $output;
	}

	/**
	 * Flatten an array.
	 *
	 * @param mixed[] $data
	 * @param mixed[] $result
	 * @return mixed[]
	 */
	public static function array_flatten($data, array $result = []): array {
		foreach ($data as $flat) {
			if (is_array($flat)) {
				$result = self::array_flatten($flat, $result);
			} else {
				$result[] = $flat;
			}
		}

		return $result;
	}

	/**
	 * Generate a new token
	 *
	 * @return string The generated token
	 */
	public static function generate_token(): string {
		return strtolower(Base32::encode(random_bytes(20)));
	}

	/**
	 * Get an existing user by the SSO external ID, or if none exists,
	 * create a new user.
	 *
	 * @param string $external_id The user's SSO external ID
	 * @param string $email The user's email address
	 * @param bool $allow_create Whether to allow creating a new user
	 * @return ?array<string, mixed> User data
	 */
	public static function user_get_or_create(string $external_id, string $email, bool $allow_create = true): ?array {
		$database = \i8Database::get();

		$userQuery = $database->prepare("SELECT * FROM users WHERE external_id = ?");
		if (($userQueryResult = $userQuery->execute([$external_id])) === true) {
			if (($userData = $userQuery->fetch()) !== false) {
				if (array_key_exists('id', $userData) && intval($userData['id']) > 0) {
					// User exists! Update the email address on the user to the one passed 
					// to this function, if it differs, and then return the user array.

					if ($userData['email'] !== $email) {
						$userUpdateQuery = $database->prepare("UPDATE users SET email = ? WHERE id = ?");
						$userUpdateQuery->execute([$email, $userData['id']]);
						$userData['email'] = $email;
					}

					return $userData;
				}
			}
		}

		// If we get here, no user existed, create a new one
		// First check if we're actually okay to create the user though, and just return otherwise
		if (!$allow_create) {
			return null;
		}

		$userCreateQuery = $database->prepare("INSERT INTO users (external_id, email, apikey) VALUES (?, ?, ?)");
		if ($userCreateQuery->execute([$external_id, $email, self::generate_token()])) {
			// User successfully created, recursively call ourself to retrieve the data
			return self::user_get_or_create($external_id, $email, false);
		}

		// User creation failed, return null
		return null;
	}
	
	/**
	 * Get an existing user by their API key
	 *
	 * @param string $apikey The user's API key
	 * @return ?array<string, mixed> User data, or null
	 */
	public static function user_get_by_apikey(string $apikey): ?array {
		$database = \i8Database::get();

		$userQuery = $database->prepare("SELECT * FROM users WHERE apikey = ?");
		if ($userQuery->execute([$apikey])) {
			if (($userData = $userQuery->fetch()) !== false) {
				return $userData;
			};
		}

		return null;
	}
	
	/**
	 * Get an existing URL entry by the URL, else create a new entry
	 *
	 * @param string $url The URL
	 * @param int $creator_id The ID of the creator of this URL entry
	 * @param bool $allow_create Whether to allow creating a new URL entry
	 * @return ?array<string, mixed> URL entry data
	 */
	public static function url_get_or_create(string $url, int $creator_id = 0, bool $allow_create = true): ?array {
		$database = \i8Database::get();

		$urlQuery = $database->prepare("SELECT * FROM urls WHERE url = ?");
		if (($urlQueryResult = $urlQuery->execute([$url])) !== false) {
			if (($urlData = $urlQuery->fetch()) !== false) {
				return $urlData;
			}
		}

		// If we get here, no URL entry existed, create a new one
		// First check if we're actually okay to create though, otherwise return
		if (!$allow_create) {
			return null;
		}

		$urlCreateQuery = $database->prepare("INSERT INTO urls (url, creator) VALUES (?, ?)");
		if ($urlCreateQuery->execute([$url, $creator_id])) {
			// URL successfully created, recursively call ourself to retrieve the data
			return self::url_get_or_create($url, $creator_id, false);
		}

		// URL creation failed, return null
		return null;
	}

	/**
	 * Get an existing URL by it's ID
	 *
	 * @param int $id The URL entry ID
	 * @return ?array<string, mixed> URL data, or null
	 */
	public static function url_get(int $id): ?array {
		$database = \i8Database::get();

		$urlQuery = $database->prepare("SELECT * FROM urls WHERE id = ?");
		if ($urlQuery->execute([$id]) === true) {
			if (($urlData = $urlQuery->fetch()) !== false) {
				return $urlData;
			}
		}

		return null;
	}
}
