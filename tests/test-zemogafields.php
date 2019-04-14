<?php
/**
 * Class SampleTest
 *
 * @package Zemoga_Fields
 */

/**
 * Sample test case.
 */
class Test_ZemogaFields extends WP_UnitTestCase {

	/**
	 * Test the zemoga fields constructor
	 */
	public function test_construct() {
		$zf            = new ZemogaFields();
		$is_registered = has_action( 'add_meta_boxes', array( $zf, 'fields_metabox' ) )
		&& has_action( 'admin_enqueue_scripts', array( $zf, 'fields_scripts' ) )
		&& has_action( 'save_post', array( $zf, 'save_fields' ) );
		$this->assertTrue( $is_registered );
	}

	/**
	 * Test if metabox are addded to pages with fields template
	 *
	 * @return void
	 */
	public function test_fields_metabox() {
		$zf = new ZemogaFields();

		$post_data = array(
			'post_type' => 'page',
		);

		$post_id = self::factory()->post->create_object( $post_data );
		update_post_meta( $post_id, '_wp_page_template', 'page-templates/fields.php' );
		$post = get_post( $post_id );

		$zf->fields_metabox( 'page', $post );

		$this->assertTrue( $this->is_meta_box_registered( 'fields_cont', 'page' ) );
	}

	/**
	 * Test if the fields_cont metabox is not registered when the page doesn't have the 'fields'
	 * template
	 *
	 * @return void
	 */
	public function test_metabox_not_fields_template() {
		global $wp_meta_boxes;
		$wp_meta_boxes = array(); // the previous method add the metabox.

		$zf = new ZemogaFields();

		$post_data = array(
			'post_type' => 'page',
		);

		$post_id = self::factory()->post->create_object( $post_data );
		update_post_meta( $post_id, '_wp_page_template', 'other_template.php' );
		$post = get_post( $post_id );

		$zf->fields_metabox( 'page', $post );

		$this->assertFalse( $this->is_meta_box_registered( 'fields_cont', 'page' ) );
	}

	/**
	 *  Check if a meta box has been registered.
	 *  Returns true if the supplied meta box id has been registered. false if not.
	 *  Note that this does not check what page type
	 *
	 *  @param String $meta_box_id An id for the meta box.
	 *  @param String $post_type The post type the box is registered to  (optional).
	 *  @return mixed Returns boolean or a WP_Error if $wp_meta_boxes is not yet set.
	 */
	public function is_meta_box_registered( $meta_box_id, $post_type = false ) {
		global $wp_meta_boxes, $grist_meta_box_found;
		$grist_meta_box_found = false; // assume not found by default
		// if meta boxes are not yet set up, let's issue an error.
		if ( empty( $wp_meta_boxes ) || ! is_array( $wp_meta_boxes ) ) {
			return false;
		}
		// should we only look at meta boxes for a specific post type?
		if ( $post_type ) {
			$meta_boxes = $wp_meta_boxes[ $post_type ];
		} else {
			$meta_boxes = $wp_meta_boxes;
		}
		// step through each meta box registration and check if the supplied id exists.
		array_walk_recursive(
			$meta_boxes,
			function ( $value, $key, $meta ) {
				global $grist_meta_box_found;
				if ( 'id' === $key && strtolower( $value ) === strtolower( $meta ) ) {
					$grist_meta_box_found = true;
				}
			},
			$meta_box_id
		);
		$return = $grist_meta_box_found; // temp store the return value.
		unset( $grist_meta_box_found );  // remove var from from global space.
		return $return;
	}

	/**
	 * Test if the scripts are enqueued when for page with fields template
	 *
	 * @return void
	 */
	public function test_enqueue_scripts() {
		$zf = new ZemogaFields();

		$scripts_object = array(
			array(
				'id'   => 'zf-scripts',
				'url'  => ZF_URL . 'assets/js/fields-scripts.js',
				'type' => 'script',
			),
			array(
				'id'   => 'zf-styles',
				'url'  => ZF_URL . 'assets/css/fields-styles.css',
				'type' => 'style',
			),
		);

		$post_data = array(
			'post_type' => 'page',
		);

		$post_id = self::factory()->post->create_object( $post_data );
		update_post_meta( $post_id, '_wp_page_template', 'page-templates/fields.php' );
		$post = get_post( $post_id );

		$zf->enqueue_scripts( $post );

		$result = true;

		foreach ( $scripts_object as $script_object ) {
			$id   = $script_object['id'];
			$url  = $script_object['url'];
			$type = $script_object['type'];
			if ( ! $this->script_is_enqueued( $id, $url, $type ) ) {
				$result = false;
				break;
			}
		}

		// Test wp_localize_script.
		global $wp_scripts;
		$data = $wp_scripts->get_data( 'zf-scripts', 'data' );
		$pos  = strpos( $data, '"fields_template"' );
		$pos  = false === $pos ? false : true;

		$this->assertTrue( $pos );
		$this->assertTrue( $result );
	}

	/**
	 * Return true if a script/style is enqueued
	 *
	 * @param string $script_id id of the enqueued script/style.
	 * @param string $script_url url of the enqueued script/style.
	 * @param string $script_type it can be 'script' or 'style'.
	 * @return boolean
	 */
	public function script_is_enqueued( $script_id, $script_url, $script_type = 'script' ) {
		$script_array = [];
		global $wp_styles, $wp_scripts;
		$script_object;
		switch ( $script_type ) {
			case 'script':
				$script_object = $wp_scripts;
				break;
			case 'style':
				$script_object = $wp_styles;
				break;
		}

		foreach ( $script_object->queue as $script ) {
			if ( $script_object->registered[ $script ]->src === $script_url
				&& $script_id === $script ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Test if the metadata is saved in the page.
	 *
	 * @return void
	 */
	public function test_save_fields() {
		$zf = new ZemogaFields();

		$_POST['post_type'] = 'page';
		$_POST['devs']      = array(
			array(
				'name'    => 'Carlos',
				'caption' => 'Developer',
				'url'     => 'https://google.com',
				'photo'   => 'image.jpg',
			),
		);

		wp_set_current_user( 1 ); // Set the logged user.

		$post_data = array(
			'post_type' => 'page',
		);

		$post_id = self::factory()->post->create_object( $post_data );
		$zf->save_fields( $post_id );

		$saved_data = get_post_meta( $post_id, 'developers', true );

		$this->assertCount( 1, $saved_data );
		$this->assertArrayHasKey( 'name', $saved_data[0] );
		$this->assertArrayHasKey( 'caption', $saved_data[0] );
		$this->assertArrayHasKey( 'url', $saved_data[0] );
		$this->assertArrayHasKey( 'photo', $saved_data[0] );
		$this->assertSame( 'Carlos', $saved_data[0]['name'] );
		$this->assertSame( 'Developer', $saved_data[0]['caption'] );
		$this->assertSame( 'https://google.com', $saved_data[0]['url'] );
		$this->assertSame( 'image.jpg', $saved_data[0]['photo'] );

	}
}
