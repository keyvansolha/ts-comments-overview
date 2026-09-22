<?php
/**
 * تست با پاسخ واقعی سرویس (fixture گرفته‌شده از خود mytsapp.ir برای محصول 238607).
 *
 * این تست همان شکلی را می‌سنجد که سرویس در عمل برمی‌گرداند:
 *   { woocommerce_id: 238607, analysis: { ... } }
 * با topic‌های ساختاریافته ({label, count, direction}) و قوت/ضعف‌های
 * ({text, count}) — همان چیزی که در نسخه‌ی اول اشتباه خوانده می‌شد.
 *
 * @package TS_Comments_Overview
 */

$GLOBALS['ts_co_options'] = array( 'date_format' => 'Y/m/d' );

$raw = file_get_contents( __DIR__ . '/fixtures/comments-analysis-238607.json' );
ts_co_assert_true( false !== $raw, 'فایل نمونه‌ی پاسخ سرویس خوانده می‌شود' );

$body = json_decode( (string) $raw, true );
ts_co_assert_true( is_array( $body ), 'پاسخ نمونه JSON معتبر است' );
ts_co_assert_same( 238607, $body['woocommerce_id'], 'پاسخ شامل woocommerce_id است' );

// ---------------------------------------------------------------------------
// باز کردن پوشش analysis
// ---------------------------------------------------------------------------

$record = TS_Comments_Overview_Payload::unwrap( $body );
ts_co_assert_same(
	$body['analysis']['summary'],
	$record['summary'],
	'رکورد از پوشش analysis بیرون کشیده می‌شود'
);

$data = TS_Comments_Overview_Payload::normalize( $body );

ts_co_assert_contains( 'بیس قدرتمند', $data['summary'], 'خلاصه‌ی واقعی سرویس خوانده می‌شود' );
ts_co_assert_same( 4.35, $data['rating'], 'امتیاز کل ۴.۳۵ خوانده می‌شود' );
ts_co_assert_same( 20, $data['total_comments'], 'تعداد نظرات ۲۰ خوانده می‌شود' );
ts_co_assert_same( 85, $data['recommend_percentage'], 'درصد پیشنهاد ۸۵ خوانده می‌شود' );
ts_co_assert_same( 'همه فروشگاه‌ها', $data['provider_name'], 'نام سرویس‌دهنده خوانده می‌شود' );
ts_co_assert_same( 1, $data['source_provider_count'], 'تعداد منابع ۱ است' );
ts_co_assert_same( 20, $data['source_comment_count'], 'تعداد نظرات منبع ۲۰ است' );
ts_co_assert_false( $data['is_derived'], 'is_derived=false حفظ می‌شود' );
ts_co_assert_false( $data['stale'], 'stale=false حفظ می‌شود' );
ts_co_assert_same( 'all', $data['scope'], 'scope حفظ می‌شود' );
ts_co_assert_true( is_int( $data['updated_at'] ), 'updatedAt با میکروثانیه درست پارس می‌شود' );
ts_co_assert_same( strtotime( '2026-09-22T08:55:38.917162+00:00' ), $data['updated_at'], 'زمان به‌روزرسانی درست است' );

ts_co_assert_same( 100, $data['sentiment']['total'], 'مجموع احساسات ۱۰۰ است' );
ts_co_assert_same( 80, $data['sentiment']['positive'], 'احساسات مثبت ۸۰ است' );
ts_co_assert_same( 10, $data['sentiment']['negative'], 'احساسات منفی ۱۰ است' );
ts_co_assert_same( 10, $data['sentiment']['neutral'], 'احساسات بی‌طرف ۱۰ است' );

// ---------------------------------------------------------------------------
// موضوع‌ها: { label, count, direction }
// ---------------------------------------------------------------------------

ts_co_assert_same( 7, count( $data['topics'] ), 'هر هفت موضوع خوانده می‌شود' );
ts_co_assert_same( 'کیفیت صدا', $data['topics'][0]['label'], 'برچسب موضوع از کلید label خوانده می‌شود' );
ts_co_assert_same( 10, $data['topics'][0]['count'], 'شمارش موضوع از کلید count خوانده می‌شود' );
ts_co_assert_same( 'up', $data['topics'][0]['direction'], 'جهت موضوع (up) نگه داشته می‌شود' );
ts_co_assert_same( 'down', $data['topics'][2]['direction'], 'جهت موضوع (down) نگه داشته می‌شود' );
ts_co_assert_same( 'اسپیکرهای جداشونده', $data['topics'][6]['label'], 'موضوع آخر درست خوانده می‌شود' );

// ---------------------------------------------------------------------------
// قوت/ضعف: { text, count }
// ---------------------------------------------------------------------------

