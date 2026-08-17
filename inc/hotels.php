<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function ifs_terp_hotels_tab() {
    global $wpdb;
    $table_hotels    = $wpdb->prefix . 'iterp_hotel_bookings';
    $table_customers = $wpdb->prefix . 'iterp_customers';

    $message = '';

    if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['ifs_hotel_submit'] ) ) {
        check_admin_referer( 'ifs_hotel_action', 'ifs_hotel_nonce' );

        $buy_price  = floatval( $_POST['buy_price'] );
        $sell_price = floatval( $_POST['sell_price'] );
        $profit     = $sell_price - $buy_price;

        $wpdb->insert(
            $table_hotels,
            array(
                'customer_id' => intval( $_POST['customer_id'] ),
                'hotel_name'  => sanitize_text_field( $_POST['hotel_name'] ),
                'city'        => sanitize_text_field( $_POST['city'] ),
                'check_in'    => sanitize_text_field( $_POST['check_in'] ),
                'check_out'   => sanitize_text_field( $_POST['check_out'] ),
                'room_type'   => sanitize_text_field( $_POST['room_type'] ),
                'voucher_no'  => sanitize_text_field( $_POST['voucher_no'] ),
                'buy_price'   => $buy_price,
                'sell_price'  => $sell_price,
                'profit'      => $profit,
                'status'      => sanitize_text_field( $_POST['status'] ),
                'created_by'  => get_current_user_id(),
                'created_at'  => current_time( 'mysql' )
            ),
            array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%f', '%f', '%s', '%d', '%s' )
        );

        if ( function_exists( 'ifs_terp_log_activity' ) ) {
            ifs_terp_log_activity( "Reserved Hotel: " . sanitize_text_field( $_POST['hotel_name'] ) );
        }

        $message = '<div class="notice notice-success is-dismissible"><p>Hotel reservation recorded successfully.</p></div>';
    }

    $customers = $wpdb->get_results( "SELECT id, full_name, mobile FROM $table_customers ORDER BY full_name ASC" );
    $bookings  = $wpdb->get_results( "
        SELECT h.*, c.full_name, c.mobile 
        FROM $table_hotels h 
        LEFT JOIN $table_customers c ON h.customer_id = c.id 
        ORDER BY h.id DESC
    " );
    ?>
    <div class="ifs-terp-module-wrapper" style="padding: 20px; max-width: 1200px;">
        <h2 style="margin: 0 0 20px 0; font-size: 22px; font-weight: 700; color: #0f172a;">Hotel & Resort Reservations</h2>

        <?php echo $message; ?>

        <div style="background: #fff; padding: 25px; border-radius: 8px; border: 1px solid #e2e8f0; margin-bottom: 25px;">
            <h3 style="margin-top: 0; color: #003376; font-size: 15px; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px;">Issue Hotel Voucher / Reservation</h3>
            <form method="post" action="">
                <?php wp_nonce_field( 'ifs_hotel_action', 'ifs_hotel_nonce' ); ?>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 15px;">
                    <div>
                        <label style="display:block; font-weight:600; font-size:13px; margin-bottom:5px;">Select Guest / Client *</label>
                        <select name="customer_id" required style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:4px;">
                            <option value="">-- Choose Client --</option>
                            <?php foreach ( $customers as $cus ) : ?>
                                <option value="<?php echo $cus->id; ?>"><?php echo esc_html( $cus->full_name . ' (' . $cus->mobile . ')' ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label style="display:block; font-weight:600; font-size:13px; margin-bottom:5px;">Hotel / Resort Name *</label>
                        <input type="text" name="hotel_name" required placeholder="e.g. Swissotel Makkah / Sea Pearl Cox's Bazar" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:4px;">
                    </div>
                    <div>
                        <label style="display:block; font-weight:600; font-size:13px; margin-bottom:5px;">City / Country *</label>
                        <input type="text" name="city" required placeholder="e.g. Makkah / Dubai / Bangkok" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:4px;">
                    </div>
                    <div>
                        <label style="display:block; font-weight:600; font-size:13px; margin-bottom:5px;">Room Type</label>
                        <input type="text" name="room_type" placeholder="e.g. Deluxe Double / 1 Quad Room" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:4px;">
                    </div>
                    <div>
                        <label style="display:block; font-weight:600; font-size:13px; margin-bottom:5px;">Check-in Date *</label>
                        <input type="date" name="check_in" required value="<?php echo date('Y-m-d'); ?>" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:4px;">
                    </div>
                    <div>
                        <label style="display:block; font-weight:600; font-size:13px; margin-bottom:5px;">Check-out Date *</label>
                        <input type="date" name="check_out" required value="<?php echo date('Y-m-d', strtotime('+3 days')); ?>" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:4px;">
                    </div>
                    <div>
                        <label style="display:block; font-weight:600; font-size:13px; margin-bottom:5px;">Supplier Cost (৳) *</label>
                        <input type="number" step="0.01" name="buy_price" required placeholder="0.00" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:4px;">
                    </div>
                    <div>
                        <label style="display:block; font-weight:600; font-size:13px; margin-bottom:5px;">Client Sell Price (৳) *</label>
                        <input type="number" step="0.01" name="sell_price" required placeholder="0.00" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:4px; font-weight:bold;">
                    </div>
                    <div>
                        <label style="display:block; font-weight:600; font-size:13px; margin-bottom:5px;">Booking Voucher No</label>
                        <input type="text" name="voucher_no" placeholder="e.g. HTL-998231" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:4px;">
                    </div>
                    <div>
                        <label style="display:block; font-weight:600; font-size:13px; margin-bottom:5px;">Status</label>
                        <select name="status" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:4px;">
                            <option value="Confirmed">Confirmed</option>
                            <option value="Reserved">Reserved</option>
                            <option value="Cancelled">Cancelled</option>
                        </select>
                    </div>
                </div>
                <div style="margin-top: 15px; text-align: right;">
                    <button type="submit" name="ifs_hotel_submit" style="background: #003376; color: #fff; border: none; padding: 10px 20px; border-radius: 4px; font-weight: 600; cursor: pointer;">Save Hotel Booking</button>
                </div>
            </form>
        </div>

        <div style="background: #fff; padding: 20px; border-radius: 8px; border: 1px solid #e2e8f0;">
            <table class="arms-pricing-table" id="ifsHotelsTable" style="width:100%; border-collapse: collapse;">
                <thead>
                    <tr style="background:#f8fafc; border-bottom: 2px solid #e2e8f0; text-align: left;">
                        <th style="padding: 10px;">ID</th>
                        <th style="padding: 10px;">Guest</th>
                        <th style="padding: 10px;">Hotel Name & City</th>
                        <th style="padding: 10px;">Stay Dates</th>
                        <th style="padding: 10px;">Voucher No</th>
                        <th style="padding: 10px; text-align: right;">Sell Price (৳)</th>
                        <th style="padding: 10px; text-align: right;">Profit (৳)</th>
                        <th style="padding: 10px; text-align: center;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( $bookings ) : foreach ( $bookings as $h ) : ?>
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 10px;"><strong>#HT-<?php echo esc_html( $h->id ); ?></strong></td>
                            <td style="padding: 10px; font-weight: 600;"><?php echo esc_html( $h->full_name ?: 'Direct' ); ?></td>
                            <td style="padding: 10px; color: #003376; font-weight: 700;"><?php echo esc_html( $h->hotel_name ); ?> <div style="font-size:11px; color:#64748b; font-weight:normal;"><?php echo esc_html( $h->city ); ?> (<?php echo esc_html( $h->room_type ); ?>)</div></td>
                            <td style="padding: 10px; font-size: 12px;"><?php echo date( 'd M Y', strtotime( $h->check_in ) ); ?> - <?php echo date( 'd M Y', strtotime( $h->check_out ) ); ?></td>
                            <td style="padding: 10px; font-family: monospace; font-weight: 600;"><?php echo esc_html( $h->voucher_no ?: '-' ); ?></td>
                            <td style="padding: 10px; text-align: right; font-weight: 700;">৳<?php echo number_format( $h->sell_price, 2 ); ?></td>
                            <td style="padding: 10px; text-align: right; font-weight: 700; color: #166534;">৳<?php echo number_format( $h->profit, 2 ); ?></td>
                            <td style="padding: 10px; text-align: center;"><span style="background: #dcfce7; color: #166534; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 700;"><?php echo esc_html( $h->status ); ?></span></td>
                        </tr>
                    <?php endforeach; else : ?>
                        <tr><td colspan="8" style="text-align: center; padding: 20px;">No hotel reservations found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <script>
        jQuery(document).ready(function($) {
            if ($.fn.DataTable) {
                $('#ifsHotelsTable').DataTable({ "pageLength": 15, "order": [[ 0, "desc" ]] });
            }
        });
    </script>
    <?php
}