<?php
/**
 * Plugin Name: Npcink Abilities Toolkit Demo
 * Description: Example provider plugin that registers abilities through Npcink Abilities Toolkit.
 * Version: 0.1.0
 * Requires at least: 7.0
 * Requires PHP: 8.0
 * Author: Npcink
 * Text Domain: npcink-abilities-toolkit-demo
 *
 * @package MagickAIAbilitiesDemo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'plugins_loaded',
	static function () {
		if ( ! function_exists( 'magick_ai_abilities_register_readonly' ) ) {
			return;
		}

		if ( function_exists( 'magick_ai_abilities_register_category' ) ) {
			magick_ai_abilities_register_category(
				'acme-demo',
				array(
					'label'       => __( 'ACME Demo Abilities', 'npcink-abilities-toolkit-demo' ),
					'description' => __( 'Example abilities provided by a standalone plugin.', 'npcink-abilities-toolkit-demo' ),
				)
			);
		}

		magick_ai_abilities_register_readonly(
			'acme/content-inventory-summary',
			array(
				'label'          => __( 'Content Inventory Summary', 'npcink-abilities-toolkit-demo' ),
				'description'    => __( 'Returns counts of common public content types for testing agent discovery.', 'npcink-abilities-toolkit-demo' ),
				'category'       => 'acme-demo',
				'capability'     => 'edit_posts',
				'required_scope' => 'cap.content.read',
				'input_schema'   => array(
					'type'                 => 'object',
					'additionalProperties' => false,
				),
				'output_schema'  => array(
					'type'       => 'object',
					'properties' => array(
						'posts' => array( 'type' => 'integer' ),
						'pages' => array( 'type' => 'integer' ),
					),
				),
				'execute_callback' => static function () {
					$posts = wp_count_posts( 'post' );
					$pages = wp_count_posts( 'page' );

					return array(
						'posts' => isset( $posts->publish ) ? (int) $posts->publish : 0,
						'pages' => isset( $pages->publish ) ? (int) $pages->publish : 0,
					);
				},
			)
		);

		magick_ai_abilities_register_readonly(
			'acme/projected-site-summary',
			array(
				'label'                     => __( 'Projected Site Summary', 'npcink-abilities-toolkit-demo' ),
				'description'               => __( 'Shows how a provider plugin explicitly opts into Magick AI compatibility projection.', 'npcink-abilities-toolkit-demo' ),
				'category'                  => 'acme-demo',
				'capability'                => 'manage_options',
				'required_scope'            => 'cap.site.read',
				'project_to_magick_catalog' => true,
				'channels'                  => array( 'abilities_rest', 'mcp' ),
				'input_schema'              => array(
					'type'                 => 'object',
					'additionalProperties' => false,
				),
				'output_schema'             => array(
					'type'       => 'object',
					'properties' => array(
						'name' => array( 'type' => 'string' ),
						'url'  => array( 'type' => 'string' ),
					),
				),
				'execute_callback'          => static function () {
					return array(
						'name' => get_bloginfo( 'name' ),
						'url'  => home_url(),
					);
				},
			)
		);

		magick_ai_abilities_register_write_proposal(
			'acme/create-draft-proposal',
			array(
				'label'            => __( 'Create Draft Proposal', 'npcink-abilities-toolkit-demo' ),
				'description'      => __( 'Builds a draft proposal without creating a post.', 'npcink-abilities-toolkit-demo' ),
				'category'         => 'acme-demo',
				'capability'       => 'edit_posts',
				'required_scope'   => 'cap.content.write',
				'input_schema'     => array(
					'type'       => 'object',
					'properties' => array(
						'title'   => array( 'type' => 'string' ),
						'content' => array( 'type' => 'string' ),
					),
					'required'   => array( 'title' ),
				),
				'output_schema'    => array(
					'type'       => 'object',
					'properties' => array(
						'proposal_id' => array( 'type' => 'string' ),
						'diff'        => array( 'type' => 'object' ),
						'next_actions' => array(
							'type'  => 'array',
							'items' => array( 'type' => 'string' ),
						),
					),
				),
				'execute_callback' => static function ( $input = array() ) {
					$input = is_array( $input ) ? $input : array();
					$title = sanitize_text_field( (string) ( $input['title'] ?? '' ) );

					return array(
						'proposal_id'  => 'demo-' . md5( $title . '|' . wp_rand() ),
						'diff'         => array(
							'post_status' => array(
								'from' => null,
								'to'   => 'draft',
							),
							'post_title'  => array(
								'from' => null,
								'to'   => $title,
							),
						),
						'next_actions' => array( 'approve_in_host', 'reject' ),
					);
				},
			)
		);
	}
);
