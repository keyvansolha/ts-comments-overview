<?php
/**
 * بستر تست افزونه‌ی TS Comments Overview — بدون نیاز به نصب وردپرس.
 *
 * این فایل حداقلِ توابع وردپرس را شبیه‌سازی می‌کند تا کد واقعی افزونه (تنظیمات،
 * نرمال‌سازی پیلود، کلاینت API و رندر) اجرا و بررسی شود.
 *
 * اجرا:  php tests/run.php
 *
 * @package TS_Comments_Overview
 */

// ---------------------------------------------------------------------------
// ثابت‌ها
// ---------------------------------------------------------------------------

define( 'ABSPATH', dirname( __DIR__ ) . '/' );

/**
 * توکن واقعی شبیه‌سازی‌شده.
 *
 * پیش از بارگذاری افزونه تعریف می‌شود تا همان مسیر wp-config.php در تست
 * اجرا شود (افزونه با defined() مقدار را بازنویسی نمی‌کند).
 *
 * برای تست زنده علیه سرویس واقعی، توکن را در متغیر محیطی TSCO_API_TOKEN بدهید و
 * TSCO_REAL_HTTP=1 را هم ست کنید (توکن هیچ‌وقت داخل مخزن ذخیره نمی‌شود).
 */
define(
	'TS_COMMENTS_OVERVIEW_API_TOKEN',
	(string) ( getenv( 'TSCO_API_TOKEN' ) ?: 'test-token-abcdefghijklmnop-1234567890' )
);

/** مسیر قالب واقعی، برای بررسی یکپارچگی هوک. */
define( 'TS_CO_TEST_THEME_DIR', '/mnt/K1/git/Site/public_html/wp-content/themes/amazing' );

$GLOBALS['ts_co_options']       = array();
$GLOBALS['ts_co_transients']    = array();
$GLOBALS['ts_co_actions']       = array();
$GLOBALS['ts_co_styles']        = array();
$GLOBALS['ts_co_http_calls']    = array();
$GLOBALS['ts_co_http_handler']  = null;
$GLOBALS['ts_co_is_product']    = false;
$GLOBALS['ts_co_failures']      = array();
$GLOBALS['ts_co_assertions']    = 0;
$GLOBALS['ts_co_test_count']    = 0;

// ---------------------------------------------------------------------------
// خطا
// ---------------------------------------------------------------------------

class WP_Error {

	/** @var string */
	private $code;

	/** @var string */
	private $message;

	/** @var mixed */
	private $data;

	/**
	 * @param string $code    کد.
	 * @param string $message پیام.
	 * @param mixed  $data    داده.
	 */
	public function __construct( $code = '', $message = '', $data = null ) {
		$this->code    = (string) $code;
		$this->message = (string) $message;
		$this->data    = $data;
	}

	/** @return string */
	public function get_error_code() {
		return $this->code;
	}

	/** @return string */
	public function get_error_message() {
		return $this->message;
	}

	/** @return mixed */
	public function get_error_data() {
		return $this->data;
	}
}

/**
 * @param mixed $thing مقدار.
 * @return bool
 */
function is_wp_error( $thing ) {
	return $thing instanceof WP_Error;
}

// ---------------------------------------------------------------------------
// پوسته‌ی افزونه
// ---------------------------------------------------------------------------

/**
 * شبیه‌سازی get_file_data برای هدر افزونه.
 *
 * نسخه از هدر واقعی همان فایل خوانده می‌شود (نه یک عدد ثابت در تست) تا تست‌ها با
 * بالا رفتن نسخه از هدر عقب نیفتند و مسیر «نسخه از هدر» واقعاً بررسی شود.
 *
 * @param string   $file    فایل.
 * @param string[] $headers هدرها.
 * @param string   $context متن.
 * @return array<string,string>
 */
function get_file_data( $file, $headers, $context = '' ) {
	$out = array();

	foreach ( $headers as $key => $label ) {
		$out[ $key ] = '';
	}

	if ( is_string( $file ) && is_readable( $file ) ) {
		$contents = (string) file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

		foreach ( $headers as $key => $label ) {
			if ( preg_match( '/^[ 	\/*#@]*' . preg_quote( $label, '/' ) . ':\s*(.+)$/mi', $contents, $matches ) === 1 ) {
				$out[ $key ] = trim( $matches[1] );
			}
		}
	}

	return $out;
}

