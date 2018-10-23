<?php
/**
 * Support for GravityForms
 *
 * @version 1.1.2
 * @author Michel Weimerskirch
 * @link http://michel.weimerskirch.net
 */

class qTranslateSupportForGravityforms {
  public function __construct() {
    add_filter( 'gform_pre_render', array( $this, 'gform_pre_render' ) );
    add_filter( 'gform_pre_submission_filter', array( $this, 'gform_pre_render' ) );
    add_filter( 'gform_polls_form_pre_results', array( $this, 'gform_pre_render' ) );
    add_filter( 'gform_form_tag', array( $this, 'gform_form_tag' ) );
    add_filter( "gform_confirmation", array( $this, "gform_confirmation" ), 10, 4 );
    add_filter( "gform_pre_send_email", array( $this, "gform_pre_send_email" ) );
  }

  public function gform_pre_render( $form ) {
    if ( ! $this->isEnabled() ) {
      return $form;
    }
    if ( isset( $form['button']['text'] ) ) {
      $form['button']['text'] = $this->translate( $form['button']['text'] );
    }
    if ( isset( $form['title'] ) ) {
      $form['title'] = $this->translate( $form['title'] );
    }
    if ( isset( $form['description'] ) ) {
      $form['description'] = $this->translate( $form['description'] );
    }
    if ( isset( $form['confirmation']['message'] ) ) {
      $form['confirmation']['message'] = $this->translate( $form['confirmation']['message'] );
    }
    if ( isset( $form['fields'] ) ) {
      foreach ( $form['fields'] as $id => $field ) {
        $form['fields'][ $id ]->label              = $this->translate( $form['fields'][ $id ]->label );
        $form['fields'][ $id ]->placeholder        = $this->translate( $form['fields'][ $id ]->placeholder );
        $form['fields'][ $id ]->content            = $this->translate( $form['fields'][ $id ]->content );
        $form['fields'][ $id ]->description        = $this->translate( $form['fields'][ $id ]->description );
        $form['fields'][ $id ]->defaultValue       = $this->translate( $form['fields'][ $id ]->defaultValue );
        $form['fields'][ $id ]->errorMessage       = $this->translate( $form['fields'][ $id ]->errorMessage );
        $form['fields'][ $id ]->validation_message = $this->translate( $form['fields'][ $id ]->validation_message );
        $form['fields'][ $id ]->choices            = $this->translate( $form['fields'][ $id ]->choices );
        if ( isset( $form['fields'][ $id ]->conditionalLogic['rules'] ) ) {
          foreach ( $form['fields'][ $id ]->conditionalLogic['rules'] as $value => $key ) {
            foreach ( $key as $value2 => $key2 ) {
              $form['fields'][ $id ]->conditionalLogic['rules'][ $value ][ $value2 ] = $this->translate( $key2 );
            }
          }
        }
        // Translate sub-labels
        if ( isset( $form['fields'][ $id ]->inputs ) && $form['fields'][ $id ]->inputs ) {
          foreach ( $form['fields'][ $id ]->inputs as $input_id => $input ) {
            if ( isset( $input['customLabel'] ) ) {
              $form['fields'][ $id ]->inputs[ $input_id ]['customLabel'] = $this->translate( $input['customLabel'] );
            }
            if ( isset( $input['placeholder'] ) ) {
              $form['fields'][ $id ]->inputs[ $input_id ]['placeholder'] = $this->translate( $input['placeholder'] );
            }
          }
        }
        // Support for the poll add-on
        if ( isset( $form['fields'][ $id ]->choices ) && $form['fields'][ $id ]->choices ) {
          foreach ( $form['fields'][ $id ]->choices as $value => $key ) {
            $form['fields'][ $id ]['choices'][ $value ]['text'] = $this->translate( $key['text'] );
          }
        }
        if ( isset( $form['fields'][ $id ]->nextButton ) && $form['fields'][ $id ]->nextButton ) {
          $form['fields'][ $id ]->nextButton['text'] = $this->translate( $form['fields'][ $id ]->nextButton['text'] );
        }
        if ( isset( $form['fields'][ $id ]->previousButton ) && $form['fields'][ $id ]->previousButton ) {
          $form['fields'][ $id ]->previousButton['text'] = $this->translate( $form['fields'][ $id ]->previousButton['text'] );
        }
      }
    }
    if ( isset( $form['lastPageButton'] ) ) {
      $form['lastPageButton'] = $this->translate( $form['lastPageButton'] );
    }
    if ( isset( $form['pagination'] ) ) {
      foreach ( $form['pagination']['pages'] as $id => $title ) {
        $form['pagination']['pages'][ $id ] = $this->translate( $form['pagination']['pages'][ $id ] );
      }
    }

    return $form;
  }

  private function isEnabled() {
    return ( function_exists( 'qtrans_useCurrentLanguageIfNotFoundUseDefaultLanguage' ) || function_exists( 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage' ) );
  }

  private function translate( $text ) {
    if ( function_exists( 'qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage' ) ) {
      return qtranxf_useCurrentLanguageIfNotFoundUseDefaultLanguage( $text );
    } else {
      return qtrans_useCurrentLanguageIfNotFoundUseDefaultLanguage( $text );
    }
  }

  public function gform_form_action_attribute( $matches ) {
    global $q_config;

    return 'action="' . $this->convertURL( $matches[1], $q_config['language'] ) . '"';
  }

  private function convertURL( $url, $lang ) {
    if ( function_exists( 'qtranxf_convertURL' ) ) {
      return qtranxf_convertURL( $url, $lang );
    } else {
      return qtrans_convertURL( $url, $lang );
    }
  }

  public function gform_form_tag( $tag ) {
    if ( ! $this->isEnabled() ) {
      return $tag;
    }
    $tag = preg_replace_callback( "|action='([^']+)'|", array( &$this, 'gform_form_action_attribute' ), $tag );

    return $tag;
  }

  public function gform_confirmation( $confirmation, $form, $lead, $ajax ) {
    if ( ! $this->isEnabled() ) {
      return $confirmation;
    }
    $confirmation = $this->translate( $confirmation );

    return $confirmation;
  }

  public function gform_pre_send_email( $email ) {
    if ( ! $this->isEnabled() ) {
      return $email;
    }
    $email["message"] = $this->translate( $email["message"] );
    $email["subject"] = $this->translate( $email["subject"] );

    return $email;
  }
}

new qTranslateSupportForGravityforms();
