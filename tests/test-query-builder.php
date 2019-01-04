<?php

use Awethemes\Database\Database;
use WPLibs\Model\Query\Builder;
use WPLibs\Model\Query\DB_Query;
use WPLibs\Model\Query\Post_Query;
use WPLibs\Model\Query\Query_Vars;
use WPLibs\Model\Query\Term_Query;

class Query_Builder_Test extends WP_UnitTestCase {
	public function setUp() {
		parent::setUp();
	}

	public function testPostQueryBuilder() {
		// Test basic query builder.
		$builder = new Builder( new Post_Query() );

		$vars = $builder->get_query()->get_query_vars();
		$this->assertInstanceOf( Query_Vars::class, $vars );

		$builder->select('ID')->take( 10 )->skip( 5 )->orderby('ID', 'ASC');
		$this->assertEquals( 'ID', $vars['fields'] );
		$this->assertEquals( 10, $vars['posts_per_page'] );
		$this->assertEquals( 5, $vars['offset'] );
		$this->assertEquals( 'ASC', $vars['order'] );
		$this->assertEquals( 'ID', $vars['orderby'] );

		// Test fluent builder
		$builder = new Builder( new Post_Query( [ 'suppress_filters' => true ]) );
		$builder
			->include( [ 1, 2, 3 ] )
			->exclude( 4 )
			->parent( 100 )
			->ignore_sticky_posts()
			->no_found_rows( false )
			->with( [ 'author' => 19 ] );

		$vars = $builder->get_query()->get_query_vars();
		$this->assertTrue( $vars['suppress_filters'] );
		$this->assertEquals( [1, 2, 3], $vars['post__in'] );
		$this->assertEquals( 4, $vars['post__not_in'] );
		$this->assertEquals( 100, $vars['post_parent'] );
		$this->assertFalse( $vars['no_found_rows'] );
		$this->assertTrue( $vars['ignore_sticky_posts'] );
		$this->assertEquals( 19, $vars['author'] );
	}

	public function testTermQueryBuilder() {
		// Test basic query builder.
		$builder = new Builder( new Term_Query() );

		$vars = $builder->get_query()->get_query_vars();
		$this->assertInstanceOf( Query_Vars::class, $vars );

		$builder->select('term_id')->take( 10 )->skip( 5 )->orderby('term_id', 'ASC');
		$this->assertEquals( 'term_id', $vars['fields'] );
		$this->assertEquals( 10, $vars['number'] );
		$this->assertEquals( 5, $vars['offset'] );
		$this->assertEquals( 'ASC', $vars['order'] );
		$this->assertEquals( 'term_id', $vars['orderby'] );
	}

	public function testDBQueryBuilder() {
		// Test basic query builder.
		$builder = new Builder( new DB_Query( Database::table( 'posts' ) ) );
		$builder->select( 'ID' )->take( 10 )->skip( 5 )->orderby( 'ID', 'ASC' );

		$vars = $builder->get_query()->get_query_vars();
		$this->assertInstanceOf( \Awethemes\Database\Builder::class, $vars );
		$this->assertEquals( "select `ID` from `wptests_posts` order by `ID` asc limit 10 offset 5", $vars->toSql() );

		//
		$builder = new Builder( new DB_Query( Database::table( 'users' ) ) );
		$builder->where( 'ID', '>=', 100 )->orWhere( 'ID', '<=', 10 );

		$vars = $builder->get_query()->get_query_vars();
		$this->assertEquals( "select * from `wptests_users` where `ID` >= %d or `ID` <= %d", $vars->toSql() );
		$this->assertEquals( [ 100, 10 ], $vars->getBindings() );
	}

	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function testDBQueryBuilderException() {
		$builder = new Builder( new DB_Query( Database::table( 'posts' ) ) );
		$builder->post__in();
	}

	public function testClone() {
		$builder = new Builder( $query = new Post_Query() );
		$this->assertSame( $query, $builder->get_query() );

		$clone = clone $builder;
		$this->assertNotSame( $query, $clone->get_query() );
	}

	/**
	 * @expectedException \RuntimeException
	 */
	public function testWithoutModel() {
		$builder = new Builder( new Post_Query() );
		$builder->find( 100 );
	}
}
