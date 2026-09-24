<?php
/**
 * تست‌های رندر بخش خلاصه در سه حالت + بررسی یکپارچگی با قالب.
 *
 * @package TS_Comments_Overview
 */

$GLOBALS['ts_co_options'] = array( 'date_format' => 'Y-m-d' );

// ---------------------------------------------------------------------------
// حالت خاموش
// ---------------------------------------------------------------------------

TS_Comments_Overview_Settings::save( array( 'mode' => TS_Comments_Overview_Settings::MODE_OFF ) );
ts_co_reset_environment();
ts_co_set_http_response( 200, ts_co_fixture_payload() );

ts_co_assert_same( '', TS_Comments_Overview_Render::get_html( 4321 ), 'حالت خاموش هیچ خروجی‌ای تولید نمی‌کند' );
ts_co_assert_same( 0, ts_co_http_call_count(), 'در حالت خاموش هیچ درخواستی به سرویس زده نمی‌شود' );

// ---------------------------------------------------------------------------
// حالت خلاصه
// ---------------------------------------------------------------------------

TS_Comments_Overview_Settings::save( array( 'mode' => TS_Comments_Overview_Settings::MODE_SUMMARY ) );
ts_co_reset_environment();
ts_co_set_http_response( 200, ts_co_fixture_payload() );

$html = TS_Comments_Overview_Render::get_html( 4321 );

ts_co_assert_true( '' !== $html, 'حالت خلاصه خروجی تولید می‌کند' );
ts_co_assert_contains( '<section class="ts-comments-overview"', $html, 'بخش خلاصه با کلاس اختصاصی رندر می‌شود' );
ts_co_assert_contains( 'dir="rtl"', $html, 'بخش خلاصه راست‌به‌چپ است' );
ts_co_assert_contains( 'آنچه کاربران در اینترنت درباره این محصول می‌گویند', $html, 'عنوان بخش، اینترنتی بودن نظرات را روشن می‌کند' );
ts_co_assert_contains( 'جمع‌بندی خودکار نظراتی که کاربران در سایت‌های دیگر ثبت کرده‌اند', $html, 'زیرعنوان منبع نظرات را توضیح می‌دهد' );
ts_co_assert_contains( '<p>کیفیت صدا در کل رضایت‌بخش است.</p>', $html, 'خلاصه به پاراگراف تبدیل می‌شود' );
ts_co_assert_contains( '<li>بیس عمیق و بدون اعوجاج</li>', $html, 'خطوط فهرستی خلاصه رندر می‌شوند' );
ts_co_assert_contains( '<strong>عمر باتری</strong>', $html, 'پررنگ‌سازی خلاصه کار می‌کند' );

// آمار
ts_co_assert_contains( '4.3', $html, 'امتیاز کل نمایش داده می‌شود' );
ts_co_assert_contains( '78٪', $html, 'درصد پیشنهاد نمایش داده می‌شود' );
ts_co_assert_contains( '135', $html, 'تعداد نظرات بررسی‌شده نمایش داده می‌شود' );
ts_co_assert_contains( 'ts-comments-overview__scores', $html, 'امتیاز کل و درصد پیشنهاد در هدر و در کارت‌های برجسته هستند' );
ts_co_assert_contains( 'ts-comments-overview__score-value', $html, 'عدد امتیاز کلاس اختصاصی کارت را دارد' );
ts_co_assert_contains( 'ts-comments-overview__reviews-note', $html, 'تعداد نظرات به‌صورت خط کوچک زیر عنوان می‌آید' );
ts_co_assert_not_contains( 'ts-comments-overview__stat"', $html, 'ردیف آمار قبلی حذف شده است' );
ts_co_assert_true(
	strpos( $html, 'ts-comments-overview__scores' ) < strpos( $html, 'ts-comments-overview__sentiment' ),
	'کارت‌های امتیاز پیش از نوار احساسات (در هدر) قرار دارند'
);

// احساسات (۶۲/۲۷/۱۱ از ۱۰۰)
ts_co_assert_contains( 'ts-comments-overview__bar-seg--positive', $html, 'نوار احساسات: بخش مثبت وجود دارد' );
ts_co_assert_contains( '--ts-co-share: 62%', $html, 'نوار احساسات: سهم مثبت درست است' );
ts_co_assert_contains( '--ts-co-share: 27%', $html, 'نوار احساسات: سهم بی‌طرف درست است' );
ts_co_assert_contains( '--ts-co-share: 11%', $html, 'نوار احساسات: سهم منفی درست است' );
ts_co_assert_contains( '62٪', $html, 'درصد مثبت در راهنما نمایش داده می‌شود' );

