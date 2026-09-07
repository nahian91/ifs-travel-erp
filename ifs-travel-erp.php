<?php
/**
 * Plugin Name: IFS Travel ERP
 * Plugin URI:  https://infinityflamesoft.com
 * Description: Complete Enterprise ERP for Travel Agencies featuring Air Ticketing, Refund & Reissue, Visa Processing, Hajj & Umrah, Tour Packages, Hotel Bookings & Contracted Properties, Suppliers/GDS, B2B Sub-Agents, Billing/Accounts, Office Expense Ledger, Staff/HR, Settings, and Financial Reports.
 * Version:     1.0.0
 * Author:      Infinity Flame Soft (DevNahian)
 * Author URI:  https://infinityflamesoft.com
 * Text Domain: ifs-travel-erp
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/*--------------------------------------------------------------
# 1. Constants & Definitions
--------------------------------------------------------------*/
if ( ! defined( 'ITERP_VERSION' ) ) {
    define( 'ITERP_VERSION', '1.0.0' );
}
if ( ! defined( 'ITERP_PATH' ) ) {
    define( 'ITERP_PATH', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'ITERP_URL' ) ) {
    define( 'ITERP_URL', plugin_dir_url( __FILE__ ) );
}

/*--------------------------------------------------------------
# 2. Role-Based Access Control (RBAC) Utility
--------------------------------------------------------------*/
function ifs_terp_has_access( $allowed_roles = array() ) {
    if ( empty( $allowed_roles ) ) {
        return true;
    }
    
    $current_user = wp_get_current_user();
    if ( ! $current_user || ! $current_user->exists() ) {
        return false;
    }

    // Super Admin & Shop Managers bypass all restrictions
    if ( in_array( 'administrator', (array) $current_user->roles, true ) || current_user_can( 'manage_options' ) ) {
        return true;
    }

    foreach ( $allowed_roles as $role ) {
        if ( in_array( $role, (array) $current_user->roles, true ) ) {
            return true;
        }
    }
    
    return false;
}

/*--------------------------------------------------------------
# 3. Scripts & Styles Enqueue
--------------------------------------------------------------*/
function ifs_terp_admin_enqueue_assets( $hook ) {
    if ( $hook !== 'toplevel_page_ifs_travel_erp' ) {
        return;
    }

    $plugin_uri = ITERP_URL;

    /* =====================
       Styles
    ===================== */
    wp_enqueue_style( 'bootstrap', $plugin_uri . 'assets/css/bootstrap.min.css', array(), ITERP_VERSION );
    wp_enqueue_style( 'datatables', $plugin_uri . 'assets/css/jquery.dataTables.min.css', array(), ITERP_VERSION );
    wp_enqueue_style( 'main-style', $plugin_uri . 'assets/css/style.css', array(), ITERP_VERSION );
    wp_enqueue_style( 'ifs-travel-erp-admin-style', $plugin_uri . 'assets/css/admin-style.css', array(), ITERP_VERSION );

    /* =====================
       Scripts
    ===================== */
    wp_enqueue_script( 'jquery' );
    wp_enqueue_script( 'bootstrap', $plugin_uri . 'assets/js/bootstrap.bundle.min.js', array( 'jquery' ), ITERP_VERSION, true );
    wp_enqueue_script( 'datatables', $plugin_uri . 'assets/js/jquery.dataTables.min.js', array( 'jquery' ), ITERP_VERSION, true );
    wp_enqueue_script( 'datepicker', $plugin_uri . 'assets/js/bootstrap-datepicker.js', array( 'jquery' ), ITERP_VERSION, true );
    wp_enqueue_script( 'ifs-travel-erp-main', $plugin_uri . 'assets/js/main.js', array( 'jquery' ), ITERP_VERSION, true );
    wp_enqueue_script( 'ifs-travel-erp-admin-script', $plugin_uri . 'assets/js/admin-script.js', array( 'jquery' ), ITERP_VERSION, true );
}
add_action( 'admin_enqueue_scripts', 'ifs_terp_admin_enqueue_assets' );

/*--------------------------------------------------------------
# 4. Include All Modular Sub-Files
--------------------------------------------------------------*/
if ( file_exists( ITERP_PATH . 'inc/dashboard.php' ) ) {
    require_once ITERP_PATH . 'inc/dashboard.php';
}
if ( file_exists( ITERP_PATH . 'inc/customers.php' ) ) {
    require_once ITERP_PATH . 'inc/customers.php';
}
if ( file_exists( ITERP_PATH . 'inc/ticketing.php' ) ) {
    require_once ITERP_PATH . 'inc/ticketing.php';
}
if ( file_exists( ITERP_PATH . 'inc/refund-reissue.php' ) ) {
    require_once ITERP_PATH . 'inc/refund-reissue.php';
}
if ( file_exists( ITERP_PATH . 'inc/visa.php' ) ) {
    require_once ITERP_PATH . 'inc/visa.php';
}
if ( file_exists( ITERP_PATH . 'inc/hajj-umrah.php' ) ) {
    require_once ITERP_PATH . 'inc/hajj-umrah.php';
}
if ( file_exists( ITERP_PATH . 'inc/tours.php' ) ) {
    require_once ITERP_PATH . 'inc/tours.php';
}
if ( file_exists( ITERP_PATH . 'inc/hotels.php' ) ) {
    require_once ITERP_PATH . 'inc/hotels.php';
}
if ( file_exists( ITERP_PATH . 'inc/suppliers.php' ) ) {
    require_once ITERP_PATH . 'inc/suppliers.php';
}
if ( file_exists( ITERP_PATH . 'inc/b2b-agents.php' ) ) {
    require_once ITERP_PATH . 'inc/b2b-agents.php';
}
if ( file_exists( ITERP_PATH . 'inc/invoices.php' ) ) {
    require_once ITERP_PATH . 'inc/invoices.php';
}
if ( file_exists( ITERP_PATH . 'inc/accounts.php' ) ) {
    require_once ITERP_PATH . 'inc/accounts.php'; // Accounts & Ledger Module Router
}
if ( file_exists( ITERP_PATH . 'inc/staff.php' ) ) {
    require_once ITERP_PATH . 'inc/staff.php';
}
if ( file_exists( ITERP_PATH . 'inc/reports.php' ) ) {
    require_once ITERP_PATH . 'inc/reports.php';
}
if ( file_exists( ITERP_PATH . 'inc/settings.php' ) ) {
    require_once ITERP_PATH . 'inc/settings.php';
}

