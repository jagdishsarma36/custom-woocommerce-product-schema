<?php
/**
 * Plugin Name: Custom WooCommerce Product Schema
 * Plugin URI: https://jagdish.info
 * Description: Adds Product, Offer, AggregateRating and Review schema to WooCommerce products without requiring Yoast WooCommerce SEO.
 * Version: 1.1.0
 * Author: Jagdish Sarma
 * License: GPL2+
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CWPS_Product_Schema {

	public function __construct() {
		add_action( 'wp_head', [ $this, 'output_schema' ], 99 );
	}

	public function output_schema() {

		if ( ! function_exists( 'is_product' ) || ! is_product() ) {
			return;
		}

		global $product;

		if ( ! $product instanceof WC_Product ) {
			return;
		}

		$product_id = $product->get_id();

		$image = '';

		if ( $product->get_image_id() ) {
			$image = wp_get_attachment_url( $product->get_image_id() );
		}

		$schema = [
			'@context'    => 'https://schema.org',
			'@type'       => 'Product',
			'@id'         => get_permalink( $product_id ) . '#product',
			'name'        => wp_strip_all_tags( $product->get_name() ),
			'description' => wp_strip_all_tags(
				$product->get_short_description()
					? $product->get_short_description()
					: $product->get_description()
			),
			'sku'         => $product->get_sku(),
			'url'         => get_permalink( $product_id ),
		];

		/**
		 * Product Image
		 */
		if ( ! empty( $image ) ) {
			$schema['image'] = [ $image ];
		}

		/**
		 * Brand
		 */
		$schema['brand'] = [
			'@type' => 'Brand',
			'name'  => 'SMI Cold Therapy',
		];

		/**
		 * Offer
		 */
		if ( $product->get_price() !== '' ) {

			$schema['offers'] = [
				'@type'         => 'Offer',
				'url'           => get_permalink( $product_id ),
				'priceCurrency' => get_woocommerce_currency(),
				'price'         => $product->get_price(),
				'availability'  => $product->is_in_stock()
					? 'https://schema.org/InStock'
					: 'https://schema.org/OutOfStock',
				'itemCondition' => 'https://schema.org/NewCondition',
			];
		}

		/**
		 * Static Reviews
		 */
		$reviews = [];

		$reviews[] = [
			'@type' => 'Review',
			'author' => [
				'@type' => 'Person',
				'name'  => 'John Charles Herzberg',
			],
			'name' => 'Excellent Cold Therapy Wrap',
			'reviewRating' => [
				'@type'       => 'Rating',
				'ratingValue' => '5',
				'bestRating'  => '5',
			],
			'reviewBody' => 'I purchased the SMI Cold Therapy Knee Wrap along with four cooling packs, and it has been outstanding. The wrap fits securely, delivers consistent cold therapy, and the extra packs make it easy to rotate without waiting for refreezing. It is well made, comfortable, and genuinely helps reduce swelling and pain. Highly recommended.',
		];

		$reviews[] = [
			'@type' => 'Review',
			'author' => [
				'@type' => 'Person',
				'name'  => 'Charles MEADOWS',
			],
			'name' => 'Great Product',
			'reviewRating' => [
				'@type'       => 'Rating',
				'ratingValue' => '5',
				'bestRating'  => '5',
			],
			'reviewBody' => 'Works perfectly. We use the gel bags regularly and find they meet our expectations. The medical community is moving toward utilizing cold therapy in lieu of medications to control and relieve pain. Customer service was very courteous when corresponding. Thumbs up.',
		];

		$reviews[] = [
			'@type' => 'Review',
			'author' => [
				'@type' => 'Person',
				'name'  => 'Kenneth Rech',
			],
			'name' => 'Very Nice Product',
			'reviewRating' => [
				'@type'       => 'Rating',
				'ratingValue' => '5',
				'bestRating'  => '5',
			],
			'reviewBody' => 'My wife received her first knee wrap from the hospital which was SMI Cold Therapy. During her recovery I purchased another knee wrap with several additional ice packs. The quality of this product is much better than any other we have tried. The ice packs last more than three hours and refreeze very firm and cold.',
		];

		$reviews[] = [
			'@type' => 'Review',
			'author' => [
				'@type' => 'Person',
				'name'  => 'Wilson Hutton',
			],
			'name' => 'Exactly What I Was Looking For',
			'reviewRating' => [
				'@type'       => 'Rating',
				'ratingValue' => '5',
				'bestRating'  => '5',
			],
			'reviewBody' => 'I wanted to replace a previous wrap that had become worn with something that would take the same size cold packs. It was great to find a slightly updated version of the exact same product. A rare thing in today\'s market.',
		];

		$schema['review'] = $reviews;

		/**
		 * Aggregate Rating
		 *
		 * If BayReviews has a higher review count, use it.
		 * Otherwise use the static review count.
		 */
		$static_review_count = count( $reviews );
		$static_rating_value = 5;

		$bay_review_count = (int) get_post_meta(
			$product_id,
			'bayreviews_review_count',
			true
		);

		$bay_rating_value = (float) get_post_meta(
			$product_id,
			'bayreviews_review_average',
			true
		);

		$final_review_count = max(
			$bay_review_count,
			$static_review_count
		);

		$final_rating_value = $bay_rating_value > 0
			? $bay_rating_value
			: $static_rating_value;

		$schema['aggregateRating'] = [
			'@type'       => 'AggregateRating',
			'ratingValue' => $final_rating_value,
			'reviewCount' => $final_review_count,
			'bestRating'  => '5',
			'worstRating' => '1',
		];

		echo '<script type="application/ld+json">';
		echo wp_json_encode(
			$schema,
			JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
		);
		echo '</script>';
	}
}

new CWPS_Product_Schema();