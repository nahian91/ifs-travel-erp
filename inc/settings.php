<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// 1. Core navigation bar
if ( defined( 'ITERP_PATH' ) && file_exists( ITERP_PATH . 'inc/settings/settings-tabs.php' ) ) {
    require_once ITERP_PATH . 'inc/settings/settings-tabs.php';
}

// 2. Tab sub-modules with clean array loop & file_exists guards
$settings_sub_files = array(
    'general.php',
    'visa-requirements.php',
    'hajj-packages.php',
    'tour-packages.php',
    'hotel-properties.php',
);

if ( defined( 'ITERP_PATH' ) ) {
    foreach ( $settings_sub_files as $settings_file ) {
        $file_path = ITERP_PATH . 'inc/settings/' . $settings_file;
        if ( file_exists( $file_path ) ) {
            require_once $file_path;
        }
    }
}

/**
 * Main Settings Master Router Engine
 */
if ( ! function_exists( 'ifs_terp_settings_tab' ) ) {
    function ifs_terp_settings_tab() {
        $sub_tab = isset( $_GET['sub'] ) ? sanitize_key( wp_unslash( $_GET['sub'] ) ) : 'general';
        ?>
        <div class="wrap ifs-settings-workspace" style="max-width: 1400px; margin: 20px auto; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
            <?php 
            if ( function_exists( 'ifs_terp_settings_render_tabs' ) ) {
                ifs_terp_settings_render_tabs( $sub_tab );
            }
            ?>

            <div class="ifs-settings-content-area">
                <?php
                switch ( $sub_tab ) {
                    case 'general':
                        if ( function_exists( 'ifs_terp_settings_general_panel' ) ) {
                            ifs_terp_settings_general_panel();
                        } else {
                            echo '<div class="notice notice-error"><p>' . esc_html__( 'General settings panel not loaded.', 'ifs-travel-erp' ) . '</p></div>';
                        }
                        break;
                    case 'visa_req':
                        if ( function_exists( 'ifs_terp_settings_visa_req_router' ) ) {
                            ifs_terp_settings_visa_req_router();
                        } else {
                            echo '<div class="notice notice-error"><p>' . esc_html__( 'Visa requirements module not loaded.', 'ifs-travel-erp' ) . '</p></div>';
                        }
                        break;
                    case 'hajj_pkg':
                        if ( function_exists( 'ifs_terp_settings_hajj_pkg_router' ) ) {
                            ifs_terp_settings_hajj_pkg_router();
                        } else {
                            echo '<div class="notice notice-error"><p>' . esc_html__( 'Hajj packages module not loaded.', 'ifs-travel-erp' ) . '</p></div>';
                        }
                        break;
                    case 'tour_pkg':
                        if ( function_exists( 'ifs_terp_tour_plans_router' ) ) {
                            ifs_terp_tour_plans_router();
                        } else {
                            echo '<div class="notice notice-error"><p>' . esc_html__( 'Tour plans module not loaded.', 'ifs-travel-erp' ) . '</p></div>';
                        }
                        break;
                    case 'hotels_prop':
                        if ( function_exists( 'ifs_terp_settings_hotels_router' ) ) {
                            ifs_terp_settings_hotels_router();
                        } else {
                            echo '<div class="notice notice-error"><p>' . esc_html__( 'Hotel properties module not loaded.', 'ifs-travel-erp' ) . '</p></div>';
                        }
                        break;
                    default:
                        if ( function_exists( 'ifs_terp_settings_general_panel' ) ) {
                            ifs_terp_settings_general_panel();
                        } else {
                            echo '<div class="notice notice-error"><p>' . esc_html__( 'General settings panel not loaded.', 'ifs-travel-erp' ) . '</p></div>';
                        }
                        break;
                }
                ?>
            </div>
        </div>
        <?php
    }
}