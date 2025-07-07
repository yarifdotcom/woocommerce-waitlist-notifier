<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'WWN_Waitlist_Settings_Page' ) ) {

    class WWN_Waitlist_Settings_Page extends WC_Settings_Page {

        public function __construct() {
            $this->id    = 'wwn_waitlist';
            $this->label = __('Waitlist', 'woocommerce');
            parent::__construct();
        }

        public function get_settings() {
            $settings = array(
                array(
                    'title' => __('Waitlist Settings', 'woocommerce'),
                    'type'  => 'title',
                    'desc'  => 'Configure how waitlist notifications are handled.',
                    'id'    => 'wwn_waitlist_settings_title',
                ),
                array(
                    'title'    => __('Notification Email Address', 'woocommerce'),
                    'desc'     => __('The email address that will receive notifications when a customer joins the waitlist.', 'woocommerce'),
                    'id'       => 'wwn_notify_email',
                    'type'     => 'email',
                    'default'  => get_option('admin_email'),
                    'desc_tip' => true,
                ),
                array(
                    'type' => 'sectionend',
                    'id'   => 'wwn_waitlist_settings_end',
                ),
            );

            return apply_filters('woocommerce_get_settings_' . $this->id, $settings);
        }
    }
}