/**
 * @param string $file فایل.
 * @return string
 */
function plugin_dir_path( $file ) {
	return rtrim( dirname( $file ), '/' ) . '/';
}

/**
 * @param string $file فایل.
 * @return string
 */
function plugin_dir_url( $file ) {
	return 'https://www.tehranspeaker.com/wp-content/plugins/' . basename( dirname( $file ) ) . '/';
}

/**
 * @param string $file فایل.
 * @return string
 */
function plugin_basename( $file ) {
	return basename( dirname( $file ) ) . '/' . basename( $file );
}

/**
 * @param string   $file     فایل.
 * @param callable $callback تابع.
 * @return void
 */
function register_activation_hook( $file, $callback ) {}

/**
 * @param string   $file     فایل.
 * @param callable $callback تابع.
 * @return void
 */
function register_deactivation_hook( $file, $callback ) {}

// ---------------------------------------------------------------------------
// هوک‌ها
// ---------------------------------------------------------------------------

/**
 * @param string   $hook     نام.
 * @param callable $callback تابع.
 * @param int      $priority اولویت.
 * @param int      $args     آرگومان.
 * @return void
 */
function add_action( $hook, $callback, $priority = 10, $args = 1 ) {
	$GLOBALS['ts_co_actions'][ $hook ][] = $callback;
}

/**
 * @param string   $hook     نام.
 * @param callable $callback تابع.
 * @param int      $priority اولویت.
 * @param int      $args     آرگومان.
 * @return void
 */
function add_filter( $hook, $callback, $priority = 10, $args = 1 ) {}

/**
 * @param string $hook  نام.
 * @param mixed  $value مقدار.
 * @return mixed
 */
function apply_filters( $hook, $value ) {
	$filters = isset( $GLOBALS['ts_co_filters'][ $hook ] ) ? $GLOBALS['ts_co_filters'][ $hook ] : array();

	foreach ( $filters as $callback ) {
		$value = call_user_func( $callback, $value );
	}

	return $value;
}

/**
 * @param string $hook نام.
 * @return bool
 */
function did_action( $hook ) {
	return ! empty( $GLOBALS['ts_co_actions'][ $hook ] );
}

// ---------------------------------------------------------------------------
// آپشن‌ها و ترنزینت‌ها
// ---------------------------------------------------------------------------

/**
 * @param string $name    نام.
 * @param mixed  $default پیش‌فرض.
 * @return mixed
 */
function get_option( $name, $default = false ) {
	if ( 'date_format' === $name && ! array_key_exists( $name, $GLOBALS['ts_co_options'] ) ) {
		return $default;
	}

	return array_key_exists( $name, $GLOBALS['ts_co_options'] ) ? $GLOBALS['ts_co_options'][ $name ] : $default;
}

/**
 * @param string $name     نام.
 * @param mixed  $value    مقدار.
 * @param string $deprecated بی‌استفاده.
 * @param string $autoload autoload.
 * @return bool
 */
function add_option( $name, $value = '', $deprecated = '', $autoload = 'yes' ) {
	if ( array_key_exists( $name, $GLOBALS['ts_co_options'] ) ) {
		return false;
	}

	$GLOBALS['ts_co_options'][ $name ] = $value;

	return true;
}

/**
 * @param string $name     نام.
 * @param mixed  $value    مقدار.
 * @param mixed  $autoload autoload.
 * @return bool
 */
function update_option( $name, $value, $autoload = null ) {
	$GLOBALS['ts_co_options'][ $name ] = $value;

	return true;
}

/**
 * @param string $name نام.
 * @return bool
 */
function delete_option( $name ) {
	unset( $GLOBALS['ts_co_options'][ $name ] );

	return true;
}

/**
 * @param string $key   کلید.
 * @param mixed  $value مقدار.
 * @param int    $ttl   عمر.
 * @return bool
 */
function set_transient( $key, $value, $ttl = 0 ) {
	$GLOBALS['ts_co_transients'][ $key ] = array(
		'value'   => $value,
		'expires' => $ttl > 0 ? time() + (int) $ttl : 0,
	);

	return true;
}

