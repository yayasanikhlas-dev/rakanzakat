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
				'label'   => __( 'Penerangan', 'rakanzakat' ),
				'type'    => \Elementor\Controls_Manager::TEXTAREA,
				'default' => '',
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
				'label'   => __( 'Teks panduan', 'rakanzakat' ),
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
						'zakat_key' => 'pendapatan',
					),
					array(
						'icon'      => 'shop',
						'title'     => 'Zakat Perniagaan',
						'text'      => 'Zakat aset perniagaan, perkongsian, enterprise mahupun syarikat Sdn Bhd mengikut tempoh haul.',
						'zakat_key' => 'perniagaan',
					),
					array(
						'icon'      => 'savings',
						'title'     => 'Zakat Wang Simpanan',
						'text'      => 'Baki terendah akaun simpanan atau deposit tetap yang genap tempoh setahun melebihi nisab.',
						'zakat_key' => 'simpanan',
					),
					array(
						'icon'      => 'diamond',
						'title'     => 'Zakat Emas',
						'text'      => 'Kiraan zakat emas simpanan (melebihi 85g) serta emas perhiasan melebihi kadar \'uruf negeri.',
						'zakat_key' => 'emas',
					),
					array(
						'icon'      => 'wallet',
						'title'     => 'Zakat KWSP',
						'text'      => 'Dikenakan ke atas pengeluaran wang KWSP pada hari pengeluaran diterima secara tunai.',
						'zakat_key' => 'kwsp',
					),
					array(
						'icon'      => 'trend',
						'title'     => 'Zakat Saham',
						'text'      => 'Pelaburan ekuiti dan unit amanah patuh Syariah yang dimiliki bagi tujuan dividen atau dagangan.',
						'zakat_key' => 'saham',
					),
					array(
						'icon'      => 'update',
						'title'     => 'Qada Zakat',
						'text'      => 'Menyempurnakan bayaran zakat bagi tahun-tahun lalu yang terlepas atau belum sempat ditunaikan.',
						'zakat_key' => 'qada',
					),
					array(
						'icon'      => 'layers',
						'title'     => 'Lain-lain Zakat',
						'text'      => 'Termasuk zakat fitrah, harta rikaz, zakat ternakan dan pelbagai kategori khas lain mengikut syarak.',
						'zakat_key' => 'lain-lain',
					),
				),
			)
		);
		$this->end_controls_section();
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
	}

	protected function render() {
		echo RakanZakat_Stitch_Sections::cta( $this->get_settings_for_display() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
