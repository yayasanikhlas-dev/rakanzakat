<?php
/**
 * Stitch landing sections (screenshot blocks) for Elementor widgets.
 *
 * @package RakanZakat
 */

defined( 'ABSPATH' ) || exit;

class RakanZakat_Stitch_Sections {

	public static function href( $control, $fallback = '#' ) {
		if ( is_array( $control ) && ! empty( $control['url'] ) ) {
			return $control['url'];
		}
		if ( is_string( $control ) && '' !== $control ) {
			return $control;
		}
		return $fallback;
	}

	public static function icon( $name ) {
		$icons = array(
			'help'     => '<path d="M11 18h2v-2h-2v2zm1-16C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm0-14c-2.21 0-4 1.79-4 4h2c0-1.1.9-2 2-2s2 .9 2 2c0 2-3 1.75-3 5h2c0-2.25 3-2.5 3-5 0-2.21-1.79-4-4-4z"/>',
			'calc'     => '<path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V5h14v14zM7 7h2v2H7V7zm4 0h2v2h-2V7zm4 0h2v2h-2V7zM7 11h2v2H7v-2zm4 0h2v2h-2v-2zm4 0h2v6h-2v-6zM7 15h2v2H7v-2zm4 0h2v2h-2v-2z"/>',
			'shield'   => '<path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm0 10.99h7c-.53 4.12-3.28 7.79-7 8.94V12H5V6.3l7-3.11v8.8z"/>',
			'work'     => '<path d="M20 6h-4V4c0-1.11-.89-2-2-2h-4c-1.11 0-2 .89-2 2v2H4c-1.11 0-1.99.89-1.99 2L2 19c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V8c0-1.11-.89-2-2-2zm-6 0h-4V4h4v2z"/>',
			'shop'     => '<path d="M21.9 8.89l-1.05-4.37c-.22-.9-1-1.52-1.91-1.52H5.05c-.9 0-1.69.63-1.9 1.52L2.1 8.89c-.24 1.02-.02 2.06.62 2.88.08.11.19.19.28.29V19c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2v-6.94c.09-.09.2-.18.28-.28.64-.82.87-1.87.62-2.89zM13 5h1.96l.54 4.52c.07.62-.48 1.14-1.1 1.14-.56 0-1.04-.42-1.1-.97L13 5zm-4.5 0H10l-.3 4.69c-.07.55-.55.97-1.1.97-.62 0-1.17-.52-1.1-1.14L8.5 5zM4.22 9.47l.95-3.97h1.61l-.54 4.52c-.07.64.43 1.22 1.07 1.22.04 0 .08 0 .12-.01-1.02-.19-1.81-1.05-1.91-2.1L4.22 9.47zM20 19H4v-6.03c.08.01.16.03.24.03.87 0 1.66-.43 2.14-1.1.5.67 1.3 1.1 2.19 1.1.89 0 1.69-.43 2.19-1.1.5.67 1.3 1.1 2.19 1.1.89 0 1.69-.43 2.19-1.1.48.67 1.27 1.1 2.14 1.1.08 0 .16-.02.24-.03V19zm-.47-9.53c-.1 1.05-.89 1.91-1.91 2.1.04.01.08.01.12.01.64 0 1.14-.58 1.07-1.22L17.22 5.5h1.61l.7 3.97z"/>',
			'savings'  => '<path d="M19.83 7.5l-2.27-2.27c.07-.42.18-.81.32-1.15.48-1.19.19-2.61-.81-3.61-1.03-1.03-2.38-1.31-3.56-.8-.29.12-.57.27-.82.46-.24-.18-.51-.33-.8-.46-1.17-.51-2.53-.22-3.56.81-1.01 1-1.3 2.42-.81 3.61.14.34.25.73.32 1.15L4.17 7.5C3.47 8.2 3 9.16 3 10.28V17c0 1.1.9 2 2 2h1v1c0 .55.45 1 1 1s1-.45 1-1v-1h8v1c0 .55.45 1 1 1s1-.45 1-1v-1h1c1.1 0 2-.9 2-2v-6.72c0-1.12-.47-2.08-1.17-2.78zM12 4c.74 0 1.17.64 1.33 1.18.12.41.17.86.17 1.32H10.5c0-.46.05-.91.17-1.32C10.83 4.64 11.26 4 12 4zm7 13H5v-6.72c0-.48.2-.92.52-1.28L7.77 6.75h8.46l2.25 2.25c.32.36.52.8.52 1.28V17z"/><circle cx="8.5" cy="12.5" r="1.5"/><path d="M14.5 11h-2v2h2v2h2v-2h2v-2h-2v-2h-2v2z"/>',
			'diamond'  => '<path d="M19 3H5L2 9l10 12L22 9l-3-6zM9.62 8l1.5-3h1.76l1.5 3H9.62zM11 10v6.68L5.44 10H11zm2 0h5.56L13 16.68V10zM5.26 8l1.5-3h1.7L6.96 8H5.26zm9.28-3h1.7l1.5 3h-1.7l-1.5-3z"/>',
			'wallet'   => '<path d="M21 7.28V5c0-1.1-.9-2-2-2H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2v-2.28c.59-.35 1-.98 1-1.72V9c0-.74-.41-1.38-1-1.72zM20 9v6h-7V9h7zM5 19V5h14v2h-6c-1.1 0-2 .9-2 2v6c0 1.1.9 2 2 2h6v2H5z"/><circle cx="16" cy="12" r="1.5"/>',
			'trend'    => '<path d="M16 6l2.29 2.29-4.88 4.88-4-4L2 16.59 3.41 18l6-6 4 4 6.3-6.29L22 12V6z"/>',
			'update'   => '<path d="M21 10.12h-6.78l2.74-2.82c-2.73-2.7-7.15-2.8-9.88-.1-2.73 2.71-2.73 7.08 0 9.79s7.15 2.71 9.88 0C18.32 15.65 19 14.08 19 12.1h2c0 1.98-.88 4.55-2.64 6.29-3.51 3.48-9.21 3.48-12.72 0-3.5-3.47-3.53-9.11-.02-12.58s9.14-3.47 12.65 0L21 3v7.12zM12.5 8v4.25l3.5 2.08-.72 1.21L11 13V8h1.5z"/>',
			'layers'   => '<path d="M11.99 18.54l-7.37-5.73L3 14.07l9 7 9-7-1.63-1.27-7.38 5.74zM12 16l7.36-5.73L21 9l-9-7-9 7 1.63 1.27L12 16z"/>',
			'arrow'    => '<path d="M12 4l-1.41 1.41L16.17 11H4v2h12.17l-5.58 5.59L12 20l8-8z"/>',
			'percent'  => '<path d="M7.5 4C5.57 4 4 5.57 4 7.5S5.57 11 7.5 11 11 9.43 11 7.5 9.43 4 7.5 4zM19 4l-9.5 16H7L16.5 4H19zM16.5 13c-1.93 0-3.5 1.57-3.5 3.5s1.57 3.5 3.5 3.5 3.5-1.57 3.5-3.5-1.57-3.5-3.5-3.5z"/>',
			'lock'     => '<path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/>',
			'verified' => '<path d="M23 12l-2.44-2.79.34-3.69-3.61-.82-1.89-3.2L12 2.96 8.6 1.5 6.71 4.69 3.1 5.5l.34 3.7L1 12l2.44 2.79-.34 3.7 3.61.82L8.6 22.5l3.4-1.47 3.4 1.46 1.89-3.19 3.61-.82-.34-3.69L23 12zm-12.91 4.72l-3.8-3.81 1.48-1.48 2.32 2.33 5.85-5.87 1.48 1.48-7.33 7.35z"/>',
			'mail'     => '<path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>',
		);
		$path = isset( $icons[ $name ] ) ? $icons[ $name ] : $icons['help'];
		return '<svg viewBox="0 0 24 24" aria-hidden="true">' . $path . '</svg>';
	}

