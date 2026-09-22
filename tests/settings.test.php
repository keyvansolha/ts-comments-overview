<?php
/**
 * تست‌های تنظیمات و پیکربندی.
 *
 * @package TS_Comments_Overview
 */

ts_co_reset_environment();
$GLOBALS['ts_co_options'] = array();

ts_co_assert_same(
	TS_Comments_Overview_Settings::MODE_OFF,
	TS_Comments_Overview_Settings::get_mode(),
	'حالت پیش‌فرض خاموش است'
);
ts_co_assert_false( TS_Comments_Overview_Settings::is_enabled(), 'پیش‌فرض غیرفعال است' );
ts_co_assert_false( TS_Comments_Overview_Settings::shows_pros_cons(), 'پیش‌فرض نقاط قوت/ضعف ندارد' );

$saved = TS_Comments_Overview_Settings::save( array( 'mode' => TS_Comments_Overview_Settings::MODE_SUMMARY_PROS_CONS ) );
ts_co_assert_same( TS_Comments_Overview_Settings::MODE_SUMMARY_PROS_CONS, $saved['mode'], 'ذخیره‌ی حالت خلاصه + قوت/ضعف' );
ts_co_assert_true( TS_Comments_Overview_Settings::is_enabled(), 'حالت انتخاب‌شده فعال است' );
ts_co_assert_true( TS_Comments_Overview_Settings::shows_pros_cons(), 'حالت انتخاب‌شده قوت/ضعف را نشان می‌دهد' );

// مقدار نامعتبر هرگز به سایت راه پیدا نمی‌کند.
$saved = TS_Comments_Overview_Settings::save( array( 'mode' => 'evil"><script>' ) );
ts_co_assert_same( TS_Comments_Overview_Settings::MODE_OFF, $saved['mode'], 'حالت نامعتبر به خاموش برمی‌گردد' );

$saved = TS_Comments_Overview_Settings::save( 'not-an-array' );
ts_co_assert_same( TS_Comments_Overview_Settings::MODE_OFF, $saved['mode'], 'ورودی غیرآرایه به خاموش برمی‌گردد' );

$saved = TS_Comments_Overview_Settings::save( array( 'mode' => TS_Comments_Overview_Settings::MODE_SUMMARY ) );
ts_co_assert_same( TS_Comments_Overview_Settings::MODE_SUMMARY, $saved['mode'], 'ذخیره‌ی حالت خلاصه' );
ts_co_assert_false( TS_Comments_Overview_Settings::shows_pros_cons(), 'حالت خلاصه، قوت/ضعف نشان نمی‌دهد' );

ts_co_assert_same( 3, count( TS_Comments_Overview_Settings::modes() ), 'فقط سه حالت مجاز وجود دارد' );
ts_co_assert_true(
	array_key_exists( TS_Comments_Overview_Settings::MODE_OFF, TS_Comments_Overview_Settings::modes() )
	&& array_key_exists( TS_Comments_Overview_Settings::MODE_SUMMARY, TS_Comments_Overview_Settings::modes() )
	&& array_key_exists( TS_Comments_Overview_Settings::MODE_SUMMARY_PROS_CONS, TS_Comments_Overview_Settings::modes() ),
	'سه گزینه‌ی خاموش / خلاصه / خلاصه+قوت‌وضعف در پیشخوان ثبت شده‌اند'
);

// توکن: تنها از ثابت wp-config.php (یا فیلتر) خوانده می‌شود.
ts_co_assert_same( TS_COMMENTS_OVERVIEW_API_TOKEN, TS_Comments_Overview_Settings::api_token(), 'توکن از ثابت wp-config.php خوانده می‌شود' );
ts_co_assert_true( TS_Comments_Overview_Settings::is_configured(), 'با توکن معتبر، اتصال کامل است' );

ts_co_assert_true( TS_Comments_Overview_Settings::is_placeholder_token( 'PUT-YOUR-X-API-TOKEN-HERE' ), 'جای‌نگهدار به‌عنوان توکن نامعتبر شناخته می‌شود' );
ts_co_assert_true( TS_Comments_Overview_Settings::is_placeholder_token( '' ), 'توکن خالی نامعتبر است' );
ts_co_assert_true( TS_Comments_Overview_Settings::is_placeholder_token( 'short' ), 'توکن کوتاه نامعتبر است' );
ts_co_assert_false( TS_Comments_Overview_Settings::is_placeholder_token( 'a1b2c3d4e5f6g7h8i9j0' ), 'توکن معتبر پذیرفته می‌شود' );

// نشانی سرویس: فقط https.
ts_co_assert_same(
	'https://mytsapp.ir/api/external/comments-analysis/',
	TS_Comments_Overview_Settings::api_endpoint(),
	'نشانی سرویس همان endpoint اعلام‌شده است'
);

$GLOBALS['ts_co_filters'] = array(
	'ts_comments_overview_api_endpoint' => array(
		static function () {
			return 'http://insecure.test/api/';
		},
	),
);
ts_co_assert_same( '', TS_Comments_Overview_Settings::api_endpoint(), 'نشانی http رد می‌شود' );
$GLOBALS['ts_co_filters'] = array();

ts_co_assert_same( 21600, TS_Comments_Overview_Settings::cache_ttl(), 'مدت کش موفق ۶ ساعت است' );
ts_co_assert_same( 900, TS_Comments_Overview_Settings::error_ttl(), 'مدت کش خطا ۱۵ دقیقه است' );

// هوک قالب با نام مستندشده ثبت شده است.
ts_co_assert_true(
	isset( $GLOBALS['ts_co_actions']['plugins_loaded'] ),
	'افزونه در plugins_loaded راه‌اندازی می‌شود'
);

ts_comments_overview_bootstrap();
ts_co_assert_true(
	! empty( $GLOBALS['ts_co_actions'][ TS_COMMENTS_OVERVIEW_HOOK ] ),
	'رندر بخش خلاصه روی هوک قالب ثبت شده است'
);