// موضوع‌ها
ts_co_assert_contains( 'ts-comments-overview__topics-list', $html, 'فهرست موضوع‌ها رندر می‌شود' );
ts_co_assert_contains( 'ts-comments-overview__topic-label">کیفیت صدا<', $html, 'موضوع‌ها به‌عنوان برچسب نمایش داده می‌شوند' );

// منابع و پانویس
ts_co_assert_not_contains( 'بر پایه‌ی 2 منبع', $html, 'تعداد منابع نمایش داده نمی‌شود' );
ts_co_assert_not_contains( 'دیجی‌کالا', $html, 'نام منبع در رابط کاربری نیست' );
ts_co_assert_not_contains( 'ts-comments-overview__source', $html, 'هیچ عنصر منبعی در خروجی نیست' );
ts_co_assert_not_contains( '2026-08-01', $html, 'تاریخ آخرین به‌روزرسانی در رابط کاربری نمایش داده نمی‌شود' );
ts_co_assert_not_contains( 'آخرین به‌روزرسانی', $html, 'هیچ نشانه‌ای از زمان به‌روزرسانی در خروجی نیست' );
ts_co_assert_contains( 'این خلاصه به‌صورت خودکار از مجموع نظرات کاربران در سایت‌های اینترنتی دیگر ساخته شده است', $html, 'سلب مسئولیت ذکر می‌شود' );

// هشدار برجسته‌ی «این نظرات از فروشگاه دیگری است» باید پیش از متن خلاصه بیاید.
ts_co_assert_contains( 'ts-comments-overview__notice', $html, 'هشدار منبع نظرات رندر می‌شود' );
ts_co_assert_contains( 'این نظرات از سایت‌های اینترنتی دیگر جمع‌بندی شده است، نه از خریداران تهران‌اسپیکر', $html, 'هشدار، فروشگاه خودمان را از منبع نظرات جدا می‌کند' );
ts_co_assert_contains( 'بسته‌بندی نامناسب، کالای آسیب‌دیده یا مرجوعی', $html, 'هشدار، ایرادهای مربوط به فروشگاه‌های دیگر را نام می‌برد' );
ts_co_assert_contains( 'icon-info', $html, 'آیکون هشدار از مجموعه آیکون‌های قالب است' );
ts_co_assert_true(
	strpos( $html, 'ts-comments-overview__notice' ) < strpos( $html, 'ts-comments-overview__summary' ),
	'هشدار پیش از متن خلاصه در خروجی قرار دارد'
);
ts_co_assert_not_contains( 'به‌روزرسانی در انتظار است', $html, 'برای داده‌ی تازه، هشدار کهنگی نمایش داده نمی‌شود' );

// در حالت خلاصه، نقاط قوت و ضعف نباید نمایش داده شوند.
ts_co_assert_not_contains( 'نقاط قوت', $html, 'حالت خلاصه، نقاط قوت را نمایش نمی‌دهد' );
ts_co_assert_not_contains( 'نقاط ضعف', $html, 'حالت خلاصه، نقاط ضعف را نمایش نمی‌دهد' );

// ---------------------------------------------------------------------------
// حالت خلاصه + نقاط قوت و ضعف
// ---------------------------------------------------------------------------

TS_Comments_Overview_Settings::save( array( 'mode' => TS_Comments_Overview_Settings::MODE_SUMMARY_PROS_CONS ) );
ts_co_reset_environment();
ts_co_set_http_response( 200, ts_co_fixture_payload() );

$html = TS_Comments_Overview_Render::get_html( 4321 );