/*--------------------------------------------------------------
# 5. Database Table Creation (Strict dbDelta Compliant)
--------------------------------------------------------------*/
function ifs_terp_create_system_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    // 1. Core Customers Directory
    $table_customers = $wpdb->prefix . 'iterp_customers';
    $sql_customers = "CREATE TABLE $table_customers (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        title varchar(20) DEFAULT 'MR' NOT NULL,
        passport_given_name varchar(150) DEFAULT '' NOT NULL,
        passport_surname varchar(150) DEFAULT '' NOT NULL,
        full_name varchar(255) NOT NULL,
        father_spouse_name varchar(255) DEFAULT '' NOT NULL,
        mother_name varchar(255) DEFAULT '' NOT NULL,
        gender varchar(20) DEFAULT 'Male' NOT NULL,
        marital_status varchar(30) DEFAULT 'Married' NOT NULL,
        passenger_type varchar(30) DEFAULT 'Adult' NOT NULL,
        date_of_birth date DEFAULT '1970-01-01' NOT NULL,
        birth_place varchar(100) DEFAULT '' NOT NULL,
        blood_group varchar(10) DEFAULT '' NOT NULL,
        nationality varchar(100) DEFAULT 'Bangladeshi' NOT NULL,
        nid_no varchar(100) DEFAULT '' NOT NULL,
        profession varchar(150) DEFAULT '' NOT NULL,
        mobile varchar(50) NOT NULL,
        whatsapp_no varchar(50) DEFAULT '' NOT NULL,
        email varchar(100) DEFAULT '' NOT NULL,
        emergency_contact varchar(100) DEFAULT '' NOT NULL,
        passport_no varchar(100) DEFAULT '' NOT NULL,
        prev_passport_no varchar(100) DEFAULT '' NOT NULL,
        passport_type varchar(50) DEFAULT 'Regular' NOT NULL,
        passport_issue_date date DEFAULT '1970-01-01' NOT NULL,
        passport_expiry date DEFAULT '1970-01-01' NOT NULL,
        passport_issue_place varchar(150) DEFAULT '' NOT NULL,
        frequent_flyer_no varchar(100) DEFAULT '' NOT NULL,
        meal_preference varchar(50) DEFAULT 'MOML' NOT NULL,
        wheelchair_ssr varchar(50) DEFAULT 'NONE' NOT NULL,
        client_type varchar(50) DEFAULT 'Retail' NOT NULL,
        city varchar(100) DEFAULT '' NOT NULL,
        address text NOT NULL,
        photo_url text NOT NULL,
        passport_copy_url text NOT NULL,
        nid_copy_url text NOT NULL,
        created_at datetime DEFAULT '1970-01-01 00:00:00' NOT NULL,
        PRIMARY KEY  (id),
        KEY mobile_idx (mobile),
        KEY passport_idx (passport_no)
    ) $charset_collate;";
    dbDelta( $sql_customers );

    // 2. Air Ticketing Ledger
    $table_tickets = $wpdb->prefix . 'iterp_tickets';
    $sql_tickets = "CREATE TABLE $table_tickets (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        customer_id bigint(20) NOT NULL,
        passenger_name varchar(255) DEFAULT '' NOT NULL,
        passenger_phone varchar(50) DEFAULT '' NOT NULL,
        passenger_email varchar(100) DEFAULT '' NOT NULL,
        passport_no varchar(100) DEFAULT '' NOT NULL,
        agent_id bigint(20) DEFAULT 0 NOT NULL,
        supplier_id bigint(20) DEFAULT 0 NOT NULL,
        supplier_ref varchar(100) DEFAULT '' NOT NULL,
        pnr varchar(20) NOT NULL,
        airline_pnr varchar(20) DEFAULT '' NOT NULL,
        ticket_no varchar(50) NOT NULL,
        airline varchar(100) NOT NULL,
        flight_no varchar(50) DEFAULT '' NOT NULL,
        return_flight_no varchar(50) DEFAULT '' NOT NULL,
        sector text NOT NULL,
        via_transit varchar(100) DEFAULT 'Direct' NOT NULL,
        cabin_class varchar(50) DEFAULT 'Economy' NOT NULL,
        fare_basis varchar(50) DEFAULT '' NOT NULL,
        flight_type varchar(50) DEFAULT 'One Way' NOT NULL,
        issue_date date DEFAULT '1970-01-01' NOT NULL,
        travel_date date DEFAULT '1970-01-01' NOT NULL,
        flight_time varchar(20) DEFAULT '' NOT NULL,
        arrival_date date DEFAULT '1970-01-01' NOT NULL,
        arrival_time varchar(20) DEFAULT '' NOT NULL,
        return_date date DEFAULT '1970-01-01' NOT NULL,
        return_flight_time varchar(20) DEFAULT '' NOT NULL,
        return_arrival_time varchar(20) DEFAULT '' NOT NULL,
        baggage varchar(50) DEFAULT '20 KG' NOT NULL,
        gds_pcc varchar(50) DEFAULT 'Sabre' NOT NULL,
        base_fare decimal(12,2) DEFAULT '0.00' NOT NULL,
        tax_amount decimal(12,2) DEFAULT '0.00' NOT NULL,
        commission_amount decimal(12,2) DEFAULT '0.00' NOT NULL,
        ait_amount decimal(12,2) DEFAULT '0.00' NOT NULL,
        discount_amount decimal(12,2) DEFAULT '0.00' NOT NULL,
        buy_price decimal(12,2) DEFAULT '0.00' NOT NULL,
        sell_price decimal(12,2) DEFAULT '0.00' NOT NULL,
        paid_amount decimal(12,2) DEFAULT '0.00' NOT NULL,
        due_amount decimal(12,2) DEFAULT '0.00' NOT NULL,
        profit decimal(12,2) DEFAULT '0.00' NOT NULL,
        status varchar(50) DEFAULT 'Issued' NOT NULL,
        payment_status varchar(30) DEFAULT 'Paid' NOT NULL,
        payment_method varchar(50) DEFAULT 'Bank Transfer' NOT NULL,
        transaction_id varchar(100) DEFAULT '' NOT NULL,
        remarks text,
        ticket_copy_url text NOT NULL,
        issued_by bigint(20) NOT NULL,
        created_at datetime DEFAULT '1970-01-01 00:00:00' NOT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY pnr (pnr),
        UNIQUE KEY ticket_no (ticket_no),
        KEY customer_idx (customer_id),
        KEY agent_idx (agent_id),
        KEY supplier_idx (supplier_id),
        KEY travel_date_idx (travel_date),
        KEY issue_date_idx (issue_date)
    ) $charset_collate;";
    dbDelta( $sql_tickets );

    // 3. Ticket Refund, Reissue & Void Ledger
    $table_refunds = $wpdb->prefix . 'iterp_refund_reissue';
    $sql_refunds = "CREATE TABLE $table_refunds (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        type varchar(30) NOT NULL,
        ticket_id bigint(20) NOT NULL,
        customer_id bigint(20) DEFAULT 0 NOT NULL,
        agent_id bigint(20) DEFAULT 0 NOT NULL,
        supplier_id bigint(20) DEFAULT 0 NOT NULL,
        pnr varchar(50) NOT NULL,
        new_pnr varchar(50) DEFAULT '' NOT NULL,
        ticket_no varchar(50) NOT NULL,
        new_ticket_no varchar(50) DEFAULT '' NOT NULL,
        original_fare decimal(12,2) DEFAULT '0.00' NOT NULL,
        airline_penalty decimal(12,2) DEFAULT '0.00' NOT NULL,
        agency_service_charge decimal(12,2) DEFAULT '0.00' NOT NULL,
        fare_difference decimal(12,2) DEFAULT '0.00' NOT NULL,
        refund_amount decimal(12,2) DEFAULT '0.00' NOT NULL,
        settlement_method varchar(50) DEFAULT 'Bank Transfer' NOT NULL,
        status varchar(50) DEFAULT 'Processed' NOT NULL,
        remarks text NOT NULL,
        processed_by bigint(20) NOT NULL,
        created_at datetime DEFAULT '1970-01-01 00:00:00' NOT NULL,
        PRIMARY KEY  (id),
        KEY ticket_idx (ticket_id),
        KEY customer_idx (customer_id),
        KEY agent_idx (agent_id),
        KEY pnr_idx (pnr)
    ) $charset_collate;";
    dbDelta( $sql_refunds );

    // 4. Visa Processing Tracker
    $table_visas = $wpdb->prefix . 'iterp_visa_applications';
    $sql_visas = "CREATE TABLE $table_visas (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        customer_id bigint(20) NOT NULL,
        passenger_name varchar(255) DEFAULT '' NOT NULL,
        passport_no varchar(100) DEFAULT '' NOT NULL,
        passport_expiry date DEFAULT '1970-01-01' NOT NULL,
        passport_status varchar(50) DEFAULT 'In Office' NOT NULL,
        agent_id bigint(20) DEFAULT 0 NOT NULL,
        supplier_id bigint(20) DEFAULT 0 NOT NULL,
        country varchar(100) NOT NULL,
        visa_type varchar(100) NOT NULL,
        entry_type varchar(50) DEFAULT 'Single Entry' NOT NULL,
        tracking_no varchar(100) DEFAULT '' NOT NULL,
        embassy_app_no varchar(100) DEFAULT '' NOT NULL,
        issued_visa_no varchar(100) DEFAULT '' NOT NULL,
        processing_center varchar(150) DEFAULT 'VFS Global Dhaka' NOT NULL,
        submission_date date DEFAULT '1970-01-01' NOT NULL,
        appointment_date date DEFAULT '1970-01-01' NOT NULL,
        expected_delivery date DEFAULT '1970-01-01' NOT NULL,
        issue_date date DEFAULT '1970-01-01' NOT NULL,
        expiry_date date DEFAULT '1970-01-01' NOT NULL,
        validity_days int(11) DEFAULT 30 NOT NULL,
        stay_duration varchar(50) DEFAULT '30 Days' NOT NULL,
        embassy_fee decimal(12,2) DEFAULT '0.00' NOT NULL,
        service_fee decimal(12,2) DEFAULT '0.00' NOT NULL,
        buy_price decimal(12,2) DEFAULT '0.00' NOT NULL,
        sell_price decimal(12,2) DEFAULT '0.00' NOT NULL,
        paid_amount decimal(12,2) DEFAULT '0.00' NOT NULL,
        due_amount decimal(12,2) DEFAULT '0.00' NOT NULL,
        profit decimal(12,2) DEFAULT '0.00' NOT NULL,
        status varchar(50) DEFAULT 'Processing' NOT NULL,
        payment_status varchar(30) DEFAULT 'Unpaid' NOT NULL,
        payment_method varchar(50) DEFAULT 'Bank Transfer' NOT NULL,
        documents_collected text NOT NULL,
        passport_scan_url text NOT NULL,
        photo_url text NOT NULL,
        visa_doc_url text NOT NULL,
        supporting_doc_url text NOT NULL,
        remarks text,
        created_by bigint(20) NOT NULL,
        created_at datetime DEFAULT '1970-01-01 00:00:00' NOT NULL,
        PRIMARY KEY  (id),
        KEY customer_idx (customer_id),
        KEY agent_idx (agent_id),
        KEY tracking_idx (tracking_no),
        KEY country_idx (country)
    ) $charset_collate;";
    dbDelta( $sql_visas );

    // 5. Visa Requirements Directory
    $table_visa_reqs = $wpdb->prefix . 'iterp_visa_requirements';
    $sql_visa_reqs = "CREATE TABLE $table_visa_reqs (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        country_name varchar(100) NOT NULL,
        visa_type varchar(100) NOT NULL,
        entry_type varchar(50) DEFAULT 'Single Entry' NOT NULL,
        submission_mode varchar(100) DEFAULT 'VFS / Biometric In-Person' NOT NULL,
        consular_jurisdiction varchar(150) DEFAULT '' NOT NULL,
        processing_time varchar(100) DEFAULT '' NOT NULL,
        stay_validity varchar(100) DEFAULT '30 Days' NOT NULL,
        embassy_fee decimal(12,2) DEFAULT '0.00' NOT NULL,
        service_fee decimal(12,2) DEFAULT '0.00' NOT NULL,
        standard_fee decimal(12,2) DEFAULT '0.00' NOT NULL,
        requirements_list text NOT NULL,
        admin_notes text DEFAULT '' NOT NULL,
        created_at datetime DEFAULT '1970-01-01 00:00:00' NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta( $sql_visa_reqs );

    // 6. Hajj & Umrah Packages
    $table_hajj_packages = $wpdb->prefix . 'iterp_hajj_packages';
    $sql_hajj_packages = "CREATE TABLE $table_hajj_packages (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        package_name varchar(255) NOT NULL,
        package_type varchar(100) DEFAULT 'Umrah' NOT NULL,
        visa_type varchar(100) DEFAULT 'Umrah Visa (B2B/B2C)' NOT NULL,
        transport_type varchar(100) DEFAULT 'AC Bus / Coaster Sharing' NOT NULL,
        meal_plan varchar(150) DEFAULT 'Full Board (Buffet)' NOT NULL,
        total_days int(11) DEFAULT 15 NOT NULL,
        nights_makkah int(11) DEFAULT 7 NOT NULL,
        nights_madinah int(11) DEFAULT 7 NOT NULL,
        cost_bdt decimal(12,2) DEFAULT '0.00' NOT NULL,
        selling_price decimal(12,2) DEFAULT '0.00' NOT NULL,
        price_quad decimal(12,2) DEFAULT '0.00' NOT NULL,
        price_triple decimal(12,2) DEFAULT '0.00' NOT NULL,
        price_double decimal(12,2) DEFAULT '0.00' NOT NULL,
        price_single decimal(12,2) DEFAULT '0.00' NOT NULL,
        cost_sar decimal(12,2) DEFAULT '0.00' NOT NULL,
        capacity int(11) DEFAULT 0 NOT NULL,
        hotel_makkah varchar(255) DEFAULT '' NOT NULL,
        makkah_distance varchar(100) DEFAULT '' NOT NULL,
        hotel_madinah varchar(255) DEFAULT '' NOT NULL,
        madinah_distance varchar(100) DEFAULT '' NOT NULL,
        airline_name varchar(150) DEFAULT '' NOT NULL,
        inclusions_standard text NOT NULL,
        inclusions_json text NOT NULL,
        exclusions_json text NOT NULL,
        created_at datetime DEFAULT '1970-01-01 00:00:00' NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta( $sql_hajj_packages );

    // 7. Hajj & Umrah Pilgrim Bookings
    $table_hajj_bookings = $wpdb->prefix . 'iterp_hajj_bookings';
    $sql_hajj_bookings = "CREATE TABLE $table_hajj_bookings (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        customer_id bigint(20) NOT NULL,
        agent_id bigint(20) DEFAULT 0 NOT NULL,
        supplier_id bigint(20) DEFAULT 0 NOT NULL,
        package_id bigint(20) NOT NULL,
        mahram_customer_id bigint(20) DEFAULT 0 NOT NULL,
        mahram_relation varchar(50) DEFAULT '' NOT NULL,
        room_sharing varchar(50) DEFAULT 'Quad' NOT NULL,
        pilgrim_type varchar(50) DEFAULT 'Adult' NOT NULL,
        pilgrim_id varchar(50) DEFAULT '' NOT NULL,
        brn_no varchar(100) DEFAULT '' NOT NULL,
        mofaza_no varchar(100) DEFAULT '' NOT NULL,
        tracking_id varchar(100) DEFAULT '' NOT NULL,
        nusuk_id varchar(100) DEFAULT '' NOT NULL,
        passport_expiry date DEFAULT '1970-01-01' NOT NULL,
        flight_airline varchar(100) DEFAULT '' NOT NULL,
        flight_pnr varchar(50) DEFAULT '' NOT NULL,
        flight_date date DEFAULT '1970-01-01' NOT NULL,
        return_flight_date date DEFAULT '1970-01-01' NOT NULL,
        saudi_mobile varchar(50) DEFAULT '' NOT NULL,
        emergency_contact varchar(50) DEFAULT '' NOT NULL,
        hotel_makkah varchar(255) DEFAULT '' NOT NULL,
        hotel_madinah varchar(255) DEFAULT '' NOT NULL,
        nights_makkah int(11) DEFAULT 0 NOT NULL,
        nights_madinah int(11) DEFAULT 0 NOT NULL,
        tent_zone varchar(100) DEFAULT '' NOT NULL,
        vaccine_status varchar(50) DEFAULT 'Verified' NOT NULL,
        haramain_train tinyint(1) DEFAULT 0 NOT NULL,
        ziyarah_included tinyint(1) DEFAULT 1 NOT NULL,
        kit_delivered tinyint(1) DEFAULT 0 NOT NULL,
        visa_doc_url text NOT NULL,
        buy_price decimal(12,2) DEFAULT '0.00' NOT NULL,
        sell_price decimal(12,2) DEFAULT '0.00' NOT NULL,
        paid_amount decimal(12,2) DEFAULT '0.00' NOT NULL,
        due_amount decimal(12,2) DEFAULT '0.00' NOT NULL,
        profit decimal(12,2) DEFAULT '0.00' NOT NULL,
        payment_status varchar(30) DEFAULT 'Paid' NOT NULL,
        payment_method varchar(50) DEFAULT 'Bank Transfer' NOT NULL,
        transaction_id varchar(100) DEFAULT '' NOT NULL,
        visa_status varchar(50) DEFAULT 'Pending' NOT NULL,
        status varchar(50) DEFAULT 'Booked' NOT NULL,
        remarks text,
        created_by bigint(20) NOT NULL,
        created_at datetime DEFAULT '1970-01-01 00:00:00' NOT NULL,
        PRIMARY KEY  (id),
        KEY customer_idx (customer_id),
        KEY package_idx (package_id),
        KEY agent_idx (agent_id),
        KEY supplier_idx (supplier_id),
        KEY flight_date_idx (flight_date),
        KEY status_idx (status)
    ) $charset_collate;";
    dbDelta( $sql_hajj_bookings );

    // 8. Tour Bookings
    $table_tours = $wpdb->prefix . 'iterp_tours';
    $sql_tours = "CREATE TABLE $table_tours (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        customer_id bigint(20) NOT NULL,
        agent_id bigint(20) DEFAULT 0 NOT NULL,
        supplier_id bigint(20) DEFAULT 0 NOT NULL,
        package_title varchar(255) NOT NULL,
        destination varchar(100) NOT NULL,
        duration varchar(50) DEFAULT '' NOT NULL,
        travel_date date DEFAULT '1970-01-01' NOT NULL,
        buy_price decimal(12,2) DEFAULT '0.00' NOT NULL,
        sell_price decimal(12,2) DEFAULT '0.00' NOT NULL,
        profit decimal(12,2) DEFAULT '0.00' NOT NULL,
        status varchar(50) DEFAULT 'Reserved' NOT NULL,
        created_by bigint(20) NOT NULL,
        created_at datetime DEFAULT '1970-01-01 00:00:00' NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta( $sql_tours );

    // 8.1. Tour Package Templates
    $table_tour_packages = $wpdb->prefix . 'iterp_tour_packages';
    $sql_tour_packages = "CREATE TABLE $table_tour_packages (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        package_code varchar(50) DEFAULT '' NOT NULL,
        package_name varchar(255) NOT NULL,
        package_type varchar(100) DEFAULT 'Holiday Tour' NOT NULL,
        season_type varchar(100) DEFAULT 'Regular / Off-Peak' NOT NULL,
        destination varchar(255) NOT NULL,
        departure_point varchar(150) DEFAULT '' NOT NULL,
        supplier_id bigint(20) DEFAULT 0 NOT NULL,
        difficulty_level varchar(100) DEFAULT 'Easy / Leisure' NOT NULL,
        total_days int(11) DEFAULT 3 NOT NULL,
        total_nights int(11) DEFAULT 2 NOT NULL,
        min_pax int(11) DEFAULT 2 NOT NULL,
        valid_till date DEFAULT '1970-01-01' NOT NULL,
        cost_bdt decimal(12,2) DEFAULT '0.00' NOT NULL,
        selling_price decimal(12,2) DEFAULT '0.00' NOT NULL,
        hotel_name varchar(255) DEFAULT '' NOT NULL,
        child_policy varchar(255) DEFAULT '' NOT NULL,
        package_image_url text DEFAULT '' NOT NULL,
        payment_policy text DEFAULT '' NOT NULL,
        admin_notes text DEFAULT '' NOT NULL,
        inclusions_standard text NOT NULL,
        inclusions_text text NOT NULL,
        exclusions_text text NOT NULL,
        created_at datetime DEFAULT '1970-01-01 00:00:00' NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta( $sql_tour_packages );

    // 9. Hotel Bookings
    $table_hotels = $wpdb->prefix . 'iterp_hotel_bookings';
    $sql_hotels = "CREATE TABLE $table_hotels (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        customer_id bigint(20) NOT NULL,
        agent_id bigint(20) DEFAULT 0 NOT NULL,
        supplier_id bigint(20) DEFAULT 0 NOT NULL,
        hotel_name varchar(255) NOT NULL,
        city varchar(100) NOT NULL,
        check_in date DEFAULT '1970-01-01' NOT NULL,
        check_out date DEFAULT '1970-01-01' NOT NULL,
        room_type varchar(100) DEFAULT 'Deluxe Room' NOT NULL,
        meal_plan varchar(150) DEFAULT 'Bed & Breakfast (BB)' NOT NULL,
        voucher_no varchar(100) DEFAULT '' NOT NULL,
        confirmation_no varchar(100) DEFAULT '' NOT NULL,
        buy_price decimal(12,2) DEFAULT '0.00' NOT NULL,
        sell_price decimal(12,2) DEFAULT '0.00' NOT NULL,
        paid_amount decimal(12,2) DEFAULT '0.00' NOT NULL,
        due_amount decimal(12,2) DEFAULT '0.00' NOT NULL,
        profit decimal(12,2) DEFAULT '0.00' NOT NULL,
        payment_status varchar(30) DEFAULT 'Unpaid' NOT NULL,
        payment_method varchar(50) DEFAULT 'Bank Transfer' NOT NULL,
        status varchar(50) DEFAULT 'Confirmed' NOT NULL,
        special_req text,
        created_by bigint(20) NOT NULL,
        created_at datetime DEFAULT '1970-01-01 00:00:00' NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta( $sql_hotels );

    // 9.1. Hotel Properties Directory
    $table_hotel_properties = $wpdb->prefix . 'iterp_hotel_properties';
    $sql_hotel_properties = "CREATE TABLE $table_hotel_properties (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        property_name varchar(255) NOT NULL,
        property_type varchar(100) DEFAULT 'Hotel' NOT NULL,
        city varchar(150) NOT NULL,
        country varchar(150) NOT NULL,
        star_rating varchar(20) DEFAULT '4 Star' NOT NULL,
        haram_distance varchar(100) DEFAULT '' NOT NULL,
        total_beds int(11) DEFAULT 0 NOT NULL,
        contract_start_date date DEFAULT '1970-01-01' NOT NULL,
        contract_end_date date DEFAULT '1970-01-01' NOT NULL,
        contact_person varchar(150) DEFAULT '' NOT NULL,
        contact_phone varchar(50) DEFAULT '' NOT NULL,
        contract_rate decimal(12,2) DEFAULT '0.00' NOT NULL,
        standard_sell decimal(12,2) DEFAULT '0.00' NOT NULL,
        address text,
        amenities text,
        created_at datetime DEFAULT '1970-01-01 00:00:00' NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta( $sql_hotel_properties );

    // 10. Suppliers & GDS Portals
    $table_suppliers = $wpdb->prefix . 'iterp_suppliers';
    $sql_suppliers = "CREATE TABLE $table_suppliers (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        supplier_name varchar(255) NOT NULL,
        supplier_type varchar(100) DEFAULT 'GDS / IATA' NOT NULL,
        contact_person varchar(100) DEFAULT '' NOT NULL,
        phone varchar(50) NOT NULL,
        email varchar(100) DEFAULT '' NOT NULL,
        current_balance decimal(12,2) DEFAULT '0.00' NOT NULL,
        status varchar(30) DEFAULT 'Active' NOT NULL,
        created_at datetime DEFAULT '1970-01-01 00:00:00' NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta( $sql_suppliers );

    // 11. Supplier Transaction Ledger
    $table_supplier_ledger = $wpdb->prefix . 'iterp_supplier_ledger';
    $sql_supplier_ledger = "CREATE TABLE $table_supplier_ledger (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        supplier_id bigint(20) NOT NULL,
        reference_type varchar(50) NOT NULL,
        debit decimal(12,2) DEFAULT '0.00' NOT NULL,
        credit decimal(12,2) DEFAULT '0.00' NOT NULL,
        balance_after decimal(12,2) DEFAULT '0.00' NOT NULL,
        note text NOT NULL,
        created_at datetime DEFAULT '1970-01-01 00:00:00' NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta( $sql_supplier_ledger );

    // 12. B2B Sub-Agents
    $table_agents = $wpdb->prefix . 'iterp_agents';
    $sql_agents = "CREATE TABLE $table_agents (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        agency_name varchar(255) NOT NULL,
        contact_person varchar(255) NOT NULL,
        mobile varchar(50) NOT NULL,
        email varchar(100) NOT NULL,
        city varchar(100) DEFAULT '' NOT NULL,
        address text NOT NULL,
        trade_license_no varchar(100) DEFAULT '' NOT NULL,
        agency_tier varchar(100) DEFAULT 'Standard Partner' NOT NULL,
        commission_mode varchar(50) DEFAULT 'Percentage' NOT NULL,
        commission_rate decimal(5,2) DEFAULT '0.00' NOT NULL,
        current_balance decimal(12,2) DEFAULT '0.00' NOT NULL,
        credit_limit decimal(12,2) DEFAULT '0.00' NOT NULL,
        credit_threshold int(11) DEFAULT 80 NOT NULL,
        agreement_doc_url text NOT NULL,
        status varchar(30) DEFAULT 'Active' NOT NULL,
        created_at datetime DEFAULT '1970-01-01 00:00:00' NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta( $sql_agents );

    // 13. B2B Agent Individual Ledgers
    $table_agent_ledgers = $wpdb->prefix . 'iterp_agent_ledgers';
    $sql_agent_ledgers = "CREATE TABLE $table_agent_ledgers (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        agent_id bigint(20) NOT NULL,
        reference_type varchar(50) NOT NULL,
        reference_id bigint(20) DEFAULT 0 NOT NULL,
        debit decimal(12,2) DEFAULT '0.00' NOT NULL,
        credit decimal(12,2) DEFAULT '0.00' NOT NULL,
        balance_after decimal(12,2) DEFAULT '0.00' NOT NULL,
        note text NOT NULL,
        created_at datetime DEFAULT '1970-01-01 00:00:00' NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta( $sql_agent_ledgers );

    // 14. Centralized Invoicing & Billing
    $table_invoices = $wpdb->prefix . 'iterp_invoices';
    $sql_invoices = "CREATE TABLE $table_invoices (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        invoice_no varchar(50) NOT NULL,
        client_type varchar(30) DEFAULT 'Customer' NOT NULL,
        client_id bigint(20) NOT NULL,
        subtotal decimal(12,2) DEFAULT '0.00' NOT NULL,
        discount decimal(10,2) DEFAULT '0.00' NOT NULL,
        net_total decimal(12,2) DEFAULT '0.00' NOT NULL,
        paid_amount decimal(12,2) DEFAULT '0.00' NOT NULL,
        due_amount decimal(12,2) DEFAULT '0.00' NOT NULL,
        payment_status varchar(30) DEFAULT 'Unpaid' NOT NULL,
        items_json longtext NOT NULL,
        created_by bigint(20) NOT NULL,
        created_at datetime DEFAULT '1970-01-01 00:00:00' NOT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY invoice_no (invoice_no)
    ) $charset_collate;";
    dbDelta( $sql_invoices );

    // 15. Accounts Ledger (Income, Expense, Cash Flow)
    $table_ledger = $wpdb->prefix . 'iterp_ledger';
    $sql_ledger = "CREATE TABLE $table_ledger (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        transaction_type varchar(30) NOT NULL,
        category varchar(100) NOT NULL,
        amount decimal(12,2) DEFAULT '0.00' NOT NULL,
        payment_method varchar(50) DEFAULT 'Cash' NOT NULL,
        reference_no varchar(100) DEFAULT '' NOT NULL,
        description text NOT NULL,
        transaction_date datetime DEFAULT '1970-01-01 00:00:00' NOT NULL,
        logged_by bigint(20) NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta( $sql_ledger );
}
register_activation_hook( __FILE__, 'ifs_terp_create_system_tables' );

