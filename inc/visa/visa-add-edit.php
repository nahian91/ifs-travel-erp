<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function ifs_terp_visa_add_edit_page() {
    global $wpdb;
    $table_visas     = $wpdb->prefix . 'iterp_visas';
    $table_customers = $wpdb->prefix . 'iterp_customers';
    
    $id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;
    $is_edit = ( $id > 0 );
    $message = '';

    if ( $_SERVER['REQUEST_METHOD'] == 'POST' && isset( $_POST['ifs_visa_submit'] ) ) {
        check_admin_referer( 'ifs_visa_save_action', 'ifs_visa_nonce' );

        $buy_price  = floatval( $_POST['buy_price'] );
        $sell_price = floatval( $_POST['sell_price'] );
        $profit     = $sell_price - $buy_price;

        $data = array(
            'customer_id'         => intval( $_POST['customer_id'] ),
            'country'             => sanitize_text_field( $_POST['country'] ),
            'visa_type'           => sanitize_text_field( $_POST['visa_type'] ),
            'submission_date'     => sanitize_text_field( $_POST['submission_date'] ),
            'expected_delivery'   => sanitize_text_field( $_POST['expected_delivery'] ),
            'buy_price'           => $buy_price,
            'sell_price'          => $sell_price,
            'profit'              => $profit,
            'status'              => sanitize_text_field( $_POST['status'] ),
            'documents_collected' => sanitize_textarea_field( $_POST['documents_collected'] ),
            'created_by'          => get_current_user_id()
        );

        if ( $is_edit ) {
            $wpdb->update( $table_visas, $data, array( 'id' => $id ) );
            $message = '<div class="notice notice-success is-dismissible"><p>Visa record updated successfully.</p></div>';
        } else {
            $data['created_at'] = current_time( 'mysql' );
            $wpdb->insert( $table_visas, $data );
            $id = $wpdb->insert_id;
            $is_edit = true;
            $message = '<div class="notice notice-success is-dismissible"><p>New visa application added successfully.</p></div>';
        }

        if ( function_exists('ifs_terp_log_activity') ) {
            ifs_terp_log_activity( "Saved Visa File for: " . $data['country'] . " (ID: #VSA-$id)" );
        }
    }

    $row = false;
    if ( $is_edit ) {
        $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_visas WHERE id = %d", $id ) );
    }

    $customers = $wpdb->get_results( "SELECT id, full_name, mobile, passport_no FROM $table_customers ORDER BY full_name ASC" );
    ?>

    <?php echo $message; ?>

    <div class="arms-form-container" style="background:#fff; padding:25px; border-radius:8px; border:1px solid #e2e8f0; max-width: 900px; margin-top: 20px;">
        <h3 style="margin-top:0; color:#003376; border-bottom:1px solid #e2e8f0; padding-bottom:12px;">
            <?php echo $is_edit ? 'Edit Visa Application (#VSA-' . $id . ')' : 'Open New Visa Processing File'; ?>
        </h3>

        <form method="post" action="">
            <?php wp_nonce_field( 'ifs_visa_save_action', 'ifs_visa_nonce' ); ?>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px;">
                
                <div style="grid-column: 1 / -1;">
                    <label style="display:block; font-weight:600; margin-bottom:5px;">Select Applicant / Customer *</label>
                    <select name="customer_id" required style="width:100%; max-width:400px; padding:8px; border:1px solid #cbd5e1; border-radius:4px;">
                        <option value="">-- Choose Customer --</option>
                        <?php foreach ( $customers as $cus ) : ?>
                            <option value="<?php echo $cus->id; ?>" <?php selected( $is_edit && $row->customer_id == $cus->id ); ?>>
                                <?php echo esc_html( $cus->full_name . ' | Mob: ' . $cus->mobile . ' | Pass: ' . ($cus->passport_no ?: 'N/A') ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label style="display:block; font-weight:600; margin-bottom:5px;">Destination Country *</label>
                    <input type="text" name="country" required value="<?php echo $is_edit ? esc_attr($row->country) : ''; ?>" placeholder="e.g. Saudi Arabia / UAE / UK" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:4px;">
                </div>

                <div>
                    <label style="display:block; font-weight:600; margin-bottom:5px;">Visa Category / Type *</label>
                    <input type="text" name="visa_type" required value="<?php echo $is_edit ? esc_attr($row->visa_type) : ''; ?>" placeholder="e.g. Tourist / Business / Work / E-Visa" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:4px;">
                </div>

                <div>
                    <label style="display:block; font-weight:600; margin-bottom:5px;">Embassy Submission Date</label>
                    <input type="date" name="submission_date" value="<?php echo $is_edit ? esc_attr($row->submission_date) : ''; ?>" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:4px;">
                </div>

                <div>
                    <label style="display:block; font-weight:600; margin-bottom:5px;">Expected Delivery Date</label>
                    <input type="date" name="expected_delivery" value="<?php echo $is_edit ? esc_attr($row->expected_delivery) : ''; ?>" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:4px;">
                </div>

                <div>
                    <label style="display:block; font-weight:600; margin-bottom:5px;">Supplier / Embassy Cost (৳) *</label>
                    <input type="number" step="0.01" name="buy_price" id="vsa_buy" required value="<?php echo $is_edit ? esc_attr($row->buy_price) : ''; ?>" placeholder="0.00" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:4px;">
                </div>

                <div>
                    <label style="display:block; font-weight:600; margin-bottom:5px;">Customer Total Fee (৳) *</label>
                    <input type="number" step="0.01" name="sell_price" id="vsa_sell" required value="<?php echo $is_edit ? esc_attr($row->sell_price) : ''; ?>" placeholder="0.00" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:4px;">
                </div>

                <div>
                    <label style="display:block; font-weight:600; margin-bottom:5px;">Profit Margin (৳)</label>
                    <input type="text" id="vsa_profit" readonly value="<?php echo $is_edit ? number_format($row->profit, 2) : '0.00'; ?>" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:4px; font-weight:bold; background:#f8fafc;">
                </div>

                <div>
                    <label style="display:block; font-weight:600; margin-bottom:5px;">Current Application Status</label>
                    <select name="status" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:4px;">
                        <option value="Processing" <?php selected($is_edit && $row->status == 'Processing'); ?>>Processing (Submitted)</option>
                        <option value="Approved" <?php selected($is_edit && $row->status == 'Approved'); ?>>Approved</option>
                        <option value="Delivered" <?php selected($is_edit && $row->status == 'Delivered'); ?>>Delivered to Client</option>
                        <option value="Rejected" <?php selected($is_edit && $row->status == 'Rejected'); ?>>Rejected</option>
                    </select>
                </div>

                <div style="grid-column: 1 / -1;">
                    <label style="display:block; font-weight:600; margin-bottom:5px;">Checklist / Received Documents</label>
                    <textarea name="documents_collected" rows="3" placeholder="e.g. Original Passport, 2x White Background Photos, Bank Statement, Trade License copy..." style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:4px;"><?php echo $is_edit ? esc_textarea($row->documents_collected) : ''; ?></textarea>
                </div>

            </div>

            <div style="margin-top: 25px; border-top:1px solid #e2e8f0; padding-top:15px; display:flex; justify-content:space-between; align-items:center;">
                <a href="<?php echo admin_url( 'admin.php?page=ifs_travel_erp&tab=visa' ); ?>" style="color:#64748b; text-decoration:none;">Cancel</a>
                <button type="submit" name="ifs_visa_submit" style="background: #003376; color: #fff; border: none; padding: 10px 20px; border-radius: 4px; font-weight: 600; cursor: pointer;">
                    Save Visa File
                </button>
            </div>
        </form>
    </div>

    <script>
        jQuery(document).ready(function($) {
            function updateVisaProfit() {
                var buy = parseFloat($('#vsa_buy').val()) || 0;
                var sell = parseFloat($('#vsa_sell').val()) || 0;
                var profit = sell - buy;
                $('#vsa_profit').val(profit.toFixed(2));
                if(profit < 0) {
                    $('#vsa_profit').css({'background-color': '#fee2e2', 'color': '#dc2626'});
                } else {
                    $('#vsa_profit').css({'background-color': '#dcfce7', 'color': '#166534'});
                }
            }
            $('#vsa_buy, #vsa_sell').on('input', updateVisaProfit);
        });
    </script>
    <?php
}