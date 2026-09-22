<?php
/**
 * پیشخوان افزونه‌ی «خلاصه نظرات محصولات».
 *
 * - انتخاب حالت نمایش (خاموش / خلاصه / خلاصه + نقاط قوت و ضعف)
 * - وضعیت توکن و اتصال (بدون نمایش مقدار توکن)
 * - بررسی زنده‌ی پاسخ سرویس برای یک محصول (بدون نوشتن کش)
 * - پاک کردن کش تحلیل‌ها
 *
 * @package TS_Comments_Overview
 */

defined( 'ABSPATH' ) || exit;

/**
 * صفحه‌ی تنظیمات و ابزارهای بررسی.
 */
final class TS_Comments_Overview_Admin {

	/** اسلاگ صفحه‌ی تنظیمات. */
	const PAGE_SLUG = 'ts-comments-overview';

	/** نام اکشن بررسی زنده. */
	const ACTION_TEST = 'ts_comments_overview_test';

	/** نام اکشن پاک کردن کش. */
	const ACTION_FLUSH = 'ts_comments_overview_flush';

	/** نام آپشن موقت نتیجه‌ی بررسی. */
	const TEST_TRANSIENT = 'ts_co_last_test_';

	/**
	 * ثبت هوک‌های پیشخوان.
	 *
	 * @return void
	 */
	public static function register_hooks() {
		add_action( 'admin_menu', array( __CLASS__, 'add_menu' ) );
		add_action( 'admin_post_' . self::ACTION_TEST, array( __CLASS__, 'handle_test' ) );
		add_action( 'admin_post_' . self::ACTION_FLUSH, array( __CLASS__, 'handle_flush' ) );
		add_action( 'admin_notices', array( __CLASS__, 'configuration_notice' ) );
		add_filter( 'plugin_action_links_' . TS_COMMENTS_OVERVIEW_BASENAME, array( __CLASS__, 'action_links' ) );
	}

	/**
	 * افزودن صفحه زیر منوی «تنظیمات».
	 *
	 * @return void
	 */
	public static function add_menu() {
		add_options_page(
			'خلاصه نظرات محصولات',
			'خلاصه نظرات محصولات',
			'manage_options',
			self::PAGE_SLUG,
			array( __CLASS__, 'render_page' )
		);
	}

	/**
	 * افزودن پیوند تنظیمات به فهرست افزونه‌ها.
	 *
	 * @param string[] $links پیوندها.
	 * @return string[]
	 */
	public static function action_links( $links ) {
		$url = self::page_url();

		array_unshift( $links, '<a href="' . esc_url( $url ) . '">تنظیمات</a>' );

		return $links;
	}

	/**
	 * نشانی صفحه‌ی تنظیمات.
	 *
	 * @return string
	 */
	public static function page_url() {
		return admin_url( 'options-general.php?page=' . self::PAGE_SLUG );
	}

	/**
	 * هشدار پیشخوان وقتی حالت روشن است ولی اتصال کامل نیست.
	 *
	 * @return void
	 */
	public static function configuration_notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( ! TS_Comments_Overview_Settings::is_enabled() || TS_Comments_Overview_Settings::is_configured() ) {
			return;
		}

		$token_missing = '' === TS_Comments_Overview_Settings::api_token();

