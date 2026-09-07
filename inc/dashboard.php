<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Dashboard Tab - IFS Travel ERP Overview Panel
 */
function ifs_terp_dashboard_tab() {
    global $wpdb;

    // Database Tables
    $tbl_tickets   = $wpdb->prefix . 'iterp_tickets';
    $tbl_visas     = $wpdb->prefix . 'iterp_visa_applications';
    $tbl_invoices  = $wpdb->prefix . 'iterp_invoices';
    $tbl_ledger    = $wpdb->prefix . 'iterp_ledger';
    $tbl_agents    = $wpdb->prefix . 'iterp_agents';
    $tbl_customers = $wpdb->prefix . 'iterp_customers';
    $tbl_hajj      = $wpdb->prefix . 'iterp_hajj_bookings';

    // Time Frames
    $today_start = current_time( 'Y-m-d 00:00:00' );
    $today_end   = current_time( 'Y-m-d 23:59:59' );
    $month_start = current_time( 'Y-m-01 00:00:00' );
    $month_end   = current_time( 'Y-m-t 23:59:59' );

    // 1. Financial Inflow/Outflow Aggregation (Single Optimized Query)
    $ledger_totals = $wpdb->get_row( $wpdb->prepare(
        "SELECT 
            SUM(CASE WHEN transaction_type = 'Income'  AND transaction_date BETWEEN %s AND %s THEN amount ELSE 0 END) AS today_income,
            SUM(CASE WHEN transaction_type = 'Expense' AND transaction_date BETWEEN %s AND %s THEN amount ELSE 0 END) AS today_expense,
            SUM(CASE WHEN transaction_type = 'Income'  AND transaction_date BETWEEN %s AND %s THEN amount ELSE 0 END) AS month_income,
            SUM(CASE WHEN transaction_type = 'Expense' AND transaction_date BETWEEN %s AND %s THEN amount ELSE 0 END) AS month_expense
         FROM $tbl_ledger",
        $today_start, $today_end,
        $today_start, $today_end,
        $month_start, $month_end,
        $month_start, $month_end
    ) );

    $today_income  = (float) ( $ledger_totals->today_income ?? 0 );
    $today_expense = (float) ( $ledger_totals->today_expense ?? 0 );
    $month_income  = (float) ( $ledger_totals->month_income ?? 0 );
    $month_expense = (float) ( $ledger_totals->month_expense ?? 0 );
    $net_margin    = $month_income - $month_expense;

    // 2. Operational Counts
    $today_tickets    = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM $tbl_tickets WHERE created_at BETWEEN %s AND %s", $today_start, $today_end ) );
    $processing_visas = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM $tbl_visas WHERE status = %s", 'Processing' ) );
    $active_hajj      = (int) $wpdb->get_var( "SELECT COUNT(id) FROM $tbl_hajj WHERE status IN ('Booked', 'Confirmed')" );
    $market_dues      = (float) $wpdb->get_var( "SELECT SUM(due_amount) FROM $tbl_invoices WHERE due_amount > 0" );
    $active_agents    = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM $tbl_agents WHERE status = %s", 'Active' ) );

    // 3. Tab URL Generator
    $tab_url = function( $tab, $sub = '', $view = '' ) {
        $args = array(
            'page' => 'ifs_travel_erp',
            'tab'  => sanitize_key( $tab ),
        );
        if ( ! empty( $sub ) ) {
            $args['sub'] = sanitize_key( $sub );
        }
        if ( ! empty( $view ) ) {
            $args['view'] = sanitize_key( $view );
        }
        return add_query_arg( $args, admin_url( 'admin.php' ) );
    };

    // 4. Quick Action Buttons Config
    $quick_actions = array(
        array( 'label' => __( 'New Flight Ticket', 'ifs-travel-erp' ), 'icon' => 'dashicons-tickets-alt',     'url' => $tab_url( 'ticketing', '', 'add' ),             'color' => '#0284c7', 'primary' => true ),
        array( 'label' => __( 'New Visa', 'ifs-travel-erp' ),          'icon' => 'dashicons-admin-site-alt3', 'url' => $tab_url( 'visa', '', 'add' ),                  'color' => '#d97706' ),
        array( 'label' => __( 'New Pilgrim', 'ifs-travel-erp' ),       'icon' => 'dashicons-groups',          'url' => $tab_url( 'hajj_umrah', '', 'add' ),             'color' => '#059669' ),
        array( 'label' => __( 'Create Invoice', 'ifs-travel-erp' ),    'icon' => 'dashicons-media-document',  'url' => $tab_url( 'invoices', '', 'add' ),               'color' => '#7c3aed' ),
        array( 'label' => __( 'Record Income', 'ifs-travel-erp' ),     'icon' => 'dashicons-money-alt',       'url' => $tab_url( 'accounts', 'income', 'add' ),         'color' => '#15803d' ),
        array( 'label' => __( 'Record Expense', 'ifs-travel-erp' ),    'icon' => 'dashicons-cart',            'url' => $tab_url( 'accounts', 'expense', 'add' ),        'color' => '#dc2626' ),
    );

    // 5. KPI Cards Config
    $kpi_cards = array(
        array(
            'title'    => __( "Today's Tickets", 'ifs-travel-erp' ),
            'value'    => number_format( $today_tickets ),
            'color'    => 'blue',
            'icon'     => 'dashicons-tickets-alt',
            'link'     => $tab_url( 'ticketing' ),
            'link_txt' => __( 'View tickets &rarr;', 'ifs-travel-erp' )
        ),
        array(
            'title'    => __( 'Visas in Progress', 'ifs-travel-erp' ),
            'value'    => number_format( $processing_visas ),
            'color'    => 'amber',
            'icon'     => 'dashicons-admin-site-alt3',
            'link'     => $tab_url( 'visa' ),
            'link_txt' => __( 'Track applications &rarr;', 'ifs-travel-erp' )
        ),
        array(
            'title'    => __( 'Hajj & Umrah Pilgrims', 'ifs-travel-erp' ),
            'value'    => number_format( $active_hajj ),
            'color'    => 'cyan',
            'icon'     => 'dashicons-groups',
            'link'     => $tab_url( 'hajj_umrah' ),
            'link_txt' => __( 'Pilgrim list &rarr;', 'ifs-travel-erp' )
        ),
        array(
            'title'    => __( 'Total Unpaid Dues', 'ifs-travel-erp' ),
            'value'    => '৳' . number_format( $market_dues, 2 ),
            'color'    => 'rose',
            'icon'     => 'dashicons-warning',
            'link'     => $tab_url( 'invoices' ),
            'link_txt' => __( 'Collect dues &rarr;', 'ifs-travel-erp' )
        ),
        array(
            'title'    => __( "Today's Inflow", 'ifs-travel-erp' ),
            'value'    => '৳' . number_format( $today_income, 2 ),
            'color'    => 'emerald',
            'icon'     => 'dashicons-arrow-down-alt',
            'sub'      => __( 'Cash & bank received', 'ifs-travel-erp' ),
            'link'     => $tab_url( 'accounts', 'income' ),
            'link_txt' => __( 'Income ledger &rarr;', 'ifs-travel-erp' )
        ),
        array(
            'title'    => __( "Today's Outflow", 'ifs-travel-erp' ),
            'value'    => '৳' . number_format( $today_expense, 2 ),
            'color'    => 'slate',
            'icon'     => 'dashicons-arrow-up-alt',
            'sub'      => __( 'Expenses paid', 'ifs-travel-erp' ),
            'link'     => $tab_url( 'accounts', 'expense' ),
            'link_txt' => __( 'Expense ledger &rarr;', 'ifs-travel-erp' )
        ),
        array(
            'title'    => __( 'Profit This Month', 'ifs-travel-erp' ),
            'value'    => '৳' . number_format( $net_margin, 2 ),
            'color'    => ( $net_margin >= 0 ) ? 'emerald' : 'rose',
            'icon'     => 'dashicons-chart-line',
            'sub'      => ( $net_margin >= 0 ) ? __( 'Profitable', 'ifs-travel-erp' ) : __( 'Net Loss', 'ifs-travel-erp' ),
            'link'     => $tab_url( 'accounts', 'reports' ),
            'link_txt' => __( 'Financial report &rarr;', 'ifs-travel-erp' )
        ),
        array(
            'title'    => __( 'Active B2B Agents', 'ifs-travel-erp' ),
            'value'    => number_format( $active_agents ),
            'color'    => 'indigo',
            'icon'     => 'dashicons-networking',
            'link'     => $tab_url( 'b2b_agents' ),
            'link_txt' => __( 'View agents &rarr;', 'ifs-travel-erp' )
        ),
    );

    // 6. Recent Feeds
    $recent_tickets = $wpdb->get_results( "
        SELECT t.*, c.full_name as customer_name 
        FROM $tbl_tickets t 
        LEFT JOIN $tbl_customers c ON t.customer_id = c.id 
        ORDER BY t.id DESC LIMIT 5
    " );

    $recent_visas = $wpdb->get_results( "
        SELECT v.*, c.full_name as customer_name 
        FROM $tbl_visas v 
        LEFT JOIN $tbl_customers c ON v.customer_id = c.id 
        ORDER BY v.id DESC LIMIT 5
    " );

    // 7. Greeting Text
    $hour         = (int) current_time( 'H' );
    $greeting     = ( $hour < 12 ) ? __( 'Good morning', 'ifs-travel-erp' ) : ( ( $hour < 17 ) ? __( 'Good afternoon', 'ifs-travel-erp' ) : __( 'Good evening', 'ifs-travel-erp' ) );
    $current_user = wp_get_current_user();
    $user_name    = ! empty( $current_user->display_name ) ? $current_user->display_name : 'Team';
    $gmt_offset   = (float) get_option( 'gmt_offset' );
    ?>

    <div class="ifs-dash-wrap">
        
        <!-- Welcome Hero Header -->
        <div class="ifs-dash-hero">
            <div class="ifs-hero-profile">
                <div class="ifs-hero-logo">
                    <img src="<?php echo esc_url( ITERP_URL . 'assets/img/logo.png' ); ?>" alt="Logo" onerror="this.style.display='none'">
                    <span class="online-indicator"></span>
                </div>
                <div>
                    <h1 class="ifs-hero-title"><?php echo esc_html( "$greeting, $user_name!" ); ?></h1>
                    <p class="ifs-hero-desc"><?php esc_html_e( 'Here is what is happening across your travel operations today.', 'ifs-travel-erp' ); ?></p>
                </div>
            </div>

            <div class="ifs-hero-timebox">
                <div class="timebox-date"><span class="dashicons dashicons-calendar-alt"></span> <?php echo esc_html( date_i18n( 'l, jS F Y', current_time( 'timestamp' ) ) ); ?></div>
                <div class="timebox-time"><span class="dashicons dashicons-clock"></span> <span id="ifsLiveClock"><?php echo esc_html( current_time( 'H:i:s' ) ); ?></span></div>
            </div>
        </div>

        <!-- Quick Shortcut Actions -->
        <div class="ifs-quick-links">
            <span class="quick-title"><?php esc_html_e( 'Quick Actions:', 'ifs-travel-erp' ); ?></span>
            <div class="quick-buttons">
                <?php foreach ( $quick_actions as $btn ) : ?>
                    <a href="<?php echo esc_url( $btn['url'] ); ?>" class="ifs-btn-chip <?php echo ! empty( $btn['primary'] ) ? 'btn-primary' : ''; ?>">
                        <span class="dashicons <?php echo esc_attr( $btn['icon'] ); ?>" style="<?php echo empty( $btn['primary'] ) ? 'color: ' . esc_attr( $btn['color'] ) . ';' : ''; ?>"></span>
                        <?php echo esc_html( $btn['label'] ); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Metric Cards Grid -->
        <div class="ifs-kpi-grid">
            <?php foreach ( $kpi_cards as $kpi ) : ?>
                <div class="ifs-kpi-box border-<?php echo esc_attr( $kpi['color'] ); ?>">
                    <div class="kpi-icon bg-<?php echo esc_attr( $kpi['color'] ); ?>">
                        <span class="dashicons <?php echo esc_attr( $kpi['icon'] ); ?>"></span>
                    </div>
                    <div class="kpi-body">
                        <span class="kpi-label"><?php echo esc_html( $kpi['title'] ); ?></span>
                        <div class="kpi-number text-<?php echo esc_attr( $kpi['color'] ); ?>"><?php echo esc_html( $kpi['value'] ); ?></div>
                        <?php if ( ! empty( $kpi['link'] ) ) : ?>
                            <a href="<?php echo esc_url( $kpi['link'] ); ?>" class="kpi-link"><?php echo wp_kses_post( $kpi['link_txt'] ); ?></a>
                        <?php elseif ( ! empty( $kpi['sub'] ) ) : ?>
                            <span class="kpi-note"><?php echo esc_html( $kpi['sub'] ); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Recent Activity Feed -->
        <div class="ifs-feed-layout">
            
            <!-- Latest Air Tickets -->
            <div class="ifs-table-card">
                <div class="card-header">
                    <h3><span class="dashicons dashicons-tickets-alt"></span> <?php esc_html_e( 'Recent Flight Tickets', 'ifs-travel-erp' ); ?></h3>
                    <a href="<?php echo esc_url( $tab_url( 'ticketing' ) ); ?>" class="card-link"><?php esc_html_e( 'View All', 'ifs-travel-erp' ); ?></a>
                </div>
                <div class="table-scroll">
                    <table class="ifs-clean-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e( 'Passenger / PNR', 'ifs-travel-erp' ); ?></th>
                                <th><?php esc_html_e( 'Sector / Airline', 'ifs-travel-erp' ); ?></th>
                                <th class="col-align-right"><?php esc_html_e( 'Fare', 'ifs-travel-erp' ); ?></th>
                                <th class="col-align-right"><?php esc_html_e( 'Status', 'ifs-travel-erp' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ( ! empty( $recent_tickets ) ) : foreach ( $recent_tickets as $t ) : ?>
                                <tr>
                                    <td>
                                        <div class="tbl-main"><?php echo esc_html( ! empty( $t->customer_name ) ? $t->customer_name : ( ! empty( $t->passenger_name ) ? $t->passenger_name : 'Direct Client' ) ); ?></div>
                                        <div class="tbl-sub"><?php echo esc_html( $t->pnr ); ?></div>
                                    </td>
                                    <td>
                                        <div class="tbl-main"><?php echo esc_html( $t->airline ); ?></div>
                                        <div class="tbl-sub"><?php echo esc_html( $t->sector ); ?></div>
                                    </td>
                                    <td class="col-align-right font-mono-weight">৳<?php echo esc_html( number_format( (float) $t->sell_price, 2 ) ); ?></td>
                                    <td class="col-align-right">
                                        <span class="badge badge-success"><?php echo esc_html( $t->status ); ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; else : ?>
                                <tr><td colspan="4" class="no-records"><?php esc_html_e( 'No recent tickets issued yet.', 'ifs-travel-erp' ); ?></td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Latest Visas -->
            <div class="ifs-table-card">
                <div class="card-header">
                    <h3><span class="dashicons dashicons-admin-site-alt3"></span> <?php esc_html_e( 'Recent Visa Files', 'ifs-travel-erp' ); ?></h3>
                    <a href="<?php echo esc_url( $tab_url( 'visa' ) ); ?>" class="card-link"><?php esc_html_e( 'View All', 'ifs-travel-erp' ); ?></a>
                </div>
                <div class="table-scroll">
                    <table class="ifs-clean-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e( 'Applicant', 'ifs-travel-erp' ); ?></th>
                                <th><?php esc_html_e( 'Destination', 'ifs-travel-erp' ); ?></th>
                                <th><?php esc_html_e( 'Delivery Date', 'ifs-travel-erp' ); ?></th>
                                <th class="col-align-right"><?php esc_html_e( 'Status', 'ifs-travel-erp' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ( ! empty( $recent_visas ) ) : foreach ( $recent_visas as $v ) : 
                                $has_valid_delivery = ! empty( $v->expected_delivery ) && $v->expected_delivery !== '1970-01-01' && $v->expected_delivery !== '0000-00-00';
                            ?>
                                <tr>
                                    <td>
                                        <div class="tbl-main"><?php echo esc_html( ! empty( $v->customer_name ) ? $v->customer_name : ( ! empty( $v->passenger_name ) ? $v->passenger_name : 'Direct Client' ) ); ?></div>
                                        <div class="tbl-sub"><?php echo esc_html( $v->visa_type ); ?></div>
                                    </td>
                                    <td><strong><?php echo esc_html( $v->country ); ?></strong></td>
                                    <td><?php echo $has_valid_delivery ? esc_html( date_i18n( 'd M Y', strtotime( $v->expected_delivery ) ) ) : '—'; ?></td>
                                    <td class="col-align-right">
                                        <span class="badge badge-warning"><?php echo esc_html( $v->status ); ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; else : ?>
                                <tr><td colspan="4" class="no-records"><?php esc_html_e( 'No visa applications recorded.', 'ifs-travel-erp' ); ?></td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

    </div>

    <!-- WordPress Synced Clock Script -->
    <script>
    jQuery(document).ready(function($) {
        var offsetMs = <?php echo (float) ( $gmt_offset * 3600 * 1000 ); ?>;
        var currentUtcMs = <?php echo (int) ( time() * 1000 ); ?>;
        
        function updateClock() {
            currentUtcMs += 1000;
            var localDate = new Date(currentUtcMs + offsetMs);
            
            var hours = localDate.getUTCHours().toString().padStart(2, '0');
            var minutes = localDate.getUTCMinutes().toString().padStart(2, '0');
            var seconds = localDate.getUTCSeconds().toString().padStart(2, '0');
            
            var el = document.getElementById('ifsLiveClock');
            if (el) {
                el.textContent = hours + ':' + minutes + ':' + seconds;
            }
        }
        setInterval(updateClock, 1000);
    });
    </script>
    <?php
}