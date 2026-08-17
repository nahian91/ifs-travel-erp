<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

function ifs_terp_customer_add_edit_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'iterp_customers';
    
    $id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;
    $is_edit = ( $id > 0 );
    $message = '';

    if ( $_SERVER['REQUEST_METHOD'] == 'POST' && isset( $_POST['ifs_customer_submit'] ) ) {
        check_admin_referer( 'ifs_customer_save_action', 'ifs_customer_nonce' );

        $data = array(
            'full_name'       => sanitize_text_field( $_POST['full_name'] ),
            'mobile'          => sanitize_text_field( $_POST['mobile'] ),
            'email'           => sanitize_email( $_POST['email'] ),
            'passport_no'     => sanitize_text_field( $_POST['passport_no'] ),
            'passport_expiry' => sanitize_text_field( $_POST['passport_expiry'] ),
            'client_type'     => sanitize_text_field( $_POST['client_type'] ),
            'address'         => sanitize_textarea_field( $_POST['address'] )
        );

        if ( $is_edit ) {
            $wpdb->update( $table_name, $data, array( 'id' => $id ) );
            $message = '<div class="notice notice-success is-dismissible"><p>Customer updated successfully.</p></div>';
        } else {
            $data['created_at'] = current_time( 'mysql' );
            $wpdb->insert( $table_name, $data );
            $id = $wpdb->insert_id;
            $is_edit = true;
            $message = '<div class="notice notice-success is-dismissible"><p>New customer added successfully.</p></div>';
        }
        
        if ( function_exists('ifs_terp_log_activity') ) {
            ifs_terp_log_activity( "Saved Customer ID: #CUS-" . $id );
        }
    }

    $row = false;
    if ( $is_edit ) {
        $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE id = %d", $id ) );
    }
    ?>

    <?php echo $message; ?>

    <div class="arms-form-container" style="background:#fff; padding:25px; border-radius:8px; border:1px solid #e2e8f0; max-width: 900px; margin-top: 20px;">
        <h3 style="margin-top:0; color:#003376; border-bottom:1px solid #e2e8f0; padding-bottom:12px;">
            <?php echo $is_edit ? 'Edit Customer Profile (#CUS-' . $id . ')' : 'Add New Retail Customer'; ?>
        </h3>

        <form method="post" action="">
            <?php wp_nonce_field( 'ifs_customer_save_action', 'ifs_customer_nonce' ); ?>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                <div>
                    <label style="display:block; font-weight:600; margin-bottom:5px;">Full Name *</label>
                    <input type="text" name="full_name" required value="<?php echo $is_edit ? esc_attr($row->full_name) : ''; ?>" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:4px;" placeholder="e.g. Md. Rahim">
                </div>
                
                <div>
                    <label style="display:block; font-weight:600; margin-bottom:5px;">Mobile Number *</label>
                    <input type="text" name="mobile" required value="<?php echo $is_edit ? esc_attr($row->mobile) : ''; ?>" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:4px;" placeholder="e.g. 01711XXXXXX">
                </div>

                <div>
                    <label style="display:block; font-weight:600; margin-bottom:5px;">Email Address</label>
                    <input type="email" name="email" value="<?php echo $is_edit ? esc_attr($row->email) : ''; ?>" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:4px;" placeholder="rahim@example.com">
                </div>

                <div>
                    <label style="display:block; font-weight:600; margin-bottom:5px;">Client Type</label>
                    <select name="client_type" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:4px;">
                        <option value="Retail" <?php selected($is_edit && $row->client_type == 'Retail'); ?>>Retail</option>
                        <option value="Corporate" <?php selected($is_edit && $row->client_type == 'Corporate'); ?>>Corporate</option>
                        <option value="VIP" <?php selected($is_edit && $row->client_type == 'VIP'); ?>>VIP</option>
                    </select>
                </div>

                <div>
                    <label style="display:block; font-weight:600; margin-bottom:5px;">Passport Number</label>
                    <input type="text" name="passport_no" value="<?php echo $is_edit ? esc_attr($row->passport_no) : ''; ?>" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:4px;" placeholder="e.g. A02345678">
                </div>

                <div>
                    <label style="display:block; font-weight:600; margin-bottom:5px;">Passport Expiry Date</label>
                    <input type="date" name="passport_expiry" value="<?php echo $is_edit ? esc_attr($row->passport_expiry) : ''; ?>" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:4px;">
                </div>

                <div style="grid-column: 1 / -1;">
                    <label style="display:block; font-weight:600; margin-bottom:5px;">Address</label>
                    <textarea name="address" rows="3" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:4px;" placeholder="House, Road, Area, City..."><?php echo $is_edit ? esc_textarea($row->address) : ''; ?></textarea>
                </div>
            </div>

            <div style="margin-top: 25px; border-top:1px solid #e2e8f0; padding-top:15px; display:flex; justify-content:space-between; align-items:center;">
                <a href="<?php echo admin_url( 'admin.php?page=ifs_travel_erp&tab=customers' ); ?>" style="color:#64748b; text-decoration:none;">Cancel</a>
                <button type="submit" name="ifs_customer_submit" style="background: #003376; color: #fff; border: none; padding: 10px 20px; border-radius: 4px; font-weight: 600; cursor: pointer;">
                    Save Customer Profile
                </button>
            </div>
        </form>
    </div>
    <?php
}