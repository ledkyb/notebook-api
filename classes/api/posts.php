<?php

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Not today, bad hombre.' );
}

class Posts {
	function __construct() {
		add_action( 'rest_api_init', [ $this, 'engine_register' ] );
	}


	function prepare_response( array $posts ) {

		$response = [];

		foreach ( $posts as $post ) {
			$tags = [];

			foreach ( wp_get_post_tags( $post->ID ) as $tag ) {
				array_push( $tags, [
					'name' => $tag->name,
					'slug' => $tag->slug,
				] );
			}
			$response[] = [
				'id'      => $post->ID,
				'title'   => $post->post_title,
				'date'    => get_the_date( 'F d, Y', $post->ID ),
				'tags'    => $tags,
				'content' => $post->post_content,
				'excerpt' => apply_filters('the_excerpt', get_post_field('post_excerpt', $post->ID))

			];

		}

		return rest_ensure_response( $response );

	}

	function engine_filter( \WP_REST_Request $request ) {
		$arguments = [
			'post_type'      => 'any',
			'status'         => 'publish',
			'orderby'        => 'date',
			'posts_per_page' => 12,
			'order'          => 'DESC'
		];

		if ( isset( $request['tag'] ) && ! empty( $request['tag'] ) ) {
			$arguments['tag'] = (string) strip_tags( $request['tag'] );
		}

		if ( isset( $request['number'] ) && ! empty( $request['number'] ) ) {
			$arguments['posts_per_page'] = (int) $request['number'];
		}

		if ( isset( $request['id'] ) && ! empty( $request['id'] ) ) {

			$requested_posts = strip_tags( $request['id'] );

			$arguments['p'] = (int) $requested_posts;

			$engine_query = new \WP_Query( $arguments );

			$response = $this->prepare_response( $engine_query->posts );

			return $query_response = rest_ensure_response( $response );
		}

		return $query_response = rest_ensure_response( $this->prepare_response( ( new \WP_Query( $arguments ) )->posts ) );
	}

	function query_args() {
		$args = [];

		$args['id'] = [
			'description' => esc_html( 'Requires a post ID.' ),
			'type'        => 'integer'
		];

		$args['posts_per_page'] = [
			'description' => esc_html( 'Number of posts to return.' ),
			'type'        => 'integer'
		];

		return $args;
	}

	function engine_register() {
		register_rest_route( 'ledkyb/api', '/posts', [
			'methods'  => \WP_REST_Server::READABLE, //  READABLE || EDITABLE || DELETABLE || ALLMETHODS
			'callback' => [
				$this,
				'engine_filter'
			],
			'args'     => $this->query_args(),
		] );
	}
}

new Posts();
