<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Explicitly require the modular files with file_exists guards
$hotel_sub_files = array(
    'all-hotel.php',
    'add-hotel.php',
    'view-hotel.php',
    'delete-hotel.php',
);

foreach ( $hotel_sub_files as $hotel_file ) {
    $file_path = ITERP_PATH . 'inc/settings/hotel/' . $hotel_file;
    if ( file_exists( $file_path ) ) {
        require_once $file_path;
    }
}

/**
 * Output Hotel Module Enterprise Layout Styles safely
 */
if ( ! function_exists( 'ifs_terp_hotel_properties_styles' ) ) {
    function ifs_terp_hotel_properties_styles() {
        ?>
        <style>
            .ifs-section-subnav-wrap {
                display: flex; justify-content: space-between; align-items: center; background: #ffffff;
                border: 1px solid #e2e8f0; border-radius: 12px; padding: 10px 16px; margin-bottom: 22px;
                box-shadow: 0 2px 8px -2px rgba(15, 23, 42, 0.04); flex-wrap: wrap; gap: 12px;
            }
            .ifs-section-subnav-left { display: flex; align-items: center; gap: 8px; }
            .ifs-subnav-badge {
                display: inline-flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 700;
                color: #0284c7; background: #f0f9ff; border: 1px solid #e0f2fe; padding: 5px 12px; border-radius: 20px;
            }
            .ifs-subnav-badge .dashicons { font-size: 15px; width: 15px; height: 15px; }
            .ifs-nested-pills { display: inline-flex; gap: 6px; background: #f8fafc; border: 1px solid #e2e8f0; padding: 4px; border-radius: 8px; }
            .ifs-nested-pill-item {
                display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; font-size: 12.5px;
                font-weight: 700; color: #475569; text-decoration: none; border-radius: 6px; transition: all 0.18s ease;
            }
            .ifs-nested-pill-item .dashicons { font-size: 15px; width: 15px; height: 15px; color: #64748b; }
            .ifs-nested-pill-item:hover { color: #0f172a; background: #f1f5f9; }
            .ifs-nested-pill-item.active { background: #003376; color: #ffffff; box-shadow: 0 2px 6px rgba(0, 51, 118, 0.25); }
            .ifs-nested-pill-item.active .dashicons { color: #ffffff; }

            /* Action Buttons */
            .ifs-action-btn {
                width: 32px; height: 32px; border-radius: 7px; display: inline-flex; align-items: center;
                justify-content: center; text-decoration: none; border: 1px solid transparent; transition: all 0.18s ease-in-out;
            }
            .ifs-action-btn .dashicons { font-size: 16px; width: 16px; height: 16px; line-height: 1; }
            .ifs-action-btn.view { background: #e0f2fe; color: #0284c7; border-color: #bae6fd; }
            .ifs-action-btn.view:hover { background: #0284c7; color: #ffffff; border-color: #0284c7; transform: translateY(-1px); }
            .ifs-action-btn.edit { background: #f0fdf4; color: #166534; border-color: #bbf7d0; }
            .ifs-action-btn.edit:hover { background: #16a34a; color: #ffffff; border-color: #16a34a; transform: translateY(-1px); }
            .ifs-action-btn.delete { background: #fef2f2; color: #b91c1c; border-color: #fecaca; }
            .ifs-action-btn.delete:hover { background: #dc2626; color: #ffffff; border-color: #dc2626; transform: translateY(-1px); }

            /* Split Layout & Live Preview Styles */
            .ifs-reqs-workspace { max-width: 1440px; margin: 0 auto; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; color: #0f172a; }
            .ifs-toast { padding: 14px 20px; border-radius: 10px; font-size: 13.5px; font-weight: 600; display: flex; align-items: center; gap: 10px; margin-bottom: 22px; }
            .ifs-toast.success { background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; }
            .ifs-toast.danger  { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
            .ifs-split-reqs-editor { display: grid; grid-template-columns: 1fr 390px; gap: 28px; align-items: flex-start; }
            @media (max-width: 1140px) { .ifs-split-reqs-editor { grid-template-columns: 1fr; } }
            .ifs-panel-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 26px; margin-bottom: 22px; box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.03); }
            .ifs-card-header { display: flex; align-items: center; gap: 14px; margin-bottom: 22px; padding-bottom: 14px; border-bottom: 1px solid #f1f5f9; }
            .ifs-step-num { width: 34px; height: 34px; border-radius: 9px; background: linear-gradient(135deg, #003376 0%, #0284c7 100%); color: #ffffff; font-weight: 800; font-size: 13px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px rgba(0, 51, 118, 0.2); flex-shrink: 0; }
            .ifs-card-title { margin: 0; font-size: 15.5px; font-weight: 800; color: #0f172a; }
            .ifs-card-desc { margin: 2px 0 0 0; font-size: 12.5px; color: #64748b; }
            .ifs-grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px 18px; }
            @media (max-width: 768px) { .ifs-grid-2 { grid-template-columns: 1fr; } }
            .ifs-field-block { display: flex; flex-direction: column; gap: 5px; }
            .ifs-field-label { font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.4px; }
            .ifs-field-label .req { color: #e11d48; }
            .ifs-field-wrap { position: relative; display: flex; align-items: center; width: 100%; height: 42px; }
            .ifs-field-wrap .field-icon { position: absolute; left: 12px; color: #94a3b8; font-size: 17px; width: 17px; height: 17px; pointer-events: none; z-index: 2; }
            .ifs-input-field {
                width: 100% !important; height: 42px !important; line-height: 40px !important; padding: 0 14px !important;
                border: 1px solid #cbd5e1 !important; border-radius: 8px !important; font-size: 13.5px !important;
                color: #0f172a !important; background: #ffffff !important; outline: none !important;
            }
            .ifs-field-wrap .ifs-input-field { padding-left: 38px !important; }
            .ifs-field-wrap select.ifs-input-field {
                appearance: none; -webkit-appearance: none;
                background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
                background-repeat: no-repeat; background-position: right 10px center; background-size: 14px; padding-right: 32px !important;
            }
            .font-mono { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; }
            .font-bold { font-weight: 700; }
            .color-emerald { color: #059669 !important; }
            .repeater-items-container { display: flex; flex-direction: column; gap: 8px; }
            .repeater-row { display: flex; align-items: center; gap: 8px; }
            .row-indicator { font-size: 13px; font-weight: 800; width: 22px; height: 22px; display: inline-flex; align-items: center; justify-content: center; border-radius: 50%; flex-shrink: 0; }
            .row-indicator.inc { background: #e0f2fe; color: #0284c7; }
            .btn-remove-row { background: #fee2e2; border: 1px solid #fecaca; color: #b91c1c; border-radius: 6px; width: 34px; height: 34px; font-size: 18px; line-height: 1; cursor: pointer; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
            .btn-remove-row:hover { background: #ef4444; color: #ffffff; border-color: #ef4444; }
            .ifs-btn-add-row { background: #eff6ff; color: #0284c7; border: 1px solid #bae6fd; padding: 6px 14px; border-radius: 8px; font-size: 12px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 4px; }
            .ifs-btn-add-row:hover { background: #0284c7; color: #ffffff; }
            .ifs-action-strip { display: flex; justify-content: space-between; align-items: center; padding: 18px 24px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; }
            .ifs-btn-back { color: #64748b; text-decoration: none; font-size: 13.5px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; }
            .ifs-btn-primary { background: linear-gradient(135deg, #003376 0%, #0284c7 100%); color: #ffffff !important; border: none; padding: 11px 26px; border-radius: 8px; font-size: 13.5px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
            .ifs-preview-sticky { position: sticky; top: 30px; }
            .ifs-card-preview-header { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; margin-bottom: 12px; display: flex; align-items: center; gap: 6px; }
            .ifs-advisory-card { background: linear-gradient(135deg, #0b1329 0%, #1e293b 50%, #0f172a 100%); border-radius: 18px; padding: 24px; color: #ffffff; border: 1px solid rgba(255, 255, 255, 0.12); position: relative; overflow: hidden; margin-bottom: 18px; }
            .advisory-watermark { position: absolute; right: -15px; bottom: -15px; opacity: 0.04; pointer-events: none; }
            .advisory-watermark .dashicons { font-size: 140px; width: 140px; height: 140px; color: #ffffff; }
            .advisory-head-strip { display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; padding-bottom: 10px; border-bottom: 1px solid rgba(255, 255, 255, 0.08); }
            .advisory-badge-tag { font-size: 9.5px; font-weight: 800; letter-spacing: 0.8px; color: #94a3b8; display: inline-flex; align-items: center; gap: 4px; }
            .advisory-badge-tag .dashicons { font-size: 13px; width: 13px; height: 13px; color: #38bdf8; }
            .advisory-time-tag { background: rgba(56, 189, 248, 0.12); color: #38bdf8; border: 1px solid rgba(56, 189, 248, 0.25); padding: 3px 8px; border-radius: 6px; font-size: 9.5px; font-weight: 800; text-transform: uppercase; display: inline-flex; align-items: center; gap: 3px; }
            .advisory-hero { margin-bottom: 16px; }
            .advisory-country { margin: 0; font-size: 20px; font-weight: 900; color: #ffffff; text-transform: uppercase; }
            .advisory-category { font-size: 11.5px; color: #38bdf8; margin-top: 3px; display: block; text-transform: uppercase; font-weight: 700; }
            .advisory-meta-pills { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 8px; }
            .advisory-mode-badge, .advisory-validity-badge { display: inline-flex; align-items: center; gap: 4px; font-size: 10px; color: #e2e8f0; background: rgba(255, 255, 255, 0.07); border: 1px solid rgba(255, 255, 255, 0.06); padding: 3px 8px; border-radius: 6px; font-weight: 600; }
            .advisory-mode-badge .dashicons, .advisory-validity-badge .dashicons { font-size: 12px; width: 12px; height: 12px; color: #38bdf8; }
            .advisory-fee-box { background: rgba(0, 0, 0, 0.3); border: 1px solid rgba(255, 255, 255, 0.06); border-radius: 12px; padding: 14px; margin-bottom: 14px; display: flex; justify-content: space-between; align-items: center; }
            .advisory-fee-lbl { font-size: 9px; font-weight: 700; color: #94a3b8; letter-spacing: 0.5px; }
            .advisory-fee-sub { font-size: 10px; color: #cbd5e1; margin-top: 2px; font-family: ui-monospace, monospace; }
            .advisory-fee-val { margin: 0; font-size: 18px; font-weight: 900; color: #4ade80; }
            .advisory-checklist-box { background: rgba(255, 255, 255, 0.04); border: 1px solid rgba(255, 255, 255, 0.05); border-radius: 12px; padding: 12px 14px; margin-bottom: 12px; }
            .checklist-head { font-size: 9.5px; font-weight: 800; color: #38bdf8; display: flex; align-items: center; gap: 4px; margin-bottom: 6px; }
            .checklist-head .dashicons { font-size: 12px; width: 12px; height: 12px; }
            .checklist-content { font-size: 11px; color: #cbd5e1; line-height: 1.5; max-height: 120px; overflow-y: auto; }
            .advisory-footer-strip { font-size: 9.5px; color: #94a3b8; display: flex; justify-content: space-between; align-items: center; padding-top: 8px; border-top: 1px solid rgba(255, 255, 255, 0.08); }
            .live-pulse-dot { width: 7px; height: 7px; background: #4ade80; border-radius: 50%; display: inline-block; }
        </style>
        <?php
    }
}
add_action( 'admin_head', 'ifs_terp_hotel_properties_styles' );

/**
 * Hotel Properties Sub-Router Engine
 */
if ( ! function_exists( 'ifs_terp_settings_hotels_router' ) ) {
    function ifs_terp_settings_hotels_router() {
        $hotel_sub = isset( $_GET['hotel_sub'] ) ? sanitize_key( wp_unslash( $_GET['hotel_sub'] ) ) : 'all';
        $base_url  = admin_url( 'admin.php?page=ifs_travel_erp&tab=settings&sub=hotels_prop' );

        if ( $hotel_sub === 'delete' ) {
            if ( function_exists( 'ifs_terp_settings_hotel_delete_handler' ) ) {
                ifs_terp_settings_hotel_delete_handler();
            } else {
                wp_die( esc_html__( 'Delete handler callback not available.', 'ifs-travel-erp' ) );
            }
            return;
        }

        $nav_items = array(
            'all' => array(
                'label' => __( 'All Properties', 'ifs-travel-erp' ),
                'icon'  => 'dashicons-building',
                'url'   => add_query_arg( 'hotel_sub', 'all', $base_url ),
            ),
            'add' => array(
                'label' => isset( $_GET['id'] ) && $hotel_sub === 'edit' ? __( 'Edit Property', 'ifs-travel-erp' ) : __( 'Add New Property', 'ifs-travel-erp' ),
                'icon'  => isset( $_GET['id'] ) && $hotel_sub === 'edit' ? 'dashicons-edit' : 'dashicons-plus-alt2',
                'url'   => add_query_arg( 'hotel_sub', 'add', $base_url ),
            ),
        );

        if ( $hotel_sub === 'view' ) {
            $nav_items['view'] = array(
                'label' => __( 'Property Overview', 'ifs-travel-erp' ),
                'icon'  => 'dashicons-visibility',
                'url'   => '#',
            );
        }
        ?>
        <div class="ifs-section-subnav-wrap">
            <div class="ifs-section-subnav-left">
                <span class="ifs-subnav-badge"><span class="dashicons dashicons-admin-multisite"></span> <?php esc_html_e( 'Accommodation Contracts', 'ifs-travel-erp' ); ?></span>
            </div>
            <nav class="ifs-nested-pills">
                <?php foreach ( $nav_items as $key => $item ) : 
                    $is_active = ( $hotel_sub === $key || ( $key === 'add' && $hotel_sub === 'edit' ) );
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
        if ( $hotel_sub === 'view' ) {
            if ( function_exists( 'ifs_terp_hotel_property_view_panel' ) ) {
                ifs_terp_hotel_property_view_panel();
            } else {
                echo '<div class="notice notice-error"><p>' . esc_html__( 'View panel module not loaded.', 'ifs-travel-erp' ) . '</p></div>';
            }
        } elseif ( $hotel_sub === 'add' || $hotel_sub === 'edit' || ( isset( $_GET['id'] ) && $hotel_sub !== 'view' ) ) {
            if ( function_exists( 'ifs_terp_hotel_property_form_panel' ) ) {
                ifs_terp_hotel_property_form_panel();
            } else {
                echo '<div class="notice notice-error"><p>' . esc_html__( 'Form panel module not loaded.', 'ifs-travel-erp' ) . '</p></div>';
            }
        } else {
            if ( function_exists( 'ifs_terp_hotel_properties_list_panel' ) ) {
                ifs_terp_hotel_properties_list_panel();
            } else {
                echo '<div class="notice notice-error"><p>' . esc_html__( 'List directory module not loaded.', 'ifs-travel-erp' ) . '</p></div>';
            }
        }
    }
}