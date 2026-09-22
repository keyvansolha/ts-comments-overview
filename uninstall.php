<?php
/**
 * پاک‌سازی هنگام حذف افزونه.
 *
 * فقط داده‌هایی که خود افزونه ساخته است حذف می‌شوند:
 *   - آپشن تنظیمات و ایندکس کش
 *   - ترنزینت‌های کش تحلیل نظرات
 *
 * توکن در wp-config.php است و به‌عمد دست‌نخورده می‌ماند؛ حذف خودکار آن می‌تواند
 * نصب را بی‌صدا خراب کند.
 *
 * @package TS_Comments_Overview
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'ts_comments_overview_settings' );

$ts_comments_overview_index = get_option( 'ts_comments_overview_cache_index', array() );

if ( is_array( $ts_comments_overview_index ) ) {
	foreach ( $ts_comments_overview_index as $ts_comments_overview_id ) {
		$ts_comments_overview_id = absint( $ts_comments_overview_id );

		if ( $ts_comments_overview_id > 0 ) {
			delete_transient( 'ts_co_analysis_' . $ts_comments_overview_id );
			delete_transient( 'ts_co_error_' . $ts_comments_overview_id );
		}
	}
}

delete_option( 'ts_comments_overview_cache_index' );
