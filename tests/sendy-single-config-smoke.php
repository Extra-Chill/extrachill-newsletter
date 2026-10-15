<?php
/**
 * Smoke coverage for the single Sendy configuration and push error surfacing.
 *
 * - Push to Sendy returns the provider's real error (with a REST status)
 *   instead of a generic success:false.
 * - Sendy credentials live only in Data Machine Business: settings read and
 *   write through it, the key is never returned, and legacy copies migrate.
 * - Subscribe/metrics calls no longer pass a config payload.
 *
 * Run with: php tests/sendy-single-config-smoke.php
 */

namespace DataMachineBusiness\Abilities\Sendy {
	class SendyAbilities {
		public static $config = array();
		public static $reject = false;

		public static function get_campaign_config(): array {
			return self::$config;
		}

		public static function has_api_config(): bool {
			return ! empty( self::$config['api_key'] ) && ! empty( self::$config['sendy_url'] );
		}

		public static function update_api_config( string $api_key, string $sendy_url ) {
			if ( self::$reject || '' === $api_key || '' === $sendy_url ) {
				return new \WP_Error( 'sendy_config_invalid', 'The Sendy URL is invalid.' );
			}
			self::$config['api_key']   = $api_key;
			self::$config['sendy_url'] = rtrim( $sendy_url, '/' );
			return true;
		}
	}
}

namespace {
	use DataMachineBusiness\Abilities\Sendy\SendyAbilities;

	define( 'ABSPATH', __DIR__ );
	define( 'HOUR_IN_SECONDS', 3600 );

	final class WP_Error {
		private $code;
		private $message;
		private $data;

		public function __construct( $code = '', $message = '', $data = '' ) {
			$this->code    = $code;
			$this->message = $message;
			$this->data    = $data;
		}

		public function get_error_code() {
			return $this->code;
		}

		public function get_error_message() {
			return $this->message;
		}

		public function get_error_data() {
			return $this->data;
		}
	}

	final class SingleConfigAbility {
		public $calls = array();
		public $result;

		public function __construct( $result ) {
			$this->result = $result;
		}

		public function execute( $input ) {
			$this->calls[] = $input;
			return $this->result;
		}
	}

	$GLOBALS['sc_abilities'] = array();
	$GLOBALS['sc_options']   = array();
	$GLOBALS['sc_meta']      = array();

	function __( $text, $domain = '' ) {
		unset( $domain );
		return $text;
	}

	function add_action( $hook, $callback ) {
		unset( $hook, $callback );
	}

	function wp_register_ability( $name, $definition ) {
		unset( $name, $definition );
	}

	function current_user_can( $capability ) {
		unset( $capability );
		return true;
	}

	function wp_get_ability( $name ) {
		return $GLOBALS['sc_abilities'][ $name ] ?? null;
	}

	function wp_has_ability( $name ) {
		return isset( $GLOBALS['sc_abilities'][ $name ] );
	}

	function absint( $value ) {
		return abs( (int) $value );
	}

	function sanitize_text_field( $value ) {
		return trim( (string) $value );
	}

	function sanitize_email( $value ) {
		return trim( (string) $value );
	}

	function esc_url_raw( $value ) {
		return trim( (string) $value );
	}

	function is_email( $value ) {
		return (bool) filter_var( $value, FILTER_VALIDATE_EMAIL );
	}

	function wp_parse_args( $args, $defaults ) {
		return array_merge( $defaults, (array) $args );
	}

	function is_wp_error( $value ) {
		return $value instanceof WP_Error;
	}

	function get_site_option( $name, $default = array() ) {
		return $GLOBALS['sc_options'][ $name ] ?? $default;
	}

	function update_site_option( $name, $value ) {
		$GLOBALS['sc_options'][ $name ] = $value;
		return true;
	}

	function get_transient( $key ) {
		unset( $key );
		return false;
	}

	function set_transient( $key, $value, $ttl ) {
		unset( $key, $value, $ttl );
		return true;
	}

	function get_post( $id ) {
		if ( 69 !== $id ) {
			return null;
		}
		$post            = new stdClass();
		$post->ID        = 69;
		$post->post_type = 'newsletter';
		return $post;
	}

	function get_post_meta( $id, $key, $single = false ) {
		unset( $single );
		return $GLOBALS['sc_meta'][ $id ][ $key ] ?? '';
	}

	function update_post_meta( $id, $key, $value ) {
		$GLOBALS['sc_meta'][ $id ][ $key ] = $value;
		return true;
	}

	function prepare_newsletter_email_content( $post ) {
		unset( $post );
		return array(
			'subject'       => 'Subject',
			'html_template' => '<p>Hi</p>',
			'plain_text'    => 'Hi',
		);
	}

