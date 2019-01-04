<?php

use WPLibs\Model\Query\Query_Vars;

class Query_Vars_Test extends WP_UnitTestCase {
	public function setUp() {
		parent::setUp();
	}

	public function testFluent() {
		$query = new Query_Vars( [ 'post_type' => 'post' ] );
		$this->assertInstanceOf( \ArrayAccess::class, $query);

		$query->post__in     = 1;
		$query->post__not_in = 2;

		$this->assertArrayHasKey( 'post__in', $query->get_query_vars() );
		$this->assertArrayHasKey( 'post_type', $query->get_query_vars() );
		$this->assertArrayHasKey( 'post__not_in', $query->get_query_vars() );

		$this->assertEquals( 1, $query->post__in );
		$this->assertEquals( 2, $query['post__not_in'] );
	}

	public function testFluentCallFunction() {
		$query = new Query_Vars( [ 'post_type' => 'post' ] );

		$query
			->post__in( 1 )
			->post__not_in( 2 )
			->suppress_filters();

		$this->assertArrayHasKey( 'post__in', $query->get_query_vars() );
		$this->assertArrayHasKey( 'post__not_in', $query->get_query_vars() );
		$this->assertArrayHasKey( 'suppress_filters', $query->get_query_vars() );

		$this->assertEquals( 1, $query->post__in );
		$this->assertEquals( 2, $query['post__not_in'] );
		$this->assertSame( true, $query['suppress_filters'] );
	}
}
