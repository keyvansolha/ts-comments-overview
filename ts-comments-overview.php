<?php
/**
 * Plugin Name:       TS Comments Overview
 * Plugin URI:        https://www.tehranspeaker.com/
 * Description:       نمایش خلاصه‌ی نظرات کاربران (تحلیل سرویس mytsapp.ir) در بخش نظرات صفحه محصول تهران‌اسپیکر، با حالت‌های خاموش/خلاصه/خلاصه + نقاط قوت و ضعف، آستانه‌ی کیفیت برای پنهان کردن خلاصه‌ی محصول‌های ضعیف، و نشان‌گذاری ورودی «نظرات» در نوار بخش‌های صفحه.
 * Version:           0.5.0
 * Author:            Keyvan Havestin
 * Text Domain:       ts-comments-overview
 * Domain Path:       /languages
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Requires Plugins:  woocommerce
 *
 * @package TS_Comments_Overview
 *
 * ---------------------------------------------------------------------------
 * قرارداد با قالب Amazing
 * ---------------------------------------------------------------------------
 * ۱) نمایش بخش خلاصه — قالب در دو فایل زیر، دقیقاً پیش از فهرست نظرات، هوک
 *    زیر را اجرا می‌کند:
 *   - lib/Product/template/desktop/comments.php
 *   - lib/Product/template/mobile/panels/comments.php
 *
 *     do_action( 'wbs_product_comments_overview', $product );
 *
 * ۲) نشان‌گذاری ورودی «نظرات» — قالب پیش از چاپ تب/لینک نظرات، این تابع را
 *    می‌پرسد تا کلاس خودش (has-comments-summary) را بگذارد:
 *
 *     function_exists( 'ts_comments_overview_shows_summary' ) && ts_comments_overview_shows_summary()
 *
 *    استایل آن حلقه (حلقه‌ی رنگین‌کمانی) در خود قالب است، نه این افزونه؛
 *    افزونه فقط می‌گوید برای این محصول خلاصه‌ای برای نمایش هست یا نه. چون
 *    تصمیم از همان داده‌ی get_html می‌آید، نوار و بخش خلاصه هرگز ناهماهنگ
 *    نمی‌شوند.
 *
 * نام هوک با ثابت TS_COMMENTS_OVERVIEW_HOOK قابل بازنویسی از wp-config.php است.
 * اگر افزونه غیرفعال باشد، نه این هوک کاری می‌کند و نه این تابع وجود دارد؛
 * قالب مثل قبل کار می‌کند.
 */

defined( 'ABSPATH' ) || exit;

/*
|--------------------------------------------------------------------------
| نسخه و مسیرها
|--------------------------------------------------------------------------
|
| نسخه از هدر خود افزونه خوانده می‌شود تا نسخه‌ی دارایی‌های CSS با هدر عقب
| نیفتد (همان الگوی ts-bnpl و tehranspeaker-audio-lab).
|
*/
$ts_comments_overview_header = get_file_data( __FILE__, array( 'version' => 'Version' ), 'plugin' );

define(
	'TS_COMMENTS_OVERVIEW_VERSION',
	! empty( $ts_comments_overview_header['version'] ) ? $ts_comments_overview_header['version'] : '0.0.0'
);
unset( $ts_comments_overview_header );

define( 'TS_COMMENTS_OVERVIEW_FILE', __FILE__ );
define( 'TS_COMMENTS_OVERVIEW_PATH', plugin_dir_path( __FILE__ ) );
define( 'TS_COMMENTS_OVERVIEW_URL', plugin_dir_url( __FILE__ ) );
define( 'TS_COMMENTS_OVERVIEW_BASENAME', plugin_basename( __FILE__ ) );

/*
|--------------------------------------------------------------------------
| پیش‌فرض‌های قابل بازنویسی از wp-config.php
|--------------------------------------------------------------------------
|
| همه‌ی مقادیر با defined() محافظت شده‌اند تا بتوان آن‌ها را پیش از بارگذاری
| افزونه در wp-config.php تعریف کرد، بدون دست زدن به کد افزونه.
|
*/

/** نشانی سرویس تحلیل نظرات. */
defined( 'TS_COMMENTS_OVERVIEW_API_ENDPOINT' ) || define(
	'TS_COMMENTS_OVERVIEW_API_ENDPOINT',
	'https://mytsapp.ir/api/external/comments-analysis/'
);

