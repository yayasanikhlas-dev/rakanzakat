<?php
/**
 * Elementor widgets for Stitch landing screenshot sections.
 *
 * @package RakanZakat
 */

defined( 'ABSPATH' ) || exit;

abstract class RakanZakat_Elementor_Stitch_Base extends \Elementor\Widget_Base {

	public function get_categories() {
		return array( 'rakanzakat', 'general' );
	}

	public function get_style_depends() {
		return array( 'rakanzakat-jakarta', 'rakanzakat-stitch' );
	}

	public function get_script_depends() {
		return array( 'rakanzakat-stitch' );
	}

	protected function heading_controls( $defaults ) {
		$this->start_controls_section(
			'content_section',
			array(
				'label' => __( 'Kandungan', 'rakanzakat' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);
		if ( array_key_exists( 'eyebrow', $defaults ) ) {
			$this->add_control(
				'eyebrow',
				array(
					'label'   => __( 'Teks kecil', 'rakanzakat' ),
					'type'    => \Elementor\Controls_Manager::TEXT,
					'default' => $defaults['eyebrow'],
				)
			);
		}
		if ( array_key_exists( 'title', $defaults ) ) {
			$this->add_control(
				'title',
				array(
					'label'   => __( 'Tajuk', 'rakanzakat' ),
					'type'    => \Elementor\Controls_Manager::TEXT,
					'default' => $defaults['title'],
				)
			);
		}
		if ( array_key_exists( 'lead', $defaults ) ) {
			$this->add_control(
				'lead',
				array(
					'label'   => __( 'Penerangan', 'rakanzakat' ),
					'type'    => \Elementor\Controls_Manager::TEXTAREA,
					'default' => $defaults['lead'],
				)
			);
		}
	}

	protected function register_style_controls() {
		$this->style_section();
		$this->style_heading();
		$name = $this->get_name();
		if ( 'rakanzakat_cta' !== $name ) {
			$this->style_cards();
		}
		if ( 'rakanzakat_faq_v2' === $name ) {
			$this->style_faq();
		}
		if ( 'rakanzakat_cta' === $name ) {
			$this->style_cta_box();
		}
		if ( 'rakanzakat_calc' === $name ) {
			$this->style_calc();
		}
		$this->style_buttons();
		$this->style_chip();
	}

	private static function no_global() {
		return array( 'active' => false );
	}

	private function style_section() {
		$this->start_controls_section(
			'style_section',
			array(
				'label' => __( 'Seksyen', 'rakanzakat' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);
		$this->add_group_control(
			\Elementor\Group_Control_Background::get_type(),
			array(
				'name'     => 'section_bg',
				'selector' => '{{WRAPPER}} .rzs',
			)
		);
		$this->add_responsive_control(
			'section_padding',
			array(
				'label'      => __( 'Padding dalam', 'rakanzakat' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .rzs-inner' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);
		$this->end_controls_section();
	}

	private function style_heading() {
		$this->start_controls_section(
			'style_heading',
			array(
				'label' => __( 'Tajuk', 'rakanzakat' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);
		$this->add_control(
			'kicker_color',
			array(
				'label'   => __( 'Warna teks kecil', 'rakanzakat' ),
				'type'    => \Elementor\Controls_Manager::COLOR,
				'global'  => self::no_global(),
				'selectors' => array(
					'{{WRAPPER}} .rzs-kicker' => 'color: {{VALUE}};',
				),
			)
		);
		$this->add_control(
			'kicker_dot',
			array(
				'label'   => __( 'Warna titik', 'rakanzakat' ),
				'type'    => \Elementor\Controls_Manager::COLOR,
				'global'  => self::no_global(),
				'selectors' => array(
					'{{WRAPPER}} .rzs-kicker i' => 'background: {{VALUE}};',
				),
			)
		);
		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'     => 'kicker_typo',
				'label'    => __( 'Tipografi teks kecil', 'rakanzakat' ),
				'selector' => '{{WRAPPER}} .rzs-kicker',
				'global'   => self::no_global(),
			)
		);
		$this->add_control(
			'title_color',
			array(
				'label'   => __( 'Warna tajuk', 'rakanzakat' ),
				'type'    => \Elementor\Controls_Manager::COLOR,
				'global'  => self::no_global(),
				'selectors' => array(
					'{{WRAPPER}} .rzs-title' => 'color: {{VALUE}};',
				),
			)
		);
		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'     => 'title_typo',
				'label'    => __( 'Tipografi tajuk', 'rakanzakat' ),
				'selector' => '{{WRAPPER}} .rzs-title',
				'global'   => self::no_global(),
			)
		);
		$this->add_control(
			'lead_color',
			array(
				'label'   => __( 'Warna penerangan', 'rakanzakat' ),
				'type'    => \Elementor\Controls_Manager::COLOR,
				'global'  => self::no_global(),
				'selectors' => array(
					'{{WRAPPER}} .rzs-lead' => 'color: {{VALUE}};',
				),
			)
		);
		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'     => 'lead_typo',
				'label'    => __( 'Tipografi penerangan', 'rakanzakat' ),
				'selector' => '{{WRAPPER}} .rzs-lead',
				'global'   => self::no_global(),
			)
		);
		$this->end_controls_section();
	}

	private function style_cards() {
		$card = '{{WRAPPER}} .rzs-card, {{WRAPPER}} .rzs-impact-card, {{WRAPPER}} .rzs-calc-card, {{WRAPPER}} .rzs-acc';
		$this->start_controls_section(
			'style_cards',
			array(
				'label' => __( 'Kad', 'rakanzakat' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);
		$this->add_control(
			'card_bg',
			array(
				'label'   => __( 'Latar kad', 'rakanzakat' ),
				'type'    => \Elementor\Controls_Manager::COLOR,
				'global'  => self::no_global(),
				'selectors' => array(
					$card => 'background-color: {{VALUE}};',
				),
			)
		);
		$this->add_control(
			'card_title_color',
			array(
				'label'   => __( 'Warna tajuk kad', 'rakanzakat' ),
				'type'    => \Elementor\Controls_Manager::COLOR,
				'global'  => self::no_global(),
				'selectors' => array(
					'{{WRAPPER}} .rzs-card h3, {{WRAPPER}} .rzs-impact-card h3, {{WRAPPER}} .rzs-cat h3' => 'color: {{VALUE}};',
				),
			)
		);
		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'     => 'card_title_typo',
				'label'    => __( 'Tipografi tajuk kad', 'rakanzakat' ),
				'selector' => '{{WRAPPER}} .rzs-card h3, {{WRAPPER}} .rzs-impact-card h3, {{WRAPPER}} .rzs-cat h3',
				'global'   => self::no_global(),
			)
		);
		$this->add_control(
			'card_text_color',
			array(
				'label'   => __( 'Warna teks kad', 'rakanzakat' ),
				'type'    => \Elementor\Controls_Manager::COLOR,
				'global'  => self::no_global(),
				'selectors' => array(
					'{{WRAPPER}} .rzs-card p, {{WRAPPER}} .rzs-impact-card p, {{WRAPPER}} .rzs-cat p, {{WRAPPER}} .rzs-calc__points li' => 'color: {{VALUE}};',
				),
			)
		);
		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'     => 'card_text_typo',
				'label'    => __( 'Tipografi teks kad', 'rakanzakat' ),
				'selector' => '{{WRAPPER}} .rzs-card p, {{WRAPPER}} .rzs-impact-card p, {{WRAPPER}} .rzs-cat p',
				'global'   => self::no_global(),
			)
		);
		$this->add_control(
			'icon_bg',
			array(
				'label'   => __( 'Latar ikon', 'rakanzakat' ),
				'type'    => \Elementor\Controls_Manager::COLOR,
				'global'  => self::no_global(),
				'selectors' => array(
					'{{WRAPPER}} .rzs-icon' => 'background-color: {{VALUE}};',
				),
			)
		);
		$this->add_control(
			'icon_color',
			array(
				'label'   => __( 'Warna ikon', 'rakanzakat' ),
				'type'    => \Elementor\Controls_Manager::COLOR,
				'global'  => self::no_global(),
				'selectors' => array(
					'{{WRAPPER}} .rzs-icon' => 'color: {{VALUE}};',
				),
			)
		);
		$this->add_control(
			'card_radius',
			array(
				'label'      => __( 'Radius kad', 'rakanzakat' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 40 ) ),
				'selectors'  => array(
					$card => 'border-radius: {{SIZE}}{{UNIT}};',
				),
			)
		);
		$this->add_responsive_control(
			'card_padding',
			array(
				'label'      => __( 'Padding kad', 'rakanzakat' ),
				'type'       => \Elementor\Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em' ),
				'selectors'  => array(
					$card => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);
		$this->end_controls_section();
	}

	private function style_buttons() {
		$this->start_controls_section(
			'style_buttons',
			array(
				'label' => __( 'Butang', 'rakanzakat' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);
		$this->start_controls_tabs( 'btn_tabs' );
		$this->start_controls_tab(
			'btn_normal',
			array( 'label' => __( 'Normal', 'rakanzakat' ) )
		);
		$this->add_control(
			'btn_bg',
			array(
				'label'   => __( 'Latar', 'rakanzakat' ),
				'type'    => \Elementor\Controls_Manager::COLOR,
				'global'  => self::no_global(),
				'selectors' => array(
					'{{WRAPPER}} .rzs-btn' => 'background-color: {{VALUE}};',
				),
			)
		);
		$this->add_control(
			'btn_color',
			array(
				'label'   => __( 'Teks', 'rakanzakat' ),
				'type'    => \Elementor\Controls_Manager::COLOR,
				'global'  => self::no_global(),
				'selectors' => array(
					'{{WRAPPER}} .rzs-btn' => 'color: {{VALUE}};',
				),
			)
		);
		$this->end_controls_tab();
		$this->start_controls_tab(
			'btn_hover',
			array( 'label' => __( 'Hover', 'rakanzakat' ) )
		);
		$this->add_control(
			'btn_bg_hover',
			array(
				'label'   => __( 'Latar', 'rakanzakat' ),
				'type'    => \Elementor\Controls_Manager::COLOR,
				'global'  => self::no_global(),
				'selectors' => array(
					'{{WRAPPER}} .rzs-btn:hover' => 'background-color: {{VALUE}};',
				),
			)
		);
		$this->add_control(
			'btn_color_hover',
			array(
				'label'   => __( 'Teks', 'rakanzakat' ),
				'type'    => \Elementor\Controls_Manager::COLOR,
				'global'  => self::no_global(),
				'selectors' => array(
					'{{WRAPPER}} .rzs-btn:hover' => 'color: {{VALUE}};',
				),
			)
		);
		$this->end_controls_tab();
		$this->end_controls_tabs();
		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'     => 'btn_typo',
				'selector' => '{{WRAPPER}} .rzs-btn',
				'global'   => self::no_global(),
			)
		);
		$this->add_control(
			'btn_radius',
			array(
				'label'      => __( 'Radius', 'rakanzakat' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 40 ) ),
				'selectors'  => array(
					'{{WRAPPER}} .rzs-btn' => 'border-radius: {{SIZE}}{{UNIT}};',
				),
			)
		);
		$this->add_control(
			'link_color',
			array(
				'label'   => __( 'Warna pautan', 'rakanzakat' ),
				'type'    => \Elementor\Controls_Manager::COLOR,
				'global'  => self::no_global(),
				'selectors' => array(
					'{{WRAPPER}} .rzs-link, {{WRAPPER}} .rzs-cat__foot a:not(.rzs-btn)' => 'color: {{VALUE}};',
				),
			)
		);
		$this->end_controls_section();
	}

	private function style_chip() {
		$this->start_controls_section(
			'style_chip',
			array(
				'label' => __( 'Lencana', 'rakanzakat' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);
		$this->add_control(
			'chip_bg',
			array(
				'label'   => __( 'Latar', 'rakanzakat' ),
				'type'    => \Elementor\Controls_Manager::COLOR,
				'global'  => self::no_global(),
				'selectors' => array(
					'{{WRAPPER}} .rzs-chip' => 'background-color: {{VALUE}};',
				),
			)
		);
		$this->add_control(
			'chip_color',
			array(
				'label'   => __( 'Teks', 'rakanzakat' ),
				'type'    => \Elementor\Controls_Manager::COLOR,
				'global'  => self::no_global(),
				'selectors' => array(
					'{{WRAPPER}} .rzs-chip' => 'color: {{VALUE}};',
				),
			)
		);
		$this->end_controls_section();
	}

	private function style_faq() {
		$this->start_controls_section(
			'style_faq',
			array(
				'label' => __( 'Soalan FAQ', 'rakanzakat' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);
		$this->add_control(
			'faq_q_color',
			array(
				'label'   => __( 'Warna soalan', 'rakanzakat' ),
				'type'    => \Elementor\Controls_Manager::COLOR,
				'global'  => self::no_global(),
				'selectors' => array(
					'{{WRAPPER}} .rzs-acc summary' => 'color: {{VALUE}};',
				),
			)
		);
		$this->add_group_control(
			\Elementor\Group_Control_Typography::get_type(),
			array(
				'name'     => 'faq_q_typo',
				'selector' => '{{WRAPPER}} .rzs-acc summary',
				'global'   => self::no_global(),
			)
		);
		$this->add_control(
			'faq_a_color',
			array(
				'label'   => __( 'Warna jawapan', 'rakanzakat' ),
				'type'    => \Elementor\Controls_Manager::COLOR,
				'global'  => self::no_global(),
				'selectors' => array(
					'{{WRAPPER}} .rzs-acc p' => 'color: {{VALUE}};',
				),
			)
		);
		$this->end_controls_section();
	}

	private function style_cta_box() {
		$this->start_controls_section(
			'style_cta_box',
			array(
				'label' => __( 'Kotak CTA', 'rakanzakat' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);
		$this->add_group_control(
			\Elementor\Group_Control_Background::get_type(),
			array(
				'name'     => 'cta_box_bg',
				'selector' => '{{WRAPPER}} .rzs-cta-box',
			)
		);
		$this->add_control(
			'cta_note_color',
			array(
				'label'   => __( 'Warna nota', 'rakanzakat' ),
				'type'    => \Elementor\Controls_Manager::COLOR,
				'global'  => self::no_global(),
				'selectors' => array(
					'{{WRAPPER}} .rzs-cta-note' => 'color: {{VALUE}};',
				),
			)
		);
		$this->end_controls_section();
	}

	private function style_calc() {
		$this->start_controls_section(
			'style_calc',
			array(
				'label' => __( 'Kad kalkulator', 'rakanzakat' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			)
		);
		$this->add_control(
			'calc_card_bg',
			array(
				'label'   => __( 'Latar kad', 'rakanzakat' ),
				'type'    => \Elementor\Controls_Manager::COLOR,
				'global'  => self::no_global(),
				'selectors' => array(
					'{{WRAPPER}} .rzs-calc-card' => 'background-color: {{VALUE}};',
				),
			)
		);
		$this->add_control(
			'calc_head_color',
			array(
				'label'   => __( 'Warna tajuk kad', 'rakanzakat' ),
				'type'    => \Elementor\Controls_Manager::COLOR,
				'global'  => self::no_global(),
				'selectors' => array(
					'{{WRAPPER}} .rzs-calc-card__title' => 'color: {{VALUE}};',
				),
			)
		);
		$this->add_control(
			'calc_result_bg',
			array(
				'label'   => __( 'Latar hasil', 'rakanzakat' ),
				'type'    => \Elementor\Controls_Manager::COLOR,
				'global'  => self::no_global(),
				'selectors' => array(
					'{{WRAPPER}} .rzs-calc-result' => 'background-color: {{VALUE}};',
				),
			)
		);
		$this->add_control(
			'calc_total_color',
			array(
				'label'   => __( 'Warna jumlah', 'rakanzakat' ),
				'type'    => \Elementor\Controls_Manager::COLOR,
				'global'  => self::no_global(),
				'selectors' => array(
					'{{WRAPPER}} .rzs-calc-result__sum' => 'color: {{VALUE}};',
				),
			)
		);
		$this->end_controls_section();
	}

	protected function icon_options() {
		return array(
			'help'     => 'Soalan',
			'calc'     => 'Kiraan',
			'shield'   => 'Pengesahan',
			'work'     => 'Pendapatan',
			'shop'     => 'Perniagaan',
			'savings'  => 'Simpanan',
			'diamond'  => 'Emas',
			'wallet'   => 'KWSP',
			'trend'    => 'Saham',
			'update'   => 'Qada',
			'layers'   => 'Lain-lain',
			'verified' => 'Disahkan',
			'lock'     => 'Kunci',
			'mail'     => 'Emel',
			'percent'  => 'Peratus',
		);
	}
}

class RakanZakat_Elementor_Guide_Widget extends RakanZakat_Elementor_Stitch_Base {

	public function get_name() {
		return 'rakanzakat_guide';
	}

	public function get_title() {
		return __( 'RZ: Panduan & Taksiran', 'rakanzakat' );
	}

	public function get_icon() {
		return 'eicon-help-o';
	}

	public function get_keywords() {
		return array( 'zakat', 'panduan', 'taksiran' );
	}

	protected function register_controls() {
		$this->heading_controls(
			array(
				'eyebrow' => 'Panduan & Taksiran',
				'title'   => 'Masih Belum Pasti Tentang Zakat Anda?',
				'lead'    => 'Ramai orang menangguhkan bayaran kerana belum pasti tentang kewajipan, jumlah atau saluran pembayaran. Mulakan dengan perkara yang anda mahu semak.',
			)
		);
		$repeater = new \Elementor\Repeater();
		$repeater->add_control(
			'icon',
			array(
				'label'   => __( 'Ikon', 'rakanzakat' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => $this->icon_options(),
				'default' => 'help',
			)
		);
		$repeater->add_control(
			'title',
			array(
				'label'   => __( 'Tajuk kad', 'rakanzakat' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => '',
			)
		);
		$repeater->add_control(
			'text',
			array(
				'label'   => __( 'Teks kad', 'rakanzakat' ),
				'type'    => \Elementor\Controls_Manager::TEXTAREA,
				'default' => '',
			)
		);
		$repeater->add_control(
			'link_text',
			array(
				'label'   => __( 'Teks pautan', 'rakanzakat' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => '',
			)
		);
		$repeater->add_control(
			'link',
			array(
				'label'   => __( 'Pautan', 'rakanzakat' ),
				'type'    => \Elementor\Controls_Manager::URL,
				'default' => array( 'url' => '#kategori-zakat' ),
			)
		);
		$this->add_control(
			'cards',
			array(
				'label'       => __( 'Kad', 'rakanzakat' ),
				'type'        => \Elementor\Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ title }}}',
				'default'     => array(
					array(
						'icon'      => 'help',
						'title'     => 'Adakah saya wajib berzakat?',
						'text'      => 'Kenal pasti jenis harta atau pendapatan yang tertakluk kepada zakat.',
						'link_text' => 'Semak Jenis Zakat',
						'link'      => array( 'url' => '#kategori-zakat' ),
					),
					array(
						'icon'      => 'calc',
						'title'     => 'Berapa jumlah yang perlu dibayar?',
						'text'      => 'Gunakan panduan kiraan yang berkaitan sebelum meneruskan bayaran.',
						'link_text' => 'Semak Cara Kiraan',
						'link'      => array( 'url' => '#langkah-bayar' ),
					),
					array(
						'icon'      => 'shield',
						'title'     => 'Adakah pembayaran ini rasmi?',
						'text'      => 'Lihat maklumat saluran bayaran, status amil dan proses resit.',
						'link_text' => 'Lihat Saluran Rasmi',
						'link'      => array( 'url' => '#saluran-rasmi' ),
					),
				),
			)
		);
		$this->end_controls_section();
		$this->register_style_controls();
	}

	protected function render() {
		echo RakanZakat_Stitch_Sections::guide( $this->get_settings_for_display() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}

class RakanZakat_Elementor_Cats_Widget extends RakanZakat_Elementor_Stitch_Base {

	public function get_name() {
		return 'rakanzakat_cats';
	}

	public function get_title() {
		return __( 'RZ: Kategori Zakat', 'rakanzakat' );
	}

	public function get_icon() {
		return 'eicon-gallery-grid';
	}

	public function get_keywords() {
		return array( 'zakat', 'kategori', 'jenis' );
	}

	protected function register_controls() {
		$this->heading_controls(
			array(
				'eyebrow' => 'Kategori Zakat',
				'title'   => 'Pilih Jenis Zakat Yang Ingin Ditunaikan',
				'lead'    => 'Pilih kategori yang berkaitan untuk melihat panduan ringkas atau teruskan pembayaran.',
			)
		);
		$this->add_control(
			'badge',
			array(
				'label'   => __( 'Lencana nisab', 'rakanzakat' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => 'Kadar 2.5% mengikut nisab',
			)
		);
		$this->add_control(
			'card_style',
			array(
				'label'   => __( 'Gaya kad', 'rakanzakat' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'style1',
				'options' => array(
					'style1' => __( 'Style 1 — kad biasa', 'rakanzakat' ),
					'style2' => __( 'Style 2 — hover penerangan penuh', 'rakanzakat' ),
				),
			)
		);
		$repeater = new \Elementor\Repeater();
		$repeater->add_control(
			'icon',
			array(
				'label'   => __( 'Ikon', 'rakanzakat' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => $this->icon_options(),
				'default' => 'work',
			)
		);
		$repeater->add_control(
			'title',
			array(
				'label'   => __( 'Tajuk', 'rakanzakat' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => '',
			)
		);
		$repeater->add_control(
			'text',
			array(
				'label'   => __( 'Penerangan ringkas', 'rakanzakat' ),
				'type'    => \Elementor\Controls_Manager::TEXTAREA,
				'default' => '',
			)
		);
		$repeater->add_control(
			'full',
			array(
				'label'       => __( 'Penerangan penuh (Style 2)', 'rakanzakat' ),
				'type'        => \Elementor\Controls_Manager::TEXTAREA,
				'default'     => '',
				'description' => __( 'Dipaparkan bila kad di-hover. Style 1 tidak guna medan ini.', 'rakanzakat' ),
			)
		);
		$repeater->add_control(
			'zakat_key',
			array(
				'label'   => __( 'Kod jenis (borang)', 'rakanzakat' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => 'pendapatan',
			)
		);
		$repeater->add_control(
			'guide_text',
			array(
				'label'   => __( 'Teks butang kiri (Cara Kira / Panduan)', 'rakanzakat' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => 'Lihat Panduan',
			)
		);
		$repeater->add_control(
			'guide_link',
			array(
				'label'   => __( 'Pautan panduan', 'rakanzakat' ),
				'type'    => \Elementor\Controls_Manager::URL,
				'default' => array( 'url' => '#langkah-bayar' ),
			)
		);
		$repeater->add_control(
			'pay_text',
			array(
				'label'   => __( 'Teks butang bayar', 'rakanzakat' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => 'Bayar Sekarang',
			)
		);
		$repeater->add_control(
			'pay_link',
			array(
				'label'   => __( 'Pautan bayar', 'rakanzakat' ),
				'type'    => \Elementor\Controls_Manager::URL,
				'default' => array( 'url' => '#bayar' ),
			)
		);
		$this->add_control(
			'cards',
			array(
				'label'       => __( 'Jenis zakat', 'rakanzakat' ),
				'type'        => \Elementor\Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ title }}}',
				'default'     => array(
					array(
						'icon'      => 'work',
						'title'     => 'Zakat Pendapatan',
						'text'      => 'Zakat ke atas gaji, elaun, bonus dan sumber pendapatan peribadi tahunan yang melepasi nisab.',
						'full'      => 'Zakat pendapatan dikira 2.5% ke atas gaji, elaun, bonus dan pendapatan sampingan selepas tolakan had kifayah. Wajib jika pendapatan bersih tahunan melepasi nisab semasa Lembaga Zakat Selangor. Isi gaji bulanan dalam kalkulator, semak jumlah, kemudian tunaikan melalui saluran rasmi.',
						'zakat_key' => 'pendapatan',
					),
					array(
						'icon'      => 'shop',
						'title'     => 'Zakat Perniagaan',
						'text'      => 'Zakat aset perniagaan, perkongsian, enterprise mahupun syarikat Sdn Bhd mengikut tempoh haul.',
						'full'      => 'Zakat perniagaan dikenakan ke atas aset semasa (wang, stok, hutang belum terima) tolak liabiliti semasa, setelah genap haul setahun dan melepasi nisab. Kadarnya 2.5%. Sediakan penyata ringkas, kira nilai bersih, kemudian bayar melalui Rakan Zakat.',
						'zakat_key' => 'perniagaan',
					),
					array(
						'icon'      => 'savings',
						'title'     => 'Zakat Wang Simpanan',
						'text'      => 'Baki terendah akaun simpanan atau deposit tetap yang genap tempoh setahun melebihi nisab.',
						'full'      => 'Zakat simpanan dikira 2.5% ke atas baki terendah dalam tempoh haul (termasuk simpanan, deposit tetap dan seumpamanya) jika melepasi nisab. Kumpul baki terendah setiap akaun, banding dengan nisab, kemudian tunaikan jumlah yang wajib.',
						'zakat_key' => 'simpanan',
					),
					array(
						'icon'      => 'diamond',
						'title'     => 'Zakat Emas',
						'text'      => 'Kiraan zakat emas simpanan (melebihi 85g) serta emas perhiasan melebihi kadar \'uruf negeri.',
						'full'      => 'Emas simpanan dizakatkan 2.5% jika beratnya melebihi 85g dan genap haul. Emas perhiasan mengikut \'uruf negeri — hanya lebihan di atas \'uruf dikira. Timbang atau nilai semasa, tolak \'uruf jika berkenaan, kemudian bayar zakat ke atas nilai tersebut.',
						'zakat_key' => 'emas',
					),
					array(
						'icon'      => 'wallet',
						'title'     => 'Zakat KWSP',
						'text'      => 'Dikenakan ke atas pengeluaran wang KWSP pada hari pengeluaran diterima secara tunai.',
						'full'      => 'Zakat KWSP dikenakan pada hari pengeluaran diterima (bukan baki dalam akaun). Kadar 2.5% ke atas jumlah pengeluaran yang melepasi nisab. Simpan penyata pengeluaran, kira 2.5%, dan tunaikan segera melalui saluran rasmi Rakan Zakat.',
						'zakat_key' => 'kwsp',
					),
					array(
						'icon'      => 'trend',
						'title'     => 'Zakat Saham',
						'text'      => 'Pelaburan ekuiti dan unit amanah patuh Syariah yang dimiliki bagi tujuan dividen atau dagangan.',
						'full'      => 'Saham dan unit amanah patuh Syariah dizakatkan 2.5% ke atas nilai pasaran semasa (atau kos, mengikut niat pegangan) setelah haul dan nisab. Kumpul nilai portfolio pada tarikh haul, pastikan patuh Syariah, kemudian bayar zakat yang wajib.',
						'zakat_key' => 'saham',
					),
					array(
						'icon'      => 'update',
						'title'     => 'Qada Zakat',
						'text'      => 'Menyempurnakan bayaran zakat bagi tahun-tahun lalu yang terlepas atau belum sempat ditunaikan.',
						'full'      => 'Qada zakat menyempurnakan kewajipan tahun-tahun lepas yang tertinggal. Anggar pendapatan atau aset bagi setiap tahun, kira 2.5% mengikut nisab tahun berkenaan, kemudian bayar jumlah terkumpul. Niatkan sebagai qada, bukan zakat tahun semasa.',
						'zakat_key' => 'qada',
					),
					array(
						'icon'      => 'layers',
						'title'     => 'Lain-lain Zakat',
						'text'      => 'Termasuk zakat fitrah, harta rikaz, zakat ternakan dan pelbagai kategori khas lain mengikut syarak.',
						'full'      => 'Kategori ini merangkumi zakat fitrah, rikaz, ternakan dan sumbangan khas lain. Fitrah mengikut kadar kepala yang ditetapkan; kategori lain ikut dalil dan ketetapan LZS. Pilih jenis yang betul pada borang, masukkan amaun, dan tunaikan melalui saluran rasmi.',
						'zakat_key' => 'lain-lain',
					),
				),
			)
		);
		$this->end_controls_section();
		$this->register_style_controls();
	}

	protected function render() {
		echo RakanZakat_Stitch_Sections::categories( $this->get_settings_for_display() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}

class RakanZakat_Elementor_Tiga_Widget extends RakanZakat_Elementor_Stitch_Base {

	public function get_name() {
		return 'rakanzakat_steps_v2';
	}

	public function get_title() {
		return __( 'RZ: Tiga Langkah', 'rakanzakat' );
	}

	public function get_icon() {
		return 'eicon-number-field';
	}

	public function get_keywords() {
		return array( 'zakat', 'langkah', 'cara' );
	}

	protected function register_controls() {
		$this->heading_controls(
			array(
				'eyebrow' => 'Langkah Mudah',
				'title'   => 'Selesaikan Bayaran Zakat Dalam Tiga Langkah',
				'lead'    => 'Penyempurnaan kewajipan zakat dipermudahkan melalui antara muka yang selamat, patuh Syariah dan pantas.',
			)
		);
		$this->add_control(
			'btn_text',
			array(
				'label'   => __( 'Teks butang', 'rakanzakat' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => 'Mulakan Pembayaran',
			)
		);
		$this->add_control(
			'btn_url',
			array(
				'label'   => __( 'Pautan butang', 'rakanzakat' ),
				'type'    => \Elementor\Controls_Manager::URL,
				'default' => array( 'url' => '#bayar' ),
			)
		);
		$repeater = new \Elementor\Repeater();
		$repeater->add_control(
			'number',
			array(
				'label'   => __( 'Nombor', 'rakanzakat' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => '1',
			)
		);
		$repeater->add_control(
			'title',
			array(
				'label'   => __( 'Tajuk', 'rakanzakat' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => '',
			)
		);
		$repeater->add_control(
			'text',
			array(
				'label'   => __( 'Penerangan', 'rakanzakat' ),
				'type'    => \Elementor\Controls_Manager::TEXTAREA,
				'default' => '',
			)
		);
		$this->add_control(
			'cards',
			array(
				'label'       => __( 'Langkah', 'rakanzakat' ),
				'type'        => \Elementor\Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ title }}}',
				'default'     => array(
					array(
						'number' => '1',
						'title'  => '1. Pilih jenis zakat',
						'text'   => 'Pilih kategori zakat yang ingin ditunaikan.',
					),
					array(
						'number' => '2',
						'title'  => '2. Masukkan maklumat dan jumlah',
						'text'   => 'Lengkapkan maklumat yang diperlukan, kemudian semak jumlah bayaran.',
					),
					array(
						'number' => '3',
						'title'  => '3. Bayar dan simpan resit',
						'text'   => 'Teruskan melalui saluran pembayaran rasmi dan simpan resit untuk rujukan anda.',
					),
				),
			)
		);
		$this->end_controls_section();
		$this->register_style_controls();
	}

	protected function render() {
		echo RakanZakat_Stitch_Sections::steps( $this->get_settings_for_display() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}

class RakanZakat_Elementor_Official_Widget extends RakanZakat_Elementor_Stitch_Base {

	public function get_name() {
		return 'rakanzakat_official';
	}

	public function get_title() {
		return __( 'RZ: Saluran Rasmi', 'rakanzakat' );
	}

	public function get_icon() {
		return 'eicon-lock-user';
	}

	public function get_keywords() {
		return array( 'zakat', 'amil', 'saluran', 'rasmi' );
	}

	protected function register_controls() {
		$this->heading_controls(
			array(
				'title' => 'Saluran Pembayaran Yang Jelas',
				'lead'  => 'Pembayaran zakat melalui Rakan Zakat disalurkan menggunakan saluran Lembaga Zakat Selangor. Urusan ini dikendalikan oleh Maahad Tahfiz Al-Fateh sebagai Penolong Amil IPIS di bawah Lembaga Zakat Selangor, Kod PA 2928.',
			)
		);
		$this->add_control(
			'badge',
			array(
				'label'   => __( 'Lencana', 'rakanzakat' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => 'Ketelusan & Pengesahan Rasmi',
			)
		);
		$this->add_control(
			'lead_2',
			array(
				'label'   => __( 'Perenggan kedua', 'rakanzakat' ),
				'type'    => \Elementor\Controls_Manager::TEXTAREA,
				'default' => 'Selepas pembayaran berjaya, resit rasmi diproses melalui saluran yang ditetapkan untuk simpanan dan rujukan pembayar.',
			)
		);
		$this->add_control(
			'link_1_text',
			array(
				'label'   => __( 'Pautan 1', 'rakanzakat' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => 'Semak Maklumat Amil',
			)
		);
		$this->add_control(
			'link_1_url',
			array(
				'label'   => __( 'URL pautan 1', 'rakanzakat' ),
				'type'    => \Elementor\Controls_Manager::URL,
				'default' => array( 'url' => '#faq-section' ),
			)
		);
		$this->add_control(
			'link_2_text',
			array(
				'label'   => __( 'Pautan 2', 'rakanzakat' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => 'Ketahui Proses Resit',
			)
		);
		$this->add_control(
			'link_2_url',
			array(
				'label'   => __( 'URL pautan 2', 'rakanzakat' ),
				'type'    => \Elementor\Controls_Manager::URL,
				'default' => array( 'url' => '#faq-section' ),
			)
		);
		$this->add_control(
			'card_heading',
			array(
				'label'     => __( 'Kad amil', 'rakanzakat' ),
				'type'      => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);
		$this->add_control(
			'card_title',
			array(
				'label'   => __( 'Tajuk kad', 'rakanzakat' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => 'Penolong Amil IPIS',
			)
		);
		$this->add_control(
			'card_sub',
			array(
				'label'   => __( 'Sub tajuk kad', 'rakanzakat' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => 'Institusi Pendidikan Islam Selangor',
			)
		);
		$this->add_control(
			'card_code',
			array(
				'label'   => __( 'Kod pada lencana', 'rakanzakat' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => 'Kod PA 2928',
			)
		);
		$this->add_control(
			'row1_label',
			array(
				'label'   => __( 'Label 1', 'rakanzakat' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => 'Nama Entiti:',
			)
		);
		$this->add_control(
			'row1_value',
			array(
				'label'   => __( 'Nilai 1', 'rakanzakat' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => 'Maahad Tahfiz Al-Fateh',
			)
		);
		$this->add_control(
			'row2_label',
			array(
				'label'   => __( 'Label 2', 'rakanzakat' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => 'No. Pendaftaran Penolong Amil:',
			)
		);
		$this->add_control(
			'row2_value',
			array(
				'label'   => __( 'Nilai 2', 'rakanzakat' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => 'Kod PA 2928',
			)
		);
		$this->add_control(
			'row3_label',
			array(
				'label'   => __( 'Label 3', 'rakanzakat' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => 'Agensi Pengiktirafan:',
			)
		);
		$this->add_control(
			'row3_value',
			array(
				'label'   => __( 'Nilai 3', 'rakanzakat' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => 'Lembaga Zakat Selangor (MAIS)',
			)
		);
		$this->add_control(
			'row4_label',
			array(
				'label'   => __( 'Label 4', 'rakanzakat' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => 'Saluran Resit:',
			)
		);
		$this->add_control(
			'row4_value',
			array(
				'label'   => __( 'Nilai 4', 'rakanzakat' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => 'Dihantar terus melalui emel rasmi & SMS',
			)
		);
		$this->add_control(
			'tax_label',
			array(
				'label'   => __( 'Label cukai', 'rakanzakat' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => 'Semakan Pelepasan Cukai:',
			)
		);
		$this->add_control(
			'tax_value',
			array(
				'label'   => __( 'Teks cukai', 'rakanzakat' ),
				'type'    => \Elementor\Controls_Manager::TEXTAREA,
				'default' => 'Layak rebat cukai pendapatan LHDN (Seksyen 44(11A) Akta Cukai Pendapatan 1967). Resit rasmi boleh dimuat turun sebagai bukti tuntutan sah.',
			)
		);
		$this->end_controls_section();
		$this->register_style_controls();
	}

	protected function render() {
		echo RakanZakat_Stitch_Sections::official( $this->get_settings_for_display() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}

class RakanZakat_Elementor_Impact_Widget extends RakanZakat_Elementor_Stitch_Base {

	public function get_name() {
		return 'rakanzakat_impact';
	}

	public function get_title() {
		return __( 'RZ: Impak Komuniti', 'rakanzakat' );
	}

	public function get_icon() {
		return 'eicon-image-box';
	}

	public function get_keywords() {
		return array( 'zakat', 'asnaf', 'impak', 'komuniti' );
	}

	protected function register_controls() {
		$this->heading_controls(
			array(
				'eyebrow' => 'Impak Nyata Komuniti',
				'title'   => 'Zakat Membantu Mereka Yang Memerlukan',
				'lead'    => 'Zakat yang ditunaikan melalui saluran rasmi menyokong usaha membantu golongan asnaf berdasarkan ketetapan dan kaedah agihan yang berkuat kuasa.',
			)
		);
		$this->add_control(
			'link_text',
			array(
				'label'   => __( 'Pautan kanan', 'rakanzakat' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => 'Lihat Kisah Manfaat',
			)
		);
		$this->add_control(
			'link_url',
			array(
				'label'   => __( 'URL pautan kanan', 'rakanzakat' ),
				'type'    => \Elementor\Controls_Manager::URL,
				'default' => array( 'url' => '#bayar' ),
			)
		);
		$repeater = new \Elementor\Repeater();
		$repeater->add_control(
			'image',
			array(
				'label' => __( 'Gambar', 'rakanzakat' ),
				'type'  => \Elementor\Controls_Manager::MEDIA,
			)
		);
		$repeater->add_control(
			'tag',
			array(
				'label'   => __( 'Tag gambar', 'rakanzakat' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => '',
			)
		);
		$repeater->add_control(
			'title',
			array(
				'label'   => __( 'Tajuk', 'rakanzakat' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => '',
			)
		);
		$repeater->add_control(
			'text',
			array(
				'label'   => __( 'Penerangan', 'rakanzakat' ),
				'type'    => \Elementor\Controls_Manager::TEXTAREA,
				'default' => '',
			)
		);
		$repeater->add_control(
			'meta',
			array(
				'label'   => __( 'Teks kiri bawah', 'rakanzakat' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => '',
			)
		);
		$repeater->add_control(
			'badge',
			array(
				'label'   => __( 'Lencana kanan', 'rakanzakat' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => '',
			)
		);
		$this->add_control(
			'cards',
			array(
				'label'       => __( 'Kisah impak', 'rakanzakat' ),
				'type'        => \Elementor\Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ title }}}',
				'default'     => array(
					array(
						'image' => array(
							'url' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuBlZHCHdFe0yDQCltSV7hQIMX3tLXcbK6jAuHDQH7PneIyt7Npm3M8eJLVwcV_BMgEtX5DpDggx9fAEy8PvZOWY0C7F1ToxjpEyX83aActP4pHbPRCq2eQ6qEuDE4Vqxe1xWHzfI5EkTTne5FyjcLnAkpz5-1C4sfKH4H9LXwP2Sw5rePpRjM1IIiox0fAg2pnZUqAbyQpIn2CMwNzhagr-Q0ztcRJHHfaCcBPEDvsIENSFaExF3FaXEw',
						),
						'tag'   => 'Pendidikan Asnaf',
						'title' => 'Bantuan Pendidikan Asnaf',
						'text'  => 'Tajaan yuran persekolahan, peralatan pembelajaran serta biasiswa pendidikan tinggi anak-anak asnaf di seluruh Selangor.',
						'meta'  => 'Agihan Biasiswa & Buku',
						'badge' => 'LZS 2024',
					),
					array(
						'image' => array(
							'url' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuAIzgnu2K2ZafJ3ySIjnr50PKyHF79oUjytHI_kTNqwcxEEPUbALpSlaKSIQ6hMft0LEtcedFn_hGBYIxEiq73CcuBRB_uygW-LezGHOSm7z8Wxh0hZSCZrJDj8TCuh6_nF1CukT0WDAQg0l9mPdr12gwbTWfwxl2bd5hv9iIm1v9p4SczZM62YwBZhVpMfkZSSk83tIzHocLXO4iC5YlGaV5ZrBMWvwM3QqsYQcRvWmEsDms8S1iE9kg',
						),
						'tag'   => 'Kebajikan Asas',
						'title' => 'Agihan Keperluan Asas',
						'text'  => 'Bantuan bulanan wang tunai, kotak makanan keperluan harian dan penambahbaikan tempat tinggal fakir miskin.',
						'meta'  => 'Program Makanan Komuniti',
						'badge' => 'Selangor',
					),
					array(
						'image' => array(
							'url' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuD-lq_CDZBVakxBrdxXyUO4CLDposELl7g4gJ2VoRVED-E9UsIn-UBd8HOk3pMaCYD8tPpBxT87jD-U_BwYAz4QK6qcA01rImIG2-TYupSHxxe6wm1oC4iwFInKtNHVUWYGSOPD9maxZZ7DCO_sZevWVNVotYoG_8ekQe8HptV3QEFqhAhgM49PxH6X7dPgihKZ_fppx5QFeODRP6UexOLOTPtFDvqjYkbkoRD7krtjgsWEUmzq0dJOYg',
						),
						'tag'   => 'Ekonomi Madani',
						'title' => 'Pembangunan Usahawan Asnaf',
						'text'  => 'Pemberian modal perniagaan mikro, bengkel kemahiran teknikal dan bimbingan mentor agar asnaf mampu berdikari.',
						'meta'  => 'Modal Mikro & Bimbingan',
						'badge' => 'Berdikari',
					),
				),
			)
		);
		$this->end_controls_section();
		$this->register_style_controls();
	}

	protected function render() {
		echo RakanZakat_Stitch_Sections::impact( $this->get_settings_for_display() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}

class RakanZakat_Elementor_Faq2_Widget extends RakanZakat_Elementor_Stitch_Base {

	public function get_name() {
		return 'rakanzakat_faq_v2';
	}

	public function get_title() {
		return __( 'RZ: FAQ', 'rakanzakat' );
	}

	public function get_icon() {
		return 'eicon-accordion';
	}

	public function get_keywords() {
		return array( 'zakat', 'faq', 'soalan' );
	}

	protected function register_controls() {
		$this->heading_controls(
			array(
				'eyebrow' => 'Soalan & Jawapan',
				'title'   => 'Soalan Lazim (FAQ)',
				'lead'    => 'Jawapan telus kepada persoalan paling kerap ditanya mengenai pelaksanaan zakat dan pengesahan bayaran.',
			)
		);
		$repeater = new \Elementor\Repeater();
		$repeater->add_control(
			'q',
			array(
				'label'   => __( 'Soalan', 'rakanzakat' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => '',
			)
		);
		$repeater->add_control(
			'a',
			array(
				'label'   => __( 'Jawapan', 'rakanzakat' ),
				'type'    => \Elementor\Controls_Manager::TEXTAREA,
				'default' => '',
			)
		);
		$this->add_control(
			'items',
			array(
				'label'       => __( 'Soalan', 'rakanzakat' ),
				'type'        => \Elementor\Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ q }}}',
				'default'     => array(
					array(
						'q' => 'Adakah bayaran melalui Rakan Zakat rasmi?',
						'a' => 'Ya, rasmi. Rakan Zakat bekerjasama dengan Maahad Tahfiz Al-Fateh yang merupakan Penolong Amil IPIS berdaftar di bawah Lembaga Zakat Selangor (Kod PA 2928). Setiap bayaran disalurkan terus ke akaun rasmi Lembaga Zakat Selangor.',
					),
					array(
						'q' => 'Adakah saya akan menerima resit?',
						'a' => 'Ya. Selepas transaksi berjaya, anda akan menerima resit rasmi yang sah dikeluarkan bagi tujuan simpanan serta pelepasan cukai pendapatan LHDN.',
					),
					array(
						'q' => 'Apakah jenis zakat yang boleh dibayar?',
						'a' => 'Anda boleh membayar semua kategori utama termasuk Zakat Pendapatan, Wang Simpanan, Perniagaan, Emas & Perak, KWSP, Saham, dan Qada Zakat.',
					),
					array(
						'q' => 'Bagaimana jika saya tidak pasti jumlah zakat?',
						'a' => 'Anda boleh merujuk kalkulator dan panduan kiraan zakat ringkas kami mengikut ketetapan nisab terkini Lembaga Zakat Selangor sebelum mengisi jumlah bayaran.',
					),
					array(
						'q' => 'Bolehkah saya membayar bagi pihak orang lain atau syarikat?',
						'a' => 'Boleh. Anda hanya perlu memasukkan nama dan nombor kad pengenalan atau nombor pendaftaran syarikat pembayar semasa melengkapkan butiran transaksi.',
					),
					array(
						'q' => 'Siapa yang boleh saya hubungi jika transaksi bermasalah?',
						'a' => 'Pasukan khidmat bantuan kami sedia membantu melalui WhatsApp rasmi di +60 11-1234 5678 atau emel bantuan@rakanzakat.com pada setiap hari bekerja (9.00 pagi – 6.00 petang).',
					),
				),
			)
		);
		$this->end_controls_section();
		$this->register_style_controls();
	}

	protected function render() {
		echo RakanZakat_Stitch_Sections::faq( $this->get_settings_for_display() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}

class RakanZakat_Elementor_Cta_Widget extends RakanZakat_Elementor_Stitch_Base {

	public function get_name() {
		return 'rakanzakat_cta';
	}

	public function get_title() {
		return __( 'RZ: CTA Bayar', 'rakanzakat' );
	}

	public function get_icon() {
		return 'eicon-call-to-action';
	}

	public function get_keywords() {
		return array( 'zakat', 'cta', 'bayar' );
	}

	protected function register_controls() {
		$this->heading_controls(
			array(
				'title' => 'Sedia Untuk Menunaikan Zakat?',
				'lead'  => 'Pilih jenis zakat anda dan teruskan pembayaran melalui saluran rasmi.',
			)
		);
		$this->add_control(
			'btn_text',
			array(
				'label'   => __( 'Teks butang', 'rakanzakat' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => 'Bayar Zakat Sekarang',
			)
		);
		$this->add_control(
			'btn_url',
			array(
				'label'   => __( 'Pautan butang', 'rakanzakat' ),
				'type'    => \Elementor\Controls_Manager::URL,
				'default' => array( 'url' => '#bayar' ),
			)
		);
		$this->add_control(
			'note',
			array(
				'label'   => __( 'Nota bawah', 'rakanzakat' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => 'Penolong Amil IPIS Kod PA 2928 • Lembaga Zakat Selangor',
			)
		);
		$this->end_controls_section();
		$this->register_style_controls();
	}

	protected function render() {
		echo RakanZakat_Stitch_Sections::cta( $this->get_settings_for_display() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}

class RakanZakat_Elementor_Calc_Widget extends RakanZakat_Elementor_Stitch_Base {

	public function get_name() {
		return 'rakanzakat_calc';
	}

	public function get_title() {
		return __( 'RZ: Kalkulator Zakat', 'rakanzakat' );
	}

	public function get_icon() {
		return 'eicon-number-field';
	}

	public function get_keywords() {
		return array( 'zakat', 'kalkulator', 'kira', 'nisab' );
	}

	protected function register_controls() {
		$this->heading_controls(
			array(
				'eyebrow' => 'Kalkulator Zakat',
				'title'   => 'Kira Zakat Anda Dengan Tepat',
				'lead'    => 'Pilih jenis zakat, kira mengikut kadar dan nisab kategori itu, kemudian tunaikan terus ke pembayaran.',
			)
		);
		$repeater = new \Elementor\Repeater();
		$repeater->add_control(
			'text',
			array(
				'label'   => __( 'Perkara', 'rakanzakat' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => '',
			)
		);
		$this->add_control(
			'points',
			array(
				'label'       => __( 'Poin kiri', 'rakanzakat' ),
				'type'        => \Elementor\Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ text }}}',
				'default'     => array(
					array( 'text' => 'Setiap jenis zakat ada kadar, nisab dan formula sendiri.' ),
					array( 'text' => 'Hasil terpapar serta-merta — gaji, kifayah, atau nilai aset.' ),
					array( 'text' => 'Tunaikan terus melalui saluran rasmi dengan resit LZS.' ),
				),
			)
		);
		$this->add_control(
			'card_title',
			array(
				'label'   => __( 'Tajuk kad', 'rakanzakat' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => 'Kalkulator & Bayaran Pantas',
			)
		);
		$this->add_control(
			'card_badge',
			array(
				'label'   => __( 'Lencana kad', 'rakanzakat' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => 'LZS 2024',
			)
		);
		$this->add_control(
			'card_intro',
			array(
				'label'   => __( 'Pengenalan kad', 'rakanzakat' ),
				'type'    => \Elementor\Controls_Manager::TEXTAREA,
				'default' => 'Pilih jenis zakat. Kadar, nisab dan formula menyesuaikan mengikut kategori yang dipilih.',
			)
		);
		$types = new \Elementor\Repeater();
		$types->add_control(
			'zakat_key',
			array(
				'label'   => __( 'Kod jenis (borang)', 'rakanzakat' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => RakanZakat_Settings::zakat_types(),
				'default' => 'pendapatan',
			)
		);
		$types->add_control(
			'label',
			array(
				'label'   => __( 'Nama dalam senarai', 'rakanzakat' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => '',
			)
		);
		$types->add_control(
			'mode',
			array(
				'label'   => __( 'Cara kira', 'rakanzakat' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'amount',
				'options' => array(
					'income' => __( 'Pendapatan: gaji - kifayah x 12', 'rakanzakat' ),
					'amount' => __( 'Nilai RM x kadar %', 'rakanzakat' ),
					'head'   => __( 'Fitrah: bilangan x kadar RM', 'rakanzakat' ),
					'flat'   => __( 'Amaun terus (tanpa darab kadar)', 'rakanzakat' ),
				),
			)
		);
		$types->add_control(
			'rate',
			array(
				'label'   => __( 'Kadar (% atau RM/orang untuk fitrah)', 'rakanzakat' ),
				'type'    => \Elementor\Controls_Manager::NUMBER,
				'default' => 2.5,
				'min'     => 0,
				'step'    => 0.01,
				'condition' => array( 'mode!' => 'flat' ),
			)
		);
		$types->add_control(
			'nisab_year',
			array(
				'label'     => __( 'Nisab setahun (RM)', 'rakanzakat' ),
				'type'      => \Elementor\Controls_Manager::NUMBER,
				'default'   => 24198,
				'min'       => 0,
				'step'      => 0.01,
				'condition' => array( 'skip_nisab!' => 'yes' ),
			)
		);
		$types->add_control(
			'nisab_month',
			array(
				'label'     => __( 'Nisab sebulan (RM)', 'rakanzakat' ),
				'type'      => \Elementor\Controls_Manager::NUMBER,
				'default'   => 2016.5,
				'min'       => 0,
				'step'      => 0.01,
				'condition' => array( 'skip_nisab!' => 'yes' ),
			)
		);
		$types->add_control(
			'skip_nisab',
			array(
				'label'        => __( 'Abaikan nisab (sentiasa kira)', 'rakanzakat' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => '',
			)
		);
		$types->add_control(
			'field_a_label',
			array(
				'label'     => __( 'Label medan 1', 'rakanzakat' ),
				'type'      => \Elementor\Controls_Manager::TEXT,
				'default'   => 'Gaji Bulanan (RM)',
				'condition' => array( 'mode' => 'income' ),
			)
		);
		$types->add_control(
			'field_b_label',
			array(
				'label'     => __( 'Label medan 2', 'rakanzakat' ),
				'type'      => \Elementor\Controls_Manager::TEXT,
				'default'   => 'Tolakan Had Kifayah (RM)',
				'condition' => array( 'mode' => 'income' ),
			)
		);
		$types->add_control(
			'field_a_value',
			array(
				'label'     => __( 'Nilai contoh medan 1', 'rakanzakat' ),
				'type'      => \Elementor\Controls_Manager::NUMBER,
				'default'   => 5000,
				'condition' => array( 'mode' => 'income' ),
			)
		);
		$types->add_control(
			'field_b_value',
			array(
				'label'     => __( 'Nilai contoh medan 2', 'rakanzakat' ),
				'type'      => \Elementor\Controls_Manager::NUMBER,
				'default'   => 2500,
				'condition' => array( 'mode' => 'income' ),
			)
		);
		$types->add_control(
			'amount_label',
			array(
				'label'     => __( 'Label medan amaun', 'rakanzakat' ),
				'type'      => \Elementor\Controls_Manager::TEXT,
				'default'   => 'Nilai / Amaun (RM)',
				'condition' => array( 'mode' => array( 'amount', 'head', 'flat' ) ),
			)
		);
		$types->add_control(
			'amount_value',
			array(
				'label'     => __( 'Nilai contoh amaun', 'rakanzakat' ),
				'type'      => \Elementor\Controls_Manager::NUMBER,
				'default'   => 0,
				'condition' => array( 'mode' => array( 'amount', 'head', 'flat' ) ),
			)
		);
		$types->add_control(
			'period',
			array(
				'label'   => __( 'Label tempoh hasil', 'rakanzakat' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => '/ setahun',
			)
		);
		$types->add_control(
			'hint',
			array(
				'label'   => __( 'Nota kiraan', 'rakanzakat' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => '',
			)
		);
		$this->add_control(
			'types',
			array(
				'label'       => __( 'Jenis zakat', 'rakanzakat' ),
				'description' => __( 'Satu item untuk setiap jenis. Dalam item boleh ubah kadar, nisab bulan/tahun, cara kira dan label medan.', 'rakanzakat' ),
				'type'        => \Elementor\Controls_Manager::REPEATER,
				'fields'      => $types->get_controls(),
				'title_field' => '{{{ label }}} ({{{ zakat_key }}})',
				'default'     => array(
					array(
						'zakat_key'     => 'pendapatan',
						'label'         => 'Zakat Pendapatan (Gaji & Upah)',
						'mode'          => 'income',
						'rate'          => 2.5,
						'nisab_year'    => 24198,
						'nisab_month'   => 2016.5,
						'field_a_label' => 'Gaji Bulanan (RM)',
						'field_b_label' => 'Tolakan Had Kifayah (RM)',
						'field_a_value' => 5000,
						'field_b_value' => 2500,
						'period'        => '/ setahun',
						'hint'          => 'Kiraan: Bulanan x 12 bulan',
					),
					array(
						'zakat_key'    => 'fitrah',
						'label'        => 'Zakat Fitrah',
						'mode'         => 'head',
						'rate'         => 7,
						'skip_nisab'   => 'yes',
						'amount_label' => 'Bilangan individu',
						'amount_value' => 1,
						'period'       => '/ jumlah',
						'hint'         => 'Kiraan: bilangan x kadar RM/orang',
					),
					array(
						'zakat_key'    => 'perniagaan',
						'label'        => 'Zakat Perniagaan',
						'mode'         => 'amount',
						'rate'         => 2.5,
						'nisab_year'   => 24198,
						'amount_label' => 'Nilai bersih aset (RM)',
						'period'       => '/ setahun',
					),
					array(
						'zakat_key'    => 'simpanan',
						'label'        => 'Zakat Simpanan',
						'mode'         => 'amount',
						'rate'         => 2.5,
						'nisab_year'   => 24198,
						'amount_label' => 'Baki terendah (RM)',
						'period'       => '/ setahun',
					),
					array(
						'zakat_key'    => 'emas',
						'label'        => 'Zakat Emas',
						'mode'         => 'amount',
						'rate'         => 2.5,
						'nisab_year'   => 24198,
						'amount_label' => 'Nilai emas (RM)',
						'period'       => '/ setahun',
					),
					array(
						'zakat_key'    => 'saham',
						'label'        => 'Zakat Saham',
						'mode'         => 'amount',
						'rate'         => 2.5,
						'nisab_year'   => 24198,
						'amount_label' => 'Nilai portfolio (RM)',
						'period'       => '/ setahun',
					),
					array(
						'zakat_key'    => 'kwsp',
						'label'        => 'Zakat KWSP',
						'mode'         => 'amount',
						'rate'         => 2.5,
						'nisab_year'   => 24198,
						'amount_label' => 'Jumlah pengeluaran (RM)',
						'period'       => '/ pengeluaran',
					),
					array(
						'zakat_key'    => 'qada',
						'label'        => 'Qada Zakat',
						'mode'         => 'flat',
						'rate'         => 2.5,
						'skip_nisab'   => 'yes',
						'amount_label' => 'Amaun qada (RM)',
						'period'       => '',
					),
					array(
						'zakat_key'    => 'pertanian',
						'label'        => 'Zakat Pertanian',
						'mode'         => 'amount',
						'rate'         => 5,
						'nisab_year'   => 24198,
						'amount_label' => 'Nilai hasil (RM)',
						'period'       => '/ musim',
					),
					array(
						'zakat_key'    => 'lain-lain',
						'label'        => 'Lain-lain / Sumbangan',
						'mode'         => 'flat',
						'rate'         => 2.5,
						'skip_nisab'   => 'yes',
						'amount_label' => 'Amaun (RM)',
						'period'       => '',
					),
				),
			)
		);
		$this->add_control(
			'btn_text',
			array(
				'label'   => __( 'Teks butang', 'rakanzakat' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => 'Tunaikan Zakat Ini Sekarang',
			)
		);
		$this->add_control(
			'btn_url',
			array(
				'label'   => __( 'Pautan butang', 'rakanzakat' ),
				'type'    => \Elementor\Controls_Manager::URL,
				'default' => array( 'url' => '#bayar' ),
			)
		);
		$this->add_control(
			'card_note',
			array(
				'label'   => __( 'Nota bawah kad', 'rakanzakat' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => 'Resit rasmi LZS serta-merta • Pelepasan cukai LHDN 100%',
			)
		);
		$this->end_controls_section();
		$this->register_style_controls();
	}

	protected function render() {
		echo RakanZakat_Stitch_Sections::calculator( $this->get_settings_for_display() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
