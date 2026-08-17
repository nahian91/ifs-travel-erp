<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function ifs_terp_hajj_booking_add_edit_page() {
    global $wpdb;
    $table_bookings  = $wpdb->prefix . 'iterp_hajj_bookings';
    $table_customers = $wpdb->prefix . 'iterp_customers';
    $table_packages  = $wpdb->prefix . 'iterp_hajj_packages';
    
    $id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;
    $is_edit = ( $id > 0 );
    $message = '';

    if ( $_SERVER['REQUEST_METHOD'] == 'POST' && isset( $_POST['ifs_hajj_submit'] ) ) {
        check_admin_referer( 'ifs_hajj_save_action', 'ifs_hajj_nonce' );

        $buy_price  = floatval( $_POST['buy_price'] );
        $sell_price = floatval( $_POST['sell_price'] );
        $profit     = $sell_price - $buy_price;

        $data = array(
            'customer_id'        => intval( $_POST['customer_id'] ),
            'package_id'         => intval( $_POST['package_id'] ),
            'mahram_customer_id' => intval( $_POST['mahram_customer_id'] ),
            'room_sharing'       => sanitize_text_field( $_POST['room_sharing'] ),
            'brn_no'             => sanitize_text_field( $_POST['brn_no'] ),
            'mofaza_no'          => sanitize_text_field( $_POST['mofaza_no'] ),
            'visa_status'        => sanitize_text_field( $_POST['visa_status'] ),
            'buy_price'          => $buy_price,
            'sell_price'         => $sell_price,
            'profit'             => $profit,
            'status'             => sanitize_text_field( $_POST['status'] ),
            'created_by'         => get_current_user_id()
        );

        if ( $is_edit ) {
            $wpdb->update( $table_bookings, $data, array( 'id' => $id ) );
            $message = '<div class="notice notice-success is-dismissible"><p>Pilgrim booking updated successfully.</p></div>';
        } else {
            $data['created_at'] = current_time( 'mysql' );
            $wpdb->insert( $table_bookings, $data );
            $id = $wpdb->insert_id;
            $is_edit = true;
            $message = '<div class="notice notice-success is-dismissible"><p>New pilgrim booking added successfully.</p></div>';
        }

        if ( function_exists('ifs_terp_log_activity') ) {
            ifs_terp_log_activity( "Saved Hajj/Umrah Booking (ID: #HB-$id)" );
        }
    }

    $row = false;
    if ( $is_edit ) {
        $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_bookings WHERE id = %d", $id ) );
    }

    $customers = $wpdb->get_results( "SELECT id, full_name, mobile, passport_no FROM $table_customers ORDER BY full_name ASC" );
    $packages  = $wpdb->get_results( "SELECT id, package_name, package_type, cost_bdt FROM $table_packages ORDER BY package_name ASC" );
    ?>

    <?php echo $message; ?>

    <div class="arms-form-container" style="background:#fff; padding:25px; border-radius:8px; border:1px solid #e2e8f0; max-width: 900px; margin-top: 20px;">
        <h3 style="margin-top:0; color:#003376; border-bottom:1px solid #e2e8f0; padding-bottom:12px;">
            <?php echo $is_edit ? 'Edit Pilgrim Booking (#HB-' . $id . ')' : 'Register Pilgrim (Hajj & Umrah Booking)'; ?>
        </h3>

        <form method="post" action="">
            <?php wp_nonce_field( 'ifs_hajj_save_action', 'ifs_hajj_nonce' ); ?>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px;">
                
                <div>
                    <label style="display:block; font-weight:600; margin-bottom:5px;">Select Pilgrim (Customer) *</label>
                    <select name="customer_id" required style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:4px;">
                        <option value="">-- Select Pilgrim --</option>
                        <?php foreach ( $customers as $cus ) : ?>
                            <option value="<?php echo $cus->id; ?>" <?php selected( $is_edit && $row->customer_id == $cus->id ); ?>>
                                <?php echo esc_html( $cus->full_name . ' | Pass: ' . ($cus->passport_no ?: 'N/A') ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label style="display:block; font-weight:600; margin-bottom:5px;">Hajj / Umrah Package *</label>
                    <select name="package_id" required style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:4px;">
                        <option value="">-- Choose Package --</option>
                        <?php foreach ( $packages as $pkg ) : ?>
                            <option value="<?php echo $pkg->id; ?>" <?php selected( $is_edit && $row->package_id == $pkg->id ); ?>>
                                <?php echo esc_html( $pkg->package_name . ' (' . $pkg->package_type . ')' ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label style="display:block; font-weight:600; margin-bottom:5px;">Mahram Relation (Optional)</label>
                    <select name="mahram_customer_id" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:4px;">
                        <option value="0">-- None / Self / Male --</option>
                        <?php foreach ( $customers as $cus ) : ?>
                            <option value="<?php echo $cus->id; ?>" <?php selected( $is_edit && $row->mahram_customer_id == $cus->id ); ?>>
                                <?php echo esc_html( $cus->full_name . ' (' . $cus->mobile . ')' ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label style="display:block; font-weight:600; margin-bottom:5px;">Room Sharing Plan *</label>
                    <select name="room_sharing" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:4px;">
                        <option value="Quad" <?php selected($is_edit && $row->room_sharing == 'Quad'); ?>>Quad (4 Persons)</option>
                        <option value="Triple" <?php selected($is_edit && $row->room_sharing == 'Triple'); ?>>Triple (3 Persons)</option>
                        <option value="Double" <?php selected($is_edit && $row->room_sharing == 'Double'); ?>>Double (2 Persons)</option>
                        <option value="Single" <?php selected($is_edit && $row->room_sharing == 'Single'); ?>>Single Room</option>
                    </select>
                </div>

                <div>
                    <label style="display:block; font-weight:600; margin-bottom:5px;">Saudi BRN Number</label>
                    <input type="text" name="brn_no" value="<?php echo $is_edit ? esc_attr($row->brn_no) : ''; ?>" placeholder="e.g. BRN-884920" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:4px;">
                </div>

                <div>
                    <label style="display:block; font-weight:600; margin-bottom:5px;">Mofaza Number</label>
                    <input type="text" name="mofaza_no" value="<?php echo $is_edit ? esc_attr($row->mofaza_no) : ''; ?>" placeholder="e.g. MOF-1029384" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:4px;">
                </div>

                <div>
                    <label style="display:block; font-weight:600; margin-bottom:5px;">Visa Status</label>
                    <select name="visa_status" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:4px;">
                        <option value="Pending" <?php selected($is_edit && $row->visa_status == 'Pending'); ?>>Pending</option>
                        <option value="Submitted" <?php selected($is_edit && $row->visa_status == 'Submitted'); ?>>Submitted</option>
                        <option value="Issued" <?php selected($is_edit && $row->visa_status == 'Issued'); ?>>Issued</option>
                        <option value="Rejected" <?php selected($is_edit && $row->visa_status == 'Rejected'); ?>>Rejected</option>
                    </select>
                </div>

                <div>
                    <label style="display:block; font-weight:600; margin-bottom:5px;">Booking Status</label>
                    <select name="status" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:4px;">
                        <option value="Booked" <?php selected($is_edit && $row->status == 'Booked'); ?>>Booked</option>
                        <option value="Confirmed" <?php selected($is_edit && $row->status == 'Confirmed'); ?>>Confirmed</option>
                        <option value="Completed" <?php selected($is_edit && $row->status == 'Completed'); ?>>Completed</option>
                        <option value="Cancelled" <?php selected($is_edit && $row->status == 'Cancelled'); ?>>Cancelled</option>
                    </select>
                </div>

                <div>
                    <label style="display:block; font-weight:600; margin-bottom:5px;">Total Net Cost (৳) *</label>
                    <input type="number" step="0.01" name="buy_price" id="hajj_buy" required value="<?php echo $is_edit ? esc_attr($row->buy_price) : ''; ?>" placeholder="0.00" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:4px;">
                </div>

                <div>
                    <label style="display:block; font-weight:600; margin-bottom:5px;">Package Selling Price (৳) *</label>
                    <input type="number" step="0.01" name="sell_price" id="hajj_sell" required value="<?php echo $is_edit ? esc_attr($row->sell_price) : ''; ?>" placeholder="0.00" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:4px;">
                </div>

                <div>
                    <label style="display:block; font-weight:600; margin-bottom:5px;">Profit Margin (৳)</label>
                    <input type="text" id="hajj_profit" readonly value="<?php echo $is_edit ? number_format($row->profit, 2) : '0.00'; ?>" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:4px; font-weight:bold; background:#f8fafc;">
                </div>

            </div>

            <div style="margin-top: 25px; border-top:1px solid #e2e8f0; padding-top:15px; display:flex; justify-content:space-between; align-items:center;">
                <a href="<?php echo admin_url( 'admin.php?page=ifs_travel_erp&tab=hajj_umrah' ); ?>" style="color:#64748b; text-decoration:none;">Cancel</a>
                <button type="submit" name="ifs_hajj_submit" style="background: #003376; color: #fff; border: none; padding: 10px 20px; border-radius: 4px; font-weight: 600; cursor: pointer;">
                    Save Pilgrim Booking
                </button>
            </div>
        </form>
    </div>

    <script>
        jQuery(document).ready(function($) {
            function updateHajjProfit() {
                var buy = parseFloat($('#hajj_buy').val()) || 0;
                var sell = parseFloat($('#hajj_sell').val()) || 0;
                var profit = sell - buy;
                $('#hajj_profit').val(profit.toFixed(2));
                if(profit < 0) {
                    $('#hajj_profit').css({'background-color': '#fee2e2', 'color': '#dc2626'});
                } else {
                    $('#hajj_profit').css({'background-color': '#dcfce7', 'color': '#166534'});
                }
            }
            $('#hajj_buy, #hajj_sell').on('input', updateHajjProfit);
        });
    </script>
    <?php
}