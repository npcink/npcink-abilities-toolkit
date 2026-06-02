<?php
/**
 * Ability registrar.
 *
 * @package MagickAIAbilities
 */

namespace Magick_AI_Abilities\Registry;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Stores and registers Abilities API abilities.
 */
final class Ability_Registrar {
	/**
	 * Category registrar.
	 *
	 * @var Category_Registrar
	 */
	private $categories;

	/**
	 * Contract normalizer.
	 *
	 * @var Contract_Normalizer
	 */
	private $contract_normalizer;

	/**
	 * Ability definitions.
	 *
	 * @var array<string,array<string,mixed>>
	 */
	private $abilities = array();

	/**
	 * Constructor.
	 *
	 * @param Category_Registrar  $categories Category registrar.
	 * @param Contract_Normalizer $contract_normalizer Contract normalizer.
	 */
	public function __construct( Category_Registrar $categories, Contract_Normalizer $contract_normalizer ) {
		$this->categories           = $categories;
		$this->contract_normalizer = $contract_normalizer;
	}

	/**
	 * Registers hooks.
	 *
	 * @return void
	 */
	public function boot() {
		add_action( 'wp_abilities_api_init', array( $this, 'register_with_wordpress' ), 10 );
	}

	/**
	 * Adds a read-only ability.
	 *
	 * @param string              $ability_id Ability id.
	 * @param array<string,mixed> $definition Ability definition.
	 * @return bool
	 */
	public function add_readonly( $ability_id, array $definition ) {
		return $this->add( $ability_id, $definition, 'readonly' );
	}

	/**
	 * Adds a write proposal ability.
	 *
	 * @param string              $ability_id Ability id.
	 * @param array<string,mixed> $definition Ability definition.
	 * @return bool
	 */
	public function add_write_proposal( $ability_id, array $definition ) {
		return $this->add( $ability_id, $definition, 'write_proposal' );
	}

	/**
	 * Adds a host-governed write ability.
	 *
	 * Host-governed write abilities may expose dry-run previews directly, but
	 * commits must be approved by a host plugin through the package commit filter.
	 *
	 * @param string              $ability_id Ability id.
	 * @param array<string,mixed> $definition Ability definition.
	 * @return bool
	 */
	public function add_write_host_governed( $ability_id, array $definition ) {
		return $this->add( $ability_id, $definition, 'write_host' );
	}

	/**
	 * Adds a host-governed destructive ability.
	 *
	 * @param string              $ability_id Ability id.
	 * @param array<string,mixed> $definition Ability definition.
	 * @return bool
	 */
	public function add_destructive_host_governed( $ability_id, array $definition ) {
		return $this->add( $ability_id, $definition, 'destructive_host' );
	}

	/**
	 * Returns all normalized ability definitions.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public function all() {
		return $this->abilities;
	}

	/**
	 * Registers queued abilities with WordPress.
	 *
	 * @return void
	 */
	public function register_with_wordpress() {
		if ( ! function_exists( 'wp_register_ability' ) ) {
			return;
		}

		foreach ( $this->abilities as $ability_id => $definition ) {
			$this->register_single_with_wordpress( $ability_id, $definition );
		}
	}

	/**
	 * Adds an ability definition.
	 *
	 * @param string              $ability_id Ability id.
	 * @param array<string,mixed> $definition Ability definition.
	 * @param string              $mode Registration mode.
	 * @return bool
	 */
	private function add( $ability_id, array $definition, $mode ) {
		$normalized = $this->contract_normalizer->normalize( $ability_id, $definition, $mode );
		$ability_id = $normalized['ability_id'];

		if ( '' === $ability_id || false === strpos( $ability_id, '/' ) ) {
			return false;
		}

		$this->abilities[ $ability_id ] = $normalized;

		$this->emit_observability_event(
			'abilities.ability.registered',
			array(
				'ability_id' => $ability_id,
				'mode'       => $mode,
				'status'     => 'ok',
			)
		);

		if (
			function_exists( 'wp_register_ability' )
			&& (
				( function_exists( 'doing_action' ) && doing_action( 'wp_abilities_api_init' ) )
				|| ( function_exists( 'did_action' ) && did_action( 'wp_abilities_api_init' ) > 0 )
			)
		) {
			$this->register_single_with_wordpress( $ability_id, $normalized );
		}

		return true;
	}

