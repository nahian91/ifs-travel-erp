<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Add/Edit Customer Console - Flat Minimal UI Edition (No Shadows)
 * Features: Single-Column Full-Width Flow, Integrated Dashicon Field Wrappers, 
 * WP Media Library Vault Integration & Live GDS Passport Preview Widget
 */
function ifs_terp_customer_add_edit_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'iterp_customers';
    
    $id       = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
    $is_edit  = ( $id > 0 );
    $message  = '';
    $errors   = array();
    $base_url = admin_url( 'admin.php?page=ifs_travel_erp&tab=customers' );

    if ( function_exists( 'wp_enqueue_media' ) ) {
        wp_enqueue_media();
    }

    // Auto-migration helper: ensure newly supported columns exist in iterp_customers
    $existing_columns = $wpdb->get_col( "DESC {$table_name}", 0 );
    if ( ! empty( $existing_columns ) ) {
        $required_columns = array(
            'visa_profession_type'   => "VARCHAR(100) DEFAULT 'Private Service' NOT NULL",
            'visa_status'            => "VARCHAR(50) DEFAULT 'Pending' NOT NULL",
            'emergency_relation'     => "VARCHAR(100) DEFAULT '' NOT NULL",
            'frequent_flyer_airline' => "VARCHAR(100) DEFAULT '' NOT NULL",
            'corporate_ref'          => "VARCHAR(150) DEFAULT '' NOT NULL",
            'district'               => "VARCHAR(100) DEFAULT '' NOT NULL",
            'hajj_tracking_id'       => "VARCHAR(100) DEFAULT '' NOT NULL",
            'remarks'                => "TEXT NULL",
            'prof_copy_url'          => "TEXT NOT NULL",
        );

        foreach ( $required_columns as $col => $definition ) {
            if ( ! in_array( $col, $existing_columns, true ) ) {
                $wpdb->query( "ALTER TABLE {$table_name} ADD {$col} {$definition}" );
            }
        }
    }

    // Handle Form Submission
    if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['ifs_customer_submit'] ) ) {
        check_admin_referer( 'ifs_customer_save_action', 'ifs_customer_nonce' );

        $title                = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : 'MR';
        $given_name           = isset( $_POST['given_name'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_POST['given_name'] ) ) ) : '';
        $surname              = isset( $_POST['surname'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_POST['surname'] ) ) ) : '';
        $full_name            = trim( $given_name . ' ' . $surname );
        $father_spouse_name   = isset( $_POST['father_spouse_name'] ) ? sanitize_text_field( wp_unslash( $_POST['father_spouse_name'] ) ) : '';
        $mother_name          = isset( $_POST['mother_name'] ) ? sanitize_text_field( wp_unslash( $_POST['mother_name'] ) ) : '';
        $gender               = isset( $_POST['gender'] ) ? sanitize_text_field( wp_unslash( $_POST['gender'] ) ) : 'Male';
        $marital_status       = isset( $_POST['marital_status'] ) ? sanitize_text_field( wp_unslash( $_POST['marital_status'] ) ) : 'Married';
        $passenger_type       = isset( $_POST['passenger_type'] ) ? sanitize_text_field( wp_unslash( $_POST['passenger_type'] ) ) : 'Adult';
        $date_of_birth        = isset( $_POST['date_of_birth'] ) ? sanitize_text_field( wp_unslash( $_POST['date_of_birth'] ) ) : '';
        $birth_place          = isset( $_POST['birth_place'] ) ? sanitize_text_field( wp_unslash( $_POST['birth_place'] ) ) : '';
        $blood_group          = isset( $_POST['blood_group'] ) ? sanitize_text_field( wp_unslash( $_POST['blood_group'] ) ) : '';
        $nationality          = isset( $_POST['nationality'] ) ? sanitize_text_field( wp_unslash( $_POST['nationality'] ) ) : 'Bangladeshi';
        $nid_no               = isset( $_POST['nid_no'] ) ? sanitize_text_field( wp_unslash( $_POST['nid_no'] ) ) : '';
        $profession           = isset( $_POST['profession'] ) ? sanitize_text_field( wp_unslash( $_POST['profession'] ) ) : '';
        $visa_profession_type = isset( $_POST['visa_profession_type'] ) ? sanitize_text_field( wp_unslash( $_POST['visa_profession_type'] ) ) : 'Private Service';
        $visa_status          = isset( $_POST['visa_status'] ) ? sanitize_text_field( wp_unslash( $_POST['visa_status'] ) ) : 'Pending';
        
        $mobile               = isset( $_POST['mobile'] ) ? sanitize_text_field( wp_unslash( $_POST['mobile'] ) ) : '';
        $whatsapp_no          = isset( $_POST['whatsapp_no'] ) ? sanitize_text_field( wp_unslash( $_POST['whatsapp_no'] ) ) : '';
        $email                = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
        $emergency_contact    = isset( $_POST['emergency_contact'] ) ? sanitize_text_field( wp_unslash( $_POST['emergency_contact'] ) ) : '';
        $emergency_relation   = isset( $_POST['emergency_relation'] ) ? sanitize_text_field( wp_unslash( $_POST['emergency_relation'] ) ) : '';
        
        $passport_no          = isset( $_POST['passport_no'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_POST['passport_no'] ) ) ) : '';
        $prev_passport_no     = isset( $_POST['prev_passport_no'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_POST['prev_passport_no'] ) ) ) : '';
        $passport_type        = isset( $_POST['passport_type'] ) ? sanitize_text_field( wp_unslash( $_POST['passport_type'] ) ) : 'Regular';
        $passport_issue_date  = isset( $_POST['passport_issue_date'] ) ? sanitize_text_field( wp_unslash( $_POST['passport_issue_date'] ) ) : '';
        $passport_expiry      = isset( $_POST['passport_expiry'] ) ? sanitize_text_field( wp_unslash( $_POST['passport_expiry'] ) ) : '';
        $passport_issue_place = isset( $_POST['passport_issue_place'] ) ? sanitize_text_field( wp_unslash( $_POST['passport_issue_place'] ) ) : '';
        
        $frequent_flyer_no      = isset( $_POST['frequent_flyer_no'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_POST['frequent_flyer_no'] ) ) ) : '';
        $frequent_flyer_airline = isset( $_POST['frequent_flyer_airline'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_POST['frequent_flyer_airline'] ) ) ) : '';
        $meal_preference        = isset( $_POST['meal_preference'] ) ? sanitize_text_field( wp_unslash( $_POST['meal_preference'] ) ) : 'MOML';
        $wheelchair_ssr         = isset( $_POST['wheelchair_ssr'] ) ? sanitize_text_field( wp_unslash( $_POST['wheelchair_ssr'] ) ) : 'NONE';
        $client_type            = isset( $_POST['client_type'] ) ? sanitize_text_field( wp_unslash( $_POST['client_type'] ) ) : 'Retail';
        $corporate_ref          = isset( $_POST['corporate_ref'] ) ? sanitize_text_field( wp_unslash( $_POST['corporate_ref'] ) ) : '';
        $city                   = isset( $_POST['city'] ) ? sanitize_text_field( wp_unslash( $_POST['city'] ) ) : '';
        $district               = isset( $_POST['district'] ) ? sanitize_text_field( wp_unslash( $_POST['district'] ) ) : '';
        $hajj_tracking_id       = isset( $_POST['hajj_tracking_id'] ) ? sanitize_text_field( wp_unslash( $_POST['hajj_tracking_id'] ) ) : '';
        $address                = isset( $_POST['address'] ) ? sanitize_textarea_field( wp_unslash( $_POST['address'] ) ) : '';
        $remarks                = isset( $_POST['remarks'] ) ? sanitize_textarea_field( wp_unslash( $_POST['remarks'] ) ) : '';
        
        $photo_url              = isset( $_POST['photo_url'] ) ? esc_url_raw( wp_unslash( $_POST['photo_url'] ) ) : '';
        $passport_copy          = isset( $_POST['passport_copy_url'] ) ? esc_url_raw( wp_unslash( $_POST['passport_copy_url'] ) ) : '';
        $nid_copy_url           = isset( $_POST['nid_copy_url'] ) ? esc_url_raw( wp_unslash( $_POST['nid_copy_url'] ) ) : '';
        $prof_copy_url          = isset( $_POST['prof_copy_url'] ) ? esc_url_raw( wp_unslash( $_POST['prof_copy_url'] ) ) : '';

        if ( empty( $given_name ) ) {
            $errors[] = esc_html__( 'First Name is required.', 'ifs-travel-erp' );
        }
        if ( empty( $mobile ) ) {
            $errors[] = esc_html__( 'Mobile number is required.', 'ifs-travel-erp' );
        }

        if ( empty( $errors ) ) {
            $data = array(
                'title'                  => $title,
                'passport_given_name'    => $given_name,
                'passport_surname'       => $surname,
                'full_name'              => $full_name,
                'father_spouse_name'     => $father_spouse_name,
                'mother_name'            => $mother_name,
                'gender'                 => $gender,
                'marital_status'         => $marital_status,
                'passenger_type'         => $passenger_type,
                'date_of_birth'          => ! empty( $date_of_birth ) ? $date_of_birth : '1970-01-01',
                'birth_place'            => $birth_place,
                'blood_group'            => $blood_group,
                'nationality'            => $nationality,
                'nid_no'                 => $nid_no,
                'profession'             => $profession,
                'visa_profession_type'   => $visa_profession_type,
                'visa_status'            => $visa_status,
                'mobile'                 => $mobile,
                'whatsapp_no'            => $whatsapp_no,
                'email'                  => $email,
                'emergency_contact'      => $emergency_contact,
                'emergency_relation'     => $emergency_relation,
                'passport_no'            => $passport_no,
                'prev_passport_no'       => $prev_passport_no,
                'passport_type'          => $passport_type,
                'passport_issue_date'    => ! empty( $passport_issue_date ) ? $passport_issue_date : '1970-01-01',
                'passport_expiry'        => ! empty( $passport_expiry ) ? $passport_expiry : '1970-01-01',
                'passport_issue_place'   => $passport_issue_place,
                'frequent_flyer_no'      => $frequent_flyer_no,
                'frequent_flyer_airline' => $frequent_flyer_airline,
                'meal_preference'        => $meal_preference,
                'wheelchair_ssr'         => $wheelchair_ssr,
                'client_type'            => $client_type,
                'corporate_ref'          => $corporate_ref,
                'city'                   => $city,
                'district'               => $district,
                'hajj_tracking_id'       => $hajj_tracking_id,
                'address'                => $address,
                'remarks'                => $remarks,
                'photo_url'              => $photo_url,
                'passport_copy_url'      => $passport_copy,
                'nid_copy_url'           => $nid_copy_url,
                'prof_copy_url'          => $prof_copy_url
            );

            if ( $is_edit ) {
                $wpdb->update( $table_name, $data, array( 'id' => $id ) );
                $message = '<div class="ifs-toast success"><span class="dashicons dashicons-yes-alt"></span> ' . esc_html__( 'Customer updated successfully.', 'ifs-travel-erp' ) . '</div>';
            } else {
                $data['created_at'] = current_time( 'mysql' );
                $wpdb->insert( $table_name, $data );
                $id      = $wpdb->insert_id;
                $is_edit = true;
                $message = '<div class="ifs-toast success"><span class="dashicons dashicons-yes-alt"></span> ' . sprintf( esc_html__( 'New customer saved (#CUS-%s).', 'ifs-travel-erp' ), str_pad( (string) $id, 5, '0', STR_PAD_LEFT ) ) . '</div>';
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
        $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_name} WHERE id = %d", $id ) );
    }

    // Split Name fallback
    $val_given   = $is_edit ? esc_attr( $row->passport_given_name ?? '' ) : '';
    $val_surname = $is_edit ? esc_attr( $row->passport_surname ?? '' ) : '';
    if ( empty( $val_given ) && $is_edit && ! empty( $row->full_name ) ) {
        $name_parts  = explode( ' ', trim( $row->full_name ) );
        $val_surname = ( count( $name_parts ) > 1 ) ? array_pop( $name_parts ) : '';
        $val_given   = implode( ' ', $name_parts ) ?: $row->full_name;
    }

    // Field Values
    $val_title           = $is_edit ? esc_attr( $row->title ?? 'MR' ) : 'MR';
    $val_father          = $is_edit ? esc_attr( $row->father_spouse_name ?? '' ) : '';
    $val_mother          = $is_edit ? esc_attr( $row->mother_name ?? '' ) : '';
    $val_gender          = $is_edit ? esc_attr( $row->gender ?? 'Male' ) : 'Male';
    $val_marital         = $is_edit ? esc_attr( $row->marital_status ?? 'Married' ) : 'Married';
    $val_ptype           = $is_edit ? esc_attr( $row->passenger_type ?? 'Adult' ) : 'Adult';
    $val_dob             = ( $is_edit && ! empty( $row->date_of_birth ) && $row->date_of_birth !== '1970-01-01' && $row->date_of_birth !== '0000-00-00' ) ? esc_attr( $row->date_of_birth ) : '';
    $val_birth_place     = $is_edit ? esc_attr( $row->birth_place ?? '' ) : '';
    $val_blood           = $is_edit ? esc_attr( $row->blood_group ?? '' ) : '';
    $val_nation          = $is_edit ? esc_attr( $row->nationality ?? 'Bangladeshi' ) : 'Bangladeshi';
    $val_nid             = $is_edit ? esc_attr( $row->nid_no ?? '' ) : '';
    $val_profession      = $is_edit ? esc_attr( $row->profession ?? '' ) : '';
    $val_visa_prof_type  = $is_edit ? esc_attr( $row->visa_profession_type ?? 'Private Service' ) : 'Private Service';
    $val_visa_status     = $is_edit ? esc_attr( $row->visa_status ?? 'Pending' ) : 'Pending';
    
    $val_mobile          = $is_edit ? esc_attr( $row->mobile ?? '' ) : '';
    $val_whatsapp        = $is_edit ? esc_attr( $row->whatsapp_no ?? '' ) : '';
    $val_email           = $is_edit ? esc_attr( $row->email ?? '' ) : '';
    $val_emergency       = $is_edit ? esc_attr( $row->emergency_contact ?? '' ) : '';
    $val_em_relation     = $is_edit ? esc_attr( $row->emergency_relation ?? '' ) : '';
    
    $val_passport        = $is_edit ? esc_attr( $row->passport_no ?? '' ) : '';
    $val_prev_pass       = $is_edit ? esc_attr( $row->prev_passport_no ?? '' ) : '';
    $val_pass_type       = $is_edit ? esc_attr( $row->passport_type ?? 'Regular' ) : 'Regular';
    $val_issue_date      = ( $is_edit && ! empty( $row->passport_issue_date ) && $row->passport_issue_date !== '1970-01-01' && $row->passport_issue_date !== '0000-00-00' ) ? esc_attr( $row->passport_issue_date ) : '';
    $val_expiry          = ( $is_edit && ! empty( $row->passport_expiry ) && $row->passport_expiry !== '1970-01-01' && $row->passport_expiry !== '0000-00-00' ) ? esc_attr( $row->passport_expiry ) : '';
    $val_issue_place     = $is_edit ? esc_attr( $row->passport_issue_place ?? '' ) : '';
    
    $val_ffn             = $is_edit ? esc_attr( $row->frequent_flyer_no ?? '' ) : '';
    $val_ffa             = $is_edit ? esc_attr( $row->frequent_flyer_airline ?? '' ) : '';
    $val_meal            = $is_edit ? esc_attr( $row->meal_preference ?? 'MOML' ) : 'MOML';
    $val_wheelchair      = $is_edit ? esc_attr( $row->wheelchair_ssr ?? 'NONE' ) : 'NONE';
    $val_type            = $is_edit ? esc_attr( $row->client_type ?? 'Retail' ) : 'Retail';
    $val_corp_ref        = $is_edit ? esc_attr( $row->corporate_ref ?? '' ) : '';
    $val_city            = $is_edit ? esc_attr( $row->city ?? '' ) : '';
    $val_district        = $is_edit ? esc_attr( $row->district ?? '' ) : '';
    $val_hajj_id         = $is_edit ? esc_attr( $row->hajj_tracking_id ?? '' ) : '';
    $val_address         = $is_edit ? esc_attr( $row->address ?? '' ) : '';
    $val_remarks         = $is_edit ? esc_attr( $row->remarks ?? '' ) : '';
    
    $val_photo           = $is_edit ? esc_url( $row->photo_url ?? '' ) : '';
    $val_copy            = $is_edit ? esc_url( $row->passport_copy_url ?? '' ) : '';
    $val_nid_copy        = $is_edit ? esc_url( $row->nid_copy_url ?? '' ) : '';
    $val_prof_copy       = $is_edit ? esc_url( $row->prof_copy_url ?? '' ) : '';
    
    // List of 64 Districts of Bangladesh
    $bd_districts = array(
        'Bagerhat', 'Bandarban', 'Barguna', 'Barishal', 'Bhola', 'Bogura', 'Brahmanbaria', 'Chandpur', 'Chapainawabganj', 'Chattogram', 'Chuadanga', 'Cox\'s Bazar', 'Cumilla', 'Dinajpur', 'Faridpur', 'Feni', 'Gaibandha', 'Gazipur', 'Gopalganj', 'Habiganj', 'Jamalpur', 'Jashore', 'Jhalokathi', 'Jhenaidah', 'Joypurhat', 'Khagrachhari', 'Khulna', 'Kishoreganj', 'Kurigram', 'Kushtia', 'Lakshmipur', 'Lalmonirhat', 'Madaripur', 'Magura', 'Manikganj', 'Meherpur', 'Moulvibazar', 'Munshiganj', 'Mymensingh', 'Naogaon', 'Narail', 'Narayanganj', 'Narsingdi', 'Natore', 'Netrokona', 'Nilphamari', 'Noakhali', 'Pabna', 'Panchagarh', 'Patuakhali', 'Pirojpur', 'Rajbari', 'Rajshahi', 'Rangamati', 'Rangpur', 'Satkhira', 'Shariatpur', 'Sherpur', 'Sirajganj', 'Sunamganj', 'Sylhet', 'Tangail', 'Thakurgaon'
    );
    sort( $bd_districts );
    ?>

    <div class="wrap ifs-editor-workspace">
        <?php echo wp_kses_post( $message ); ?>

        <form method="post" action="" id="ifsCustomerForm" class="ifs-split-editor">
            <?php wp_nonce_field( 'ifs_customer_save_action', 'ifs_customer_nonce' ); ?>
            
            <div class="ifs-form-body">
                
                <div class="ifs-panel-card">
                    <div class="ifs-card-header">
                        <div class="ifs-step-num">01</div>
                        <div>
                            <h3 class="ifs-card-title"><?php esc_html_e( 'Personal Information', 'ifs-travel-erp' ); ?></h3>
                            <p class="ifs-card-desc"><?php esc_html_e( 'Basic details of the passenger', 'ifs-travel-erp' ); ?></p>
                        </div>
                    </div>

                    <div class="ifs-grid-3">
                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_title"><?php esc_html_e( 'Title', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-admin-users field-icon"></span>
                                <select name="title" id="inp_title" class="ifs-input-field">
                                    <option value="MR" <?php selected( $val_title, 'MR' ); ?>>Mr. (Male)</option>
                                    <option value="MRS" <?php selected( $val_title, 'MRS' ); ?>>Mrs. (Married Female)</option>
                                    <option value="MS" <?php selected( $val_title, 'MS' ); ?>>Ms. (Single Female)</option>
                                    <option value="MSTR" <?php selected( $val_title, 'MSTR' ); ?>>Master (Boy)</option>
                                    <option value="MISS" <?php selected( $val_title, 'MISS' ); ?>>Miss (Girl)</option>
                                    <option value="DR" <?php selected( $val_title, 'DR' ); ?>>Dr.</option>
                                    <option value="HAJI" <?php selected( $val_title, 'HAJI' ); ?>>Haji</option>
                                </select>
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_given_name"><?php esc_html_e( 'First Name', 'ifs-travel-erp' ); ?> <span class="req">*</span></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-id field-icon"></span>
                                <input type="text" name="given_name" id="inp_given_name" required 
                                       value="<?php echo esc_attr( $val_given ); ?>" 
                                       placeholder="e.g. MOHAMMED" class="ifs-input-field uppercase">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_surname"><?php esc_html_e( 'Last Name', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-id-alt field-icon"></span>
                                <input type="text" name="surname" id="inp_surname" 
                                       value="<?php echo esc_attr( $val_surname ); ?>" 
                                       placeholder="e.g. RAHIM" class="ifs-input-field uppercase">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_father"><?php esc_html_e( 'Father / Husband Name', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-businessman field-icon"></span>
                                <input type="text" name="father_spouse_name" id="inp_father" 
                                       value="<?php echo esc_attr( $val_father ); ?>" placeholder="Name" class="ifs-input-field">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_mother"><?php esc_html_e( 'Mother Name', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-heart field-icon"></span>
                                <input type="text" name="mother_name" id="inp_mother" 
                                       value="<?php echo esc_attr( $val_mother ); ?>" placeholder="Name" class="ifs-input-field">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_ptype"><?php esc_html_e( 'Passenger Type', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-groups field-icon"></span>
                                <select name="passenger_type" id="inp_ptype" class="ifs-input-field">
                                    <option value="Adult" <?php selected( $val_ptype, 'Adult' ); ?>>Adult (12+ Years)</option>
                                    <option value="Child" <?php selected( $val_ptype, 'Child' ); ?>>Child (2-11 Years)</option>
                                    <option value="Infant" <?php selected( $val_ptype, 'Infant' ); ?>>Infant (Under 2 Years)</option>
                                </select>
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_gender"><?php esc_html_e( 'Gender', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-admin-generic field-icon"></span>
                                <select name="gender" id="inp_gender" class="ifs-input-field">
                                    <option value="Male" <?php selected( $val_gender, 'Male' ); ?>>Male</option>
                                    <option value="Female" <?php selected( $val_gender, 'Female' ); ?>>Female</option>
                                </select>
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_dob"><?php esc_html_e( 'Date of Birth', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-calendar-alt field-icon"></span>
                                <input type="date" name="date_of_birth" id="inp_dob" 
                                       value="<?php echo esc_attr( $val_dob ); ?>" class="ifs-input-field">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_nid"><?php esc_html_e( 'NID Number', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-text field-icon"></span>
                                <input type="text" name="nid_no" id="inp_nid" 
                                       value="<?php echo esc_attr( $val_nid ); ?>" placeholder="1990123456789" class="ifs-input-field font-mono">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_profession"><?php esc_html_e( 'Profession', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-portfolio field-icon"></span>
                                <input type="text" name="profession" id="inp_profession" 
                                       value="<?php echo esc_attr( $val_profession ); ?>" placeholder="e.g. Manager" class="ifs-input-field">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_visa_prof_type"><?php esc_html_e( 'Visa Profession Category', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-id-alt field-icon"></span>
                                <select name="visa_profession_type" id="inp_visa_prof_type" class="ifs-input-field">
                                    <option value="Private Service" <?php selected( $val_visa_prof_type, 'Private Service' ); ?>>Private Service</option>
                                    <option value="Government Employee" <?php selected( $val_visa_prof_type, 'Government Employee' ); ?>>Government Employee (GO/NOC)</option>
                                    <option value="Businessman" <?php selected( $val_visa_prof_type, 'Businessman' ); ?>>Businessman / Trader</option>
                                    <option value="Student" <?php selected( $val_visa_prof_type, 'Student' ); ?>>Student</option>
                                    <option value="Housewife" <?php selected( $val_visa_prof_type, 'Housewife' ); ?>>Housewife</option>
                                    <option value="Unemployed" <?php selected( $val_visa_prof_type, 'Unemployed' ); ?>>Unemployed</option>
                                </select>
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_visa_status"><?php esc_html_e( 'Visa Status', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-yes field-icon"></span>
                                <select name="visa_status" id="inp_visa_status" class="ifs-input-field">
                                    <option value="Pending" <?php selected( $val_visa_status, 'Pending' ); ?>>Pending</option>
                                    <option value="Submitted" <?php selected( $val_visa_status, 'Submitted' ); ?>>Submitted to Embassy</option>
                                    <option value="Approved" <?php selected( $val_visa_status, 'Approved' ); ?>>Approved</option>
                                    <option value="Rejected" <?php selected( $val_visa_status, 'Rejected' ); ?>>Rejected</option>
                                    <option value="Not Required" <?php selected( $val_visa_status, 'Not Required' ); ?>>Not Required</option>
                                </select>
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_blood"><?php esc_html_e( 'Blood Group', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-sos field-icon"></span>
                                <select name="blood_group" id="inp_blood" class="ifs-input-field">
                                    <option value="">-- Select --</option>
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
                            <label class="ifs-field-label" for="inp_nation"><?php esc_html_e( 'Nationality', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-flag field-icon"></span>
                                <input type="text" name="nationality" id="inp_nation" 
                                       value="<?php echo esc_attr( $val_nation ); ?>" placeholder="Bangladeshi" class="ifs-input-field uppercase">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_birth_place"><?php esc_html_e( 'Birth Place', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-location field-icon"></span>
                                <input type="text" name="birth_place" id="inp_birth_place" 
                                       value="<?php echo esc_attr( $val_birth_place ); ?>" placeholder="e.g. Dhaka" class="ifs-input-field">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_hajj_id"><?php esc_html_e( 'Hajj Tracking ID', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-tickets field-icon"></span>
                                <input type="text" name="hajj_tracking_id" id="inp_hajj_id" 
                                       value="<?php echo esc_attr( $val_hajj_id ); ?>" placeholder="Optional ID" class="ifs-input-field font-mono uppercase">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_type"><?php esc_html_e( 'Client Type', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-awards field-icon"></span>
                                <select name="client_type" id="inp_type" class="ifs-input-field">
                                    <option value="Retail" <?php selected( $val_type, 'Retail' ); ?>>Regular Customer</option>
                                    <option value="Corporate" <?php selected( $val_type, 'Corporate' ); ?>>Corporate Office</option>
                                    <option value="VIP" <?php selected( $val_type, 'VIP' ); ?>>VIP Client</option>
                                </select>
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_corp_ref"><?php esc_html_e( 'Corporate Reference', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-building field-icon"></span>
                                <input type="text" name="corporate_ref" id="inp_corp_ref" 
                                       value="<?php echo esc_attr( $val_corp_ref ); ?>" placeholder="e.g. Acme Corporation" class="ifs-input-field">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="ifs-panel-card">
                    <div class="ifs-card-header">
                        <div class="ifs-step-num">02</div>
                        <div>
                            <h3 class="ifs-card-title"><?php esc_html_e( 'Contact Details', 'ifs-travel-erp' ); ?></h3>
                            <p class="ifs-card-desc"><?php esc_html_e( 'Phone numbers and address', 'ifs-travel-erp' ); ?></p>
                        </div>
                    </div>

                    <div class="ifs-grid-3">
                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_mobile"><?php esc_html_e( 'Mobile Number', 'ifs-travel-erp' ); ?> <span class="req">*</span></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-phone field-icon"></span>
                                <input type="text" name="mobile" id="inp_mobile" required 
                                       value="<?php echo esc_attr( $val_mobile ); ?>" 
                                       placeholder="01711-000000" class="ifs-input-field font-mono">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_whatsapp"><?php esc_html_e( 'WhatsApp Number', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-format-chat field-icon"></span>
                                <input type="text" name="whatsapp_no" id="inp_whatsapp" 
                                       value="<?php echo esc_attr( $val_whatsapp ); ?>" 
                                       placeholder="01711-000000" class="ifs-input-field font-mono">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_email"><?php esc_html_e( 'Email Address', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-email field-icon"></span>
                                <input type="email" name="email" id="inp_email" 
                                       value="<?php echo esc_attr( $val_email ); ?>" 
                                       placeholder="mail@example.com" class="ifs-input-field">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_emergency"><?php esc_html_e( 'Emergency Contact', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-warning field-icon"></span>
                                <input type="text" name="emergency_contact" id="inp_emergency" 
                                       value="<?php echo esc_attr( $val_emergency ); ?>" 
                                       placeholder="Name & Number" class="ifs-input-field">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_em_relation"><?php esc_html_e( 'Emergency Relation', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-networking field-icon"></span>
                                <input type="text" name="emergency_relation" id="inp_em_relation" 
                                       value="<?php echo esc_attr( $val_em_relation ); ?>" 
                                       placeholder="e.g. Spouse / Brother" class="ifs-input-field">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_city"><?php esc_html_e( 'City / Area', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-location field-icon"></span>
                                <input type="text" name="city" id="inp_city" 
                                       value="<?php echo esc_attr( $val_city ); ?>" placeholder="e.g. Gulshan" class="ifs-input-field">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_district"><?php esc_html_e( 'District', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-admin-site field-icon"></span>
                                <select name="district" id="inp_district" class="ifs-input-field">
                                    <option value="">-- Select District --</option>
                                    <?php foreach ( $bd_districts as $dist ) : ?>
                                        <option value="<?php echo esc_attr( $dist ); ?>" <?php selected( $val_district, $dist ); ?>><?php echo esc_html( $dist ); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="ifs-field-block col-span-2">
                            <label class="ifs-field-label" for="inp_address"><?php esc_html_e( 'Full Address', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-admin-home field-icon"></span>
                                <input type="text" name="address" id="inp_address" 
                                       value="<?php echo esc_attr( $val_address ); ?>" placeholder="House, Road, Area..." class="ifs-input-field">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="ifs-panel-card">
                    <div class="ifs-card-header">
                        <div class="ifs-step-num">03</div>
                        <div>
                            <h3 class="ifs-card-title"><?php esc_html_e( 'Passport & Travel Information', 'ifs-travel-erp' ); ?></h3>
                            <p class="ifs-card-desc"><?php esc_html_e( 'Passport details and flight preferences', 'ifs-travel-erp' ); ?></p>
                        </div>
                    </div>

                    <div class="ifs-grid-3">
                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_passport"><?php esc_html_e( 'Passport Number', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-book-alt field-icon"></span>
                                <input type="text" name="passport_no" id="inp_passport" 
                                       value="<?php echo esc_attr( $val_passport ); ?>" 
                                       placeholder="A01234567" class="ifs-input-field uppercase font-mono">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_prev_passport"><?php esc_html_e( 'Old Passport Number', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-backup field-icon"></span>
                                <input type="text" name="prev_passport_no" id="inp_prev_passport" 
                                       value="<?php echo esc_attr( $val_prev_pass ); ?>" 
                                       placeholder="Old Passport (If any)" class="ifs-input-field uppercase font-mono">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_pass_type"><?php esc_html_e( 'Passport Type', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-clipboard field-icon"></span>
                                <select name="passport_type" id="inp_pass_type" class="ifs-input-field">
                                    <option value="Regular" <?php selected( $val_pass_type, 'Regular' ); ?>>Regular / E-Passport</option>
                                    <option value="Official" <?php selected( $val_pass_type, 'Official' ); ?>>Official</option>
                                    <option value="Diplomatic" <?php selected( $val_pass_type, 'Diplomatic' ); ?>>Diplomatic</option>
                                </select>
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_issue_date"><?php esc_html_e( 'Issue Date', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-calendar field-icon"></span>
                                <input type="date" name="passport_issue_date" id="inp_issue_date" 
                                       value="<?php echo esc_attr( $val_issue_date ); ?>" class="ifs-input-field">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_expiry"><?php esc_html_e( 'Expiry Date', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-calendar-alt field-icon"></span>
                                <input type="date" name="passport_expiry" id="inp_expiry" 
                                       value="<?php echo esc_attr( $val_expiry ); ?>" class="ifs-input-field">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_issue_place"><?php esc_html_e( 'Issue Place', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-location-alt field-icon"></span>
                                <input type="text" name="passport_issue_place" id="inp_issue_place" 
                                       value="<?php echo esc_attr( $val_issue_place ); ?>" placeholder="e.g. Dhaka" class="ifs-input-field">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_ffa"><?php esc_html_e( 'Frequent Flyer Airline', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-airplane field-icon"></span>
                                <input type="text" name="frequent_flyer_airline" id="inp_ffa" 
                                       value="<?php echo esc_attr( $val_ffa ); ?>" placeholder="e.g. EK / SQ" class="ifs-input-field font-mono uppercase">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_ffn"><?php esc_html_e( 'Frequent Flyer Number', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-star-filled field-icon"></span>
                                <input type="text" name="frequent_flyer_no" id="inp_ffn" 
                                       value="<?php echo esc_attr( $val_ffn ); ?>"
                                       placeholder="e.g. 12345678" class="ifs-input-field font-mono uppercase">
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_meal"><?php esc_html_e( 'Meal Preference', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-coffee field-icon"></span>
                                <select name="meal_preference" id="inp_meal" class="ifs-input-field">
                                    <option value="MOML" <?php selected( $val_meal, 'MOML' ); ?>>Halal Meal (Default)</option>
                                    <option value="AVML" <?php selected( $val_meal, 'AVML' ); ?>>Vegetarian</option>
                                    <option value="VGML" <?php selected( $val_meal, 'VGML' ); ?>>Strict Vegan</option>
                                    <option value="CHML" <?php selected( $val_meal, 'CHML' ); ?>>Child Meal</option>
                                </select>
                            </div>
                        </div>

                        <div class="ifs-field-block">
                            <label class="ifs-field-label" for="inp_wheelchair"><?php esc_html_e( 'Wheelchair Service', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap">
                                <span class="dashicons dashicons-wheelchair field-icon"></span>
                                <select name="wheelchair_ssr" id="inp_wheelchair" class="ifs-input-field">
                                    <option value="NONE" <?php selected( $val_wheelchair, 'NONE' ); ?>>No Wheelchair</option>
                                    <option value="WCHR" <?php selected( $val_wheelchair, 'WCHR' ); ?>>Yes - Basic (WCHR)</option>
                                    <option value="WCHS" <?php selected( $val_wheelchair, 'WCHS' ); ?>>Yes - Cannot climb stairs (WCHS)</option>
                                    <option value="WCHC" <?php selected( $val_wheelchair, 'WCHC' ); ?>>Yes - Immobile (WCHC)</option>
                                </select>
                            </div>
                        </div>

                        <div class="ifs-field-block col-span-2">
                            <label class="ifs-field-label" for="inp_remarks"><?php esc_html_e( 'Internal Remarks / Notes', 'ifs-travel-erp' ); ?></label>
                            <div class="ifs-field-wrap" style="height: auto;">
                                <span class="dashicons dashicons-edit field-icon" style="top: 18px;"></span>
                                <textarea name="remarks" id="inp_remarks" rows="2" class="ifs-input-field" style="height: auto !important; padding-top: 10px; padding-bottom: 10px; line-height: 1.5 !important;" placeholder="Special staff remarks..."><?php echo esc_textarea( $val_remarks ); ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="ifs-panel-card">
                    <div class="ifs-card-header">
                        <div class="ifs-step-num">04</div>
                        <div>
                            <h3 class="ifs-card-title"><?php esc_html_e( 'Document Vault', 'ifs-travel-erp' ); ?></h3>
                            <p class="ifs-card-desc"><?php esc_html_e( 'Upload photo, passport, NID, and NOC/Trade License', 'ifs-travel-erp' ); ?></p>
                        </div>
                    </div>

                    <div class="ifs-vault-grid">
                        <div class="ifs-vault-card" id="vault_photo_card">
                            <div class="vault-card-thumb" id="vault_photo_preview">
                                <?php if ( ! empty( $val_photo ) ) : ?>
                                    <img src="<?php echo esc_url( $val_photo ); ?>" alt="Portrait" />
                                <?php else : ?>
                                    <div class="vault-empty-icon"><span class="dashicons dashicons-camera"></span></div>
                                <?php endif; ?>
                            </div>
                            <div class="vault-card-body">
                                <div class="vault-item-title"><?php esc_html_e( 'Passenger Photo', 'ifs-travel-erp' ); ?></div>
                                <input type="hidden" name="photo_url" id="inp_photo_url" value="<?php echo esc_url( $val_photo ); ?>">
                                <div class="vault-action-row">
                                    <button type="button" class="vault-btn-action" id="ifsUploadPhotoBtn">
                                        <span class="dashicons dashicons-upload"></span> <?php echo ! empty( $val_photo ) ? esc_html__( 'Change', 'ifs-travel-erp' ) : esc_html__( 'Upload', 'ifs-travel-erp' ); ?>
                                    </button>
                                    <button type="button" class="vault-btn-remove <?php echo empty( $val_photo ) ? 'hide' : ''; ?>" id="ifsRemovePhotoBtn" title="<?php esc_attr_e( 'Remove', 'ifs-travel-erp' ); ?>">
                                        <span class="dashicons dashicons-trash"></span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="ifs-vault-card" id="vault_passport_card">
                            <div class="vault-card-thumb" id="vault_passport_preview">
                                <?php if ( ! empty( $val_copy ) ) : ?>
                                    <?php if ( preg_match( '/\.(jpg|jpeg|png|webp)$/i', $val_copy ) ) : ?>
                                        <img src="<?php echo esc_url( $val_copy ); ?>" alt="Passport" />
                                    <?php else : ?>
                                        <div class="vault-pdf-icon"><span class="dashicons dashicons-pdf"></span><small>PDF</small></div>
                                    <?php endif; ?>
                                <?php else : ?>
                                    <div class="vault-empty-icon"><span class="dashicons dashicons-media-document"></span></div>
                                <?php endif; ?>
                            </div>
                            <div class="vault-card-body">
                                <div class="vault-item-title"><?php esc_html_e( 'Passport Copy', 'ifs-travel-erp' ); ?></div>
                                <input type="hidden" name="passport_copy_url" id="inp_passport_copy" value="<?php echo esc_url( $val_copy ); ?>">
                                <div class="vault-action-row">
                                    <button type="button" class="vault-btn-action" id="ifsUploadBtn">
                                        <span class="dashicons dashicons-upload"></span> <?php echo ! empty( $val_copy ) ? esc_html__( 'Change', 'ifs-travel-erp' ) : esc_html__( 'Upload', 'ifs-travel-erp' ); ?>
                                    </button>
                                    <button type="button" class="vault-btn-remove <?php echo empty( $val_copy ) ? 'hide' : ''; ?>" id="ifsRemovePassportBtn" title="<?php esc_attr_e( 'Remove', 'ifs-travel-erp' ); ?>">
                                        <span class="dashicons dashicons-trash"></span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="ifs-vault-card" id="vault_nid_card">
                            <div class="vault-card-thumb" id="vault_nid_preview">
                                <?php if ( ! empty( $val_nid_copy ) ) : ?>
                                    <?php if ( preg_match( '/\.(jpg|jpeg|png|webp)$/i', $val_nid_copy ) ) : ?>
                                        <img src="<?php echo esc_url( $val_nid_copy ); ?>" alt="NID" />
                                    <?php else : ?>
                                        <div class="vault-pdf-icon"><span class="dashicons dashicons-pdf"></span><small>PDF</small></div>
                                    <?php endif; ?>
                                <?php else : ?>
                                    <div class="vault-empty-icon"><span class="dashicons dashicons-id-alt"></span></div>
                                <?php endif; ?>
                            </div>
                            <div class="vault-card-body">
                                <div class="vault-item-title"><?php esc_html_e( 'NID / Visa Copy', 'ifs-travel-erp' ); ?></div>
                                <input type="hidden" name="nid_copy_url" id="inp_nid_copy" value="<?php echo esc_url( $val_nid_copy ); ?>">
                                <div class="vault-action-row">
                                    <button type="button" class="vault-btn-action" id="ifsUploadNidBtn">
                                        <span class="dashicons dashicons-upload"></span> <?php echo ! empty( $val_nid_copy ) ? esc_html__( 'Change', 'ifs-travel-erp' ) : esc_html__( 'Upload', 'ifs-travel-erp' ); ?>
                                    </button>
                                    <button type="button" class="vault-btn-remove <?php echo empty( $val_nid_copy ) ? 'hide' : ''; ?>" id="ifsRemoveNidBtn" title="<?php esc_attr_e( 'Remove', 'ifs-travel-erp' ); ?>">
                                        <span class="dashicons dashicons-trash"></span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="ifs-vault-card" id="vault_prof_card">
                            <div class="vault-card-thumb" id="vault_prof_preview">
                                <?php if ( ! empty( $val_prof_copy ) ) : ?>
                                    <?php if ( preg_match( '/\.(jpg|jpeg|png|webp)$/i', $val_prof_copy ) ) : ?>
                                        <img src="<?php echo esc_url( $val_prof_copy ); ?>" alt="NOC/Trade License" />
                                    <?php else : ?>
                                        <div class="vault-pdf-icon"><span class="dashicons dashicons-pdf"></span><small>PDF</small></div>
                                    <?php endif; ?>
                                <?php else : ?>
                                    <div class="vault-empty-icon"><span class="dashicons dashicons-portfolio"></span></div>
                                <?php endif; ?>
                            </div>
                            <div class="vault-card-body">
                                <div class="vault-item-title"><?php esc_html_e( 'NOC / Trade License', 'ifs-travel-erp' ); ?></div>
                                <input type="hidden" name="prof_copy_url" id="inp_prof_copy" value="<?php echo esc_url( $val_prof_copy ); ?>">
                                <div class="vault-action-row">
                                    <button type="button" class="vault-btn-action" id="ifsUploadProfBtn">
                                        <span class="dashicons dashicons-upload"></span> <?php echo ! empty( $val_prof_copy ) ? esc_html__( 'Change', 'ifs-travel-erp' ) : esc_html__( 'Upload', 'ifs-travel-erp' ); ?>
                                    </button>
                                    <button type="button" class="vault-btn-remove <?php echo empty( $val_prof_copy ) ? 'hide' : ''; ?>" id="ifsRemoveProfBtn" title="<?php esc_attr_e( 'Remove', 'ifs-travel-erp' ); ?>">
                                        <span class="dashicons dashicons-trash"></span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="ifs-action-strip">
                    <a href="<?php echo esc_url( $base_url . '&sub=list' ); ?>" class="ifs-btn-back">
                        <span class="dashicons dashicons-arrow-left-alt"></span> <?php esc_html_e( 'Cancel', 'ifs-travel-erp' ); ?>
                    </a>
                    <button type="submit" name="ifs_customer_submit" class="ifs-btn-primary">
                        <span class="dashicons dashicons-saved"></span> 
                        <?php echo $is_edit ? esc_html__( 'Save Changes', 'ifs-travel-erp' ) : esc_html__( 'Save Customer', 'ifs-travel-erp' ); ?>
                    </button>
                </div>
            </div>

            <div class="ifs-preview-sidebar">
                <div class="ifs-preview-sticky">
                    <div class="ifs-card-preview-header">
                        <div class="preview-header-left">
                            <span class="pulse-beacon"></span>
                            <span><?php esc_html_e( 'Live Passenger Profile', 'ifs-travel-erp' ); ?></span>
                        </div>
                        <span class="preview-secure-tag"><span class="dashicons dashicons-shield"></span> IATA</span>
                    </div>

                    <div class="ifs-travel-card">
                        <div class="card-chip-strip">
                            <div class="airline-brand-tag">
                                <span class="dashicons dashicons-airplane"></span>
                                <span id="prev_nation"><?php echo esc_html( strtoupper( $val_nation ) ); ?></span>
                            </div>
                            <span class="card-tier-badge" id="prev_tier"><?php echo esc_html( strtoupper( $val_type ) ); ?></span>
                        </div>

                        <div class="card-profile-hero">
                            <div class="card-avatar-wrapper">
                                <div class="card-avatar" id="prev_avatar">
                                    <?php if ( ! empty( $val_photo ) ) : ?>
                                        <img src="<?php echo esc_url( $val_photo ); ?>" alt="Passenger" id="prev_avatar_img" />
                                    <?php else : ?>
                                        <span id="prev_avatar_txt"><?php echo esc_html( $val_title ); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="card-type-chip" id="prev_type_badge">
                                    <?php echo ( $val_ptype === 'Adult' ) ? 'ADT' : ( ( $val_ptype === 'Child' ) ? 'CHD' : 'INF' ); ?>
                                </div>
                            </div>
                            <div class="hero-name-details">
                                <h4 class="card-name" id="prev_name"><?php echo esc_html( $val_title . '. ' . ( ! empty( $row->full_name ) ? $row->full_name : 'MOHAMMED RAHIM' ) ); ?></h4>
                                <div class="card-id-row">
                                    <span class="gds-pnr-tag">ID: #CUS-<?php echo $is_edit ? esc_html( str_pad( (string) $id, 5, '0', STR_PAD_LEFT ) ) : 'NEW'; ?></span>
                                    <span class="gds-status-dot"></span>
                                </div>
                            </div>
                        </div>

                        <div class="card-data-grid">
                            <div class="grid-cell">
                                <span class="card-label">PASSPORT NO</span>
                                <span class="card-val font-mono" id="prev_passport"><?php echo ! empty( $val_passport ) ? esc_html( $val_passport ) : 'NOT SET'; ?></span>
                            </div>
                            <div class="grid-cell">
                                <span class="card-label">VALIDITY</span>
                                <span class="card-val" id="prev_expiry">
                                    <?php 
                                    if ( ! empty( $val_expiry ) && $val_expiry !== '1970-01-01' ) {
                                        echo esc_html( date_i18n( 'd M Y', strtotime( $val_expiry ) ) );
                                    } else {
                                        echo 'NO DATE';
                                    }
                                    ?>
                                </span>
                            </div>
                            <div class="grid-cell">
                                <span class="card-label">GENDER &amp; DOB</span>
                                <span class="card-val" id="prev_gender_dob"><?php echo esc_html( $val_gender . ', ' . ( ! empty( $val_dob ) ? $val_dob : '-' ) ); ?></span>
                            </div>
                            <div class="grid-cell">
                                <span class="card-label">MEAL</span>
                                <span class="card-val font-mono" id="prev_meal"><?php echo esc_html( $val_meal ); ?></span>
                            </div>
                            <div class="grid-cell">
                                <span class="card-label">MOBILE</span>
                                <span class="card-val font-mono" id="prev_mobile"><?php echo ! empty( $val_mobile ) ? esc_html( $val_mobile ) : '+880 17...'; ?></span>
                            </div>
                            <div class="grid-cell">
                                <span class="card-label">WHEELCHAIR</span>
                                <span class="card-val" id="prev_wheelchair"><?php echo esc_html( $val_wheelchair ); ?></span>
                            </div>
                        </div>

                        <div class="card-mrz-zone">
                            <div class="mrz-line font-mono" id="prev_mrz_1">P&lt;BGD&lt;&lt;RAHIM&lt;&lt;MOHAMMED&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;</div>
                            <div class="mrz-line font-mono" id="prev_mrz_2">A000000000BGD0000000M0000000&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;&lt;00</div>
                        </div>

                        <div class="card-barcode-strip">
                            <div class="barcode-lines"></div>
                            <div class="barcode-sub-meta font-mono">IATCI &bull; ELECTRONIC RECORD VALIDATED</div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <style>
        .ifs-editor-workspace { max-width: 1400px; margin: 20px auto; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; color: #0f172a; box-sizing: border-box; }
        .ifs-editor-workspace *, .ifs-editor-workspace *::before, .ifs-editor-workspace *::after { box-sizing: border-box; box-shadow: none !important; text-shadow: none !important; }
        .ifs-toast { padding: 13px 18px; border-radius: 10px; font-size: 13.5px; font-weight: 600; display: flex; align-items: center; gap: 10px; margin-bottom: 22px; }
        .ifs-toast.success { background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; }
        .ifs-toast.danger  { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
        .ifs-split-editor { display: grid; grid-template-columns: 1fr 400px; gap: 28px; align-items: flex-start; }
        @media (max-width: 1180px) { .ifs-split-editor { grid-template-columns: 1fr; } }
        .ifs-panel-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 26px; margin-bottom: 22px; }
        .ifs-card-header { display: flex; align-items: center; gap: 14px; margin-bottom: 22px; padding-bottom: 14px; border-bottom: 1px solid #f1f5f9; }
        .ifs-step-num { width: 34px; height: 34px; border-radius: 9px; background: #003376; color: #ffffff; font-weight: 800; font-size: 13px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .ifs-card-title { margin: 0; font-size: 15.5px; font-weight: 800; color: #0f172a; }
        .ifs-card-desc { margin: 2px 0 0 0; font-size: 12.5px; color: #64748b; }
        .ifs-grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 18px; }
        .col-span-2 { grid-column: span 2; }
        @media (max-width: 820px) { .ifs-grid-3 { grid-template-columns: 1fr; } .col-span-2 { grid-column: span 1; } }
        .ifs-field-block { display: flex; flex-direction: column; justify-content: flex-start; gap: 6px; width: 100%; }
        .ifs-field-label { font-size: 11px; font-weight: 700; color: #475569; text-transform: capitalize; letter-spacing: 0.5px; line-height: 1.2; }
        .ifs-field-label .req { color: #e11d48; margin-left: 2px; }
        .ifs-field-wrap { position: relative; display: flex; align-items: center; width: 100%; height: 42px; }
        .ifs-field-wrap .field-icon { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 18px; width: 18px; height: 18px; line-height: 18px; pointer-events: none; z-index: 3; transition: color 0.2s ease; }
        .ifs-field-wrap .ifs-input-field { width: 100% !important; height: 42px !important; max-height: 42px !important; min-height: 42px !important; line-height: 40px !important; padding: 0 14px 0 40px !important; border: 1px solid #cbd5e1 !important; border-radius: 8px !important; font-size: 13.5px !important; color: #0f172a !important; background-color: #ffffff !important; outline: none !important; transition: border-color 0.2s ease; margin: 0 !important; display: block; }
        .ifs-field-wrap select.ifs-input-field { appearance: none; -webkit-appearance: none; background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e") !important; background-repeat: no-repeat !important; background-position: right 12px center !important; background-size: 14px !important; padding-right: 36px !important; cursor: pointer; }
        .ifs-field-wrap input[type="date"].ifs-input-field { cursor: pointer; }
        .ifs-field-wrap .ifs-input-field:focus { border-color: #003376 !important; background-color: #f8fafc !important; }
        .ifs-field-wrap:focus-within .field-icon { color: #003376; }
        .uppercase { text-transform: uppercase; }
        .font-mono { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace !important; font-weight: 600; }
        .ifs-vault-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 18px; }
        .ifs-vault-card { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; display: flex; flex-direction: column; gap: 14px; }
        .vault-card-thumb { width: 100%; height: 110px; border-radius: 10px; background: #ffffff; border: 1px dashed #cbd5e1; display: flex; align-items: center; justify-content: center; overflow: hidden; }
        .vault-card-thumb img { width: 100%; height: 100%; object-fit: cover; }
        .vault-empty-icon { color: #94a3b8; }
        .vault-empty-icon .dashicons { font-size: 32px; width: 32px; height: 32px; }
        .vault-pdf-icon { display: flex; flex-direction: column; align-items: center; color: #dc2626; font-weight: 800; }
        .vault-pdf-icon .dashicons { font-size: 36px; width: 36px; height: 36px; }
        .vault-pdf-icon small { font-size: 10px; background: #fee2e2; padding: 1px 5px; border-radius: 3px; }
        .vault-card-body { display: flex; flex-direction: column; gap: 6px; }
        .vault-item-title { font-size: 12.5px; font-weight: 700; color: #0f172a; }
        .vault-action-row { display: flex; align-items: center; gap: 6px; }
        .vault-btn-action { height: 32px; flex: 1; background: #ffffff; border: 1px solid #cbd5e1; padding: 0 10px; border-radius: 6px; font-size: 11.5px; font-weight: 700; color: #334155; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; gap: 4px; transition: background 0.2s; }
        .vault-btn-action:hover { background: #003376; color: #ffffff; border-color: #003376; }
        .vault-btn-remove { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; border-radius: 6px; width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; }
        .vault-btn-remove:hover { background: #dc2626; color: #ffffff; }
        .vault-btn-remove.hide { display: none !important; }
        .ifs-action-strip { display: flex; justify-content: space-between; align-items: center; padding: 18px 24px; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; }
        .ifs-btn-back { color: #64748b; text-decoration: none; font-size: 13.5px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; }
        .ifs-btn-primary { background: #003376; color: #ffffff; border: none; height: 42px; padding: 0 24px; border-radius: 8px; font-size: 13.5px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
        .ifs-btn-primary:hover { background: #0284c7; }
        .ifs-preview-sticky { position: sticky; top: 30px; }
        .ifs-card-preview-header { font-size: 12px; font-weight: 800; text-transform: uppercase; color: #475569; margin-bottom: 12px; display: flex; justify-content: space-between; align-items: center; }
        .preview-header-left { display: flex; align-items: center; gap: 8px; }
        .pulse-beacon { width: 8px; height: 8px; border-radius: 50%; background: #10b981; }
        .preview-secure-tag { font-size: 10px; font-weight: 800; background: #e2e8f0; color: #475569; padding: 2px 7px; border-radius: 4px; display: inline-flex; align-items: center; gap: 4px; }
        .ifs-travel-card { background: #00224f; border-radius: 18px; padding: 22px; color: #ffffff; border: 1px solid #1e3a8a; }
        .card-chip-strip { display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; border-bottom: 1px solid rgba(255, 255, 255, 0.15); padding-bottom: 10px; }
        .airline-brand-tag { display: flex; align-items: center; gap: 6px; font-size: 10px; font-weight: 800; letter-spacing: 1px; color: #7dd3fc; }
        .card-tier-badge { background: rgba(255, 255, 255, 0.18); padding: 2px 8px; border-radius: 6px; font-size: 9.5px; font-weight: 800; }
        .card-profile-hero { display: flex; align-items: center; gap: 14px; margin-bottom: 16px; }
        .card-avatar-wrapper { position: relative; flex-shrink: 0; }
        .card-avatar { width: 50px; height: 50px; border-radius: 12px; background: rgba(255, 255, 255, 0.15); border: 2px solid rgba(255, 255, 255, 0.4); display: flex; align-items: center; justify-content: center; font-weight: 900; font-size: 16px; overflow: hidden; }
        .card-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .card-type-chip { position: absolute; bottom: -4px; right: -4px; background: #0284c7; color: #ffffff; font-size: 8px; font-weight: 900; padding: 1px 4px; border-radius: 4px; border: 1px solid #ffffff; }
        .hero-name-details { flex: 1; min-width: 0; }
        .card-name { margin: 0 0 3px 0; font-size: 14px; font-weight: 800; color: #ffffff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .card-id-row { display: flex; align-items: center; gap: 8px; }
        .gds-pnr-tag { font-size: 10.5px; color: #bae6fd; font-family: monospace; font-weight: 700; }
        .gds-status-dot { width: 6px; height: 6px; border-radius: 50%; background: #38bdf8; }
        .card-data-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px 12px; padding: 12px 0; border-top: 1px dashed rgba(255, 255, 255, 0.2); border-bottom: 1px dashed rgba(255, 255, 255, 0.2); margin-bottom: 12px; }
        .grid-cell { display: flex; flex-direction: column; gap: 2px; }
        .card-label { font-size: 8px; font-weight: 800; color: #7dd3fc; letter-spacing: 0.5px; }
        .card-val { font-size: 11px; font-weight: 700; color: #ffffff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .card-mrz-zone { background: rgba(0, 0, 0, 0.35); padding: 8px; border-radius: 6px; margin-bottom: 12px; border: 1px solid rgba(255, 255, 255, 0.08); }
        .mrz-line { font-size: 8.5px; color: #e0f2fe; letter-spacing: 1px; line-height: 1.3; white-space: nowrap; overflow: hidden; }
        .card-barcode-strip { text-align: center; }
        .barcode-lines { height: 16px; background: repeating-linear-gradient(90deg, #ffffff, #ffffff 2px, transparent 2px, transparent 4px); opacity: 0.8; margin-bottom: 3px; }
        .barcode-sub-meta { font-size: 7.5px; color: #7dd3fc; letter-spacing: 0.8px; }
    </style>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const inpTitle       = document.getElementById('inp_title');
        const inpGivenName   = document.getElementById('inp_given_name');
        const inpSurname     = document.getElementById('inp_surname');
        const inpGender      = document.getElementById('inp_gender');
        const inpPtype       = document.getElementById('inp_ptype');
        const inpDob         = document.getElementById('inp_dob');
        const inpMobile      = document.getElementById('inp_mobile');
        const inpPassport    = document.getElementById('inp_passport');
        const inpExpiry      = document.getElementById('inp_expiry');
        const inpMeal        = document.getElementById('inp_meal');
        const inpWheel       = document.getElementById('inp_wheelchair');
        const inpNation      = document.getElementById('inp_nation');
        const inpType        = document.getElementById('inp_type');

        const prevName       = document.getElementById('prev_name');
        const prevAvatar     = document.getElementById('prev_avatar');
        const prevMobile     = document.getElementById('prev_mobile');
        const prevPassport   = document.getElementById('prev_passport');
        const prevExpiry     = document.getElementById('prev_expiry');
        const prevGenderDob  = document.getElementById('prev_gender_dob');
        const prevMeal       = document.getElementById('prev_meal');
        const prevWheelchair = document.getElementById('prev_wheelchair');
        const prevTypeBadge  = document.getElementById('prev_type_badge');
        const prevMrz1       = document.getElementById('prev_mrz_1');
        const prevMrz2       = document.getElementById('prev_mrz_2');
        const prevNation     = document.getElementById('prev_nation');
        const prevTier       = document.getElementById('prev_tier');

        function updateCardLive() {
            const titleVal = inpTitle ? inpTitle.value : 'MR';
            const given    = inpGivenName ? inpGivenName.value.trim().toUpperCase() : '';
            const surname  = inpSurname ? inpSurname.value.trim().toUpperCase() : '';
            const full     = (given + ' ' + surname).trim();
            
            if (full) {
                if (prevName) prevName.textContent = titleVal + '. ' + full;
                const photoInput = document.getElementById('inp_photo_url');
                if ((!photoInput || !photoInput.value) && prevAvatar) {
                    const parts = full.split(' ');
                    const initials = parts.length > 1 ? (parts[0][0] + parts[parts.length-1][0]) : parts[0].slice(0, 2);
                    prevAvatar.innerHTML = '<span>' + initials + '</span>';
                }
            } else {
                if (prevName) prevName.textContent = titleVal + '. MOHAMMED RAHIM';
                const photoInput = document.getElementById('inp_photo_url');
                if ((!photoInput || !photoInput.value) && prevAvatar) {
                    prevAvatar.innerHTML = '<span>MR</span>';
                }
            }

            if (prevMobile) prevMobile.textContent     = (inpMobile && inpMobile.value.trim()) ? inpMobile.value.trim() : '+880 17...';
            if (prevPassport) prevPassport.textContent = (inpPassport && inpPassport.value.trim()) ? inpPassport.value.trim().toUpperCase() : 'NOT SET';
            if (prevMeal) prevMeal.textContent         = inpMeal ? inpMeal.value : 'MOML';
            if (prevWheelchair) prevWheelchair.textContent = inpWheel ? inpWheel.value : 'NONE';
            if (prevNation) prevNation.textContent     = (inpNation && inpNation.value.trim()) ? inpNation.value.trim().toUpperCase() : 'BANGLADESH';
            if (prevTier) prevTier.textContent         = inpType ? inpType.value.toUpperCase() : 'RETAIL';
            
            if (prevTypeBadge && inpPtype) {
                const pt = inpPtype.value;
                prevTypeBadge.textContent = (pt === 'Adult') ? 'ADT' : ((pt === 'Child') ? 'CHD' : 'INF');
            }

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
                    prevExpiry.textContent = '< 6 MOS';
                    prevExpiry.style.color = '#fde047';
                } else {
                    prevExpiry.textContent = 'VALID (' + expDate.getFullYear() + ')';
                    prevExpiry.style.color = '#86efac';
                }
            } else if (prevExpiry) {
                prevExpiry.textContent = 'NO DATE';
                prevExpiry.style.color = '#ffffff';
            }

            const mrzSurname = surname || 'SURNAME';
            const mrzGiven   = given || 'GIVENNAME';
            const mrzPassNo  = (inpPassport && inpPassport.value.trim()) ? inpPassport.value.trim().toUpperCase() : 'A00000000';
            const cleanSur   = mrzSurname.replace(/[^A-Z]/g, '');
            const cleanGiv   = mrzGiven.replace(/[^A-Z]/g, '<');
            
            let l1 = 'P<BGD' + cleanSur + '<<' + cleanGiv;
            while (l1.length < 44) { l1 += '<'; }
            if (l1.length > 44) l1 = l1.substring(0, 44);

            let dobFormatted = '000000';
            if (inpDob && inpDob.value) {
                const dobParts = inpDob.value.split('-');
                if (dobParts.length === 3) {
                    dobFormatted = dobParts[0].substring(2, 4) + dobParts[1] + dobParts[2];
                }
            }

            let expFormatted = '000000';
            if (inpExpiry && inpExpiry.value) {
                const expParts = inpExpiry.value.split('-');
                if (expParts.length === 3) {
                    expFormatted = expParts[0].substring(2, 4) + expParts[1] + expParts[2];
                }
            }

            const mrzGender = (inpGender && inpGender.value === 'Female') ? 'F' : 'M';
            let l2 = mrzPassNo;
            while (l2.length < 9) { l2 += '<'; }
            l2 += '0BGD' + dobFormatted + '0' + mrzGender + expFormatted + '0<<<<<<<<<<<<<<00';
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

        // WP Media Uploader Integration
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
                        button: { text: 'Attach File' },
                        multiple: false
                    }).on('select', function() {
                        const attachment = uploader.state().get('selection').first().toJSON();
                        if (attachment && attachment.url) {
                            input.value = attachment.url;
                            
                            if (attachment.url.match(/\.(jpg|jpeg|png|webp|gif)$/i)) {
                                preview.innerHTML = '<img src="' + attachment.url + '" alt="Document" />';
                            } else {
                                preview.innerHTML = '<div class="vault-pdf-icon"><span class="dashicons dashicons-pdf"></span><small>PDF</small></div>';
                            }
                            
                            if (removeBtn) removeBtn.classList.remove('hide');

                            if (isPhoto) {
                                const cardAvatar = document.getElementById('prev_avatar');
                                if (cardAvatar) {
                                    cardAvatar.innerHTML = '<img src="' + attachment.url + '" alt="Passenger" />';
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
                    } else if (inputId === 'inp_prof_copy') {
                        preview.innerHTML = '<div class="vault-empty-icon"><span class="dashicons dashicons-portfolio"></span></div>';
                    } else {
                        preview.innerHTML = '<div class="vault-empty-icon"><span class="dashicons dashicons-id-alt"></span></div>';
                    }
                });
            }
        }

        setupMediaVault('ifsUploadPhotoBtn', 'ifsRemovePhotoBtn', 'inp_photo_url', 'vault_photo_preview', true);
        setupMediaVault('ifsUploadBtn', 'ifsRemovePassportBtn', 'inp_passport_copy', 'vault_passport_preview', false);
        setupMediaVault('ifsUploadNidBtn', 'ifsRemoveNidBtn', 'inp_nid_copy', 'vault_nid_preview', false);
        setupMediaVault('ifsUploadProfBtn', 'ifsRemoveProfBtn', 'inp_prof_copy', 'vault_prof_preview', false);
    });
    </script>
    <?php
}