<?php
/**
 * تست‌های کلاینت API: درخواست، هدر توکن، کش، کش خطا و پاک‌سازی کش.
 *
 * @package TS_Comments_Overview
 */

$GLOBALS['ts_co_options'] = array();
TS_Comments_Overview_Settings::save( array( 'mode' => TS_Comments_Overview_Settings::MODE_SUMMARY ) );

ts_co_reset_environment();
ts_co_set_http_response( 200, ts_co_fixture_payload() );

$result = TS_Comments_Overview_API::get( 4321 );

ts_co_assert_false( is_wp_error( $result ), 'درخواست موفق خطا برنمی‌گرداند' );
ts_co_assert_same( 1, ts_co_http_call_count(), 'برای هر محصول یک درخواست HTTP زده می‌شود' );

$call = ts_co_last_http_call();
ts_co_assert_contains( 'woocommerce_id=4321', $call['url'], 'شناسه محصول در query string ارسال می‌شود' );
ts_co_assert_contains( 'https://mytsapp.ir/api/external/comments-analysis/', $call['url'], 'درخواست به endpoint اعلام‌شده می‌رود' );
ts_co_assert_same( TS_COMMENTS_OVERVIEW_API_TOKEN, $call['args']['headers']['X-API-Token'], 'هدر X-API-Token از wp-config.php فرستاده می‌شود' );
ts_co_assert_same( 'application/json', $call['args']['headers']['Accept'], 'هدر Accept صحیح است' );
ts_co_assert_same( 0, $call['args']['redirection'], 'ریدایرکت دنبال نمی‌شود' );
ts_co_assert_true( $call['args']['timeout'] > 0 && $call['args']['timeout'] <= 10, 'مهلت درخواست محدود است' );
ts_co_assert_contains(
	'TS-Comments-Overview/' . TS_COMMENTS_OVERVIEW_VERSION,
	(string) $call['args']['user-agent'],
	'نسخه‌ی افزونه در User-Agent درخواست می‌آید'
);
ts_co_assert_true( isset( $result['data']['summary'] ), 'داده‌ی نرمال‌شده در پاسخ کش‌شدنی برمی‌گردد' );

// کش: درخواست دوم شبکه‌ای نمی‌خورد.
$second = TS_Comments_Overview_API::get( 4321 );
ts_co_assert_same( 1, ts_co_http_call_count(), 'درخواست دوم از حافظه‌ی موقت پاسخ می‌گیرد' );
ts_co_assert_same( $result['data']['summary'], $second['data']['summary'], 'داده‌ی کش‌شده همان داده‌ی قبلی است' );

// force = نادیده گرفتن کش.
$forced = TS_Comments_Overview_API::get( 4321, true );
ts_co_assert_same( 2, ts_co_http_call_count(), 'فراخوانی با force کش را نادیده می‌گیرد' );
ts_co_assert_true( ! is_wp_error( $forced ), 'فراخوانی با force هم موفق است' );

// پاک‌سازی کش یک محصول.
TS_Comments_Overview_API::flush( 4321 );
TS_Comments_Overview_API::get( 4321 );
ts_co_assert_same( 3, ts_co_http_call_count(), 'پس از پاک کردن کش، درخواست دوباره زده می‌شود' );

// خطای سرویس: کوتاه‌مدت کش می‌شود تا صفحه‌ی محصول کند نشود.
ts_co_reset_environment();
ts_co_set_http_response( 401, array( 'detail' => 'Authentication credentials were not provided.' ) );

$unauthorized = TS_Comments_Overview_API::get( 555 );
ts_co_assert_true( is_wp_error( $unauthorized ), 'توکن نامعتبر خطا برمی‌گرداند' );
ts_co_assert_same( 'ts_comments_overview_http_error', $unauthorized->get_error_code(), 'کد خطای HTTP ثبت می‌شود' );
ts_co_assert_contains( '401', $unauthorized->get_error_message(), 'پیام خطا کد وضعیت را نشان می‌دهد' );

TS_Comments_Overview_API::get( 555 );
ts_co_assert_same( 1, ts_co_http_call_count(), 'خطا هم کش می‌شود و درخواست تکراری زده نمی‌شود' );

ts_co_reset_environment();
ts_co_set_http_failure();
$unreachable = TS_Comments_Overview_API::get( 777 );
ts_co_assert_true( is_wp_error( $unreachable ), 'خطای شبکه به WP_Error تبدیل می‌شود' );
ts_co_assert_same( 'ts_comments_overview_unreachable', $unreachable->get_error_code(), 'کد خطای دسترسی ثبت می‌شود' );

