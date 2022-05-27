<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function qtranxf_migrate_options_update( $name_to, $name_from ) {
    global $wpdb;
    $option_names = $wpdb->get_col( "SELECT `option_name` FROM {$wpdb->options} WHERE `option_name` LIKE '$name_to\_%'" );
    foreach ( $option_names as $name ) {
        if ( strpos( $name, '_flag_location' ) > 0 ) {
            continue;
        }
        $nm    = str_replace( $name_to, $name_from, $name );
        $value = get_option( $nm );
        if ( $value === false ) {
            continue;
        }
        update_option( $name, $value );
    }
}

function qtranxf_migrate_options_copy( $name_to, $name_from ) {
    global $wpdb;
    $options = $wpdb->get_results( "SELECT option_name, option_value FROM {$wpdb->options} WHERE `option_name` LIKE '$name_from\_%'" );

    $skip_options = [
        'qtranslate_flag_location',
        'qtranslate_admin_notices',
        'qtranslate_domains',
        'qtranslate_editor_mode',
        'qtranslate_custom_fields',
        'qtranslate_custom_field_classes',
        'qtranslate_text_field_filters',
        'qtranslate_qtrans_compatibility',
        'qtranslate_header_css_on',
        'qtranslate_header_css',
        'qtranslate_filter_options_mode',
        'qtranslate_filter_options',
        'qtranslate_highlight_mode',
        'qtranslate_highlight_mode_custom_css',
        'qtranslate_lsb_style',
        'qtranslate_custom_i18n_config',
        'qtranslate_config_files',
        'qtranslate_page_configs',
        'qtranslate_admin_config',
        'qtranslate_front_config',
    ];
    foreach ( $options as $option ) {
        $name = $option->option_name;
        if ( ! in_array( $name, $skip_options ) and ! strpos( $name, '_flag_location' ) ) {
            $value = maybe_unserialize( $option->option_value );
            $nm    = str_replace( $name_from, $name_to, $name );
            update_option( $nm, $value );
        }
    }
    //save enabled languages
    global $q_config, $qtranslate_options;
    foreach ( $qtranslate_options['languages'] as $nm => $opn ) {
        $op = str_replace( $name_from, $name_to, $opn );
        update_option( $op, $q_config[ $nm ] );
    }
}

function qtranxf_migrate_import_mqtranslate() {
    qtranxf_migrate_import( 'mqTranslate', 'mqtranslate' );
    update_option( 'qtranslate_qtrans_compatibility', '1' );//since 3.1
    $nm = '<strong>mqTranslate</strong>';
    qtranxf_add_warning( sprintf( __( 'Option "%s" has also been turned on, as the most common case for importing configuration from %s. You may turn it off manually if your setup does not require it. Refer to %sFAQ%s for more information.', 'qtranslate' ), __( 'Compatibility Functions', 'qtranslate' ), $nm, '<a href="https://github.com/qtranslate/qtranslate-xt/wiki/FAQ#compatibility-functions" target="_blank">', '</a>' ) );
}

function qtranxf_migrate_export_mqtranslate() {
    qtranxf_migrate_export( 'mqTranslate', 'mqtranslate' );
}

function qtranxf_migrate_import_qtranslate_xp() {
    qtranxf_migrate_import( 'qTranslate Plus', 'ppqtranslate' );
}

function qtranxf_migrate_export_qtranslate_xp() {
    qtranxf_migrate_export( 'qTranslate Plus', 'ppqtranslate' );
}