/*--------------------------------------------------------------
# 6. Activity Logger Helper (Fallback Implementation)
--------------------------------------------------------------*/
if ( ! function_exists( 'ifs_terp_log_activity' ) ) {
    function ifs_terp_log_activity( $action ) {
        $user_id = get_current_user_id();
        $entry   = sprintf(
            '[%s] User #%d: %s',
            current_time( 'mysql' ),
            $user_id,
            sanitize_text_field( $action )
        );
        $logs = get_option( 'iterp_activity_logs', array() );
        if ( ! is_array( $logs ) ) {
            $logs = array();
        }
        array_unshift( $logs, $entry );
        if ( count( $logs ) > 500 ) {
            $logs = array_slice( $logs, 0, 500 );
        }
        update_option( 'iterp_activity_logs', $logs, false );
    }
}

/*--------------------------------------------------------------
# 7. Admin Menu Core Mounting
--------------------------------------------------------------*/
add_action( 'admin_menu', function() {
    add_menu_page(
        'IFS Travel ERP',
        'Travel ERP',
        'read', 
        'ifs_travel_erp',
        'ifs_terp_main_router_page', 
        'dashicons-airplane',
        20
    );
});

/*--------------------------------------------------------------
# 8. Main Dynamic Tab Router Engine
--------------------------------------------------------------*/
function ifs_terp_main_router_page() {
    $all_tabs = array(
        'dashboard' => array(
            'label' => __( 'Dashboard', 'ifs-travel-erp' ),
            'svg'   => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 544 512"><path d="M528 0H16C7.2 0 0 7.2 0 16v480c0 8.8 7.2 16 16 16h512c8.8 0 16-7.2 16-16V16c0-8.8-7.2-16-16-16zM272 248v-88c0-4.4 3.6-8 8-8h184c4.4 0 8 3.6 8 8v88c0 4.4-3.6 8-8 8H280c-4.4 0-8-3.6-8-8zm0 176v-88c0-4.4 3.6-8 8-8h184c4.4 0 8 3.6 8 8v88c0 4.4-3.6 8-8 8H280c-4.4 0-8-3.6-8-8zM72 152c0-4.4 3.6-8 8-8h112c4.4 0 8 3.6 8 8v208c0 4.4-3.6 8-8 8H80c-4.4 0-8-3.6-8-8V152z"/></svg>',
            'roles' => array()
        ),
        'customers' => array(
            'label' => __( 'Customers', 'ifs-travel-erp' ),
            'svg'   => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path d="M224 256c70.7 0 128-57.3 128-128S294.7 0 224 0 96 57.3 96 128s57.3 128 128 128zm89.6 32h-16.7c-22.2 10.2-46.9 16-72.9 16s-50.6-5.8-72.9-16h-16.7C60.2 288 0 348.2 0 422.4V464c0 26.5 21.5 48 48 48h352c26.5 0 48-21.5 48-48v-41.6c0-74.2-60.2-134.4-134.4-134.4z"/></svg>',
            'roles' => array( 'admin_manager', 'ticketing_staff', 'visa_officer' )
        ),
        'ticketing' => array(
            'label' => __( 'Tickets', 'ifs-travel-erp' ),
            'svg'   => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><path d="M482.3 192c34.2 0 93.7 29 93.7 64c0 36-59.5 64-93.7 64l-116.6 0L265.2 495.9c-5.7 10-16.3 16.1-27.8 16.1l-56.2 0c-10.6 0-18.3-10.2-15.4-20.4l49-171.6L112 320 68.8 377.6c-3 4-7.8 6.4-12.8 6.4l-42 0c-7.8 0-14-6.3-14-14c0-1.3 .2-2.6 .5-3.9L32 256 .5 145.9c-.4-1.3-.5-2.6-.5-3.9c0-7.8 6.3-14 14-14l42 0c5 0 9.8 2.4 12.8 6.4L112 192l102.9 0-49-171.6C162.9 10.2 170.6 0 181.2 0l56.2 0c11.5 0 22.1 6.2 27.8 16.1L365.7 192l116.6 0z"/></svg>',
            'roles' => array( 'admin_manager', 'ticketing_staff' )
        ),
        'visa' => array(
            'label' => __( 'Visas', 'ifs-travel-erp' ),
            'svg'   => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M0 96C0 60.7 28.7 32 64 32h384c35.3 0 64 28.7 64 64V416c0 35.3-28.7 64-64 64H64c-35.3 0-64-28.7-64-64V96zM48 232v32c0 8.8 7.2 16 16 16h48c8.8 0 16-7.2 16-16v-32c0-8.8-7.2-16-16-16H64c-8.8 0-16 7.2-16 16zm0 96v32c0 8.8 7.2 16 16 16h48c8.8 0 16-7.2 16-16v-32c0-8.8-7.2-16-16-16H64c-8.8 0-16 7.2-16 16zM192 216c-13.3 0-24 10.7-24 24v32c0 13.3 10.7 24 24 24h48c13.3 0 24-10.7 24-24v-32c0-13.3-10.7-24-24-24h-48zm-24 112v32c0 13.3 10.7 24 24 24h48c13.3 0 24-10.7 24-24v-32c0-13.3-10.7-24-24-24h-48c-13.3 0-24 10.7-24 24z"/></svg>',
            'roles' => array( 'admin_manager', 'visa_officer' )
        ),
        'hajj_umrah' => array(
            'label' => __( 'Hajj & Umrah', 'ifs-travel-erp' ),
            'svg'   => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><path d="M575.8 255.5c0 18-15 32.1-32 32.1h-32l.7 160.2c0 2.7-.2 5.4-.5 8.1V472c0 22.1-17.9 40-40 40H456c-1.1 0-2.2 0-3.3-.1c-1.4 .1-2.8 .1-4.2 .1H416 392c-22.1 0-40-17.9-40-40V448 384c0-17.7-14.3-32-32-32H256c-17.7 0-32 14.3-32 32v64 24c0 22.1-17.9 40-40 40H160 128.1c-1.5 0-3-.1-4.5-.2c-1.2 .1-2.4 .2-3.6 .2H104c-22.1 0-40-17.9-40-40V360c0-.9 0-1.9 .1-2.8V287.6H32c-18 0-32-14-32-32.1c0-9 3-17 10-24L266.4 8c7-7 15-8 22-8s15 2 21 7L564.8 231.5c8 7 11 15 11 24z"/></svg>',
            'roles' => array( 'admin_manager', 'visa_officer' )
        ),
        'tours' => array(
            'label' => __( 'Tours', 'ifs-travel-erp' ),
            'svg'   => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M512 96c0 53-43 96-96 96c-11.3 0-22.1-2-32-5.5V416c0 53-43 96-96 96s-96-43-96-96V186.5c-9.9 3.6-20.7 5.5-32 5.5c-53 0-96-43-96-96S107 0 160 0c41.3 0 76.6 26.1 90.4 63C261.2 26.6 288.6 0 320 0c26.2 0 49.4 18.5 56.6 44C390.4 18.1 423.8 0 464 0c26.5 0 48 21.5 48 48v48z"/></svg>',
            'roles' => array( 'admin_manager', 'ticketing_staff' )
        ),
        'hotels' => array(
            'label' => __( 'Hotels', 'ifs-travel-erp' ),
            'svg'   => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M0 32C0 14.3 14.3 0 32 0H480c17.7 0 32 14.3 32 32s-14.3 32-32 32V448c17.7 0 32 14.3 32 32s-14.3 32-32 32H304V384c0-26.5-21.5-48-48-48s-48 21.5-48 48v128H32c-17.7 0-32-14.3-32-32s14.3-32 32-32V64C14.3 64 0 49.7 0 32zm96 80v32c0 8.8 7.2 16 16 16h32c8.8 0 16-7.2 16-16V112c0-8.8-7.2-16-16-16H112c-8.8 0-16 7.2-16 16zm128 0v32c0 8.8 7.2 16 16 16h32c8.8 0 16-7.2 16-16V112c0-8.8-7.2-16-16-16H240c-8.8 0-16 7.2-16 16zm128 0v32c0 8.8 7.2 16 16 16h32c8.8 0 16-7.2 16-16V112c0-8.8-7.2-16-16-16H368c-8.8 0-16 7.2-16 16zm-256 96v32c0 8.8 7.2 16 16 16h32c8.8 0 16-7.2 16-16V208c0-8.8-7.2-16-16-16H112c-8.8 0-16 7.2-16 16zm128 0v32c0 8.8 7.2 16 16 16h32c8.8 0 16-7.2 16-16V208c0-8.8-7.2-16-16-16H240c-8.8 0-16 7.2-16 16zm128 0v32c0 8.8 7.2 16 16 16h32c8.8 0 16-7.2 16-16V208c0-8.8-7.2-16-16-16H368c-8.8 0-16 7.2-16 16z"/></svg>',
            'roles' => array( 'admin_manager', 'ticketing_staff' )
        ),
        'suppliers' => array(
            'label' => __( 'Suppliers', 'ifs-travel-erp' ),
            'svg'   => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M32 32c17.7 0 32 14.3 32 32V400c0 8.8 7.2 16 16 16H480c17.7 0 32 14.3 32 32s-14.3 32-32 32H80c-44.2 0-80-35.8-80-80V64C0 46.3 14.3 32 32 32zM160 224c17.7 0 32 14.3 32 32v96c0 17.7-14.3 32-32 32s-32-14.3-32-32V256c0-17.7 14.3-32 32-32zm128-64c17.7 0 32 14.3 32 32V352c0 17.7-14.3 32-32 32s-32-14.3-32-32V192c0-17.7 14.3-32 32-32zm128-64c17.7 0 32 14.3 32 32V352c0 17.7-14.3 32-32 32s-32-14.3-32-32V128c0-17.7 14.3-32 32-32z"/></svg>',
            'roles' => array( 'admin_manager', 'accountant' )
        ),
        'b2b_agents' => array(
            'label' => __( 'B2B Agents', 'ifs-travel-erp' ),
            'svg'   => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512"><path d="M96 224c35.3 0 64-28.7 64-64s-28.7-64-64-64-64 28.7-64 64 28.7 64 64 64zm448 0c35.3 0 64-28.7 64-64s-28.7-64-64-64-64 28.7-64 64 28.7 64 64 64zm32 32h-64c-17.6 0-33.5 7.1-45.1 18.6 40.3 22.1 68.9 62 75.1 109.4h66c17.7 0 32-14.3 32-32v-32c0-35.3-28.7-64-64-64zm-256 0c61.9 0 112-50.1 112-112S381.9 32 320 32 208 82.1 208 144s50.1 112 112 112zm76.8 32h-8.3c-20.8 10-43.9 16-68.5 16s-47.6-6-68.5-16h-8.3C179.6 288 128 339.6 128 403.2V432c0 26.5 21.5 48 48 48h288c26.5 0 48-21.5 48-48v-28.8c0-63.6-51.6-115.2-115.2-115.2zm-223.7-13.4C161.5 263.1 145.6 256 128 256H64c-35.3 0-64 28.7-64 64v32c0 17.7 14.3 32 32 32h65.9c6.3-47.4 34.9-87.3 75.2-109.4z"/></svg>',
            'roles' => array( 'admin_manager', 'accountant' )
        ),
        'invoices' => array(
            'label' => __( 'Invoices', 'ifs-travel-erp' ),
            'svg'   => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M464 128H48c-26.5 0-48 21.5-48 48v240c0 26.5 21.5 48 48 48h416c26.5 0 48-21.5 48-48V176c0-26.5-21.5-48-48-48zm-16 240c0 13.3-10.7 24-24 24H96c-13.3 0-24-10.7-24-24V216c0-13.3 10.7-24 24-24h304c13.3 0 24 10.7 24 24v152zm-88-104c-22.1 0-40 17.9-40 40s17.9 40 40 40 40-17.9 40-40-17.9-40-40-40zM400 64H48c-8.8 0-16 7.2-16 16v16h448V80c0-8.8-7.2-16-16-16z"/></svg>',
            'roles' => array( 'accountant', 'admin_manager' )
        ),
        'accounts' => array(
            'label' => __( 'Accounts', 'ifs-travel-erp' ),
            'svg'   => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><path d="M64 64C28.7 64 0 92.7 0 128V384c0 35.3 28.7 64 64 64H512c35.3 0 64-28.7 64-64V128c0-35.3-28.7-64-64-64H64zm64 320H64V128h64V384zm64-256H512V384H192V128zM48 256c0-8.8 7.2-16 16-16s16 7.2 16 16s-7.2 16-16 16s-16-7.2-16-16z"/></svg>',
            'roles' => array( 'accountant', 'admin_manager' )
        ),
        'staff' => array(
            'label' => __( 'Team & HR', 'ifs-travel-erp' ),
            'svg'   => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512"><path d="M144 0a80 80 0 1 1 0 160A80 80 0 1 1 144 0zM512 0a80 80 0 1 1 0 160A80 80 0 1 1 512 0zM0 298.7C0 239.8 47.8 192 106.7 192h74.7c58.9 0 106.7 47.8 106.7 106.7V352H0v-53.3zM352 352v-53.3c0-58.9 47.8-106.7 106.7-106.7h74.7c58.9 0 106.7 47.8 106.7 106.7V352H352zm-32-160a64 64 0 1 1 0-128 64 64 0 1 1 0 128zm-80 160c0-44.2 35.8-80 80-80h0c44.2 0 80 35.8 80 80v32H240v-32zM0 416c0-17.7 14.3-32 32-32h576c17.7 0 32 14.3 32 32v32c0 17.7-14.3 32-32 32H32c-17.7 0-32-14.3-32-32v-32z"/></svg>',
            'roles' => array( 'admin_manager' )
        ),
        'reports' => array(
            'label' => __( 'Reports', 'ifs-travel-erp' ),
            'svg'   => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><path d="M336 0H48C21.5 0 0 21.5 0 48v416c0 26.5 21.5 48 48 48h288c26.5 0 48-21.5 48-48V48c0-26.5-21.5-48-48-48zM144 432H96v-48h48v48zm0-96H96v-48h48v48zm0-96H96v-48h48v48zm144 192H176v-48h112v48zm0-96H176v-48h112v48zm0-96H176v-48h112v48zm0-112H96V80h192v48z"/></svg>',
            'roles' => array( 'admin_manager', 'accountant' )
        ),
        'refund_reissue' => array(
            'label' => __( 'Changes & Refunds', 'ifs-travel-erp' ),
            'svg'   => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M48 256c0 114.9 93.1 208 208 208c52.2 0 100-19.2 136.7-51l-43.6-43.6c-26.6 22.2-60.8 35.6-98.1 35.6-85.9 0-155.6-69.7-155.6-155.6c0-42.5 17-81.1 44.5-109.1l52.5 52.5c6.7 6.7 17.5 6.7 24.2 0s6.7-17.5 0-24.2L123.6 25.4c-6.7-6.7-17.5-6.7-24.2 0L6.4 118.4c-6.7 6.7-6.7 17.5 0 24.2s17.5 6.7 24.2 0l42.6-42.6C34.4 142.1 16 196.8 16 256h32zm416 0c0-114.9-93.1-208-208-208c-52.2 0-100 19.2-136.7 51l43.6 43.6c26.6-22.2 60.8-35.6 98.1-35.6c85.9 0 155.6 69.7 155.6 155.6c0 42.5-17 81.1-44.5 109.1l-52.5-52.5c-6.7-6.7-17.5-6.7-24.2 0s-6.7 17.5 0 24.2l93.1 93.1c6.7 6.7 17.5 6.7 24.2 0l93.1-93.1c6.7-6.7 6.7-17.5 0-24.2s-17.5-6.7-24.2 0l-42.6 42.6c38.8-42.1 57.2-96.8 57.2-156h-32z"/></svg>',
            'roles' => array( 'admin_manager', 'ticketing_staff' )
        ),
        'settings' => array(
            'label' => __( 'Settings', 'ifs-travel-erp' ),
            'svg'   => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M495.9 166.6c3.2 8.7.5 18.4-6.4 24.6l-43.3 39.4c1.1 8.3 1.7 16.8 1.7 25.4s-.6 17.1-1.7 25.4l43.3 39.4c6.9 6.2 9.6 15.9 6.4 24.6-4.4 11.9-9.7 23.3-15.8 34.3-4.7 8.7-12.8 14.9-22.4 16.5l-58.1 9.4c-10.7 10.3-22.6 19.4-35.4 27.2l12 57.7c2.5 11.9-1.9 24.3-11.4 31.5-11.8 9-24.3 17-37.4 23.9-9.1 4.8-20 3.7-27.8-2.7l-48.4-39.2c-10.3 1.7-20.8 2.6-31.5 2.6s-21.2-.9-31.5-2.6l-48.4 39.2c-7.8 6.4-18.7 7.5-27.8 2.7-13.1-6.9-25.6-14.9-37.4-23.9-9.5-7.2-13.9-19.6-11.4-31.5l12-57.7c-12.8-7.8-24.7-16.9-35.4-27.2l-58.1-9.4c-9.6-1.6-17.7-7.8-22.4-16.5-6.1-11-11.4-22.4-15.8-34.3-3.2-8.7-.5-18.4 6.4-24.6l43.3-39.4C16.6 257.1 16 248.6 16 240s.6-17.1 1.7-25.4L-5.6 175.2c-6.9-6.2-9.6-15.9-6.4-24.6 4.4-11.9 9.7-23.3 15.8-34.3 4.7-8.7 12.8-14.9 22.4-16.5l58.1-9.4c10.7-10.3 22.6-19.4 35.4-27.2l-12-57.7c-2.5-11.9 1.9-24.3 11.4-31.5 11.8-9 24.3-17 37.4-23.9 9.1-4.8 20-3.7 27.8 2.7l48.4 39.2c10.3-1.7 20.8-2.6 31.5-2.6s21.2-.9-31.5-2.6l-48.4 39.2c-7.8 6.4-18.7 7.5-27.8 2.7-13.1-6.9-25.6-14.9-37.4-23.9-9.5-7.2-13.9-19.6-11.4-31.5l12-57.7c12.8 7.8 24.7 16.9 35.4 27.2l58.1 9.4c9.6 1.6 17.7 7.8 22.4 16.5 6.1 11 11.4 22.4 15.8 34.3zM256 336c44.2 0 80-35.8 80-80s-35.8-80-80-80-80 35.8-80 80 35.8 80 80 80z"/></svg>',
            'roles' => array( 'admin_manager' )
        ),
    );

    $active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'dashboard';
    
    if ( ! array_key_exists( $active_tab, $all_tabs ) ) {
        $active_tab = 'dashboard';
    }

    if ( ! ifs_terp_has_access( $all_tabs[ $active_tab ]['roles'] ) ) {
        echo '<div class="notice notice-error"><p>' . esc_html__( 'Access Denied: You do not possess the required privilege level to monitor this operations desk.', 'ifs-travel-erp' ) . '</p></div>';
        return;
    }

    $is_print_mode = ( isset( $_GET['action'] ) && sanitize_key( $_GET['action'] ) === 'print' );

    $current_user_id = get_current_user_id();
    $current_user    = wp_get_current_user();
    
    $fname = get_user_meta( $current_user_id, 'first_name', true );
    $lname = get_user_meta( $current_user_id, 'last_name', true );
    $full_name = trim( $fname . ' ' . $lname );
    $display_name    = ! empty( $full_name ) ? $full_name : ( ! empty( $current_user->display_name ) ? $current_user->display_name : $current_user->user_login );
    $designation     = ! empty( $current_user->roles ) ? ucfirst( reset( $current_user->roles ) ) : 'Travel Agent';
    $custom_avatar   = get_user_meta( $current_user_id, 'profile_photo', true );
    $logout_url      = wp_logout_url( admin_url( 'admin.php?page=ifs_travel_erp' ) );
    ?>

    <div id="ifs-terp-wrapper" class="ifs-travel-erp-system <?php echo $is_print_mode ? 'ifs-terp-print' : ''; ?>">
        
        <?php if ( ! $is_print_mode ) : ?>
            <div class="ifs-terp-sidebar-container" id="ifsSidebarContainer">
                <div class="ifs-terp-sidebar-brand">
                    <div class="brand-title-wrap">
                        <span class="dashicons dashicons-airplane"></span>
                        <h3 class="brand-text">IFS Travel ERP</h3>
                    </div>
                    <button type="button" id="ifsSidebarToggle" class="ifs-sidebar-toggle-btn" title="<?php esc_attr_e( 'Toggle Sidebar', 'ifs-travel-erp' ); ?>">
                        <span class="dashicons dashicons-menu-alt3"></span>
                    </button>
                </div>

                <div class="ifs-terp-sidebar-scroll-box">
                    <ul class="ifs-terp-left-tabs">
                        <?php 
                        foreach ( $all_tabs as $slug => $config ) : 
                            if ( ! ifs_terp_has_access( $config['roles'] ) ) {
                                continue; 
                            }
                            $active_class = ( $active_tab === $slug ) ? 'active' : '';
                            $target_url   = admin_url( 'admin.php?page=ifs_travel_erp&tab=' . $slug );
                            ?>
                            <li class="<?php echo esc_attr( 'tab-' . $slug ); ?>">
                                <a class="<?php echo esc_attr( $active_class ); ?>" href="<?php echo esc_url( $target_url ); ?>" title="<?php echo esc_attr( $config['label'] ); ?>">
                                    <?php echo $config['svg']; ?>
                                    <span class="tab-label"><?php echo esc_html( $config['label'] ); ?></span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        <?php endif; ?>

        <div class="ifs-terp-right-box">
            
            <?php if ( ! $is_print_mode ) : ?>
                <!-- Top Header Bar -->
                <div class="ifs-top-header-bar">
                    <div class="ifs-top-left-cluster">
                        <div class="ifs-top-brand-badge">
                            <span class="dashicons dashicons-shield-alt"></span>
                            <span>IFS Enterprise Core</span>
                        </div>
                        <div class="ifs-top-search-indicator">
                            <span class="dashicons dashicons-dashboard"></span>
                            <span>Live Operations Console</span>
                        </div>
                    </div>

                    <div class="ifs-top-user-menu">
                        <div class="top-user-avatar">
                            <?php 
                            if ( ! empty( $custom_avatar ) ) {
                                echo '<img src="' . esc_url( $custom_avatar ) . '" alt="' . esc_attr( $display_name ) . '" width="42" height="42" />';
                            } else {
                                echo get_avatar( $current_user_id, 42, '', '', array( 'class' => 'avatar-round' ) ); 
                            }
                            ?>
                        </div>
                        <div class="top-user-info">
                            <span class="top-user-name"><?php echo esc_html( $display_name ); ?></span>
                            <span class="top-user-role"><?php echo esc_html( str_replace( '_', ' ', $designation ) ); ?></span>
                        </div>
                        <a href="<?php echo esc_url( $logout_url ); ?>" class="ifs-top-logout-btn" title="<?php esc_attr_e( 'Log Out', 'ifs-travel-erp' ); ?>">
                            <span class="dashicons dashicons-button-power"></span> Logout
                        </a>
                    </div>
                </div>
            <?php endif; ?>

            <div class="ifs-content-body-area">
                <?php
                switch ( $active_tab ) {
                    case 'dashboard':
                        if ( function_exists( 'ifs_terp_dashboard_tab' ) ) { ifs_terp_dashboard_tab(); }
                        break;
                    case 'customers':
                        if ( function_exists( 'ifs_terp_customers_tab' ) ) { ifs_terp_customers_tab(); }
                        break;
                    case 'ticketing':
                        if ( function_exists( 'ifs_terp_ticketing_tab' ) ) { ifs_terp_ticketing_tab(); }
                        break;
                    case 'visa':
                        if ( function_exists( 'ifs_terp_visa_tab' ) ) { ifs_terp_visa_tab(); }
                        break;
                    case 'hajj_umrah':
                        if ( function_exists( 'ifs_terp_hajj_umrah_tab' ) ) { ifs_terp_hajj_umrah_tab(); }
                        break;
                    case 'tours':
                        if ( function_exists( 'ifs_terp_tours_tab' ) ) { ifs_terp_tours_tab(); }
                        break;
                    case 'hotels':
                        if ( function_exists( 'ifs_terp_hotels_tab' ) ) { ifs_terp_hotels_tab(); }
                        break;
                    case 'suppliers':
                        if ( function_exists( 'ifs_terp_suppliers_tab' ) ) { ifs_terp_suppliers_tab(); }
                        break;
                    case 'b2b_agents':
                        if ( function_exists( 'ifs_terp_b2b_agents_tab' ) ) { ifs_terp_b2b_agents_tab(); }
                        break;
                    case 'invoices':
                        if ( function_exists( 'ifs_terp_invoices_tab' ) ) { ifs_terp_invoices_tab(); }
                        break;
                    case 'accounts':
                        if ( function_exists( 'ifs_terp_accounts_tab' ) ) { 
                            ifs_terp_accounts_tab(); 
                        } elseif ( function_exists( 'ifs_terp_ledger_entry_page' ) ) {
                            ifs_terp_ledger_entry_page();
                        }
                        break;
                    case 'staff':
                        if ( function_exists( 'ifs_terp_staff_tab' ) ) { 
                            ifs_terp_staff_tab(); 
                        } elseif ( function_exists( 'ifs_terp_staff_list_page' ) ) { 
                            ifs_terp_staff_list_page(); 
                        }
                        break;
                    case 'reports':
                        if ( function_exists( 'ifs_terp_reports_tab' ) ) { 
                            ifs_terp_reports_tab(); 
                        } elseif ( function_exists( 'ifs_terp_financial_reports_page' ) ) {
                            ifs_terp_financial_reports_page();
                        }
                        break;
                    case 'refund_reissue':
                        if ( function_exists( 'ifs_terp_refund_reissue_tab' ) ) { ifs_terp_refund_reissue_tab(); }
                        break;
                    case 'settings':
                        if ( function_exists( 'ifs_terp_settings_tab' ) ) { 
                            ifs_terp_settings_tab(); 
                        } else {
                            ?>
                            <div class="wrap ifs-table-card">
                                <div class="ifs-table-top-bar">
                                    <h3><span class="dashicons dashicons-admin-generic"></span> General Settings</h3>
                                    <p>Configure system preferences, agency details, and defaults.</p>
                                </div>
                                <div class="ifs-form-wrap" style="padding: 20px;">
                                    <p>Settings module active. Sub-file or callback loading placeholder.</p>
                                </div>
                            </div>
                            <?php
                        }
                        break;
                }
                ?>
            </div>
        </div>
    </div>

    <!-- Sidebar Collapse & Responsive Handling -->
    <script>
    jQuery(document).ready(function($) {
        $('#ifsSidebarToggle').on('click', function() {
            $('#ifsSidebarContainer').toggleClass('collapsed');
        });
    });
    </script>
    <?php
}

