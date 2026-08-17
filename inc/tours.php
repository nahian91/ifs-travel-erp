<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function ifs_terp_tours_tab() {
    global $wpdb;
    $table_tours     = $wpdb->prefix . 'iterp_tours';
    $table_customers = $wpdb->prefix . 'iterp_customers';

    $message = '';

    // Handle Add Tour Booking
    if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['ifs_tour_submit'] ) ) {
        check_admin_referer( 'ifs_tour_action', 'ifs_tour_nonce' );

        $buy_price  = floatval( $_POST['buy_price'] );
        $sell_price = floatval( $_POST['sell_price'] );
        $profit     = $sell_price - $buy_price;

        $wpdb->insert(
            $table_tours,
            array(
                'customer_id'   => intval( $_POST['customer_id'] ),
                'package_title' => sanitize_text_field( $_POST['package_title'] ),
                'destination'   => sanitize_text_field( $_POST['destination'] ),
                'duration'      => sanitize_text_field( $_POST['duration'] ),
                'travel_date'   => sanitize_text_field( $_POST['travel_date'] ),
                'buy_price'     => $buy_price,
                'sell_price'    => $sell_price,
                'profit'        => $profit,
                'status'        => sanitize_text_field( $_POST['status'] ),
                'created_by'    => get_current_user_id(),
                'created_at'    => current_time( 'mysql' )
            ),
            array( '%d', '%s', '%s', '%s', '%s', '%f', '%f', '%f', '%s', '%d', '%s' )
        );

        if ( function_exists( 'ifs_terp_log_activity' ) ) {
            ifs_terp_log_activity( "Booked Tour Package: " . sanitize_text_field( $_POST['package_title'] ) );
        }

        $message = '<div class="notice notice-success is-dismissible"><p>Tour booking recorded successfully.</p></div>';
    }

    $customers = $wpdb->get_results( "SELECT id, full_name, mobile FROM $table_customers ORDER BY full_name ASC" );
    $bookings  = $wpdb->get_results( "
        SELECT t.*, c.full_name, c.mobile 
        FROM $table_tours t 
        LEFT JOIN $table_customers c ON t.customer_id = c.id 
        ORDER BY t.id DESC
    " );
    ?>
    <div class="ifs-terp-module-wrapper" style="padding: 20px; max-width: 1200px;">
        <h2 style="margin: 0 0 20px 0; font-size: 22px; font-weight: 700; color: #0f172a;">Holiday & Tour Packages Desk</h2>

        <?php echo $message; ?>

        <div style="background: #fff; padding: 25px; border-radius: 8px; border: 1px solid #e2e8f0; margin-bottom: 25px;">
            <h3 style="margin-top: 0; color: #003376; font-size: 15px; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px;">Book Holiday Tour Package</h3>
            <form method="post" action="">
                <?php wp_nonce_field( 'ifs_tour_action', 'ifs_tour_nonce' ); ?>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 15px;">
                    <div>
                        <label style="display:block; font-weight:600; font-size:13px; margin-bottom:5px;">Select Client *</label>
                        <select name="customer_id" required style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:4px;">
                            <option value="">-- Choose Client --</option>
                            <?php foreach ( $customers as $cus ) : ?>
                                <option value="<?php echo $cus->id; ?>"><?php echo esc_html( $cus->full_name . ' (' . $cus->mobile . ')' ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label style="display:block; font-weight:600; font-size:13px; margin-bottom:5px;">Package Title *</label>
                        <input type="text" name="package_title" required placeholder="e.g. 4D/3N Bangkok Holiday" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:4px;">
                    </div>
                    <div>
                        <label style="display:block; font-weight:600; font-size:13px; margin-bottom:5px;">Destination Country / City *</label>
                        <input type="text" name="destination" required placeholder="e.g. Thailand, Maldives, Cox's Bazar" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:4px;">
                    </div>
                    <div>
                        <label style="display:block; font-weight:600; font-size:13px; margin-bottom:5px;">Duration</label>
                        <input type="text" name="duration" placeholder="e.g. 4 Days, 3 Nights" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:4px;">
                    </div>
                    <div>
                        <label style="display:block; font-weight:600; font-size:13px; margin-bottom:5px;">Travel Date</label>
                        <input type="date" name="travel_date" value="<?php echo date('Y-m-d'); ?>" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:4px;">
                    </div>
                    <div>
                        <label style="display:block; font-weight:600; font-size:13px; margin-bottom:5px;">Net Cost Rate (৳) *</label>
                        <input type="number" step="0.01" name="buy_price" required placeholder="0.00" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:4px;">
                    </div>
                    <div>
                        <label style="display:block; font-weight:600; font-size:13px; margin-bottom:5px;">Selling Price (৳) *</label>
                        <input type="number" step="0.01" name="sell_price" required placeholder="0.00" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:4px; font-weight:bold;">
                    </div>
                    <div>
                        <label style="display:block; font-weight:600; font-size:13px; margin-bottom:5px;">Status</label>
                        <select name="status" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:4px;">
                            <option value="Confirmed">Confirmed</option>
                            <option value="Pending">Pending</option>
                            <option value="Cancelled">Cancelled</option>
                        </select>
                    </div>
                </div>
                <div style="margin-top: 15px; text-align: right;">
                    <button type="submit" name="ifs_tour_submit" style="background: #003376; color: #fff; border: none; padding: 10px 20px; border-radius: 4px; font-weight: 600; cursor: pointer;">Save Tour Booking</button>
                </div>
            </form>
        </div>

        <div style="background: #fff; padding: 20px; border-radius: 8px; border: 1px solid #e2e8f0;">
            <table class="arms-pricing-table" id="ifsToursTable" style="width:100%; border-collapse: collapse;">
                <thead>
                    <tr style="background:#f8fafc; border-bottom: 2px solid #e2e8f0; text-align: left;">
                        <th style="padding: 10px;">Booking ID</th>
                        <th style="padding: 10px;">Client</th>
                        <th style="padding: 10px;">Package Title</th>
                        <th style="padding: 10px;">Destination</th>
                        <th style="padding: 10px;">Travel Date</th>
                        <th style="padding: 10px; text-align: right;">Sell Price (৳)</th>
                        <th style="padding: 10px; text-align: right;">Profit (৳)</th>
                        <th style="padding: 10px; text-align: center;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( $bookings ) : foreach ( $bookings as $b ) : ?>
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 10px;"><strong>#TR-<?php echo esc_html( $b->id ); ?></strong></td>
                            <td style="padding: 10px; font-weight: 600;"><?php echo esc_html( $b->full_name ?: 'Direct' ); ?></td>
                            <td style="padding: 10px; color: #003376; font-weight: 700;"><?php echo esc_html( $b->package_title ); ?></td>
                            <td style="padding: 10px;"><?php echo esc_html( $b->destination ); ?> (<?php echo esc_html( $b->duration ); ?>)</td>
                            <td style="padding: 10px; font-size: 13px;"><?php echo date( 'd M Y', strtotime( $b->travel_date ) ); ?></td>
                            <td style="padding: 10px; text-align: right; font-weight: 700;">৳<?php echo number_format( $b->sell_price, 2 ); ?></td>
                            <td style="padding: 10px; text-align: right; font-weight: 700; color: #166534;">৳<?php echo number_format( $b->profit, 2 ); ?></td>
                            <td style="padding: 10px; text-align: center;"><span style="background: #dcfce7; color: #166534; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 700;"><?php echo esc_html( $b->status ); ?></span></td>
                        </tr>
                    <?php endforeach; else : ?>
                        <tr><td colspan="8" style="text-align: center; padding: 20px;">No tour bookings recorded yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <script>
        jQuery(document).ready(function($) {
            if ($.fn.DataTable) {
                $('#ifsToursTable').DataTable({ "pageLength": 15, "order": [[ 0, "desc" ]] });
            }
        });
    </script>
    <?php
}