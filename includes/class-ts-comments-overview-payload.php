<?php
/**
 * نرمال‌سازی پاسخ سرویس تحلیل نظرات به یک ساختار داخلی و امن.
 *
 * سرویس بیرونی است؛ پس هر مقدار ورودی «غیرقابل اعتماد» فرض می‌شود:
 *   - همه‌ی رشته‌ها پاکسازی و کوتاه می‌شوند.
 *   - خلاصه به‌صورت متن ساده با قالب‌بندی حداقلی و امن رندر می‌شود (بدون HTML).
 *   - همه‌ی اعداد به بازه‌ی منطقی خودشان محدود می‌شوند.
 *   - کلیدهای ناشناخته نادیده گرفته می‌شوند؛ کلید تکراری با نام‌های مختلف
 *     (camelCase / snake_case) پذیرفته می‌شود.
 *
 * @package TS_Comments_Overview
 */

defined( 'ABSPATH' ) || exit;

/**
 * تبدیل پیلود سرویس به ساختار داخلی افزونه.
 */
final class TS_Comments_Overview_Payload {

	/** حداکثر طول خلاصه. */
	const MAX_SUMMARY = 1500;

	/** حداکثر طول هر آیتم فهرست. */
	const MAX_ITEM = 240;

	/** حداکثر تعداد آیتم در هر فهرست (قوت/ضعف/منبع). */
	const MAX_LIST = 12;

	/** حداکثر تعداد موضوع‌ها. */
	const MAX_TOPICS = 20;

	/**
	 * باز کردن پوشش پاسخ سرویس.
	 *
	 * پاسخ سرویس می‌تواند یکی از این شکل‌ها باشد:
	 *   - { woocommerce_id: 238607, analysis: { ...رکورد... } }   ← شکل واقعی سرویس
	 *   - { data: { ...رکورد... } } / { result: ... } / { record: ... }
	 *   - { results: [ { ...رکورد... } ] } / { items: [...] }
	 *   - خودِ رکورد بدون پوشش
	 *
	 * @param array<mixed> $body بدنه‌ی JSON.
	 * @return array<mixed>
	 */
	public static function unwrap( array $body ) {
		if ( self::looks_like_record( $body ) ) {
			return $body;
		}

		foreach ( array( 'analysis', 'data', 'result', 'record' ) as $key ) {
			if ( isset( $body[ $key ] ) && is_array( $body[ $key ] ) && self::looks_like_record( $body[ $key ] ) ) {
				return $body[ $key ];
			}
		}

		foreach ( array( 'results', 'items' ) as $key ) {
			if ( ! empty( $body[ $key ] ) && is_array( $body[ $key ] ) ) {
				$first = reset( $body[ $key ] );
				if ( is_array( $first ) && self::looks_like_record( $first ) ) {
					return $first;
				}
			}
		}

		// آخرین تلاش: هر مقدار آرایه‌ای سطح اول که شکل رکورد داشته باشد (نام پوشش
		// در سرویس می‌تواند بعداً عوض شود؛ این‌جا به نام کلید وابسته نمی‌مانیم).
		foreach ( $body as $value ) {
			if ( is_array( $value ) && self::looks_like_record( $value ) ) {
				return $value;
			}
		}

		return $body;
	}

