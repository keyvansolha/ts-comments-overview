<?php
/**
 * نمونه‌ی پاسخ سرویس تحلیل نظرات، دقیقاً با ساختاری که سرویس برمی‌گرداند.
 *
 * @package TS_Comments_Overview
 */

/**
 * پیلود کامل (شامل کلیدهای تکراری camelCase و lowercase که خود سرویس هر دو را
 * برمی‌گرداند).
 *
 * @return array<string,mixed>
 */
function ts_co_fixture_payload() {
	return array(
		'topics'               => array( 'کیفیت صدا', 'عمر باتری', 'قیمت', 'ارگونومی' ),
		'summary'              => "کیفیت صدا در کل رضایت‌بخش است.\n - بیس عمیق و بدون اعوجاج\n - اتصال بی‌سیم پایدار\n**عمر باتری** نسبت به رقبا کمتر گزارش شده است.",
		'sentiment'            => array(
			'positive' => 62,
			'negative' => 11,
			'neutral'  => 27,
		),
		'strengths'            => array( 'کیفیت صدا و تفکیک سازها', 'اتصال بی‌سیم پایدار', 'کیفیت ساخت مناسب' ),
		'weaknesses'           => array( 'عمر باتری کوتاه‌تر از انتظار', 'فقدان کدک aptX در نسخه پایه' ),
		'providerName'         => 'ترب',
		'providerBreakdown'    => array(
			array(
				'providerName' => 'ترب',
				'commentCount' => 84,
				'percentage'   => 62,
			),
			array(
				'name'         => 'دیجی‌کالا',
				'comments'     => 51,
				'percent'      => 38,
			),
		),
		'overallRating'        => 4.31,
		'totalComments'        => 135,
		'recommendPercentage'  => 78,
		'scope'                => 'all',
		'isderived'            => true,
		'isDerived'            => true,
		'coverage'             => array(
			'providers' => 2,
			'comments'  => 135,
		),
		'stale'                => false,
		'sourcecommentcount'   => 418,
		'sourceCommentCount'   => 418,
		'sourceprovidercount'  => 2,
		'sourceProviderCount'  => 2,
		'sourceproviderkeys'   => array( 'torob', 'digikala' ),
		'sourceProviderKeys'   => array( 'torob', 'digikala' ),
		'provider'             => array(
			'name' => 'ترب',
			'url'  => 'https://torob.com',
		),
		'updatedAt'            => '2026-08-01T09:15:00+03:30',
	);
}

/**
 * پیلود پیچیده در پوشش data.
 *
 * @return array<string,mixed>
 */
function ts_co_fixture_wrapped() {
	return array(
		'status' => 'ok',
		'data'   => array(
			'summary'            => 'کاربران در مجموع از این محصول راضی هستند.',
			'sentiment'          => array(
				'positive' => 10,
				'negative' => 2,
				'neutral'  => 3,
			),
			'overall_rating'     => 4.5,
			'comment_count'      => 15,
			'recommend_percentage' => 83,
			'updated_at'         => '2026-07-20T12:00:00Z',
		),
	);
}
