<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

function ifs_terp_visa_requirements_page() {
    global $wpdb;
    $table_reqs = $wpdb->prefix . 'iterp_visa_requirements';

    $action_sub = isset( $_GET['action_sub'] ) ? sanitize_text_field( $_GET['action_sub'] ) : '';
    $req_id     = isset( $_GET['req_id'] ) ? intval( $_GET['req_id'] ) : 0;
    $message    = '';

    /* =========================================================================
       1. DELETE REQUIREMENT RECORD
       ========================================================================= */
    if ( $action_sub === 'delete' && $req_id > 0 ) {
        if ( isset( $_GET['_wpnonce'] ) && wp_verify_nonce( $_GET['_wpnonce'], 'delete_visa_req_' . $req_id ) ) {
            $wpdb->delete( $table_reqs, array( 'id' => $req_id ), array( '%d' ) );
            if ( function_exists( 'ifs_terp_log_activity' ) ) {
                ifs_terp_log_activity( "Deleted Visa Requirement Config ID: #" . $req_id );
            }
            $message = '<div class="notice notice-success is-dismissible"><p>Country requirement removed successfully.</p></div>';
        } else {
            $message = '<div class="notice notice-error is-dismissible"><p>Security token verification failed.</p></div>';
        }
    }

    /* =========================================================================
       2. FORM SUBMIT HANDLER (ADD / UPDATE)
       ========================================================================= */
    if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['ifs_visa_req_submit'] ) ) {
        check_admin_referer( 'ifs_visa_req_nonce_action', 'ifs_visa_req_nonce' );

        $is_edit_mode = ( isset( $_POST['edit_req_id'] ) && intval( $_POST['edit_req_id'] ) > 0 );
        $edit_id      = $is_edit_mode ? intval( $_POST['edit_req_id'] ) : 0;

        $country_name      = sanitize_text_field( $_POST['country_name'] );
        $visa_type         = sanitize_text_field( $_POST['visa_type'] );
        $processing_time   = sanitize_text_field( $_POST['processing_time'] );
        $standard_fee      = floatval( $_POST['standard_fee'] );
        $requirements_list = sanitize_textarea_field( $_POST['requirements_list'] );

        $data_array = array(
            'country_name'      => $country_name,
            'visa_type'         => $visa_type,
            'processing_time'   => $processing_time,
            'standard_fee'      => $standard_fee,
            'requirements_list' => $requirements_list,
        );

        if ( $is_edit_mode ) {
            $wpdb->update( $table_reqs, $data_array, array( 'id' => $edit_id ) );
            if ( function_exists( 'ifs_terp_log_activity' ) ) {
                ifs_terp_log_activity( "Updated Visa Requirement for: " . $country_name . " (ID: #" . $edit_id . ")" );
            }
            $message = '<div class="notice notice-success is-dismissible"><p>Visa requirement updated successfully.</p></div>';
        } else {
            $data_array['created_at'] = current_time( 'mysql' );
            $wpdb->insert( $table_reqs, $data_array );
            $new_id = $wpdb->insert_id;
            if ( function_exists( 'ifs_terp_log_activity' ) ) {
                ifs_terp_log_activity( "Added New Visa Requirement for: " . $country_name . " (ID: #" . $new_id . ")" );
            }
            $message = '<div class="notice notice-success is-dismissible"><p>New visa requirement added successfully.</p></div>';
        }
    }

    /* =========================================================================
       3. FETCH EDIT RECORD IF REQUESTED
       ========================================================================= */
    $edit_data = false;
    if ( $action_sub === 'edit' && $req_id > 0 ) {
        $edit_data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_reqs WHERE id = %d", $req_id ) );
    }

    // Fetch all requirements
    $all_reqs = $wpdb->get_results( "SELECT * FROM $table_reqs ORDER BY country_name ASC" );
    ?>

    <?php echo $message; ?>

    <div class="arms-card-panel" style="margin-top: 20px;">
        <div class="arms-p-header" style="padding: 15px 20px; margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center;">
            <h2 style="font-size: 18px; margin: 0;">Visa Requirements & Embassy Fees Directory</h2>
        </div>

        <!-- Add / Edit Form Card -->
        <div style="background: #fff; padding: 25px; border: 1px solid #e2e8f0; border-radius: 8px; margin-bottom: 30px;">
            <h3 style="margin-top: 0; color: #003376; font-size: 16px; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px;">
                <span class="dashicons dashicons-edit-page" style="vertical-align: middle;"></span>
                <?php echo $edit_data ? 'Edit Requirement: ' . esc_html( $edit_data->country_name ) : 'Configure New Country Requirement'; ?>
            </h3>

            <form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=ifs_travel_erp&tab=visa&sub=requirements' ) ); ?>">
                <?php wp_nonce_field( 'ifs_visa_req_nonce_action', 'ifs_visa_req_nonce' ); ?>
                
                <?php if ( $edit_data ) : ?>
                    <input type="hidden" name="edit_req_id" value="<?php echo esc_attr( $edit_data->id ); ?>">
                <?php endif; ?>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px;">
                    <div>
                        <label style="display:block; font-weight:600; font-size:13px; margin-bottom:5px; color:#1e293b;">Country Name *</label>
                        <input type="text" name="country_name" required value="<?php echo $edit_data ? esc_attr( $edit_data->country_name ) : ''; ?>" placeholder="e.g. United Kingdom / UAE / Singapore" style="width:100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 4px;">
                    </div>

                    <div>
                        <label style="display:block; font-weight:600; font-size:13px; margin-bottom:5px; color:#1e293b;">Visa Type / Category *</label>
                        <input type="text" name="visa_type" required value="<?php echo $edit_data ? esc_attr( $edit_data->visa_type ) : ''; ?>" placeholder="e.g. Standard Tourist / Business / E-Visa" style="width:100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 4px;">
                    </div>

                    <div>
                        <label style="display:block; font-weight:600; font-size:13px; margin-bottom:5px; color:#1e293b;">Standard Processing Time</label>
                        <input type="text" name="processing_time" value="<?php echo $edit_data ? esc_attr( $edit_data->processing_time ) : ''; ?>" placeholder="e.g. 7-10 Working Days" style="width:100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 4px;">
                    </div>

                    <div>
                        <label style="display:block; font-weight:600; font-size:13px; margin-bottom:5px; color:#1e293b;">Govt / VFS Standard Fee (৳) *</label>
                        <input type="number" step="0.01" name="standard_fee" required value="<?php echo $edit_data ? esc_attr( $edit_data->standard_fee ) : ''; ?>" placeholder="0.00" style="width:100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 4px;">
                    </div>

                    <div style="grid-column: 1 / -1;">
                        <label style="display:block; font-weight:600; font-size:13px; margin-bottom:5px; color:#1e293b;">Required Documents Checklist *</label>
                        <textarea name="requirements_list" required rows="4" placeholder="1. Original Passport (Min 6 months validity)&#10;2. 2 Copies Photo (35x45mm white background)&#10;3. Bank Statement & Solvency (Last 6 months)&#10;4. Trade License (Translated & Notarized) or NOC for Job Holder" style="width:100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 4px;"><?php echo $edit_data ? esc_textarea( $edit_data->requirements_list ) : ''; ?></textarea>
                    </div>
                </div>

                <div style="margin-top: 20px; border-top: 1px solid #e2e8f0; padding-top: 15px; display: flex; justify-content: space-between; align-items: center;">
                    <?php if ( $edit_data ) : ?>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=ifs_travel_erp&tab=visa&sub=requirements' ) ); ?>" style="color: #64748b; text-decoration: none;">Cancel Edit</a>
                    <?php else : ?>
                        <span></span>
                    <?php endif; ?>
                    <button type="submit" name="ifs_visa_req_submit" style="background: #003376; color: #fff; border: none; padding: 10px 22px; border-radius: 4px; cursor: pointer; font-weight: 600;">
                        <?php echo $edit_data ? 'Update Requirement' : 'Save Requirement Entry'; ?>
                    </button>
                </div>
            </form>
        </div>

        <!-- Requirements List Table -->
        <div class="arms-table-responsive">
            <table class="arms-pricing-table" id="ifsVisaReqTable" style="width:100%;">
                <thead>
                    <tr>
                        <th style="width: 15%;">Country</th>
                        <th style="width: 15%;">Visa Type</th>
                        <th style="width: 15%;">Processing Time</th>
                        <th style="width: 12%; text-align: right;">Standard Fee (৳)</th>
                        <th style="width: 33%;">Required Documents Checklist</th>
                        <th style="width: 10%; text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( $all_reqs ) : foreach ( $all_reqs as $req ) : ?>
                        <tr>
                            <td style="font-weight: 700; color: #0f172a;"><?php echo esc_html( $req->country_name ); ?></td>
                            <td><span style="background: #e0f2fe; color: #0369a1; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 600;"><?php echo esc_html( $req->visa_type ); ?></span></td>
                            <td style="font-size: 13px; color: #475569;"><?php echo esc_html( $req->processing_time ?: '-' ); ?></td>
                            <td style="text-align: right; font-weight: 700; color: #0f172a;">৳<?php echo number_format( $req->standard_fee, 2 ); ?></td>
                            <td style="font-size: 12px; color: #334155; line-height: 1.5;"><?php echo nl2br( esc_html( $req->requirements_list ) ); ?></td>
                            <td style="text-align: right;">
                                <div style="display: flex; gap: 5px; justify-content: flex-end;">
                                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=ifs_travel_erp&tab=visa&sub=requirements&action_sub=edit&req_id=' . $req->id ) ); ?>" style="background:#f1f5f9; padding:4px 8px; border-radius:4px; text-decoration:none; color:#2563eb;" title="Edit">
                                        <span class="dashicons dashicons-edit" style="font-size:14px; vertical-align:middle;"></span>
                                    </a>
                                    <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=ifs_travel_erp&tab=visa&sub=requirements&action_sub=delete&req_id=' . $req->id ), 'delete_visa_req_' . $req->id ) ); ?>" style="background:#fef2f2; padding:4px 8px; border-radius:4px; text-decoration:none; color:#dc2626;" onclick="return confirm('Are you sure you want to delete this country requirement?');" title="Delete">
                                        <span class="dashicons dashicons-trash" style="font-size:14px; vertical-align:middle;"></span>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; else : ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 25px; color: #64748b;">No visa requirements configured yet. Use the form above to add your first country requirement.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        jQuery(document).ready(function($) {
            $('#ifsVisaReqTable').DataTable({
                "pageLength": 15,
                "ordering": true,
                "order": [[ 0, "asc" ]],
                "info": true,
                "searching": true
            });
        });
    </script>
    <?php
}