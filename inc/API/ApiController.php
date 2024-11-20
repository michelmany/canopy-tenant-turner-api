<?php

namespace Inc\API;

use GuzzleHttp\Exception\GuzzleException;
use WP_Error;
use WP_Post;
use WP_Query;
use GuzzleHttp\Client;

class ApiController {

	public const POST_TYPE = 'listing';
	public const BASE_URI = 'https://app.tenantturner.com';
	public const FETCH_LIMIT = 100;

	public function __construct() {
	}

	/**
	 * @return void
	 */
	public function register(): void {
		add_action( 'wp_ajax_process_location_post', array( $this, 'ajaxProcessLocationPost' ) );
	}

	/**
	 *
	 * @return void
	 * @throws GuzzleException
	 */
	public function processLocationPost(): void {
		$entities = get_transient( 'entities_to_process' );
		if ( ! $entities ) {
			$entities = $this->all();
			set_transient( 'entities_to_process', $entities, 60 * 60 ); // Store entities in a transient for 1 hour
		}

		$this->processEntitiesBatch( $entities );
		$this->cleanRemovedPostsFromApi( $this->all() );
	}

	/**
	 * Process entities in batches.
	 *
	 * @param  array  $entities
	 *
	 * @return void
	 */
	private function processEntitiesBatch( array &$entities ): void {
		$processed = 0;

		foreach ( $entities as $index => $entity ) {
			if ( $processed >= 10 ) {
				break;
			}

			$postExists = $this->getPostBySlug( $entity->id, self::POST_TYPE );
			if ( $postExists ) {
				$this->updateEntity( $postExists, $entity, self::POST_TYPE );
			} else {
				$this->createEntity( $entity, self::POST_TYPE );
			}

			unset( $entities[ $index ] );
			$processed ++;
		}

		set_transient( 'entities_to_process', $entities, 60 * 60 ); // Update the transient
	}


	/**
	 * Get all Listings from Tenant Turner API.
	 *
	 * @return array
	 * @throws GuzzleException
	 */
	public function all(): array {
		$client = new Client( [ 'base_uri' => self::BASE_URI ] );
		$page = 1;
		$loadedEntities = [];

		do {
			$listings = $client->request(
				'GET',
				'/listings/customer/20544',
				[
					'form_params' => [
						'per_page' => self::FETCH_LIMIT,
						'page'     => $page,
					],
				]
			);

			$page ++;

			$resultJson = json_decode( $listings->getBody()->getContents(), false );

			$loadedEntities[] = $resultJson;
		} while ( count( $resultJson ) > count( array_merge( ...$loadedEntities ) ) );

		return array_merge( ...$loadedEntities );
	}

	/**
	 * @param $id
	 * @param $postType
	 *
	 * @return false|int|WP_Post
	 */
	protected function getPostBySlug( $id, $postType = null ): bool|int|WP_Post {
		if ( ! $postType ) {
			$postType = self::POST_TYPE;
		}

		$posts = get_posts(
			[
				'name'           => strtolower( $id ),
				'post_type'      => $postType,
				'posts_per_page' => 1,
				'post_status'    => array( 'publish', 'draft' ),
			]
		);

		if ( ! empty( $posts ) ) {
			return $posts[0];
		}

		return false;
	}

	/**
	 * @param $wpEntityPost
	 * @param $entity
	 * @param $postType
	 *
	 * @return void
	 */
	public function updateEntity( $wpEntityPost, $entity, $postType = null ): void {
		if ( ! $postType ) {
			$postType = self::POST_TYPE;
		}

		$updateArray = [
			'ID'          => $wpEntityPost->ID,
			'post_type'   => $postType,
			'post_status' => 'publish',
			'post_title'  => esc_html( $entity->title ),
		];

		wp_update_post( $updateArray );

		// Prepare address field
		$address = [
			'address' => "{$entity->address}, {$entity->city}, {$entity->state} {$entity->zip}",
			'lat'     => $entity->latitude,
			'lng'     => $entity->longitude,
			'zoom'    => 16,
		];

		// Upload images and get their attachment IDs
		$imageIds = [];
		foreach ( $entity->photos as $photoUrl ) {
			$imageId = $this->uploadImage( $photoUrl );
			if ( $imageId ) {
				$imageIds[] = $imageId;
			}
		}

		// Update ACF fields
		update_field( 'rent', $entity->rentAmount, $wpEntityPost->ID );
		update_field( 'deposit', $entity->depositAmount, $wpEntityPost->ID );
		update_field( 'bedrooms', $entity->beds, $wpEntityPost->ID );
		update_field( 'bathrooms', $entity->baths, $wpEntityPost->ID );
		update_field( 'square_footage', $entity->squareFootage, $wpEntityPost->ID );
		update_field( 'address', $address, $wpEntityPost->ID );
		update_field( 'image_gallery', $imageIds, $wpEntityPost->ID );
		update_field( 'description', $entity->description, $wpEntityPost->ID );
		update_field( 'schedule_viewing_url', $entity->btnUrl, $wpEntityPost->ID );
		update_field( 'submit_an_application_url', $entity->applyUrl, $wpEntityPost->ID );
		update_field( 'restrictions', '', $wpEntityPost->ID ); // Empty field
	}

