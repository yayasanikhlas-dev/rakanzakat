<?php
/**
 * Nested Elementor hero carousel: each slide is a container you can fill.
 *
 * @package RakanZakat
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( '\Elementor\Modules\NestedElements\Base\Widget_Nested_Base' ) ) {
	return;
}

class RakanZakat_Elementor_Hero_Slides_Widget extends \Elementor\Modules\NestedElements\Base\Widget_Nested_Base {

	public function get_name() {
		return 'rakanzakat_hero_slides';
	}

	public function get_title() {
		return __( 'RZ: Hero Carousel', 'rakanzakat' );
	}

	public function get_icon() {
		return 'eicon-slides';
	}

	public function get_categories() {
		return array( 'rakanzakat' );
	}

	public function get_keywords() {
		return array( 'zakat', 'hero', 'slide', 'slider', 'carousel', 'container' );
	}

	public function get_style_depends() {
		return array( 'rakanzakat-jakarta', 'rakanzakat-stitch' );
	}

	public function get_script_depends() {
		return array( 'rakanzakat-stitch' );
	}

	public function show_in_panel() {
		if ( ! class_exists( '\Elementor\Plugin' ) || empty( \Elementor\Plugin::$instance->experiments ) ) {
			return true;
		}
		return (bool) \Elementor\Plugin::$instance->experiments->is_feature_active( 'container' );
	}

	protected function get_default_children_elements() {
		return array(
			$this->slide_container( 1 ),
			$this->slide_container( 2 ),
			$this->slide_container( 3 ),
		);
	}

	protected function slide_container( $index ) {
		return array(
			'elType'   => 'container',
			'settings' => array(
				'_title'               => sprintf(
					/* translators: %d: slide number */
					__( 'Slide #%d', 'rakanzakat' ),
					$index
				),
				'content_width'        => 'full',
				'flex_direction'       => 'column',
				'flex_justify_content' => 'center',
				'flex_align_items'     => 'center',
			),
		);
	}

	protected function get_default_repeater_title_setting_key() {
		return 'slide_title';
	}

	protected function get_default_children_title() {
		return esc_html__( 'Slide #%d', 'rakanzakat' );
	}

	protected function get_default_children_placeholder_selector() {
		return '.rzs-hero-slides__track';
	}

	protected function get_initial_config() {
		$config = parent::get_initial_config();
		$config['support_improved_repeaters'] = true;
		$config['support_nesting']            = true;
		$config['target_container']           = array( '.rzs-hero-slides__track' );
		$config['node']                       = 'div';
		$config['is_interlaced']              = false;
		return $config;
	}

	protected function register_controls() {
		$this->start_controls_section(
			'content_section',
			array(
				'label' => __( 'Carousel', 'rakanzakat' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'howto',
			array(
				'type'            => \Elementor\Controls_Manager::RAW_HTML,
				'raw'             => '<p style="line-height:1.45;margin:0">' . esc_html__( 'Setiap slide ialah container Elementor. Klik slide pada kanvas, kemudian drop Heading, Text, Image atau Container ke dalamnya — sama macam Nested Carousel.', 'rakanzakat' ) . '</p>',
				'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
			)
		);

		$repeater = new \Elementor\Repeater();
		$repeater->add_control(
			'slide_title',
			array(
				'label'       => __( 'Nama slide', 'rakanzakat' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'default'     => __( 'Slide', 'rakanzakat' ),
				'label_block' => true,
			)
		);

		$nested_type = class_exists( '\Elementor\Modules\NestedElements\Controls\Control_Nested_Repeater' )
			? \Elementor\Modules\NestedElements\Controls\Control_Nested_Repeater::CONTROL_TYPE
			: \Elementor\Controls_Manager::REPEATER;

		$this->add_control(
			'items',
			array(
				'label'       => __( 'Slide', 'rakanzakat' ),
				'type'        => $nested_type,
				'fields'      => $repeater->get_controls(),
				'default'     => array(
					array( 'slide_title' => 'Slide #1' ),
					array( 'slide_title' => 'Slide #2' ),
					array( 'slide_title' => 'Slide #3' ),
				),
				'title_field' => '{{{ slide_title }}}',
				'button_text' => __( 'Tambah slide', 'rakanzakat' ),
			)
		);

		$this->add_control(
			'autoplay',
			array(
				'label'        => __( 'Autoplay', 'rakanzakat' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);
		$this->add_control(
			'delay',
			array(
				'label'     => __( 'Tempoh slide (saat)', 'rakanzakat' ),
				'type'      => \Elementor\Controls_Manager::NUMBER,
				'default'   => 6,
				'min'       => 2,
				'max'       => 30,
				'condition' => array( 'autoplay' => 'yes' ),
			)
		);
		$this->add_control(
			'show_arrows',
			array(
				'label'        => __( 'Anak panah', 'rakanzakat' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);
		$this->add_control(
			'show_dots',
			array(
				'label'        => __( 'Titik', 'rakanzakat' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);
		$this->add_control(
			'loop',
			array(
				'label'        => __( 'Ulang dari mula', 'rakanzakat' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);
		$this->add_responsive_control(
			'min_height',
			array(
				'label'      => __( 'Tinggi minimum', 'rakanzakat' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'vh' ),
				'range'      => array(
					'px' => array(
						'min' => 240,
						'max' => 1200,
					),
					'vh' => array(
						'min' => 30,
						'max' => 100,
					),
				),
				'default'    => array(
					'unit' => 'vh',
					'size' => 90,
				),
				'selectors'  => array(
					'{{WRAPPER}} .rzs-hero-slides' => '--rzs-hero-h: {{SIZE}}{{UNIT}};',
				),
			)
		);
		$this->end_controls_section();
	}

	protected function render() {
		$s      = $this->get_settings_for_display();
		$items  = isset( $s['items'] ) && is_array( $s['items'] ) ? $s['items'] : array();
		$count  = count( $items );
		if ( $count < 1 ) {
			$count = count( $this->get_children() );
		}
		$auto   = 'yes' === ( $s['autoplay'] ?? 'yes' ) ? '1' : '0';
		$delay  = max( 2, min( 30, (int) ( $s['delay'] ?? 6 ) ) );
		$loop   = 'yes' === ( $s['loop'] ?? 'yes' ) ? '1' : '0';
		$arrows = 'yes' === ( $s['show_arrows'] ?? 'yes' );
		$dots   = 'yes' === ( $s['show_dots'] ?? 'yes' );
		?>
		<div class="rzs rzs-hero-slides js-rz-hero-slides" data-autoplay="<?php echo esc_attr( $auto ); ?>" data-delay="<?php echo esc_attr( $delay ); ?>" data-loop="<?php echo esc_attr( $loop ); ?>">
			<div class="rzs-hero-slides__viewport">
				<div class="rzs-hero-slides__track">
					<?php
					for ( $i = 0; $i < $count; $i++ ) {
						$this->print_child( $i );
					}
					?>
				</div>
			</div>
			<?php if ( $arrows ) : ?>
				<button type="button" class="rzs-hero-slides__arrow rzs-hero-slides__arrow--prev js-rz-hero-prev" aria-label="<?php esc_attr_e( 'Slide sebelum', 'rakanzakat' ); ?>"><?php echo RakanZakat_Stitch_Sections::icon( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
				<button type="button" class="rzs-hero-slides__arrow rzs-hero-slides__arrow--next js-rz-hero-next" aria-label="<?php esc_attr_e( 'Slide seterusnya', 'rakanzakat' ); ?>"><?php echo RakanZakat_Stitch_Sections::icon( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></button>
			<?php endif; ?>
			<?php if ( $dots ) : ?>
				<div class="rzs-hero-slides__dots js-rz-hero-dots" role="tablist"></div>
			<?php endif; ?>
		</div>
		<?php
	}

	public function print_child( $index, $item_settings = array() ) {
		$children = $this->get_children();
		$child    = isset( $children[ $index ] ) ? $children[ $index ] : null;
		if ( ! $child ) {
			return;
		}

		$mark = function ( $should_render, $container ) use ( $child ) {
			if ( $container->get_id() === $child->get_id() ) {
				$container->add_render_attribute( '_wrapper', 'class', 'rzs-hero-slides__slide' );
			}
			return $should_render;
		};

		add_filter( 'elementor/frontend/container/should_render', $mark, 10, 2 );
		$child->print_element();
		remove_filter( 'elementor/frontend/container/should_render', $mark );
	}

	protected function content_template() {
		?>
		<#
		var items = settings.items || [];
		#>
		<div class="rzs rzs-hero-slides js-rz-hero-slides" data-autoplay="{{ 'yes' === settings.autoplay ? '1' : '0' }}" data-delay="{{ settings.delay || 6 }}" data-loop="{{ 'yes' === settings.loop ? '1' : '0' }}">
			<div class="rzs-hero-slides__viewport">
				<div class="rzs-hero-slides__track"></div>
			</div>
			<# if ( 'yes' === settings.show_arrows ) { #>
				<button type="button" class="rzs-hero-slides__arrow rzs-hero-slides__arrow--prev js-rz-hero-prev" aria-label="<?php echo esc_attr__( 'Slide sebelum', 'rakanzakat' ); ?>"></button>
				<button type="button" class="rzs-hero-slides__arrow rzs-hero-slides__arrow--next js-rz-hero-next" aria-label="<?php echo esc_attr__( 'Slide seterusnya', 'rakanzakat' ); ?>"></button>
			<# } #>
			<# if ( 'yes' === settings.show_dots ) { #>
				<div class="rzs-hero-slides__dots js-rz-hero-dots"></div>
			<# } #>
		</div>
		<?php
	}
}
