<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if ( ! function_exists( 'ifs_terp_invoices_render_tabs' ) ) {
    /**
     * Sub-Navigation Bar for Invoices Module
     * 
     * @param string $active_tab The currently active sub-action (invoices, create_invoice, view_invoice)
     */
    function ifs_terp_invoices_render_tabs( $active_tab = 'invoices' ) {
        global $wpdb;
        $base_url = admin_url( 'admin.php?page=ifs_travel_erp&tab=invoices' ); 

        // Quick invoice count for badge
        $table_invoices = $wpdb->prefix . 'iterp_invoices';
        $total_invoices = (int) $wpdb->get_var( "SELECT COUNT(id) FROM {$table_invoices}" );

        // Tab Definitions (Keeping Invoices and New Invoice)
        $nav_items = array(
            'invoices' => array(
                'label' => __( 'Invoices', 'ifs-travel-erp' ),
                'icon'  => 'dashicons-media-document',
                'url'   => add_query_arg( 'sub', 'invoices', $base_url ),
                'badge' => $total_invoices,
            ),
            'create_invoice' => array(
                'label' => __( 'New Invoice', 'ifs-travel-erp' ),
                'icon'  => 'dashicons-plus-alt2',
                'url'   => add_query_arg( 'sub', 'create_invoice', $base_url ),
            ),
        );

        // Contextual state for viewing single invoice
        if ( 'view_invoice' === $active_tab ) {
            $nav_items['view_invoice'] = array(
                'label' => __( 'View Invoice', 'ifs-travel-erp' ),
                'icon'  => 'dashicons-visibility',
                'url'   => '#',
            );
        }
        ?>

        <div class="ifs-module-header">
            <!-- Module Heading -->
            <div class="ifs-module-title-wrap">
                <div class="ifs-module-icon">
                    <span class="dashicons dashicons-money-alt"></span>
                </div>
                <div>
                    <h2 class="ifs-module-title"><?php esc_html_e( 'Invoices & Billing', 'ifs-travel-erp' ); ?></h2>
                    <p class="ifs-module-subtitle"><?php esc_html_e( 'Manage customer bills, itemized billing statements, and payment receipts', 'ifs-travel-erp' ); ?></p>
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
}