	/**
	 * Registers one normalized ability with WordPress.
	 *
	 * @param string              $ability_id Ability id.
	 * @param array<string,mixed> $definition Normalized ability definition.
	 * @return void
	 */
	private function register_single_with_wordpress( $ability_id, array $definition ) {
		if ( ! function_exists( 'wp_register_ability' ) ) {
			return;
		}

		if ( function_exists( 'wp_has_ability' ) && wp_has_ability( $ability_id ) ) {
			return;
		}

		$category = isset( $definition['category'] ) ? sanitize_key( (string) $definition['category'] ) : '';
		if ( '' !== $category && ! isset( $this->categories->all()[ $category ] ) ) {
			$this->categories->add(
				$category,
				array(
					'label'       => $category,
					'description' => '',
				)
			);
		}

		wp_register_ability(
			$ability_id,
			array(
				'label'               => $definition['label'],
				'description'         => $definition['description'],
				'category'            => $definition['category'],
				'input_schema'        => $definition['input_schema'],
				'output_schema'       => $definition['output_schema'],
				'execute_callback'    => $this->observed_execute_callback( $ability_id, $definition ),
				'permission_callback' => $definition['permission_callback'],
				'meta'                => $definition['meta'],
			)
		);

		$this->emit_observability_event(
			'abilities.ability.wordpress_registered',
			array(
				'ability_id' => $ability_id,
				'mode'       => (string) ( $definition['mode'] ?? '' ),
				'status'     => 'ok',
			)
		);
	}

	/**
	 * Wraps ability execution with metadata-only local observability.
	 *
	 * @param string              $ability_id Ability id.
	 * @param array<string,mixed> $definition Normalized ability definition.
	 * @return callable
	 */
	private function observed_execute_callback( $ability_id, array $definition ) {
		$callback = $definition['execute_callback'];
		$mode     = (string) ( $definition['mode'] ?? '' );

		return function ( ...$args ) use ( $ability_id, $callback, $mode ) {
			$started = microtime( true );

			try {
				$result  = call_user_func_array( $callback, $args );
				$is_error = function_exists( 'is_wp_error' ) && is_wp_error( $result );
				$this->emit_observability_event(
					'abilities.callback.completed',
					array(
						'ability_id' => $ability_id,
						'mode'       => $mode,
						'status'     => $is_error ? 'error' : 'ok',
						'error_code' => $is_error ? (string) $result->get_error_code() : '',
						'latency_ms' => $this->elapsed_ms( $started ),
					)
				);

				return $result;
			} catch ( \Throwable $exception ) {
				$this->emit_observability_event(
					'abilities.callback.completed',
					array(
						'ability_id' => $ability_id,
						'mode'       => $mode,
						'status'     => 'error',
						'error_code' => get_class( $exception ),
						'latency_ms' => $this->elapsed_ms( $started ),
					)
				);

				throw $exception;
			}
		};
	}

	/**
	 * Emits a local observability event when the public helper is available.
	 *
	 * @param string              $event_kind Event kind.
	 * @param array<string,mixed> $payload Event details.
	 * @return void
	 */
	private function emit_observability_event( $event_kind, array $payload ) {
		if ( ! function_exists( 'magick_ai_abilities_emit_observability_event' ) ) {
			return;
		}

		magick_ai_abilities_emit_observability_event(
			array_merge(
				array(
					'event_kind' => $event_kind,
				),
				$payload
			)
		);
	}

	/**
	 * Returns elapsed milliseconds.
	 *
	 * @param float $started Start time.
	 * @return int
	 */
	private function elapsed_ms( $started ) {
		return max( 0, (int) round( ( microtime( true ) - $started ) * 1000 ) );
	}
}
