<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Enterprise Ultra-Modern Visa Requirements & Embassy Fees Directory
 * Features: Live Country Card Preview, Checklist Vault, Real-time Search, Clean Integrated Layout
 */
function ifs_terp_visa_requirements_page() {
    global $wpdb;
    $table_reqs = $wpdb->prefix . 'iterp_visa_requirements';

    $action_sub = isset( $_GET['action_sub'] ) ? sanitize_text_field( $_GET['action_sub'] ) : '';
    $req_id     = isset( $_GET['req_id'] ) ? intval( $_GET['req_id'] ) : 0;
    $message    = '';
    $base_url   = admin_url( 'admin.php?page=ifs_travel_erp&tab=visa&sub=requirements' );

    /* =========================================================================
       1. DELETE REQUIREMENT RECORD
       ========================================================================= */
    if ( $action_sub === 'delete' && $req_id > 0 ) {
        if ( isset( $_GET['_wpnonce'] ) && wp_verify_nonce( $_GET['_wpnonce'], 'delete_visa_req_' . $req_id ) ) {
            $country_name = $wpdb->get_var( $wpdb->prepare( "SELECT country_name FROM $table_reqs WHERE id = %d", $req_id ) );
            $wpdb->delete( $table_reqs, array( 'id' => $req_id ), array( '%d' ) );
            if ( function_exists( 'ifs_terp_log_activity' ) ) {
                ifs_terp_log_activity( "Deleted Visa Requirement Config ID: #" . $req_id . " (" . $country_name . ")" );
            }
            wp_safe_redirect( add_query_arg( array( 'page' => 'ifs_travel_erp', 'tab' => 'visa', 'sub' => 'requirements', 'msg' => 'deleted' ), admin_url( 'admin.php' ) ) );
            exit;
        } else {
            $message = '<div class="ifs-toast danger"><span class="dashicons dashicons-dismiss"></span> Security token verification failed.</div>';
        }
    }

    if ( isset( $_GET['msg'] ) && $_GET['msg'] === 'deleted' ) {
        $message = '<div class="ifs-toast success"><span class="dashicons dashicons-yes-alt"></span> Country visa requirement configuration removed successfully.</div>';
    }

    /* =========================================================================
       2. FORM SUBMIT HANDLER (ADD / UPDATE)
       ========================================================================= */
    if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['ifs_visa_req_submit'] ) ) {
        check_admin_referer( 'ifs_visa_req_nonce_action', 'ifs_visa_req_nonce' );

        $is_edit_mode = ( isset( $_POST['edit_req_id'] ) && intval( $_POST['edit_req_id'] ) > 0 );
        $edit_id      = $is_edit_mode ? intval( $_POST['edit_req_id'] ) : 0;

        $country_name      = sanitize_text_field( $_POST['country_name'] ?? '' );
        $visa_type         = sanitize_text_field( $_POST['visa_type'] ?? 'Tourist / Visit Visa' );
        $processing_time   = sanitize_text_field( $_POST['processing_time'] ?? '7-10 Working Days' );
        $standard_fee      = floatval( $_POST['standard_fee'] ?? 0 );
        $requirements_list = sanitize_textarea_field( $_POST['requirements_list'] ?? '' );

        if ( empty( $country_name ) ) {
            $message = '<div class="ifs-toast danger"><span class="dashicons dashicons-dismiss"></span> Country name is required.</div>';
        } else {
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
                $message = '<div class="ifs-toast success"><span class="dashicons dashicons-yes-alt"></span> Visa requirement for ' . esc_html( $country_name ) . ' updated successfully.</div>';
            } else {
                $data_array['created_at'] = current_time( 'mysql' );
                $wpdb->insert( $table_reqs, $data_array );
                $new_id = $wpdb->insert_id;
                if ( function_exists( 'ifs_terp_log_activity' ) ) {
                    ifs_terp_log_activity( "Added New Visa Requirement for: " . $country_name . " (ID: #" . $new_id . ")" );
                }
                $message = '<div class="ifs-toast success"><span class="dashicons dashicons-yes-alt"></span> New visa requirement for ' . esc_html( $country_name ) . ' configured successfully.</div>';
            }
        }
    }

    /* =========================================================================
       3. FETCH EDIT RECORD IF REQUESTED
       ========================================================================= */
    $edit_data = false;
    if ( $action_sub === 'edit' && $req_id > 0 ) {
        $edit_data = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_reqs WHERE id = %d", $req_id ) );
    }

    // Default Fallbacks
    $val_country = $edit_data ? esc_attr( $edit_data->country_name ) : 'United Kingdom';
    $val_type    = $edit_data ? esc_attr( $edit_data->visa_type ) : 'Standard Tourist Visa';
    $val_time    = $edit_data ? esc_attr( $edit_data->processing_time ) : '7-10 Working Days';
    $val_fee     = $edit_data ? floatval( $edit_data->standard_fee ) : 16500;
    $val_reqs    = $edit_data ? esc_textarea( $edit_data->requirements_list ) : "1. Original Passport (Min 6 months validity with at least 2 blank pages)\n2. 2 Copies Recent Passport Size Photos (35x45mm, White Background, Matte Paper)\n3. Personal Bank Statement & Solvency Certificate (Last 6 Months, Min Balance ৳500,000+)\n4. Trade License with Notarized English Translation & Company Letterhead (For Business)\n5. Official Leave Approval / No Objection Certificate (NOC) & Pay Slips (For Job Holders)\n6. National ID (NID) / Birth Certificate Photocopy & Marriage Certificate (If applicable)";

    // Summary Aggregations
    $total_countries = (int) $wpdb->get_var( "SELECT COUNT(DISTINCT country_name) FROM $table_reqs" );
    $total_types     = (int) $wpdb->get_var( "SELECT COUNT(id) FROM $table_reqs" );
    $avg_fee         = (float) $wpdb->get_var( "SELECT AVG(standard_fee) FROM $table_reqs" );

    // Fetch all requirements
    $all_reqs = $wpdb->get_results( "SELECT * FROM $table_reqs ORDER BY country_name ASC" );
    ?>

    <div class="ifs-reqs-workspace">
        <?php echo $message; ?>

        <!-- Metric Counter Ribbon -->
        <div class="ifs-list-metric-ribbon">
            <div class="ifs-metric-chip">
                <div class="chip-icon bg-blue"><span class="dashicons dashicons-admin-site-alt3"></span></div>
                <div>
                    <span class="chip-label">Configured Countries</span>
                    <strong class="chip-val"><?php echo number_format( $total_countries ); ?></strong>
                </div>
            </div>
            <div class="ifs-metric-chip">
                <div class="chip-icon bg-indigo"><span class="dashicons dashicons-portfolio"></span></div>
                <div>
                    <span class="chip-label">Visa Rules Vault</span>
                    <strong class="chip-val"><?php echo number_format( $total_types ); ?> Categories</strong>
                </div>
            </div>
            <div class="ifs-metric-chip">
                <div class="chip-icon bg-emerald"><span class="dashicons dashicons-money-alt"></span></div>
                <div>
                    <span class="chip-label">Avg. Standard Fee</span>
                    <strong class="chip-val color-emerald">৳<?php echo number_format( $avg_fee, 2 ); ?></strong>
                </div>
            </div>
            <div class="ifs-metric-chip">
                <div class="chip-icon bg-cyan"><span class="dashicons dashicons-clipboard"></span></div>
                <div>
                    <span class="chip-label">Live Advisory Engine</span>
                    <strong class="chip-val color-blue">Active</strong>
                </div>
            </div>
        </div>

        <!-- Split Screen: Form & Live Country Advisory Card Preview -->
        <form method="post" action="<?php echo esc_url( $base_url ); ?>" class="ifs-split-reqs-editor" id="ifsReqsForm">
            <?php wp_nonce_field( 'ifs_visa_req_nonce_action', 'ifs_visa_req_nonce' ); ?>
            
            <?php if ( $edit_data ) : ?>
                <input type="hidden" name="edit_req_id" value="<?php echo esc_attr( $edit_data->id ); ?>">
            <?php endif; ?>

            <div class="ifs-reqs-form-body">
                
                <!-- Section 1: Country Configuration & Fee Setup -->
                <div class="ifs-panel-card">
                    <div class="ifs-card-header">
                        <div class="ifs-step-num">01</div>
                        <div>
                            <h3 class="ifs-card-title"><?php echo $edit_data ? 'Update Country Advisory: ' . esc_html( $edit_data->country_name ) : 'Configure Country Requirement & Standard Fees'; ?></h3>
                            <p class="ifs-card-desc">Define standard visa rules, processing turnarounds, and mandatory embassy costs</p>
                        </div>
                    </div>

                    <div class="ifs-grid-2">
                        <!-- Country Name -->
                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_country_name">Destination Country <span class="req">*</span></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-admin-site-alt3 field-icon"></span>
                                <input type="text" name="country_name" id="inp_country_name" required 
                                       value="<?php echo $val_country; ?>" 
                                       placeholder="e.g. United Kingdom / UAE / Canada" class="ifs-input-field">
                            </div>
                        </div>

                        <!-- Visa Type -->
                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_visa_type">Visa Type / Category <span class="req">*</span></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-tag field-icon"></span>
                                <input type="text" name="visa_type" id="inp_visa_type" required 
                                       value="<?php echo $val_type; ?>" 
                                       placeholder="e.g. Standard Tourist / Business / E-Visa" class="ifs-input-field">
                            </div>
                        </div>

                        <!-- Processing Time -->
                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_processing_time">Standard Processing Turnaround</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-clock field-icon"></span>
                                <input type="text" name="processing_time" id="inp_processing_time" 
                                       value="<?php echo $val_time; ?>" 
                                       placeholder="e.g. 7-10 Working Days" class="ifs-input-field">
                            </div>
                        </div>

                        <!-- Standard Fee -->
                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_standard_fee">Standard Embassy / VFS Fee (৳) <span class="req">*</span></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-money-alt field-icon"></span>
                                <input type="number" step="0.01" name="standard_fee" id="inp_standard_fee" required 
                                       value="<?php echo $val_fee; ?>" 
                                       placeholder="0.00" class="ifs-input-field font-mono font-bold color-blue">
                            </div>
                        </div>

                        <!-- Requirements Checklist -->
                        <div class="ifs-field-block col-span-2">
                            <label class="ifs-field-label" for="inp_requirements_list">Required Documents Checklist (Bullet Points) <span class="req">*</span></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-clipboard field-icon textarea-icon"></span>
                                <textarea name="requirements_list" id="inp_requirements_list" required rows="6" 
                                          class="ifs-input-field has-textarea-icon" 
                                          placeholder="1. Original Passport...&#10;2. Photos...&#10;3. Bank Statement..."><?php echo $val_reqs; ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Toolbar -->
                <div class="ifs-action-strip">
                    <?php if ( $edit_data ) : ?>
                        <a href="<?php echo esc_url( $base_url ); ?>" class="ifs-btn-back">
                            <span class="dashicons dashicons-arrow-left-alt"></span> Cancel Edit
                        </a>
                    <?php else : ?>
                        <span class="ifs-submeta-hint"><span class="dashicons dashicons-info"></span> Rules update dynamically across applicant entry desks</span>
                    <?php endif; ?>
                    <button type="submit" name="ifs_visa_req_submit" class="ifs-btn-primary">
                        <span class="dashicons dashicons-saved"></span> 
                        <?php echo $edit_data ? 'Update Requirement Advisory' : 'Save Requirement Entry'; ?>
                    </button>
                </div>
            </div>

            <!-- Right Sidebar: Live Country Advisory Card Preview -->
            <div class="ifs-preview-sidebar">
                <div class="ifs-preview-sticky">
                    <div class="ifs-card-preview-header">
                        <span class="dashicons dashicons-visibility"></span> Live Country Advisory Card Preview
                    </div>

                    <!-- Modern Country Requirement Widget -->
                    <div class="ifs-advisory-card">
                        <div class="advisory-head-strip">
                            <span class="advisory-badge-tag"><span class="dashicons dashicons-admin-site-alt3"></span> DESTINATION</span>
                            <span class="advisory-time-tag" id="prev_time">7-10 WORKING DAYS</span>
                        </div>

                        <div class="advisory-hero">
                            <h3 class="advisory-country" id="prev_country">UNITED KINGDOM</h3>
                            <span class="advisory-category" id="prev_category">STANDARD TOURIST VISA</span>
                        </div>

                        <div class="advisory-fee-box">
                            <span class="advisory-fee-lbl">STANDARD EMBASSY FEE</span>
                            <h4 class="advisory-fee-val font-mono" id="prev_fee">৳16,500.00</h4>
                        </div>

                        <div class="advisory-checklist-box">
                            <span class="checklist-head"><span class="dashicons dashicons-yes-alt"></span> MANDATORY CHECKLIST PREVIEW:</span>
                            <div class="checklist-content" id="prev_checklist">
                                1. Original Passport (Min 6 months validity)<br>
                                2. Photos (35x45mm White BG)<br>
                                3. Bank Statement & Solvency
                            </div>
                        </div>

                        <div class="advisory-footer-strip">
                            <span class="dashicons dashicons-shield"></span> IATA & VFS Global Standard Compliance
                        </div>
                    </div>

                    <!-- Fast Presets Widget -->
                    <div class="ifs-presets-box">
                        <span class="presets-title"><span class="dashicons dashicons-superhero"></span> Quick Country Templates:</span>
                        <div class="presets-btn-group">
                            <button type="button" class="preset-pill" onclick="applyPreset('Saudi Arabia', 'Tourist / Umrah E-Visa', '2-3 Working Days', 18500)">Saudi Arabia</button>
                            <button type="button" class="preset-pill" onclick="applyPreset('United Arab Emirates', '30 Days Tourist Visa', '24-48 Hours', 12500)">UAE (Dubai)</button>
                            <button type="button" class="preset-pill" onclick="applyPreset('Thailand', 'Tourist Single Entry', '4-5 Working Days', 6000)">Thailand</button>
                            <button type="button" class="preset-pill" onclick="applyPreset('Singapore', 'E-Visa Tourist', '3-4 Working Days', 4500)">Singapore</button>
                            <button type="button" class="preset-pill" onclick="applyPreset('Malaysia', 'eNTRI / E-Visa', '48 Hours', 5500)">Malaysia</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <!-- Requirements Directory Table Card -->
        <div class="ifs-table-card" style="margin-top: 30px;">
            <div class="ifs-table-top-bar">
                <div class="ifs-table-title-group">
                    <h3 class="ifs-table-heading"><span class="dashicons dashicons-list-view"></span> Configured Country Advisory Directory</h3>
                    <p class="ifs-table-caption">Global embassy fees, standard checklist requirements, and application turnaround times</p>
                </div>
            </div>

            <div class="ifs-table-responsive-wrapper">
                <table class="ifs-pro-datatable" id="ifsVisaReqTable">
                    <thead>
                        <tr>
                            <th style="width: 160px;">Country Name</th>
                            <th>Visa Category</th>
                            <th>Processing Time</th>
                            <th style="text-align: right;">Standard Fee (৳)</th>
                            <th>Mandatory Document Checklist</th>
                            <th style="text-align: right; width: 100px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ( $all_reqs ) : foreach ( $all_reqs as $req ) : ?>
                            <tr>
                                <td>
                                    <div class="ifs-country-cell">
                                        <span class="dashicons dashicons-admin-site-alt3 country-icon"></span>
                                        <strong><?php echo esc_html( $req->country_name ); ?></strong>
                                    </div>
                                </td>
                                <td>
                                    <span class="ifs-tier-pill tier-corporate"><?php echo esc_html( $req->visa_type ); ?></span>
                                </td>
                                <td>
                                    <span class="ifs-time-pill"><span class="dashicons dashicons-clock"></span> <?php echo esc_html( $req->processing_time ?: 'Standard' ); ?></span>
                                </td>
                                <td style="text-align: right; font-weight: 800; color: #003376; font-family: ui-monospace, monospace; font-size: 13.5px;">
                                    ৳<?php echo number_format( $req->standard_fee, 2 ); ?>
                                </td>
                                <td>
                                    <div class="ifs-checklist-cell">
                                        <?php echo nl2br( esc_html( $req->requirements_list ) ); ?>
                                    </div>
                                </td>
                                <td style="text-align: right;">
                                    <div class="ifs-action-buttons">
                                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=ifs_travel_erp&tab=visa&sub=requirements&action_sub=edit&req_id=' . $req->id ) ); ?>" 
                                           class="ifs-btn-action edit" title="Edit Requirement">
                                            <span class="dashicons dashicons-edit"></span>
                                        </a>
                                        <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=ifs_travel_erp&tab=visa&sub=requirements&action_sub=delete&req_id=' . $req->id ), 'delete_visa_req_' . $req->id ) ); ?>" 
                                           class="ifs-btn-action delete" 
                                           onclick="return confirm('Are you sure you want to delete this country requirement?');" title="Delete">
                                            <span class="dashicons dashicons-trash"></span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; else : ?>
                            <tr>
                                <td colspan="6" class="ifs-empty-table">
                                    <div class="ifs-empty-state">
                                        <span class="dashicons dashicons-admin-site-alt3"></span>
                                        <h4>No Visa Requirements Configured Yet</h4>
                                        <p>Use the form above to add your first country visa policy & checklist.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Ultra High-End Stylesheet -->
    <style>
        .ifs-reqs-workspace { max-width: 1440px; margin: 0 auto; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; color: #0f172a; }
        
        .ifs-toast { padding: 14px 20px; border-radius: 10px; font-size: 13.5px; font-weight: 600; display: flex; align-items: center; gap: 10px; margin-bottom: 22px; box-shadow: 0 4px 12px rgba(0,0,0,0.03); }
        .ifs-toast.success { background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; }
        .ifs-toast.danger  { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
        .ifs-toast .dashicons { font-size: 18px; width: 18px; height: 18px; }

        /* Metric Counter Ribbon */
        .ifs-list-metric-ribbon {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 18px;
            margin-bottom: 24px;
        }
        .ifs-metric-chip {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 16px 20px;
            display: flex;
            align-items: center;
            gap: 16px;
            box-shadow: 0 2px 8px -2px rgba(15, 23, 42, 0.03);
        }
        .chip-icon { width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #ffffff; flex-shrink: 0; }
        .chip-icon.bg-blue   { background: linear-gradient(135deg, #003376 0%, #0284c7 100%); }
        .chip-icon.bg-indigo { background: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%); }
        .chip-icon.bg-emerald{ background: linear-gradient(135deg, #059669 0%, #047857 100%); }
        .chip-icon.bg-cyan   { background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%); }
        .chip-icon .dashicons { font-size: 22px; width: 22px; height: 22px; }

        .chip-label { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.4px; display: block; margin-bottom: 2px; }
        .chip-val { font-size: 19px; font-weight: 800; color: #0f172a; letter-spacing: -0.5px; }
        .color-blue { color: #003376 !important; }
        .color-emerald { color: #059669 !important; }

        /* Split Editor Layout */
        .ifs-split-reqs-editor { display: grid; grid-template-columns: 1fr 390px; gap: 28px; align-items: flex-start; }
        @media (max-width: 1140px) { .ifs-split-reqs-editor { grid-template-columns: 1fr; } }

        .ifs-panel-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 26px; margin-bottom: 22px; box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.03); }
        .ifs-card-header { display: flex; align-items: center; gap: 14px; margin-bottom: 22px; padding-bottom: 14px; border-bottom: 1px solid #f1f5f9; }
        .ifs-step-num { width: 34px; height: 34px; border-radius: 9px; background: linear-gradient(135deg, #003376 0%, #0284c7 100%); color: #ffffff; font-weight: 800; font-size: 13px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px rgba(0, 51, 118, 0.2); flex-shrink: 0; }
        .ifs-card-title { margin: 0; font-size: 15.5px; font-weight: 800; color: #0f172a; }
        .ifs-card-desc { margin: 2px 0 0 0; font-size: 12.5px; color: #64748b; }

        .ifs-grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px 18px; }
        .col-span-2 { grid-column: span 2; }
        @media (max-width: 768px) { .ifs-grid-2 { grid-template-columns: 1fr; } .col-span-2 { grid-column: span 1; } }

        .ifs-field-block { display: flex; flex-direction: column; gap: 5px; }
        .ifs-field-label { font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.4px; }
        .ifs-field-label .req { color: #e11d48; }

        .ifs-field-wrap { position: relative; display: flex; align-items: center; width: 100%; }
        .ifs-field-wrap .field-icon {
            position: absolute;
            left: 12px;
            color: #94a3b8;
            font-size: 17px;
            width: 17px;
            height: 17px;
            pointer-events: none;
            z-index: 2;
            transition: color 0.2s ease;
        }
        .ifs-field-wrap .textarea-icon { top: 12px; }

        .ifs-field-wrap .ifs-input-field {
            width: 100%;
            padding: 9px 12px 9px 38px !important;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 13.5px;
            color: #0f172a;
            background: #ffffff;
            outline: none;
            transition: all 0.2s ease;
            position: relative;
            z-index: 1;
        }
        textarea.ifs-input-field.has-textarea-icon { padding: 10px 12px 10px 38px !important; font-family: inherit; line-height: 1.5; }
        .ifs-field-wrap .ifs-input-field:focus { border-color: #003376; box-shadow: 0 0 0 3px rgba(0, 51, 118, 0.12); }
        .ifs-field-wrap:focus-within .field-icon { color: #003376; }

        .font-mono { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; }
        .font-bold { font-weight: 700; }

        /* Action Toolbar */
        .ifs-action-strip { display: flex; justify-content: space-between; align-items: center; padding: 18px 24px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; }
        .ifs-btn-back { color: #64748b; text-decoration: none; font-size: 13.5px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; }
        .ifs-btn-back:hover { color: #0f172a; }
        .ifs-submeta-hint { font-size: 12px; color: #64748b; display: inline-flex; align-items: center; gap: 4px; }
        .ifs-submeta-hint .dashicons { font-size: 14px; width: 14px; height: 14px; color: #0284c7; }
        .ifs-btn-primary {
            background: linear-gradient(135deg, #003376 0%, #0284c7 100%);
            color: #ffffff !important;
            border: none;
            padding: 11px 26px;
            border-radius: 8px;
            font-size: 13.5px;
            font-weight: 700;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 14px rgba(0, 51, 118, 0.25);
            transition: all 0.2s ease;
        }
        .ifs-btn-primary:hover { transform: translateY(-1px); box-shadow: 0 6px 18px rgba(0, 51, 118, 0.35); }

        /* Right Preview Sidebar */
        .ifs-preview-sticky { position: sticky; top: 30px; }
        .ifs-card-preview-header { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; margin-bottom: 12px; display: flex; align-items: center; gap: 6px; }

        .ifs-advisory-card {
            background: linear-gradient(145deg, #0f172a 0%, #1e293b 60%, #334155 100%);
            border-radius: 16px;
            padding: 22px;
            color: #ffffff;
            box-shadow: 0 16px 36px -6px rgba(15, 23, 42, 0.35);
            border: 1px solid rgba(255, 255, 255, 0.15);
            position: relative;
            overflow: hidden;
            margin-bottom: 18px;
        }
        .advisory-head-strip { display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; padding-bottom: 10px; border-bottom: 1px dashed rgba(255, 255, 255, 0.2); }
        .advisory-badge-tag { font-size: 9px; font-weight: 800; letter-spacing: 1px; color: #94a3b8; display: inline-flex; align-items: center; gap: 4px; }
        .advisory-badge-tag .dashicons { font-size: 12px; width: 12px; height: 12px; }
        .advisory-time-tag { background: rgba(2, 132, 199, 0.25); color: #38bdf8; border: 1px solid rgba(56, 189, 248, 0.3); padding: 2px 7px; border-radius: 4px; font-size: 9.5px; font-weight: 800; text-transform: uppercase; }

        .advisory-hero { margin-bottom: 16px; }
        .advisory-country { margin: 0; font-size: 18px; font-weight: 900; color: #ffffff; letter-spacing: -0.3px; text-transform: uppercase; }
        .advisory-category { font-size: 11px; color: #94a3b8; margin-top: 2px; display: block; text-transform: uppercase; font-weight: 600; }

        .advisory-fee-box { background: rgba(0, 0, 0, 0.2); border-radius: 10px; padding: 12px 14px; margin-bottom: 14px; display: flex; justify-content: space-between; align-items: center; }
        .advisory-fee-lbl { font-size: 9px; font-weight: 700; color: #94a3b8; letter-spacing: 0.5px; }
        .advisory-fee-val { margin: 0; font-size: 17px; font-weight: 900; color: #86efac; }

        .advisory-checklist-box { background: rgba(255, 255, 255, 0.05); border-radius: 10px; padding: 12px 14px; margin-bottom: 12px; }
        .checklist-head { font-size: 9.5px; font-weight: 800; color: #38bdf8; display: flex; align-items: center; gap: 4px; margin-bottom: 6px; letter-spacing: 0.3px; }
        .checklist-head .dashicons { font-size: 12px; width: 12px; height: 12px; }
        .checklist-content { font-size: 11px; color: #cbd5e1; line-height: 1.5; max-height: 100px; overflow-y: auto; }

        .advisory-footer-strip { font-size: 9px; color: #94a3b8; display: flex; align-items: center; gap: 4px; padding-top: 6px; border-top: 1px solid rgba(255, 255, 255, 0.1); }
        .advisory-footer-strip .dashicons { font-size: 12px; width: 12px; height: 12px; color: #38bdf8; }

        /* Presets Box */
        .ifs-presets-box { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02); }
        .presets-title { font-size: 11.5px; font-weight: 800; color: #003376; text-transform: uppercase; letter-spacing: 0.4px; display: flex; align-items: center; gap: 6px; margin-bottom: 10px; }
        .presets-title .dashicons { font-size: 15px; width: 15px; height: 15px; }
        .presets-btn-group { display: flex; flex-wrap: wrap; gap: 6px; }
        .preset-pill { background: #f1f5f9; border: 1px solid #cbd5e1; border-radius: 6px; padding: 4px 9px; font-size: 11px; font-weight: 700; color: #334155; cursor: pointer; transition: all 0.15s ease; }
        .preset-pill:hover { background: #003376; color: #ffffff; border-color: #003376; }

        /* Table Card */
        .ifs-table-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.03); overflow: hidden; }
        .ifs-table-top-bar { padding: 22px 26px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #f1f5f9; flex-wrap: wrap; gap: 15px; }
        .ifs-table-heading { margin: 0; font-size: 17px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 8px; }
        .ifs-table-heading .dashicons { color: #003376; font-size: 20px; width: 20px; height: 20px; }
        .ifs-table-caption { margin: 3px 0 0 0; font-size: 13px; color: #64748b; }

        .ifs-table-responsive-wrapper { padding: 15px 24px 24px 24px; overflow-x: auto; }
        .ifs-pro-datatable { width: 100%; border-collapse: collapse; font-size: 13px; }
        .ifs-pro-datatable thead th { background: #f8fafc; color: #475569; font-size: 11px; font-weight: 700; text-transform: uppercase; padding: 12px 14px; border-bottom: 2px solid #e2e8f0; text-align: left; }
        .ifs-pro-datatable tbody td { padding: 13px 14px; border-bottom: 1px solid #f1f5f9; vertical-align: top; color: #334155; }

        .ifs-country-cell { display: flex; align-items: center; gap: 8px; font-size: 13.5px; color: #0f172a; }
        .country-icon { color: #0284c7; font-size: 16px; width: 16px; height: 16px; }

        .ifs-tier-pill { font-size: 10.5px; font-weight: 700; padding: 2px 7px; border-radius: 4px; display: inline-block; }
        .tier-corporate { background: #e0f2fe; color: #0369a1; }

        .ifs-time-pill { font-size: 11px; color: #475569; display: inline-flex; align-items: center; gap: 4px; font-weight: 600; }
        .ifs-time-pill .dashicons { font-size: 13px; width: 13px; height: 13px; color: #94a3b8; }

        .ifs-checklist-cell { font-size: 12px; color: #475569; line-height: 1.5; max-width: 450px; }

        .ifs-action-buttons { display: flex; gap: 5px; justify-content: flex-end; }
        .ifs-btn-action { width: 30px; height: 30px; border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; text-decoration: none; transition: all 0.15s ease; }
        .ifs-btn-action.edit   { background: #eff6ff; color: #2563eb; }
        .ifs-btn-action.edit:hover { background: #dbeafe; color: #1d4ed8; }
        .ifs-btn-action.delete { background: #fef2f2; color: #dc2626; }
        .ifs-btn-action.delete:hover { background: #fee2e2; color: #b91c1c; }
        .ifs-btn-action .dashicons { font-size: 14px; width: 14px; height: 14px; }

        .ifs-empty-table { text-align: center; padding: 50px 20px !important; }
        .ifs-empty-state .dashicons { font-size: 44px; width: 44px; height: 44px; color: #cbd5e1; margin-bottom: 10px; }
    </style>

    <!-- Real-Time Interactive Script & Presets Engine -->
    <script>
    function applyPreset(country, type, time, fee) {
        document.getElementById('inp_country_name').value   = country;
        document.getElementById('inp_visa_type').value      = type;
        document.getElementById('inp_processing_time').value= time;
        document.getElementById('inp_standard_fee').value   = fee;
        
        const event = new Event('input', { bubbles: true });
        document.getElementById('inp_country_name').dispatchEvent(event);
    }

    document.addEventListener('DOMContentLoaded', function() {
        const inpCountry = document.getElementById('inp_country_name');
        const inpType    = document.getElementById('inp_visa_type');
        const inpTime    = document.getElementById('inp_processing_time');
        const inpFee     = document.getElementById('inp_standard_fee');
        const inpReqs    = document.getElementById('inp_requirements_list');

        const prevCountry   = document.getElementById('prev_country');
        const prevCategory  = document.getElementById('prev_category');
        const prevTime      = document.getElementById('prev_time');
        const prevFee       = document.getElementById('prev_fee');
        const prevChecklist = document.getElementById('prev_checklist');

        function updateAdvisoryCard() {
            if (prevCountry) prevCountry.textContent   = (inpCountry && inpCountry.value.trim()) ? inpCountry.value.trim().toUpperCase() : 'DESTINATION';
            if (prevCategory) prevCategory.textContent = (inpType && inpType.value.trim()) ? inpType.value.trim().toUpperCase() : 'TOURIST VISA';
            if (prevTime) prevTime.textContent         = (inpTime && inpTime.value.trim()) ? inpTime.value.trim().toUpperCase() : 'STANDARD TIME';

            const feeVal = parseFloat(inpFee ? inpFee.value : 0) || 0;
            if (prevFee) prevFee.textContent = '৳' + feeVal.toLocaleString('en-US', { minimumFractionDigits: 2 });

            if (prevChecklist && inpReqs) {
                const lines = inpReqs.value.split('\n').filter(l => l.trim() !== '');
                if (lines.length > 0) {
                    prevChecklist.innerHTML = lines.slice(0, 4).join('<br>') + (lines.length > 4 ? '<br><em>+ ' + (lines.length - 4) + ' more items...</em>' : '');
                } else {
                    prevChecklist.textContent = 'No checklist items defined yet.';
                }
            }
        }

        [inpCountry, inpType, inpTime, inpFee, inpReqs].forEach(el => {
            if (el) {
                el.addEventListener('input', updateAdvisoryCard);
                el.addEventListener('change', updateAdvisoryCard);
            }
        });

        updateAdvisoryCard();

        if (window.jQuery && jQuery.fn.DataTable) {
            jQuery('#ifsVisaReqTable').DataTable({
                "pageLength": 15,
                "order": [[ 0, "asc" ]],
                "language": {
                    "search": "_INPUT_",
                    "searchPlaceholder": "Search Country, Category, Fees...",
                    "lengthMenu": "Show _MENU_ entries",
                    "paginate": { "previous": "&larr; Prev", "next": "Next &rarr;" }
                }
            });
        }
    });
    </script>
    <?php
}