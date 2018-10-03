<?php
/*
Plugin Name: Toggle Wordpress Auto Paragraph (wpautop)
Description: wpautop() is a built in WordPress filter that changes double line-breaks in the text into HTML paragraphs. WordPress uses it to filter the content and the excerpt. This plugin allows you to toggle this filter per post/page.
Author: Naycer Tulas
Author URI: https://naycertulas.com
*/

if ( ! function_exists('njgt_is_edit_page')) {
	function njgt_is_edit_page( $new_edit = null ) {

		global $pagenow;

		if (!is_admin()) return false;


		if($new_edit == "edit") {

			return in_array( $pagenow, array( 'post.php',  ) );

		}

		elseif($new_edit == "new") {

			return in_array( $pagenow, array( 'post-new.php' ) );

		}

		else {

			return in_array( $pagenow, array( 'post.php', 'post-new.php' ) );

		}
	}
}

if( ! function_exists('njgt_auto_p_meta_box_markup')) {

	function njgt_auto_p_meta_box_markup() {

		wp_nonce_field(basename(__FILE__), "njgt_auto_p_nonce");

		$autop = get_post_meta(get_the_ID(), 'njgt-no-auto-p',true);

		echo '<pre>',var_dump($autop),'</pre>';

		$checked = false;
		
		if (njgt_is_edit_page('new')) {

			$post_type_setting = get_option('njgt-no-auto-p-'.get_post_type());

			if ($post_type_setting == "true") {

				$checked = true;

			}

		} elseif (njgt_is_edit_page('edit')) {

			if ($autop == "true") {

				$checked = true;

			}
		}

		?>

		<div class="njgt-toggle">

			<span>Disable wpautop for this <?php echo get_post_type(); ?> ? </span>

			<label for="njgt-no-auto-p" class="switch"> 
		
				<input type="checkbox" value="true" name="njgt-no-auto-p" id="njgt-no-auto-p" <?php if($checked) { echo "checked" ; } ?> >

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

		} else {
			$meta_box_checkbox_value = "false";
		}

		update_post_meta($post_id, "njgt-no-auto-p", $meta_box_checkbox_value);
		
	}

}

add_action("save_post", "njgt_save_auto_p_meta_box", 10, 3);


if ( ! function_exists('njgt_add_auto_p_meta_box') ){

	function add_auto_p_meta_box() {

		$cpt_args = array(

			'public'   => true,
			'_builtin' => false

		);


		$custom_post_types = get_post_types($cpt_args);

		$builtin_post_types = array('post','page');

		$screen = array_merge($builtin_post_types,$custom_post_types);

		add_meta_box("njgt-auto-p-meta-box", "Toggle Auto Paragraph", "njgt_auto_p_meta_box_markup", $screen, "side", "low", null);
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

if( ! function_exists('njgt_add_toggle_settings_page') ) {

	function njgt_add_toggle_settings_page() {

		add_options_page(__('Toggle Auto Paragaph Settings'), __('Toggle Auto Paragaph'), 'manage_options', 'njgt-toggle-autop-settings', 'njgt_toggle_autop_settings');

	}

}

add_action('admin_menu', 'njgt_add_toggle_settings_page');

if(! function_exists('njgt_toggle_autop_settings')){

	function njgt_toggle_autop_settings() {

		$cpt_args = array(
			'public'   => true,
			'_builtin' => false
		);


		$custom_post_types = get_post_types($cpt_args);

		$builtin_post_types = array('post','page');

		$all_post_types = array_merge($builtin_post_types,$custom_post_types);

		if (!current_user_can('manage_options')) {

			wp_die( __('You do not have sufficient permissions to access this page.') );

		}


		$hidden_field_name = 'mt_submit_hidden';


		if( isset($_POST[ $hidden_field_name ]) && $_POST[ $hidden_field_name ] == 'Y' ) {

		
			 foreach ($all_post_types as $post_type):
				update_option( 'njgt-no-auto-p-'.$post_type,$_POST['njgt-no-auto-p-'.$post_type]);
			 endforeach;
		

			?>

			<div class="updated"><p><strong><?php _e('Settings saved.'); ?></strong></p></div>

			<?php

		}

		?>

		<div class="wrap">

			<h2>Toggle Auto Paragaph Settings</h2>	

			<form name="form1" method="post" action="">

				<input type="hidden" name="<?php echo $hidden_field_name; ?>" value="Y">

				<h4><strong style="color:#b20000">Disable</strong> wpautop by default on the following post types (only affects new posts) : </h4>

				<?php foreach ($all_post_types as $post_type): ?>

					<?php $curr_val = get_option('njgt-no-auto-p-'.$post_type); ?>

					<div class="njgt-toggle njgt-setting">

						<span class="cpt"> <?php echo ucfirst($post_type); ?> </span>

						<label for="njgt-no-auto-p-<?php echo $post_type; ?>" class="switch"> 

							<input type="checkbox" value="true" name="njgt-no-auto-p-<?php echo $post_type; ?>" id="njgt-no-auto-p-<?php echo $post_type; ?>" <?php checked($curr_val, "true");?> >

							<span class="slider round"></span>

						</label>	

					</div>

				<?php endforeach; ?>

				<hr />

				<p class="submit"><input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes') ?>" /></p>

			</form>

		</div>

		<?php
	}

}
