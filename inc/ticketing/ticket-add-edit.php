<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function ifs_terp_ticket_add_edit_page() {
    global $wpdb;
    $table_tickets   = $wpdb->prefix . 'iterp_tickets';
    $table_customers = $wpdb->prefix . 'iterp_customers';
    
    $id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;
    $is_edit = ( $id > 0 );
    $message = '';

    if ( $_SERVER['REQUEST_METHOD'] == 'POST' && isset( $_POST['ifs_ticket_submit'] ) ) {
        check_admin_referer( 'ifs_ticket_save_action', 'ifs_ticket_nonce' );

        $buy_price  = floatval( $_POST['buy_price'] );
        $sell_price = floatval( $_POST['sell_price'] );
        $profit     = $sell_price - $buy_price;

        $data = array(
            'customer_id' => intval( $_POST['customer_id'] ),
            'pnr'         => strtoupper( sanitize_text_field( $_POST['pnr'] ) ),
            'ticket_no'   => sanitize_text_field( $_POST['ticket_no'] ),
            'airline'     => sanitize_text_field( $_POST['airline'] ),
            'sector'      => strtoupper( sanitize_text_field( $_POST['sector'] ) ),
            'cabin_class' => sanitize_text_field( $_POST['cabin_class'] ),
            'travel_date' => sanitize_text_field( $_POST['travel_date'] ),
            'buy_price'   => $buy_price,
            'sell_price'  => $sell_price,
            'profit'      => $profit,
            'status'      => sanitize_text_field( $_POST['status'] ),
            'issued_by'   => get_current_user_id()
        );

        if ( $is_edit ) {
            $updated = $wpdb->update( $table_tickets, $data, array( 'id' => $id ) );
            if ( $updated !== false ) {
                $message = '<div class="notice notice-success is-dismissible"><p>Ticket updated successfully.</p></div>';
            } else {
                $message = '<div class="notice notice-error is-dismissible"><p>Update failed. Duplicate PNR or Ticket Number.</p></div>';
            }
        } else {
            $data['created_at'] = current_time( 'mysql' );
            $inserted = $wpdb->insert( $table_tickets, $data );
            if ( $inserted ) {
                $id = $wpdb->insert_id;
                $is_edit = true;
                $message = '<div class="notice notice-success is-dismissible"><p>Ticket issued successfully.</p></div>';
            } else {
                $message = '<div class="notice notice-error is-dismissible"><p>Failed to issue ticket. PNR or Ticket No already exists.</p></div>';
            }
        }

        if ( function_exists('ifs_terp_log_activity') ) {
            ifs_terp_log_activity( "Processed Ticket PNR: " . $data['pnr'] . " (ID: #TKT-$id)" );
        }
    }

    $row = false;
    if ( $is_edit ) {
        $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_tickets WHERE id = %d", $id ) );
    }

    $customers = $wpdb->get_results( "SELECT id, full_name, mobile FROM $table_customers ORDER BY full_name ASC" );
    ?>

    <?php echo $message; ?>

    <div class="arms-form-container" style="background:#fff; padding:25px; border-radius:8px; border:1px solid #e2e8f0; max-width: 900px; margin-top: 20px;">
        <h3 style="margin-top:0; color:#003376; border-bottom:1px solid #e2e8f0; padding-bottom:12px;">
            <?php echo $is_edit ? 'Edit Ticket Record (#TKT-' . $id . ')' : 'Issue New Air Ticket'; ?>
        </h3>

        <form method="post" action="">
            <?php wp_nonce_field( 'ifs_ticket_save_action', 'ifs_ticket_nonce' ); ?>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px;">
                
                <div style="grid-column: 1 / -1;">
                    <label style="display:block; font-weight:600; margin-bottom:5px;">Select Passenger / Client *</label>
                    <select name="customer_id" required style="width:100%; max-width:400px; padding:8px; border:1px solid #cbd5e1; border-radius:4px;">
                        <option value="">-- Choose Client --</option>
                        <?php foreach ( $customers as $cus ) : ?>
                            <option value="<?php echo $cus->id; ?>" <?php selected( $is_edit && $row->customer_id == $cus->id ); ?>>
                                <?php echo esc_html( $cus->full_name . ' (' . $cus->mobile . ')' ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label style="display:block; font-weight:600; margin-bottom:5px;">PNR / Booking Reference *</label>
                    <input type="text" name="pnr" required value="<?php echo $is_edit ? esc_attr($row->pnr) : ''; ?>" placeholder="e.g. 6QWE89" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:4px; text-transform:uppercase;">
                </div>

                <div>
                    <label style="display:block; font-weight:600; margin-bottom:5px;">E-Ticket Number *</label>
                    <input type="text" name="ticket_no" required value="<?php echo $is_edit ? esc_attr($row->ticket_no) : ''; ?>" placeholder="e.g. 077-1234567890" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:4px;">
                </div>

                <div>
                    <label style="display:block; font-weight:600; margin-bottom:5px;">Airlines Name *</label>
                    <input type="text" name="airline" required value="<?php echo $is_edit ? esc_attr($row->airline) : ''; ?>" placeholder="e.g. Biman Bangladesh Airlines" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:4px;">
                </div>

                <div>
                    <label style="display:block; font-weight:600; margin-bottom:5px;">Sector / Routing *</label>
                    <input type="text" name="sector" required value="<?php echo $is_edit ? esc_attr($row->sector) : ''; ?>" placeholder="e.g. DAC-DXB-LHR" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:4px; text-transform:uppercase;">
                </div>

                <div>
                    <label style="display:block; font-weight:600; margin-bottom:5px;">Cabin Class</label>
                    <select name="cabin_class" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:4px;">
                        <option value="Economy" <?php selected($is_edit && $row->cabin_class == 'Economy'); ?>>Economy Class</option>
                        <option value="Premium Economy" <?php selected($is_edit && $row->cabin_class == 'Premium Economy'); ?>>Premium Economy</option>
                        <option value="Business" <?php selected($is_edit && $row->cabin_class == 'Business'); ?>>Business Class</option>
                        <option value="First" <?php selected($is_edit && $row->cabin_class == 'First'); ?>>First Class</option>
                    </select>
                </div>

                <div>
                    <label style="display:block; font-weight:600; margin-bottom:5px;">Travel Date *</label>
                    <input type="date" name="travel_date" required value="<?php echo $is_edit ? esc_attr($row->travel_date) : ''; ?>" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:4px;">
                </div>

                <div>
                    <label style="display:block; font-weight:600; margin-bottom:5px;">Supplier / GDS Buy Price (৳) *</label>
                    <input type="number" step="0.01" name="buy_price" id="ifs_buy_price" required value="<?php echo $is_edit ? esc_attr($row->buy_price) : ''; ?>" placeholder="0.00" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:4px;">
                </div>

                <div>
                    <label style="display:block; font-weight:600; margin-bottom:5px;">Customer Sell Price (৳) *</label>
                    <input type="number" step="0.01" name="sell_price" id="ifs_sell_price" required value="<?php echo $is_edit ? esc_attr($row->sell_price) : ''; ?>" placeholder="0.00" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:4px;">
                </div>

                <div>
                    <label style="display:block; font-weight:600; margin-bottom:5px;">Gross Profit Margin (৳)</label>
                    <input type="text" id="ifs_profit_display" readonly value="<?php echo $is_edit ? number_format($row->profit, 2) : '0.00'; ?>" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:4px; font-weight:bold; background:#f8fafc;">
                </div>

                <div>
                    <label style="display:block; font-weight:600; margin-bottom:5px;">Ticket Status</label>
                    <select name="status" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:4px;">
                        <option value="Issued" <?php selected($is_edit && $row->status == 'Issued'); ?>>Issued</option>
                        <option value="Refunded" <?php selected($is_edit && $row->status == 'Refunded'); ?>>Refunded</option>
                        <option value="Void" <?php selected($is_edit && $row->status == 'Void'); ?>>Void</option>
                    </select>
                </div>

            </div>

            <div style="margin-top: 25px; border-top:1px solid #e2e8f0; padding-top:15px; display:flex; justify-content:space-between; align-items:center;">
                <a href="<?php echo admin_url( 'admin.php?page=ifs_travel_erp&tab=ticketing' ); ?>" style="color:#64748b; text-decoration:none;">Cancel</a>
                <button type="submit" name="ifs_ticket_submit" style="background: #003376; color: #fff; border: none; padding: 10px 20px; border-radius: 4px; font-weight: 600; cursor: pointer;">
                    Save Ticket Record
                </button>
            </div>
        </form>
    </div>

    <script>
        jQuery(document).ready(function($) {
            function updateProfit() {
                var buy = parseFloat($('#ifs_buy_price').val()) || 0;
                var sell = parseFloat($('#ifs_sell_price').val()) || 0;
                var profit = sell - buy;
                $('#ifs_profit_display').val(profit.toFixed(2));
                if(profit < 0) {
                    $('#ifs_profit_display').css({'background-color': '#fee2e2', 'color': '#dc2626'});
                } else {
                    $('#ifs_profit_display').css({'background-color': '#dcfce7', 'color': '#166534'});
                }
            }
            $('#ifs_buy_price, #ifs_sell_price').on('input', updateProfit);
        });
    </script>
    <?php
}