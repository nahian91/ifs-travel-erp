<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Enterprise Next-Gen Add/Edit Customer Console (100% GDS & Aviation Ready)
 * Every Input Box Crafted With A Dedicated Dashicon & Seamless Micro-Interactions
 */
function ifs_terp_customer_add_edit_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'iterp_customers';
    
    $id       = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;
    $is_edit  = ( $id > 0 );
    $message  = '';
    $errors   = array();
    $base_url = admin_url( 'admin.php?page=ifs_travel_erp&tab=customers' );

    if ( function_exists( 'wp_enqueue_media' ) ) {
        wp_enqueue_media();
    }

    // Handle Form Submission
    if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['ifs_customer_submit'] ) ) {
        check_admin_referer( 'ifs_customer_save_action', 'ifs_customer_nonce' );

        $title                = sanitize_text_field( $_POST['title'] ?? 'MR' );
        $given_name           = strtoupper( sanitize_text_field( $_POST['given_name'] ?? '' ) );
        $surname              = strtoupper( sanitize_text_field( $_POST['surname'] ?? '' ) );
        $full_name            = trim( $given_name . ' ' . $surname );
        $gender               = sanitize_text_field( $_POST['gender'] ?? 'Male' );
        $date_of_birth        = sanitize_text_field( $_POST['date_of_birth'] ?? '' );
        $blood_group          = sanitize_text_field( $_POST['blood_group'] ?? '' );
        $nationality          = sanitize_text_field( $_POST['nationality'] ?? 'Bangladeshi' );
        $nid_no               = sanitize_text_field( $_POST['nid_no'] ?? '' );
        $mobile               = sanitize_text_field( $_POST['mobile'] ?? '' );
        $email                = sanitize_email( $_POST['email'] ?? '' );
        $emergency_contact    = sanitize_text_field( $_POST['emergency_contact'] ?? '' );
        $passport_no          = strtoupper( sanitize_text_field( $_POST['passport_no'] ?? '' ) );
        $passport_issue_date  = sanitize_text_field( $_POST['passport_issue_date'] ?? '' );
        $passport_expiry      = sanitize_text_field( $_POST['passport_expiry'] ?? '' );
        $passport_issue_place = sanitize_text_field( $_POST['passport_issue_place'] ?? '' );
        $frequent_flyer_no    = sanitize_text_field( $_POST['frequent_flyer_no'] ?? '' );
        $meal_preference      = sanitize_text_field( $_POST['meal_preference'] ?? 'MOML' );
        $client_type          = sanitize_text_field( $_POST['client_type'] ?? 'Retail' );
        $address              = sanitize_textarea_field( $_POST['address'] ?? '' );
        $passport_copy        = esc_url_raw( $_POST['passport_copy_url'] ?? '' );

        if ( empty( $given_name ) ) {
            $errors[] = 'Passenger given name is required.';
        }
        if ( empty( $mobile ) ) {
            $errors[] = 'Primary contact mobile number is required.';
        }

        if ( empty( $errors ) ) {
            $data = array(
                'title'                => $title,
                'full_name'            => $full_name,
                'gender'               => $gender,
                'date_of_birth'        => ! empty( $date_of_birth ) ? $date_of_birth : '1970-01-01',
                'nationality'          => $nationality,
                'nid_no'               => $nid_no,
                'mobile'               => $mobile,
                'email'                => $email,
                'emergency_contact'    => $emergency_contact,
                'passport_no'          => $passport_no,
                'passport_issue_date'  => ! empty( $passport_issue_date ) ? $passport_issue_date : '1970-01-01',
                'passport_expiry'      => ! empty( $passport_expiry ) ? $passport_expiry : '1970-01-01',
                'passport_issue_place' => $passport_issue_place,
                'client_type'          => $client_type,
                'address'              => $address,
                'passport_copy_url'    => $passport_copy
            );

            if ( $is_edit ) {
                $wpdb->update( $table_name, $data, array( 'id' => $id ) );
                $message = '<div class="ifs-toast success"><span class="dashicons dashicons-yes-alt"></span> Traveler portfolio updated successfully.</div>';
            } else {
                $data['created_at'] = current_time( 'mysql' );
                $wpdb->insert( $table_name, $data );
                $id      = $wpdb->insert_id;
                $is_edit = true;
                $message = '<div class="ifs-toast success"><span class="dashicons dashicons-yes-alt"></span> New traveler profile registered (#CUS-' . str_pad( (string) $id, 5, '0', STR_PAD_LEFT ) . ').</div>';
            }
            
            if ( function_exists( 'ifs_terp_log_activity' ) ) {
                ifs_terp_log_activity( "Updated Customer Record #CUS-" . $id . " (" . $title . " " . $full_name . ")" );
            }
        } else {
            $message = '<div class="ifs-toast danger"><span class="dashicons dashicons-dismiss"></span> ' . implode( '<br>', $errors ) . '</div>';
        }
    }

    $row = false;
    if ( $is_edit ) {
        $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE id = %d", $id ) );
    }

    // Split Name for edit mode
    $raw_name   = $is_edit ? $row->full_name : '';
    $name_parts = explode( ' ', $raw_name );
    $val_surname = ( count( $name_parts ) > 1 ) ? array_pop( $name_parts ) : '';
    $val_given   = implode( ' ', $name_parts ) ?: $raw_name;

    // Default Values
    $val_title       = $is_edit ? esc_attr( $row->title ?? 'MR' ) : 'MR';
    $val_gender      = $is_edit ? esc_attr( $row->gender ?? 'Male' ) : 'Male';
    $val_dob         = ( $is_edit && ! empty( $row->date_of_birth ) && $row->date_of_birth !== '1970-01-01' ) ? esc_attr( $row->date_of_birth ) : '';
    $val_nation      = $is_edit ? esc_attr( $row->nationality ?? 'Bangladeshi' ) : 'Bangladeshi';
    $val_nid         = $is_edit ? esc_attr( $row->nid_no ?? '' ) : '';
    $val_mobile      = $is_edit ? esc_attr( $row->mobile ?? '' ) : '';
    $val_email       = $is_edit ? esc_attr( $row->email ?? '' ) : '';
    $val_emergency   = $is_edit ? esc_attr( $row->emergency_contact ?? '' ) : '';
    $val_passport    = $is_edit ? esc_attr( $row->passport_no ?? '' ) : '';
    $val_issue_date  = ( $is_edit && ! empty( $row->passport_issue_date ) && $row->passport_issue_date !== '1970-01-01' ) ? esc_attr( $row->passport_issue_date ) : '';
    $val_expiry      = ( $is_edit && ! empty( $row->passport_expiry ) && $row->passport_expiry !== '1970-01-01' ) ? esc_attr( $row->passport_expiry ) : '';
    $val_issue_place = $is_edit ? esc_attr( $row->passport_issue_place ?? '' ) : '';
    $val_type        = $is_edit ? esc_attr( $row->client_type ?? 'Retail' ) : 'Retail';
    $val_address     = $is_edit ? esc_textarea( $row->address ?? '' ) : '';
    $val_copy        = $is_edit ? esc_url( $row->passport_copy_url ?? '' ) : '';
    ?>

    <div class="ifs-editor-workspace">
        <?php echo $message; ?>

        <form method="post" action="" id="ifsCustomerForm" class="ifs-split-editor">
            <?php wp_nonce_field( 'ifs_customer_save_action', 'ifs_customer_nonce' ); ?>
            
            <div class="ifs-form-body">
                
                <!-- Section 1: Demographics & Personal Details -->
                <div class="ifs-panel-card">
                    <div class="ifs-card-header">
                        <div class="ifs-step-num">01</div>
                        <div>
                            <h3 class="ifs-card-title">Demographics & Identity Credentials</h3>
                            <p class="ifs-card-desc">GDS split name format, national ID, and demographic parameters</p>
                        </div>
                    </div>

                    <div class="ifs-grid-3">
                        <!-- Title / Honorific -->
                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_title">Title / Honorific</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-welcome-learn-more field-icon"></span>
                                <select name="title" id="inp_title" class="ifs-input-field">
                                    <option value="MR" <?php selected( $val_title, 'MR' ); ?>>MR</option>
                                    <option value="MRS" <?php selected( $val_title, 'MRS' ); ?>>MRS</option>
                                    <option value="MS" <?php selected( $val_title, 'MS' ); ?>>MS</option>
                                    <option value="MSTR" <?php selected( $val_title, 'MSTR' ); ?>>MSTR (Child Male)</option>
                                    <option value="MISS" <?php selected( $val_title, 'MISS' ); ?>>MISS (Child Female)</option>
                                    <option value="DR" <?php selected( $val_title, 'DR' ); ?>>DR</option>
                                </select>
                            </div>
                        </div>

                        <!-- Given Name -->
                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_given_name">Given / First Name <span class="req">*</span></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-admin-users field-icon"></span>
                                <input type="text" name="given_name" id="inp_given_name" required 
                                       value="<?php echo esc_attr( $val_given ); ?>" 
                                       placeholder="e.g. MOHAMMED" class="ifs-input-field uppercase">
                            </div>
                        </div>

                        <!-- Surname -->
                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_surname">Surname / Last Name</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-nametag field-icon"></span>
                                <input type="text" name="surname" id="inp_surname" 
                                       value="<?php echo esc_attr( $val_surname ); ?>" 
                                       placeholder="e.g. RAHIM" class="ifs-input-field uppercase">
                            </div>
                        </div>

                        <!-- Gender -->
                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_gender">Gender</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-universal-access field-icon"></span>
                                <select name="gender" id="inp_gender" class="ifs-input-field">
                                    <option value="Male" <?php selected( $val_gender, 'Male' ); ?>>Male</option>
                                    <option value="Female" <?php selected( $val_gender, 'Female' ); ?>>Female</option>
                                    <option value="Other" <?php selected( $val_gender, 'Other' ); ?>>Other</option>
                                </select>
                            </div>
                        </div>

                        <!-- Date of Birth -->
                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_dob">Date of Birth</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-calendar-alt field-icon"></span>
                                <input type="date" name="date_of_birth" id="inp_dob" 
                                       value="<?php echo $val_dob; ?>" class="ifs-input-field">
                            </div>
                        </div>

                        <!-- Blood Group -->
                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_blood">Blood Group</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-heart field-icon"></span>
                                <select name="blood_group" id="inp_blood" class="ifs-input-field">
                                    <option value="">-- Unknown --</option>
                                    <option value="A+">A+</option>
                                    <option value="A-">A-</option>
                                    <option value="B+">B+</option>
                                    <option value="B-">B-</option>
                                    <option value="O+">O+</option>
                                    <option value="O-">O-</option>
                                    <option value="AB+">AB+</option>
                                    <option value="AB-">AB-</option>
                                </select>
                            </div>
                        </div>

                        <!-- Nationality -->
                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_nation">Nationality</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-admin-site-alt3 field-icon"></span>
                                <input type="text" name="nationality" id="inp_nation" 
                                       value="<?php echo $val_nation; ?>" placeholder="Bangladeshi" class="ifs-input-field">
                            </div>
                        </div>

                        <!-- National ID -->
                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_nid">National ID (NID / Smart Card)</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-id-alt field-icon"></span>
                                <input type="text" name="nid_no" id="inp_nid" 
                                       value="<?php echo $val_nid; ?>" placeholder="e.g. 1990123456789" class="ifs-input-field font-mono">
                            </div>
                        </div>

                        <!-- Classification Tier -->
                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_type">Classification Tier</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-tag field-icon"></span>
                                <select name="client_type" id="inp_type" class="ifs-input-field">
                                    <option value="Retail" <?php selected( $val_type, 'Retail' ); ?>>Retail Passenger</option>
                                    <option value="Corporate" <?php selected( $val_type, 'Corporate' ); ?>>Corporate Client</option>
                                    <option value="VIP" <?php selected( $val_type, 'VIP' ); ?>>VIP Traveler</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Contact & Emergency -->
                <div class="ifs-panel-card">
                    <div class="ifs-card-header">
                        <div class="ifs-step-num">02</div>
                        <div>
                            <h3 class="ifs-card-title">Communication & Emergency Contacts</h3>
                            <p class="ifs-card-desc">Primary contact lines and relative contact during overseas transit / Hajj</p>
                        </div>
                    </div>

                    <div class="ifs-grid-3">
                        <!-- Primary Mobile -->
                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_mobile">Primary Mobile <span class="req">*</span></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-phone field-icon"></span>
                                <input type="text" name="mobile" id="inp_mobile" required 
                                       value="<?php echo $val_mobile; ?>" 
                                       placeholder="+880 1711-000000" class="ifs-input-field">
                            </div>
                        </div>

                        <!-- Email Address -->
                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_email">Email Address</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-email field-icon"></span>
                                <input type="email" name="email" id="inp_email" 
                                       value="<?php echo $val_email; ?>" 
                                       placeholder="passenger@mail.com" class="ifs-input-field">
                            </div>
                        </div>

                        <!-- Emergency Contact -->
                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_emergency">Emergency Contact (Name & Tel)</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-sos field-icon"></span>
                                <input type="text" name="emergency_contact" id="inp_emergency" 
                                       value="<?php echo $val_emergency; ?>" 
                                       placeholder="Brother: 01712-XXXXXX" class="ifs-input-field">
                            </div>
                        </div>

                        <!-- Permanent Address -->
                        <div class="ifs-field-block col-span-3">
                            <label class="ifs-field-label" for="inp_address">Permanent / Residential Address</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-location-alt field-icon textarea-icon"></span>
                                <textarea name="address" id="inp_address" rows="2" class="ifs-input-field has-textarea-icon" 
                                          placeholder="House/Apartment, Road, Area, District, Country..."><?php echo $val_address; ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 3: Passport, GDS & Flight Preferences -->
                <div class="ifs-panel-card">
                    <div class="ifs-card-header">
                        <div class="ifs-step-num">03</div>
                        <div>
                            <h3 class="ifs-card-title">Passport Details & Flight Preferences (SSR)</h3>
                            <p class="ifs-card-desc">Travel document compliance and airline special service requests</p>
                        </div>
                    </div>

                    <div class="ifs-grid-3">
                        <!-- Passport Number -->
                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_passport">Passport Number</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-id field-icon"></span>
                                <input type="text" name="passport_no" id="inp_passport" 
                                       value="<?php echo $val_passport; ?>" 
                                       placeholder="A01234567" class="ifs-input-field uppercase font-mono">
                            </div>
                        </div>

                        <!-- Issue Date -->
                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_issue_date">Issue Date</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-calendar field-icon"></span>
                                <input type="date" name="passport_issue_date" id="inp_issue_date" 
                                       value="<?php echo $val_issue_date; ?>" class="ifs-input-field">
                            </div>
                        </div>

                        <!-- Expiry Date -->
                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_expiry">Expiry Date</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-clock field-icon"></span>
                                <input type="date" name="passport_expiry" id="inp_expiry" 
                                       value="<?php echo $val_expiry; ?>" class="ifs-input-field">
                            </div>
                        </div>

                        <!-- Place of Issue -->
                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_issue_place">Place of Issue</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-building field-icon"></span>
                                <input type="text" name="passport_issue_place" id="inp_issue_place" 
                                       value="<?php echo $val_issue_place; ?>" placeholder="e.g. Dhaka / Sylhet" class="ifs-input-field">
                            </div>
                        </div>

                        <!-- Meal Preference (SSR) -->
                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_meal">Meal Preference (SSR)</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-coffee field-icon"></span>
                                <select name="meal_preference" id="inp_meal" class="ifs-input-field">
                                    <option value="MOML">Moslem / Halal Meal (MOML)</option>
                                    <option value="AVML">Asian Vegetarian (AVML)</option>
                                    <option value="VGML">Strict Vegan (VGML)</option>
                                    <option value="CHML">Child Meal (CHML)</option>
                                    <option value="DBML">Diabetic Meal (DBML)</option>
                                </select>
                            </div>
                        </div>

                        <!-- Frequent Flyer Number -->
                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_ffn">Frequent Flyer No (FFN)</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-star-filled field-icon"></span>
                                <input type="text" name="frequent_flyer_no" id="inp_ffn" 
                                       placeholder="e.g. EK-123456789" class="ifs-input-field font-mono uppercase">
                            </div>
                        </div>

                        <!-- Scanned Passport Attachment -->
                        <div class="ifs-field-block col-span-3">
                            <label class="ifs-field-label" for="inp_passport_copy">Scanned Passport Document URL</label>
                            <div class="ifs-media-uploader-box">
                                <div class="ifs-field-wrap" style="flex: 1;">
                                    <span class="dashicons dashicons-media-document field-icon"></span>
                                    <input type="text" name="passport_copy_url" id="inp_passport_copy" 
                                           value="<?php echo $val_copy; ?>" 
                                           placeholder="Upload scanned passport PDF/Image" class="ifs-input-field">
                                </div>
                                <button type="button" class="ifs-btn-upload" id="ifsUploadBtn">
                                    <span class="dashicons dashicons-upload"></span> Media
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Toolbar -->
                <div class="ifs-action-strip">
                    <a href="<?php echo esc_url( $base_url . '&sub=list' ); ?>" class="ifs-btn-back">
                        <span class="dashicons dashicons-arrow-left-alt"></span> Cancel
                    </a>
                    <button type="submit" name="ifs_customer_submit" class="ifs-btn-primary">
                        <span class="dashicons dashicons-saved"></span> 
                        <?php echo $is_edit ? 'Update Traveler Profile' : 'Register Customer'; ?>
                    </button>
                </div>
            </div>

            <!-- Right Sidebar: Digital Traveler Identity Card -->
            <div class="ifs-preview-sidebar">
                <div class="ifs-preview-sticky">
                    <div class="ifs-card-preview-header">
                        <span class="dashicons dashicons-id"></span> Live GDS Passenger Profile
                    </div>

                    <div class="ifs-travel-card">
                        <div class="card-chip-strip">
                            <span class="card-airline-tag" id="prev_nation">BANGLADESHI</span>
                            <span class="card-tier-badge" id="prev_tier">RETAIL</span>
                        </div>

                        <div class="card-profile-hero">
                            <div class="card-avatar" id="prev_avatar">MR</div>
                            <div>
                                <h4 class="card-name" id="prev_name">MR. MOHAMMED RAHIM</h4>
                                <div class="card-id" id="prev_id">ID: #CUS-<?php echo $is_edit ? str_pad((string)$id, 5, '0', STR_PAD_LEFT) : 'NEW'; ?></div>
                            </div>
                        </div>

                        <div class="card-data-grid">
                            <div>
                                <span class="card-label">PASSPORT NO</span>
                                <span class="card-val font-mono" id="prev_passport">NOT SET</span>
                            </div>
                            <div>
                                <span class="card-label">EXPIRY STATUS</span>
                                <span class="card-val" id="prev_expiry">NO DATE</span>
                            </div>
                            <div>
                                <span class="card-label">GENDER / DOB</span>
                                <span class="card-val" id="prev_gender_dob">Male, -</span>
                            </div>
                            <div>
                                <span class="card-label">CONTACT MOBILE</span>
                                <span class="card-val" id="prev_mobile">+880 17...</span>
                            </div>
                        </div>

                        <div class="card-barcode-strip">
                            <div class="barcode-lines"></div>
                            <span class="barcode-code font-mono" id="prev_barcode">||| | | || ||| || ||| |</span>
                        </div>
                    </div>

                    <div class="ifs-tip-box">
                        <div class="tip-title"><span class="dashicons dashicons-airplane"></span> IATA & GDS Split Name Protocol</div>
                        <p class="tip-body">Separating <strong>Given Name</strong> and <strong>Surname</strong> ensures 100% error-free PNR passenger name generation for Sabre & Amadeus GDS systems.</p>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <!-- Ultra High-End UI Stylesheet -->
    <style>
        .ifs-editor-workspace { max-width: 1400px; margin: 0 auto; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; color: #0f172a; }
        .ifs-toast { padding: 14px 20px; border-radius: 10px; font-size: 13.5px; font-weight: 600; display: flex; align-items: center; gap: 10px; margin-bottom: 22px; box-shadow: 0 4px 12px rgba(0,0,0,0.03); }
        .ifs-toast.success { background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; }
        .ifs-toast.danger  { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
        
        .ifs-split-editor { display: grid; grid-template-columns: 1fr 380px; gap: 28px; align-items: flex-start; }
        @media (max-width: 1120px) { .ifs-split-editor { grid-template-columns: 1fr; } }
        
        .ifs-panel-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 26px; margin-bottom: 22px; box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.03); }
        .ifs-card-header { display: flex; align-items: center; gap: 14px; margin-bottom: 22px; padding-bottom: 14px; border-bottom: 1px solid #f1f5f9; }
        .ifs-step-num { width: 34px; height: 34px; border-radius: 9px; background: linear-gradient(135deg, #003376 0%, #0284c7 100%); color: #ffffff; font-weight: 800; font-size: 13px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px rgba(0, 51, 118, 0.2); flex-shrink: 0; }
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

        .ifs-field-wrap select.ifs-input-field {
            appearance: none;
            -webkit-appearance: none;
            background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 14px;
            padding-right: 32px !important;
        }

        textarea.ifs-input-field.has-textarea-icon { padding: 10px 12px 10px 38px !important; font-family: inherit; }
        .ifs-field-wrap .ifs-input-field:focus { border-color: #003376; box-shadow: 0 0 0 3px rgba(0, 51, 118, 0.12); }
        .ifs-field-wrap:focus-within .field-icon { color: #003376; }

        .uppercase { text-transform: uppercase; }
        .font-mono { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-weight: 700; }
        
        .ifs-media-uploader-box { display: flex; gap: 8px; align-items: center; width: 100%; }
        .ifs-btn-upload { background: #f1f5f9; border: 1px solid #cbd5e1; height: 38px; padding: 0 16px; border-radius: 8px; font-size: 12.5px; font-weight: 600; color: #334155; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; white-space: nowrap; transition: all 0.2s ease; }
        .ifs-btn-upload:hover { background: #e2e8f0; color: #0f172a; }
        
        .ifs-action-strip { display: flex; justify-content: space-between; align-items: center; padding: 18px 24px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; }
        .ifs-btn-back { color: #64748b; text-decoration: none; font-size: 13.5px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; }
        .ifs-btn-primary { background: linear-gradient(135deg, #003376 0%, #0284c7 100%); color: #ffffff; border: none; padding: 11px 26px; border-radius: 8px; font-size: 13.5px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 14px rgba(0, 51, 118, 0.25); }
        
        .ifs-preview-sticky { position: sticky; top: 30px; }
        .ifs-card-preview-header { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b; margin-bottom: 12px; display: flex; align-items: center; gap: 6px; }
        .ifs-travel-card { background: linear-gradient(135deg, #001e47 0%, #003376 50%, #0369a1 100%); border-radius: 16px; padding: 22px; color: #ffffff; box-shadow: 0 16px 32px -6px rgba(0, 51, 118, 0.35); position: relative; overflow: hidden; }
        .card-chip-strip { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
        .card-airline-tag { font-size: 9.5px; font-weight: 800; letter-spacing: 1px; color: #bae6fd; }
        .card-tier-badge { background: rgba(255, 255, 255, 0.18); backdrop-filter: blur(4px); padding: 2px 8px; border-radius: 4px; font-size: 9.5px; font-weight: 800; letter-spacing: 0.5px; }
        .card-profile-hero { display: flex; align-items: center; gap: 14px; margin-bottom: 18px; }
        .card-avatar { width: 46px; height: 46px; border-radius: 12px; background: rgba(255, 255, 255, 0.2); border: 1px solid rgba(255, 255, 255, 0.3); display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 15px; }
        .card-name { margin: 0; font-size: 14px; font-weight: 800; color: #ffffff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 210px; }
        .card-id { font-size: 11px; color: #bae6fd; font-family: monospace; }
        .card-data-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; padding-top: 14px; border-top: 1px dashed rgba(255, 255, 255, 0.2); margin-bottom: 16px; }
        .card-label { display: block; font-size: 8.5px; font-weight: 700; color: #93c5fd; letter-spacing: 0.5px; margin-bottom: 2px; }
        .card-val { font-size: 11.5px; font-weight: 700; color: #ffffff; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .card-barcode-strip { border-top: 1px solid rgba(255, 255, 255, 0.15); padding-top: 10px; text-align: center; }
        .barcode-lines { height: 16px; background: repeating-linear-gradient(90deg, #ffffff, #ffffff 2px, transparent 2px, transparent 4px, #ffffff 4px, #ffffff 5px, transparent 5px, transparent 8px); opacity: 0.7; margin-bottom: 4px; }
        .barcode-code { font-size: 9px; color: #93c5fd; letter-spacing: 2px; }
        .ifs-tip-box { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; margin-top: 18px; }
        .tip-title { font-size: 12px; font-weight: 700; color: #003376; display: flex; align-items: center; gap: 6px; margin-bottom: 6px; }
        .tip-body { margin: 0; font-size: 12px; color: #64748b; line-height: 1.5; }
    </style>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const inpTitle     = document.getElementById('inp_title');
        const inpGivenName = document.getElementById('inp_given_name');
        const inpSurname   = document.getElementById('inp_surname');
        const inpGender    = document.getElementById('inp_gender');
        const inpDob       = document.getElementById('inp_dob');
        const inpNation    = document.getElementById('inp_nation');
        const inpMobile    = document.getElementById('inp_mobile');
        const inpPassport  = document.getElementById('inp_passport');
        const inpExpiry    = document.getElementById('inp_expiry');
        const inpType      = document.getElementById('inp_type');

        const prevName      = document.getElementById('prev_name');
        const prevAvatar    = document.getElementById('prev_avatar');
        const prevMobile    = document.getElementById('prev_mobile');
        const prevPassport  = document.getElementById('prev_passport');
        const prevExpiry    = document.getElementById('prev_expiry');
        const prevTier      = document.getElementById('prev_tier');
        const prevNation    = document.getElementById('prev_nation');
        const prevGenderDob = document.getElementById('prev_gender_dob');

        function updateCardLive() {
            const titleVal = inpTitle ? inpTitle.value : 'MR';
            const given    = inpGivenName ? inpGivenName.value.trim() : '';
            const surname  = inpSurname ? inpSurname.value.trim() : '';
            const full     = (given + ' ' + surname).trim();
            
            if (full) {
                prevName.textContent = titleVal + '. ' + full.toUpperCase();
                const parts = full.split(' ');
                prevAvatar.textContent = parts.length > 1 ? (parts[0][0] + parts[parts.length-1][0]).toUpperCase() : parts[0].slice(0, 2).toUpperCase();
            } else {
                prevName.textContent = titleVal + '. MOHAMMED RAHIM';
                prevAvatar.textContent = 'MR';
            }

            if (prevMobile) prevMobile.textContent     = (inpMobile && inpMobile.value.trim()) ? inpMobile.value.trim() : '+880 17...';
            if (prevPassport) prevPassport.textContent = (inpPassport && inpPassport.value.trim()) ? inpPassport.value.trim().toUpperCase() : 'NOT SET';
            if (prevTier) prevTier.textContent         = inpType ? inpType.value.toUpperCase() : 'RETAIL';
            if (prevNation) prevNation.textContent     = (inpNation && inpNation.value.trim()) ? inpNation.value.trim().toUpperCase() : 'BANGLADESHI';

            const gVal = inpGender ? inpGender.value : 'Male';
            const dVal = (inpDob && inpDob.value) ? inpDob.value : '-';
            if (prevGenderDob) prevGenderDob.textContent = gVal + ', ' + dVal;

            if (inpExpiry && inpExpiry.value) {
                const expDate  = new Date(inpExpiry.value);
                const today    = new Date();
                const diffDays = Math.ceil((expDate - today) / (1000 * 60 * 60 * 24));

                if (diffDays < 0) {
                    prevExpiry.textContent = 'EXPIRED';
                    prevExpiry.style.color = '#f87171';
                } else if (diffDays < 180) {
                    prevExpiry.textContent = '< 6 MOS WARNING';
                    prevExpiry.style.color = '#fde047';
                } else {
                    prevExpiry.textContent = 'VALID (' + expDate.getFullYear() + ')';
                    prevExpiry.style.color = '#86efac';
                }
            } else if (prevExpiry) {
                prevExpiry.textContent = 'NO DATE';
                prevExpiry.style.color = '#ffffff';
            }
        }

        [inpTitle, inpGivenName, inpSurname, inpGender, inpDob, inpNation, inpMobile, inpPassport, inpExpiry, inpType].forEach(el => {
            if (el) {
                el.addEventListener('input', updateCardLive);
                el.addEventListener('change', updateCardLive);
            }
        });

        updateCardLive();

        // WP Media Uploader Integration
        const uploadBtn = document.getElementById('ifsUploadBtn');
        const copyInput = document.getElementById('inp_passport_copy');
        if (uploadBtn && window.wp && wp.media) {
            uploadBtn.addEventListener('click', function(e) {
                e.preventDefault();
                const customUploader = wp.media({
                    title: 'Select Passport Scan or Document',
                    button: { text: 'Attach File' },
                    multiple: false
                }).on('select', function() {
                    const attachment = customUploader.state().get('selection').first().toJSON();
                    if (copyInput && attachment.url) {
                        copyInput.value = attachment.url;
                    }
                }).open();
            });
        }
    });
    </script>
    <?php
}