ts_co_assert_contains( 'ts-comments-overview__panel--pros', $html, 'پنل نقاط قوت رندر می‌شود' );
ts_co_assert_contains( 'ts-comments-overview__panel--cons', $html, 'پنل نقاط ضعف رندر می‌شود' );
ts_co_assert_contains( 'نقاط قوت', $html, 'عنوان نقاط قوت نمایش داده می‌شود' );
ts_co_assert_contains( 'نقاط ضعف', $html, 'عنوان نقاط ضعف نمایش داده می‌شود' );
ts_co_assert_contains( 'ts-comments-overview__list-item', $html, 'آیتم‌های نقاط قوت به‌صورت فهرست رندر می‌شوند' );
ts_co_assert_contains( 'کیفیت صدا و تفکیک سازها', $html, 'متن نقاط قوت نمایش داده می‌شود' );
ts_co_assert_contains( 'عمر باتری کوتاه‌تر از انتظار', $html, 'متن نقاط ضعف نمایش داده می‌شود' );
ts_co_assert_contains( 'icon-like', $html, 'آیکون قوت از مجموعه آیکون‌های قالب است' );
ts_co_assert_contains( 'icon-dislike', $html, 'آیکون ضعف از مجموعه آیکون‌های قالب است' );

// اگر برای محصولی قوت/ضعف خالی باشد، فقط خلاصه می‌ماند.
ts_co_reset_environment();
ts_co_set_http_response(
	200,
	array(
		'summary'    => 'فقط خلاصه.',
		'sentiment'  => array(
			'positive' => 5,
			'negative' => 1,
			'neutral'  => 1,
		),
		'strengths'  => array(),
		'weaknesses' => array(),
	)
);
$html = TS_Comments_Overview_Render::get_html( 4321 );
ts_co_assert_contains( 'فقط خلاصه.', $html, 'خلاصه در حالت پیشرفته هم نمایش داده می‌شود' );
ts_co_assert_not_contains( 'نقاط قوت', $html, 'بدون داده‌ی قوت، پنل قوت ساخته نمی‌شود' );
ts_co_assert_not_contains( 'نقاط ضعف', $html, 'بدون داده‌ی ضعف، پنل ضعف ساخته نمی‌شود' );

// ---------------------------------------------------------------------------
// داده‌ی مخرب از سرویس
// ---------------------------------------------------------------------------

ts_co_reset_environment();
ts_co_set_http_response(
	200,
	array(
		'summary'              => "</p><script>alert('xss')</script><img src=x onerror=alert(1)>",
		'topics'               => array( '<script>alert(2)</script>موضوع' ),
		'strengths'            => array( '"><svg onload=alert(3)>' ),
		'weaknesses'           => array( 'نرمال' ),
		'sentiment'            => array(
			'positive' => 3,
			'negative' => 1,
			'neutral'  => 1,
		),
		'overallRating'        => 99,
		'totalComments'        => -5,
		'recommendPercentage'  => '۱۵۰٪',
		'providerBreakdown'    => array( array( 'providerName' => '<b>منبع</b>' ) ),
		'updatedAt'            => 'not-a-date',
	)
);

$html = TS_Comments_Overview_Render::get_html( 4321 );
ts_co_assert_not_contains( '<script', $html, 'هیچ اسکریپتی از داده‌ی سرویس در خروجی نیست' );
ts_co_assert_not_contains( '<img', $html, 'هیچ تصویر تزریقی در خروجی نیست' );
ts_co_assert_not_contains( '<svg', $html, 'هیچ svg تزریقی در خروجی نیست' );
ts_co_assert_not_contains( 'alert(', $html, 'محتوای اسکریپت سرویس حذف می‌شود' );
ts_co_assert_same( 0, preg_match( '/<[a-z]+[^>]*\son[a-z]+\s*=/i', $html ), 'هیچ attribute رویدادی در خروجی ساخته نمی‌شود' );
ts_co_assert_contains( 'موضوع', $html, 'متن سالم کنار تگ مخرب حفظ می‌شود' );
ts_co_assert_contains( '5', $html, 'امتیاز خارج از بازه به ۵ اصلاح می‌شود' );
ts_co_assert_contains( '100٪', $html, 'درصد بزرگ‌تر از ۱۰۰ محدود می‌شود' );
ts_co_assert_not_contains( '99', $html, 'امتیاز ۹۹ در خروجی دیده نمی‌شود' );

// ---------------------------------------------------------------------------
// کهنگی داده
// ---------------------------------------------------------------------------

ts_co_reset_environment();
$stale = ts_co_fixture_payload();
$stale['stale'] = true;
ts_co_set_http_response( 200, $stale );
$html = TS_Comments_Overview_Render::get_html( 4321 );
/*
 * نشان کهنگی داده عمداً حذف شده است: «به‌روزرسانی در انتظار است» برای بازدیدکننده
 * معنایی نداشت و در کارت زرد دیده می‌شد.
 */