ts_co_reset_environment();
ts_co_set_http_response( 500, array( 'error' => 'boom' ) );
$server_error = TS_Comments_Overview_API::get( 888 );
ts_co_assert_true( is_wp_error( $server_error ), 'خطای ۵۰۰ مدیریت می‌شود' );

// پاسخ معتبر ولی بدون محتوا نباید بخش خالی رندر کند.
ts_co_reset_environment();
ts_co_set_http_response(
	200,
	array(
		'summary'   => '',
		'sentiment' => array(
			'positive' => 0,
			'negative' => 0,
			'neutral'  => 0,
		),
	)
);
$empty = TS_Comments_Overview_API::get( 999 );
ts_co_assert_true( is_wp_error( $empty ), 'پاسخ بدون خلاصه و بدون احساسات، خطا محسوب می‌شود' );
ts_co_assert_same( 'ts_comments_overview_empty', $empty->get_error_code(), 'کد خطای «داده‌ای نیست» ثبت می‌شود' );

// پاسخ نامعتبر (غیر JSON).
ts_co_reset_environment();
ts_co_set_http_response( 200, '<html>gateway error</html>' );
$broken = TS_Comments_Overview_API::get( 1000 );
ts_co_assert_true( is_wp_error( $broken ), 'بدنه‌ی غیر JSON خطا می‌دهد' );

// شناسه‌ی نامعتبر هرگز درخواست نمی‌فرستد.
ts_co_reset_environment();
$invalid = TS_Comments_Overview_API::get( 0 );
ts_co_assert_true( is_wp_error( $invalid ), 'شناسه‌ی نامعتبر خطا می‌دهد' );
ts_co_assert_same( 0, ts_co_http_call_count(), 'برای شناسه‌ی نامعتبر درخواستی ارسال نمی‌شود' );

// بدون توکن (مثلاً روی محیطی که wp-config تنظیم نشده) هیچ درخواستی زده نمی‌شود.
ts_co_reset_environment();
$GLOBALS['ts_co_filters'] = array(
	'ts_comments_overview_api_token' => array(
		static function () {
			return '';
		},
	),
);
$unconfigured = TS_Comments_Overview_API::get( 123 );
ts_co_assert_true( is_wp_error( $unconfigured ), 'بدون توکن، خطای پیکربندی برمی‌گردد' );
ts_co_assert_same( 'ts_comments_overview_not_configured', $unconfigured->get_error_code(), 'کد خطای پیکربندی درست است' );
ts_co_assert_same( 0, ts_co_http_call_count(), 'بدون توکن هیچ درخواستی به بیرون ارسال نمی‌شود' );
$GLOBALS['ts_co_filters'] = array();

// ایندکس کش و پاک‌سازی گروهی.
ts_co_reset_environment();
ts_co_set_http_response( 200, ts_co_fixture_payload() );
TS_Comments_Overview_API::get( 101 );
TS_Comments_Overview_API::get( 202 );
ts_co_assert_same( array( 101, 202 ), TS_Comments_Overview_API::index(), 'شناسه‌های کش‌شده در ایندکس ثبت می‌شوند' );

$flushed = TS_Comments_Overview_API::flush_all();
ts_co_assert_same( 2, $flushed, 'پاک‌سازی گروهی همه‌ی موارد ایندکس را پاک می‌کند' );
ts_co_assert_same( array(), TS_Comments_Overview_API::index(), 'ایندکس پس از پاک‌سازی خالی می‌شود' );

TS_Comments_Overview_API::get( 101 );
ts_co_assert_true( ts_co_http_call_count() > 0, 'پس از پاک‌سازی گروهی، داده دوباره از سرویس خوانده می‌شود' );

// ابزار بررسی پیشخوان: بدون نوشتن در کش.
ts_co_reset_environment();
ts_co_set_http_response( 200, ts_co_fixture_payload() );
$live = TS_Comments_Overview_API::get_live( 303 );
ts_co_assert_true( ! is_wp_error( $live ), 'بررسی زنده موفق است' );
ts_co_assert_true( isset( $live['raw'] ), 'بررسی زنده پاسخ خام را هم برمی‌گرداند' );
ts_co_assert_same( array(), TS_Comments_Overview_API::index(), 'بررسی زنده چیزی در کش نمی‌نویسد' );
