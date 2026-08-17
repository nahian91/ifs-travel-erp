<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// Sub-files include
require_once plugin_dir_path( __FILE__ ) . 'customers/customer-tabs.php';
require_once plugin_dir_path( __FILE__ ) . 'customers/customer-delete.php';
require_once plugin_dir_path( __FILE__ ) . 'customers/customer-list.php';
require_once plugin_dir_path( __FILE__ ) . 'customers/customer-add-edit.php';
require_once plugin_dir_path( __FILE__ ) . 'customers/customer-view.php';

/**
 * Customers Tab Router
 */
function ifs_terp_customers_tab() {
    $sub_action = isset( $_GET['sub'] ) ? sanitize_text_field( $_GET['sub'] ) : 'list';

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
            }
            break;

        case 'view':
            if ( function_exists( 'ifs_terp_customer_view_page' ) ) {
                ifs_terp_customer_view_page();
            }
            break;

        case 'delete':
            if ( function_exists( 'ifs_terp_customer_delete_handler' ) ) {
                ifs_terp_customer_delete_handler();
            }
            break;

        case 'list':
        default:
            if ( function_exists( 'ifs_terp_customer_list_page' ) ) {
                ifs_terp_customer_list_page();
            }
            break;
    }

    echo '</div>';
}