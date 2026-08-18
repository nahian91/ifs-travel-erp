<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Enterprise Next-Gen Add/Edit Customer Console (Ultra-Premium Redesign)
 * Features: Interactive Document Vault with Live Mini-Previews, ICAO/IATA Holographic Boarding Pass & Live Reactive GDS Profile
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
        $father_spouse_name   = sanitize_text_field( $_POST['father_spouse_name'] ?? '' );
        $mother_name          = sanitize_text_field( $_POST['mother_name'] ?? '' );
        $gender               = sanitize_text_field( $_POST['gender'] ?? 'Male' );
        $marital_status       = sanitize_text_field( $_POST['marital_status'] ?? 'Married' );
        $passenger_type       = sanitize_text_field( $_POST['passenger_type'] ?? 'Adult' );
        $date_of_birth        = sanitize_text_field( $_POST['date_of_birth'] ?? '' );
        $birth_place          = sanitize_text_field( $_POST['birth_place'] ?? '' );
        $blood_group          = sanitize_text_field( $_POST['blood_group'] ?? '' );
        $nationality          = sanitize_text_field( $_POST['nationality'] ?? 'Bangladeshi' );
        $nid_no               = sanitize_text_field( $_POST['nid_no'] ?? '' );
        $profession           = sanitize_text_field( $_POST['profession'] ?? '' );
        
        $mobile               = sanitize_text_field( $_POST['mobile'] ?? '' );
        $whatsapp_no          = sanitize_text_field( $_POST['whatsapp_no'] ?? '' );
        $email                = sanitize_email( $_POST['email'] ?? '' );
        $emergency_contact    = sanitize_text_field( $_POST['emergency_contact'] ?? '' );
        
        $passport_no          = strtoupper( sanitize_text_field( $_POST['passport_no'] ?? '' ) );
        $prev_passport_no     = strtoupper( sanitize_text_field( $_POST['prev_passport_no'] ?? '' ) );
        $passport_type        = sanitize_text_field( $_POST['passport_type'] ?? 'Regular' );
        $passport_issue_date  = sanitize_text_field( $_POST['passport_issue_date'] ?? '' );
        $passport_expiry      = sanitize_text_field( $_POST['passport_expiry'] ?? '' );
        $passport_issue_place = sanitize_text_field( $_POST['passport_issue_place'] ?? '' );
        
        $frequent_flyer_no    = strtoupper( sanitize_text_field( $_POST['frequent_flyer_no'] ?? '' ) );
        $meal_preference      = sanitize_text_field( $_POST['meal_preference'] ?? 'MOML' );
        $wheelchair_ssr       = sanitize_text_field( $_POST['wheelchair_ssr'] ?? 'NONE' );
        $client_type          = sanitize_text_field( $_POST['client_type'] ?? 'Retail' );
        $city                 = sanitize_text_field( $_POST['city'] ?? '' );
        $address              = sanitize_textarea_field( $_POST['address'] ?? '' );
        $photo_url            = esc_url_raw( $_POST['photo_url'] ?? '' );
        $passport_copy        = esc_url_raw( $_POST['passport_copy_url'] ?? '' );
        $nid_copy_url         = esc_url_raw( $_POST['nid_copy_url'] ?? '' );

        if ( empty( $given_name ) ) {
            $errors[] = 'Passenger given name is required.';
        }
        if ( empty( $mobile ) ) {
            $errors[] = 'Primary contact mobile number is required.';
        }

        if ( empty( $errors ) ) {
            $data = array(
                'title'                => $title,
                'passport_given_name'  => $given_name,
                'passport_surname'     => $surname,
                'full_name'            => $full_name,
                'father_spouse_name'   => $father_spouse_name,
                'mother_name'          => $mother_name,
                'gender'               => $gender,
                'marital_status'       => $marital_status,
                'passenger_type'       => $passenger_type,
                'date_of_birth'        => ! empty( $date_of_birth ) ? $date_of_birth : '1970-01-01',
                'birth_place'          => $birth_place,
                'blood_group'          => $blood_group,
                'nationality'          => $nationality,
                'nid_no'               => $nid_no,
                'profession'           => $profession,
                'mobile'               => $mobile,
                'whatsapp_no'          => $whatsapp_no,
                'email'                => $email,
                'emergency_contact'    => $emergency_contact,
                'passport_no'          => $passport_no,
                'prev_passport_no'     => $prev_passport_no,
                'passport_type'        => $passport_type,
                'passport_issue_date'  => ! empty( $passport_issue_date ) ? $passport_issue_date : '1970-01-01',
                'passport_expiry'      => ! empty( $passport_expiry ) ? $passport_expiry : '1970-01-01',
                'passport_issue_place' => $passport_issue_place,
                'frequent_flyer_no'    => $frequent_flyer_no,
                'meal_preference'      => $meal_preference,
                'wheelchair_ssr'       => $wheelchair_ssr,
                'client_type'          => $client_type,
                'city'                 => $city,
                'address'              => $address,
                'photo_url'            => $photo_url,
                'passport_copy_url'    => $passport_copy,
                'nid_copy_url'         => $nid_copy_url
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
                ifs_terp_log_activity( "Saved Customer Record #CUS-" . $id . " (" . $title . " " . $full_name . ")" );
            }
        } else {
            $message = '<div class="ifs-toast danger"><span class="dashicons dashicons-dismiss"></span> ' . implode( '<br>', $errors ) . '</div>';
        }
    }

    $row = false;
    if ( $is_edit ) {
        $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE id = %d", $id ) );
    }

    // Split Name fallback
    $val_given    = $is_edit ? esc_attr( $row->passport_given_name ?? '' ) : '';
    $val_surname  = $is_edit ? esc_attr( $row->passport_surname ?? '' ) : '';
    if ( empty( $val_given ) && $is_edit && ! empty( $row->full_name ) ) {
        $name_parts  = explode( ' ', trim( $row->full_name ) );
        $val_surname = ( count( $name_parts ) > 1 ) ? array_pop( $name_parts ) : '';
        $val_given   = implode( ' ', $name_parts ) ?: $row->full_name;
    }

    // Field Values
    $val_title       = $is_edit ? esc_attr( $row->title ?? 'MR' ) : 'MR';
    $val_father      = $is_edit ? esc_attr( $row->father_spouse_name ?? '' ) : '';
    $val_mother      = $is_edit ? esc_attr( $row->mother_name ?? '' ) : '';
    $val_gender      = $is_edit ? esc_attr( $row->gender ?? 'Male' ) : 'Male';
    $val_marital     = $is_edit ? esc_attr( $row->marital_status ?? 'Married' ) : 'Married';
    $val_ptype       = $is_edit ? esc_attr( $row->passenger_type ?? 'Adult' ) : 'Adult';
    $val_dob         = ( $is_edit && ! empty( $row->date_of_birth ) && $row->date_of_birth !== '1970-01-01' ) ? esc_attr( $row->date_of_birth ) : '';
    $val_birth_place = $is_edit ? esc_attr( $row->birth_place ?? '' ) : '';
    $val_blood       = $is_edit ? esc_attr( $row->blood_group ?? '' ) : '';
    $val_nation      = $is_edit ? esc_attr( $row->nationality ?? 'Bangladeshi' ) : 'Bangladeshi';
    $val_nid         = $is_edit ? esc_attr( $row->nid_no ?? '' ) : '';
    $val_profession  = $is_edit ? esc_attr( $row->profession ?? '' ) : '';
    
    $val_mobile      = $is_edit ? esc_attr( $row->mobile ?? '' ) : '';
    $val_whatsapp    = $is_edit ? esc_attr( $row->whatsapp_no ?? '' ) : '';
    $val_email       = $is_edit ? esc_attr( $row->email ?? '' ) : '';
    $val_emergency   = $is_edit ? esc_attr( $row->emergency_contact ?? '' ) : '';
    
    $val_passport    = $is_edit ? esc_attr( $row->passport_no ?? '' ) : '';
    $val_prev_pass   = $is_edit ? esc_attr( $row->prev_passport_no ?? '' ) : '';
    $val_pass_type   = $is_edit ? esc_attr( $row->passport_type ?? 'Regular' ) : 'Regular';
    $val_issue_date  = ( $is_edit && ! empty( $row->passport_issue_date ) && $row->passport_issue_date !== '1970-01-01' ) ? esc_attr( $row->passport_issue_date ) : '';
    $val_expiry      = ( $is_edit && ! empty( $row->passport_expiry ) && $row->passport_expiry !== '1970-01-01' ) ? esc_attr( $row->passport_expiry ) : '';
    $val_issue_place = $is_edit ? esc_attr( $row->passport_issue_place ?? '' ) : '';
    
    $val_ffn         = $is_edit ? esc_attr( $row->frequent_flyer_no ?? '' ) : '';
    $val_meal        = $is_edit ? esc_attr( $row->meal_preference ?? 'MOML' ) : 'MOML';
    $val_wheelchair  = $is_edit ? esc_attr( $row->wheelchair_ssr ?? 'NONE' ) : 'NONE';
    $val_type        = $is_edit ? esc_attr( $row->client_type ?? 'Retail' ) : 'Retail';
    $val_city        = $is_edit ? esc_attr( $row->city ?? '' ) : '';
    $val_address     = $is_edit ? esc_textarea( $row->address ?? '' ) : '';
    $val_photo       = $is_edit ? esc_url( $row->photo_url ?? '' ) : '';
    $val_copy        = $is_edit ? esc_url( $row->passport_copy_url ?? '' ) : '';
    $val_nid_copy    = $is_edit ? esc_url( $row->nid_copy_url ?? '' ) : '';
    ?>

    <div class="ifs-editor-workspace">
        <?php echo $message; ?>

        <form method="post" action="" id="ifsCustomerForm" class="ifs-split-editor">
            <?php wp_nonce_field( 'ifs_customer_save_action', 'ifs_customer_nonce' ); ?>
            
            <div class="ifs-form-body">
                
                <!-- Section 1: Demographics & Identity Credentials -->
                <div class="ifs-panel-card">
                    <div class="ifs-card-header">
                        <div class="ifs-step-num">01</div>
                        <div>
                            <h3 class="ifs-card-title">Demographics & Identity Credentials</h3>
                            <p class="ifs-card-desc">GDS split name format, parentage details, profession, and national identification</p>
                        </div>
                    </div>

                    <div class="ifs-grid-3">
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
                                    <option value="HAJI" <?php selected( $val_title, 'HAJI' ); ?>>HAJI</option>
                                </select>
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_given_name">Given / First Name <span class="req">*</span></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-admin-users field-icon"></span>
                                <input type="text" name="given_name" id="inp_given_name" required 
                                       value="<?php echo esc_attr( $val_given ); ?>" 
                                       placeholder="e.g. MOHAMMED" class="ifs-input-field uppercase">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_surname">Surname / Last Name</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-nametag field-icon"></span>
                                <input type="text" name="surname" id="inp_surname" 
                                       value="<?php echo esc_attr( $val_surname ); ?>" 
                                       placeholder="e.g. RAHIM" class="ifs-input-field uppercase">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_father">Father / Spouse Name</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-businessman field-icon"></span>
                                <input type="text" name="father_spouse_name" id="inp_father" 
                                       value="<?php echo $val_father; ?>" placeholder="Father / Husband Name" class="ifs-input-field">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_mother">Mother's Name</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-heart field-icon"></span>
                                <input type="text" name="mother_name" id="inp_mother" 
                                       value="<?php echo $val_mother; ?>" placeholder="Required for Visa / E-Visa" class="ifs-input-field">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_ptype">Passenger Type (GDS)</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-groups field-icon"></span>
                                <select name="passenger_type" id="inp_ptype" class="ifs-input-field">
                                    <option value="Adult" <?php selected( $val_ptype, 'Adult' ); ?>>Adult (12+ Yrs)</option>
                                    <option value="Child" <?php selected( $val_ptype, 'Child' ); ?>>Child (2-11 Yrs)</option>
                                    <option value="Infant" <?php selected( $val_ptype, 'Infant' ); ?>>Infant (Under 2 Yrs)</option>
                                </select>
                            </div>
                        </div>

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

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_marital">Marital Status</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-groups field-icon"></span>
                                <select name="marital_status" id="inp_marital" class="ifs-input-field">
                                    <option value="Married" <?php selected( $val_marital, 'Married' ); ?>>Married</option>
                                    <option value="Single" <?php selected( $val_marital, 'Single' ); ?>>Single / Unmarried</option>
                                    <option value="Divorced" <?php selected( $val_marital, 'Divorced' ); ?>>Divorced</option>
                                    <option value="Widowed" <?php selected( $val_marital, 'Widowed' ); ?>>Widowed</option>
                                </select>
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_dob">Date of Birth</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-calendar-alt field-icon"></span>
                                <input type="date" name="date_of_birth" id="inp_dob" 
                                       value="<?php echo $val_dob; ?>" class="ifs-input-field">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_birth_place">Birth Place / District</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-location field-icon"></span>
                                <input type="text" name="birth_place" id="inp_birth_place" 
                                       value="<?php echo $val_birth_place; ?>" placeholder="e.g. Dhaka / Sylhet" class="ifs-input-field">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_blood">Blood Group</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-heart field-icon"></span>
                                <select name="blood_group" id="inp_blood" class="ifs-input-field">
                                    <option value="">-- Unknown --</option>
                                    <option value="A+" <?php selected( $val_blood, 'A+' ); ?>>A+</option>
                                    <option value="A-" <?php selected( $val_blood, 'A-' ); ?>>A-</option>
                                    <option value="B+" <?php selected( $val_blood, 'B+' ); ?>>B+</option>
                                    <option value="B-" <?php selected( $val_blood, 'B-' ); ?>>B-</option>
                                    <option value="O+" <?php selected( $val_blood, 'O+' ); ?>>O+</option>
                                    <option value="O-" <?php selected( $val_blood, 'O-' ); ?>>O-</option>
                                    <option value="AB+" <?php selected( $val_blood, 'AB+' ); ?>>AB+</option>
                                    <option value="AB-" <?php selected( $val_blood, 'AB-' ); ?>>AB-</option>
                                </select>
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_nation">Nationality</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-admin-site-alt3 field-icon"></span>
                                <input type="text" name="nationality" id="inp_nation" 
                                       value="<?php echo $val_nation; ?>" placeholder="Bangladeshi" class="ifs-input-field">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_nid">National ID (NID / Smart Card)</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-id-alt field-icon"></span>
                                <input type="text" name="nid_no" id="inp_nid" 
                                       value="<?php echo $val_nid; ?>" placeholder="e.g. 1990123456789" class="ifs-input-field font-mono">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_profession">Profession / Occupation</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-portfolio field-icon"></span>
                                <input type="text" name="profession" id="inp_profession" 
                                       value="<?php echo $val_profession; ?>" placeholder="e.g. Businessman / Engineer" class="ifs-input-field">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_type">Client Tier</label>
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

                <!-- Section 2: Contact & Address Logistics -->
                <div class="ifs-panel-card">
                    <div class="ifs-card-header">
                        <div class="ifs-step-num">02</div>
                        <div>
                            <h3 class="ifs-card-title">Communication & Address Logistics</h3>
                            <p class="ifs-card-desc">Direct lines, WhatsApp routing, and emergency contacts during transit</p>
                        </div>
                    </div>

                    <div class="ifs-grid-3">
                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_mobile">Primary Mobile <span class="req">*</span></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-phone field-icon"></span>
                                <input type="text" name="mobile" id="inp_mobile" required 
                                       value="<?php echo $val_mobile; ?>" 
                                       placeholder="+880 1711-000000" class="ifs-input-field">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_whatsapp">WhatsApp Number</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-format-chat field-icon"></span>
                                <input type="text" name="whatsapp_no" id="inp_whatsapp" 
                                       value="<?php echo $val_whatsapp; ?>" 
                                       placeholder="+880 1711-000000" class="ifs-input-field">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_email">Email Address</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-email field-icon"></span>
                                <input type="email" name="email" id="inp_email" 
                                       value="<?php echo $val_email; ?>" 
                                       placeholder="passenger@mail.com" class="ifs-input-field">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_emergency">Emergency Contact</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-sos field-icon"></span>
                                <input type="text" name="emergency_contact" id="inp_emergency" 
                                       value="<?php echo $val_emergency; ?>" 
                                       placeholder="Brother: 01712-XXXXXX" class="ifs-input-field">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_city">City / District</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-admin-multisite field-icon"></span>
                                <input type="text" name="city" id="inp_city" 
                                       value="<?php echo $val_city; ?>" placeholder="e.g. Dhaka, Sylhet" class="ifs-input-field">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_address">Street Address</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-location-alt field-icon textarea-icon"></span>
                                <textarea name="address" id="inp_address" rows="1" class="ifs-input-field has-textarea-icon" 
                                          placeholder="House, Road, Postal Code..."><?php echo $val_address; ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 3: Passport, GDS & Flight Preferences -->
                <div class="ifs-panel-card">
                    <div class="ifs-card-header">
                        <div class="ifs-step-num">03</div>
                        <div>
                            <h3 class="ifs-card-title">Passport Details & Airline Special Service Requests</h3>
                            <p class="ifs-card-desc">Travel document specifications, frequent flyer, wheelchair, and meal preferences</p>
                        </div>
                    </div>

                    <div class="ifs-grid-3">
                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_passport">Passport Number</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-id field-icon"></span>
                                <input type="text" name="passport_no" id="inp_passport" 
                                       value="<?php echo $val_passport; ?>" 
                                       placeholder="A01234567" class="ifs-input-field uppercase font-mono">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_prev_passport">Previous Passport No</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-backup field-icon"></span>
                                <input type="text" name="prev_passport_no" id="inp_prev_passport" 
                                       value="<?php echo $val_prev_pass; ?>" 
                                       placeholder="Old Passport (If any)" class="ifs-input-field uppercase font-mono">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_pass_type">Passport Type</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-media-document field-icon"></span>
                                <select name="passport_type" id="inp_pass_type" class="ifs-input-field">
                                    <option value="Regular" <?php selected( $val_pass_type, 'Regular' ); ?>>Ordinary / Regular</option>
                                    <option value="Official" <?php selected( $val_pass_type, 'Official' ); ?>>Official</option>
                                    <option value="Diplomatic" <?php selected( $val_pass_type, 'Diplomatic' ); ?>>Diplomatic</option>
                                </select>
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_issue_date">Issue Date</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-calendar field-icon"></span>
                                <input type="date" name="passport_issue_date" id="inp_issue_date" 
                                       value="<?php echo $val_issue_date; ?>" class="ifs-input-field">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_expiry">Expiry Date</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-clock field-icon"></span>
                                <input type="date" name="passport_expiry" id="inp_expiry" 
                                       value="<?php echo $val_expiry; ?>" class="ifs-input-field">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_issue_place">Place of Issue</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-building field-icon"></span>
                                <input type="text" name="passport_issue_place" id="inp_issue_place" 
                                       value="<?php echo $val_issue_place; ?>" placeholder="e.g. Dhaka / Sylhet" class="ifs-input-field">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_meal">Meal Preference (SSR)</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-coffee field-icon"></span>
                                <select name="meal_preference" id="inp_meal" class="ifs-input-field">
                                    <option value="MOML" <?php selected( $val_meal, 'MOML' ); ?>>Moslem / Halal Meal (MOML)</option>
                                    <option value="AVML" <?php selected( $val_meal, 'AVML' ); ?>>Asian Vegetarian (AVML)</option>
                                    <option value="VGML" <?php selected( $val_meal, 'VGML' ); ?>>Strict Vegan (VGML)</option>
                                    <option value="CHML" <?php selected( $val_meal, 'CHML' ); ?>>Child Meal (CHML)</option>
                                    <option value="DBML" <?php selected( $val_meal, 'DBML' ); ?>>Diabetic Meal (DBML)</option>
                                </select>
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_wheelchair">Wheelchair / Assistance</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-wheelchair field-icon"></span>
                                <select name="wheelchair_ssr" id="inp_wheelchair" class="ifs-input-field">
                                    <option value="NONE" <?php selected( $val_wheelchair, 'NONE' ); ?>>No Assistance Required</option>
                                    <option value="WCHR" <?php selected( $val_wheelchair, 'WCHR' ); ?>>WCHR (Ramp - Can Climb Stairs)</option>
                                    <option value="WCHS" <?php selected( $val_wheelchair, 'WCHS' ); ?>>WCHS (Steps - Cannot Climb Stairs)</option>
                                    <option value="WCHC" <?php selected( $val_wheelchair, 'WCHC' ); ?>>WCHC (Cabin - Completely Immobile)</option>
                                </select>
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_ffn">Frequent Flyer No (FFN)</label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-star-filled field-icon"></span>
                                <input type="text" name="frequent_flyer_no" id="inp_ffn" 
                                       value="<?php echo $val_ffn; ?>"
                                       placeholder="e.g. EK-123456789" class="ifs-input-field font-mono uppercase">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 4: Interactive Digital Vault & Attachment Manager with Live Previews -->
                <div class="ifs-panel-card">
                    <div class="ifs-card-header">
                        <div class="ifs-step-num">04</div>
                        <div>
                            <h3 class="ifs-card-title">Digital Vault & Attachment Manager</h3>
                            <p class="ifs-card-desc">Interactive document storage with live visual status, upload integration, and direct previews</p>
                        </div>
                    </div>

                    <div class="ifs-vault-grid">
                        
                        <!-- 1. Passenger Portrait -->
                        <div class="ifs-vault-card" id="vault_photo_card">
                            <div class="vault-card-thumb" id="vault_photo_preview">
                                <?php if ( ! empty( $val_photo ) ) : ?>
                                    <img src="<?php echo $val_photo; ?>" alt="Passenger Portrait" />
                                <?php else : ?>
                                    <div class="vault-empty-icon"><span class="dashicons dashicons-camera"></span></div>
                                <?php endif; ?>
                            </div>
                            <div class="vault-card-body">
                                <div class="vault-item-title">Passenger Portrait Photo</div>
                                <div class="vault-item-meta">Standard 2x2 White Background Photo</div>
                                <input type="hidden" name="photo_url" id="inp_photo_url" value="<?php echo $val_photo; ?>">
                                <div class="vault-action-row">
                                    <button type="button" class="vault-btn-action" id="ifsUploadPhotoBtn">
                                        <span class="dashicons dashicons-upload"></span> <?php echo ! empty( $val_photo ) ? 'Replace Photo' : 'Upload Photo'; ?>
                                    </button>
                                    <button type="button" class="vault-btn-remove <?php echo empty( $val_photo ) ? 'hide' : ''; ?>" id="ifsRemovePhotoBtn" title="Remove">
                                        <span class="dashicons dashicons-trash"></span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- 2. Passport Scan Document -->
                        <div class="ifs-vault-card" id="vault_passport_card">
                            <div class="vault-card-thumb" id="vault_passport_preview">
                                <?php if ( ! empty( $val_copy ) ) : ?>
                                    <?php if ( preg_match('/\.(jpg|jpeg|png|webp)$/i', $val_copy) ) : ?>
                                        <img src="<?php echo $val_copy; ?>" alt="Passport Scan" />
                                    <?php else : ?>
                                        <div class="vault-pdf-icon"><span class="dashicons dashicons-pdf"></span><small>PDF</small></div>
                                    <?php endif; ?>
                                <?php else : ?>
                                    <div class="vault-empty-icon"><span class="dashicons dashicons-media-document"></span></div>
                                <?php endif; ?>
                            </div>
                            <div class="vault-card-body">
                                <div class="vault-item-title">Passport Copy / Bio-Page</div>
                                <div class="vault-item-meta">Full page scan in PDF or High-res Image</div>
                                <input type="hidden" name="passport_copy_url" id="inp_passport_copy" value="<?php echo $val_copy; ?>">
                                <div class="vault-action-row">
                                    <button type="button" class="vault-btn-action" id="ifsUploadBtn">
                                        <span class="dashicons dashicons-upload"></span> <?php echo ! empty( $val_copy ) ? 'Replace Scan' : 'Upload Passport'; ?>
                                    </button>
                                    <button type="button" class="vault-btn-remove <?php echo empty( $val_copy ) ? 'hide' : ''; ?>" id="ifsRemovePassportBtn" title="Remove">
                                        <span class="dashicons dashicons-trash"></span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- 3. National ID / Visa Scan -->
                        <div class="ifs-vault-card" id="vault_nid_card">
                            <div class="vault-card-thumb" id="vault_nid_preview">
                                <?php if ( ! empty( $val_nid_copy ) ) : ?>
                                    <?php if ( preg_match('/\.(jpg|jpeg|png|webp)$/i', $val_nid_copy) ) : ?>
                                        <img src="<?php echo $val_nid_copy; ?>" alt="NID Document" />
                                    <?php else : ?>
                                        <div class="vault-pdf-icon"><span class="dashicons dashicons-pdf"></span><small>PDF</small></div>
                                    <?php endif; ?>
                                <?php else : ?>
                                    <div class="vault-empty-icon"><span class="dashicons dashicons-id-alt"></span></div>
                                <?php endif; ?>
                            </div>
                            <div class="vault-card-body">
                                <div class="vault-item-title">National ID / Visa Record</div>
                                <div class="vault-item-meta">Smart NID Card or Prior Visa Stamp Copy</div>
                                <input type="hidden" name="nid_copy_url" id="inp_nid_copy" value="<?php echo $val_nid_copy; ?>">
                                <div class="vault-action-row">
                                    <button type="button" class="vault-btn-action" id="ifsUploadNidBtn">
                                        <span class="dashicons dashicons-upload"></span> <?php echo ! empty( $val_nid_copy ) ? 'Replace File' : 'Upload NID/Visa'; ?>
                                    </button>
                                    <button type="button" class="vault-btn-remove <?php echo empty( $val_nid_copy ) ? 'hide' : ''; ?>" id="ifsRemoveNidBtn" title="Remove">
                                        <span class="dashicons dashicons-trash"></span>
                                    </button>
                                </div>
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

            <!-- Right Sidebar: Live Holographic GDS Passenger Profile & Digital Dossier -->
            <div class="ifs-preview-sidebar">
                <div class="ifs-preview-sticky">
                    
                    <div class="ifs-card-preview-header">
                        <div class="preview-header-left">
                            <span class="pulse-beacon"></span>
                            <span>Live GDS Passenger Profile</span>
                        </div>
                        <span class="preview-secure-tag"><span class="dashicons dashicons-shield"></span> IATA APIS</span>
                    </div>

                    <!-- Holographic Boarding Pass / Passport Profile -->
                    <div class="ifs-travel-card">
                        
                        <!-- Top Header Status Band -->
                        <div class="card-chip-strip">
                            <div class="airline-brand-tag">
                                <span class="dashicons dashicons-airplane"></span>
                                <span id="prev_nation">BANGLADESH</span>
                            </div>
                            <span class="card-tier-badge" id="prev_tier">RETAIL</span>
                        </div>

                        <!-- Hero Passenger Row with Live Photo Avatar Sync -->
                        <div class="card-profile-hero">
                            <div class="card-avatar-wrapper">
                                <div class="card-avatar" id="prev_avatar">
                                    <?php if ( ! empty( $val_photo ) ) : ?>
                                        <img src="<?php echo $val_photo; ?>" alt="Passenger" id="prev_avatar_img" />
                                    <?php else : ?>
                                        <span id="prev_avatar_txt">MR</span>
                                    <?php endif; ?>
                                </div>
                                <div class="card-type-chip" id="prev_type_badge">ADT</div>
                            </div>
                            <div class="hero-name-details">
                                <h4 class="card-name" id="prev_name">MR. MOHAMMED RAHIM</h4>
                                <div class="card-id-row">
                                    <span class="gds-pnr-tag">ID: #CUS-<?php echo $is_edit ? str_pad((string)$id, 5, '0', STR_PAD_LEFT) : 'NEW'; ?></span>
                                    <span class="gds-status-dot" title="GDS Synced"></span>
                                </div>
                            </div>
                        </div>

                        <!-- Key Spec Grid -->
                        <div class="card-data-grid">
                            <div class="grid-cell">
                                <span class="card-label">PASSPORT NO</span>
                                <span class="card-val font-mono" id="prev_passport">NOT SET</span>
                            </div>
                            <div class="grid-cell">
                                <span class="card-label">VALIDITY STATUS</span>
                                <span class="card-val" id="prev_expiry">NO DATE</span>
                            </div>
                            <div class="grid-cell">
                                <span class="card-label">GENDER &amp; DOB</span>
                                <span class="card-val" id="prev_gender_dob">Male, -</span>
                            </div>
                            <div class="grid-cell">
                                <span class="card-label">SPECIAL MEAL (SSR)</span>
                                <span class="card-val font-mono" id="prev_meal">MOML</span>
                            </div>
                            <div class="grid-cell">
                                <span class="card-label">CONTACT MOBILE</span>
                                <span class="card-val font-mono" id="prev_mobile">+880 17...</span>
                            </div>
                            <div class="grid-cell">
                                <span class="card-label">WHEELCHAIR SSR</span>
                                <span class="card-val" id="prev_wheelchair">NONE</span>
                            </div>
                        </div>

                        <!-- ICAO MRZ Code Band -->
                        <div class="card-mrz-zone">
                            <div class="mrz-line font-mono" id="prev_mrz_1">P&lt;BGD&lt;&lt;RAHIM&lt;&lt;MOHAMMED&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;</div>
                            <div class="mrz-line font-mono" id="prev_mrz_2">A000000000BGD0000000M0000000&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;00</div>
                        </div>

                        <!-- Barcode Decor Strip -->
                        <div class="card-barcode-strip">
                            <div class="barcode-lines"></div>
                            <div class="barcode-sub-meta font-mono">IATCI &bull; ELECTRONIC DOSSIER RECORD VALIDATED</div>
                        </div>

                    </div>

                    <!-- GDS Tip Note Card -->
                    <div class="ifs-tip-box">
                        <div class="tip-title"><span class="dashicons dashicons-yes-alt"></span> IATA &amp; GDS Split Name Protocol</div>
                        <p class="tip-body">Separating <strong>Given Name</strong> and <strong>Surname</strong> ensures 100% error-free PNR generation for Sabre, Amadeus, and Galileo systems.</p>
                    </div>

                </div>
            </div>
        </form>
    </div>

    <!-- High-End Ultra Premium Stylesheet -->
    <style>
        .ifs-editor-workspace { max-width: 1400px; margin: 0 auto; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; color: #0f172a; }
        .ifs-toast { padding: 14px 20px; border-radius: 10px; font-size: 13.5px; font-weight: 600; display: flex; align-items: center; gap: 10px; margin-bottom: 22px; box-shadow: 0 4px 12px rgba(0,0,0,0.03); }
        .ifs-toast.success { background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; }
        .ifs-toast.danger  { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
        
        .ifs-split-editor { display: grid; grid-template-columns: 1fr 400px; gap: 28px; align-items: flex-start; }
        @media (max-width: 1180px) { .ifs-split-editor { grid-template-columns: 1fr; } }
        
        .ifs-panel-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 26px; margin-bottom: 22px; box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.03); }
        .ifs-card-header { display: flex; align-items: center; gap: 14px; margin-bottom: 22px; padding-bottom: 14px; border-bottom: 1px solid #f1f5f9; }
        .ifs-step-num { width: 34px; height: 34px; border-radius: 9px; background: linear-gradient(135deg, #003376 0%, #0284c7 100%); color: #ffffff; font-weight: 800; font-size: 13px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px rgba(0, 51, 118, 0.2); flex-shrink: 0; }
        .ifs-card-title { margin: 0; font-size: 15.5px; font-weight: 800; color: #0f172a; }
        .ifs-card-desc { margin: 2px 0 0 0; font-size: 12.5px; color: #64748b; }
        
        .ifs-grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px 18px; }
        @media (max-width: 768px) { .ifs-grid-3 { grid-template-columns: 1fr; } }
        
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
        
        /* ----------------------------------------------------
           DIGITAL VAULT INTERACTIVE PREVIEW CARDS
        ---------------------------------------------------- */
        .ifs-vault-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 18px;
        }
        .ifs-vault-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 16px;
            display: flex;
            flex-direction: column;
            gap: 14px;
            transition: all 0.2s ease;
            position: relative;
        }
        .ifs-vault-card:hover {
            border-color: #cbd5e1;
            box-shadow: 0 6px 18px -4px rgba(15, 23, 42, 0.06);
            background: #ffffff;
        }
        .vault-card-thumb {
            width: 100%;
            height: 125px;
            border-radius: 10px;
            background: #ffffff;
            border: 1px dashed #cbd5e1;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
        }
        .vault-card-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .vault-empty-icon {
            display: flex;
            flex-direction: column;
            align-items: center;
            color: #94a3b8;
        }
        .vault-empty-icon .dashicons {
            font-size: 36px;
            width: 36px;
            height: 36px;
        }
        .vault-pdf-icon {
            display: flex;
            flex-direction: column;
            align-items: center;
            color: #dc2626;
            font-weight: 800;
        }
        .vault-pdf-icon .dashicons {
            font-size: 40px;
            width: 40px;
            height: 40px;
        }
        .vault-pdf-icon small {
            font-size: 11px;
            margin-top: 2px;
            background: #fee2e2;
            padding: 1px 6px;
            border-radius: 4px;
        }
        .vault-card-body {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .vault-item-title {
            font-size: 13px;
            font-weight: 700;
            color: #0f172a;
        }
        .vault-item-meta {
            font-size: 11.5px;
            color: #64748b;
            margin-bottom: 8px;
            line-height: 1.35;
        }
        .vault-action-row {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .vault-btn-action {
            flex: 1;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            padding: 7px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 700;
            color: #334155;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            transition: all 0.2s ease;
        }
        .vault-btn-action:hover {
            background: #003376;
            color: #ffffff;
            border-color: #003376;
        }
        .vault-btn-action .dashicons {
            font-size: 14px;
            width: 14px;
            height: 14px;
        }
        .vault-btn-remove {
            background: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fecaca;
            border-radius: 6px;
            width: 32px;
            height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .vault-btn-remove:hover {
            background: #dc2626;
            color: #ffffff;
        }
        .vault-btn-remove.hide {
            display: none !important;
        }

        /* ----------------------------------------------------
           ACTION STRIP
        ---------------------------------------------------- */
        .ifs-action-strip { display: flex; justify-content: space-between; align-items: center; padding: 18px 24px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; }
        .ifs-btn-back { color: #64748b; text-decoration: none; font-size: 13.5px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; }
        .ifs-btn-primary { background: linear-gradient(135deg, #003376 0%, #0284c7 100%); color: #ffffff; border: none; padding: 11px 26px; border-radius: 8px; font-size: 13.5px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 14px rgba(0, 51, 118, 0.25); }
        
        /* ----------------------------------------------------
           LIVE GDS PASSENGER PROFILE CARD (ULTRA-POLISHED)
        ---------------------------------------------------- */
        .ifs-preview-sticky { position: sticky; top: 30px; }
        .ifs-card-preview-header {
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: #475569;
            margin-bottom: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .preview-header-left {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .pulse-beacon {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #10b981;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.25);
            animation: pulseGlow 1.8s infinite;
        }
        @keyframes pulseGlow {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.2); opacity: 0.6; }
        }
        .preview-secure-tag {
            font-size: 10px;
            font-weight: 800;
            background: #e2e8f0;
            color: #475569;
            padding: 2px 7px;
            border-radius: 4px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .preview-secure-tag .dashicons { font-size: 12px; width: 12px; height: 12px; }

        .ifs-travel-card {
            background: radial-gradient(circle at 100% 0%, #0369a1 0%, #002b66 50%, #001738 100%);
            border-radius: 18px;
            padding: 24px;
            color: #ffffff;
            box-shadow: 0 20px 40px -8px rgba(0, 51, 118, 0.45);
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.12);
        }
        .ifs-travel-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle, rgba(255,255,255,0.08) 0%, transparent 70%);
            pointer-events: none;
        }
        .card-chip-strip {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.15);
            padding-bottom: 12px;
        }
        .airline-brand-tag {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 10.5px;
            font-weight: 800;
            letter-spacing: 1px;
            color: #7dd3fc;
        }
        .airline-brand-tag .dashicons { font-size: 14px; width: 14px; height: 14px; }
        .card-tier-badge {
            background: rgba(255, 255, 255, 0.18);
            backdrop-filter: blur(6px);
            padding: 2px 9px;
            border-radius: 6px;
            font-size: 9.5px;
            font-weight: 800;
            letter-spacing: 0.6px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .card-profile-hero {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 18px;
        }
        .card-avatar-wrapper {
            position: relative;
            flex-shrink: 0;
        }
        .card-avatar {
            width: 56px;
            height: 56px;
            border-radius: 14px;
            background: rgba(255, 255, 255, 0.15);
            border: 2px solid rgba(255, 255, 255, 0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 900;
            font-size: 17px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.25);
        }
        .card-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .card-type-chip {
            position: absolute;
            bottom: -4px;
            right: -4px;
            background: #0284c7;
            color: #ffffff;
            font-size: 8.5px;
            font-weight: 900;
            padding: 1px 4px;
            border-radius: 4px;
            border: 1px solid #ffffff;
        }
        .hero-name-details {
            flex: 1;
            min-width: 0;
        }
        .card-name {
            margin: 0 0 4px 0;
            font-size: 15px;
            font-weight: 800;
            color: #ffffff;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            letter-spacing: -0.2px;
        }
        .card-id-row {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .gds-pnr-tag {
            font-size: 11px;
            color: #bae6fd;
            font-family: ui-monospace, monospace;
            font-weight: 700;
        }
        .gds-status-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #38bdf8;
        }

        .card-data-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px 14px;
            padding: 14px 0;
            border-top: 1px dashed rgba(255, 255, 255, 0.2);
            border-bottom: 1px dashed rgba(255, 255, 255, 0.2);
            margin-bottom: 14px;
        }
        .grid-cell {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        .card-label {
            display: block;
            font-size: 8.5px;
            font-weight: 800;
            color: #7dd3fc;
            letter-spacing: 0.6px;
        }
        .card-val {
            font-size: 11.5px;
            font-weight: 700;
            color: #ffffff;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* ICAO MRZ Band */
        .card-mrz-zone {
            background: rgba(0, 0, 0, 0.25);
            padding: 8px 10px;
            border-radius: 8px;
            margin-bottom: 14px;
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
        .mrz-line {
            font-size: 9px;
            color: #e0f2fe;
            letter-spacing: 1.2px;
            line-height: 1.35;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: clip;
        }

        /* Barcode Area */
        .card-barcode-strip {
            text-align: center;
        }
        .barcode-lines {
            height: 20px;
            background: repeating-linear-gradient(90deg, #ffffff, #ffffff 2px, transparent 2px, transparent 4px, #ffffff 4px, #ffffff 5px, transparent 5px, transparent 8px, #ffffff 8px, #ffffff 10px, transparent 10px, transparent 12px);
            opacity: 0.8;
            margin-bottom: 4px;
            border-radius: 2px;
        }
        .barcode-sub-meta {
            font-size: 8px;
            color: #7dd3fc;
            letter-spacing: 1px;
        }

        /* Tip Box */
        .ifs-tip-box { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; margin-top: 18px; box-shadow: 0 2px 8px rgba(0,0,0,0.02); }
        .tip-title { font-size: 12px; font-weight: 700; color: #003376; display: flex; align-items: center; gap: 6px; margin-bottom: 6px; }
        .tip-title .dashicons { color: #16a34a; font-size: 15px; width: 15px; height: 15px; }
        .tip-body { margin: 0; font-size: 12px; color: #64748b; line-height: 1.5; }
    </style>

    <!-- Real-Time Interactive Profile & Media Handler Engine -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const inpTitle     = document.getElementById('inp_title');
        const inpGivenName = document.getElementById('inp_given_name');
        const inpSurname   = document.getElementById('inp_surname');
        const inpGender    = document.getElementById('inp_gender');
        const inpPtype     = document.getElementById('inp_ptype');
        const inpDob       = document.getElementById('inp_dob');
        const inpNation    = document.getElementById('inp_nation');
        const inpMobile    = document.getElementById('inp_mobile');
        const inpPassport  = document.getElementById('inp_passport');
        const inpExpiry    = document.getElementById('inp_expiry');
        const inpType      = document.getElementById('inp_type');
        const inpMeal      = document.getElementById('inp_meal');
        const inpWheel     = document.getElementById('inp_wheelchair');

        const prevName       = document.getElementById('prev_name');
        const prevAvatar     = document.getElementById('prev_avatar');
        const prevMobile     = document.getElementById('prev_mobile');
        const prevPassport   = document.getElementById('prev_passport');
        const prevExpiry     = document.getElementById('prev_expiry');
        const prevTier       = document.getElementById('prev_tier');
        const prevNation     = document.getElementById('prev_nation');
        const prevGenderDob  = document.getElementById('prev_gender_dob');
        const prevMeal       = document.getElementById('prev_meal');
        const prevWheelchair = document.getElementById('prev_wheelchair');
        const prevTypeBadge  = document.getElementById('prev_type_badge');
        const prevMrz1       = document.getElementById('prev_mrz_1');
        const prevMrz2       = document.getElementById('prev_mrz_2');

        function updateCardLive() {
            const titleVal = inpTitle ? inpTitle.value : 'MR';
            const given    = inpGivenName ? inpGivenName.value.trim().toUpperCase() : '';
            const surname  = inpSurname ? inpSurname.value.trim().toUpperCase() : '';
            const full     = (given + ' ' + surname).trim();
            
            if (full) {
                prevName.textContent = titleVal + '. ' + full;
                const photoInput = document.getElementById('inp_photo_url');
                if (!photoInput || !photoInput.value) {
                    const parts = full.split(' ');
                    const initials = parts.length > 1 ? (parts[0][0] + parts[parts.length-1][0]) : parts[0].slice(0, 2);
                    prevAvatar.innerHTML = '<span>' + initials + '</span>';
                }
            } else {
                prevName.textContent = titleVal + '. MOHAMMED RAHIM';
                const photoInput = document.getElementById('inp_photo_url');
                if (!photoInput || !photoInput.value) {
                    prevAvatar.innerHTML = '<span>MR</span>';
                }
            }

            if (prevMobile) prevMobile.textContent         = (inpMobile && inpMobile.value.trim()) ? inpMobile.value.trim() : '+880 17...';
            if (prevPassport) prevPassport.textContent     = (inpPassport && inpPassport.value.trim()) ? inpPassport.value.trim().toUpperCase() : 'NOT SET';
            if (prevTier) prevTier.textContent             = inpType ? inpType.value.toUpperCase() : 'RETAIL';
            if (prevNation) prevNation.textContent         = (inpNation && inpNation.value.trim()) ? inpNation.value.trim().toUpperCase() : 'BANGLADESH';
            if (prevMeal) prevMeal.textContent             = inpMeal ? inpMeal.value : 'MOML';
            if (prevWheelchair) prevWheelchair.textContent = inpWheel ? inpWheel.value : 'NONE';
            
            if (prevTypeBadge && inpPtype) {
                const pt = inpPtype.value;
                prevTypeBadge.textContent = (pt === 'Adult') ? 'ADT' : ((pt === 'Child') ? 'CHD' : 'INF');
            }

            const gVal = inpGender ? inpGender.value : 'Male';
            const dVal = (inpDob && inpDob.value) ? inpDob.value : '-';
            if (prevGenderDob) prevGenderDob.textContent = gVal + ', ' + dVal;

            // Expiry Calculation
            if (inpExpiry && inpExpiry.value) {
                const expDate  = new Date(inpExpiry.value);
                const today    = new Date();
                const diffDays = Math.ceil((expDate - today) / (1000 * 60 * 60 * 24));

                if (diffDays < 0) {
                    prevExpiry.textContent = 'EXPIRED';
                    prevExpiry.style.color = '#f87171';
                } else if (diffDays < 180) {
                    prevExpiry.textContent = '< 6 MOS (' + diffDays + 'd)';
                    prevExpiry.style.color = '#fde047';
                } else {
                    prevExpiry.textContent = 'VALID (' + expDate.getFullYear() + ')';
                    prevExpiry.style.color = '#86efac';
                }
            } else if (prevExpiry) {
                prevExpiry.textContent = 'NO DATE';
                prevExpiry.style.color = '#ffffff';
            }

            // Real-Time ICAO MRZ Generator
            const mrzSurname = surname || 'SURNAME';
            const mrzGiven   = given || 'GIVENNAME';
            const mrzPassNo  = (inpPassport && inpPassport.value.trim()) ? inpPassport.value.trim().toUpperCase() : 'A00000000';
            const cleanSur   = mrzSurname.replace(/[^A-Z]/g, '');
            const cleanGiv   = mrzGiven.replace(/[^A-Z]/g, '<');
            
            let l1 = 'P<BGD' + cleanSur + '<<' + cleanGiv;
            while(l1.length < 44) { l1 += '<'; }
            if (l1.length > 44) l1 = l1.substring(0, 44);

            let l2 = mrzPassNo;
            while(l2.length < 9) { l2 += '<'; }
            l2 += '0BGD0000000M0000000<<<<<<<<<<<<<00';
            if (l2.length > 44) l2 = l2.substring(0, 44);

            if (prevMrz1) prevMrz1.textContent = l1;
            if (prevMrz2) prevMrz2.textContent = l2;
        }

        [inpTitle, inpGivenName, inpSurname, inpGender, inpPtype, inpDob, inpNation, inpMobile, inpPassport, inpExpiry, inpType, inpMeal, inpWheel].forEach(el => {
            if (el) {
                el.addEventListener('input', updateCardLive);
                el.addEventListener('change', updateCardLive);
            }
        });

        updateCardLive();

        // WP Media Uploader Helpers with Live Vault Preview Updates
        function setupMediaVault(btnId, removeBtnId, inputId, previewBoxId, isPhoto) {
            const btn = document.getElementById(btnId);
            const removeBtn = document.getElementById(removeBtnId);
            const input = document.getElementById(inputId);
            const preview = document.getElementById(previewBoxId);

            if (btn && window.wp && wp.media) {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const uploader = wp.media({
                        title: 'Select or Upload Document File',
                        button: { text: 'Attach To Dossier' },
                        multiple: false
                    }).on('select', function() {
                        const attachment = uploader.state().get('selection').first().toJSON();
                        if (attachment && attachment.url) {
                            input.value = attachment.url;
                            
                            // Update Vault Card Preview
                            if (attachment.url.match(/\.(jpg|jpeg|png|webp|gif)$/i)) {
                                preview.innerHTML = '<img src="' + attachment.url + '" alt="Document" />';
                            } else {
                                preview.innerHTML = '<div class="vault-pdf-icon"><span class="dashicons dashicons-pdf"></span><small>PDF</small></div>';
                            }
                            
                            if (removeBtn) removeBtn.classList.remove('hide');

                            // If portrait photo, also update live GDS card avatar
                            if (isPhoto) {
                                const cardAvatar = document.getElementById('prev_avatar');
                                if (cardAvatar) {
                                    cardAvatar.innerHTML = '<img src="' + attachment.url + '" alt="Passenger" id="prev_avatar_img" />';
                                }
                            }
                        }
                    }).open();
                });
            }

            if (removeBtn) {
                removeBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    input.value = '';
                    removeBtn.classList.add('hide');
                    
                    if (isPhoto) {
                        preview.innerHTML = '<div class="vault-empty-icon"><span class="dashicons dashicons-camera"></span></div>';
                        updateCardLive();
                    } else if (inputId === 'inp_passport_copy') {
                        preview.innerHTML = '<div class="vault-empty-icon"><span class="dashicons dashicons-media-document"></span></div>';
                    } else {
                        preview.innerHTML = '<div class="vault-empty-icon"><span class="dashicons dashicons-id-alt"></span></div>';
                    }
                });
            }
        }

        setupMediaVault('ifsUploadPhotoBtn', 'ifsRemovePhotoBtn', 'inp_photo_url', 'vault_photo_preview', true);
        setupMediaVault('ifsUploadBtn', 'ifsRemovePassportBtn', 'inp_passport_copy', 'vault_passport_preview', false);
        setupMediaVault('ifsUploadNidBtn', 'ifsRemoveNidBtn', 'inp_nid_copy', 'vault_nid_preview', false);
    });
    </script>
    <?php
}