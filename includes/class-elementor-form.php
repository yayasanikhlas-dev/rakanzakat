<?php
/**
 * Elementor widget: Borang Zakat.
 *
 * @package RakanZakat
 */

defined( 'ABSPATH' ) || exit;

class RakanZakat_Elementor_Form_Widget extends \Elementor\Widget_Base {

	public function get_name() {
		return 'rakanzakat_form';
	}

	public function get_title() {
		return __( 'Borang Zakat', 'rakanzakat' );
	}

	public function get_icon() {
		return 'eicon-form-horizontal';
	}

	public function get_categories() {
		return array( 'rakanzakat', 'general' );
	}

	public function get_keywords() {
		return array( 'zakat', 'billplz', 'donation', 'form', 'rakanzakat' );
	}

	public function get_style_depends() {
		return array( 'rakanzakat-form' );
	}

	public function get_script_depends() {
		return array( 'rakanzakat-form' );
	}

	protected function register_controls() {
		$this->start_controls_section(
			'content_section',
			array(
				'label' => __( 'Kandungan', 'rakanzakat' ),
				'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'header',
			array(
				'label'        => __( 'Tunjuk header atas borang', 'rakanzakat' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => __( 'Ya', 'rakanzakat' ),
				'label_off'    => __( 'Tidak', 'rakanzakat' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->add_control(
			'kicker',
			array(
				'label'   => __( 'Lencana', 'rakanzakat' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => 'Cara Pembayaran',
			)
		);

		$this->add_control(
			'title',
			array(
				'label'   => __( 'Tajuk', 'rakanzakat' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => 'Bayar Zakat Secara Online',
			)
		);

		$this->add_control(
			'description',
			array(
				'label'   => __( 'Penerangan', 'rakanzakat' ),
				'type'    => \Elementor\Controls_Manager::TEXTAREA,
				'default' => 'Sistem pembayaran zakat secara online ini menyediakan platform pembayaran zakat dengan lebih efisyen dan bersistematik.',
			)
		);

		$this->add_control(
			'step_1',
			array(
				'label'   => __( 'Langkah 1', 'rakanzakat' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => '1. Isi Maklumat Pembayaran',
			)
		);

		$this->add_control(
			'step_2',
			array(
				'label'   => __( 'Langkah 2', 'rakanzakat' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => '2. Pilih Kaedah Pembayaran',
			)
		);

		$this->add_control(
			'step_3',
			array(
				'label'   => __( 'Langkah 3', 'rakanzakat' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => '3. Resit bayaran zakat',
			)
		);

		$this->add_control(
			'button',
			array(
				'label'   => __( 'Teks butang', 'rakanzakat' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => 'Bayar Sekarang',
			)
		);

		$this->add_control(
			'note',
			array(
				'label'   => __( 'Nota bawah', 'rakanzakat' ),
				'type'    => \Elementor\Controls_Manager::TEXT,
				'default' => '',
			)
		);

		$this->add_control(
			'presets',
			array(
				'label'        => __( 'Tunjuk amaun cepat', 'rakanzakat' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'label_on'     => __( 'Ya', 'rakanzakat' ),
				'label_off'    => __( 'Tidak', 'rakanzakat' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			)
		);

		$this->end_controls_section();
	}

	protected function render() {
		$s = $this->get_settings_for_display();
		echo RakanZakat_Shortcode::form( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			array(
				'header'      => ! empty( $s['header'] ) ? 'yes' : 'no',
				'kicker'      => $s['kicker'] ?? '',
				'title'       => $s['title'] ?? '',
				'description' => $s['description'] ?? '',
				'step_1'      => $s['step_1'] ?? '',
				'step_2'      => $s['step_2'] ?? '',
				'step_3'      => $s['step_3'] ?? '',
				'button'      => $s['button'] ?? '',
				'note'        => $s['note'] ?? '',
				'presets'     => ! empty( $s['presets'] ) ? 'yes' : 'no',
			)
		);
	}
}
