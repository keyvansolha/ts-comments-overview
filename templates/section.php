<?php
/**
 * قالب بخش «خلاصه نظرات کاربران».
 *
 * متغیرهای موجود در این فایل:
 *
 * @var array<string,mixed> $data ساختار نرمال‌شده‌ی سرویس تحلیل نظرات.
 * @var string              $mode حالت نمایش فعلی (summary | summary_pros_cons).
 *
 * قواعد این قالب:
 *   - هیچ رنگی به‌صورت مستقیم تعیین نمی‌شود؛ همه‌چیز از توکن‌های معنایی قالب
 *     (theme-system.css) می‌آید و بنابراین دارک/لایت خودکار درست است.
 *   - هر مقدار متنی با esc_html / esc_attr چاپ می‌شود.
 *   - خلاصه با قالب‌بندی امن ساخته‌شده در TS_Comments_Overview_Payload
 *     (escape پیش از ساخت تگ) چاپ می‌شود.
 *
 * @package TS_Comments_Overview
 */

defined( 'ABSPATH' ) || exit;

if ( ! isset( $data ) || ! is_array( $data ) ) {
	return;
}

$ts_co_sentiment      = isset( $data['sentiment'] ) && is_array( $data['sentiment'] ) ? $data['sentiment'] : array(
	'positive' => 0,
	'negative' => 0,
	'neutral'  => 0,
	'total'    => 0,
);
$ts_co_total          = (int) $ts_co_sentiment['total'];
$ts_co_rating         = isset( $data['rating'] ) ? $data['rating'] : null;
$ts_co_recommend      = isset( $data['recommend_percentage'] ) ? $data['recommend_percentage'] : null;
$ts_co_comments       = isset( $data['total_comments'] ) ? $data['total_comments'] : null;
$ts_co_sources        = isset( $data['providers'] ) && is_array( $data['providers'] ) ? $data['providers'] : array();
$ts_co_provider_count = isset( $data['source_provider_count'] ) ? $data['source_provider_count'] : null;
$ts_co_summary_html   = TS_Comments_Overview_Payload::format_text( isset( $data['summary'] ) ? (string) $data['summary'] : '' );
$ts_co_topics         = isset( $data['topics'] ) && is_array( $data['topics'] ) ? $data['topics'] : array();
$ts_co_strengths      = isset( $data['strengths'] ) && is_array( $data['strengths'] ) ? $data['strengths'] : array();
$ts_co_weaknesses     = isset( $data['weaknesses'] ) && is_array( $data['weaknesses'] ) ? $data['weaknesses'] : array();
$ts_co_updated        = TS_Comments_Overview_Render::updated_label( isset( $data['updated_at'] ) ? $data['updated_at'] : null );
$ts_co_is_derived     = ! empty( $data['is_derived'] );
$ts_co_stale          = ! empty( $data['stale'] );

$ts_co_show_pros_cons = TS_Comments_Overview_Settings::shows_pros_cons()
	&& ( ! empty( $ts_co_strengths ) || ! empty( $ts_co_weaknesses ) );

$ts_co_source_count = null !== $ts_co_provider_count
	? (int) $ts_co_provider_count
	: ( ! empty( $ts_co_sources ) ? count( $ts_co_sources ) : null );