	function get_newsletter_integrations() {
		return array(
			'homepage' => array(
				'label'       => 'Homepage',
				'list_id_key' => 'homepage_list_id',
			),
		);
	}

	require_once dirname( __DIR__ ) . '/inc/core/sendy-api.php';
	require_once dirname( __DIR__ ) . '/inc/core/abilities/campaign-management.php';
	require_once dirname( __DIR__ ) . '/inc/core/abilities/campaign.php';
	require_once dirname( __DIR__ ) . '/inc/core/abilities/settings.php';
	require_once dirname( __DIR__ ) . '/inc/core/abilities/stats.php';

	$failures = array();
	$passes   = 0;
	$assert   = static function ( $condition, $message ) use ( &$failures, &$passes ) {
		if ( $condition ) {
			++$passes;
			return;
		}
		$failures[] = $message;
	};

	// ── Push surfaces the provider's real error ──
	$push = new SingleConfigAbility( new WP_Error( 'sendy_config_incomplete', 'The Sendy API configuration requires api_key and sendy_url in the datamachine_sendy_config network option.' ) );
	$GLOBALS['sc_abilities']['datamachine/sendy-push-campaign'] = $push;

	$failed = extrachill_newsletter_ability_push_campaign( array( 'post_id' => 69 ) );
	$assert( is_wp_error( $failed ), 'provider errors are returned as errors, not success:false' );
	$assert( 'sendy_config_incomplete' === $failed->get_error_code(), 'the provider error code is preserved' );
	$assert( false !== strpos( $failed->get_error_message(), 'datamachine_sendy_config' ), 'the real cause reaches the admin alert' );
	$assert( 0 === strpos( $failed->get_error_message(), 'Push to Sendy failed: ' ), 'the message says what failed' );
	$assert( 502 === ( $failed->get_error_data()['status'] ?? null ), 'REST gets a 502, not a 200' );

	$push->result = array( 'success' => false, 'message' => 'Unable to create campaign' );
	$rejected     = extrachill_newsletter_ability_push_campaign( array( 'post_id' => 69 ) );
	$assert( is_wp_error( $rejected ) && 'sendy_push_failed' === $rejected->get_error_code(), 'success:false from the provider becomes an error' );
	$assert( false !== strpos( $rejected->get_error_message(), 'Unable to create campaign' ), 'the provider message is kept' );
	$assert( '' === get_post_meta( 69, '_sendy_campaign_id', true ), 'failed pushes record no campaign' );

	$push->result = array( 'success' => true, 'campaign_id' => '321', 'created' => true );
	$ok           = extrachill_newsletter_ability_push_campaign( array( 'post_id' => 69 ) );
	$assert( ! is_wp_error( $ok ) && true === $ok['success'] && '321' === $ok['campaign_id'], 'successful pushes still succeed' );
	$assert( '321' === get_post_meta( 69, '_sendy_campaign_id', true ), 'new campaign IDs are still recorded' );
	foreach ( $push->calls as $call ) {
		$assert( ! isset( $call['config'] ) && ! isset( $call['api_key'] ), 'push passes no credentials' );
	}

	// ── Sender identity no longer carries credentials ──
	$GLOBALS['sc_options']['extrachill_newsletter_settings'] = array(
		'sendy_api_key' => 'legacy-secret',
		'sendy_url'     => 'https://mail.example.test',
		'from_name'     => 'Extra Chill',
	);
	$identity = get_sendy_config();
	$assert( ! isset( $identity['api_key'] ) && ! isset( $identity['sendy_url'] ), 'get_sendy_config returns sender identity only' );
	$assert( 'Extra Chill' === $identity['from_name'] && '1' === $identity['brand_id'], 'identity keeps stored values and defaults' );
	$assert( ! function_exists( 'extrachill_newsletter_sendy_dmb_config' ), 'the duplicate config builder is gone' );

	// ── Migration: legacy copy seeds DMB once, then is removed ──
	SendyAbilities::$config = array( 'db' => array( 'host' => 'localhost' ) );
	SendyAbilities::$reject = true;
	extrachill_newsletter_migrate_sendy_credentials();
	$assert( isset( $GLOBALS['sc_options']['extrachill_newsletter_settings']['sendy_api_key'] ), 'legacy key is kept when DMB rejects it' );

