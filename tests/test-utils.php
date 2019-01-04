<?php

use WPLibs\Model\Utils\Utils;

class Utils_Test extends WP_UnitTestCase {
	public function testPostExists() {
		$id = $this->factory->post->create();
		$this->assertTrue( Utils::post_exists( $id ) );
		$this->assertFalse( Utils::post_exists( 1000000000 ) );
	}

	public function testParseId() {
		$pid = $this->factory->post->create_and_get();
		$uid = $this->factory->user->create_and_get();
		$tid = $this->factory->term->create_and_get();

		$this->assertEquals( $pid->ID, Utils::parse_object_id( $pid ) );
		$this->assertEquals( $uid->ID, Utils::parse_object_id( $uid ) );
		$this->assertEquals( $tid->term_id, Utils::parse_object_id( $tid ) );
		$this->assertEquals( 100, Utils::parse_object_id( 100 ) );
		$this->assertEquals( 100, Utils::parse_object_id( '100' ) );
		$this->assertNull( Utils::parse_object_id( '-1' ) );
		$this->assertNull( Utils::parse_object_id( false ) );
	}

	public function testClassUsesRecursiveShouldReturnTraitsOnParentClasses() {
		$this->assertSame( [
			SupportTestTraitTwo::class => SupportTestTraitTwo::class,
			SupportTestTraitOne::class => SupportTestTraitOne::class,
		], Utils::class_uses( SupportTestClassTwo::class ) );
	}

	public function testClassUsesRecursiveAcceptsObject() {
		$this->assertSame( [
			SupportTestTraitTwo::class => SupportTestTraitTwo::class,
			SupportTestTraitOne::class => SupportTestTraitOne::class,
		], Utils::class_uses( new SupportTestClassTwo ) );
	}

	public function testClassUsesRecursiveReturnParentTraitsFirst() {
		$this->assertSame( [
			SupportTestTraitTwo::class   => SupportTestTraitTwo::class,
			SupportTestTraitOne::class   => SupportTestTraitOne::class,
			SupportTestTraitThree::class => SupportTestTraitThree::class,
		], Utils::class_uses( SupportTestClassThree::class ) );
	}
}

trait SupportTestTraitOne {
	//
}

trait SupportTestTraitTwo {
	use SupportTestTraitOne;
}

class SupportTestClassOne {
	use SupportTestTraitTwo;
}

class SupportTestClassTwo extends SupportTestClassOne {
	//
}

trait SupportTestTraitThree {
	//
}

class SupportTestClassThree extends SupportTestClassTwo {
	use SupportTestTraitThree;
}
