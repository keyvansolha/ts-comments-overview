<?php
/**
 * تست‌های نرمال‌سازی پیلود سرویس (مقاوم‌سازی در برابر داده‌ی نامعتبر).
 *
 * @package TS_Comments_Overview
 */

$GLOBALS['ts_co_options'] = array();

$data = TS_Comments_Overview_Payload::normalize( ts_co_fixture_payload() );

ts_co_assert_same( 62, $data['sentiment']['positive'], 'احساسات مثبت خوانده می‌شود' );
ts_co_assert_same( 11, $data['sentiment']['negative'], 'احساسات منفی خوانده می‌شود' );
ts_co_assert_same( 27, $data['sentiment']['neutral'], 'احساسات بی‌طرف خوانده می‌شود' );
ts_co_assert_same( 100, $data['sentiment']['total'], 'مجموع احساسات محاسبه می‌شود' );

ts_co_assert_same( 4.31, $data['rating'], 'overallRating حفظ می‌شود' );
ts_co_assert_same( 135, $data['total_comments'], 'totalComments حفظ می‌شود' );
ts_co_assert_same( 78, $data['recommend_percentage'], 'recommendPercentage حفظ می‌شود' );
ts_co_assert_same( 'ترب', $data['provider_name'], 'providerName حفظ می‌شود' );
ts_co_assert_same( 2, $data['source_provider_count'], 'sourceProviderCount حفظ می‌شود' );
ts_co_assert_same( 3, count( $data['strengths'] ), 'سه نقطه‌ی قوت خوانده می‌شود' );
ts_co_assert_same( 2, count( $data['weaknesses'] ), 'دو نقطه‌ی ضعف خوانده می‌شود' );
ts_co_assert_same( 4, count( $data['topics'] ), 'موضوع‌ها خوانده می‌شوند' );
ts_co_assert_true( true === $data['is_derived'], 'isDerived تشخیص داده می‌شود' );
ts_co_assert_false( $data['stale'], 'stale=false حفظ می‌شود' );

ts_co_assert_same( 2, count( $data['providers'] ), 'providerBreakdown با دو شکل مختلف کلید خوانده می‌شود' );
ts_co_assert_same( 'دیجی‌کالا', $data['providers'][1]['name'], 'نام منبع دوم از کلید name خوانده می‌شود' );
ts_co_assert_same( 51, $data['providers'][1]['comments'], 'تعداد نظر منبع دوم از کلید comments خوانده می‌شود' );

ts_co_assert_true( is_int( $data['updated_at'] ) && $data['updated_at'] > 0, 'updatedAt به زمان یونیکس تبدیل می‌شود' );

// پوشش data و نام کلیدهای snake_case.
$wrapped = TS_Comments_Overview_Payload::normalize( ts_co_fixture_wrapped() );
ts_co_assert_same( 'کاربران در مجموع از این محصول راضی هستند.', $wrapped['summary'], 'پیلود داخل data باز می‌شود' );
ts_co_assert_same( 4.5, $wrapped['rating'], 'overall_rating هم پذیرفته می‌شود' );
ts_co_assert_same( 15, $wrapped['total_comments'], 'comment_count هم پذیرفته می‌شود' );
ts_co_assert_same( 83, $wrapped['recommend_percentage'], 'recommend_percentage هم پذیرفته می‌شود' );

// داده‌ی بی‌معنا نباید خطا بدهد.
$empty = TS_Comments_Overview_Payload::normalize( array( 'summary' => '', 'sentiment' => 'oops', 'topics' => 42 ) );
ts_co_assert_same( '', $empty['summary'], 'خلاصه‌ی خالی می‌ماند' );
ts_co_assert_same( 0, $empty['sentiment']['total'], 'sentiment نامعتبر صفر می‌شود' );
ts_co_assert_same( array(), $empty['topics'], 'topics نامعتبر آرایه‌ی خالی می‌شود' );
ts_co_assert_empty( $empty['rating'], 'امتیاز نامعتبر خالی می‌ماند' );
ts_co_assert_same(
	array(
		array(
			'label'     => 'یک موضوع متنی',
			'count'     => null,
			'direction' => '',
		),
	),
	TS_Comments_Overview_Payload::normalize( array( 'topics' => 'یک موضوع متنی' ) )['topics'],
	'موضوعِ رشته‌ای به آیتم ساختاریافته تبدیل می‌شود'
);