ts_co_assert_not_contains( 'به‌روزرسانی در انتظار است', $html, 'نشان کهنگی داده نمایش داده نمی‌شود' );
ts_co_assert_not_contains( 'ts-comments-overview__chip', $html, 'هیچ نشان کهنگی‌ای در خروجی نیست' );
ts_co_assert_contains( 'این خلاصه به‌صورت خودکار', $html, 'متن سلب مسئولیت باقی می‌ماند' );

// ---------------------------------------------------------------------------
// بدون داده / خطا
// ---------------------------------------------------------------------------

ts_co_reset_environment();
ts_co_set_http_response( 404, array( 'detail' => 'not found' ) );
ts_co_assert_same( '', TS_Comments_Overview_Render::get_html( 4321 ), 'خطای سرویس به بخش خالی تبدیل می‌شود (بدون پیام خطا روی سایت)' );

// توکن تنظیم‌نشده: هیچ خروجی‌ای نباید تولید شود.
$GLOBALS['ts_co_filters'] = array(
	'ts_comments_overview_api_token' => array(
		static function () {
			return '';
		},
	),
);
ts_co_reset_environment();
ts_co_set_http_response( 200, ts_co_fixture_payload() );
ts_co_assert_same( '', TS_Comments_Overview_Render::get_html( 4321 ), 'بدون توکن، بخش خلاصه رندر نمی‌شود' );
ts_co_assert_same( 0, ts_co_http_call_count(), 'بدون توکن، درخواستی ارسال نمی‌شود' );
$GLOBALS['ts_co_filters'] = array();

// ---------------------------------------------------------------------------
// شناسه‌ی محصول
// ---------------------------------------------------------------------------

class TS_CO_Test_Product {

	/** @var int */
	private $id;

	/**
	 * @param int $id شناسه.
	 */
	public function __construct( $id ) {
		$this->id = $id;
	}

	/** @return int */
	public function get_id() {
		return $this->id;
	}
}

ts_co_assert_same( 42, TS_Comments_Overview_Render::resolve_product_id( new TS_CO_Test_Product( 42 ) ), 'شناسه از آبجکت محصول خوانده می‌شود' );
ts_co_assert_same( 77, TS_Comments_Overview_Render::resolve_product_id( 77 ), 'شناسه عددی پذیرفته می‌شود' );
ts_co_assert_same( 0, TS_Comments_Overview_Render::resolve_product_id( null ), 'بدون محصول و بدون context، شناسه صفر است' );

// ---------------------------------------------------------------------------
// کمکی‌های نمایش
// ---------------------------------------------------------------------------

ts_co_assert_same( 62, TS_Comments_Overview_Render::percentage( 62, 100 ), 'درصد درست محاسبه می‌شود' );
ts_co_assert_same( 0, TS_Comments_Overview_Render::percentage( 5, 0 ), 'تقسیم بر صفر مدیریت می‌شود' );
ts_co_assert_same( '4.3', TS_Comments_Overview_Render::rating_label( 4.31 ), 'امتیاز با یک رقم اعشار نمایش داده می‌شود' );
ts_co_assert_same( '4', TS_Comments_Overview_Render::rating_label( 4.0 ), 'صفر اضافه‌ی امتیاز حذف می‌شود' );
ts_co_assert_same( '1,235', TS_Comments_Overview_Render::number_label( 1235 ), 'عدد با جداکننده‌ی هزارگان نمایش داده می‌شود' );

// ---------------------------------------------------------------------------
// صف‌بندی دارایی‌ها
// ---------------------------------------------------------------------------

TS_Comments_Overview_Settings::save( array( 'mode' => TS_Comments_Overview_Settings::MODE_SUMMARY ) );
ts_co_reset_environment();
$GLOBALS['ts_co_is_product'] = false;
TS_Comments_Overview_Render::enqueue_assets();
ts_co_assert_false( isset( $GLOBALS['ts_co_styles'][ TS_Comments_Overview_Render::STYLE_HANDLE ] ), 'خارج از صفحه محصول، استایل بارگذاری نمی‌شود' );

ts_co_reset_environment();
$GLOBALS['ts_co_styles']['amazing-theme-system'] = array( 'src' => 'theme-system.css' );
$GLOBALS['ts_co_is_product']                    = true;
TS_Comments_Overview_Render::enqueue_assets();
$GLOBALS['ts_co_is_product'] = false;