	SendyAbilities::$reject = false;
	extrachill_newsletter_migrate_sendy_credentials();
	$assert( 'legacy-secret' === SendyAbilities::$config['api_key'] && 'https://mail.example.test' === SendyAbilities::$config['sendy_url'], 'legacy credentials seed an empty DMB config' );
	$assert( isset( SendyAbilities::$config['db'] ), 'migration keeps DMB db config' );
	$assert( ! isset( $GLOBALS['sc_options']['extrachill_newsletter_settings']['sendy_api_key'] ) && ! isset( $GLOBALS['sc_options']['extrachill_newsletter_settings']['sendy_url'] ), 'legacy copy is removed after migrating' );
	$assert( 'Extra Chill' === $GLOBALS['sc_options']['extrachill_newsletter_settings']['from_name'], 'migration keeps other settings' );

	SendyAbilities::$config = array( 'api_key' => 'dmb-secret', 'sendy_url' => 'https://dmb.example.test' );
	$GLOBALS['sc_options']['extrachill_newsletter_settings']['sendy_api_key'] = 'stale-secret';
	extrachill_newsletter_migrate_sendy_credentials();
	$assert( 'dmb-secret' === SendyAbilities::$config['api_key'], 'a stale legacy key never overwrites DMB' );
	$assert( ! isset( $GLOBALS['sc_options']['extrachill_newsletter_settings']['sendy_api_key'] ), 'a stale legacy key is still cleaned up' );

	// ── Settings read/write through DMB ──
	$settings = extrachill_newsletter_ability_get_settings( array() );
	$assert( '' === $settings['settings']['sendy_api_key'] && true === $settings['settings']['sendy_api_key_set'], 'settings never return the API key' );
	$assert( 'https://dmb.example.test' === $settings['settings']['sendy_url'], 'settings show the DMB URL' );
	$assert( false === strpos( serialize( $settings ), 'dmb-secret' ), 'the key appears nowhere in settings output' );

	extrachill_newsletter_ability_update_settings( array( 'sendy_api_key' => '', 'sendy_url' => 'https://new.example.test/', 'from_name' => 'EC' ) );
	$assert( 'dmb-secret' === SendyAbilities::$config['api_key'] && 'https://new.example.test' === SendyAbilities::$config['sendy_url'], 'blank key keeps the saved key while the URL updates' );
	$assert( 'EC' === get_site_option( 'extrachill_newsletter_settings' )['from_name'], 'sender identity still saves' );
	$assert( ! isset( get_site_option( 'extrachill_newsletter_settings' )['sendy_api_key'] ), 'the key is never stored in newsletter settings' );

	extrachill_newsletter_ability_update_settings( array( 'sendy_api_key' => 'rotated-secret' ) );
	$assert( 'rotated-secret' === SendyAbilities::$config['api_key'] && 'https://new.example.test' === SendyAbilities::$config['sendy_url'], 'a new key rotates without touching the URL' );

	SendyAbilities::$reject = true;
	$bad = extrachill_newsletter_ability_update_settings( array( 'sendy_url' => 'nope' ) );
	$assert( is_wp_error( $bad ), 'a rejected connection update is reported' );
	SendyAbilities::$reject = false;

	SendyAbilities::$config = array();
	$warned                 = extrachill_newsletter_ability_get_settings( array() );
	$assert( false === $warned['settings']['sendy_api_key_set'], 'missing key is reported' );
	$assert( (bool) array_filter( $warned['warnings'], static fn( $w ) => false !== strpos( $w, 'Sendy API connection' ) ), 'missing connection is a settings warning' );

	// ── Metrics no longer pass a config payload ──
	$metrics = new SingleConfigAbility( array( 'total_active' => 1, 'lists' => array() ) );
	$GLOBALS['sc_abilities']['datamachine/sendy-metrics'] = $metrics;
	extrachill_newsletter_ability_subscriber_stats( array( 'force_refresh' => true ) );
	$assert( ! isset( $metrics->calls[0]['config'] ), 'subscriber stats rely on the DMB config' );

	$subscribe_src = file_get_contents( dirname( __DIR__ ) . '/inc/core/abilities/subscribe.php' );
	$analytics_src = file_get_contents( dirname( __DIR__ ) . '/inc/core/abilities/analytics.php' );
	$assert( false === strpos( $subscribe_src, "'config'" ) && false === strpos( $analytics_src, "'config'" ), 'subscribe and analytics build no config payload themselves' );
	$assert( array() === extrachill_newsletter_sendy_provider_args(), 'a canonical-config DMB gets no legacy args' );

	if ( ! empty( $failures ) ) {
		fwrite( STDERR, implode( PHP_EOL, $failures ) . PHP_EOL );
		exit( 1 );
	}

	echo "Sendy single-config smoke checks passed ({$passes} assertions).\n";
}
