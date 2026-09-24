/**
 * نشان‌گذاری ورودی «نظرات» در نوار بخش‌های صفحه محصول.
 *
 * وقتی برای محصول خلاصه‌ی نظرات روی صفحه رندر شده باشد، ورودی «نظرات» در
 * نوار بخش‌ها یک حاشیه‌ی رنگین‌کمانی متحرک می‌گیرد تا کاربر بفهمد پشت آن تب
 * چیز تازه‌ای هست. اگر برای محصول خلاصه‌ای نمایش داده نشده باشد (مثلاً به
 * دلیل آستانه‌ی کیفیت)، هیچ تغییری در صفحه انجام نمی‌شود.
 *
 * نشان‌گذاری از روی DOM انجام می‌شود، نه از روی قالب؛ بنابراین روی نوار
 * دسکتاپ (تب productComments) و روی میان‌بر نظرات موبایل (data-action) کار
 * می‌کند و اگر قالب بعداً یک ورودی «نظرات» به نوار موبایل اضافه کند، بدون
 * تغییر کد همین‌جا پوشش داده می‌شود.
 *
 * @package TS_Comments_Overview
 */
( function () {
	'use strict';

	/** بخش خلاصه‌ای که افزونه رندر می‌کند. */
	var SUMMARY_SELECTOR = '.ts-comments-overview';

	/** کلاسی که استایل حاشیه‌ی رنگین‌کمانی به آن وصل است. */
	var GLOW_CLASS = 'ts-co-nav-glow';

	/** هر عنصری که کاربر را به نظرات می‌رساند. */
	var TARGET_SELECTOR = [
		'[data-tab="productComments"]',
		'[data-action="comments"]',
		'tab-panel a[href*="#productComments"]',
		'tab-panel a[href*="#comments"]'
	].join( ',' );

	/**
	 * نزدیک‌ترین عنصر قابل نشان‌گذاری.
	 *
	 * برای دکمه‌ی تب، خود دکمه و برای میان‌بر موبایل، خود li هدف است.
	 *
	 * @param {Element} element عنصر پیداشده.
	 * @return {Element} عنصری که کلاس روی آن می‌نشیند.
	 */
	function glowNode( element ) {
		if ( element.tagName === 'LI' ) {
			return element;
		}

		return element.closest( 'li' ) || element;
	}

	/**
	 * افزودن کلاس به همه‌ی ورودی‌های نظرات، اگر خلاصه روی صفحه هست.
	 *
	 * @return {boolean} آیا خلاصه‌ای روی صفحه پیدا شد؟
	 */
	function apply() {
		if ( ! document.querySelector( SUMMARY_SELECTOR ) ) {
			return false;
		}

		var targets = document.querySelectorAll( TARGET_SELECTOR );

		for ( var i = 0; i < targets.length; i++ ) {
			glowNode( targets[ i ] ).classList.add( GLOW_CLASS );
		}

		return true;
	}

	var scheduled = false;

	/** اجرای دسته‌ای در فریم بعدی (تغییرات DOM پشت‌سرهم را جمع می‌کند). */
	function schedule() {
		if ( scheduled ) {
			return;
		}

		scheduled = true;

		if ( typeof window.requestAnimationFrame === 'function' ) {
			window.requestAnimationFrame( function () {
				scheduled = false;
				apply();
			} );

			return;
		}

		scheduled = false;
		apply();
	}

	/** راه‌اندازی: یک‌بار اجرا و بعد تماشای پنل‌هایی که بعداً اضافه می‌شوند. */
	function start() {
		apply();

		if ( typeof window.MutationObserver !== 'function' ) {
			return;
		}

		// پنل نظرات موبایل و تب‌ها ممکن است بعد از بارگذاری اضافه شوند؛
		// ناظر تا وقتی خلاصه پیدا شود فعال می‌ماند و بعد خودش را خاموش می‌کند.
		var observer = new window.MutationObserver( function () {
			if ( apply() ) {
				observer.disconnect();
			}
		} );

		observer.observe( document.documentElement, { childList: true, subtree: true } );

		window.setTimeout( function () {
			observer.disconnect();
		}, 20000 );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', schedule );
	} else {
		schedule();
	}

	// تب‌های دسکتاپ با تغییر کلاس کار می‌کنند و پنل را حذف/اضافه نمی‌کنند؛
	// این شنونده فقط برای حالتی است که قالب محتوای تب را دوباره بسازد.
	document.addEventListener( 'click', function ( event ) {
		var target = event.target;

		if ( ! target || typeof target.closest !== 'function' ) {
			return;
		}

		if ( target.closest( '[data-tab], [data-action="comments"]' ) ) {
			window.setTimeout( apply, 0 );
		}
	} );
}() );