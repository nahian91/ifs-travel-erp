<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Enterprise Next-Gen Hajj & Umrah Pilgrim Registration Console
 * Features: Package Auto-Pricing, Room Sharing Matrix, Saudi MoFA / BRN Vault, Mahram Mapping, B2B Sub-Agent Support & Live Digital Pilgrim Pass Preview
 */
function ifs_terp_hajj_booking_add_edit_page() {
    global $wpdb;
    $table_bookings  = $wpdb->prefix . 'iterp_hajj_bookings';
    $table_customers = $wpdb->prefix . 'iterp_customers';
    $table_packages  = $wpdb->prefix . 'iterp_hajj_packages';
    $table_agents    = $wpdb->prefix . 'iterp_agents';
    $table_suppliers = $wpdb->prefix . 'iterp_suppliers';
    
    $id       = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;
    $is_edit  = ( $id > 0 );
    $message  = '';
    $errors   = array();
    $base_url = admin_url( 'admin.php?page=ifs_travel_erp&tab=hajj_umrah' );

    // Process Form Submission
    if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['ifs_hajj_submit'] ) ) {
        check_admin_referer( 'ifs_hajj_save_action', 'ifs_hajj_nonce' );

        $customer_id        = intval( $_POST['customer_id'] ?? 0 );
        $agent_id           = intval( $_POST['agent_id'] ?? 0 );
        $supplier_id        = intval( $_POST['supplier_id'] ?? 0 );
        $package_id         = intval( $_POST['package_id'] ?? 0 );
        $mahram_customer_id = intval( $_POST['mahram_customer_id'] ?? 0 );
        $room_sharing       = sanitize_text_field( $_POST['room_sharing'] ?? 'Quad' );
        $pilgrim_type       = sanitize_text_field( $_POST['pilgrim_type'] ?? 'Adult' );
        $brn_no             = strtoupper( sanitize_text_field( $_POST['brn_no'] ?? '' ) );
        $mofaza_no          = strtoupper( sanitize_text_field( $_POST['mofaza_no'] ?? '' ) );
        $tracking_id        = strtoupper( sanitize_text_field( $_POST['tracking_id'] ?? '' ) );
        $nusuk_id           = strtoupper( sanitize_text_field( $_POST['nusuk_id'] ?? '' ) );
        $flight_date        = sanitize_text_field( $_POST['flight_date'] ?? '' );
        $return_flight_date = sanitize_text_field( $_POST['return_flight_date'] ?? '' );
        $visa_status        = sanitize_text_field( $_POST['visa_status'] ?? 'Pending' );
        $status             = sanitize_text_field( $_POST['status'] ?? 'Booked' );
        $buy_price          = floatval( $_POST['buy_price'] ?? 0 );
        $sell_price         = floatval( $_POST['sell_price'] ?? 0 );
        $profit             = $sell_price - $buy_price;
        $remarks            = sanitize_textarea_field( $_POST['remarks'] ?? '' );

        if ( empty( $customer_id ) ) {
            $errors[] = 'Please select a registered pilgrim (customer).';
        }
        if ( empty( $package_id ) ) {
            $errors[] = 'Please select a Hajj or Umrah package.';
        }

        if ( empty( $errors ) ) {
            $data = array(
                'customer_id'        => $customer_id,
                'agent_id'           => $agent_id,
                'supplier_id'        => $supplier_id,
                'package_id'         => $package_id,
                'mahram_customer_id' => $mahram_customer_id,
                'room_sharing'       => $room_sharing,
                'pilgrim_type'       => $pilgrim_type,
                'brn_no'             => $brn_no,
                'mofaza_no'          => $mofaza_no,
                'tracking_id'        => $tracking_id,
                'nusuk_id'           => $nusuk_id,
                'flight_date'        => ! empty( $flight_date ) ? $flight_date : '1970-01-01',
                'return_flight_date' => ! empty( $return_flight_date ) ? $return_flight_date : '1970-01-01',
                'visa_status'        => $visa_status,
                'buy_price'          => $buy_price,
                'sell_price'         => $sell_price,
                'profit'             => $profit,
                'status'             => $status,
                'remarks'            => $remarks,
                'created_by'         => get_current_user_id()
            );

            if ( $is_edit ) {
                $wpdb->update( $table_bookings, $data, array( 'id' => $id ) );
                $message = '<div class="ifs-toast success"><span class="dashicons dashicons-yes-alt"></span> Pilgrim booking updated successfully.</div>';
            } else {
                $data['created_at'] = current_time( 'mysql' );
                $wpdb->insert( $table_bookings, $data );
                $id      = $wpdb->insert_id;
                $is_edit = true;
                $message = '<div class="ifs-toast success"><span class="dashicons dashicons-yes-alt"></span> New pilgrim registration confirmed (#HB-' . str_pad( (string) $id, 5, '0', STR_PAD_LEFT ) . ').</div>';
            }

            if ( function_exists( 'ifs_terp_log_activity' ) ) {
                ifs_terp_log_activity( "Registered Pilgrim Booking #HB-$id | Customer ID: #CUS-$customer_id | Package ID: #PKG-$package_id" );
            }
        } else {
            $message = '<div class="ifs-toast danger"><span class="dashicons dashicons-dismiss"></span> ' . implode( '<br>', $errors ) . '</div>';
        }
    }

    $row = false;
    if ( $is_edit ) {
        $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_bookings WHERE id = %d", $id ) );
    }

    $customers = $wpdb->get_results( "SELECT id, title, full_name, mobile, passport_no, gender, blood_group FROM $table_customers ORDER BY full_name ASC" );
    $packages  = $wpdb->get_results( "SELECT id, package_name, package_type, cost_bdt, selling_price, total_days, hotel_makkah, hotel_madinah FROM $table_packages ORDER BY package_name ASC" );
    $agents    = $wpdb->get_results( "SELECT id, agency_name, current_balance FROM $table_agents WHERE status = 'Active' ORDER BY agency_name ASC" );
    $suppliers = $wpdb->get_results( "SELECT id, supplier_name, current_balance FROM $table_suppliers WHERE status = 'Active' ORDER BY supplier_name ASC" );

    // Form Defaults
    $val_customer     = $is_edit ? intval( $row->customer_id ) : 0;
    $val_agent        = $is_edit ? intval( $row->agent_id ?? 0 ) : 0;
    $val_supplier     = $is_edit ? intval( $row->supplier_id ?? 0 ) : 0;
    $val_package      = $is_edit ? intval( $row->package_id ) : 0;
    $val_mahram       = $is_edit ? intval( $row->mahram_customer_id ?? 0 ) : 0;
    $val_sharing      = $is_edit ? esc_attr( $row->room_sharing ) : 'Quad';
    $val_pilgrim_type = $is_edit ? esc_attr( $row->pilgrim_type ?? 'Adult' ) : 'Adult';
    $val_brn          = $is_edit ? esc_attr( $row->brn_no ?? '' ) : '';
    $val_mofaza       = $is_edit ? esc_attr( $row->mofaza_no ?? '' ) : '';
    $val_tracking     = $is_edit ? esc_attr( $row->tracking_id ?? '' ) : '';
    $val_nusuk        = $is_edit ? esc_attr( $row->nusuk_id ?? '' ) : '';
    $val_flight       = ( $is_edit && ! empty( $row->flight_date ) && $row->flight_date !== '1970-01-01' ) ? esc_attr( $row->flight_date ) : '';
    $val_return       = ( $is_edit && ! empty( $row->return_flight_date ) && $row->return_flight_date !== '1970-01-01' ) ? esc_attr( $row->return_flight_date ) : '';
    $val_visa_status  = $is_edit ? esc_attr( $row->visa_status ) : 'Pending';
    $val_status       = $is_edit ? esc_attr( $row->status ) : 'Booked';
    $val_buy          = $is_edit ? floatval( $row->buy_price ) : '';
    $val_sell         = $is_edit ? floatval( $row->sell_price ) : '';
    $val_profit       = $is_edit ? floatval( $row->profit ) : 0;
    $val_remarks      = $is_edit ? esc_textarea( $row->remarks ?? '' ) : '';
    ?>

    <div class="ifs-hajj-workspace">
        <?php echo $message; ?>

        <form method="post" action="" id="ifsHajjForm" class="ifs-split-hajj-editor">
            <?php wp_nonce_field( 'ifs_hajj_save_action', 'ifs_hajj_nonce' ); ?>

            <div class="ifs-hajj-form-body">
                
                <!-- Section 1: Pilgrim Bio, Package & Mahram Relation -->
                <div class="ifs-panel-card">
                    <div class="ifs-card-header">
                        <div class="ifs-step-num">01</div>
                        <div>
                            <h3 class="ifs-card-title">Pilgrim Portfolio & Hajj/Umrah Package</h3>
                            <p class="ifs-card-desc">Assign customer dossier, selected package tier, and female mahram mapping</p>
                        </div>
                    </div>

                    <div class="ifs-grid-3">
                        <!-- Pilgrim Selection -->
                        <div class="ifs-field-block col-span-2">
                            <label class="ifs-field-label" for="inp_customer">Pilgrim (Passenger Dossier) <span class="req">*</span></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-admin-users field-icon"></span>
                                <select name="customer_id" id="inp_customer" required class="ifs-input-field">
                                    <option value="">-- Choose Registered Pilgrim --</option>
                                    <?php foreach ( $customers as $cus ) : 
                                        $t_prefix = ! empty( $cus->title ) ? $cus->title . '. ' : '';
                                        $p_num    = ! empty( $cus->passport_no ) ? ' [PPT: ' . $cus->passport_no . ']' : '';
                                        $gender   = ! empty( $cus->gender ) ? $cus->gender : 'Male';
                                    ?>
                                        <option value="<?php echo $cus->id; ?>" 
                                                data-name="<?php echo esc_attr( $t_prefix . $cus->full_name ); ?>"
                                                data-passport="<?php echo esc_attr( $cus->passport_no ?: 'NOT SET' ); ?>"
                                                data-gender="<?php echo esc_attr( $gender ); ?>"
                                                data-blood="<?php echo esc_attr( $cus->blood_group ?: 'Unknown' ); ?>"
                                                <?php selected( $val_customer, $cus->id ); ?>>
                                            <?php echo esc_html( $t_prefix . $cus->full_name . ' (' . $cus->mobile . ')' . $p_num . ' [' . $gender . ']' ); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Pilgrim Passenger Type -->
                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_pilgrim_type">Passenger Category</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-universal-access field-icon"></span>
                                <select name="pilgrim_type" id="inp_pilgrim_type" class="ifs-input-field">
                                    <option value="Adult" <?php selected( $val_pilgrim_type, 'Adult' ); ?>>Adult Pilgrim</option>
                                    <option value="Child" <?php selected( $val_pilgrim_type, 'Child' ); ?>>Child (2-11 Yrs)</option>
                                    <option value="Infant" <?php selected( $val_pilgrim_type, 'Infant' ); ?>>Infant (Under 2 Yrs)</option>
                                </select>
                            </div>
                        </div>

                        <!-- Package Selection -->
                        <div class="ifs-field-block col-span-2">
                            <label class="ifs-field-label" for="inp_package">Hajj / Umrah Package Plan <span class="req">*</span></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-palmtree field-icon"></span>
                                <select name="package_id" id="inp_package" required class="ifs-input-field">
                                    <option value="">-- Choose Package Plan --</option>
                                    <?php foreach ( $packages as $pkg ) : ?>
                                        <option value="<?php echo $pkg->id; ?>" 
                                                data-name="<?php echo esc_attr( $pkg->package_name ); ?>"
                                                data-type="<?php echo esc_attr( $pkg->package_type ); ?>"
                                                data-cost="<?php echo esc_attr( $pkg->cost_bdt ?? 0 ); ?>"
                                                data-sell="<?php echo esc_attr( $pkg->selling_price ?? $pkg->cost_bdt ); ?>"
                                                data-days="<?php echo esc_attr( $pkg->total_days ?? 15 ); ?>"
                                                data-makkah="<?php echo esc_attr( $pkg->hotel_makkah ?? 'Makkah Hotel' ); ?>"
                                                data-madinah="<?php echo esc_attr( $pkg->hotel_madinah ?? 'Madinah Hotel' ); ?>"
                                                <?php selected( $val_package, $pkg->id ); ?>>
                                            <?php echo esc_html( $pkg->package_name . ' (' . $pkg->package_type . ') | ' . ($pkg->total_days ?? 15) . ' Days | Rate: ৳' . number_format( $pkg->selling_price ?? $pkg->cost_bdt, 0 ) ); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Mahram Mapping -->
                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_mahram">Mahram / Guardian Pilgrim</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-groups field-icon"></span>
                                <select name="mahram_customer_id" id="inp_mahram" class="ifs-input-field">
                                    <option value="0">-- None / Self / Male Pilgrim --</option>
                                    <?php foreach ( $customers as $cus ) : ?>
                                        <option value="<?php echo $cus->id; ?>" <?php selected( $val_mahram, $cus->id ); ?>>
                                            <?php echo esc_html( $cus->full_name . ' (' . $cus->mobile . ')' ); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Room Sharing Plan -->
                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_sharing">Room Sharing Matrix <span class="req">*</span></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-building field-icon"></span>
                                <select name="room_sharing" id="inp_sharing" class="ifs-input-field">
                                    <option value="Quad" <?php selected( $val_sharing, 'Quad' ); ?>>Quad Sharing (4 Beds)</option>
                                    <option value="Triple" <?php selected( $val_sharing, 'Triple' ); ?>>Triple Sharing (3 Beds)</option>
                                    <option value="Double" <?php selected( $val_sharing, 'Double' ); ?>>Double Sharing (2 Beds)</option>
                                    <option value="Single" <?php selected( $val_sharing, 'Single' ); ?>>Single Private Room</option>
                                </select>
                            </div>
                        </div>

                        <!-- B2B Sub-Agent -->
                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_agent">B2B Sub-Agent (If Any)</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-businessman field-icon"></span>
                                <select name="agent_id" id="inp_agent" class="ifs-input-field">
                                    <option value="0">Direct Retail Pilgrim</option>
                                    <?php foreach ( $agents as $ag ) : ?>
                                        <option value="<?php echo $ag->id; ?>" <?php selected( $val_agent, $ag->id ); ?>>
                                            <?php echo esc_html( $ag->agency_name . ' (Bal: ৳' . number_format( $ag->current_balance, 0 ) . ')' ); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Moallem / Saudi Supplier -->
                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_supplier">Moallem / Umrah Wholesaler</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-networking field-icon"></span>
                                <select name="supplier_id" id="inp_supplier" class="ifs-input-field">
                                    <option value="0">Direct Ministry Account</option>
                                    <?php foreach ( $suppliers as $sup ) : ?>
                                        <option value="<?php echo $sup->id; ?>" <?php selected( $val_supplier, $sup->id ); ?>>
                                            <?php echo esc_html( $sup->supplier_name . ' (Bal: ৳' . number_format( $sup->current_balance, 0 ) . ')' ); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Saudi Ministry, MoFA, BRN & Flight Credentials -->
                <div class="ifs-panel-card">
                    <div class="ifs-card-header">
                        <div class="ifs-step-num">02</div>
                        <div>
                            <h3 class="ifs-card-title">Saudi Ministry, MoFA, BRN & Flight Transit</h3>
                            <p class="ifs-card-desc">Saudi Ministry of Hajj & Umrah portal credentials, Nusuk tracking, and flight transit dates</p>
                        </div>
                    </div>

                    <div class="ifs-grid-3">
                        <!-- Saudi BRN Number -->
                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_brn">Saudi Hotel / Transport BRN No</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-tag field-icon"></span>
                                <input type="text" name="brn_no" id="inp_brn" 
                                       value="<?php echo $val_brn; ?>" 
                                       placeholder="e.g. BRN-884920" class="ifs-input-field uppercase font-mono">
                            </div>
                        </div>

                        <!-- MoFA Number -->
                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_mofaza">Saudi MoFA / Mofaza No</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-id-alt field-icon"></span>
                                <input type="text" name="mofaza_no" id="inp_mofaza" 
                                       value="<?php echo $val_mofaza; ?>" 
                                       placeholder="e.g. MOF-1029384" class="ifs-input-field uppercase font-mono">
                            </div>
                        </div>

                        <!-- Nusuk / Group ID -->
                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_nusuk">Nusuk Masar / Group ID</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-shield-alt field-icon"></span>
                                <input type="text" name="nusuk_id" id="inp_nusuk" 
                                       value="<?php echo $val_nusuk; ?>" 
                                       placeholder="e.g. NSK-9908" class="ifs-input-field uppercase font-mono">
                            </div>
                        </div>

                        <!-- Departure Flight Date -->
                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_flight_date">Departure Flight Date</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-airplane field-icon"></span>
                                <input type="date" name="flight_date" id="inp_flight_date" 
                                       value="<?php echo $val_flight; ?>" class="ifs-input-field">
                            </div>
                        </div>

                        <!-- Return Flight Date -->
                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_return_flight_date">Return Flight Date</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-calendar-alt field-icon"></span>
                                <input type="date" name="return_flight_date" id="inp_return_flight_date" 
                                       value="<?php echo $val_return; ?>" class="ifs-input-field">
                            </div>
                        </div>

                        <!-- Ministry Visa Status -->
                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_visa_status">Ministry Visa Status</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-yes-alt field-icon"></span>
                                <select name="visa_status" id="inp_visa_status" class="ifs-input-field">
                                    <option value="Pending" <?php selected( $val_visa_status, 'Pending' ); ?>>Pending (Document Collection)</option>
                                    <option value="Submitted" <?php selected( $val_visa_status, 'Submitted' ); ?>>Submitted to MoFA</option>
                                    <option value="Issued" <?php selected( $val_visa_status, 'Issued' ); ?>>Visa Issued & Stamped</option>
                                    <option value="Rejected" <?php selected( $val_visa_status, 'Rejected' ); ?>>Rejected / Flagged</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 3: Commercial Package Pricing & Margin Breakdown -->
                <div class="ifs-panel-card">
                    <div class="ifs-card-header">
                        <div class="ifs-step-num">03</div>
                        <div>
                            <h3 class="ifs-card-title">Commercial Package Pricing & Margin Calculator</h3>
                            <p class="ifs-card-desc">Ground arrangement cost, airfare inclusion, pilgrim billing rate, and live calculated yield</p>
                        </div>
                    </div>

                    <div class="ifs-grid-3">
                        <!-- Net Buy Cost -->
                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="hajj_buy">Supplier / Net Cost Rate (৳) <span class="req">*</span></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-cart field-icon"></span>
                                <input type="number" step="0.01" name="buy_price" id="hajj_buy" required 
                                       value="<?php echo $val_buy; ?>" placeholder="0.00" class="ifs-input-field font-mono">
                            </div>
                        </div>

                        <!-- Client Selling Price -->
                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="hajj_sell">Pilgrim Package Selling Rate (৳) <span class="req">*</span></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-money-alt field-icon"></span>
                                <input type="number" step="0.01" name="sell_price" id="hajj_sell" required 
                                       value="<?php echo $val_sell; ?>" placeholder="0.00" class="ifs-input-field font-mono font-bold color-blue">
                            </div>
                        </div>

                        <!-- Calculated Profit -->
                        <div class="ifs-field-block">
                            <label class="ifs-field-label">Gross Margin / Profit (৳)</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-chart-line field-icon"></span>
                                <input type="text" id="hajj_profit" readonly 
                                       value="<?php echo number_format( $val_profit, 2 ); ?>" 
                                       class="ifs-input-field font-mono font-bold <?php echo ( $val_profit >= 0 ) ? 'profit-positive' : 'profit-negative'; ?>">
                            </div>
                        </div>

                        <!-- Booking Status -->
                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_status">Booking Lifecycle Status</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-clipboard field-icon"></span>
                                <select name="status" id="inp_status" class="ifs-input-field">
                                    <option value="Booked" <?php selected( $val_status, 'Booked' ); ?>>Booked (Initial Token Paid)</option>
                                    <option value="Confirmed" <?php selected( $val_status, 'Confirmed' ); ?>>Confirmed & Visa Ready</option>
                                    <option value="Completed" <?php selected( $val_status, 'Completed' ); ?>>Completed (Returned)</option>
                                    <option value="Cancelled" <?php selected( $val_status, 'Cancelled' ); ?>>Cancelled / Refunded</option>
                                </select>
                            </div>
                        </div>

                        <!-- Remarks -->
                        <div class="ifs-field-block col-span-2">
                            <label class="ifs-field-label" for="inp_remarks">Pilgrim Special Request / Health / Food Notes</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-edit field-icon"></span>
                                <input type="text" name="remarks" id="inp_remarks" 
                                       value="<?php echo $val_remarks; ?>" 
                                       placeholder="e.g. Wheelchair service in Haram, Diabetic Diet, Ground Floor room request" class="ifs-input-field">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Toolbar -->
                <div class="ifs-action-strip">
                    <a href="<?php echo esc_url( $base_url . '&sub=list' ); ?>" class="ifs-btn-back">
                        <span class="dashicons dashicons-arrow-left-alt"></span> Cancel
                    </a>
                    <button type="submit" name="ifs_hajj_submit" class="ifs-btn-primary">
                        <span class="dashicons dashicons-saved"></span> 
                        <?php echo $is_edit ? 'Update Pilgrim Booking' : 'Confirm Pilgrim Registration'; ?>
                    </button>
                </div>
            </div>

            <!-- Right Sidebar: Live Digital Pilgrim Pass Card Preview -->
            <div class="ifs-preview-sidebar">
                <div class="ifs-preview-sticky">
                    <div class="ifs-card-preview-header">
                        <span class="dashicons dashicons-id"></span> Live Digital Pilgrim Pass Preview
                    </div>

                    <!-- Modern Pilgrim Pass Card Widget -->
                    <div class="ifs-pilgrim-card">
                        <div class="pilgrim-head-strip">
                            <span class="pilgrim-brand-tag" id="prev_pkg_type">HAJJ / UMRAH</span>
                            <span class="pilgrim-sharing-badge" id="prev_sharing">QUAD ROOM</span>
                        </div>

                        <div class="pilgrim-bio-hero">
                            <div class="pilgrim-avatar" id="prev_avatar">MR</div>
                            <div>
                                <h4 class="pilgrim-name" id="prev_name">MOHAMMED RAHIM</h4>
                                <div class="pilgrim-submeta" id="prev_meta">PPT: NOT SET &bull; BLOOD: A+</div>
                            </div>
                        </div>

                        <div class="pilgrim-package-strip">
                            <span class="pkg-label">PACKAGE PLAN</span>
                            <strong class="pkg-val" id="prev_pkg_name">SELECT PACKAGE</strong>
                        </div>

                        <div class="pilgrim-grid-specs font-mono">
                            <div>
                                <span class="spec-lbl">SAUDI MOFA NO</span>
                                <strong class="spec-val color-cyan" id="prev_mofa">PENDING</strong>
                            </div>
                            <div>
                                <span class="spec-lbl">HOTEL BRN</span>
                                <strong class="spec-val" id="prev_brn">------</strong>
                            </div>
                            <div>
                                <span class="spec-lbl">VISA STATUS</span>
                                <strong class="spec-val color-amber" id="prev_visa_stat">PENDING</strong>
                            </div>
                            <div>
                                <span class="spec-lbl">FLIGHT DATE</span>
                                <strong class="spec-val color-green" id="prev_flight_disp">TBD</strong>
                            </div>
                        </div>

                        <div class="pilgrim-fee-footer">
                            <div class="fee-row">
                                <span>TOTAL PACKAGE FARE:</span>
                                <strong class="color-green font-mono" id="prev_sell">৳0.00</strong>
                            </div>
                            <span class="pilgrim-barcode font-mono" id="prev_barcode">H&lt;BGD&lt;&lt;PILGRIM&lt;&lt;MAKKAH&lt;MADINAH&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;</span>
                        </div>
                    </div>

                    <!-- Commercial Intelligence Box -->
                    <div class="ifs-intel-box">
                        <div class="intel-head"><span class="dashicons dashicons-analytics"></span> Real-Time Pilgrim Yield</div>
                        <div class="intel-body">
                            <div class="intel-row">
                                <span>Gross Margin:</span>
                                <strong id="intel_profit" class="color-green">৳0.00</strong>
                            </div>
                            <div class="intel-row">
                                <span>Yield Ratio:</span>
                                <strong id="intel_ratio">0.0%</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Ultra High-End Stylesheet -->
    <style>
        .ifs-hajj-workspace { max-width: 1420px; margin: 0 auto; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; color: #0f172a; }
        
        .ifs-toast { padding: 14px 20px; border-radius: 10px; font-size: 13.5px; font-weight: 600; display: flex; align-items: center; gap: 10px; margin-bottom: 22px; box-shadow: 0 4px 12px rgba(0,0,0,0.03); }
        .ifs-toast.success { background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; }
        .ifs-toast.danger  { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
        .ifs-toast .dashicons { font-size: 18px; width: 18px; height: 18px; }

        .ifs-split-hajj-editor { display: grid; grid-template-columns: 1fr 390px; gap: 28px; align-items: flex-start; }
        @media (max-width: 1140px) { .ifs-split-hajj-editor { grid-template-columns: 1fr; } }

        .ifs-panel-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 26px; margin-bottom: 22px; box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.03); }
        .ifs-card-header { display: flex; align-items: center; gap: 14px; margin-bottom: 22px; padding-bottom: 14px; border-bottom: 1px solid #f1f5f9; }
        .ifs-step-num { width: 34px; height: 34px; border-radius: 9px; background: linear-gradient(135deg, #047857 0%, #059669 100%); color: #ffffff; font-weight: 800; font-size: 13px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px rgba(5, 150, 105, 0.25); flex-shrink: 0; }
        .ifs-card-title { margin: 0; font-size: 15.5px; font-weight: 800; color: #0f172a; }
        .ifs-card-desc { margin: 2px 0 0 0; font-size: 12.5px; color: #64748b; }

        .ifs-grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px 18px; }
        .col-span-2 { grid-column: span 2; }
        .col-span-3 { grid-column: span 3; }
        @media (max-width: 768px) { .ifs-grid-3 { grid-template-columns: 1fr; } .col-span-2, .col-span-3 { grid-column: span 1; } }

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
        .ifs-field-wrap select.ifs-input-field {
            appearance: none;
            -webkit-appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 14px;
            padding-right: 32px !important;
        }
        .ifs-field-wrap .ifs-input-field:focus { border-color: #059669; box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.12); }
        .ifs-field-wrap:focus-within .field-icon { color: #059669; }

        .uppercase { text-transform: uppercase; }
        .font-mono { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; }
        .font-bold { font-weight: 700; }
        .color-blue { color: #003376 !important; }

        .profit-positive { background: #f0fdf4 !important; color: #166534 !important; border-color: #bbf7d0 !important; }
        .profit-negative { background: #fef2f2 !important; color: #dc2626 !important; border-color: #fecaca !important; }

        /* Action Toolbar */
        .ifs-action-strip { display: flex; justify-content: space-between; align-items: center; padding: 18px 24px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; }
        .ifs-btn-back { color: #64748b; text-decoration: none; font-size: 13.5px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; }
        .ifs-btn-back:hover { color: #0f172a; }
        .ifs-btn-primary {
            background: linear-gradient(135deg, #047857 0%, #059669 100%);
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
            box-shadow: 0 4px 14px rgba(5, 150, 105, 0.25);
            transition: all 0.2s ease;
        }
        .ifs-btn-primary:hover { transform: translateY(-1px); box-shadow: 0 6px 18px rgba(5, 150, 105, 0.35); }

        /* Right Preview Sidebar */
        .ifs-preview-sticky { position: sticky; top: 30px; }
        .ifs-card-preview-header { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; margin-bottom: 12px; display: flex; align-items: center; gap: 6px; }

        .ifs-pilgrim-card {
            background: linear-gradient(145deg, #064e3b 0%, #047857 60%, #059669 100%);
            border-radius: 16px;
            padding: 22px;
            color: #ffffff;
            box-shadow: 0 16px 36px -6px rgba(4, 120, 87, 0.35);
            border: 1px solid rgba(255, 255, 255, 0.15);
            position: relative;
            overflow: hidden;
        }

        .pilgrim-head-strip { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; padding-bottom: 10px; border-bottom: 1px dashed rgba(255, 255, 255, 0.2); }
        .pilgrim-brand-tag { font-size: 11px; font-weight: 800; letter-spacing: 0.8px; color: #a7f3d0; text-transform: uppercase; }
        .pilgrim-sharing-badge { background: rgba(255, 255, 255, 0.18); backdrop-filter: blur(4px); padding: 2px 8px; border-radius: 4px; font-size: 9.5px; font-weight: 800; text-transform: uppercase; }

        .pilgrim-bio-hero { display: flex; align-items: center; gap: 14px; margin-bottom: 16px; }
        .pilgrim-avatar { width: 46px; height: 46px; border-radius: 12px; background: rgba(255, 255, 255, 0.2); border: 1px solid rgba(255, 255, 255, 0.3); display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 15px; flex-shrink: 0; }
        .pilgrim-name { margin: 0; font-size: 14px; font-weight: 800; color: #ffffff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 210px; }
        .pilgrim-submeta { font-size: 11px; color: #a7f3d0; margin-top: 2px; }

        .pilgrim-package-strip { background: rgba(0, 0, 0, 0.18); padding: 8px 12px; border-radius: 8px; margin-bottom: 14px; }
        .pkg-label { font-size: 8.5px; font-weight: 700; color: #6ee7b7; letter-spacing: 0.5px; display: block; margin-bottom: 2px; }
        .pkg-val { font-size: 12px; font-weight: 700; color: #ffffff; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

        .pilgrim-grid-specs { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; padding-bottom: 14px; margin-bottom: 12px; border-bottom: 1px dashed rgba(255, 255, 255, 0.2); }
        .spec-lbl { font-size: 8.5px; font-weight: 700; color: #a7f3d0; letter-spacing: 0.5px; display: block; margin-bottom: 2px; }
        .spec-val { font-size: 12px; font-weight: 700; color: #ffffff; display: block; }
        .color-cyan { color: #38bdf8 !important; }
        .color-amber { color: #fde047 !important; }
        .color-green { color: #86efac !important; }

        .pilgrim-fee-footer { display: flex; flex-direction: column; gap: 6px; }
        .fee-row { display: flex; justify-content: space-between; align-items: center; font-size: 12px; color: #d1fae5; }
        .fee-row strong { font-size: 15px; }
        .pilgrim-barcode { font-size: 8px; color: #a7f3d0; letter-spacing: 1px; text-align: center; margin-top: 4px; }

        /* Intelligence Card */
        .ifs-intel-box { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; margin-top: 18px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02); }
        .intel-head { font-size: 12px; font-weight: 800; color: #047857; text-transform: uppercase; letter-spacing: 0.4px; display: flex; align-items: center; gap: 6px; margin-bottom: 10px; }
        .intel-body { display: flex; flex-direction: column; gap: 6px; }
        .intel-row { display: flex; justify-content: space-between; font-size: 12.5px; color: #475569; }
        .intel-row strong { font-weight: 800; font-size: 13.5px; }
    </style>

    <!-- Real-Time Interactive Engine -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const inpCustomer   = document.getElementById('inp_customer');
        const inpPackage    = document.getElementById('inp_package');
        const inpSharing    = document.getElementById('inp_sharing');
        const inpBrn        = document.getElementById('inp_brn');
        const inpMofaza     = document.getElementById('inp_mofaza');
        const inpFlight     = document.getElementById('inp_flight_date');
        const inpVisaStat   = document.getElementById('inp_visa_status');
        const inpBuy        = document.getElementById('hajj_buy');
        const inpSell       = document.getElementById('hajj_sell');

        const prevName       = document.getElementById('prev_name');
        const prevAvatar     = document.getElementById('prev_avatar');
        const prevMeta       = document.getElementById('prev_meta');
        const prevPkgType    = document.getElementById('prev_pkg_type');
        const prevPkgName    = document.getElementById('prev_pkg_name');
        const prevSharing    = document.getElementById('prev_sharing');
        const prevMofa       = document.getElementById('prev_mofa');
        const prevBrn        = document.getElementById('prev_brn');
        const prevVisaStat   = document.getElementById('prev_visa_stat');
        const prevFlightDisp = document.getElementById('prev_flight_disp');
        const prevSell       = document.getElementById('prev_sell');
        const profitDisplay  = document.getElementById('hajj_profit');
        const intelProfit    = document.getElementById('intel_profit');
        const intelRatio     = document.getElementById('intel_ratio');

        // Auto-fill pricing on package select
        if (inpPackage) {
            inpPackage.addEventListener('change', function() {
                if (this.selectedIndex > 0) {
                    const opt = this.options[this.selectedIndex];
                    const costVal = opt.getAttribute('data-cost');
                    const sellVal = opt.getAttribute('data-sell');
                    
                    if (inpBuy && (!inpBuy.value || inpBuy.value == '0' || inpBuy.value == '0.00')) {
                        inpBuy.value = costVal || '0.00';
                    }
                    if (inpSell && (!inpSell.value || inpSell.value == '0' || inpSell.value == '0.00')) {
                        inpSell.value = sellVal || '0.00';
                    }
                }
                updatePilgrimCard();
            });
        }

        function updatePilgrimCard() {
            // Customer Info
            if (inpCustomer && inpCustomer.selectedIndex > 0) {
                const opt     = inpCustomer.options[inpCustomer.selectedIndex];
                const paxName = opt.getAttribute('data-name') || 'MOHAMMED RAHIM';
                const ppt     = opt.getAttribute('data-passport') || 'NOT SET';
                const blood   = opt.getAttribute('data-blood') || 'Unknown';

                if (prevName) prevName.textContent = paxName.toUpperCase();
                if (prevMeta) prevMeta.textContent = 'PPT: ' + ppt + ' • BLOOD: ' + blood;

                const parts = paxName.trim().split(' ');
                if (prevAvatar) prevAvatar.textContent = parts.length > 1 ? (parts[0][0] + parts[parts.length-1][0]).toUpperCase() : parts[0].slice(0, 2).toUpperCase();
            } else {
                if (prevName) prevName.textContent = 'SELECT PILGRIM';
                if (prevMeta) prevMeta.textContent = 'PPT: NOT SET • BLOOD: N/A';
                if (prevAvatar) prevAvatar.textContent = 'HP';
            }

            // Package Info
            if (inpPackage && inpPackage.selectedIndex > 0) {
                const pOpt = inpPackage.options[inpPackage.selectedIndex];
                if (prevPkgName) prevPkgName.textContent = pOpt.getAttribute('data-name') || 'PACKAGE';
                if (prevPkgType) prevPkgType.textContent = (pOpt.getAttribute('data-type') || 'HAJJ / UMRAH').toUpperCase();
            } else {
                if (prevPkgName) prevPkgName.textContent = 'SELECT PACKAGE';
                if (prevPkgType) prevPkgType.textContent = 'HAJJ / UMRAH';
            }

            // Sharing & Saudi Meta
            if (prevSharing) prevSharing.textContent = (inpSharing ? inpSharing.value : 'QUAD') + ' ROOM';
            if (prevMofa) prevMofa.textContent = (inpMofaza && inpMofaza.value.trim()) ? inpMofaza.value.trim().toUpperCase() : 'PENDING';
            if (prevBrn) prevBrn.textContent   = (inpBrn && inpBrn.value.trim()) ? inpBrn.value.trim().toUpperCase() : '------';
            if (prevVisaStat) prevVisaStat.textContent = (inpVisaStat ? inpVisaStat.value : 'PENDING').toUpperCase();

            // Flight Date
            if (inpFlight && inpFlight.value) {
                const d = new Date(inpFlight.value);
                const options = { day: '2-digit', month: 'short', year: 'numeric' };
                if (prevFlightDisp) prevFlightDisp.textContent = d.toLocaleDateString('en-GB', options).toUpperCase();
            } else {
                if (prevFlightDisp) prevFlightDisp.textContent = 'TBD';
            }

            // Financial Calculations
            const buyVal  = parseFloat(inpBuy ? inpBuy.value : 0) || 0;
            const sellVal = parseFloat(inpSell ? inpSell.value : 0) || 0;
            const profit  = sellVal - buyVal;
            const ratio   = sellVal > 0 ? ((profit / sellVal) * 100).toFixed(1) : '0.0';

            if (prevSell) prevSell.textContent = '৳' + sellVal.toLocaleString('en-US', { minimumFractionDigits: 2 });
            if (profitDisplay) {
                profitDisplay.value = profit.toLocaleString('en-US', { minimumFractionDigits: 2 });
                profitDisplay.className = 'ifs-input-field font-mono font-bold ' + (profit >= 0 ? 'profit-positive' : 'profit-negative');
            }

            if (intelProfit) {
                intelProfit.textContent = '৳' + profit.toLocaleString('en-US', { minimumFractionDigits: 2 });
                intelProfit.className = (profit >= 0) ? 'color-green' : 'color-rose';
            }
            if (intelRatio) intelRatio.textContent = ratio + '%';
        }

        const watchFields = [inpCustomer, inpPackage, inpSharing, inpBrn, inpMofaza, inpFlight, inpVisaStat, inpBuy, inpSell];
        watchFields.forEach(el => {
            if (el) {
                el.addEventListener('input', updatePilgrimCard);
                el.addEventListener('change', updatePilgrimCard);
            }
        });

        updatePilgrimCard();
    });
    </script>
    <?php
}