/**
 * توکن هدر X-API-Token.
 *
 * مقدار واقعی را در wp-config.php بگذارید:
 *     define( 'TS_COMMENTS_OVERVIEW_API_TOKEN', 'توکن-واقعی' );
 *
 * توکن عمداً هیچ‌وقت در دیتابیس ذخیره نمی‌شود (نه در تنظیمات، نه در کش) تا در
 * بکاپ‌ها و خروجی‌های دیباگ بیرون نیفتد. مقدار پیش‌فرض یک جای‌نگهدار است و تا
 * زمانی که مقدار واقعی جایگزین نشود، بخش خلاصه در سایت نمایش داده نمی‌شود.
 */
defined( 'TS_COMMENTS_OVERVIEW_API_TOKEN' ) || define( 'TS_COMMENTS_OVERVIEW_API_TOKEN', 'PUT-YOUR-X-API-TOKEN-HERE' );

/** نام هوکی که قالب برای نمایش این بخش صدا می‌زند. */
defined( 'TS_COMMENTS_OVERVIEW_HOOK' ) || define( 'TS_COMMENTS_OVERVIEW_HOOK', 'wbs_product_comments_overview' );

/** مدت اعتبار کش موفق (ثانیه). پیش‌فرض: ۶ ساعت. */
defined( 'TS_COMMENTS_OVERVIEW_CACHE_TTL' ) || define( 'TS_COMMENTS_OVERVIEW_CACHE_TTL', 21600 );

/** مدت اعتبار کش ناموفق (ثانیه). پیش‌فرض: ۱۵ دقیقه. */
defined( 'TS_COMMENTS_OVERVIEW_ERROR_TTL' ) || define( 'TS_COMMENTS_OVERVIEW_ERROR_TTL', 900 );

/*
|--------------------------------------------------------------------------
| بارگذاری
|--------------------------------------------------------------------------
|
| ترتیب مهم است: تنظیمات ← پیلود ← کلاینت API ← نمایش ← پیشخوان.
|
*/
require_once TS_COMMENTS_OVERVIEW_PATH . 'includes/class-ts-comments-overview-settings.php';
require_once TS_COMMENTS_OVERVIEW_PATH . 'includes/class-ts-comments-overview-payload.php';
require_once TS_COMMENTS_OVERVIEW_PATH . 'includes/class-ts-comments-overview-api.php';
require_once TS_COMMENTS_OVERVIEW_PATH . 'includes/class-ts-comments-overview-render.php';
require_once TS_COMMENTS_OVERVIEW_PATH . 'includes/class-ts-comments-overview-admin.php';

/**
 * راه‌اندازی افزونه.
 *
 * @return void
 */
function ts_comments_overview_bootstrap() {
	TS_Comments_Overview_Settings::register_hooks();
	TS_Comments_Overview_Render::register_hooks();

	if ( is_admin() ) {
		TS_Comments_Overview_Admin::register_hooks();
	}
}
add_action( 'plugins_loaded', 'ts_comments_overview_bootstrap' );

/**
 * ثبت مقدار پیش‌فرض هنگام فعال‌سازی.
 *
 * حالت پیش‌فرض «خاموش» است؛ نمایش خلاصه فقط با انتخاب صریح مدیر روشن می‌شود.
 *
 * @return void
 */
function ts_comments_overview_activate() {
	TS_Comments_Overview_Settings::ensure_defaults();
}
register_activation_hook( __FILE__, 'ts_comments_overview_activate' );

/**
 * آیا برای محصول جاری خلاصه‌ی نظرات نمایش داده می‌شود؟
 *
 * قالب این تابع را صدا می‌زند تا ورودی «نظرات» را نشان‌گذاری کند. همیشه با
 * function_exists بررسی شود تا قالب بدون این افزونه هم کار کند.
 *
 * @param mixed $product محصول، شناسه‌ی محصول، یا null برای محصول جاری.
 * @return bool
 */
function ts_comments_overview_shows_summary( $product = null ) {
	return TS_Comments_Overview_Render::shows_summary( $product );
}

/**
 * پاک کردن کش تحلیل نظرات (ترنزینت‌ها) هنگام غیرفعال‌سازی.
 *
 * @return void
 */
function ts_comments_overview_deactivate() {
	TS_Comments_Overview_API::flush_all();
}
register_deactivation_hook( __FILE__, 'ts_comments_overview_deactivate' );