	/**
	 * @param $entity
	 * @param $postType
	 *
	 * @return int|WP_Error
	 */
	public function createEntity( $entity, $postType = null ): WP_Error|int {
		if ( ! $postType ) {
			$postType = self::POST_TYPE;
		}

		$postId = wp_insert_post( [
			'post_type'   => $postType,
			'post_status' => 'publish',
			'post_title'  => esc_html( $entity->title ),
			'post_name'   => $entity->id,
			'post_author' => 1,
		] );

		if ( is_wp_error( $postId ) ) {
			return $postId;
		}

		// Prepare address field
		$address = [
			'address' => "{$entity->address}, {$entity->city}, {$entity->state} {$entity->zip}",
			'lat'     => $entity->latitude,
			'lng'     => $entity->longitude,
			'zoom'    => 16,
		];

		// Upload images and get their attachment IDs
		$imageIds = [];
		foreach ( $entity->photos as $photoUrl ) {
			$imageId = $this->uploadImage( $photoUrl );
			if ( $imageId ) {
				$imageIds[] = $imageId;
			}
		}

		// Update ACF fields
		update_field( 'rent', $entity->rentAmount, $postId );
		update_field( 'deposit', $entity->depositAmount, $postId );
		update_field( 'bedrooms', $entity->beds, $postId );
		update_field( 'bathrooms', $entity->baths, $postId );
		update_field( 'square_footage', $entity->squareFootage, $postId );
		update_field( 'address', $address, $postId );
		update_field( 'image_gallery', $imageIds, $postId );
		update_field( 'description', $entity->description, $postId );
		update_field( 'schedule_viewing_url', $entity->btnUrl, $postId );
		update_field( 'submit_an_application_url', $entity->applyUrl, $postId );
		update_field( 'restrictions', '', $postId ); // Empty field

		return $postId;
	}

	/**
	 * Upload an image from a URL and return the attachment ID.
	 *
	 * @param  string  $imageUrl
	 *
	 * @return int|false
	 */
	private function uploadImage( string $imageUrl ): bool|int {
		$uploadDir = wp_upload_dir();
		$imageData = file_get_contents( $imageUrl );
		$filename = basename( $imageUrl );
		if ( wp_mkdir_p( $uploadDir['path'] ) ) {
			$file = $uploadDir['path'] . '/' . $filename;
		} else {
			$file = $uploadDir['basedir'] . '/' . $filename;
		}
		file_put_contents( $file, $imageData );

		$wpFileType = wp_check_filetype( $filename, null );
		$attachment = [
			'post_mime_type' => $wpFileType['type'],
			'post_title'     => sanitize_file_name( $filename ),
			'post_content'   => '',
			'post_status'    => 'inherit',
		];
		$attachId = wp_insert_attachment( $attachment, $file );
		require_once( ABSPATH . 'wp-admin/includes/image.php' );
		$attachData = wp_generate_attachment_metadata( $attachId, $file );
		wp_update_attachment_metadata( $attachId, $attachData );

		return $attachId;
	}

	/**
	 * This sets removed posts from the API to draft.
	 * The entity IDs are compared to the post names (post slug = ID).
	 *
	 * @param $entities
	 *
	 * @return void
	 */
	public function cleanRemovedPostsFromApi( $entities ): void {
		// Extract post IDs from API entities
		$postIDs = [];
		foreach ( $entities as $entity ) {
			$postIDs[] = strtolower( $entity->id ); // Ensure case consistency
		}

		//ray( $postIDs )->green();

		// Query for all posts of the post type
		$allPostsArgs = [
			'post_type'      => self::POST_TYPE,
			'posts_per_page' => - 1,
			'fields'         => 'ids', // Fetch only IDs for efficiency
		];

		$allPostsQuery = new WP_Query( $allPostsArgs );
		$allPosts = $allPostsQuery->get_posts();

		$allPostNames = [];
		foreach ( $allPosts as $postId ) {
			$post = get_post( $postId );
			if ( $post ) {
				$allPostNames[] = $post->post_name; // Use post_name for comparison
			}
		}

		//ray( $allPostNames )->orange();

		// Determine posts that should be set to draft
		$postsNotIn = array_diff( $allPostNames, $postIDs );

		// Set posts not in the API to draft
		foreach ( $postsNotIn as $postName ) {
			$post = get_page_by_path( $postName, OBJECT, self::POST_TYPE );
			if ( $post && ! empty( $post->ID ) ) {
				wp_update_post( [
					'ID'          => $post->ID, // Update the post by its ID
					'post_status' => 'draft',
				] );

				//ray( $post->post_name )->purple();
			}
		}
	}
}