	public static function kicker( $text ) {
		if ( '' === (string) $text ) {
			return '';
		}
		return '<p class="rzs-kicker"><i></i>' . esc_html( $text ) . '</p>';
	}

	public static function guide( $args ) {
		$cards = isset( $args['cards'] ) && is_array( $args['cards'] ) ? $args['cards'] : array();
		ob_start();
		?>
		<section class="rzs rzs-guide">
			<div class="rzs-inner">
				<div class="rzs-head">
					<?php echo self::kicker( $args['eyebrow'] ?? '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php if ( ! empty( $args['title'] ) ) : ?>
						<h2 class="rzs-title"><?php echo esc_html( $args['title'] ); ?></h2>
					<?php endif; ?>
					<?php if ( ! empty( $args['lead'] ) ) : ?>
						<p class="rzs-lead"><?php echo esc_html( $args['lead'] ); ?></p>
					<?php endif; ?>
				</div>
				<div class="rzs-cards-3">
					<?php foreach ( $cards as $card ) : ?>
						<article class="rzs-card">
							<div>
								<div class="rzs-icon"><?php echo self::icon( $card['icon'] ?? 'help' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
								<h3><?php echo esc_html( $card['title'] ?? '' ); ?></h3>
								<p><?php echo esc_html( $card['text'] ?? '' ); ?></p>
							</div>
							<a class="rzs-link" href="<?php echo esc_url( self::href( $card['link'] ?? '#', '#' ) ); ?>">
								<?php echo esc_html( $card['link_text'] ?? '' ); ?>
								<?php echo self::icon( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							</a>
						</article>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
		<?php
		return ob_get_clean();
	}

	public static function categories( $args ) {
		$cards = isset( $args['cards'] ) && is_array( $args['cards'] ) ? $args['cards'] : array();
		ob_start();
		?>
		<section class="rzs rzs-cats" id="kategori-zakat">
			<div class="rzs-inner">
				<div class="rzs-head rzs-head--split">
					<div>
						<?php echo self::kicker( $args['eyebrow'] ?? '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php if ( ! empty( $args['title'] ) ) : ?>
							<h2 class="rzs-title"><?php echo esc_html( $args['title'] ); ?></h2>
						<?php endif; ?>
						<?php if ( ! empty( $args['lead'] ) ) : ?>
							<p class="rzs-lead"><?php echo esc_html( $args['lead'] ); ?></p>
						<?php endif; ?>
					</div>
					<?php if ( ! empty( $args['badge'] ) ) : ?>
						<span class="rzs-chip"><?php echo self::icon( 'percent' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <?php echo esc_html( $args['badge'] ); ?></span>
					<?php endif; ?>
				</div>
				<div class="rzs-cards-4">
					<?php foreach ( $cards as $card ) : ?>
						<article class="rzs-card rzs-cat">
							<div>
								<div class="rzs-icon"><?php echo self::icon( $card['icon'] ?? 'work' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
								<h3><?php echo esc_html( $card['title'] ?? '' ); ?></h3>
								<p><?php echo esc_html( $card['text'] ?? '' ); ?></p>
							</div>
							<div class="rzs-cat__foot">
								<a href="<?php echo esc_url( self::href( $card['guide_link'] ?? '#langkah-bayar', '#langkah-bayar' ) ); ?>"><?php echo esc_html( $card['guide_text'] ?? 'Lihat Panduan' ); ?></a>
								<a class="rzs-btn rzs-btn--sm" href="<?php echo esc_url( self::href( $card['pay_link'] ?? '#bayar', '#bayar' ) ); ?>" data-rz-zakat="<?php echo esc_attr( $card['zakat_key'] ?? '' ); ?>">
									<?php echo esc_html( $card['pay_text'] ?? 'Bayar Sekarang' ); ?>
								</a>
							</div>
						</article>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
		<?php
		return ob_get_clean();
	}

	public static function steps( $args ) {
		$cards = isset( $args['cards'] ) && is_array( $args['cards'] ) ? $args['cards'] : array();
		ob_start();
		?>
		<section class="rzs rzs-steps" id="langkah-bayar">
			<div class="rzs-inner rzs-steps-wrap">
				<div class="rzs-head">
					<?php echo self::kicker( $args['eyebrow'] ?? '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php if ( ! empty( $args['title'] ) ) : ?>
						<h2 class="rzs-title"><?php echo esc_html( $args['title'] ); ?></h2>
					<?php endif; ?>
					<?php if ( ! empty( $args['lead'] ) ) : ?>
						<p class="rzs-lead"><?php echo esc_html( $args['lead'] ); ?></p>
					<?php endif; ?>
				</div>
				<div class="rzs-cards-3">
					<?php
					$i = 0;
					foreach ( $cards as $card ) :
						++$i;
						?>
						<article class="rzs-card">
							<div class="rzs-step-num<?php echo 2 === $i ? ' is-accent' : ''; ?>"><?php echo esc_html( $card['number'] ?? (string) $i ); ?></div>
							<h3><?php echo esc_html( $card['title'] ?? '' ); ?></h3>
							<p><?php echo esc_html( $card['text'] ?? '' ); ?></p>
						</article>
					<?php endforeach; ?>
				</div>
				<?php if ( ! empty( $args['btn_text'] ) ) : ?>
					<a class="rzs-btn" href="<?php echo esc_url( self::href( $args['btn_url'] ?? '#bayar', '#bayar' ) ); ?>">
						<?php echo esc_html( $args['btn_text'] ); ?>
						<?php echo self::icon( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</a>
				<?php endif; ?>
			</div>
		</section>
		<?php
		return ob_get_clean();
	}

	public static function official( $args ) {
		ob_start();
		?>
		<section class="rzs rzs-official" id="saluran-rasmi">
			<div class="rzs-inner rzs-official-grid">
				<div>
					<?php if ( ! empty( $args['badge'] ) ) : ?>
						<div class="rzs-badge"><?php echo self::icon( 'shield' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <?php echo esc_html( $args['badge'] ); ?></div>
					<?php endif; ?>
					<?php if ( ! empty( $args['title'] ) ) : ?>
						<h2 class="rzs-title"><?php echo esc_html( $args['title'] ); ?></h2>
					<?php endif; ?>
					<?php if ( ! empty( $args['lead'] ) ) : ?>
						<p class="rzs-lead"><?php echo esc_html( $args['lead'] ); ?></p>
					<?php endif; ?>
					<?php if ( ! empty( $args['lead_2'] ) ) : ?>
						<p class="rzs-lead" style="margin-top:12px"><?php echo esc_html( $args['lead_2'] ); ?></p>
					<?php endif; ?>
					<p style="margin-top:20px">
						<?php if ( ! empty( $args['link_1_text'] ) ) : ?>
							<a class="rzs-link" href="<?php echo esc_url( self::href( $args['link_1_url'] ?? '#faq-section', '#faq-section' ) ); ?>"><?php echo esc_html( $args['link_1_text'] ); ?> <?php echo self::icon( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
						<?php endif; ?>
						<?php if ( ! empty( $args['link_2_text'] ) ) : ?>
							<a class="rzs-link" style="margin-left:24px" href="<?php echo esc_url( self::href( $args['link_2_url'] ?? '#faq-section', '#faq-section' ) ); ?>"><?php echo esc_html( $args['link_2_text'] ); ?> <?php echo self::icon( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
						<?php endif; ?>
					</p>
				</div>
				<div class="rzs-cred">
					<div class="rzs-cred__top">
						<div style="display:flex;gap:12px;align-items:center">
							<div class="rzs-cred__icon"><?php echo self::icon( 'verified' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
							<div>
								<h3 style="margin:0;font-size:18px"><?php echo esc_html( $args['card_title'] ?? '' ); ?></h3>
								<p style="margin:2px 0 0;font-size:12px;color:#475569"><?php echo esc_html( $args['card_sub'] ?? '' ); ?></p>
							</div>
						</div>
						<span class="rzs-cred__code"><?php echo esc_html( $args['card_code'] ?? '' ); ?></span>
					</div>
					<dl>
						<div class="rzs-row"><dt><?php echo esc_html( $args['row1_label'] ?? '' ); ?></dt><dd><?php echo esc_html( $args['row1_value'] ?? '' ); ?></dd></div>
						<div class="rzs-row"><dt><?php echo esc_html( $args['row2_label'] ?? '' ); ?></dt><dd style="color:#0b5eda"><?php echo esc_html( $args['row2_value'] ?? '' ); ?></dd></div>
						<div class="rzs-row"><dt><?php echo esc_html( $args['row3_label'] ?? '' ); ?></dt><dd><?php echo esc_html( $args['row3_value'] ?? '' ); ?></dd></div>
						<div class="rzs-row"><dt><?php echo esc_html( $args['row4_label'] ?? '' ); ?></dt><dd style="color:#0b5eda"><?php echo esc_html( $args['row4_value'] ?? '' ); ?></dd></div>
						<div class="rzs-row" style="border:0;display:block">
							<dt><?php echo esc_html( $args['tax_label'] ?? '' ); ?></dt>
							<dd class="rzs-tax"><?php echo esc_html( $args['tax_value'] ?? '' ); ?></dd>
						</div>
					</dl>
				</div>
			</div>
		</section>
		<?php
		return ob_get_clean();
	}

	public static function impact( $args ) {
		$cards = isset( $args['cards'] ) && is_array( $args['cards'] ) ? $args['cards'] : array();
		ob_start();
		?>
		<section class="rzs rzs-impact">
			<div class="rzs-inner">
				<div class="rzs-head rzs-head--split">
					<div>
						<?php echo self::kicker( $args['eyebrow'] ?? '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php if ( ! empty( $args['title'] ) ) : ?>
							<h2 class="rzs-title"><?php echo esc_html( $args['title'] ); ?></h2>
						<?php endif; ?>
						<?php if ( ! empty( $args['lead'] ) ) : ?>
							<p class="rzs-lead"><?php echo esc_html( $args['lead'] ); ?></p>
						<?php endif; ?>
					</div>
					<?php if ( ! empty( $args['link_text'] ) ) : ?>
						<a class="rzs-link" href="<?php echo esc_url( self::href( $args['link_url'] ?? '#bayar', '#bayar' ) ); ?>">
							<?php echo esc_html( $args['link_text'] ); ?>
							<?php echo self::icon( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</a>
					<?php endif; ?>
				</div>
				<div class="rzs-cards-impact">
					<?php foreach ( $cards as $card ) : ?>
						<article class="rzs-impact-card">
							<div class="rzs-impact-card__media">
								<?php if ( ! empty( $card['image']['url'] ) ) : ?>
									<img src="<?php echo esc_url( $card['image']['url'] ); ?>" alt="<?php echo esc_attr( $card['title'] ?? '' ); ?>">
								<?php endif; ?>
								<?php if ( ! empty( $card['tag'] ) ) : ?>
									<span class="rzs-impact-card__tag"><?php echo esc_html( $card['tag'] ); ?></span>
								<?php endif; ?>
							</div>
							<div class="rzs-impact-card__body">
								<h3><?php echo esc_html( $card['title'] ?? '' ); ?></h3>
								<p><?php echo esc_html( $card['text'] ?? '' ); ?></p>
								<div class="rzs-impact-card__meta">
									<span><?php echo esc_html( $card['meta'] ?? '' ); ?></span>
									<?php if ( ! empty( $card['badge'] ) ) : ?>
										<span class="rzs-tag-dark"><?php echo esc_html( $card['badge'] ); ?></span>
									<?php endif; ?>
								</div>
							</div>
						</article>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
		<?php
		return ob_get_clean();
	}

	public static function faq( $args ) {
		$items = isset( $args['items'] ) && is_array( $args['items'] ) ? $args['items'] : array();
		ob_start();
		?>
		<section class="rzs rzs-faq" id="faq-section">
			<div class="rzs-inner rzs-faq-wrap">
				<div class="rzs-head">
					<?php echo self::kicker( $args['eyebrow'] ?? '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php if ( ! empty( $args['title'] ) ) : ?>
						<h2 class="rzs-title"><?php echo esc_html( $args['title'] ); ?></h2>
					<?php endif; ?>
					<?php if ( ! empty( $args['lead'] ) ) : ?>
						<p class="rzs-lead"><?php echo esc_html( $args['lead'] ); ?></p>
					<?php endif; ?>
				</div>
				<?php foreach ( $items as $i => $item ) : ?>
					<details class="rzs-acc"<?php echo 0 === (int) $i ? ' open' : ''; ?>>
						<summary><?php echo esc_html( $item['q'] ?? '' ); ?></summary>
						<p><?php echo esc_html( $item['a'] ?? '' ); ?></p>
					</details>
				<?php endforeach; ?>
			</div>
		</section>
		<?php
		return ob_get_clean();
	}

	public static function cta( $args ) {
		ob_start();
		?>
		<section class="rzs rzs-cta">
			<div class="rzs-inner">
				<div class="rzs-cta-box">
					<div class="rzs-cta-icon"><?php echo self::icon( 'verified' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
					<?php if ( ! empty( $args['title'] ) ) : ?>
						<h2 class="rzs-title"><?php echo esc_html( $args['title'] ); ?></h2>
					<?php endif; ?>
					<?php if ( ! empty( $args['lead'] ) ) : ?>
						<p class="rzs-lead"><?php echo esc_html( $args['lead'] ); ?></p>
					<?php endif; ?>
					<?php if ( ! empty( $args['btn_text'] ) ) : ?>
						<a class="rzs-btn" href="<?php echo esc_url( self::href( $args['btn_url'] ?? '#bayar', '#bayar' ) ); ?>">
							<?php echo self::icon( 'lock' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<?php echo esc_html( $args['btn_text'] ); ?>
						</a>
					<?php endif; ?>
					<?php if ( ! empty( $args['note'] ) ) : ?>
						<p class="rzs-cta-note"><?php echo esc_html( $args['note'] ); ?></p>
					<?php endif; ?>
				</div>
			</div>
		</section>
		<?php
		return ob_get_clean();
	}
}
