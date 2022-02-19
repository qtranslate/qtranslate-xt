<?php

/**
 * QtranslateSlug_Widget class
 */
class QtranslateSlugWidget extends WP_Widget {

    function __construct() {
        $widget_ops = array(
            'classname'   => 'qts_widget',
            'description' => __( 'Allows your visitors to choose a Language.', 'qts' )
        );
        parent::__construct( 'qtranslateslug', __( 'Language selector (QTS)', 'qts' ), $widget_ops );
    }

    function widget( $args, $instance ) {
        echo $args['before_widget'];
        $title      = empty( $instance['title'] ) ? __( 'Language', 'qts' ) : apply_filters( 'widget_title', $instance['title'] );
        $hide_title = empty( $instance['hide-title'] ) ? false : 'on';
        $type       = $instance['type'];
        $short_text = $instance['short_text'] == 'on';

        if ( $type != 'text' && $type != 'image' && $type != 'both' && $type != 'dropdown' ) {
            $type = 'text';
        }

        if ( $hide_title != 'on' ) {
            echo $args['before_title'] . $title . $args['after_title'];
        }

        qts_language_menu( $type, array( 'id' => $this->id, 'short' => $short_text ) );

        echo $args['after_widget'];
    }

    function update( $new_instance, $old_instance ) {
        $instance               = $old_instance;
        $instance['title']      = $new_instance['title'];
        $instance['hide-title'] = $new_instance['hide-title'];
        $instance['type']       = $new_instance['type'];
        $instance['short_text'] = $new_instance['short_text'];

        return $instance;
    }

    function form( $instance ) {
        $instance   = wp_parse_args( (array) $instance, array(
            'title'      => '',
            'hide-title' => false,
            'type'       => 'text'
        ) );
        $title      = $instance['title'];
        $hide_title = $instance['hide-title'];
        $type       = $instance['type'];
        $short_text = isset( $instance['short_text'] ) ? $instance['short_text'] : '';
        ?>
        <p><label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'qts' ); ?> <input
                        class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>"
                        name="<?php echo $this->get_field_name( 'title' ); ?>" type="text"
                        value="<?php echo esc_attr( $title ); ?>"/></label></p>
        <p><label for="<?php echo $this->get_field_id( 'hide-title' ); ?>"><?php _e( 'Hide Title:', 'qts' ); ?> <input
                        type="checkbox" id="<?php echo $this->get_field_id( 'hide-title' ); ?>"
                        name="<?php echo $this->get_field_name( 'hide-title' ); ?>" <?php echo ( $hide_title == 'on' ) ? 'checked="checked"' : ''; ?>/></label>
        </p>
        <p><?php _e( 'Display:', 'qts' ); ?></p>
        <p><label for="<?php echo $this->get_field_id( 'type' ); ?>1"><input type="radio"
                                                                             name="<?php echo $this->get_field_name( 'type' ); ?>"
                                                                             id="<?php echo $this->get_field_id( 'type' ); ?>1"
                                                                             value="text"<?php echo ( $type == 'text' ) ? ' checked="checked"' : '' ?>/> <?php _e( 'Text only', 'qts' ); ?>
            </label></p>
        <p><label for="<?php echo $this->get_field_id( 'type' ); ?>2"><input type="radio"
                                                                             name="<?php echo $this->get_field_name( 'type' ); ?>"
                                                                             id="<?php echo $this->get_field_id( 'type' ); ?>2"
                                                                             value="image"<?php echo ( $type == 'image' ) ? ' checked="checked"' : '' ?>/> <?php _e( 'Image only', 'qts' ); ?>
            </label></p>
        <p><label for="<?php echo $this->get_field_id( 'type' ); ?>3"><input type="radio"
                                                                             name="<?php echo $this->get_field_name( 'type' ); ?>"
                                                                             id="<?php echo $this->get_field_id( 'type' ); ?>3"
                                                                             value="both"<?php echo ( $type == 'both' ) ? ' checked="checked"' : '' ?>/> <?php _e( 'Text and Image', 'qts' ); ?>
            </label></p>
        <p><label for="<?php echo $this->get_field_id( 'type' ); ?>4"><input type="radio"
                                                                             name="<?php echo $this->get_field_name( 'type' ); ?>"
                                                                             id="<?php echo $this->get_field_id( 'type' ); ?>4"
                                                                             value="dropdown"<?php echo ( $type == 'dropdown' ) ? ' checked="checked"' : '' ?>/> <?php _e( 'Dropdown Box', 'qts' ); ?>
            </label></p>
        <p>
            <label for="<?php echo $this->get_field_id( 'short_text' ); ?>"><?php _e( 'Show short name (en):', 'qts' ); ?>
                <input type="checkbox" id="<?php echo $this->get_field_id( 'short_text' ); ?>"
                       name="<?php echo $this->get_field_name( 'short_text' ); ?>" <?php checked( $short_text, 'on' ) ?>/></label>
        </p>

        <?php
    }
}
