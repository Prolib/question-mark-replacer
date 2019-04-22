<?php

use Codeception\AssertThrows;
use ProLib\InvalidArgumentsException;
use ProLib\QuestionMarkReplacer;

class QuestionMarkReplacerTest extends \Codeception\Test\Unit {

	use AssertThrows;

	// tests
	public function testBasic() {
		$this->assertSame('foo(bar, foo)', QuestionMarkReplacer::replace('foo(?, ?)', ['bar', 'foo']));
		$this->assertSame('foo', QuestionMarkReplacer::replace('foo', []));
	}

	public function testBadArgumentCount() {
		$this->assertThrows(InvalidArgumentsException::class, function () {
			QuestionMarkReplacer::replace('foo(?)', []);
		});
	}

	public function testBackSlashes() {
		$this->assertSame('?', QuestionMarkReplacer::replace('\?', []));
		$this->assertSame('\foo', QuestionMarkReplacer::replace('\\\\?', ['foo']));
	}
}