<?php
/**
 * Plugin Name: Cheetah Express Zimbabwe 
 * Plugin URI: https://cheetahexpressgroup.com
 * Description: Automated delivery orders for Cheetah Express Zimbabwe
 * Version: 1.0.0
 * Author: Ore and Tar
 * Author URI: https://oreandtar.co.zw
 * Domain Path: /lang
 * Text Domain: cheetahexp-shipping
 */
 
if ( ! defined( 'WPINC' ) ) {
    die;
}
 
/*
 * Check if WooCommerce is active
 */
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
 
    function Cheetah_Exp_Shipping_Method() {
        if ( ! class_exists( 'Cheetah_Exp_Shipping_Method' ) ) {
            class Cheetah_Exp_Shipping_Method extends WC_Shipping_Method {
                /**
                 * Constructor for shipping class
                 *
                 * @access public
                 * @return void
                 */
                public function __construct() {
                    $this->id                 = 'cheetahexp'; 
                    $this->method_title       = __( 'Cheetah Express Shipping Method', 'cheetahexp' );  
                    $this->method_description = __( 'Shipping Method for Cheetah Express', 'cheetahexp' ); 
 
                    // Availability & Countries
                    $this->availability = 'including';
                    $this->countries = array('ZW');
 
                    $this->init();
 
                    $this->enabled = isset( $this->settings['enabled'] ) ? $this->settings['enabled'] : 'yes';
                    $this->title = isset( $this->settings['title'] ) ? $this->settings['title'] : __( 'Cheetah Express Shipping', 'cheetahexp' );
                }
 
                /**
                 * Init your settings
                 *
                 * @access public
                 * @return void
                 */
                function init() {
                    // Load the settings API
                    $this->init_form_fields(); 
                    $this->init_settings(); 
 
                    // Save settings in admin if you have any defined
                    add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
                }
 
                /**
                 * Define settings field for this shipping
                 * @return void 
                 */
                function init_form_fields() { 
 
                    $this->form_fields = array(
 
                     'enabled' => array(
                          'title' => __( 'Enable', 'cheetahexp' ),
                          'type' => 'checkbox',
                          'description' => __( 'Enable this shipping method', 'cheetahexp' ),
                          'default' => 'yes'
                          ),
 
                     'title' => array(
                        'title' => __( 'Title', 'cheetahexp' ),
                          'type' => 'text',
                          'description' => __( 'Title to be display on site', 'cheetahexp' ),
                          'default' => __( 'Cheetah Express Shipping', 'cheetahexp' )
                          ),
 
                     'weight' => array(
                        'title' => __( 'Weight (kg)', 'cheetahexp' ),
                          'type' => 'number',
                          'description' => __( 'Maximum allowed weight', 'cheetahexp' ),
                          'default' => 100
                          ),
 
                     );
 
                }
 
                /**
                 * Calculate shipping cost
                 *
                 * @access public
                 * @param mixed $package
                 * @return void
                 */
                public function calculate_shipping( $package ) {
                    
                    $weight = 0;
                    $cost = 0;
                    $country = $package["destination"]["country"];
                    $city = $package["destination"]["city"];
 
                    foreach ( $package['contents'] as $item_id => $values ) 
                    { 
                        $_product = $values['data']; 
                        $weight = $weight + $_product->get_weight() * $values['quantity']; 
                    }
 
                    $weight = wc_get_weight( $weight, 'kg' );
                    $citySet = false;

                    if ( $city == 'Harare' ) {
                        $citySet = true;
                        $cityRate = 1;
                    } elseif ( $city == 'Bulawayo' ) {
                        $citySet = true;                        
                        $cityRate = 2;
                    } elseif ( $city == 'Gweru' ) {
                        $citySet = true;                        
                        $cityRate = 3;                        
                    }

                    if ( $citySet ) {

                        $cost = $cityRate * $weight;
                        $rate = array(
                            'id' => $this->id,
                            'label' => $this->title,
                            'cost' => $cost
                        );
                        $this->add_rate( $rate );

                    }
                    
                }
            }
        }
    }
 
    add_action( 'woocommerce_shipping_init', 'Cheetah_Exp_Shipping_Method' );
 
    function add_Cheetah_Exp_Shipping_Method( $methods ) {
        $methods[] = 'Cheetah_Exp_Shipping_Method';
        return $methods;
    }
 
    add_filter( 'woocommerce_shipping_methods', 'add_Cheetah_Exp_Shipping_Method' );
 
    function cheetahexp_validate_order( $posted )   {
 
        $packages = WC()->shipping->get_packages();
 
        $chosen_methods = WC()->session->get( 'chosen_shipping_methods' );
         
        if( is_array( $chosen_methods ) && in_array( 'cheetahexp', $chosen_methods ) ) {
             
            foreach ( $packages as $i => $package ) {
 
                if ( $chosen_methods[ $i ] != "cheetahexp" ) {
                             
                    continue;
                             
                }

                // Check if city exists

                $city = $package["destination"]["city"];

                if ( $city == null ) {

                        $message = "Please enter your city to get a shipping estimate";                             
                        $messageType = "error";

                        if( ! wc_has_notice( $message, $messageType ) ) {
                        
                            wc_add_notice( $message, $messageType );
                    
                        }                    
                }

                // Check for maximum weight
 
                $Cheetah_Exp_Shipping_Method = new Cheetah_Exp_Shipping_Method();
                $weightLimit = (int) $Cheetah_Exp_Shipping_Method->settings['weight'];
                $weight = 0;
 
                foreach ( $package['contents'] as $item_id => $values ) 
                { 
                    $_product = $values['data']; 
                    $weight = $weight + $_product->get_weight() * $values['quantity']; 
                }
 
                $weight = wc_get_weight( $weight, 'kg' );
                
                if( $weight > $weightLimit ) {
 
                        $message = sprintf( __( 'Sorry, %d kg exceeds the maximum weight of %d kg for %s', 'tutsplus' ), $weight, $weightLimit, $Cheetah_Exp_Shipping_Method->title );
                             
                        $messageType = "error";
 
                        if( ! wc_has_notice( $message, $messageType ) ) {
                         
                            wc_add_notice( $message, $messageType );
                      
                        }
                }
            }       
        } 
    }

    function cheetah_exp_translation( $translation, $text, $domain ) {

        $trans_text = array(
            'Enter your address to view shipping options.' => 'Enter your address to view shipping options. Please note that shipping is only available in Harare, Bulawayo and Gweru.',
        );

        if( array_key_exists( $translation, $trans_text ) ) {
            $translation = $trans_text[$translation];
        }
        return $translation;
    }
    
    function update_on_city_change( $fields ) {
        $fields['shipping']['shipping_city']['class'][] = 'update_totals_on_change';
        return $fields;
    }

    add_filter('gettext', 'cheetah_exp_translation', 20, 3);
    add_filter('ngettext', 'cheetah_exp_translation', 20, 3);
    add_filter( 'woocommerce_checkout_fields' , 'update_on_city_change' );
 
    add_action( 'woocommerce_review_order_before_cart_contents', 'cheetahexp_validate_order' , 10 );
    add_action( 'woocommerce_after_checkout_validation', 'cheetahexp_validate_order' , 10 );
}