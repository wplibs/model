<?php

use Awethemes\Database\Database;
use WPLibs\Model\Query\DB_Query;
use WPLibs\Model\Query\Post_Query;
use WPLibs\Model\Query\Query;
use WPLibs\Model\Query\Term_Query;

class Query_Test extends WP_UnitTestCase {
	public function setUp() {
		parent::setUp();
	}

	public function testPostQuery() {
		$query = new Post_Query( [ 'post_type' => 'post' ] );
		$query->set_primary_key( 'ID' );
		$query->set_object_type( 'post' );

		$this->assertInstanceOf( Query::class, $query );
		$this->assertInstanceOf( \WPLibs\Model\Query\Query_Vars::class, $query->get_query_vars() );
		$this->assertInstanceOf( \WP_Query::class, $query->do_query( [] ) );

		$this->assertQueryActionsWork( $query );
	}

	public function testDBQuery() {
		$query = new DB_Query( Database::table( 'posts' ) );
		$query->set_table( 'posts' );
		$query->set_primary_key( 'ID' );

		$this->assertInstanceOf( Query::class, $query );
		$this->assertInstanceOf( \Awethemes\Database\Builder::class, $query->get_query_vars() );
		$this->assertInternalType( 'array', $query->do_query( $query->get_query_vars() ) );

		$this->assertQueryActionsWork( $query );
	}

	protected function assertQueryActionsWork( Query $query ) {
		// Find by ID.
		$p1 = $this->factory->post->create();

		$g1 = $query->get_by_id( $p1 );
		$this->assertArrayHasKey( 'ID', $g1 );
		$this->assertEquals( $p1, $g1['ID'] );

		// Actions.
		$insert_id = $query->insert( [ 'post_type' => 'abc' ] );
		clean_post_cache( $insert_id );
		$this->assertEquals( 'abc', get_post_type( $insert_id ) );

		$updated = $query->update( $insert_id, [ 'post_type' => 'ddd' ] );
		clean_post_cache( $insert_id );
		$this->assertNotFalse( $updated );
		$this->assertGreaterThan( 0, $updated );
		$this->assertEquals( 'ddd', get_post_type( $insert_id ) );

		if ( $query instanceof \WPLibs\Model\Post ) {
			$query->delete( $insert_id, false );
			clean_post_cache( $insert_id );
			$this->assertEquals( 'trash', get_post_status( $insert_id ) );
		}

		$query->delete( $insert_id, true );
		clean_post_cache( $insert_id );
		$this->assertFalse( get_post_status( $insert_id ) );
	}

	public function testTermQuery() {
		$query = new Term_Query();
		$query->set_primary_key( 'term_id' );
		$query->set_object_type( 'category' );

		$this->assertInstanceOf( Query::class, $query );
		$this->assertInstanceOf( \WPLibs\Model\Query\Query_Vars::class, $query->get_query_vars() );
		$this->assertInstanceOf( \WP_Term_Query::class, $query->do_query( [] ) );

		// Find by ID.
		$p1 = $this->factory->term->create( [
			'name'     => 'category1',
			'taxonomy' => 'category',
		]);

		$g1 = $query->get_by_id( $p1 );
		$this->assertArrayHasKey( 'term_id', $g1 );
		$this->assertEquals( $p1, $g1['term_id'] );

		// Actions.
		$insert_id = $query->insert( [ 'name' => 'category2' ] );
		clean_term_cache( $insert_id );
		$this->assertEquals( 'category2', get_term_field( 'name', $insert_id, 'category' ) );

		$updated = $query->update( $insert_id, [ 'name' => 'ddd' ] );
		clean_term_cache( $insert_id );
		$this->assertNotFalse( $updated );
		$this->assertGreaterThan( 0, $updated );
		$this->assertEquals( 'ddd', get_term_field( 'name', $insert_id, 'category' ) );

		$query->delete( $insert_id, false );
		clean_term_cache( $insert_id );
		$this->assertNull( get_term( $insert_id ) );
	}
}