function qtranxf_migrate_import( $plugin_name, $name_from ) {
    qtranxf_migrate_options_update( 'qtranslate', $name_from );
    $nm = '<strong>' . $plugin_name . '</strong>';
    qtranxf_add_warning( sprintf( __( 'Applicable options and taxonomy names from plugin %s have been imported. Note that the multilingual content of posts, pages and other objects has not been altered during this operation. There is no additional operation needed to import content, since its format is compatible with %s.', 'qtranslate' ), $nm, 'qTranslate&#8209;XT' ) . ' ' . sprintf( __( 'It might be a good idea to review %smigration instructions%s, if you have not yet done so.', 'qtranslate' ), '<a href="https://github.com/qtranslate/qtranslate-xt/wiki/Migration-Guide" target="_blank">', '</a>' ) );
    qtranxf_add_warning( sprintf( __( '%sImportant%s: Before you start making edits to post and pages, please, make sure that both, your front site and admin back-end, work under this configuration. It may help to review "%s" and see if any of conflicting plugins mentioned there are used here. While the current content, coming from %s, is compatible with this plugin, the newly modified posts and pages will be saved with a new square-bracket-only encoding, which has a number of advantages comparing to former %s encoding. However, the new encoding is not straightforwardly compatible with %s and you will need an additional step available under "%s" option if you ever decide to go back to %s. Even with this additional conversion step, the 3rd-party plugins custom-stored data will not be auto-converted, but manual editing will still work. That is why it is advisable to create a test-copy of your site before making any further changes. In case you encounter a problem, please give us a chance to improve %s, send the login information to the test-copy of your site to %s along with a detailed step-by-step description of what is not working, and continue using your main site with %s meanwhile. It would also help, if you share a success story as well, either on %sthe forum%s, or via the same e-mail as mentioned above. Thank you very much for trying %s.', 'qtranslate' ), '<strong>', '</strong>', '<a href="https://github.com/qtranslate/qtranslate-xt/issues" target="_blank">' . 'Known Issues' . '</a>', $nm, 'qTranslate', $nm, '<a href="https://github.com/qtranslate/qtranslate-xt/wiki/Migration-Guide#convert-database" target="_blank"><strong>' . __( 'Convert Database', 'qtranslate' ) . '</strong></a>', $nm, 'qTranslate&#8209;XT', '[no mail support]', $nm, '<a href="https://github.com/qTranslate/qtranslate-xt/issues">', '</a>', 'qTranslate&#8209;XT' ) . '<br/>' . __( 'This is a one-time message, which you will not see again, unless the same import is repeated.', 'qtranslate' ) );
}

function qtranxf_migrate_export( $plugin_name, $name_to ) {
    qtranxf_migrate_options_copy( $name_to, 'qtranslate' );
    $nm = '<strong>' . $plugin_name . '</strong>';
    qtranxf_add_message( sprintf( __( 'Applicable options have been exported to plugin %s. If you have done some post or page updates after migrating from %s, then "%s" operation is also required to convert the content to "dual language tag" style in order for plugin %s to function.', 'qtranslate' ), $nm, $nm, '<a href="https://github.com/qtranslate/qtranslate-xt/wiki/Migration-Guide/convert-database/" target="_blank"><strong>' . __( 'Convert Database', 'qtranslate' ) . '</strong></a>', $nm ) );
}

function qtranxf_migrate_plugins() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    foreach ( $_POST as $key => $value ) {
        if ( ! is_string( $value ) ) {
            continue;
        }
        if ( $value == 'none' ) {
            continue;
        }
        if ( ! qtranxf_endsWith( $key, '-migration' ) ) {
            continue;
        }
        $plugin = substr( $key, 0, -strlen( '-migration' ) );
        $f      = 'qtranxf_migrate_' . $value . '_' . str_replace( '-', '_', $plugin );
        if ( ! function_exists( $f ) ) {
            continue;
        }
        $f();
        if ( $value == 'import' ) {
            qtranxf_reload_config();
        }
    }
}

add_action( 'qtranslate_save_config', 'qtranxf_migrate_plugins', 30 );

function qtranxf_add_row_migrate( $nm, $plugin, $args = null ) {
    if ( ! file_exists( WP_PLUGIN_DIR . '/' . $plugin ) && ! file_exists( WPMU_PLUGIN_DIR . '/' . $plugin ) ) {
        return;
    }
    $href = isset( $args['href'] ) ? $args['href'] : 'https://wordpress.org/plugins/' . $plugin;
    ?>
    <tr id="qtranslate-<?php echo $plugin; ?>">
        <th scope="row"><?php _e( 'Plugin' ) ?> <a href="<?php echo $href; ?>/" target="_blank"><?php echo $nm; ?></a>
        </th>
        <td>
            <?php
            if ( ! empty( $args['compatible'] ) ) {
                _e( 'There is no need to migrate any setting, the database schema is compatible with this plugin.', 'qtranslate' );
            } else if ( ! empty( $args['text'] ) ) {
                echo $args['text'];
            } else {
                ?>
                <label for="<?php echo $plugin; ?>_no_migration"><input type="radio"
                                                                        name="<?php echo $plugin; ?>-migration"
                                                                        id="<?php echo $plugin; ?>_no_migration"
                                                                        value="none"
                                                                        checked/> <?php _e( 'Do not migrate any setting', 'qtranslate' ) ?>
                </label>
                <br/>
                <label for="<?php echo $plugin; ?>_import_migration"><input type="radio"
                                                                            name="<?php echo $plugin; ?>-migration"
                                                                            id="<?php echo $plugin; ?>_import_migration"
                                                                            value="import"/> <?php echo __( 'Import settings from ', 'qtranslate' ) . $nm; ?>
                </label>
                <?php if ( empty( $args['no_export'] ) ) { ?>
                    <br/>
                    <label for="<?php echo $plugin; ?>_export_migration"><input type="radio"
                                                                                name="<?php echo $plugin; ?>-migration"
                                                                                id="<?php echo $plugin; ?>_export_migration"
                                                                                value="export"/> <?php echo __( 'Export settings to ', 'qtranslate' ) . $nm; ?>
                    </label>
                <?php }
            }
            if ( ! empty( $args['note'] ) ) {
                echo '<p class="qtranxs-notes">' . $args['note'] . '</p>';
            }
            ?>
        </td>
    </tr>
    <?php
}

