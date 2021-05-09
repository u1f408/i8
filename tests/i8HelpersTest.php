<?php
use PHPUnit\Framework\TestCase;

final class i8HelpersTest extends TestCase {
	public function test_slug_encode() {
		$this->assertEquals(i8Helpers::slug_encode(1), "0B");
		$this->assertEquals(i8Helpers::slug_encode(999), "7H");
		$this->assertEquals(i8Helpers::slug_encode(9393939), "I6VYT");
		$this->assertEquals(i8Helpers::slug_encode(6942069420), "GO4PEVM");
	}

	public function test_slug_decode() {
		$this->assertEquals(i8Helpers::slug_decode("0B"), 1);
		$this->assertEquals(i8Helpers::slug_decode("7H"), 999);
		$this->assertEquals(i8Helpers::slug_decode("I6VYT"), 9393939);
		$this->assertEquals(i8Helpers::slug_decode("GO4PEVM"), 6942069420);
	}
}