/**
 * @param string $key کلید.
 * @return mixed
 */
function get_transient( $key ) {
	if ( ! isset( $GLOBALS['ts_co_transients'][ $key ] ) ) {
		return false;
	}

	$entry = $GLOBALS['ts_co_transients'][ $key ];

	if ( $entry['expires'] > 0 && $entry['expires'] <= time() ) {
		unset( $GLOBALS['ts_co_transients'][ $key ] );
		return false;
	}

	return $entry['value'];
}

/**
 * @param string $key کلید.
 * @return bool
 */
function delete_transient( $key ) {
	unset( $GLOBALS['ts_co_transients'][ $key ] );

	return true;
}

// ---------------------------------------------------------------------------
// HTTP
// ---------------------------------------------------------------------------

/**
 * @param string               $url  نشانی.
 * @param array<string,mixed>  $args آرگومان‌ها.
 * @return array<string,mixed>|WP_Error
 */
function wp_remote_get( $url, $args = array() ) {
	$GLOBALS['ts_co_http_calls'][] = array(
		'url'  => $url,
		'args' => $args,
	);

	$handler = $GLOBALS['ts_co_http_handler'];

	if ( is_callable( $handler ) ) {
		return call_user_func( $handler, $url, $args );
	}

	/*
	 * حالت اختیاری تست زنده: با TSCO_REAL_HTTP=1 و توکن در TSCO_API_TOKEN، همین
	 * تابع درخواست واقعی می‌زند تا کد کلاینت افزونه سرتاسری بررسی شود.
	 */
	if ( getenv( 'TSCO_REAL_HTTP' ) && function_exists( 'curl_init' ) ) {
		$headers = array();

		if ( isset( $args['headers'] ) && is_array( $args['headers'] ) ) {
			foreach ( $args['headers'] as $name => $value ) {
				$headers[] = $name . ': ' . $value;
			}
		}

		$curl = curl_init( $url );
		curl_setopt_array(
			$curl,
			array(
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_FOLLOWLOCATION => false,
				CURLOPT_TIMEOUT        => isset( $args['timeout'] ) ? (int) $args['timeout'] : 10,
				CURLOPT_HTTPHEADER     => $headers,
				CURLOPT_USERAGENT      => isset( $args['user-agent'] ) ? (string) $args['user-agent'] : 'ts-co-live-test',
			)
		);

		$body   = curl_exec( $curl );
		$status = (int) curl_getinfo( $curl, CURLINFO_RESPONSE_CODE );
		$error  = curl_error( $curl );
		curl_close( $curl );

		if ( false === $body ) {
			return new WP_Error( 'http_request_failed', (string) $error );
		}

		return array(
			'response' => array( 'code' => $status ),
			'body'     => (string) $body,
		);
	}

	return new WP_Error( 'no_handler', 'no HTTP handler registered in tests' );
}

/**
 * @param mixed $response پاسخ.
 * @return int
 */
function wp_remote_retrieve_response_code( $response ) {
	return ( is_array( $response ) && isset( $response['response']['code'] ) ) ? (int) $response['response']['code'] : 0;
}

/**
 * @param mixed $response پاسخ.
 * @return string
 */
function wp_remote_retrieve_body( $response ) {
	return ( is_array( $response ) && isset( $response['body'] ) ) ? (string) $response['body'] : '';
}

/**
 * @param string $url نشانی.
 * @return string
 */
function home_url( $url = '' ) {
	return 'https://www.tehranspeaker.com/' . ltrim( (string) $url, '/' );
}

/**
 * @param array<string,mixed> $args آرگومان‌ها.
 * @param string              $url  نشانی.
 * @return string
 */
function add_query_arg( $args, $url ) {
	$parts = wp_parse_url( $url );
	$query = isset( $parts['query'] ) ? $parts['query'] : '';
	parse_str( $query, $params );

	foreach ( $args as $key => $value ) {
		$params[ $key ] = $value;
	}

	$base = ( isset( $parts['scheme'] ) ? $parts['scheme'] . '://' : '' ) . ( isset( $parts['host'] ) ? $parts['host'] : '' );
	$base .= isset( $parts['path'] ) ? $parts['path'] : '';

	return $base . '?' . http_build_query( $params );
}

