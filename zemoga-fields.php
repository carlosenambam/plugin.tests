<?php
/**
 * Plugin Name: Zemoga Fields
 * Description: This plugin allow content managers to add Developer information to pages with the 'Fields' template
 * Author: Carlos Alvarez
 * Version: 1.0.0
 *
 * @package ZF
 */

if ( ! defined( 'ABSPATH' ) ) { // Prevents direct access to this file.
	exit;
}


define( 'ZF_URL', plugins_url( basename( __DIR__ ) ) . '/' );
define( 'ZF_VERSION', '1.0.0' );
/**
 * Plugin main class
 */
class ZemogaFields {

	/**
	 * Add the necesary hooks to add fields to the pages
	 */
	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'fields_metabox' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'fields_scripts' ) );
		add_action( 'save_post', array( $this, 'save_fields' ) );
	}

	/**
	 * Add the fields to the page edit screen
	 *
	 * @param string $post_type Name of the post type.
	 * @param object $post Current post.
	 */
	public function fields_metabox( $post_type, $post = null ) {
		// Only add the fields if the page uses the 'Fields' template.
		if ( 'page-templates/fields.php' === get_page_template_slug( $post ) ) {
			add_meta_box( 'fields_cont', 'Developers', array( $this, 'developers' ), 'page', 'normal', 'high' );
		}
	}

	/**
	 * HTML output of the fields
	 *
	 * @param WP_Post $post Current post.
	 */
	public function developers( $post ) {
		$devs = get_post_meta( $post->ID, 'developers', true );
		?>
		<button id="add_dev" class="button-primary">Add Developer</button>
		<div class="developers">
			<?php
			$i = 1;
			foreach ( $devs as $dev ) {
				echo $this->developer_fields_template( $dev, $i );
				$i++;
			}
			?>
		</div>
		<?php
	}

	/**
	 * Returns the developers fields template
	 *
	 * @param array   $values Array contaning the values of the fields.
	 * @param integer $id ID of this fields template.
	 * @return string Developer fields template.
	 */
	public function developer_fields_template( $values = array(), $id = 0 ) {
		ob_start();
		?>
		<div class="dev" id="dev<?php echo $id ? $id : '[id]'; ?>">
			<p>Developer</p>

			<input type="text" placeholder="Name" name="devs[<?php echo $id ? $id : '[id]'; ?>][name]" value="<?php echo isset( $values['name'] ) ? esc_attr( $values['name'] ) : ''; ?>">

			<button class="add_photo button-primary" data-dev="<?php echo $id ? $id : '[id]'; ?>">Add photo</button>

			<div class="show_photo">
				<?php
				if ( isset( $values['photo'] ) && $values['photo'] ) {
					?>
					<img src="<?php echo esc_attr( $values['photo'] ); ?>">
					<?php
				}
				?>
			</div>

			<input type="hidden" class="dev_photo" name="devs[<?php echo $id ? $id : '[id]'; ?>][photo]" value="<?php echo isset( $values['photo'] ) ? esc_attr( $values['photo'] ) : ''; ?>">

			<input type="text" placeholder="Caption" name="devs[<?php echo $id ? $id : '[id]'; ?>][caption]" value="<?php echo isset( $values['caption'] ) ? esc_attr( $values['caption'] ) : ''; ?>">

			<input type="text" class="url" placeholder="URL" name="devs[<?php echo $id ? $id : '[id]'; ?>][url]" value="<?php echo isset( $values['url'] ) ? esc_attr( $values['url'] ) : ''; ?>">
			<a href="" data-dev="<?php echo $id ? $id : '[id]'; ?>" class="delete_dev">Delete</a>
		</div>
		<?php
		$html = ob_get_contents();
		ob_end_clean();
		return $html;
	}

	/**
	 * Enqueue the admin scripts for the fields
	 *
	 * @param object $post Current post.
	 * @return void
	 */
	public function enqueue_scripts( $post ) {
		// Only enqueue the fields scripts if the page uses the 'Fields' template.
		if ( 'page-templates/fields.php' === get_page_template_slug( $post ) ) {
			wp_enqueue_media();
			wp_enqueue_script( 'zf-scripts', ZF_URL . 'assets/js/fields-scripts.js', array( 'jquery' ), ZF_VERSION, true );

			wp_localize_script(
				'zf-scripts',
				'zf_vars',
				array(
					'fields_template' => esc_html( $this->developer_fields_template() ),
				)
			);

			wp_enqueue_style( 'zf-styles', ZF_URL . 'assets/css/fields-styles.css', null, ZF_VERSION );
		}
	}

	/**
	 * Enqueue the admin scripts for the fields
	 */
	public function fields_scripts() {
		global $post;
		$this->enqueue_scripts( $post );
	}

	/**
	 * Save the fields as page metadata
	 *
	 * @param integer $post_id ID of the current post.
	 */
	public function save_fields( $post_id ) {
		if ( ! isset( $_POST['post_type'] ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_page', $post_id ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_id;
		}

		if ( 'page' === $_POST['post_type'] ) {
			if ( isset( $_POST['devs'] ) && ! empty( $_POST['devs'] ) ) {
				$devs = $_POST['devs'];

				foreach ( $devs as $dev ) {
					$dev['name']    = sanitize_text_field( $dev['name'] );
					$dev['caption'] = sanitize_text_field( $dev['caption'] );
					$dev['url']     = sanitize_text_field( $dev['url'] );
					$dev['photo']   = esc_url_raw( $dev['photo'] );
				}
				update_post_meta( $post_id, 'developers', $_POST['devs'] );
			}
		} else {
			return;
		}

	}

}

new ZemogaFields();