/**
 * 1. ROOT HOMEPAGE TO WP-ADMIN REDIRECT (Only for logged-in operators)
 */
function ifs_terp_root_to_admin_redirect() {
    if ( is_admin() || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) || ( defined( 'DOING_CRON' ) && DOING_CRON ) ) {
        return;
    }

    // Only redirect if a user is logged in to avoid infinite loop on logout
    if ( ! is_user_logged_in() ) {
        return;
    }

    $home_path   = ifs_terp_upper_safe_path_parse( home_url() );
    $request_uri = ifs_terp_upper_safe_path_parse( $_SERVER['REQUEST_URI'] ?? '' );

    if ( $request_uri === $home_path ) {
        wp_safe_redirect( admin_url( 'admin.php?page=ifs_travel_erp' ), 302 );
        exit;
    }
}
add_action( 'template_redirect', 'ifs_terp_root_to_admin_redirect', 5 );

function ifs_terp_upper_safe_path_parse( $url ) {
    $path = parse_url( $url, PHP_URL_PATH );
    return trim( (string) $path, '/' );
}

/**
 * 2. POST-LOGIN DASHBOARD REDIRECT
 */
function ifs_terp_custom_login_redirect( $redirect_to, $request, $user ) {
    if ( isset( $user->roles ) && is_array( $user->roles ) ) {
        return admin_url( 'admin.php?page=ifs_travel_erp' );
    }
    return $redirect_to;
}
add_filter( 'login_redirect', 'ifs_terp_custom_login_redirect', 10, 3 );