// اگر هیچ عددی برای نمایش نباشد، ردیف آمار ساخته نمی‌شود.
$ts_co_has_stats = null !== $ts_co_rating || null !== $ts_co_recommend || ! empty( $ts_co_comments );
?>
<section class="ts-comments-overview" dir="rtl" aria-labelledby="ts-comments-overview-title">
	<header class="ts-comments-overview__header">
		<div class="ts-comments-overview__heading">
			<h3 id="ts-comments-overview-title" class="ts-comments-overview__title">
				آنچه کاربران درباره این محصول می‌گویند
			</h3>
			<p class="ts-comments-overview__subtitle">خلاصه‌ی خودکار نظرات کاربران در سایت‌های دیگر درباره‌ی این محصول.</p>
		</div>
		<span class="ts-comments-overview__badge">
			<i class="icon-chat" aria-hidden="true"></i>
			<?php echo $ts_co_is_derived ? 'خلاصه‌ی هوشمند' : 'خلاصه‌ی نظرات'; ?>
		</span>
	</header>

	<?php if ( $ts_co_has_stats ) : ?>
		<ul class="ts-comments-overview__stats">
			<?php if ( null !== $ts_co_rating ) : ?>
				<li class="ts-comments-overview__stat">
					<span class="ts-comments-overview__stat-value">
						<i class="icon-star-empty" aria-hidden="true"></i>
						<?php echo esc_html( TS_Comments_Overview_Render::rating_label( $ts_co_rating ) ); ?>
					</span>
					<span class="ts-comments-overview__stat-label">امتیاز کل</span>
				</li>
			<?php endif; ?>

			<?php if ( null !== $ts_co_recommend ) : ?>
				<li class="ts-comments-overview__stat">
					<span class="ts-comments-overview__stat-value">
						<i class="icon-like" aria-hidden="true"></i>
						<?php echo esc_html( TS_Comments_Overview_Render::number_label( $ts_co_recommend ) ); ?>٪
					</span>
					<span class="ts-comments-overview__stat-label">پیشنهاد می‌کنند</span>
				</li>
			<?php endif; ?>

			<?php if ( ! empty( $ts_co_comments ) ) : ?>
				<li class="ts-comments-overview__stat">
					<span class="ts-comments-overview__stat-value">
						<i class="icon-chat" aria-hidden="true"></i>
						<?php echo esc_html( TS_Comments_Overview_Render::number_label( $ts_co_comments ) ); ?>
					</span>
					<span class="ts-comments-overview__stat-label">نظر از سایت‌های دیگر</span>
				</li>
			<?php endif; ?>
		</ul>
	<?php endif; ?>

	<?php if ( $ts_co_total > 0 ) : ?>
		<?php
		$ts_co_percent = array(
			'positive' => TS_Comments_Overview_Render::percentage( $ts_co_sentiment['positive'], $ts_co_total ),
			'neutral'  => TS_Comments_Overview_Render::percentage( $ts_co_sentiment['neutral'], $ts_co_total ),
			'negative' => TS_Comments_Overview_Render::percentage( $ts_co_sentiment['negative'], $ts_co_total ),
		);
		?>
		<div class="ts-comments-overview__sentiment">
			<div class="ts-comments-overview__sentiment-head">
				<span class="ts-comments-overview__sentiment-title">حال‌و‌هوای کلی نظرات</span>
				<span class="ts-comments-overview__sentiment-total">
					بر پایه‌ی <?php echo esc_html( TS_Comments_Overview_Render::number_label( $ts_co_total ) ); ?> نظر تحلیل‌شده
				</span>
			</div>

			<div
				class="ts-comments-overview__bar"
				role="img"
				aria-label="<?php echo esc_attr( 'نظرات مثبت ' . $ts_co_percent['positive'] . ' درصد، بی‌طرف ' . $ts_co_percent['neutral'] . ' درصد، منفی ' . $ts_co_percent['negative'] . ' درصد' ); ?>"
			>
				<?php if ( $ts_co_sentiment['positive'] > 0 ) : ?>
					<?php
					/*
					 * عرض با flex-grow بر پایه‌ی شمارش خام تعیین می‌شود، نه درصد
					 * گردشده؛ وگرنه جمع درصدهای گردشده می‌تواند ۹۹٪ شود و ته نوار
					 * یک شکاف خالی بماند. --ts-co-share فقط برای انیمیشن/دیباگ است.
					 */
					?>
					<span
						class="ts-comments-overview__bar-seg ts-comments-overview__bar-seg--positive"
						style="--ts-co-share: <?php echo (int) $ts_co_percent['positive']; ?>%; --ts-co-count: <?php echo (int) $ts_co_sentiment['positive']; ?>"
					></span>
				<?php endif; ?>
				<?php if ( $ts_co_sentiment['neutral'] > 0 ) : ?>
					<span
						class="ts-comments-overview__bar-seg ts-comments-overview__bar-seg--neutral"
						style="--ts-co-share: <?php echo (int) $ts_co_percent['neutral']; ?>%; --ts-co-count: <?php echo (int) $ts_co_sentiment['neutral']; ?>"
					></span>
				<?php endif; ?>
				<?php if ( $ts_co_sentiment['negative'] > 0 ) : ?>
					<span
						class="ts-comments-overview__bar-seg ts-comments-overview__bar-seg--negative"
						style="--ts-co-share: <?php echo (int) $ts_co_percent['negative']; ?>%; --ts-co-count: <?php echo (int) $ts_co_sentiment['negative']; ?>"
					></span>
				<?php endif; ?>
			</div>

			<ul class="ts-comments-overview__legend">
				<li class="ts-comments-overview__legend-item ts-comments-overview__legend-item--positive">
					<span class="ts-comments-overview__dot" aria-hidden="true"></span>
					مثبت
					<strong><?php echo esc_html( $ts_co_percent['positive'] ); ?>٪</strong>
				</li>
				<li class="ts-comments-overview__legend-item ts-comments-overview__legend-item--neutral">
					<span class="ts-comments-overview__dot" aria-hidden="true"></span>
					بی‌طرف
					<strong><?php echo esc_html( $ts_co_percent['neutral'] ); ?>٪</strong>
				</li>
				<li class="ts-comments-overview__legend-item ts-comments-overview__legend-item--negative">
					<span class="ts-comments-overview__dot" aria-hidden="true"></span>
					منفی
					<strong><?php echo esc_html( $ts_co_percent['negative'] ); ?>٪</strong>
				</li>
			</ul>
		</div>
	<?php endif; ?>

	<?php if ( '' !== $ts_co_summary_html ) : ?>
		<div class="ts-comments-overview__summary">
			<?php echo wp_kses_post( $ts_co_summary_html ); ?>
		</div>
	<?php endif; ?>

	<?php if ( ! empty( $ts_co_topics ) ) : ?>
		<div class="ts-comments-overview__topics">
			<span class="ts-comments-overview__topics-title">
				<i class="icon-categories" aria-hidden="true"></i>
				موضوع‌هایی که بیشتر درباره‌شان حرف زده شده
			</span>
			<ul class="ts-comments-overview__topics-list">
				<?php foreach ( $ts_co_topics as $ts_co_topic ) : ?>
					<li class="ts-comments-overview__topic"><?php echo esc_html( $ts_co_topic ); ?></li>
				<?php endforeach; ?>
			</ul>
		</div>
	<?php endif; ?>

	<?php if ( $ts_co_show_pros_cons ) : ?>
		<div class="ts-comments-overview__panels">
			<?php if ( ! empty( $ts_co_strengths ) ) : ?>
				<div class="ts-comments-overview__panel ts-comments-overview__panel--pros">
					<p class="ts-comments-overview__panel-title">
						<i class="icon-like" aria-hidden="true"></i>
						نقاط قوت
					</p>
					<ul class="ts-comments-overview__list">
						<?php foreach ( $ts_co_strengths as $ts_co_strength ) : ?>
							<li class="ts-comments-overview__list-item"><?php echo esc_html( $ts_co_strength ); ?></li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endif; ?>

			<?php if ( ! empty( $ts_co_weaknesses ) ) : ?>
				<div class="ts-comments-overview__panel ts-comments-overview__panel--cons">
					<p class="ts-comments-overview__panel-title">
						<i class="icon-dislike" aria-hidden="true"></i>
						نقاط ضعف
					</p>
					<ul class="ts-comments-overview__list">
						<?php foreach ( $ts_co_weaknesses as $ts_co_weakness ) : ?>
							<li class="ts-comments-overview__list-item"><?php echo esc_html( $ts_co_weakness ); ?></li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<footer class="ts-comments-overview__footer">
		<p class="ts-comments-overview__meta">
			<?php if ( $ts_co_stale ) : ?>
				<span class="ts-comments-overview__chip ts-comments-overview__chip--stale">
					<i class="icon-history" aria-hidden="true"></i>
					به‌روزرسانی در انتظار است
				</span>
			<?php endif; ?>

			<?php if ( null !== $ts_co_source_count && $ts_co_source_count > 0 ) : ?>
				<span class="ts-comments-overview__chip">
					<i class="icon-global" aria-hidden="true"></i>
					بر پایه‌ی <?php echo esc_html( TS_Comments_Overview_Render::number_label( $ts_co_source_count ) ); ?> منبع
				</span>
			<?php endif; ?>

			<?php if ( '' !== $ts_co_updated ) : ?>
				<span class="ts-comments-overview__chip">
					<i class="icon-clock" aria-hidden="true"></i>
					آخرین به‌روزرسانی: <?php echo esc_html( $ts_co_updated ); ?>
				</span>
			<?php endif; ?>
		</p>

		<?php if ( ! empty( $ts_co_sources ) ) : ?>
			<ul class="ts-comments-overview__sources">
				<li class="ts-comments-overview__sources-title">منابع:</li>
				<?php foreach ( $ts_co_sources as $ts_co_source ) : ?>
					<li class="ts-comments-overview__source">
						<span class="ts-comments-overview__source-name"><?php echo esc_html( $ts_co_source['name'] ); ?></span>
						<?php if ( null !== $ts_co_source['comments'] && $ts_co_source['comments'] > 0 ) : ?>
							<span class="ts-comments-overview__source-meta">
								<?php echo esc_html( TS_Comments_Overview_Render::number_label( $ts_co_source['comments'] ) ); ?> نظر
							</span>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>

		<p class="ts-comments-overview__disclosure">
			این خلاصه به‌صورت خودکار از مجموع نظرات کاربران در سایت‌های مرجع ساخته شده است و نظر یا تأیید تهران‌اسپیکر نیست.
		</p>
	</footer>
</section>