/**
 * @param string $url نشانی.
 * @return array<string,mixed>|false
 */
function wp_parse_url( $url ) {
	return parse_url( $url );
}

/**
 * @param mixed $data داده.
 * @param int   $options گزینه‌ها.
 * @return string|false
 */
function wp_json_encode( $data, $options = 0 ) {
	return json_encode( $data, $options );
}

// ---------------------------------------------------------------------------
// پاکسازی و escape
// ---------------------------------------------------------------------------

/**
 * @param string $text متن.
 * @return string
 */
function esc_html( $text ) {
	return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
}

/**
 * @param string $text متن.
 * @return string
 */
function esc_attr( $text ) {
	return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
}

/**
 * @param string $url نشانی.
 * @return string
 */
function esc_url( $url ) {
	return (string) $url;
}

/**
 * شبیه‌سازی wp_kses_post: بلوک‌های script/style حذف، برچسب‌های غیرمجاز از میان
 * می‌روند و attributeهای غیرمجاز حذف می‌شوند.
 *
 * @param string $text متن.
 * @return string
 */
function wp_kses_post( $text ) {
	$allowed = array( 'p', 'br', 'strong', 'b', 'em', 'i', 'ul', 'ol', 'li', 'span' );
	$text    = (string) $text;

	$text = preg_replace( '#<(script|style)\b[^>]*>.*?</\1>#is', '', $text );
	$text = is_string( $text ) ? $text : '';

	$text = preg_replace_callback(
		'#<\s*(/?)\s*([a-zA-Z0-9]+)([^>]*)>#',
		static function ( $matches ) use ( $allowed ) {
			$closing = '/' === $matches[1];
			$tag     = strtolower( $matches[2] );

			if ( ! in_array( $tag, $allowed, true ) ) {
				return '';
			}

			return $closing ? '</' . $tag . '>' : '<' . $tag . '>';
		},
		$text
	);

	return is_string( $text ) ? $text : '';
}

/**
 * @param string $text          متن.
 * @param bool   $remove_breaks حذف خطوط.
 * @return string
 */
function wp_strip_all_tags( $text, $remove_breaks = false ) {
	// هم‌رفتار با هسته‌ی وردپرس: محتوای script/style کامل حذف می‌شود.
	$text = preg_replace( '@<(script|style)[^>]*?>.*?</\1>@si', '', (string) $text );
	$text = is_string( $text ) ? strip_tags( $text ) : '';

	if ( $remove_breaks ) {
		$text = preg_replace( '/[\r\n	 ]+/', ' ', $text );
		$text = is_string( $text ) ? $text : '';
	}

	return trim( $text );
}

/**
 * @param string $string متن.
 * @param bool   $strip  حذف بایت نامعتبر.
 * @return string
 */
function wp_check_invalid_utf8( $string, $strip = false ) {
	$string = (string) $string;

	if ( '' === $string ) {
		return '';
	}

	if ( function_exists( 'mb_check_encoding' ) && mb_check_encoding( $string, 'UTF-8' ) ) {
		return $string;
	}

	$clean = mb_convert_encoding( $string, 'UTF-8', 'UTF-8' );

	return is_string( $clean ) ? $clean : '';
}

/**
 * @param string $text متن.
 * @return string
 */
function sanitize_text_field( $text ) {
	return trim( (string) preg_replace( '/[\r\n\t]+/', ' ', wp_strip_all_tags( (string) $text ) ) );
}

/**
 * @param mixed $value مقدار.
 * @return mixed
 */
function wp_unslash( $value ) {
	return is_array( $value ) ? array_map( 'wp_unslash', $value ) : stripslashes( (string) $value );
}

// ---------------------------------------------------------------------------
// متفرقه
// ---------------------------------------------------------------------------

/**
 * @param mixed $value مقدار.
 * @return int
 */
function absint( $value ) {
	return abs( (int) $value );
}

/**
 * @param string $string متن.
 * @return string
 */
function trailingslashit( $string ) {
	return rtrim( (string) $string, '/\\' ) . '/';
}

/**
 * @param string $string متن.
 * @return string
 */
function untrailingslashit( $string ) {
	return rtrim( (string) $string, '/\\' );
}

/**
 * @param int|float $number عدد.
 * @param int       $decimals اعشار.
 * @return string
 */
