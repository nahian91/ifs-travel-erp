<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// 1. Include sub-action files with file_exists guards
$hajj_sub_files = array(
    'all-hajj.php',
    'add-hajj.php',
    'view-hajj.php',
    'delete-hajj.php',
);

foreach ( $hajj_sub_files as $hajj_file ) {
    $file_path = ITERP_PATH . 'inc/settings/hajj/' . $hajj_file;
    if ( file_exists( $file_path ) ) {
        require_once $file_path;
    }
}

/**
 * Output Hajj & Umrah Module Styles in Admin Head
 */
function ifs_terp_hajj_packages_styles() {
    ?>
    <style>
        .ifs-section-subnav-wrap {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 10px 16px;
            margin-bottom: 22px;
            box-shadow: 0 2px 8px -2px rgba(15, 23, 42, 0.04);
            flex-wrap: wrap;
            gap: 12px;
        }
        .ifs-section-subnav-left {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .ifs-subnav-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            font-weight: 700;
            color: #047857;
            background: #ecfdf5;
            border: 1px solid #d1fae5;
            padding: 5px 12px;
            border-radius: 20px;
            letter-spacing: 0.2px;
        }
        .ifs-subnav-badge .dashicons {
            font-size: 15px;
            width: 15px;
            height: 15px;
        }
        .ifs-nested-pills {
            display: inline-flex;
            gap: 6px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 4px;
            border-radius: 8px;
        }
        .ifs-nested-pill-item {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            font-size: 12.5px;
            font-weight: 700;
            color: #475569;
            text-decoration: none;
            border-radius: 6px;
            transition: all 0.18s ease;
        }
        .ifs-nested-pill-item .dashicons {
            font-size: 15px;
            width: 15px;
            height: 15px;
            color: #64748b;
        }
        .ifs-nested-pill-item:hover {
            color: #0f172a;
            background: #f1f5f9;
        }
        .ifs-nested-pill-item.active {
            background: #047857;
            color: #ffffff;
            box-shadow: 0 2px 6px rgba(4, 120, 87, 0.25);
        }
        .ifs-nested-pill-item.active .dashicons {
            color: #ffffff;
        }

        .ifs-action-btn {
            width: 32px;
            height: 32px;
            border-radius: 7px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            border: 1px solid transparent;
            transition: all 0.18s ease-in-out;
        }
        .ifs-action-btn .dashicons {
            font-size: 16px;
            width: 16px;
            height: 16px;
            line-height: 1;
        }
        .ifs-action-btn.view {
            background: #e0f2fe;
            color: #0284c7;
            border-color: #bae6fd;
        }
        .ifs-action-btn.view:hover {
            background: #0284c7;
            color: #ffffff;
            border-color: #0284c7;
            transform: translateY(-1px);
        }
        .ifs-action-btn.edit {
            background: #f0fdf4;
            color: #166534;
            border-color: #bbf7d0;
        }
        .ifs-action-btn.edit:hover {
            background: #16a34a;
            color: #ffffff;
            border-color: #16a34a;
            transform: translateY(-1px);
        }
        .ifs-action-btn.delete {
            background: #fef2f2;
            color: #b91c1c;
            border-color: #fecaca;
        }
        .ifs-action-btn.delete:hover {
            background: #dc2626;
            color: #ffffff;
            border-color: #dc2626;
            transform: translateY(-1px);
        }

        .ifs-btn-add-row {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            height: 34px;
            padding: 0 14px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 700;
            color: #047857;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s ease;
        }
        .ifs-btn-add-row:hover {
            background: #047857;
            color: #ffffff;
            border-color: #047857;
        }

        .ifs-nav-btn.back:hover {
            background: #e2e8f0 !important;
            color: #0f172a !important;
        }
        .ifs-nav-btn.edit:hover {
            background: #047857 !important;
            border-color: #047857 !important;
            color: #ffffff !important;
        }
        .ifs-nav-btn.delete:hover {
            background: #dc2626 !important;
            color: #ffffff !important;
            border-color: #dc2626 !important;
        }
    </style>
    <?php
}
add_action( 'admin_head', 'ifs_terp_hajj_packages_styles' );