ts_co_assert_true( isset( $GLOBALS['ts_co_styles'][ TS_Comments_Overview_Render::STYLE_HANDLE ] ), 'در صفحه محصول، استایل بخش خلاصه بارگذاری می‌شود' );
ts_co_assert_same(
	array( 'amazing-theme-system' ),
	$GLOBALS['ts_co_styles'][ TS_Comments_Overview_Render::STYLE_HANDLE ]['deps'],
	'استایل به لایه‌ی توکن‌های قالب وابسته است'
);

ts_co_reset_environment();
TS_Comments_Overview_Settings::save( array( 'mode' => TS_Comments_Overview_Settings::MODE_OFF ) );
$GLOBALS['ts_co_is_product'] = true;
TS_Comments_Overview_Render::enqueue_assets();
$GLOBALS['ts_co_is_product'] = false;
ts_co_assert_false( isset( $GLOBALS['ts_co_styles'][ TS_Comments_Overview_Render::STYLE_HANDLE ] ), 'در حالت خاموش، استایل بارگذاری نمی‌شود' );

// ---------------------------------------------------------------------------
// یکپارچگی با قالب فعال
// ---------------------------------------------------------------------------

$integration = TS_Comments_Overview_Admin::theme_integration_status();
ts_co_assert_true( $integration['integrated'], 'قالب فعال، هوک بخش خلاصه را در فایل‌های بخش نظرات صدا می‌زند' );

$desktop = TS_CO_TEST_THEME_DIR . '/lib/Product/template/desktop/comments.php';
$mobile  = TS_CO_TEST_THEME_DIR . '/lib/Product/template/mobile/panels/comments.php';
ts_co_assert_same( 'hook', $integration['checked'][ $desktop ], 'قالب دسکتاپ هوک را صدا می‌زند' );
ts_co_assert_same( 'hook', $integration['checked'][ $mobile ], 'قالب موبایل هوک را صدا می‌زند' );

// ---------------------------------------------------------------------------
// آستانه‌ی کیفیت: مبنای درصد
// ---------------------------------------------------------------------------

ts_co_assert_same(
	78,
	TS_Comments_Overview_Render::quality_percent( array( 'recommend_percentage' => 78 ) ),
	'مبنای آستانه، درصد پیشنهاد سرویس است'
);
ts_co_assert_same(
	100,
	TS_Comments_Overview_Render::quality_percent( array( 'recommend_percentage' => 130 ) ),
	'درصد بیشتر از ۱۰۰ به ۱۰۰ محدود می‌شود'
);
ts_co_assert_same(
	0,
	TS_Comments_Overview_Render::quality_percent( array( 'recommend_percentage' => -5 ) ),
	'درصد منفی به صفر محدود می‌شود'
);
ts_co_assert_same(
	80,
	TS_Comments_Overview_Render::quality_percent(
		array( 'sentiment' => array( 'positive' => 80, 'negative' => 10, 'neutral' => 10 ) )
	),
	'اگر درصد پیشنهاد نبود، سهم نظرهای مثبت مبنا می‌شود'
);
ts_co_assert_same(
	null,
	TS_Comments_Overview_Render::quality_percent( array() ),
	'بدون هیچ عددی مبنایی برای آستانه وجود ندارد'
);
ts_co_assert_same(
	null,
	TS_Comments_Overview_Render::quality_percent(
		array( 'sentiment' => array( 'positive' => 0, 'negative' => 0, 'neutral' => 0 ) )
	),
	'احساسات صفر مبنای آستانه نمی‌شود'
);

// ---------------------------------------------------------------------------
// آستانه‌ی کیفیت: تصمیم نمایش
// ---------------------------------------------------------------------------

TS_Comments_Overview_Settings::save(
	array(
		'mode'          => TS_Comments_Overview_Settings::MODE_SUMMARY,
		'min_recommend' => 80,
	)
);
ts_co_assert_true(
	TS_Comments_Overview_Render::passes_quality_threshold( array( 'recommend_percentage' => 80 ) ),
	'مرز آستانه: درصد مساوی، خلاصه نمایش داده می‌شود'
);
ts_co_assert_false(
	TS_Comments_Overview_Render::passes_quality_threshold( array( 'recommend_percentage' => 79 ) ),
	'درصد کمتر از آستانه، خلاصه را پنهان می‌کند'
);
ts_co_assert_true(
	TS_Comments_Overview_Render::passes_quality_threshold( array() ),
	'وقتی سرویس عددی نداده، پیش‌فرض نمایش است (پنهان کردن تهاجمی نیست)'
);

