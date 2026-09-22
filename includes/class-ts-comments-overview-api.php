<?php
/**
 * کلاینت خواندنی سرویس تحلیل نظرات (mytsapp.ir).
 *
 * GET {endpoint}?woocommerce_id={id}
 * هدر: X-API-Token
 *
 * سیاست‌ها:
 *   - یک درخواست به‌ازای هر محصول در هر بازه‌ی TTL؛ نتیجه در ترنزینت کش می‌شود.
 *     بدون این کش، هر بار رندر صفحه‌ی محصول (حتی در حالت بدون کش صفحه) یک
 *     درخواست شبکه‌ای به سرویس بیرونی می‌زد.
 *   - خطاها هم کوتاه‌مدت کش می‌شوند تا سرویس خواب‌رفته صفحه‌ی محصول را کند نکند.
 *   - بدنه‌ی پاسخ محدود است و ریدایرکت دنبال نمی‌شود (کاهش سطح حمله).
 *   - توکن هرگز لاگ نمی‌شود.
 *
 * @package TS_Comments_Overview
 */

defined( 'ABSPATH' ) || exit;

/**
 * دریافت و کش تحلیل نظرات یک محصول.
 */
final class TS_Comments_Overview_API {

	/** پیشوند ترنزینت داده‌ی موفق. */
	const CACHE_PREFIX = 'ts_co_analysis_';

	/** پیشوند ترنزینت خطا. */
	const ERROR_PREFIX = 'ts_co_error_';

	/** آپشن ایندکس شناسه‌های کش‌شده (برای پاک‌سازی گروهی). */
	const INDEX_OPTION = 'ts_comments_overview_cache_index';

	/** حداکثر تعداد شناسه در ایندکس کش. */
	const INDEX_LIMIT = 500;

	/** مهلت درخواست (ثانیه). */
	const TIMEOUT = 8;

	/** حداکثر حجم پاسخ (بایت). */
	const MAX_BYTES = 524288;

	/**
	 * تحلیل یک محصول.
	 *
	 * @param int  $product_id شناسه‌ی محصول ووکامرس.
	 * @param bool $force      نادیده گرفتن کش.
	 * @return array<string,mixed>|WP_Error
	 */
	public static function get( $product_id, $force = false ) {
		$product_id = absint( $product_id );

		if ( $product_id <= 0 ) {
			return self::error( 'ts_comments_overview_invalid_product', 'شناسه‌ی محصول معتبر نیست.' );
		}

		if ( ! $force ) {
			$cached = get_transient( self::CACHE_PREFIX . $product_id );
			if ( is_array( $cached ) && isset( $cached['data'] ) && is_array( $cached['data'] ) ) {
				return $cached;
			}

			$failure = get_transient( self::ERROR_PREFIX . $product_id );
			if ( is_array( $failure ) && isset( $failure['message'] ) ) {
				return self::error(
					isset( $failure['code'] ) ? (string) $failure['code'] : 'ts_comments_overview_cached_error',
					(string) $failure['message'],
					isset( $failure['status'] ) ? (int) $failure['status'] : 0
				);
			}
		}

		$response = self::request( $product_id );

		if ( is_wp_error( $response ) ) {
			self::store_failure( $product_id, $response );

			return $response;
		}

		$envelope = array(
			'data'       => TS_Comments_Overview_Payload::normalize( $response ),
			'fetched_at' => time(),
			'source'     => 'service',
		);

		self::store( $product_id, $envelope );

		return $envelope;
	}

	/**
	 * تحلیل یک محصول بدون کش و بدون ذخیره‌ی کش (برای ابزار بررسی پیشخوان).
	 *
	 * @param int $product_id شناسه‌ی محصول.
	 * @return array<string,mixed>|WP_Error
	 */
	public static function get_live( $product_id ) {
		$product_id = absint( $product_id );

		if ( $product_id <= 0 ) {
			return self::error( 'ts_comments_overview_invalid_product', 'شناسه‌ی محصول معتبر نیست.' );
		}

		$response = self::request( $product_id );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return array(
			'data'       => TS_Comments_Overview_Payload::normalize( $response ),
			'fetched_at' => time(),
			'source'     => 'live',
			'raw'        => $response,
		);
	}