function number_format_i18n( $number, $decimals = 0 ) {
	return number_format( (float) $number, (int) $decimals );
}

/**
 * @param string $format    قالب.
 * @param int    $timestamp زمان.
 * @return string
 */
function wp_date( $format, $timestamp = null ) {
	return gmdate( (string) $format, null === $timestamp ? time() : (int) $timestamp );
}

/**
 * @param string $format    قالب.
 * @param int    $timestamp زمان.
 * @return string
 */
function date_i18n( $format, $timestamp = null ) {
	return gmdate( (string) $format, null === $timestamp ? time() : (int) $timestamp );
}

/**
 * @param string $hook  نام.
 * @param string $list  فهرست.
 * @return bool
 */
function wp_style_is( $hook, $list = 'enqueued' ) {
	return isset( $GLOBALS['ts_co_styles'][ $hook ] );
}

/**
 * @param string $handle هندل.
 * @param string $src    نشانی.
 * @param array  $deps   وابستگی‌ها.
 * @param string $ver    نسخه.
 * @return void
 */
function wp_enqueue_style( $handle, $src = '', $deps = array(), $ver = false ) {
	$GLOBALS['ts_co_styles'][ $handle ] = array(
		'src'  => $src,
		'deps' => $deps,
		'ver'  => $ver,
	);
}

/**
 * @return bool
 */
function is_admin() {
	return false;
}

/**
 * @return bool
 */
function is_product() {
	return (bool) $GLOBALS['ts_co_is_product'];
}

/**
 * @param string $cap توانایی.
 * @return bool
 */
function current_user_can( $cap ) {
	return true;
}

/**
 * @param string $path مسیر.
 * @return string
 */
function get_stylesheet_directory() {
	return TS_CO_TEST_THEME_DIR;
}

/**
 * @param string $slug اسلاگ.
 * @param string $page صفحه.
 * @return string
 */
function admin_url( $slug = '', $page = '' ) {
	return 'https://www.tehranspeaker.com/wp-admin/' . ltrim( (string) $slug, '/' );
}

/**
 * @param string $group نام گروه.
 * @return void
 */
function settings_fields( $group ) {
	echo '<input type="hidden" name="option_page" value="' . $group . '" />';
}

/**
 * @param string $text متن.
 * @param string $type نوع.
 * @param string $name نام.
 * @param bool   $wrap بسته‌بندی.
 * @return void
 */
function submit_button( $text = null, $type = 'primary', $name = 'submit', $wrap = true ) {
	echo '<button type="submit" class="' . $type . '" name="' . $name . '">' . (string) $text . '</button>';
}

/**
 * @param mixed  $checked  مقدار.
 * @param mixed  $current  مقدار فعلی.
 * @param bool   $echo     چاپ.
 * @return string
 */
function checked( $checked, $current = true, $echo = true ) {
	$result = (string) $checked === (string) $current ? ' checked="checked"' : '';

	if ( $echo ) {
		echo $result;
	}

	return $result;
}

/**
 * @param string $action اکشن.
 * @param string $name   نام.
 * @return void
 */
function wp_nonce_field( $action = -1, $name = '_wpnonce' ) {
	echo '<input type="hidden" name="' . $name . '" value="nonce" />';
}

/**
 * @param string $location نشانی.
 * @param int    $status   کد.
 * @return void
 */
function wp_safe_redirect( $location, $status = 302 ) {} // phpcs:ignore

/**
 * @param string $action اکشن.
 * @return void
 */
function check_admin_referer( $action = -1 ) {}

/**
 * @param string $text متن.
 * @return string
 */
function esc_html__( $text, $domain = '' ) {
	return esc_html( $text );
}

/**
 * @param string $text متن.
 * @param string $domain دامنه.
 * @return string
 */
function __( $text, $domain = '' ) {
	return (string) $text;
}

/**
 * @return int
 */
function get_current_user_id() {
	return 1;
}

/**
 * @param string $text متن.
 * @return void
 */
function wp_die( $text = '' ) {
	// در تست، wp_die فوراً اجرا را قطع می‌کند تا مسیر غیرمجاز قابل تشخیص باشد.
	throw new RuntimeException( 'wp_die: ' . (string) $text );
}

