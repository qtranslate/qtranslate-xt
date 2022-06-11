<?php

require_once ACF_QTRANSLATE_PLUGIN_DIR . 'src/acf_interface.php';

class QTX_Module_Acf_V5 implements QTX_Module_Acf_Interface {

    /**
     * The plugin instance
     * @var QTX_Module_Acf_Plugin
     */
    protected $plugin;

    /**
     * Constructor
     *
     * @param QTX_Module_Acf_Plugin $plugin
     */
    public function __construct( $plugin ) {
        $this->plugin = $plugin;

        // a higher priority is needed for custom admin options (ACF PRO)
        add_filter( 'acf/format_value', array( $this, 'format_value' ), 5 );
        add_action( 'acf/include_fields', array( $this, 'include_fields' ), 5 );
    }

    /**
     * Load javascript and stylesheets on admin pages
     */
    public function include_fields() {
        require_once ACF_QTRANSLATE_PLUGIN_DIR . 'src/acf_5/fields/file.php';
        require_once ACF_QTRANSLATE_PLUGIN_DIR . 'src/acf_5/fields/image.php';
        require_once ACF_QTRANSLATE_PLUGIN_DIR . 'src/acf_5/fields/post_object.php';
        require_once ACF_QTRANSLATE_PLUGIN_DIR . 'src/acf_5/fields/text.php';
        require_once ACF_QTRANSLATE_PLUGIN_DIR . 'src/acf_5/fields/textarea.php';
        require_once ACF_QTRANSLATE_PLUGIN_DIR . 'src/acf_5/fields/url.php';
        require_once ACF_QTRANSLATE_PLUGIN_DIR . 'src/acf_5/fields/wysiwyg.php';

        acf()->fields->register_field_type( new QTX_Module_Acf_V5_File( $this->plugin ) );
        acf()->fields->register_field_type( new QTX_Module_Acf_V5_Image( $this->plugin ) );
        acf()->fields->register_field_type( new QTX_Module_Acf_V5_Post_Object( $this->plugin ) );
        acf()->fields->register_field_type( new QTX_Module_Acf_V5_Text( $this->plugin ) );
        acf()->fields->register_field_type( new QTX_Module_Acf_V5_Textarea( $this->plugin ) );
        acf()->fields->register_field_type( new QTX_Module_Acf_V5_Url( $this->plugin ) );
        acf()->fields->register_field_type( new QTX_Module_Acf_V5_Wysiwyg( $this->plugin ) );
    }

    /**
     * Hook/override ACF format_value
     *
     * This filter is applied to the $value after it is loaded from the db and
     * before it is returned to the template via functions such as get_field().
     *
     * @param $value
     *
     * @return array|mixed|string|void
     */
    public function format_value( $value ) {
        if ( is_string( $value ) ) {
            $value = qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage( $value );
            $value = maybe_unserialize( $value );
        }

        return $value;
    }

    /**
     * Get the visible ACF fields
     *
     * @param null $widget_id
     *
     * @return array
     */
    public function get_visible_acf_fields( $widget_id = null ) {
        global $wp_registered_widgets;

        $visible_fields = array();

        // build field group filters required for current screen
        $filter = $this->get_acf_field_group_filters();
        if ( count( $filter ) === 0 ) {
            return $visible_fields;
        }

        // widgets need some special handling since they
        // require multiple acf_get_field_group_visibility()
        // calls in order to return all the visible fields
        if ( acf_is_screen( 'widgets' ) || acf_is_screen( 'customize' ) ) {
            if ( $widget_id ) {
                $filter['widget'] = _get_widget_id_base( $widget_id );
            } else {
                // process each widget form individually for any visible fields
                foreach ( $wp_registered_widgets as $widget ) {
                    $visible_fields += $this->get_visible_acf_fields( $widget['id'] );
                }

                return $visible_fields;
            }
        }

        $supported_field_types = array(
            'email',
            'text',
            'textarea',
            'repeater',
            'flexible_content',
            'qtranslate_file',
            'qtranslate_image',
            'qtranslate_post_object',
            'qtranslate_text',
            'qtranslate_textarea',
            'qtranslate_url',
            'qtranslate_wysiwyg',
        );

        foreach ( acf_get_field_groups( $filter ) as $field_group ) {
            $fields = acf_get_fields( $field_group );
            foreach ( $fields as $field ) {
                if ( in_array( $field['type'], $supported_field_types ) ) {
                    $visible_fields[] = array( 'id' => 'acf-' . $field['key'] );
                }
            }
        }

        return $visible_fields;
    }

    /**
     * Get field group filters based on active screen.
     */
    public function get_acf_field_group_filters() {
        global $post, $pagenow, $typenow, $plugin_page;

        $filter = array();
        if ( $pagenow === 'post.php' || $pagenow === 'post-new.php' ) {
            if ( $typenow !== 'acf' ) {
                $filter['post_id']   = $post->ID;
                $filter['post_type'] = $typenow;
            }
        } elseif ( $pagenow === 'admin.php' && isset( $plugin_page ) ) {
            if ( acf_get_options_page( $plugin_page ) ) {
                $filter['post_id'] = acf_get_valid_post_id( 'options' );
            }
        } elseif ( $pagenow === 'edit-tags.php' && isset( $_GET['taxonomy'] ) ) {
            $filter['taxonomy'] = filter_var( $_GET['taxonomy'], FILTER_SANITIZE_STRING );
        } elseif ( $pagenow === 'profile.php' ) {
            $filter['user_id']   = get_current_user_id();
            $filter['user_form'] = 'edit';
        } elseif ( $pagenow === 'user-edit.php' && isset( $_GET['user_id'] ) ) {
            $filter['user_id']   = filter_var( $_GET['user_id'], FILTER_SANITIZE_NUMBER_INT );
            $filter['user_form'] = 'edit';
        } elseif ( $pagenow === 'user-new.php' ) {
            $filter['user_id']   = 'new';
            $filter['user_form'] = 'edit';
        } elseif ( $pagenow === 'media.php' || $pagenow === 'upload.php' ) {
            $filter['attachment'] = 'All';
        } elseif ( acf_is_screen( 'widgets' ) || acf_is_screen( 'customize' ) ) {
            $filter['widget'] = 'all';
        }

        return $filter;
    }
}