/**
 * 3. LOGOUT ROUTING OVERRIDE (WordPress Native Flow)
 */
function ifs_terp_custom_logout_redirect( $redirect_to, $requested_redirect_to, $user ) {
    return home_url();
}
add_filter( 'logout_redirect', 'ifs_terp_custom_logout_redirect', 10, 3 );

/**
 * 4. CUSTOM LOGIN WHITE-LABEL LOGO HOOKS
 */
function ifs_terp_login_logo_url() { return home_url(); }
add_filter( 'login_headerurl', 'ifs_terp_login_logo_url' );

function ifs_terp_login_logo_title() { return get_bloginfo( 'name' ); }
add_filter( 'login_headertext', 'ifs_terp_login_logo_title' );

/**
 * 5. MATHEMATICAL CAPTCHA ENGINE
 */
function ifs_terp_display_login_captcha() {
    $num1 = rand( 1, 9 );
    $num2 = rand( 1, 9 );
    $captcha_token = md5( uniqid( (string) rand(), true ) );
    set_transient( 'iterp_captcha_' . $captcha_token, ( $num1 + $num2 ), 300 );
    ?>
    <div class="ifs-terp-captcha-container" style="margin-bottom: 16px;">
        <label class="ifs-terp-captcha-label" for="iterp_captcha_answer" style="display: block; font-weight: 600; margin-bottom: 4px;">
            <?php esc_html_e( 'Security Verification', 'ifs-travel-erp' ); ?>
        </label>
        <p class="ifs-terp-captcha-math" style="margin: 0 0 6px 0; font-size: 13px; color: #475569;">
            <?php printf( esc_html__( 'Please solve: %d + %d = ?', 'ifs-travel-erp' ), $num1, $num2 ); ?>
        </p>
        <input type="text" name="iterp_captcha_answer" id="iterp_captcha_answer" class="input" value="" size="4" autocomplete="off" required style="width: 80px;" />
        <input type="hidden" name="iterp_captcha_token" value="<?php echo esc_attr( $captcha_token ); ?>" />
    </div>
    <?php
}
add_action( 'login_form', 'ifs_terp_display_login_captcha' );

function ifs_terp_validate_login_captcha( $user, $username, $password ) {
    // Skip captcha check if authentication already failed
    if ( is_wp_error( $user ) ) { 
        return $user; 
    }

    // Only validate captcha if POST request to wp-login.php
    if ( isset( $_SERVER['REQUEST_METHOD'] ) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['log'] ) ) {
        $user_answer = isset( $_POST['iterp_captcha_answer'] ) ? sanitize_text_field( wp_unslash( $_POST['iterp_captcha_answer'] ) ) : '';
        $token       = isset( $_POST['iterp_captcha_token'] ) ? sanitize_text_field( wp_unslash( $_POST['iterp_captcha_token'] ) ) : '';
        
        $correct_answer = get_transient( 'iterp_captcha_' . $token );
        delete_transient( 'iterp_captcha_' . $token );

        if ( $correct_answer === false || intval( $user_answer ) !== intval( $correct_answer ) ) {
            return new WP_Error( 'authentication_failed', __( '<strong>ERROR</strong>: Incorrect security verification answer.', 'ifs-travel-erp' ) );
        }
    }

    return $user;
}
add_filter( 'authenticate', 'ifs_terp_validate_login_captcha', 25, 3 );