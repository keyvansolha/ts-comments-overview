<?php
/**
 * نمایش بخش «خلاصه نظرات کاربران» در بخش نظرات صفحه محصول.
 *
 * قالب در دو نقطه (دسکتاپ و موبایل) هوک TS_COMMENTS_OVERVIEW_HOOK را اجرا
 * می‌کند. اگر افزونه غیرفعال باشد یا حالت «خاموش» باشد، این کلاس هیچ خروجی‌ای
 * تولید نمی‌کند و قالب دست‌نخورده می‌ماند.
 *
 * @package TS_Comments_Overview
 */

defined( 'ABSPATH' ) || exit;

/**
 * رندر بخش خلاصه نظرات.
 */
final class TS_Comments_Overview_Render {

	/** هندل استایل بخش خلاصه. */
	const STYLE_HANDLE = 'ts-comments-overview';

	/** هندل اسکریپت نشان‌گذاری نوار بخش‌ها. */
	const SCRIPT_HANDLE = 'ts-comments-overview-nav';

	/**
	 * ثبت هوک‌ها.
	 *
	 * @return void
	 */
	public static function register_hooks() {
		$hook = defined( 'TS_COMMENTS_OVERVIEW_HOOK' ) ? TS_COMMENTS_OVERVIEW_HOOK : 'wbs_product_comments_overview';

		add_action( $hook, array( __CLASS__, 'render' ), 10, 1 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ), 20 );
	}

	/**
	 * صف‌بندی دارایی‌ها فقط روی صفحه‌ی محصول و فقط وقتی بخش فعال است.
	 *
	 * استایل به لایه‌ی معنایی قالب (amazing-theme-system) وابسته می‌شود تا
	 * توکن‌های --surface-* / --text-* / --state-* همیشه موجود باشند.
	 *
	 * @return void
	 */
	public static function enqueue_assets() {
		if ( ! self::should_render() || ! function_exists( 'is_product' ) || ! is_product() ) {
			return;
		}

		$dependencies = wp_style_is( 'amazing-theme-system', 'registered' ) || wp_style_is( 'amazing-theme-system', 'enqueued' )
			? array( 'amazing-theme-system' )
			: array();

		$style_path = TS_COMMENTS_OVERVIEW_PATH . 'assets/css/comments-overview.css';
		$version    = file_exists( $style_path ) ? (string) filemtime( $style_path ) : TS_COMMENTS_OVERVIEW_VERSION;

		wp_enqueue_style(
			self::STYLE_HANDLE,
			TS_COMMENTS_OVERVIEW_URL . 'assets/css/comments-overview.css',
			$dependencies,
			$version
		);

		/*
		 * نشان‌گذاری ورودی «نظرات» در نوار بخش‌های صفحه محصول.
		 *
		 * اگر قالب این اسکریپت را لازم نداشته باشد، فایل وجود ندارد و هیچ
		 * درخواستی به سرور اضافه نمی‌شود.
		 */
		$script_path = TS_COMMENTS_OVERVIEW_PATH . 'assets/js/comments-overview.js';

		if ( ! file_exists( $script_path ) ) {
			return;
		}

		wp_enqueue_script(
			self::SCRIPT_HANDLE,
			TS_COMMENTS_OVERVIEW_URL . 'assets/js/comments-overview.js',
			array(),
			(string) filemtime( $script_path ),
			true
		);
	}

	/**
	 * آیا شرط‌های نمایش برقرار است؟
	 *
	 * @return bool
	 */
	public static function should_render() {
		return TS_Comments_Overview_Settings::is_enabled() && TS_Comments_Overview_Settings::is_configured();
	}

	/**
	 * چاپ بخش خلاصه (callback هوک قالب).
	 *
	 * @param mixed $product محصول ووکامرس، شناسه‌ی محصول، یا null.
	 * @return void
	 */
	public static function render( $product = null ) {
		$html = self::get_html( $product );

		if ( '' === $html ) {
			return;
		}

		// خروجی در get_html ساخته و در همان‌جا escape شده است.
		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * ساخت HTML بخش خلاصه.
	 *
	 * @param mixed $product محصول ووکامرس، شناسه‌ی محصول، یا null.
	 * @param bool  $force   نادیده گرفتن کش (ابزار پیشخوان).
	 * @return string HTML یا رشته‌ی خالی وقتی چیزی برای نمایش نیست.
	 */
	public static function get_html( $product = null, $force = false ) {
		if ( ! self::should_render() ) {
			return '';
		}

		$product_id = self::resolve_product_id( $product );

		if ( $product_id <= 0 ) {
			return '';
		}

		$analysis = $force
			? TS_Comments_Overview_API::get_live( $product_id )
			: TS_Comments_Overview_API::get( $product_id );

		if ( is_wp_error( $analysis ) || ! isset( $analysis['data'] ) || ! is_array( $analysis['data'] ) ) {
			return '';
		}

		// آستانه‌ی کیفیت: برای محصولی که کاربران از آن راضی نیستند، خلاصه‌ی
		// منفی به کاربر نشان داده نمی‌شود و بخش نظرات مثل قبل می‌ماند.
		if ( ! self::passes_quality_threshold( $analysis['data'] ) ) {
			return '';
		}

		return self::render_template( $analysis['data'] );
	}

	/**
	 * درصدی که مبنای آستانه‌ی کیفیت است.
	 *
	 * اول درصد پیشنهاد سرویس (همان عددی که در کارت بالای خلاصه به کاربر
	 * نشان می‌دهیم) و اگر سرویس آن را نداده بود، سهم نظرهای مثبت از مجموع
	 * احساسات.
	 *
	 * @param array<string,mixed> $data داده‌ی نرمال‌شده‌ی سرویس.
	 * @return int|null عدد ۰ تا ۱۰۰ یا null وقتی هیچ مبنایی وجود ندارد.
	 */
	public static function quality_percent( array $data ) {
		$recommend = isset( $data['recommend_percentage'] ) ? $data['recommend_percentage'] : null;

		if ( is_numeric( $recommend ) ) {
			return self::clamp_percent( (float) $recommend );
		}

		$sentiment = isset( $data['sentiment'] ) && is_array( $data['sentiment'] ) ? $data['sentiment'] : array();
		$positive  = isset( $sentiment['positive'] ) && is_numeric( $sentiment['positive'] ) ? (int) $sentiment['positive'] : null;

		if ( null === $positive ) {
			return null;
		}

		$total = 0;

		foreach ( array( 'positive', 'negative', 'neutral' ) as $key ) {
			if ( isset( $sentiment[ $key ] ) && is_numeric( $sentiment[ $key ] ) ) {
				$total += (int) $sentiment[ $key ];
			}
		}

		if ( $total <= 0 ) {
			return null;
		}

		return self::clamp_percent( ( $positive / $total ) * 100 );
	}

	/**
	 * آیا داده از آستانه‌ی کیفیت رد می‌شود؟
	 *
	 * اگر آستانه خاموش باشد (۰) یا سرویس هیچ عددی برای تصمیم نداده باشد،
	 * بخش خلاصه نمایش داده می‌شود؛ پنهان کردن پیش‌فرض نیست.
	 *
	 * @param array<string,mixed> $data داده‌ی نرمال‌شده‌ی سرویس.
	 * @return bool
	 */
	public static function passes_quality_threshold( array $data ) {
		if ( ! TS_Comments_Overview_Settings::has_min_recommend() ) {
			return true;
		}

		$percent = self::quality_percent( $data );

		if ( null === $percent ) {
			return true;
		}

		return $percent >= TS_Comments_Overview_Settings::min_recommend();
	}

	/**
	 * محدود کردن درصد به بازه‌ی ۰ تا ۱۰۰.
	 *
	 * @param float $value عدد خام.
	 * @return int
	 */
	private static function clamp_percent( $value ) {
		$percent = (int) round( (float) $value );

		if ( $percent < 0 ) {
			return 0;
		}

		if ( $percent > 100 ) {
			return 100;
		}

		return $percent;
	}

	/**
	 * استخراج شناسه‌ی محصول.
	 *
	 * برای محصول متغیر، شناسه‌ی والد ارسال می‌شود؛ چون نظرات و تحلیل سرویس در
	 * سطح محصول والد معنا دارند.
	 *
	 * @param mixed $product محصول، شناسه، یا null.
	 * @return int
	 */
	public static function resolve_product_id( $product = null ) {
		if ( is_object( $product ) && method_exists( $product, 'get_id' ) ) {
			$product_id = (int) $product->get_id();
		} elseif ( is_numeric( $product ) ) {
			$product_id = (int) $product;
		} else {
			$product_id = 0;
		}

		if ( $product_id <= 0 && function_exists( 'get_the_ID' ) ) {
			$product_id = (int) get_the_ID();
		}

		if ( $product_id <= 0 && isset( $GLOBALS['product'] ) && is_object( $GLOBALS['product'] ) && method_exists( $GLOBALS['product'], 'get_id' ) ) {
			$product_id = (int) $GLOBALS['product']->get_id();
		}

		return $product_id > 0 ? $product_id : 0;
	}

	/**
	 * رندر فایل قالب در بافر.
	 *
	 * @param array<string,mixed> $data ساختار نرمال‌شده‌ی سرویس.
	 * @return string
	 */
	public static function render_template( array $data ) {
		$template = TS_COMMENTS_OVERVIEW_PATH . 'templates/section.php';

		if ( ! file_exists( $template ) ) {
			return '';
		}

		$mode = TS_Comments_Overview_Settings::get_mode();

		ob_start();
		include $template;
		$html = ob_get_clean();

		return is_string( $html ) ? trim( $html ) : '';
	}

	/**
	 * درصد گردشده برای نوار احساسات.
	 *
	 * @param int $part  شمارش یک حالت.
	 * @param int $total مجموع.
	 * @return int
	 */
	public static function percentage( $part, $total ) {
		$total = (int) $total;
		$part  = (int) $part;

		if ( $total <= 0 ) {
			return 0;
		}

		return (int) round( ( $part / $total ) * 100 );
	}

	/**
	 * قالب‌بندی عدد برای نمایش (جداکننده‌ی هزارگان بر اساس زبان سایت).
	 *
	 * @param int|null $number عدد.
	 * @return string
	 */
	public static function number_label( $number ) {
		if ( null === $number ) {
			return '';
		}

		if ( function_exists( 'number_format_i18n' ) ) {
			return number_format_i18n( (int) $number );
		}

		return (string) (int) $number;
	}

	/**
	 * قالب‌بندی امتیاز (بدون صفر اضافه).
	 *
	 * @param float|null $rating امتیاز.
	 * @return string
	 */
	public static function rating_label( $rating ) {
		if ( null === $rating ) {
			return '';
		}

		$label = number_format( (float) $rating, 1, '.', '' );

		return str_replace( '.0', '', $label );
	}
}