ts_co_assert_same( 7, count( $data['strengths'] ), 'هر هفت نقطه‌ی قوت خوانده می‌شود' );
ts_co_assert_same( 'کیفیت صدا', $data['strengths'][0]['text'], 'متن نقطه‌ی قوت از کلید text خوانده می‌شود' );
ts_co_assert_same( 10, $data['strengths'][0]['count'], 'شمارش نقطه‌ی قوت خوانده می‌شود' );
ts_co_assert_same( 6, count( $data['weaknesses'] ), 'هر شش نقطه‌ی ضعف خوانده می‌شود' );
ts_co_assert_same( 'عدم وجود پورت AUX', $data['weaknesses'][1]['text'], 'متن نقطه‌ی ضعف خوانده می‌شود' );
ts_co_assert_same( 'صدای فراگیر/دالبی اتموس', $data['strengths'][6]['text'], 'متن دارای اسلش سالم می‌ماند' );

// ---------------------------------------------------------------------------
// منابع
// ---------------------------------------------------------------------------

ts_co_assert_same( 1, count( $data['providers'] ), 'یک منبع خوانده می‌شود' );
ts_co_assert_same( 'TECHNOLIFE', $data['providers'][0]['name'], 'نام منبع خوانده می‌شود' );
ts_co_assert_same( 20, $data['providers'][0]['comments'], 'تعداد نظر منبع خوانده می‌شود' );

// ---------------------------------------------------------------------------
// رندر کامل با پاسخ واقعی
// ---------------------------------------------------------------------------

TS_Comments_Overview_Settings::save( array( 'mode' => TS_Comments_Overview_Settings::MODE_SUMMARY_PROS_CONS ) );
ts_co_reset_environment();
ts_co_set_http_response( 200, $body );

$html = TS_Comments_Overview_Render::get_html( 238607 );

ts_co_assert_true( '' !== $html, 'با پاسخ واقعی، بخش خلاصه رندر می‌شود (خطای «خالی» رخ نمی‌دهد)' );
ts_co_assert_contains( 'این محصول از نظر کیفیت صدا', $html, 'خلاصه‌ی واقعی در خروجی است' );
ts_co_assert_contains( '4.4', $html, 'امتیاز ۴.۳۵ با یک رقم اعشار (۴.۴) در کارت آمار است' );
ts_co_assert_contains( '85٪', $html, 'درصد پیشنهاد در کارت آمار است' );
ts_co_assert_contains( 'نظر از سایت‌های دیگر', $html, 'برچسب تعداد نظرات نمایش داده می‌شود' );
ts_co_assert_contains( '80٪', $html, 'سهم احساسات مثبت در راهنما است' );
ts_co_assert_contains( '--ts-co-count: 80', $html, 'نوار احساسات با شمارش خام تقسیم می‌شود' );
/*
 * سرویس برای این محصول sentiment را درصدی می‌فرستد (۸۰/۱۰/۱۰) در حالی که
 * totalComments برابر ۲۰ است؛ مبنای نمایش باید همان ۲۰ نظر باشد نه جمع ۱۰۰.
 */
ts_co_assert_contains( 'بر پایه‌ی 20 نظر تحلیل‌شده', $html, 'مبنای تحلیل از totalComments می‌آید، نه جمع مقادیر sentiment' );
ts_co_assert_not_contains( 'بر پایه‌ی 100 نظر', $html, 'عدد نادرست ۱۰۰ به‌عنوان تعداد نظرات چاپ نمی‌شود' );

// موضوع‌ها با شمارش
ts_co_assert_contains( 'ts-comments-overview__topic-label">کیفیت صدا<', $html, 'موضوع اول نمایش داده می‌شود' );
ts_co_assert_contains( 'ts-comments-overview__topic-count', $html, 'شمارش موضوع نمایش داده می‌شود' );
ts_co_assert_contains( 'اسپیکرهای جداشونده', $html, 'موضوع آخر نمایش داده می‌شود' );

// قوت/ضعف با شمارش
ts_co_assert_contains( 'نقاط قوت', $html, 'پنل نقاط قوت رندر می‌شود' );
ts_co_assert_contains( 'نقاط ضعف', $html, 'پنل نقاط ضعف رندر می‌شود' );
ts_co_assert_contains( 'بیس قدرتمند', $html, 'آیتم قوت «بیس قدرتمند» در خروجی است' );
ts_co_assert_contains( '10 نظر', $html, 'شمارش نظرها کنار آیتم قوت نمایش داده می‌شود' );
ts_co_assert_contains( 'عدم وجود ورودی میکروفون', $html, 'آیتم ضعف در خروجی است' );

// منبع و تاریخ
/*
 * نام سایت‌های مرجع و تعدادشان عمداً نمایش داده نمی‌شود؛ فقط تاریخ به‌روزرسانی
 * و سلب مسئولیت می‌ماند. داده‌ی providers در ساختار داخلی/ابزار پیشخوان هست.
 */
