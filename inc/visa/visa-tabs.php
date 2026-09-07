<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Sub-Navigation Bar for Visa Operations Module
 * 
 * @param string $active_tab The currently active sub-action (list, add, edit, view)
 */
function ifs_terp_visa_render_tabs( $active_tab = 'list' ) {
    global $wpdb;
    $base_url = admin_url( 'admin.php?page=ifs_travel_erp&tab=visa' ); 

    // Quick application count for the badge
    $table_visas = $wpdb->prefix . 'iterp_visa_applications';
    $total_apps  = (int) $wpdb->get_var( "SELECT COUNT(id) FROM {$table_visas}" );

    // Tab Definitions (Requirements tab removed)
    $nav_items = array(
        'list' => array(
            'label' => __( 'All Visas', 'ifs-travel-erp' ),
            'icon'  => 'dashicons-clipboard',
            'url'   => $base_url . '&sub=list',
            'badge' => $total_apps,
        ),
        'add'  => array(
            'label' => __( 'New Visa', 'ifs-travel-erp' ),
            'icon'  => 'dashicons-plus-alt2',
            'url'   => $base_url . '&sub=add',
        ),
    );

    // Contextual states for editing or viewing
    if ( 'edit' === $active_tab ) {
        $nav_items['edit'] = array(
            'label' => __( 'Edit Visa', 'ifs-travel-erp' ),
            'icon'  => 'dashicons-edit',
            'url'   => '#',
        );
    } elseif ( 'view' === $active_tab ) {
        $nav_items['view'] = array(
            'label' => __( 'Visa Details', 'ifs-travel-erp' ),
            'icon'  => 'dashicons-visibility',
            'url'   => '#',
        );
    }
    ?>

    <div class="ifs-module-header">
        
        <!-- Module Heading -->
        <div class="ifs-module-title-wrap">
            <div class="ifs-module-icon">
                <span class="dashicons dashicons-admin-site-alt3"></span>
            </div>
            <div>
                <h2 class="ifs-module-title"><?php esc_html_e( 'Visas', 'ifs-travel-erp' ); ?></h2>
                <p class="ifs-module-subtitle"><?php esc_html_e( 'Track embassy files, appointments, and country requirements', 'ifs-travel-erp' ); ?></p>
            </div>
        </div>

        <!-- Sub-Navigation Bar -->
        <nav class="ifs-sub-nav">
            <?php foreach ( $nav_items as $key => $item ) : 
                $is_active = ( $active_tab === $key );
                ?>
                <a href="<?php echo esc_url( $item['url'] ); ?>" 
                   class="ifs-sub-nav-item <?php echo $is_active ? 'active' : ''; ?>">
                    <span class="dashicons <?php echo esc_attr( $item['icon'] ); ?>"></span>
                    <span><?php echo esc_html( $item['label'] ); ?></span>
                    <?php if ( isset( $item['badge'] ) ) : ?>
                        <span class="ifs-nav-badge"><?php echo esc_html( $item['badge'] ); ?></span>
                    <?php endif; ?>
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

        /* Nav Pills */
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

        .ifs-nav-badge {
            background: #e2e8f0;
            color: #334155;
            font-size: 11px;
            font-weight: 700;
            padding: 1px 7px;
            border-radius: 12px;
            margin-left: 2px;
        }

        .ifs-sub-nav-item.active .ifs-nav-badge {
            background: rgba(255, 255, 255, 0.2);
            color: #ffffff;
        }
    </style>
    <?php
}