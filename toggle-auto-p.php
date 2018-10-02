<?php
/*
Plugin Name: Toggle Wordpress Auto Paragraph (wpautop)
Description: wpautop() is a built in WordPress filter that changes double line-breaks in the text into HTML paragraphs. WordPress uses it to filter the content and the excerpt. This plugin allows you to toggle this filter per post/page.
Author: Naycer Tulas
Author URI: https://naycertulas.com
*/

if( ! function_exists('njgt_auto_p_meta_box_markup')) {

	function njgt_auto_p_meta_box_markup() {

		wp_nonce_field(basename(__FILE__), "njgt_auto_p_nonce");

		$autop = get_post_meta(get_the_ID(), 'njgt-no-auto-p',true);

		?>

		<div class="njgt-toggle">

			<span>Disable wpautop for this <?php echo get_post_type(); ?> ? </span>

			<label for="njgt-no-auto-p" class="switch"> 
		
				<input type="checkbox" value="true" name="njgt-no-auto-p" id="njgt-no-auto-p" <?php checked( $autop, "true" ); ?> >

				<span class="slider round"></span>

			</label>	
			
		</div>

		<?php

	}
}

if( ! function_exists('njgt_save_auto_p_meta_box')) {

	function njgt_save_auto_p_meta_box($post_id,$post,$update){

		if ( ! isset( $_POST["njgt_auto_p_nonce"] ) || ! wp_verify_nonce( $_POST["njgt_auto_p_nonce"], basename(__FILE__) ) ) {

			return $post_id;

		}

		if ( ! current_user_can("edit_post", $post_id) ) {

			return $post_id;

		}

		if ( defined("DOING_AUTOSAVE") && DOING_AUTOSAVE ) {
			return $post_id;
		}

		if( isset($_POST["njgt-no-auto-p"]) ) {	

			$meta_box_checkbox_value = $_POST["njgt-no-auto-p"];

		}

		update_post_meta($post_id, "njgt-no-auto-p", $meta_box_checkbox_value);
		
	}

}

add_action("save_post", "njgt_save_auto_p_meta_box", 10, 3);


if ( ! function_exists('njgt_add_auto_p_meta_box') ){

	function add_auto_p_meta_box() {

		add_meta_box("njgt-auto-p-meta-box", "Toggle Auto Paragraph", "njgt_auto_p_meta_box_markup", ['post','page'], "side", "low", null);
	}
}

add_action("add_meta_boxes", "add_auto_p_meta_box");


add_filter('the_content', 'njgt_no_wpautop', 9);

if( ! function_exists('njgt_no_wpautop') ) {

	function njgt_no_wpautop( $content ) {

		if ( get_post_meta(get_the_ID(),'njgt-no-auto-p',true) == "true" ) { 

			remove_filter( 'the_content', 'wpautop' );

			return $content;

		} else {

			return $content;

		}

	}

}

function njgt_toggle_auto_admin_scripts() {

	wp_enqueue_style( 'njgt-toggle-admin-style', plugins_url('/admin.css',__FILE__) );

	wp_enqueue_script( 'njgt-toggle-admin-script', plugins_url('/admin.js',__FILE__) );

}

add_action( 'admin_enqueue_scripts', 'njgt_toggle_auto_admin_scripts' );