// محدودسازی اعداد.
ts_co_assert_same( 100, TS_Comments_Overview_Payload::to_int( '150%', 0, 100 ), 'درصد بزرگ‌تر از ۱۰۰ محدود می‌شود' );
ts_co_assert_same( 0, TS_Comments_Overview_Payload::to_int( -20, 0, 100 ), 'عدد منفی محدود می‌شود' );
ts_co_assert_empty( TS_Comments_Overview_Payload::to_int( 'abc', 0, 100 ), 'عدد نامعتبر null می‌شود' );
ts_co_assert_same( 1234, TS_Comments_Overview_Payload::to_int( '1,234', 0, 100000 ), 'جداکننده‌ی هزارگان حذف می‌شود' );
ts_co_assert_same( 5.0, TS_Comments_Overview_Payload::to_float( 9, 0, 5 ), 'امتیاز بیش از حد محدود می‌شود' );
ts_co_assert_same( 150, TS_Comments_Overview_Payload::to_int( '۱۵۰٪', 0, 1000 ), 'ارقام فارسی و پسوند درصد پذیرفته می‌شود' );
ts_co_assert_same( 78, TS_Comments_Overview_Payload::to_int( '٬۷۸', 0, 1000 ), 'ارقام فارسی با جداکننده پذیرفته می‌شود' );
ts_co_assert_same( 5.0, TS_Comments_Overview_Payload::to_float( 99, 0, 5 ), 'امتیاز ۹۹ به ۵ محدود می‌شود' );

// فهرست رشته‌ها: آیتم آبجکت با کلید text هم پذیرفته می‌شود.
$list = TS_Comments_Overview_Payload::string_list(
	array(
		'متن ساده',
		array( 'text' => 'متن آبجکتی' ),
		array( 'label' => 'برچسب' ),
		array( 'unrelated' => 'ignored' ),
		123,
	),
	10
);
ts_co_assert_same( array( 'متن ساده', 'متن آبجکتی', 'برچسب', '123' ), $list, 'آیتم‌های مختلف فهرست به رشته تبدیل می‌شوند' );

$list = TS_Comments_Overview_Payload::string_list( "خط اول\nخط دوم", 10 );
ts_co_assert_same( array( 'خط اول', 'خط دوم' ), $list, 'رشته‌ی چندخطی به فهرست تبدیل می‌شود' );

// پاکسازی متن.
$plain = TS_Comments_Overview_Payload::plain( "  کیفیت\u{200C}صدا <b>عالی</b> <script>alert(1)</script> است  ", 200 );
ts_co_assert_same( 'کیفیت‌صدا عالی است', $plain, 'تگ‌ها و محتوای script حذف و نیم‌فاصله حفظ می‌شود' );

$plain = TS_Comments_Overview_Payload::plain( "خط اول\nخط دوم", 200 );
ts_co_assert_same( "خط اول\nخط دوم", $plain, 'شکست خط خلاصه حفظ می‌شود تا فهرست‌ها بسازند' );

$plain = TS_Comments_Overview_Payload::plain( str_repeat( 'ا', 50 ), 10 );
ts_co_assert_same( 10, mb_strlen( $plain, 'UTF-8' ), 'بریدن متن طولانی چندبایتی را خراب نمی‌کند' );

$plain = TS_Comments_Overview_Payload::plain( "متن\x80نامعتبر", 100 );
ts_co_assert_true( mb_check_encoding( $plain, 'UTF-8' ), 'بایت نامعتبر UTF-8 متن را خراب نمی‌کند' );

// قالب‌بندی امن متن خلاصه.
$formatted = TS_Comments_Overview_Payload::format_text( "خط اول\n- مورد اول\n- مورد دوم\n**پررنگ**" );
ts_co_assert_contains( '<p>خط اول</p>', $formatted, 'خط معمولی به پاراگراف تبدیل می‌شود' );
ts_co_assert_contains( '<ul><li>مورد اول</li><li>مورد دوم</li></ul>', $formatted, 'خطوط خط‌تیره‌دار به فهرست تبدیل می‌شوند' );
ts_co_assert_contains( '<strong>پررنگ</strong>', $formatted, 'متن پررنگ پشتیبانی می‌شود' );

$xss = TS_Comments_Overview_Payload::format_text( "</p><script>alert('xss')</script><img src=x onerror=alert(1)>\n- <svg onload=alert(1)>" );
ts_co_assert_not_contains( '<script', $xss, 'تگ script در خلاصه ساخته نمی‌شود' );
ts_co_assert_not_contains( '<img', $xss, 'تگ img در خلاصه ساخته نمی‌شود' );
ts_co_assert_not_contains( '<svg', $xss, 'تگ svg در خلاصه ساخته نمی‌شود' );
ts_co_assert_contains( '&lt;script&gt;', $xss, 'متن خطرناک به‌صورت escape‌شده نمایش داده می‌شود' );
ts_co_assert_contains( '&lt;img', $xss, 'تگ img خطرناک به‌صورت escape‌شده نمایش داده می‌شود' );

// فقط تگ‌های مجاز باید در خروجی باقی بمانند و هیچ attribute رویدادی نباید ساخته شود.
$stripped = preg_replace( '#</?(?:p|ul|ol|li|strong|b|em|i|span|br)\b[^>]*>#i', '', $xss );
ts_co_assert_not_contains( '<', (string) $stripped, 'خارج از فهرست مجاز، هیچ برچسبی در خروجی ساخته نمی‌شود' );
ts_co_assert_same( 0, preg_match( '/<[a-z]+[^>]*\son[a-z]+\s*=/i', $xss ), 'هیچ تگی با attribute رویدادی (on*) ساخته نمی‌شود' );

ts_co_assert_same( '', TS_Comments_Overview_Payload::format_text( '   ' ), 'متن خالی، خروجی خالی می‌دهد' );
