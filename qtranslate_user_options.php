<?php
add_action( 'show_user_profile', 'qtranxf_show_extra_profile_fields' );
add_action( 'edit_user_profile', 'qtranxf_show_extra_profile_fields' );

function qtranxf_show_extra_profile_fields( $user ) {
	global $q_config;
	if ( $q_config['highlight_mode'] != QTX_HIGHLIGHT_MODE_NONE ) { ?>

		<h3><?php _e( 'Translation options', 'qtranslate' ) ?></h3>

		<table class="form-table">

			<tr>
				<th><label for="qtranslate_highlight_disabled"><?php _e( 'Do not highlight fields', 'qtranslate' ) ?></label></th>

				<td>
					<input type="checkbox" value="1" name="qtranslate_highlight_disabled" id="qtranslate_highlight_disabled" <?php checked( get_the_author_meta( 'qtranslate_highlight_disabled', $user->ID ) ); ?> /><br/>
					<span class="description"><?php _e( 'If you do not like that the translatable fields are highlighted, you can disable that option here', 'qtranslate' ) ?></span>
				</td>
			</tr>

		</table>
	<?php
	}
}

add_action( 'personal_options_update', 'qtranxf_save_extra_profile_fields' );
add_action( 'edit_user_profile_update', 'qtranxf_save_extra_profile_fields' );

function qtranxf_save_extra_profile_fields( $user_id ) {
	global $q_config;

	if ( ! current_user_can( 'edit_user', $user_id ) ) {
		return false;
	}

	if ( $q_config['highlight_mode'] != QTX_HIGHLIGHT_MODE_NONE ) {
		update_user_meta( $user_id, 'qtranslate_highlight_disabled', isset($_POST['qtranslate_highlight_disabled']) );
	}
}