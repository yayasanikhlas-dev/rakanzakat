<?php
/**
 * Sidebar widget and Gutenberg block for the zakat form.
 *
 * @package RakanZakat
 */

defined( 'ABSPATH' ) || exit;

class RakanZakat_Form_Widget extends WP_Widget {

	public function __construct() {
		parent::__construct(
			'rakanzakat_form',
			__( 'Rakan Zakat — Borang Bayar', 'rakanzakat' ),
			array(
				'description' => __( 'Borang kutipan zakat. Pembayar dihantar ke Billplz.', 'rakanzakat' ),
				'classname'   => 'widget_rakanzakat_form',
			)
		);
	}

	public function widget( $args, $instance ) {
		$title = isset( $instance['title'] ) ? $instance['title'] : '';
		$title = apply_filters( 'widget_title', $title, $instance, $this->id_base );

		echo $args['before_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		if ( $title ) {
			echo $args['before_title'] . esc_html( $title ) . $args['after_title']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		echo RakanZakat_Shortcode::form(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $args['after_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	public function form( $instance ) {
		$title = isset( $instance['title'] ) ? $instance['title'] : __( 'Bayar Zakat', 'rakanzakat' );
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Tajuk', 'rakanzakat' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>
		<?php
	}

	public function update( $new_instance, $old_instance ) {
		return array(
			'title' => sanitize_text_field( $new_instance['title'] ?? '' ),
		);
	}
}

class RakanZakat_Blocks {

	public static function register_widget() {
		register_widget( 'RakanZakat_Form_Widget' );
	}

	public static function register_block() {
		wp_register_script(
			'rakanzakat-form-block',
			RAKANZAKAT_URL . 'blocks/form/index.js',
			array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-server-side-render', 'wp-i18n' ),
			RAKANZAKAT_VERSION,
			true
		);

		register_block_type(
			'rakanzakat/form',
			array(
				'api_version'     => 3,
				'title'           => __( 'Borang Zakat', 'rakanzakat' ),
				'description'     => __( 'Borang bayar zakat melalui Billplz.', 'rakanzakat' ),
				'category'        => 'widgets',
				'icon'            => 'heart',
				'supports'        => array(
					'html'  => false,
					'align' => array( 'wide', 'full' ),
				),
				'editor_script'   => 'rakanzakat-form-block',
				'render_callback' => array( __CLASS__, 'render_form' ),
			)
		);
	}

	public static function render_form() {
		return RakanZakat_Shortcode::form();
	}
}