ts_co_assert_not_contains( 'TECHNOLIFE', $html, 'نام منبع در رابط کاربری نمایش داده نمی‌شود' );
ts_co_assert_not_contains( 'منابع:', $html, 'فهرست منابع در رابط کاربری نیست' );
ts_co_assert_not_contains( 'بر پایه‌ی 1 منبع', $html, 'تعداد منابع هم نمایش داده نمی‌شود' );
ts_co_assert_not_contains( 'ts-comments-overview__source', $html, 'هیچ عنصر مربوط به منبع در خروجی نیست' );
ts_co_assert_contains( '2026/09/22', $html, 'تاریخ آخرین به‌روزرسانی نمایش داده می‌شود' );
ts_co_assert_contains(
	'این خلاصه به‌صورت خودکار از مجموع نظرات کاربران در سایت‌های دیگر ساخته شده است و نظر یا تأیید تهران‌اسپیکر نیست.',
	$html,
	'سلب مسئولیت خودکار بودن خلاصه، بدون نام بردن از منبع، باقی می‌ماند'
);
ts_co_assert_not_contains( 'به‌روزرسانی در انتظار است', $html, 'داده‌ی تازه، هشدار کهنگی نمی‌گیرد' );
ts_co_assert_not_contains( 'icon-arrow', $html, 'جهت موضوع (که معنایش تأیید نشده) در رابط کاربری نمایش داده نمی‌شود' );
ts_co_assert_contains( 'خلاصه‌ی نظرات', $html, 'برای is_derived=false، نشان «خلاصه‌ی نظرات» می‌آید' );

// ---------------------------------------------------------------------------
// حالت خلاصه: بدون قوت/ضعف
// ---------------------------------------------------------------------------

TS_Comments_Overview_Settings::save( array( 'mode' => TS_Comments_Overview_Settings::MODE_SUMMARY ) );
ts_co_reset_environment();
ts_co_set_http_response( 200, $body );

$summary_only = TS_Comments_Overview_Render::get_html( 238607 );
ts_co_assert_contains( 'این محصول از نظر کیفیت صدا', $summary_only, 'حالت خلاصه هم با پاسخ واقعی کار می‌کند' );
/*
 * نکته: خودِ متن خلاصه‌ی این محصول عبارت «نقاط قوت» را دارد، پس بررسی باید روی
 * کلاس پنل انجام شود نه روی متن.
 */
ts_co_assert_not_contains( 'ts-comments-overview__panel--pros', $summary_only, 'حالت خلاصه، پنل قوت را نشان نمی‌دهد' );
ts_co_assert_not_contains( 'ts-comments-overview__panel--cons', $summary_only, 'حالت خلاصه، پنل ضعف را نشان نمی‌دهد' );
ts_co_assert_not_contains( 'ts-comments-overview__list-item', $summary_only, 'حالت خلاصه هیچ فهرست قوت/ضعفی رندر نمی‌کند' );

// ---------------------------------------------------------------------------
// پاسخ بدون تحلیل (محصولی که داده ندارد) و خطای ۴۰۴
// ---------------------------------------------------------------------------

ts_co_reset_environment();
ts_co_set_http_response( 200, array( 'woocommerce_id' => 5, 'analysis' => null ) );
ts_co_assert_same( '', TS_Comments_Overview_Render::get_html( 5 ), 'پاسخ بدون analysis بخشی رندر نمی‌کند' );

ts_co_reset_environment();
ts_co_set_http_response( 404, array( 'detail' => 'No product found for the requested woocommerce_id.' ) );
$missing = TS_Comments_Overview_API::get( 999999999 );
ts_co_assert_true( is_wp_error( $missing ), '۴۰۴ سرویس به خطا تبدیل می‌شود' );
ts_co_assert_contains( 'خلاصه‌ای در سرویس تحلیل نظرات ثبت نشده', $missing->get_error_message(), 'پیام ۴۰۴ برای مدیر قابل فهم است' );

ts_co_reset_environment();
ts_co_set_http_response( 401, array( 'detail' => 'Invalid comments analysis API token.' ) );
$bad_token = TS_Comments_Overview_API::get( 238607 );
ts_co_assert_true( is_wp_error( $bad_token ), '۴۰۱ سرویس به خطا تبدیل می‌شود' );
ts_co_assert_contains( 'توکن را نپذیرفت', $bad_token->get_error_message(), 'پیام ۴۰۱ برای مدیر قابل فهم است' );
ts_co_assert_not_contains( TS_COMMENTS_OVERVIEW_API_TOKEN, $bad_token->get_error_message(), 'پیام خطا توکن را افشا نمی‌کند' );