/**
 * Hajj & Umrah Packages Router Engine
 */
function ifs_terp_settings_hajj_pkg_router() {
    $action_sub = isset( $_GET['action_sub'] ) ? sanitize_key( wp_unslash( $_GET['action_sub'] ) ) : 'all';
    $base_url   = admin_url( 'admin.php?page=ifs_travel_erp&tab=settings&sub=hajj_pkg' );

    // Delegate delete action first
    if ( $action_sub === 'delete' ) {
        if ( function_exists( 'ifs_terp_settings_hajj_delete_handler' ) ) {
            ifs_terp_settings_hajj_delete_handler();
        } else {
            wp_die( esc_html__( 'Delete handler callback not available.', 'ifs-travel-erp' ) );
        }
        return;
    }

    $nav_items = array(
        'all' => array(
            'label' => __( 'All Packages', 'ifs-travel-erp' ),
            'icon'  => 'dashicons-palmtree',
            'url'   => add_query_arg( 'action_sub', 'all', $base_url ),
        ),
        'add' => array(
            'label' => ( ( $action_sub === 'edit' || $action_sub === 'add' ) && isset( $_GET['id'] ) ) ? __( 'Edit Package', 'ifs-travel-erp' ) : __( 'Add Package', 'ifs-travel-erp' ),
            'icon'  => ( ( $action_sub === 'edit' || $action_sub === 'add' ) && isset( $_GET['id'] ) ) ? 'dashicons-edit' : 'dashicons-plus-alt2',
            'url'   => add_query_arg( 'action_sub', 'add', $base_url ),
        ),
    );

    if ( $action_sub === 'view' ) {
        $nav_items['view'] = array(
            'label' => __( 'Package Overview', 'ifs-travel-erp' ),
            'icon'  => 'dashicons-visibility',
            'url'   => '#',
        );
    }
    ?>
    <div class="ifs-section-subnav-wrap">
        <div class="ifs-section-subnav-left">
            <span class="ifs-subnav-badge"><span class="dashicons dashicons-calendar-alt"></span> <?php esc_html_e( 'Makkah &amp; Madinah Plans', 'ifs-travel-erp' ); ?></span>
        </div>
        <nav class="ifs-nested-pills">
            <?php foreach ( $nav_items as $key => $item ) : 
                $is_active = ( $action_sub === $key || ( $key === 'add' && $action_sub === 'edit' ) );
                ?>
                <a href="<?php echo esc_url( $item['url'] ); ?>" 
                   class="ifs-nested-pill-item <?php echo $is_active ? 'active' : ''; ?>">
                    <span class="dashicons <?php echo esc_attr( $item['icon'] ); ?>"></span>
                    <span><?php echo esc_html( $item['label'] ); ?></span>
                </a>
            <?php endforeach; ?>
        </nav>
    </div>

    <?php
    if ( $action_sub === 'view' ) {
        if ( function_exists( 'ifs_terp_settings_hajj_pkg_view_panel' ) ) {
            ifs_terp_settings_hajj_pkg_view_panel();
        } else {
            echo '<div class="notice notice-error"><p>' . esc_html__( 'View panel module not loaded.', 'ifs-travel-erp' ) . '</p></div>';
        }
    } elseif ( $action_sub === 'add' || $action_sub === 'edit' || ( isset( $_GET['id'] ) && $action_sub !== 'view' ) ) {
        if ( function_exists( 'ifs_terp_settings_hajj_pkg_form_panel' ) ) {
            ifs_terp_settings_hajj_pkg_form_panel();
        } else {
            echo '<div class="notice notice-error"><p>' . esc_html__( 'Form panel module not loaded.', 'ifs-travel-erp' ) . '</p></div>';
        }
    } else {
        if ( function_exists( 'ifs_terp_settings_hajj_pkg_list_panel' ) ) {
            ifs_terp_settings_hajj_pkg_list_panel();
        } else {
            echo '<div class="notice notice-error"><p>' . esc_html__( 'List directory module not loaded.', 'ifs-travel-erp' ) . '</p></div>';
        }
    }
}