// ---------------------------------------------------------------------------
// کمکی‌های تست
// ---------------------------------------------------------------------------

/**
 * تعیین پاسخ HTTP بعدی.
 *
 * @param int          $status کد وضعیت.
 * @param array|string $body   بدنه.
 * @return void
 */
function ts_co_set_http_response( $status, $body ) {
	$GLOBALS['ts_co_http_handler'] = static function () use ( $status, $body ) {
		return array(
			'response' => array( 'code' => (int) $status ),
			'body'     => is_string( $body ) ? $body : json_encode( $body ),
		);
	};
}

/**
 * تعیین خطای شبکه.
 *
 * @return void
 */
function ts_co_set_http_failure() {
	$GLOBALS['ts_co_http_handler'] = static function () {
		return new WP_Error( 'http_request_failed', 'connection timed out' );
	};
}

/**
 * تعداد درخواست‌های HTTP ثبت‌شده.
 *
 * @return int
 */
function ts_co_http_call_count() {
	return count( $GLOBALS['ts_co_http_calls'] );
}

/**
 * آخرین درخواست HTTP.
 *
 * @return array<string,mixed>|null
 */
function ts_co_last_http_call() {
	$calls = $GLOBALS['ts_co_http_calls'];
	return empty( $calls ) ? null : end( $calls );
}

/**
 * پاک کردن وضعیت محیط تست (نه کد افزونه).
 *
 * @return void
 */
function ts_co_reset_environment() {
	$GLOBALS['ts_co_http_calls']   = array();
	$GLOBALS['ts_co_transients']   = array();
	$GLOBALS['ts_co_styles']       = array();
	$GLOBALS['ts_co_http_handler'] = null;

	// ایندکس کش هم بخشی از زیرساخت محیط تست است، نه تنظیمات افزونه.
	unset( $GLOBALS['ts_co_options']['ts_comments_overview_cache_index'] );
}

/**
 * ثبت یک ادعا.
 *
 * @param bool   $passed آیا درست بود.
 * @param string $label  برچسب.
 * @return void
 */
function ts_co_assert( $passed, $label ) {
	++$GLOBALS['ts_co_assertions'];

	if ( ! $passed ) {
		$GLOBALS['ts_co_failures'][] = $label;
		echo "  ✗ " . $label . "\n";
	}

	return;
}

/**
 * @param mixed  $expected مقدار انتظار.
 * @param mixed  $actual   مقدار واقعی.
 * @param string $label    برچسب.
 * @return void
 */
function ts_co_assert_same( $expected, $actual, $label ) {
	$passed = $expected === $actual;

	if ( ! $passed ) {
		$label .= ' (expected: ' . var_export( $expected, true ) . ' / got: ' . var_export( $actual, true ) . ')';
	}

	ts_co_assert( $passed, $label );
}

/**
 * @param mixed  $value مقدار.
 * @param string $label برچسب.
 * @return void
 */
function ts_co_assert_true( $value, $label ) {
	ts_co_assert( true === (bool) $value, $label );
}

/**
 * @param mixed  $value مقدار.
 * @param string $label برچسب.
 * @return void
 */
function ts_co_assert_false( $value, $label ) {
	ts_co_assert( ! $value, $label );
}

/**
 * @param string $needle سوزن.
 * @param string $haystack انبار کاه.
 * @param string $label برچسب.
 * @return void
 */
function ts_co_assert_contains( $needle, $haystack, $label ) {
	ts_co_assert( false !== strpos( (string) $haystack, (string) $needle ), $label );
}

/**
 * @param string $needle سوزن.
 * @param string $haystack انبار کاه.
 * @param string $label برچسب.
 * @return void
 */
function ts_co_assert_not_contains( $needle, $haystack, $label ) {
	ts_co_assert( false === strpos( (string) $haystack, (string) $needle ), $label );
}

/**
 * @param mixed  $value مقدار.
 * @param string $label برچسب.
 * @return void
 */
function ts_co_assert_empty( $value, $label ) {
	ts_co_assert( empty( $value ), $label );
}

// ---------------------------------------------------------------------------
// بارگذاری افزونه
// ---------------------------------------------------------------------------

require_once dirname( __DIR__ ) . '/ts-comments-overview.php';
