<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Sub-files include
require_once plugin_dir_path( __FILE__ ) . 'visa/visa-tabs.php';
require_once plugin_dir_path( __FILE__ ) . 'visa/visa-delete.php';
require_once plugin_dir_path( __FILE__ ) . 'visa/visa-list.php';
require_once plugin_dir_path( __FILE__ ) . 'visa/visa-add-edit.php';
require_once plugin_dir_path( __FILE__ ) . 'visa/visa-view.php';
require_once plugin_dir_path( __FILE__ ) . 'visa/visa-requirements.php';

/**
 * Visa Processing Module Router
 */
function ifs_terp_visa_tab() {
    $sub_action = isset( $_GET['sub'] ) ? sanitize_text_field( $_GET['sub'] ) : 'list';

    echo '<div class="ifs-terp-module-wrapper" style="padding: 20px; max-width: 1200px;">';
    
    // Sub-Navigation Tabs
    if ( function_exists( 'ifs_terp_visa_render_tabs' ) ) {
        ifs_terp_visa_render_tabs( $sub_action );
    }

    // Sub-Routing
    switch ( $sub_action ) {
        case 'add':
        case 'edit':
            if ( function_exists( 'ifs_terp_visa_add_edit_page' ) ) {
                ifs_terp_visa_add_edit_page();
            }
            break;

        case 'view':
            if ( function_exists( 'ifs_terp_visa_view_page' ) ) {
                ifs_terp_visa_view_page();
            }
            break;

        case 'requirements':
            if ( function_exists( 'ifs_terp_visa_requirements_page' ) ) {
                ifs_terp_visa_requirements_page();
            }
            break;

        case 'delete':
            if ( function_exists( 'ifs_terp_visa_delete_handler' ) ) {
                ifs_terp_visa_delete_handler();
            }
            break;

        case 'list':
        default:
            if ( function_exists( 'ifs_terp_visa_list_page' ) ) {
                ifs_terp_visa_list_page();
            }
            break;
    }

    echo '</div>';
}