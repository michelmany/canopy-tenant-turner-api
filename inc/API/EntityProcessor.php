<?php

namespace Inc\API;

abstract class EntityProcessor {
	protected function updateFields( $wpEntityPost, $entity ): void {
		update_field( 'rent', $entity->rentAmount, $wpEntityPost->ID );
		update_field( 'deposit', $entity->depositAmount, $wpEntityPost->ID );
		update_field( 'bedrooms', $entity->beds, $wpEntityPost->ID );
		update_field( 'bathrooms', $entity->baths, $wpEntityPost->ID );
		update_field( 'square_footage', $entity->squareFootage, $wpEntityPost->ID );
		update_field( 'address', $this->prepareAddress( $entity ), $wpEntityPost->ID );
		update_field( 'description', $entity->description, $wpEntityPost->ID );
		update_field( 'schedule_viewing_url', $entity->btnUrl, $wpEntityPost->ID );
		update_field( 'submit_an_application_url', $entity->applyUrl, $wpEntityPost->ID );
		update_field( 'restrictions', '', $wpEntityPost->ID ); // Empty field

		// Set the first photo as the featured image
		if ( ! empty( $entity->photos ) ) {
			$featuredImageId = $this->uploadImage( $entity->photos[0] );
			if ( $featuredImageId ) {
				set_post_thumbnail( $wpEntityPost->ID, $featuredImageId );
			}

			// Upload the rest of the images
			$imageIds = $this->uploadImages( array_slice( $entity->photos, 1 ) );
			update_field( 'image_gallery', $imageIds, $wpEntityPost->ID );
		}
	}

	protected function prepareAddress( $entity ): array {
		return [
			'address' => "{$entity->address}, {$entity->city}, {$entity->state} {$entity->zip}",
			'lat'     => $entity->latitude,
			'lng'     => $entity->longitude,
			'zoom'    => 16,
		];
	}

	protected function uploadImages( array $photos ): array {
		$imageIds = [];
		foreach ( $photos as $photoUrl ) {
			$imageId = $this->uploadImage( $photoUrl );
			if ( $imageId ) {
				$imageIds[] = $imageId;
			}
		}

		return $imageIds;
	}

	abstract protected function uploadImage( string $imageUrl ): bool|int;
}