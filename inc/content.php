<?php

add_action( 'admin_enqueue_scripts', 'mrt_queue_scripts' );

?>
<style>
	input[type=text] {
		width: 100%;
	}

	.form_awesome code {
		font-size: 11px;
	}

	#exclude_id {
		width: 100%;
		height: 300px;
	}

	.mandatory {
		color: darkred;
	}

</style>
<div class="wrap">
	<h2>
		Create Pages on Multisite
	</h2>

	<p></p>

	<form action="" method="post" id="form_awesome" enctype="multipart/form-data">
		<table class="wp-list-table widefat fixed posts" cellspacing="0">
			<thead>
			<tr>
				<td>
					Select the domain you wish to create page for
				</td>
			</tr>
			</thead>
			<tbody>
			<tr>
				<td>
					<?php $list = $this->wpriders_gethomepageslist(); ?>
					<label for="exclude_id"></label>
					<select name="exclude_id[]" id="exclude_id" multiple="multiple">
						<?php foreach ( $list as $domains ) { ?>
							<option
								value="<?php echo $domains->blog_id; ?>" <?php echo ( isset( $_POST['exclude_id'] ) && in_array( $domains->blog_id, $_POST['exclude_id'] ) ) ? 'selected' : ''; ?>>
								<?php echo "(ID: {$domains->blog_id}) " . $domains->domain; ?>
								<?php if ( SUBDOMAIN_INSTALL ) {
									echo $domains->path;
								} ?>
							</option>
						<?php } ?>
					</select>
					<br/>
					<a id="select_my_options" href="#">[ select all ]</a> / <a id="deselect_my_options" href="#">[
						deselect all ]</a>
				</td>
			</tr>
			</tbody>
		</table>
		<br/>

		<div id="titlediv">
			<label for="new_title">Page title <span class="mandatory">*</span></label>
			<input type="text" id="new_title" name="new_title" value="<?php echo @$_POST['new_title'] ?>" required/><br/><br/>
			<label for="new_slug">Page slug <span class="mandatory">*</span></label>
			<input type="text" id="new_slug" name="new_slug" value="<?php echo @$_POST['new_slug'] ?>" required/><br/><br/>
			Content <span class="mandatory">*</span>:
			<?php
			$settings  = array( 'media_buttons' => true );
			$editor_id = 'new_content';
			$content   = ( isset( $_POST['new_content'] ) ) ? stripslashes( urldecode( $_POST['new_content'] ) ) : "";
			wp_editor( $content, $editor_id, $settings );
			?>
		</div>
		<input name="cpb_option" value="set_home" type="radio"
		       autocomplete="off" <?php if ( isset( $_POST['cpb_option'] ) && $_POST['cpb_option'] == 'set_home' ) {
			echo 'checked';
		} ?>>
		<code>Set this page as home</code>
		<br/><br/>

		<input name="cpb_option" value="set_blog" type="radio"
		       autocomplete="off" <?php if ( isset( $_POST['cpb_option'] ) && $_POST['cpb_option'] == 'set_blog' ) {
			echo 'checked';
		} ?>>
		<code>Set this page as blog</code>
		<br/><br/>

		<input name="cpb_option" value="set_nan" type="radio"
		       autocomplete="off" <?php if ( isset( $_POST['cpb_option'] ) && $_POST['cpb_option'] == 'set_nan' ) {
			echo 'checked';
		} ?>>
		<code>just plain save</code>

		<br/>
		<br/>
		<select name="get_template" id="get_template">
			<option
				value="default" <?php echo ( isset( $_POST['get_template'] ) && $_POST['get_template'] == 'default' ) ? "selected" : "" ?>>
				Default Template
			</option>
			<?php
			$templates = wp_get_theme()->get_page_templates();
			foreach ( $templates as $template_name => $template_filename ) {
				$selected = "";
				if ( isset( $_POST['get_template'] ) && $_POST['get_template'] == $template_name ) {
					$selected = "selected='selected'";
				}
				echo "<option value='$template_name' $selected>$template_filename</option>";
			}
			?>
		</select>
		<br/>
		<br/>
		(<span class="mandatory">*</span>) Mandatory fields
		<?php submit_button( __( 'Proceed' ) ); ?>
	</form>
	<hr/>
	<div id="controlls"></div>
	<?php
	$do_script = false;
	if ( strtolower( $_SERVER['REQUEST_METHOD'] ) == 'post' && isset( $_POST['exclude_id'] ) ) {

		$blog_list = $_POST['exclude_id'];
		if ( count( $blog_list ) > 0 ) {
			$do_script = true;
		}

		echo "<script type='application/javascript'>\n";
		echo "      var blog_list = [" . implode( ',', $blog_list ) . "];\n";
		echo "</script>\n";
	} ?>
</div>
<script type="application/javascript">

	jQuery(document).ready(function () {
		jQuery("#select_my_options").click(function (e) {
			e.preventDefault();
			jQuery('select#exclude_id option').prop('selected', true);
		});

		jQuery("#deselect_my_options").click(function (e) {
			e.preventDefault();
			jQuery('select#exclude_id option').prop('selected', false);
		});
		<?php
		if ( $do_script ) {
			echo "proceed_with_new_blog(blog_list.shift());";
		}
		?>
		function proceed_with_new_blog(index) {
			jQuery.ajax({
				type: 'POST',
				url: ajaxurl,
				data: jQuery('#form_awesome').serialize() + '&action=buildpageblog&blog_id=' + index,
				success: function (response) {
					if (response !== Object(response) || ( typeof response.success === "undefined" && typeof response.error === "undefined" )) {
						response = new Object;
						response.success = false;
						response.error = true;
						jQuery('#controlls').append('Something went wrong, the server response was null<br/>');
						console.log(response.data);
					} else {
						if (response.success)jQuery('#controlls').append('<strong style="color:darkgreen;">' + response.data + '</strong><br/>');
						else if (!response.success) {
							jQuery('#controlls').append('<strong style="color:darkred;font-weight: bold;">' + response.data + '</strong><br/>');
							console.log(response.data);
						}
						if (blog_list.length) {
							proceed_with_new_blog(blog_list.shift());
						} else {
							jQuery('#controlls').append('ALL DONE !!!');
						}
					}

				},
				error: function (jqXHR, textStatus, errorThrown) {
					jQuery('#controlls').append('<p>status code: ' + jqXHR.status + '</p><p>errorThrown: ' + errorThrown + '</p><p>jqXHR.responseText:</p><div>' + jqXHR.responseText + '</div>');
					console.log('jqXHR:');
					console.log(jqXHR);
					console.log('textStatus:');
					console.log(textStatus);
					console.log('errorThrown:');
					console.log(errorThrown);
				}
			});
		}

	});


</script>
