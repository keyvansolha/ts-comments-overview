<?php
/**
 * تست‌های پیشخوان: سه گزینه‌ی نمایش، عدم افشای توکن و هشدار پیکربندی.
 *
 * @package TS_Comments_Overview
 */

$GLOBALS['ts_co_options'] = array();
TS_Comments_Overview_Settings::save( array( 'mode' => TS_Comments_Overview_Settings::MODE_OFF ) );
ts_co_reset_environment();

// ---------------------------------------------------------------------------
// صفحه‌ی تنظیمات
// ---------------------------------------------------------------------------

ob_start();
TS_Comments_Overview_Admin::render_page();
$page = (string) ob_get_clean();

ts_co_assert_contains( 'خلاصه نظرات محصولات', $page, 'صفحه‌ی تنظیمات عنوان درست دارد' );
ts_co_assert_contains( 'ts_comments_overview_settings[mode]', $page, 'فرم تنظیمات روی همان آپشن ثبت‌شده است' );
ts_co_assert_contains( 'value="off"', $page, 'گزینه‌ی خاموش در پیشخوان هست' );
ts_co_assert_contains( 'value="summary"', $page, 'گزینه‌ی خلاصه در پیشخوان هست' );
ts_co_assert_contains( 'value="summary_pros_cons"', $page, 'گزینه‌ی خلاصه + قوت/ضعف در پیشخوان هست' );
ts_co_assert_same( 3, substr_count( $page, 'type="radio"' ), 'دقیقاً سه گزینه‌ی رادیویی وجود دارد' );
ts_co_assert_contains( 'checked="checked"', $page, 'حالت فعلی در فرم انتخاب شده است' );

// توکن هرگز نباید در HTML پیشخوان چاپ شود.
ts_co_assert_not_contains( TS_COMMENTS_OVERVIEW_API_TOKEN, $page, 'مقدار توکن در پیشخوان چاپ نمی‌شود' );
ts_co_assert_contains( 'تنظیم شده', $page, 'وضعیت توکن فقط به‌صورت «تنظیم شده/نشده» گزارش می‌شود' );
ts_co_assert_not_contains( 'wp-config.php\', \'test-token', $page, 'هیچ بخشی از توکن در خروجی نیست' );

// ---------------------------------------------------------------------------
// هشدار پیکربندی
// ---------------------------------------------------------------------------

ob_start();
TS_Comments_Overview_Admin::configuration_notice();
$notice = (string) ob_get_clean();
ts_co_assert_same( '', $notice, 'در حالت خاموش هیچ هشداری نمایش داده نمی‌شود' );

TS_Comments_Overview_Settings::save( array( 'mode' => TS_Comments_Overview_Settings::MODE_SUMMARY ) );
ob_start();
TS_Comments_Overview_Admin::configuration_notice();
$notice = (string) ob_get_clean();
ts_co_assert_same( '', $notice, 'با پیکربندی کامل، هشداری نمایش داده نمی‌شود' );

$GLOBALS['ts_co_filters'] = array(
	'ts_comments_overview_api_token' => array(
		static function () {
			return '';
		},
	),
);
ob_start();
TS_Comments_Overview_Admin::configuration_notice();
$notice = (string) ob_get_clean();
$GLOBALS['ts_co_filters'] = array();

ts_co_assert_contains( 'notice-warning', $notice, 'بدون توکن، هشدار پیشخوان نمایش داده می‌شود' );
ts_co_assert_contains( 'wp-config.php', $notice, 'هشدار مسیر تنظیم توکن را توضیح می‌دهد' );
ts_co_assert_not_contains( TS_COMMENTS_OVERVIEW_API_TOKEN, $notice, 'هشدار توکن را افشا نمی‌کند' );

// ---------------------------------------------------------------------------
// پیوندهای افزونه
// ---------------------------------------------------------------------------

$links = TS_Comments_Overview_Admin::action_links( array( 'deactivate' => '<a href="#">غیرفعال</a>' ) );
ts_co_assert_same( 2, count( $links ), 'پیوند تنظیمات به فهرست افزونه‌ها اضافه می‌شود' );
ts_co_assert_contains( 'options-general.php?page=ts-comments-overview', $links[0], 'پیوند تنظیمات به صفحه‌ی درست اشاره می‌کند' );

// ---------------------------------------------------------------------------
// وضعیت یکپارچگی با قالب
// ---------------------------------------------------------------------------

$integration = TS_Comments_Overview_Admin::theme_integration_status();
ts_co_assert_true( $integration['integrated'], 'افزونه یکپارچگی خود با قالب فعال را تشخیص می‌دهد' );
ts_co_assert_same( 2, count( $integration['checked'] ), 'هر دو نقطه‌ی نمایش (دسکتاپ و موبایل) بررسی می‌شوند' );