	/**
	 * آیا این آرایه شکل رکورد تحلیل را دارد؟
	 *
	 * @param array<mixed> $candidate آرایه‌ی نامزد.
	 * @return bool
	 */
	private static function looks_like_record( array $candidate ) {
		foreach ( array( 'summary', 'topics', 'sentiment', 'strengths', 'weaknesses', 'overallRating', 'overall_rating' ) as $key ) {
			if ( array_key_exists( $key, $candidate ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * ساخت ساختار داخلی از پاسخ سرویس.
	 *
	 * @param array<mixed> $body بدنه‌ی JSON.
	 * @return array<string,mixed>
	 */
	public static function normalize( array $body ) {
		$record = self::unwrap( $body );

		$sentiment = self::sentiment( self::pick( $record, array( 'sentiment' ) ) );

		return array(
			'summary'              => self::plain( self::pick( $record, array( 'summary', 'overview', 'text' ) ), self::MAX_SUMMARY ),
			'topics'               => self::topics( self::pick( $record, array( 'topics', 'keywords' ) ), self::MAX_TOPICS ),
			'sentiment'            => $sentiment,
			'strengths'            => self::points( self::pick( $record, array( 'strengths', 'pros', 'positivePoints' ) ), self::MAX_LIST ),
			'weaknesses'           => self::points( self::pick( $record, array( 'weaknesses', 'cons', 'negativePoints' ) ), self::MAX_LIST ),
			'rating'               => self::to_float( self::pick( $record, array( 'overallRating', 'overall_rating', 'rating' ) ), 0, 5 ),
			'total_comments'       => self::to_int( self::pick( $record, array( 'totalComments', 'comment_count', 'commentCount' ) ), 0, 100000000 ),
			'recommend_percentage' => self::to_int( self::pick( $record, array( 'recommendPercentage', 'recommend_percentage' ) ), 0, 100 ),
			'provider_name'        => self::plain( self::provider_name( $record ), 120 ),
			'providers'            => self::providers( self::pick( $record, array( 'providerBreakdown', 'provider_breakdown' ) ) ),
			'source_comment_count' => self::to_int( self::pick( $record, array( 'sourceCommentCount', 'sourcecommentcount' ) ), 0, 100000000 ),
			'source_provider_count' => self::to_int( self::pick( $record, array( 'sourceProviderCount', 'sourceprovidercount' ) ), 0, 1000 ),
			'updated_at'           => self::timestamp( self::pick( $record, array( 'updatedAt', 'updated_at' ) ) ),
			'stale'                => (bool) self::pick( $record, array( 'stale' ), false ),
			'is_derived'           => (bool) ( self::pick( $record, array( 'isDerived' ), null ) ?? self::pick( $record, array( 'isderived' ), false ) ),
			'scope'                => self::plain( self::pick( $record, array( 'scope' ), '' ), 40 ),
		);
	}

	/**
	 * خواندن کلید با چند نام ممکن.
	 *
	 * @param array<mixed> $source آرایه‌ی منبع.
	 * @param string[]     $keys   نام‌های ممکن به ترتیب اولویت.
	 * @param mixed        $default مقدار پیش‌فرض.
	 * @return mixed
	 */
	public static function pick( array $source, array $keys, $default = '' ) {
		foreach ( $keys as $key ) {
			if ( array_key_exists( $key, $source ) && null !== $source[ $key ] ) {
				return $source[ $key ];
			}
		}

		return $default;
	}

	/**
	 * نام سرویس‌دهنده: providerName، یا provider.name، یا provider (اگر رشته باشد).
	 *
	 * @param array<mixed> $record رکورد.
	 * @return string
	 */
	private static function provider_name( array $record ) {
		$name = self::pick( $record, array( 'providerName', 'provider_name' ), '' );

		if ( is_array( $name ) ) {
			$name = self::pick( $name, array( 'name', 'title', 'label' ), '' );
		}

		if ( '' === $name || null === $name ) {
			$provider = self::pick( $record, array( 'provider' ), '' );
			if ( is_array( $provider ) ) {
				$name = self::pick( $provider, array( 'name', 'title', 'label' ), '' );
			} elseif ( is_string( $provider ) ) {
				$name = $provider;
			}
		}

		return self::plain( $name, 120 );
	}

	/**
	 * نرمال‌سازی احساسات به شمارش‌های غیرمنفی + مجموع.
	 *
	 * @param mixed $raw مقدار خام sentiment.
	 * @return array{positive:int,negative:int,neutral:int,total:int}
	 */
	public static function sentiment( $raw ) {
		$result = array(
			'positive' => 0,
			'negative' => 0,
			'neutral'  => 0,
			'total'    => 0,
		);

		if ( ! is_array( $raw ) ) {
			return $result;
		}

		$map = array(
			'positive' => array( 'positive', 'pos' ),
			'negative' => array( 'negative', 'neg' ),
			'neutral'  => array( 'neutral', 'neu', 'mixed' ),
		);

		foreach ( $map as $target => $keys ) {
			$value          = self::to_int( self::pick( $raw, $keys, 0 ), 0, 100000000 );
			$result[ $target ] = null === $value ? 0 : $value;
		}

		$result['total'] = $result['positive'] + $result['negative'] + $result['neutral'];

		return $result;
	}

	/**
	 * نرمال‌سازی فهرست منابع (providerBreakdown) با تحمل شکل‌های مختلف.
	 *
	 * @param mixed $raw مقدار خام.
	 * @return array<int,array{name:string,comments:?int,percentage:?int,rating:?float}>
	 */
	public static function providers( $raw ) {
		if ( ! is_array( $raw ) ) {
			return array();
		}

		$providers = array();

		foreach ( $raw as $entry ) {
			if ( count( $providers ) >= self::MAX_LIST ) {
				break;
			}

			if ( is_string( $entry ) ) {
				$name = self::plain( $entry, 80 );
				if ( '' !== $name ) {
					$providers[] = array(
						'name'       => $name,
						'comments'   => null,
						'percentage' => null,
						'rating'     => null,
					);
				}
				continue;
			}

			if ( ! is_array( $entry ) ) {
				continue;
			}

			$name = self::plain( self::pick( $entry, array( 'providerName', 'name', 'provider', 'label', 'title' ), '' ), 80 );

			if ( '' === $name ) {
				continue;
			}

			$providers[] = array(
				'name'       => $name,
				'comments'   => self::to_int( self::pick( $entry, array( 'commentCount', 'comments', 'count', 'totalComments', 'total' ), null ), 0, 100000000 ),
				'percentage' => self::to_int( self::pick( $entry, array( 'percentage', 'percent', 'share' ), null ), 0, 100 ),
				'rating'     => self::to_float( self::pick( $entry, array( 'rating', 'overallRating' ), null ), 0, 5 ),
			);
		}

		return $providers;
	}

	/**
	 * یک رشته‌ی متنی ساده: بدون تگ، بدون کاراکتر کنترلی، با فاصله‌ی نرمال.
	 *
	 * @param mixed $value مقدار خام.
	 * @param int   $limit حداکثر طول.
	 * @return string
	 */
	public static function plain( $value, $limit ) {
		if ( is_array( $value ) ) {
			$value = self::pick( $value, array( 'text', 'label', 'title', 'name', 'value' ), '' );
		}

		if ( ! is_scalar( $value ) ) {
			return '';
		}

		$text = (string) $value;
		$text = self::utf8( $text );

		/*
		 * خطوط شکسته عمداً حفظ می‌شوند (remove_breaks = false)؛ چون خلاصه‌ی سرویس
		 * ممکن است فهرست‌وار باشد و ساختار آن در format_text استفاده می‌شود.
		 * وردپرس در همین تابع محتوای script/style را هم حذف می‌کند.
		 */
		if ( function_exists( 'wp_strip_all_tags' ) ) {
			$text = wp_strip_all_tags( $text, false );
		} else {
			$text = strip_tags( $text );
		}

		$text = self::utf8( (string) $text );

		// حذف کاراکترهای کنترلی، ولی نیم‌فاصله (U+200C) و فاصله‌ی عربی دست‌نخورده می‌ماند.
		$text = preg_replace( '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $text );
		$text = is_string( $text ) ? $text : '';
		$text = preg_replace( '/[ 	]+/u', ' ', $text );
		$text = is_string( $text ) ? trim( $text ) : '';

		return self::limit( $text, $limit );
	}

	/**
	 * اطمینان از معتبر بودن UTF-8.
	 *
	 * اگر سرویس بایتی نامعتبر برگرداند، preg_replace با مودیفایر /u مقدار null
	 * می‌دهد و متن خالی می‌شود؛ پس پیش از پردازش، بایت نامعتبر حذف می‌شود.
	 *
	 * @param string $text متن ورودی.
	 * @return string
	 */
	private static function utf8( $text ) {
		if ( function_exists( 'wp_check_invalid_utf8' ) ) {
			return (string) wp_check_invalid_utf8( $text, true );
		}

		if ( function_exists( 'mb_convert_encoding' ) ) {
			$converted = @mb_convert_encoding( $text, 'UTF-8', 'UTF-8' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			return is_string( $converted ) ? $converted : '';
		}

		return $text;
	}

	/**
	 * بریدن رشته بر اساس کاراکتر (با احترام به UTF-8).
	 *
	 * @param string $text  متن.
	 * @param int    $limit حداکثر طول.
	 * @return string
	 */
	public static function limit( $text, $limit ) {
		$limit = (int) $limit;
		if ( $limit <= 0 ) {
			return $text;
		}

		if ( function_exists( 'mb_substr' ) ) {
			return mb_substr( $text, 0, $limit );
		}

		// بریدن ساده‌ی substr می‌تواند یک کاراکتر چندبایتی را نصف کند و متن فارسی
		// را خراب کند؛ پس اول بریدن امن با PCRE امتحان می‌شود.
		if ( preg_match( '/^.{0,' . $limit . '}/us', $text, $matches ) === 1 ) {
			return $matches[0];
		}

		return substr( $text, 0, $limit );
	}

	/**
	 * نرمال‌سازی موضوع‌ها.
	 *
	 * شکل واقعی سرویس: { count: 10, label: "کیفیت صدا", direction: "up" }
	 * رشته‌ی ساده هم پذیرفته می‌شود (سازگاری با نسخه‌های قبلی/سایر شکل‌ها).
	 *
	 * «direction» فقط در ساختار داخلی نگه داشته می‌شود و در رابط کاربری نمایش
	 * داده نمی‌شود؛ چون معنای دقیق آن (روند یا قطبیت) از سرویس تأیید نشده است و
	 * نمایش حدسی آن می‌تواند گمراه‌کننده باشد.
	 *
	 * @param mixed $raw   مقدار خام.
	 * @param int   $limit حداکثر تعداد.
	 * @return array<int,array{label:string,count:?int,direction:string}>
	 */
	public static function topics( $raw, $limit ) {
		$entries = self::entries( $raw );
		$items   = array();

		foreach ( $entries as $entry ) {
			if ( count( $items ) >= (int) $limit ) {
				break;
			}

			$label = self::single_line(
				self::plain(
					is_array( $entry ) ? self::pick( $entry, array( 'label', 'text', 'title', 'name', 'topic', 'value' ), '' ) : $entry,
					self::MAX_ITEM
				)
			);

			if ( '' === $label ) {
				continue;
			}

			$direction = '';

			if ( is_array( $entry ) ) {
				$raw_direction = strtolower( self::plain( self::pick( $entry, array( 'direction', 'dir', 'trend' ), '' ), 12 ) );

				if ( in_array( $raw_direction, array( 'up', 'increase', 'rising' ), true ) ) {
					$direction = 'up';
				} elseif ( in_array( $raw_direction, array( 'down', 'decrease', 'falling' ), true ) ) {
					$direction = 'down';
				}
			}

			$items[] = array(
				'label'     => $label,
				'count'     => is_array( $entry ) ? self::to_int( self::pick( $entry, array( 'count', 'comments', 'mentions' ), null ), 0, 100000000 ) : null,
				'direction' => $direction,
			);
		}

		return $items;
	}

	/**
	 * نرمال‌سازی فهرست نقاط قوت/ضعف.
	 *
	 * شکل واقعی سرویس: { text: "کیفیت صدا", count: 10 }
	 *
	 * @param mixed $raw   مقدار خام.
	 * @param int   $limit حداکثر تعداد.
	 * @return array<int,array{text:string,count:?int}>
	 */
	public static function points( $raw, $limit ) {
		$entries = self::entries( $raw );
		$items   = array();

		foreach ( $entries as $entry ) {
			if ( count( $items ) >= (int) $limit ) {
				break;
			}

			$text = self::single_line( self::plain( $entry, self::MAX_ITEM ) );

			if ( '' === $text ) {
				continue;
			}

			$items[] = array(
				'text'  => $text,
				'count' => is_array( $entry ) ? self::to_int( self::pick( $entry, array( 'count', 'comments', 'mentions' ), null ), 0, 100000000 ) : null,
			);
		}

		return $items;
	}

	/**
	 * تبدیل ورودی خام به فهرست آیتم‌ها (رشته‌ی چندخطی هم پشتیبانی می‌شود).
	 *
	 * @param mixed $raw مقدار خام.
	 * @return array<mixed>
	 */
	private static function entries( $raw ) {
		if ( is_string( $raw ) ) {
			$raw = preg_split( '/\r\n|\r|\n/', $raw );
		}

		return is_array( $raw ) ? $raw : array();
	}

	/**
	 * یک‌خطی کردن متن (برای برچسب‌ها و آیتم‌های فهرست).
	 *
	 * @param string $text متن.
	 * @return string
	 */
	private static function single_line( $text ) {
		$text = preg_replace( '/\s+/u', ' ', (string) $text );
		$text = is_string( $text ) ? trim( $text ) : '';

		return self::limit( $text, self::MAX_ITEM );
	}

	/**
	 * فهرست رشته‌ها: آیتم‌های رشته‌ای یا آبجکت‌هایی با کلید متنی پذیرفته می‌شوند.
	 *
	 * @param mixed $raw   مقدار خام.
	 * @param int   $limit حداکثر تعداد آیتم.
	 * @return string[]
	 */
	public static function string_list( $raw, $limit ) {
		if ( is_string( $raw ) ) {
			$raw = preg_split( '/\r\n|\r|\n/', $raw );
		}

		if ( ! is_array( $raw ) ) {
			return array();
		}

		$items = array();

		foreach ( $raw as $entry ) {
			if ( count( $items ) >= (int) $limit ) {
				break;
			}

			$text = self::plain( $entry, self::MAX_ITEM );

			// هر آیتم در یک خط نمایش داده می‌شود؛ خطوط شکسته به فاصله تبدیل می‌شوند.
			$text = trim( (string) preg_replace( '/\s+/u', ' ', $text ) );
			$text = self::limit( $text, self::MAX_ITEM );

			if ( '' !== $text ) {
				$items[] = $text;
			}
		}

		return $items;
	}

	/**
	 * تبدیل به عدد صحیح در بازه‌ی مجاز؛ اگر عددی نبود null.
	 *
	 * @param mixed    $value مقدار خام.
	 * @param int|null $min   کمینه.
	 * @param int|null $max   بیشینه.
	 * @return int|null
	 */
	public static function to_int( $value, $min = null, $max = null ) {
		$number = self::extract_number( $value );

		if ( null === $number ) {
			return null;
		}

		$number = (int) round( $number );

		if ( null !== $min ) {
			$number = max( (int) $min, $number );
		}

		if ( null !== $max ) {
			$number = min( (int) $max, $number );
		}

		return $number;
	}

	/**
	 * تبدیل به عدد اعشاری در بازه‌ی مجاز؛ اگر عددی نبود null.
	 *
	 * @param mixed      $value مقدار خام.
	 * @param float|null $min   کمینه.
	 * @param float|null $max   بیشینه.
	 * @return float|null
	 */
	public static function to_float( $value, $min = null, $max = null ) {
		$number = self::extract_number( $value );

		if ( null === $number ) {
			return null;
		}

		if ( null !== $min ) {
			$number = max( (float) $min, $number );
		}

		if ( null !== $max ) {
			$number = min( (float) $max, $number );
		}

		return $number;
	}

	/**
	 * استخراج عدد از یک مقدار خام.
	 *
	 * سرویس ممکن است عدد را با جداکننده («۱٬۲۳۴») یا با پسوند («۷۸٪» / «78%»)
	 * برگرداند؛ هر دو پذیرفته می‌شود و در نهایت یک عدد تمیز تحویل داده می‌شود.
	 *
	 * @param mixed $value مقدار خام.
	 * @return float|null
	 */
	private static function extract_number( $value ) {
		if ( is_bool( $value ) || null === $value || is_array( $value ) || is_object( $value ) ) {
			return null;
		}

		if ( is_int( $value ) || is_float( $value ) ) {
			return (float) $value;
		}

		$text = str_replace( array( ',', '٬', '٫', ' ', "\xc2\xa0" ), '', trim( (string) $value ) );

		// ارقام فارسی/عربی هم پذیرفته می‌شوند («۱۵۰» → «150»).
		$text = self::latin_digits( $text );

		if ( '' === $text ) {
			return null;
		}

		if ( is_numeric( $text ) ) {
			return (float) $text;
		}

		// پسوند/پیشوند مانند «%» یا «٪» یا «نظر».
		if ( preg_match( '/-?\d+(?:\.\d+)?/', $text, $matches ) === 1 ) {
			return (float) $matches[0];
		}

		return null;
	}

	/**
	 * تبدیل ارقام فارسی و عربی به ارقام لاتین.
	 *
	 * @param string $text متن.
	 * @return string
	 */
	private static function latin_digits( $text ) {
		$map = array(
			'۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
			'۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
			'٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
			'٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
		);

		return strtr( $text, $map );
	}

	/**
	 * تبدیل updatedAt به زمان یونیکس؛ فقط ISO-8601 و مقادیر عددی پذیرفته می‌شوند.
	 *
	 * @param mixed $value مقدار خام.
	 * @return int|null
	 */
	public static function timestamp( $value ) {
		if ( is_numeric( $value ) ) {
			$stamp = (int) $value;
			return $stamp > 0 ? $stamp : null;
		}

		if ( ! is_string( $value ) || '' === trim( $value ) ) {
			return null;
		}

		$stamp = strtotime( trim( $value ) );

		return ( is_int( $stamp ) && $stamp > 0 ) ? $stamp : null;
	}

	/**
	 * قالب‌بندی متن ساده‌ی سرویس برای نمایش، بدون هیچ HTML ورودی.
	 *
	 * ابتدا متن escape می‌شود، سپس فقط سه قاعده‌ی امن اعمال می‌شود:
	 *   ۱) خطوطی که با - یا • یا * شروع می‌شوند به فهرست تبدیل می‌شوند،
	 *   ۲) **متن** به <strong> تبدیل می‌شود،
	 *   ۳) خطوط معمولی به <p> تبدیل می‌شوند.
	 *
	 * چون escape پیش از ساخت تگ‌ها انجام می‌شود، امکان تزریق HTML از سمت سرویس
	 * وجود ندارد.
	 *
	 * @param string $text متن پاک‌شده.
	 * @return string HTML امن.
	 */
	public static function format_text( $text ) {
		$text = (string) $text;

		if ( '' === trim( $text ) ) {
			return '';
		}

		$escaped = function_exists( 'esc_html' ) ? esc_html( $text ) : htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );

		// **bold** → <strong>bold</strong> (پس از escape، پس مقدار درون تگ امن است).
		$escaped = preg_replace( '/\*\*(.+?)\*\*/u', '<strong>$1</strong>', $escaped );
		$escaped = is_string( $escaped ) ? $escaped : '';

		$lines  = preg_split( '/\r\n|\r|\n/', $escaped );
		$lines  = is_array( $lines ) ? $lines : array( $escaped );
		$html   = '';
		$in_list = false;

		foreach ( $lines as $line ) {
			$trimmed = trim( $line );

			if ( '' === $trimmed ) {
				if ( $in_list ) {
					$html    .= '</ul>';
					$in_list = false;
				}
				continue;
			}

			if ( preg_match( '/^(?:[-•*‣]|\x{2013})\s*(.+)$/u', $trimmed, $matches ) ) {
				if ( ! $in_list ) {
					$html   .= '<ul>';
					$in_list = true;
				}
				$html .= '<li>' . $matches[1] . '</li>';
				continue;
			}

			if ( $in_list ) {
				$html   .= '</ul>';
				$in_list = false;
			}

			$html .= '<p>' . $trimmed . '</p>';
		}

		if ( $in_list ) {
			$html .= '</ul>';
		}

		return $html;
	}
}
