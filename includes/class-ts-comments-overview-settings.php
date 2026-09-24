<?php
/**
 * تنظیمات افزونه‌ی خلاصه نظرات محصولات.
 *
 * حالت‌های نمایش (تنها تنظیم موجود در دیتابیس):
 *   - off               : خلاصه نمایش داده نمی‌شود.
 *   - summary           : فقط خلاصه‌ی نظرات.
 *   - summary_pros_cons : خلاصه + نقاط قوت و ضعف.
 *
 * آستانه‌ی کیفیت (min_recommend):
 *   اگر درصد پیشنهاد محصول از این عدد کمتر باشد، بخش خلاصه اصلاً نمایش داده
 *   نمی‌شود؛ تا برای محصولی که کاربران از آن راضی نیستند، جمع‌بندی منفی به
 *   کاربر نشان داده نشود. مقدار ۰ یعنی این قاعده خاموش است.
 *
 * @package TS_Comments_Overview
 */

defined( 'ABSPATH' ) || exit;

/**
 * خواندن/نوشتن تنظیمات و تبدیل مقادیر وابسته به محیط (توکن، نشانی سرویس).
 */
final class TS_Comments_Overview_Settings {

	/** نام آپشن مستقل و non-autoload. */
	const OPTION = 'ts_comments_overview_settings';

	/** نسخه‌ی ساختار تنظیمات. */
	const SCHEMA_VERSION = 2;

	/** حالت‌های مجاز نمایش. */
	const MODE_OFF              = 'off';
	const MODE_SUMMARY          = 'summary';
	const MODE_SUMMARY_PROS_CONS = 'summary_pros_cons';

	/** حداقل درصد پیشنهاد برای نمایش خلاصه (پیش‌فرض). */
	const DEFAULT_MIN_RECOMMEND = 50;

	/** سقف مجاز آستانه‌ی کیفیت. */
	const MAX_MIN_RECOMMEND = 100;

	/**
	 * حالت پیش‌فرض: خاموش.
	 *
	 * @return array<string,mixed>
	 */
	public static function defaults() {
		return array(
			'schema_version' => self::SCHEMA_VERSION,
			'mode'           => self::MODE_OFF,
			'min_recommend'  => self::DEFAULT_MIN_RECOMMEND,
		);
	}

	/**
	 * نام‌های نمایشی حالت‌ها برای پیشخوان.
	 *
	 * @return array<string,string>
	 */
	public static function modes() {
		return array(
			self::MODE_OFF               => 'خاموش — خلاصه در صفحه محصول نمایش داده نشود',
			self::MODE_SUMMARY           => 'روشن — فقط خلاصه‌ی نظرات',
			self::MODE_SUMMARY_PROS_CONS => 'روشن + نقاط قوت و ضعف — خلاصه و فهرست strengths / weaknesses',
		);
	}

	/**
	 * ثبت هوک‌های تنظیمات.
	 *
	 * @return void
	 */
	public static function register_hooks() {
		add_action( 'admin_init', array( __CLASS__, 'register_setting' ) );
	}