// خروجی واقعی: fixture سرویس ۷۸٪ دارد.
ts_co_reset_environment();
ts_co_set_http_response( 200, ts_co_fixture_payload() );
ts_co_assert_same(
	'',
	TS_Comments_Overview_Render::get_html( 4321 ),
	'محصول زیر آستانه هیچ بخشی در صفحه تولید نمی‌کند'
);

TS_Comments_Overview_Settings::save(
	array(
		'mode'          => TS_Comments_Overview_Settings::MODE_SUMMARY,
		'min_recommend' => 70,
	)
);
ts_co_reset_environment();
ts_co_set_http_response( 200, ts_co_fixture_payload() );
ts_co_assert_true(
	'' !== TS_Comments_Overview_Render::get_html( 4321 ),
	'محصول بالای آستانه خلاصه را نمایش می‌دهد'
);

// صفر = قاعده خاموش.
TS_Comments_Overview_Settings::save(
	array(
		'mode'          => TS_Comments_Overview_Settings::MODE_SUMMARY,
		'min_recommend' => 0,
	)
);
ts_co_reset_environment();
ts_co_set_http_response( 200, ts_co_fixture_payload() );
ts_co_assert_true(
	'' !== TS_Comments_Overview_Render::get_html( 4321 ),
	'آستانه‌ی صفر همه‌ی محصولات را نمایش می‌دهد'
);

// ---------------------------------------------------------------------------
// نشان‌گذاری ورودی «نظرات» (حاشیه‌ی رنگین‌کمانی)
// ---------------------------------------------------------------------------

TS_Comments_Overview_Settings::save( array( 'mode' => TS_Comments_Overview_Settings::MODE_SUMMARY ) );
ts_co_reset_environment();
$GLOBALS['ts_co_is_product'] = true;
TS_Comments_Overview_Render::enqueue_assets();
$GLOBALS['ts_co_is_product'] = false;

ts_co_assert_true(
	isset( $GLOBALS['ts_co_scripts'][ TS_Comments_Overview_Render::SCRIPT_HANDLE ] ),
	'در صفحه محصول، اسکریپت نشان‌گذاری نوار بخش‌ها بارگذاری می‌شود'
);
ts_co_assert_true(
	! empty( $GLOBALS['ts_co_scripts'][ TS_Comments_Overview_Render::SCRIPT_HANDLE ]['footer'] ),
	'اسکریپت در فوتر بارگذاری می‌شود تا DOM آماده باشد'
);

$script_source = (string) file_get_contents( TS_COMMENTS_OVERVIEW_PATH . 'assets/js/comments-overview.js' );
ts_co_assert_contains( 'productComments', $script_source, 'اسکریپت تب «نظرات» دسکتاپ را هدف می‌گیرد' );
ts_co_assert_contains( 'comments"', $script_source, 'اسکریپت میان‌بر نظرات موبایل را هدف می‌گیرد' );
ts_co_assert_contains( 'ts-co-nav-glow', $script_source, 'کلاس نشان‌گذاری در اسکریپت تعریف شده است' );
ts_co_assert_contains( '.ts-comments-overview', $script_source, 'اسکریپت فقط با وجود بخش خلاصه فعال می‌شود' );

$style_source = (string) file_get_contents( TS_COMMENTS_OVERVIEW_PATH . 'assets/css/comments-overview.css' );
ts_co_assert_contains( '.ts-co-nav-glow', $style_source, 'استایل حلقه‌ی رنگین‌کمانی وجود دارد' );
ts_co_assert_contains( 'ts-co-glow-rainbow', $style_source, 'انیمیشن رنگین‌کمانی تعریف شده است' );
ts_co_assert_contains( 'prefers-reduced-motion', $style_source, 'حالت کم‌تحرکی برای حلقه رعایت شده است' );
ts_co_assert_not_contains(
	'.ts-co-nav-glow::before',
	$style_source,
	'حلقه از شبه‌عنصر استفاده نمی‌کند (قالب شبه‌عنصرهای تب را خاموش کرده است)'
);
