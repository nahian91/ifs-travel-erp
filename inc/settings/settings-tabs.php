<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'ifs_terp_settings_render_tabs' ) ) {
    /**
     * Sub-Navigation Bar for System Settings & Management Module
     *
     * @param string $active_tab Current active tab slug
     */
    function ifs_terp_settings_render_tabs( $active_tab = 'general' ) {
        $base_url = admin_url( 'admin.php?page=ifs_travel_erp&tab=settings' );

        $nav_items = array(
            'general'     => array(
                'label' => __( 'General', 'ifs-travel-erp' ),
                'icon'  => 'dashicons-admin-generic',
                'url'   => $base_url . '&sub=general',
            ),
            'visa_req'    => array(
                'label' => __( 'Visa Requirements', 'ifs-travel-erp' ),
                'icon'  => 'dashicons-id-alt',
                'url'   => $base_url . '&sub=visa_req&action_sub=all',
            ),
            'hajj_pkg'    => array(
                'label' => __( 'Hajj & Umrah', 'ifs-travel-erp' ),
                'icon'  => 'dashicons-palmtree',
                'url'   => $base_url . '&sub=hajj_pkg&action_sub=all',
            ),
            'tour_pkg'    => array(
                'label' => __( 'Tours', 'ifs-travel-erp' ),
                'icon'  => 'dashicons-location',
                'url'   => $base_url . '&sub=tour_pkg&action_sub=all',
            ),
            'hotels_prop' => array(
                'label' => __( 'Hotel Properties', 'ifs-travel-erp' ),
                'icon'  => 'dashicons-building',
                'url'   => $base_url . '&sub=hotels_prop&hotel_sub=all',
            ),
        );
        ?>
        <div class="ifs-module-header">
            <div class="ifs-module-title-wrap">
                <div class="ifs-module-icon">
                    <span class="dashicons dashicons-admin-generic"></span>
                </div>
                <div>
                    <h2 class="ifs-module-title"><?php esc_html_e( 'System Settings & Config', 'ifs-travel-erp' ); ?></h2>
                    <p class="ifs-module-subtitle"><?php esc_html_e( 'Manage agency profiles, global visa requirements, and package templates', 'ifs-travel-erp' ); ?></p>
                </div>
            </div>

            <nav class="ifs-sub-nav">
                <?php foreach ( $nav_items as $key => $item ) : 
                    $is_active = ( $active_tab === $key );
                    ?>
                    <a href="<?php echo esc_url( $item['url'] ); ?>" 
                       class="ifs-sub-nav-item <?php echo $is_active ? 'active' : ''; ?>">
                        <span class="dashicons <?php echo esc_attr( $item['icon'] ); ?>"></span>
                        <span><?php echo esc_html( $item['label'] ); ?></span>
                    </a>
                <?php endforeach; ?>
            </nav>
        </div>

        <style>
            .ifs-module-header {
                background: #ffffff;
                border: 1px solid #e2e8f0;
                border-radius: 12px;
                padding: 18px 22px;
                margin-bottom: 22px;
                display: flex;
                justify-content: space-between;
                align-items: center;
                flex-wrap: wrap;
                gap: 16px;
            }
            .ifs-module-title-wrap {
                display: flex;
                align-items: center;
                gap: 14px;
            }
            .ifs-module-icon {
                width: 44px;
                height: 44px;
                border-radius: 10px;
                background: #003376;
                display: flex;
                align-items: center;
                justify-content: center;
                color: #ffffff;
                flex-shrink: 0;
            }
            .ifs-module-icon .dashicons {
                font-size: 22px;
                width: 22px;
                height: 22px;
            }
            .ifs-module-title {
                margin: 0;
                font-size: 18px;
                font-weight: 800;
                color: #0f172a;
                line-height: 1.2;
            }
            .ifs-module-subtitle {
                margin: 2px 0 0 0;
                font-size: 13px;
                color: #64748b;
            }
            .ifs-sub-nav {
                background: #f8fafc;
                border: 1px solid #e2e8f0;
                border-radius: 8px;
                padding: 4px;
                display: inline-flex;
                gap: 4px;
                flex-wrap: wrap;
            }
            .ifs-sub-nav-item {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                padding: 7px 14px;
                font-size: 13px;
                font-weight: 600;
                color: #475569;
                text-decoration: none;
                border-radius: 6px;
                transition: all 0.15s ease;
            }
            .ifs-sub-nav-item .dashicons {
                font-size: 16px;
                width: 16px;
                height: 16px;
                color: #64748b;
            }
            .ifs-sub-nav-item:hover {
                color: #0f172a;
                background: #f1f5f9;
            }
            .ifs-sub-nav-item.active {
                background: #003376;
                color: #ffffff;
            }
            .ifs-sub-nav-item.active .dashicons {
                color: #ffffff;
            }
        </style>
        <?php
    }
}