	/**
	 * ثبت آپشن در Settings API با پاکسازی سمت سرور.
	 *
	 * @return void
	 */
	public static function register_setting() {
		register_setting(
			'ts_comments_overview',
			self::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize' ),
				'default'           => self::defaults(),
				'show_in_rest'      => false,
			)
		);
	}

	/**
	 * ساخت آپشن پیش‌فرض در اولین فعال‌سازی (non-autoload).
	 *
	 * @return void
	 */
	public static function ensure_defaults() {
		if ( false === get_option( self::OPTION, false ) ) {
			add_option( self::OPTION, self::defaults(), '', 'no' );
		}
	}

	/**
	 * پاکسازی ورودی. هر مقدار خارج از فهرست مجاز به «خاموش» برمی‌گردد تا یک
	 * مقدار ناخواسته هرگز باعث نمایش بخشی ناقص در سایت نشود.
	 *
	 * @param mixed $raw ورودی خام.
	 * @return array<string,mixed>
	 */
	public static function sanitize( $raw ) {
		if ( ! is_array( $raw ) ) {
			return self::defaults();
		}

		$mode = isset( $raw['mode'] ) ? $raw['mode'] : null;
		$mode = is_string( $mode ) ? trim( $mode ) : '';

		if ( ! array_key_exists( $mode, self::modes() ) ) {
			$mode = self::MODE_OFF;
		}

		return array(
			'schema_version' => self::SCHEMA_VERSION,
			'mode'           => $mode,
			'min_recommend'  => self::sanitize_min_recommend( $raw ),
		);
	}

	/**
	 * پاک‌سازی آستانه‌ی کیفیت.
	 *
	 * هر مقدار نامعتبر (خالی، غیرعددی، خارج از بازه) به پیش‌فرض برمی‌گردد تا
	 * یک ورودی ناخواسته باعث پنهان شدن کل بخش در سایت نشود.
	 *
	 * @param array<string,mixed> $raw ورودی خام.
	 * @return int
	 */
	private static function sanitize_min_recommend( array $raw ) {
		if ( ! array_key_exists( 'min_recommend', $raw ) ) {
			// درخواست‌هایی که فقط حالت را ذخیره می‌کنند، آستانه را پاک نکنند.
			return self::stored_min_recommend();
		}

		$value = $raw['min_recommend'];

		if ( is_string( $value ) ) {
			// ارقام فارسی/عربی و فاصله‌های اضافی هم پذیرفته شوند.
			$value = str_replace(
				array( '۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹', '٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩' ),
				array( '0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9' ),
				trim( $value )
			);
		}

		if ( ! is_numeric( $value ) ) {
			return self::DEFAULT_MIN_RECOMMEND;
		}

		$percent = (int) round( (float) $value );

		if ( $percent < 0 || $percent > self::MAX_MIN_RECOMMEND ) {
			return self::DEFAULT_MIN_RECOMMEND;
		}

		return $percent;
	}

	/**
	 * آستانه‌ی ذخیره‌شده‌ی فعلی (برای ذخیره‌های جزئی).
	 *
	 * @return int
	 */
	private static function stored_min_recommend() {
		$stored = get_option( self::OPTION, null );

		if ( ! is_array( $stored ) || ! array_key_exists( 'min_recommend', $stored ) || ! is_numeric( $stored['min_recommend'] ) ) {
			return self::DEFAULT_MIN_RECOMMEND;
		}

		$value = (int) round( (float) $stored['min_recommend'] );

		if ( $value < 0 || $value > self::MAX_MIN_RECOMMEND ) {
			return self::DEFAULT_MIN_RECOMMEND;
		}

		return $value;
	}

	/**
	 * ذخیره‌ی اتمیک تنظیمات پاک‌شده.
	 *
	 * @param mixed $raw ورودی خام.
	 * @return array<string,mixed>
	 */
	public static function save( $raw ) {
		$normalized = self::sanitize( $raw );

		if ( false === get_option( self::OPTION, false ) ) {
			add_option( self::OPTION, $normalized, '', 'no' );
		} else {
			update_option( self::OPTION, $normalized, false );
		}

		return $normalized;
	}

	/**
	 * خواندن همه‌ی تنظیمات با نرمال‌سازی زمان خواندن.
	 *
	 * @return array<string,mixed>
	 */
	public static function get_all() {
		$stored = get_option( self::OPTION, null );

		if ( ! is_array( $stored ) ) {
			return self::defaults();
		}

		return self::sanitize( $stored );
	}

	/**
	 * حالت فعلی نمایش.
	 *
	 * @return string
	 */
	public static function get_mode() {
		$settings = self::get_all();
		return isset( $settings['mode'] ) ? $settings['mode'] : self::MODE_OFF;
	}

	/**
	 * آیا بخش خلاصه باید نمایش داده شود؟
	 *
	 * @return bool
	 */
	public static function is_enabled() {
		return self::MODE_OFF !== self::get_mode();
	}

	/**
	 * آیا نقاط قوت و ضعف هم نمایش داده شوند؟
	 *
	 * @return bool
	 */
	public static function shows_pros_cons() {
		return self::MODE_SUMMARY_PROS_CONS === self::get_mode();
	}

	/**
	 * آستانه‌ی کیفیت: حداقل درصد پیشنهاد برای نمایش خلاصه.
	 *
	 * @return int عددی بین ۰ تا ۱۰۰؛ ۰ یعنی قاعده خاموش است.
	 */
	public static function min_recommend() {
		$settings = self::get_all();
		$value    = isset( $settings['min_recommend'] ) ? (int) $settings['min_recommend'] : self::DEFAULT_MIN_RECOMMEND;

		if ( $value < 0 || $value > self::MAX_MIN_RECOMMEND ) {
			return self::DEFAULT_MIN_RECOMMEND;
		}

		return $value;
	}

	/**
	 * آیا قاعده‌ی آستانه‌ی کیفیت فعال است؟
	 *
	 * @return bool
	 */
	public static function has_min_recommend() {
		return self::min_recommend() > 0;
	}

	/**
	 * نشانی سرویس (فقط https و فقط مقدار constant/فیلتر).
	 *
	 * @return string
	 */
	public static function api_endpoint() {
		$endpoint = (string) apply_filters(
			'ts_comments_overview_api_endpoint',
			defined( 'TS_COMMENTS_OVERVIEW_API_ENDPOINT' ) ? TS_COMMENTS_OVERVIEW_API_ENDPOINT : ''
		);

		$endpoint = trim( $endpoint );
		$parts    = wp_parse_url( $endpoint );

		if ( ! is_array( $parts ) || empty( $parts['host'] ) || empty( $parts['scheme'] ) ) {
			return '';
		}

		if ( 'https' !== strtolower( $parts['scheme'] ) ) {
			return '';
		}

		return $endpoint;
	}

	/**
	 * توکن سرویس. تنها منبع مجاز: ثابت wp-config.php یا فیلتر.
	 *
	 * فیلتر فقط برای محیط توسعه/تست است؛ مقدار پیش‌فرض غیرفعال است تا فیلتر
	 * ناخواسته‌ی یک افزونه‌ی دیگر نتواند توکن را جعل کند.
	 *
	 * @return string
	 */
	public static function api_token() {
		$token = defined( 'TS_COMMENTS_OVERVIEW_API_TOKEN' ) ? (string) TS_COMMENTS_OVERVIEW_API_TOKEN : '';
		$token = trim( $token );

		if ( self::is_placeholder_token( $token ) ) {
			$token = '';
		}

		/**
		 * امکان تزریق توکن در محیط تست.
		 *
		 * @param string $token توکن فعلی.
		 */
		return trim( (string) apply_filters( 'ts_comments_overview_api_token', $token ) );
	}

	/**
	 * تشخیص مقدار جای‌نگهدار (تا بخش خلاصه با توکن جعلی نمایش داده نشود).
	 *
	 * @param string $token مقدار توکن.
	 * @return bool
	 */
	public static function is_placeholder_token( $token ) {
		if ( '' === $token ) {
			return true;
		}

		$needles = array( 'PUT-YOUR', 'YOUR-TOKEN', 'YOUR_TOKEN', 'CHANGEME', 'CHANGE-ME', 'REPLACE-ME', 'XXX' );

		foreach ( $needles as $needle ) {
			if ( false !== stripos( $token, $needle ) ) {
				return true;
			}
		}

		// توکن خیلی کوتاه عملاً معتبر نیست.
		return strlen( $token ) < 8;
	}

	/**
	 * آیا افزونه آماده‌ی نمایش است؟ (هم روشن، هم پیکربندی‌شده)
	 *
	 * @return bool
	 */
	public static function is_configured() {
		return '' !== self::api_token() && '' !== self::api_endpoint();
	}

	/**
	 * مدت اعتبار کش موفق.
	 *
	 * @return int
	 */
	public static function cache_ttl() {
		return max( 0, (int) apply_filters( 'ts_comments_overview_cache_ttl', (int) TS_COMMENTS_OVERVIEW_CACHE_TTL ) );
	}

	/**
	 * مدت اعتبار کش ناموفق.
	 *
	 * @return int
	 */
	public static function error_ttl() {
		return max( 0, (int) apply_filters( 'ts_comments_overview_error_ttl', (int) TS_COMMENTS_OVERVIEW_ERROR_TTL ) );
	}
}