function qtranxf_admin_section_import_export( $request_uri ) {
    global $q_config;

    QTX_Admin_Settings::open_section( 'import' );
    ?>
    <table class="form-table qtranxs-form-table" id="qtranxs_import_config">
        <tr id="qtranslate-convert-database">
            <th scope="row"><?php _e( 'Convert Database', 'qtranslate' ) ?></th>
            <td>
                <?php printf( __( 'If you are updating from qTranslate 1.x or Polyglot, <a href="%s">click here</a> to convert posts to the new language tag format.', 'qtranslate' ), $request_uri . '&convert=true#import' ) ?>
                <?php printf( __( 'If you have installed qTranslate for the first time on a Wordpress with existing posts, you can either go through all your posts manually and save them in the correct language or <a href="%s">click here</a> to mark all existing posts as written in the default language.', 'qtranslate' ), $request_uri . '&markdefault=true#import' ) ?>
                <?php _e( 'Both processes are <b>irreversible</b>! Be sure to make a full database backup before clicking one of the links.', 'qtranslate' ) ?>
                <br/><br/>
                <label for="qtranxs_convert_database_none"><input type="radio" name="convert_database"
                                                                  id="qtranxs_convert_database_none" value="none"
                                                                  checked/>&nbsp;<?php _e( 'Do not convert database', 'qtranslate' ) ?>
                </label><br/><br/>
                <label for="qtranxs_convert_database_to_b_only"><input type="radio" name="convert_database"
                                                                       id="qtranxs_convert_database_to_b_only"
                                                                       value="b_only"/>&nbsp;<?php echo __( 'Convert database to the "square bracket only" style.', 'qtranslate' ) ?>
                    <br/>
                    <small><?php printf( __( 'The square bracket language tag %s only will be used as opposite to dual-tag (%s and %s) %s legacy database format. All string options and standard post and page fields will be uniformly encoded like %s.', 'qtranslate' ), '[:]', esc_html( '<!--:-->' ), '[:]', 'qTranslate', '"[:en]English[:de]Deutsch[:]"' ) ?></small>
                </label><br/><br/>
                <label for="qtranxs_convert_database_to_c_dual"><input type="radio" name="convert_database"
                                                                       id="qtranxs_convert_database_to_c_dual"
                                                                       value="c_dual"/>&nbsp;<?php echo __( 'Convert database back to the legacy "dual language tag" style.', 'qtranslate' ) ?>
                    <br/>
                    <small><?php _e( 'Note, that only string options and standard post and page fields are affected.', 'qtranslate' ) ?></small>
                </label><br/><br/>
                <label for="qtranxs_db_clean_terms"><input type="radio" name="convert_database"
                                                           id="qtranxs_db_clean_terms"
                                                           value="db_clean_terms"/>&nbsp;<?php echo __( 'Clean Legacy Term Names', 'qtranslate' ) ?>
                    <br/>
                    <small><?php _e( 'Clean the inconsistencies of the q-original way to store term names. Translations for some tags, categories or other terms may get lost and may need to be re-entered after this operation. The term names should stay consistent in the future.', 'qtranslate' ) ?></small>
                </label><br/><br/>
                <label for="qtranxs_db_split"><input type="radio" name="convert_database" id="qtranxs_db_split"
                                                     value="db_split"/>&nbsp;<?php echo __( 'Split database file by language.', 'qtranslate' ) ?>
                    &nbsp;
                    <?php echo sprintf( __( 'Provide full file path to the input multilingual %s database file (the resulting %s files, named with language-based suffix, are saved in the same folder, where the input file is):', 'qtranslate' ), '.sql', '.sql' ) ?>
                </label><br/><input type="text" class="widefat" name="db_file" id="qtranxs_db_file"
                                    value="<?php if ( ! empty( $q_config['db_file'] ) ) {
                                        echo $q_config['db_file'];
                                    } ?>"/><br/><br/>
                <?php echo __( 'Specify which languages to keep in the main output file. Provide a comma-separated list of two-letter codes of languages to keep. If left empty, the database is split by language into a set of language-tags-free database files.', 'qtranslate' ) ?>
                <br/><input type="text" class="widefat" name="db_langs" id="qtranxs_db_langs"
                            value="<?php if ( ! empty( $q_config['db_langs'] ) ) {
                                echo $q_config['db_langs'];
                            } ?>"/><br/><br/>
                <small><?php echo sprintf( __( 'In order to remove one or more languages from entire database, you may dump the database into a %s file, then run this procedure and upload one of the new clean %s files back to the server. A separate clean database will also be saved fir each excluded language.', 'qtranslate' ), '.sql', '.sql' )//.sprintf(__('%sRead more%s.', 'qtranslate'), '&nbsp;<a href="">', '</a>')
                    ?></small>
            </td>
        </tr>
        <?php qtranxf_add_row_migrate( 'qTranslate', 'qtranslate', array( 'compatible' => true ) ) ?>
        <?php qtranxf_add_row_migrate( 'mqTranslate', 'mqtranslate' ) ?>
        <?php qtranxf_add_row_migrate( 'qTranslate Plus', 'qtranslate-xp' ) ?>
        <?php qtranxf_add_row_migrate( 'zTranslate', 'ztranslate', array( 'compatible' => true ) ) ?>
        <?php qtranxf_add_row_migrate( 'WPML Multilingual CMS', 'sitepress-multilingual-cms', array(
            'href' => 'https://wpml.org',
            'text' => sprintf( __( 'Use plugin %s to import data.', 'qtranslate' ), '<a href="https://wordpress.org/plugins/w2q-wpml-to-qtranslate/" target="_blank">W2Q: WPML to qTranslate</a>' )
        ) ) ?>
        <?php do_action( 'qtranslate_add_row_migrate' ) ?>
        <?php if ( QTX_Module_Loader::is_module_active( 'slugs' ) ): ?>
            <tr id="qtranslate-import-slugs">
                <th scope="row"><?php _e( 'Import from slugs', 'qtranslate' ) ?></th>
                <td>
                    <label for="qtranslate_import_slugs">
                        <input type="checkbox" name="qtranslate_import_slugs"
                               id="qtranslate_import_slugs"
                               value="1"
                               onclick="let x=jQuery('#qtranslate_import_slugs_confirm'); x.prop('disabled', !jQuery(this).prop('checked')); x.prop('checked', false);"/>
                        <?php _e( 'Import options, post and term meta from legacy slugs (QTS).', 'qtranslate' ); ?>
                    </label>
                    <br/>
                    <label for="qtranslate_import_slugs_confirm">
                        <input type="checkbox"
                               name="qtranslate_import_slugs_confirm"
                               id="qtranslate_import_slugs_confirm"
                               value="1" <?php disabled( true ) ?> /> <?php _e( "Confirm import in database. Leave unchecked for a dry-run mode without change saved in database.", 'qtranslate' ) ?>
                    </label>
                </td>
            </tr>
        <?php endif ?>
        <tr>
            <th scope="row"><?php _e( 'Reset qTranslate', 'qtranslate' ) ?></th>
            <td>
                <label for="qtranslate_reset"><input type="checkbox" name="qtranslate_reset" id="qtranslate_reset"
                                                     value="1"/> <?php _e( 'Check this box and click Save Changes to reset all qTranslate settings.', 'qtranslate' ) ?>
                </label>
                <br/>
                <label for="qtranslate_reset2"><input type="checkbox" name="qtranslate_reset2" id="qtranslate_reset2"
                                                      value="1"/> <?php _e( 'Yes, I really want to reset qTranslate.', 'qtranslate' ) ?>
                </label>
                <br/>
                <label for="qtranslate_reset3"><input type="checkbox" name="qtranslate_reset3" id="qtranslate_reset3"
                                                      value="1"/> <?php _e( 'Also delete Translations for Categories/Tags/Link Categories.', 'qtranslate' ) ?>
                </label>
                <br/>
                <small><?php _e( 'If something isn\'t working correctly, you can always try to reset all qTranslate settings. A Reset won\'t delete any posts but will remove all settings (including all languages added).', 'qtranslate' ) ?></small>
                <br/>
                <label for="qtranslate_reset_admin_notices"><input type="checkbox" name="qtranslate_reset_admin_notices"
                                                                   id="qtranslate_reset_admin_notices"
                                                                   value="1"/> <?php _e( 'Reset admin notices.', 'qtranslate' ) ?>
                </label>
                <br/>
                <small><?php _e( 'All previously dismissed admin notices related to this plugin will show up again on next refresh of admin pages.', 'qtranslate' ) ?></small>
            </td>
        </tr>
    </table>
    <?php
    QTX_Admin_Settings::close_section( 'import' );
}

add_action( 'qtranslate_configuration', 'qtranxf_admin_section_import_export', 9 );