	/**
	 * پاک کردن کش یک محصول.
	 *
	 * @param int $product_id شناسه‌ی محصول.
	 * @return void
	 */
	public static function flush( $product_id ) {
		$product_id = absint( $product_id );

		if ( $product_id <= 0 ) {
			return;
		}

		delete_transient( self::CACHE_PREFIX . $product_id );
		delete_transient( self::ERROR_PREFIX . $product_id );
		self::remove_from_index( $product_id );
	}

	/**
	 * پاک کردن کش همه‌ی محصولاتی که تا حالا تحلیلشان کش شده است.
	 *
	 * @return int تعداد محصولات پاک‌شده.
	 */
	public static function flush_all() {
		$index = self::index();
		$count = 0;

		foreach ( $index as $product_id ) {
			delete_transient( self::CACHE_PREFIX . $product_id );
			delete_transient( self::ERROR_PREFIX . $product_id );
			++$count;
		}

		if ( false === get_option( self::INDEX_OPTION, false ) ) {
			add_option( self::INDEX_OPTION, array(), '', 'no' );
		} else {
			update_option( self::INDEX_OPTION, array(), false );
		}

		return $count;
	}

	/**
	 * یک درخواست HTTP به سرویس.
	 *
	 * @param int $product_id شناسه‌ی محصول.
	 * @return array<mixed>|WP_Error بدنه‌ی JSON.
	 */
	private static function request( $product_id ) {
		$endpoint = TS_Comments_Overview_Settings::api_endpoint();
		$token    = TS_Comments_Overview_Settings::api_token();

		if ( '' === $endpoint || '' === $token ) {
			return self::error(
				'ts_comments_overview_not_configured',
				'اتصال سرویس تحلیل نظرات کامل نیست؛ توکن در wp-config.php تنظیم نشده است.'
			);
		}

		$url = add_query_arg( array( 'woocommerce_id' => $product_id ), $endpoint );

		$response = wp_remote_get(
			$url,
			array(
				'timeout'             => self::TIMEOUT,
				'redirection'         => 0,
				'limit_response_size' => self::MAX_BYTES,
				'user-agent'          => 'TS-Comments-Overview/' . TS_COMMENTS_OVERVIEW_VERSION . '; ' . home_url( '/' ),
				'headers'             => array(
					'Accept'       => 'application/json',
					'X-API-Token'  => $token,
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return self::error(
				'ts_comments_overview_unreachable',
				'ارتباط با سرویس تحلیل نظرات برقرار نشد.'
			);
		}

		$status = (int) wp_remote_retrieve_response_code( $response );
		$body   = json_decode( (string) wp_remote_retrieve_body( $response ), true );

		if ( 200 !== $status ) {
			return self::error(
				'ts_comments_overview_http_error',
				self::status_message( $status ),
				$status
			);
		}

		if ( ! is_array( $body ) ) {
			return self::error(
				'ts_comments_overview_invalid_body',
				'پاسخ سرویس تحلیل نظرات خوانا نبود.'
			);
		}

		if ( ! self::has_content( $body ) ) {
			return self::error(
				'ts_comments_overview_empty',
				'برای این محصول خلاصه‌ای در سرویس تحلیل نظرات وجود ندارد.'
			);
		}

		return $body;
	}

	/**
	 * آیا پاسخ حداقلی برای نمایش دارد؟ (خلاصه یا نقاط قوت/ضعف یا احساسات)
	 *
	 * @param array<mixed> $body بدنه‌ی JSON.
	 * @return bool
	 */
	private static function has_content( array $body ) {
		$record = TS_Comments_Overview_Payload::unwrap( $body );
		$data   = TS_Comments_Overview_Payload::normalize( $record );

		if ( '' !== $data['summary'] ) {
			return true;
		}

		if ( ! empty( $data['strengths'] ) || ! empty( $data['weaknesses'] ) ) {
			return true;
		}

		return $data['sentiment']['total'] > 0;
	}

	/**
	 * ذخیره‌ی نتیجه‌ی موفق در ترنزینت + ثبت در ایندکس.
	 *
	 * @param int                 $product_id شناسه‌ی محصول.
	 * @param array<string,mixed> $envelope   داده.
	 * @return void
	 */
	private static function store( $product_id, array $envelope ) {
		$ttl = TS_Comments_Overview_Settings::cache_ttl();

		if ( $ttl > 0 ) {
			set_transient( self::CACHE_PREFIX . $product_id, $envelope, $ttl );
		}

		delete_transient( self::ERROR_PREFIX . $product_id );
		self::add_to_index( $product_id );
	}

	/**
	 * ذخیره‌ی کوتاه‌مدت خطا تا سرویس خواب‌رفته، هر رندر را کند نکند.
	 *
	 * @param int      $product_id شناسه‌ی محصول.
	 * @param WP_Error $error      خطا.
	 * @return void
	 */
	private static function store_failure( $product_id, $error ) {
		$ttl = TS_Comments_Overview_Settings::error_ttl();

		if ( $ttl > 0 ) {
			set_transient(
				self::ERROR_PREFIX . $product_id,
				array(
					'code'    => $error->get_error_code(),
					'message' => $error->get_error_message(),
					'status'  => (int) ( is_array( $error->get_error_data() ) && isset( $error->get_error_data()['status'] ) ? $error->get_error_data()['status'] : 0 ),
				),
				$ttl
			);
		}
	}

	/**
	 * پیام کاربرپسند بر اساس کد وضعیت.
	 *
	 * @param int $status کد وضعیت HTTP.
	 * @return string
	 */
	private static function status_message( $status ) {
		if ( 401 === $status || 403 === $status ) {
			return 'سرویس تحلیل نظرات توکن را نپذیرفت (کد ' . $status . ').';
		}

		if ( 404 === $status ) {
			return 'برای این محصول خلاصه‌ای در سرویس تحلیل نظرات ثبت نشده است.';
		}

		if ( 429 === $status ) {
			return 'تعداد درخواست‌ها به سرویس تحلیل نظرات زیاد بود.';
		}

		return 'سرویس تحلیل نظرات پاسخ معتبری نداد (کد ' . $status . ').';
	}

	/**
	 * ساخت WP_Error با کد وضعیت اختیاری.
	 *
	 * @param string $code    کد خطا.
	 * @param string $message پیام.
	 * @param int    $status  کد وضعیت.
	 * @return WP_Error
	 */
	private static function error( $code, $message, $status = 0 ) {
		$data = $status > 0 ? array( 'status' => $status ) : array();

		return new WP_Error( $code, $message, $data );
	}

	/**
	 * فهرست شناسه‌های کش‌شده.
	 *
	 * @return int[]
	 */
	public static function index() {
		$index = get_option( self::INDEX_OPTION, array() );

		if ( ! is_array( $index ) ) {
			return array();
		}

		return array_values( array_filter( array_map( 'absint', $index ) ) );
	}

	/**
	 * افزودن شناسه به ایندکس کش (با سقف تعداد).
	 *
	 * @param int $product_id شناسه‌ی محصول.
	 * @return void
	 */
	private static function add_to_index( $product_id ) {
		$index = self::index();

		if ( in_array( (int) $product_id, $index, true ) ) {
			return;
		}

		$index[] = (int) $product_id;

		if ( count( $index ) > self::INDEX_LIMIT ) {
			$index = array_slice( $index, -1 * self::INDEX_LIMIT );
		}

		if ( false === get_option( self::INDEX_OPTION, false ) ) {
			add_option( self::INDEX_OPTION, $index, '', 'no' );
		} else {
			update_option( self::INDEX_OPTION, $index, false );
		}
	}

	/**
	 * حذف شناسه از ایندکس کش.
	 *
	 * @param int $product_id شناسه‌ی محصول.
	 * @return void
	 */
	private static function remove_from_index( $product_id ) {
		$index = self::index();
		$key   = array_search( (int) $product_id, $index, true );

		if ( false === $key ) {
			return;
		}

		unset( $index[ $key ] );
		update_option( self::INDEX_OPTION, array_values( $index ), false );
	}
}
