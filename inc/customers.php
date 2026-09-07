<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// Sub-files include with file_exists guards
$customer_sub_files = array(
    'customer-tabs.php',
    'customer-delete.php',
    'customer-list.php',
    'customer-add-edit.php',
    'customer-view.php',
);

foreach ( $customer_sub_files as $customer_file ) {
    $file_path = plugin_dir_path( __FILE__ ) . 'customers/' . $customer_file;
    if ( file_exists( $file_path ) ) {
        require_once $file_path;
    }
}

/**
 * Customers Tab Router
 */
if ( ! function_exists( 'ifs_terp_customers_tab' ) ) {
    function ifs_terp_customers_tab() {
        $sub_action = isset( $_GET['sub'] ) ? sanitize_key( wp_unslash( $_GET['sub'] ) ) : 'list';

        echo '<div class="ifs-terp-module-wrapper" style="padding: 20px; max-width: 1200px;">';
        
        // Render Sub-Navigation Tabs
        if ( function_exists( 'ifs_terp_customer_render_tabs' ) ) {
            ifs_terp_customer_render_tabs( $sub_action );
        }

        // Load Specific Page Content
        switch ( $sub_action ) {
            case 'add':
            case 'edit':
                if ( function_exists( 'ifs_terp_customer_add_edit_page' ) ) {
                    ifs_terp_customer_add_edit_page();
                } else {
                    echo '<div class="notice notice-error"><p>' . esc_html__( 'Customer add/edit module not loaded.', 'ifs-travel-erp' ) . '</p></div>';
                }
                break;

            case 'view':
                if ( function_exists( 'ifs_terp_customer_view_page' ) ) {
                    ifs_terp_customer_view_page();
                } else {
                    echo '<div class="notice notice-error"><p>' . esc_html__( 'Customer view module not loaded.', 'ifs-travel-erp' ) . '</p></div>';
                }
                break;

            case 'delete':
                if ( function_exists( 'ifs_terp_customer_delete_handler' ) ) {
                    ifs_terp_customer_delete_handler();
                } else {
                    wp_die( esc_html__( 'Customer delete handler callback not available.', 'ifs-travel-erp' ) );
                }
                break;

            case 'list':
            default:
                if ( function_exists( 'ifs_terp_customer_list_page' ) ) {
                    ifs_terp_customer_list_page();
                } else {
                    echo '<div class="notice notice-error"><p>' . esc_html__( 'Customer list directory module not loaded.', 'ifs-travel-erp' ) . '</p></div>';
                }
                break;
        }

        echo '</div>';
    }
}