		printf(
			'<div class="notice notice-warning"><p><strong>خلاصه نظرات محصولات:</strong> %s <a href="%s">%s</a></p></div>',
			esc_html(
				$token_missing
					? 'حالت نمایش روشن است، اما توکن سرویس تحلیل نظرات در wp-config.php تنظیم نشده؛ بنابراین بخش خلاصه در سایت نمایش داده نمی‌شود.'
					: 'حالت نمایش روشن است، اما نشانی سرویس تحلیل نظرات معتبر نیست؛ بنابراین بخش خلاصه در سایت نمایش داده نمی‌شود.'
			),
			esc_url( self::page_url() ),
			esc_html( 'رفتن به تنظیمات' )
		);
	}

	/**
	 * پردازش درخواست «بررسی زنده».
	 *
	 * @return void
	 */
	public static function handle_test() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'دسترسی کافی برای این کار وجود ندارد.' );
		}

		check_admin_referer( self::ACTION_TEST );

		$product_id = isset( $_POST['product_id'] ) ? absint( wp_unslash( $_POST['product_id'] ) ) : 0;
		$result     = TS_Comments_Overview_API::get_live( $product_id );

		if ( is_wp_error( $result ) ) {
			$payload = array(
				'ok'        => false,
				'code'      => $result->get_error_code(),
				'message'   => $result->get_error_message(),
				'productId' => $product_id,
			);
		} else {
			$payload = array(
				'ok'        => true,
				'productId' => $product_id,
				'data'      => $result['data'],
				'raw'       => isset( $result['raw'] ) ? $result['raw'] : array(),
			);
		}

		set_transient( self::TEST_TRANSIENT . get_current_user_id(), $payload, 300 );

		wp_safe_redirect( add_query_arg( 'ts-co-test', $payload['ok'] ? 'ok' : 'fail', self::page_url() ) );
		exit;
	}

	/**
	 * پردازش درخواست «پاک کردن کش».
	 *
	 * @return void
	 */
	public static function handle_flush() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'دسترسی کافی برای این کار وجود ندارد.' );
		}

		check_admin_referer( self::ACTION_FLUSH );

		$product_id = isset( $_POST['product_id'] ) ? absint( wp_unslash( $_POST['product_id'] ) ) : 0;

		if ( $product_id > 0 ) {
			TS_Comments_Overview_API::flush( $product_id );
			$count = 1;
		} else {
			$count = TS_Comments_Overview_API::flush_all();
		}

		wp_safe_redirect( add_query_arg( 'ts-co-flushed', (int) $count, self::page_url() ) );
		exit;
	}

	/**
	 * وضعیت یکپارچگی با قالب فعال (آیا قالب هوک را صدا می‌زند؟).
	 *
	 * @return array{integrated:bool,checked:string[]}
	 */
	public static function theme_integration_status() {
		$hook  = defined( 'TS_COMMENTS_OVERVIEW_HOOK' ) ? TS_COMMENTS_OVERVIEW_HOOK : 'wbs_product_comments_overview';
		$paths = array(
			trailingslashit( get_stylesheet_directory() ) . 'lib/Product/template/desktop/comments.php',
			trailingslashit( get_stylesheet_directory() ) . 'lib/Product/template/mobile/panels/comments.php',
		);

		$checked   = array();
		$integrated = false;

		foreach ( $paths as $path ) {
			$exists    = file_exists( $path );
			$calls     = false;

			if ( $exists ) {
				$contents = (string) file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
				$calls    = false !== strpos( $contents, $hook );
			}

			if ( $calls ) {
				$integrated = true;
			}

			$checked[ $path ] = $calls ? 'hook' : ( $exists ? 'missing-hook' : 'missing-file' );
		}

		return array(
			'integrated' => $integrated,
			'checked'    => $checked,
		);
	}

	/**
	 * خروجی امن برای نمایش در متن‌های پیشخوان.
	 *
	 * @param mixed $value مقدار.
	 * @return string
	 */
	private static function display_value( $value ) {
		if ( is_bool( $value ) ) {
			return $value ? 'بله' : 'خیر';
		}

		if ( null === $value ) {
			return '—';
		}

		if ( is_array( $value ) ) {
			$value = wp_json_encode( $value, JSON_UNESCAPED_UNICODE );
			return is_string( $value ) ? $value : '';
		}

		return (string) $value;
	}

	/**
	 * رندر صفحه‌ی تنظیمات.
	 *
	 * @return void
	 */
	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'دسترسی کافی برای مشاهده این صفحه وجود ندارد.' );
		}

		$settings    = TS_Comments_Overview_Settings::get_all();
		$mode        = isset( $settings['mode'] ) ? (string) $settings['mode'] : TS_Comments_Overview_Settings::MODE_OFF;
		$token       = TS_Comments_Overview_Settings::api_token();
		$endpoint    = TS_Comments_Overview_Settings::api_endpoint();
		$integration = self::theme_integration_status();
		$last_test   = get_transient( self::TEST_TRANSIENT . get_current_user_id() );
		$cached_ids  = TS_Comments_Overview_API::index();
		?>
		<div class="wrap">
			<h1>خلاصه نظرات محصولات</h1>

			<?php if ( isset( $_GET['ts-co-flushed'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
				<div class="notice notice-success is-dismissible">
					<p>حافظه‌ی موقت پاک شد (<?php echo esc_html( (string) absint( wp_unslash( $_GET['ts-co-flushed'] ) ) ); ?> مورد).</p>
				</div>
			<?php endif; ?>

			<?php if ( isset( $_GET['ts-co-test'] ) && 'fail' === $_GET['ts-co-test'] ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
				<div class="notice notice-error is-dismissible">
					<p>بررسی زنده ناموفق بود؛ جزئیات در پایین صفحه آمده است.</p>
				</div>
			<?php endif; ?>

			<p>این افزونه خلاصه‌ی نظرات کاربران در سایت‌های دیگر را از سرویس تحلیل نظرات می‌گیرد و آن را بالای فهرست نظرات صفحه‌ی محصول نمایش می‌دهد.</p>

			<h2>حالت نمایش</h2>
			<form method="post" action="options.php">
				<?php settings_fields( 'ts_comments_overview' ); ?>
				<table class="form-table" role="presentation">
					<tbody>
						<tr>
							<th scope="row">نمایش بخش خلاصه</th>
							<td>
								<fieldset>
									<legend class="screen-reader-text">حالت نمایش بخش خلاصه</legend>
									<?php foreach ( TS_Comments_Overview_Settings::modes() as $value => $label ) : ?>
										<label style="display:block;margin-bottom:8px">
											<input
												type="radio"
												name="<?php echo esc_attr( TS_Comments_Overview_Settings::OPTION ); ?>[mode]"
												value="<?php echo esc_attr( $value ); ?>"
												<?php checked( $mode, $value ); ?>
											/>
											<?php echo esc_html( $label ); ?>
										</label>
									<?php endforeach; ?>
									<p class="description">
										نقاط قوت و ضعف از فیلدهای <code>strengths</code> و <code>weaknesses</code> سرویس پر می‌شوند؛ اگر برای محصولی خالی باشند، فقط خلاصه نمایش داده می‌شود.
									</p>
								</fieldset>
							</td>
						</tr>
					</tbody>
				</table>
				<?php submit_button( 'ذخیره‌ی حالت نمایش' ); ?>
			</form>

			<h2>اتصال به سرویس</h2>
			<table class="form-table" role="presentation">
				<tbody>
					<tr>
						<th scope="row">توکن (X-API-Token)</th>
						<td>
							<?php if ( '' !== $token ) : ?>
								<code>تنظیم شده — <?php echo esc_html( (string) strlen( $token ) ); ?> کاراکتر</code>
								<p class="description">
									از ثابت <code>TS_COMMENTS_OVERVIEW_API_TOKEN</code> در <code>wp-config.php</code> خوانده می‌شود. مقدار توکن در دیتابیس ذخیره نمی‌شود و اینجا نمایش داده نمی‌شود.
								</p>
							<?php else : ?>
								<code>تنظیم نشده</code>
								<p class="description">
									برای فعال شدن بخش خلاصه، این خط را در <code>wp-config.php</code> بگذارید:<br />
									<code>define( 'TS_COMMENTS_OVERVIEW_API_TOKEN', 'توکن-واقعی' );</code>
								</p>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<th scope="row">نشانی سرویس</th>
						<td>
							<?php if ( '' !== $endpoint ) : ?>
								<code><?php echo esc_html( $endpoint ); ?></code>
							<?php else : ?>
								<code style="color:#b32d2e">نامعتبر (فقط https پذیرفته می‌شود)</code>
							<?php endif; ?>
							<p class="description">
								درخواست با <code>?woocommerce_id=&lt;شناسه محصول&gt;</code> و هدر <code>X-API-Token</code> ارسال می‌شود.
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row">یکپارچگی با قالب</th>
						<td>
							<?php if ( $integration['integrated'] ) : ?>
								<code>قالب فعال، هوک <code><?php echo esc_html( defined( 'TS_COMMENTS_OVERVIEW_HOOK' ) ? TS_COMMENTS_OVERVIEW_HOOK : 'wbs_product_comments_overview' ); ?></code> را صدا می‌زند.</code>
							<?php else : ?>
								<code style="color:#b32d2e">هوک در قالب فعال پیدا نشد؛ بخش خلاصه جایی برای نمایش ندارد.</code>
								<ul style="list-style:disc;margin-inline-start:20px">
									<?php foreach ( $integration['checked'] as $path => $state ) : ?>
										<li><code><?php echo esc_html( $path ); ?></code> — <?php echo esc_html( $state ); ?></li>
									<?php endforeach; ?>
								</ul>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<th scope="row">حافظه‌ی موقت</th>
						<td>
							<p class="description">
								هر تحلیل تا <?php echo esc_html( (string) ( (int) TS_Comments_Overview_Settings::cache_ttl() / 3600 ) ); ?> ساعت کش می‌شود و خطاها <?php echo esc_html( (string) ( (int) TS_Comments_Overview_Settings::error_ttl() / 60 ) ); ?> دقیقه؛ تا سرویس خواب‌رفته، صفحه‌ی محصول کند نشود.
								در حال حاضر <?php echo esc_html( (string) count( $cached_ids ) ); ?> محصول در حافظه‌ی موقت است.
							</p>
							<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
								<?php wp_nonce_field( self::ACTION_FLUSH ); ?>
								<input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION_FLUSH ); ?>" />
								<p>
									<label>
										شناسه‌ی محصول (خالی = همه)
										<input type="number" min="0" name="product_id" value="" />
									</label>
								</p>
								<?php submit_button( 'پاک کردن حافظه‌ی موقت', 'secondary', 'submit', false ); ?>
							</form>
						</td>
					</tr>
				</tbody>
			</table>

			<h2>بررسی زنده‌ی سرویس</h2>
			<p>یک شناسه‌ی محصول ووکامرس بدهید تا پاسخ واقعی سرویس (بدون نوشتن در حافظه‌ی موقت) همین‌جا نمایش داده شود. این ابزار برای تطبیق ساختار پیلود سرویس با نمایش سایت است.</p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( self::ACTION_TEST ); ?>
				<input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION_TEST ); ?>" />
				<p>
					<label>
						شناسه‌ی محصول
						<input type="number" min="1" name="product_id" required />
					</label>
					<?php submit_button( 'دریافت از سرویس', 'secondary', 'submit', false ); ?>
				</p>
			</form>

			<?php if ( is_array( $last_test ) ) : ?>
				<h3>نتیجه‌ی آخرین بررسی</h3>
				<?php if ( empty( $last_test['ok'] ) ) : ?>
					<p><code style="color:#b32d2e"><?php echo esc_html( isset( $last_test['message'] ) ? (string) $last_test['message'] : 'خطای نامشخص' ); ?></code></p>
					<p class="description">کد خطا: <code><?php echo esc_html( isset( $last_test['code'] ) ? (string) $last_test['code'] : '' ); ?></code></p>
				<?php else : ?>
					<p>محصول <code><?php echo esc_html( isset( $last_test['productId'] ) ? (string) $last_test['productId'] : '' ); ?></code> — داده‌ی نرمال‌شده‌ای که در سایت نمایش داده می‌شود:</p>
					<table class="widefat striped" style="max-width:900px">
						<tbody>
							<?php
							$data = isset( $last_test['data'] ) && is_array( $last_test['data'] ) ? $last_test['data'] : array();
							foreach ( $data as $key => $value ) :
								?>
								<tr>
									<td style="width:220px"><code><?php echo esc_html( (string) $key ); ?></code></td>
									<td><?php echo esc_html( self::display_value( $value ) ); ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
					<details style="margin-top:12px">
						<summary>پاسخ خام سرویس</summary>
						<pre style="max-height:360px;overflow:auto;background:#fff;border:1px solid #ccd0d4;padding:12px"><?php echo esc_html( wp_json_encode( isset( $last_test['raw'] ) ? $last_test['raw'] : array(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) ); ?></pre>
					</details>
				<?php endif; ?>
			<?php endif; ?>
		</div>
		<?php
	}
}
