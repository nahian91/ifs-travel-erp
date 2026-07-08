<?php
/**
 * Plugin Name: IFS Travel ERP
 * Plugin URI:  https://infinityflamesoft.com
 * Description: Complete Management Software for Travel Agencies featuring Air Ticketing, Visa Processing, Hajj & Umrah, B2B Agents, and Accounts Management.
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

    // Super Admin explicitly bypasses all checks
    if ( in_array( 'administrator', $current_user->roles, true ) || current_user_can( 'manage_options' ) ) {
        return true;
    }

    foreach ( $allowed_roles as $role ) {
        if ( in_array( $role, $current_user->roles, true ) ) {
            return true;
        }
    }
    
    return false;
}

/*--------------------------------------------------------------
# 3. Scripts & Styles Enqueue
--------------------------------------------------------------*/
function ifs_terp_admin_enqueue_assets( $hook ) {
    // Heavy admin assets are isolated solely to the app layout page to prevent site-wide bloat
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
    wp_enqueue_script( 'bootstrap', $plugin_uri . 'assets/js/bootstrap.bundle.min.js', array('jquery'), ITERP_VERSION, true );
    wp_enqueue_script( 'datatables', $plugin_uri . 'assets/js/jquery.dataTables.min.js', array('jquery'), ITERP_VERSION, true );
    wp_enqueue_script( 'datepicker', $plugin_uri . 'assets/js/bootstrap-datepicker.js', array('jquery'), ITERP_VERSION, true );
    wp_enqueue_script( 'ifs-travel-erp-main', $plugin_uri . 'assets/js/main.js', array('jquery'), ITERP_VERSION, true );
    wp_enqueue_script( 'ifs-travel-erp-admin-script', $plugin_uri . 'assets/js/admin-script.js', array('jquery'), ITERP_VERSION, true );
}
add_action( 'admin_enqueue_scripts', 'ifs_terp_admin_enqueue_assets' );

/*--------------------------------------------------------------
# 4. Include Modular Sub-Files
--------------------------------------------------------------*/
require_once ITERP_PATH . 'inc/dashboard.php';
require_once ITERP_PATH . 'inc/customers.php';
require_once ITERP_PATH . 'inc/ticketing.php';
require_once ITERP_PATH . 'inc/visa.php';
require_once ITERP_PATH . 'inc/hajj-umrah.php';
require_once ITERP_PATH . 'inc/tours.php';
require_once ITERP_PATH . 'inc/b2b-agents.php';
require_once ITERP_PATH . 'inc/accounts.php';
require_once ITERP_PATH . 'inc/reports.php';
require_once ITERP_PATH . 'inc/settings.php';

/*--------------------------------------------------------------
# 5. Database Table Creation (Strict dbDelta Compliant)
--------------------------------------------------------------*/
function ifs_terp_create_system_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    // Core Customers Directory
    $table_customers = $wpdb->prefix . 'iterp_customers';
    $sql_customers = "CREATE TABLE $table_customers (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        full_name varchar(255) NOT NULL,
        mobile varchar(50) NOT NULL,
        email varchar(100) DEFAULT '' NOT NULL,
        passport_no varchar(100) DEFAULT '' NOT NULL,
        passport_expiry date DEFAULT '1970-01-01' NOT NULL,
        address text NOT NULL,
        client_type varchar(50) DEFAULT 'Retail' NOT NULL,
        created_at datetime DEFAULT '1970-01-01 00:00:00' NOT NULL,
        PRIMARY KEY  (id),
        KEY mobile_idx (mobile)
    ) $charset_collate;";
    dbDelta( $sql_customers );

    // Air Ticketing Ledger
    $table_tickets = $wpdb->prefix . 'iterp_tickets';
    $sql_tickets = "CREATE TABLE $table_tickets (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        customer_id bigint(20) NOT NULL,
        pnr varchar(20) NOT NULL,
        ticket_no varchar(50) NOT NULL,
        airline varchar(100) NOT NULL,
        sector text NOT NULL,
        travel_date date DEFAULT '1970-01-01' NOT NULL,
        supplier_id bigint(20) DEFAULT 0 NOT NULL,
        buy_price decimal(10,2) DEFAULT '0.00' NOT NULL,
        sell_price decimal(10,2) DEFAULT '0.00' NOT NULL,
        profit decimal(10,2) DEFAULT '0.00' NOT NULL,
        status varchar(50) DEFAULT 'Issued' NOT NULL,
        issued_by bigint(20) NOT NULL,
        created_at datetime DEFAULT '1970-01-01 00:00:00' NOT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY pnr (pnr),
        UNIQUE KEY ticket_no (ticket_no)
    ) $charset_collate;";
    dbDelta( $sql_tickets );

    // Visa Processing Tracker
    $table_visas = $wpdb->prefix . 'iterp_visas';
    $sql_visas = "CREATE TABLE $table_visas (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        customer_id bigint(20) NOT NULL,
        country varchar(100) NOT NULL,
        visa_type varchar(50) NOT NULL,
        submission_date date DEFAULT '1970-01-01' NOT NULL,
        expected_delivery date DEFAULT '1970-01-01' NOT NULL,
        supplier_id bigint(20) DEFAULT 0 NOT NULL,
        buy_price decimal(10,2) DEFAULT '0.00' NOT NULL,
        sell_price decimal(10,2) DEFAULT '0.00' NOT NULL,
        status varchar(50) DEFAULT 'Processing' NOT NULL,
        documents_collected text NOT NULL,
        created_by bigint(20) NOT NULL,
        created_at datetime DEFAULT '1970-01-01 00:00:00' NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta( $sql_visas );

    // B2B Agents Sub-System
    $table_agents = $wpdb->prefix . 'iterp_agents';
    $sql_agents = "CREATE TABLE $table_agents (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        company_name varchar(255) NOT NULL,
        contact_person varchar(255) NOT NULL,
        mobile varchar(50) NOT NULL,
        email varchar(100) NOT NULL,
        current_balance decimal(12,2) DEFAULT '0.00' NOT NULL,
        credit_limit decimal(12,2) DEFAULT '0.00' NOT NULL,
        status varchar(30) DEFAULT 'Active' NOT NULL,
        created_at datetime DEFAULT '1970-01-01 00:00:00' NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta( $sql_agents );

    // Centralized Invoicing & Billing
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

    // Accounts Ledger (Income, Expense, Bank Sync)
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

    // Security Audit Trail Logging
    $table_audit = $wpdb->prefix . 'iterp_audit_logs';
    $sql_audit = "CREATE TABLE $table_audit (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        user_role varchar(50) NOT NULL,
        action_performed text NOT NULL,
        ip_address varchar(45) NOT NULL,
        timestamp datetime DEFAULT '1970-01-01 00:00:00' NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta( $sql_audit );
}
register_activation_hook( __FILE__, 'ifs_terp_create_system_tables' );

/*--------------------------------------------------------------
# 6. Global Security Audit Logger Engine
--------------------------------------------------------------*/
function ifs_terp_log_activity( $action_description ) {
    global $wpdb;
    $current_user = wp_get_current_user();
    $user_id = $current_user->exists() ? $current_user->ID : 0;
    $user_roles = $current_user->exists() ? implode( ', ', $current_user->roles ) : 'guest';
    
    $ip_address = '0.0.0.0';
    if ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
        $raw_ip = wp_unslash( $_SERVER['REMOTE_ADDR'] );
        if ( function_exists( 'rest_is_ip_address' ) && rest_is_ip_address( $raw_ip ) ) {
            $ip_address = $raw_ip;
        } elseif ( filter_var( $raw_ip, FILTER_VALIDATE_IP ) ) {
            $ip_address = $raw_ip;
        }
    }

    $wpdb->insert(
        $wpdb->prefix . 'iterp_audit_logs',
        array(
            'user_id'          => $user_id,
            'user_role'        => $user_roles,
            'action_performed' => sanitize_text_field( $action_description ),
            'ip_address'       => $ip_address,
            'timestamp'        => current_time( 'mysql' )
        ),
        array( '%d', '%s', '%s', '%s', '%s' )
    );
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
            'label' => 'Dashboard',
            'svg'   => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 544 512"><path d="M528 0H16C7.2 0 0 7.2 0 16v480c0 8.8 7.2 16 16 16h512c8.8 0 16-7.2 16-16V16c0-8.8-7.2-16-16-16zM272 248v-88c0-4.4 3.6-8 8-8h184c4.4 0 8 3.6 8 8v88c0 4.4-3.6 8-8 8H280c-4.4 0-8-3.6-8-8zm0 176v-88c0-4.4 3.6-8 8-8h184c4.4 0 8 3.6 8 8v88c0 4.4-3.6 8-8 8H280c-4.4 0-8-3.6-8-8zM72 152c0-4.4 3.6-8 8-8h112c4.4 0 8 3.6 8 8v208c0 4.4-3.6 8-8 8H80c-4.4 0-8-3.6-8-8V152z"/></svg>',
            'roles' => array()
        ),
        'customers' => array(
            'label' => 'Customers',
            'svg'   => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path d="M224 256c70.7 0 128-57.3 128-128S294.7 0 224 0 96 57.3 96 128s57.3 128 128 128zm89.6 32h-16.7c-22.2 10.2-46.9 16-72.9 16s-50.6-5.8-72.9-16h-16.7C60.2 288 0 348.2 0 422.4V464c0 26.5 21.5 48 48 48h352c26.5 0 48-21.5 48-48v-41.6c0-74.2-60.2-134.4-134.4-134.4z"/></svg>',
            'roles' => array('admin_manager', 'ticketing_staff', 'visa_officer')
        ),
        'ticketing' => array(
            'label' => 'Air Ticketing',
            'svg'   => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><path d="M482.3 192c34.2 0 93.7 29 93.7 64c0 36-59.5 64-93.7 64l-116.6 0L265.2 495.9c-5.7 10-16.3 16.1-27.8 16.1l-56.2 0c-10.6 0-18.3-10.2-15.4-20.4l49-171.6L112 320 68.8 377.6c-3 4-7.8 6.4-12.8 6.4l-42 0c-7.8 0-14-6.3-14-14c0-1.3 .2-2.6 .5-3.9L32 256 .5 145.9c-.4-1.3-.5-2.6-.5-3.9c0-7.8 6.3-14 14-14l42 0c5 0 9.8 2.4 12.8 6.4L112 192l102.9 0-49-171.6C162.9 10.2 170.6 0 181.2 0l56.2 0c11.5 0 22.1 6.2 27.8 16.1L365.7 192l116.6 0z"/></svg>',
            'roles' => array('admin_manager', 'ticketing_staff')
        ),
        'visa' => array(
            'label' => 'Visa Processing',
            'svg'   => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M0 96C0 60.7 28.7 32 64 32h384c35.3 0 64 28.7 64 64V416c0 35.3-28.7 64-64 64H64c-35.3 0-64-28.7-64-64V96zM48 232v32c0 8.8 7.2 16 16 16h48c8.8 0 16-7.2 16-16v-32c0-8.8-7.2-16-16-16H64c-8.8 0-16 7.2-16 16zm0 96v32c0 8.8 7.2 16 16 16h48c8.8 0 16-7.2 16-16v-32c0-8.8-7.2-16-16-16H64c-8.8 0-16 7.2-16 16zM192 216c-13.3 0-24 10.7-24 24v32c0 13.3 10.7 24 24 24h48c13.3 0 24-10.7 24-24v-32c0-13.3-10.7-24-24-24h-48zm-24 112v32c0 13.3 10.7 24 24 24h48c13.3 0 24-10.7 24-24v-32c0-13.3-10.7-24-24-24h-48c-13.3 0-24 10.7-24 24z"/></svg>',
            'roles' => array('admin_manager', 'visa_officer')
        ),
        'hajj_umrah' => array(
            'label' => 'Hajj & Umrah',
            'svg'   => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><path d="M575.8 255.5c0 18-15 32.1-32 32.1h-32l.7 160.2c0 2.7-.2 5.4-.5 8.1V472c0 22.1-17.9 40-40 40H456c-1.1 0-2.2 0-3.3-.1c-1.4 .1-2.8 .1-4.2 .1H416 392c-22.1 0-40-17.9-40-40V448 384c0-17.7-14.3-32-32-32H256c-17.7 0-32 14.3-32 32v64 24c0 22.1-17.9 40-40 40H160 128.1c-1.5 0-3-.1-4.5-.2c-1.2 .1-2.4 .2-3.6 .2H104c-22.1 0-40-17.9-40-40V360c0-.9 0-1.9 .1-2.8V287.6H32c-18 0-32-14-32-32.1c0-9 3-17 10-24L266.4 8c7-7 15-8 22-8s15 2 21 7L564.8 231.5c8 7 11 15 11 24z"/></svg>',
            'roles' => array('admin_manager', 'visa_officer', 'travel_agent')
        ),
        'b2b_agents' => array(
            'label' => 'B2B Agents',
            'svg'   => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512"><path d="M96 224c35.3 0 64-28.7 64-64s-28.7-64-64-64-64 28.7-64 64 28.7 64 64 64zm448 0c35.3 0 64-28.7 64-64s-28.7-64-64-64-64 28.7-64 64 28.7 64 64 64zm32 32h-64c-17.6 0-33.5 7.1-45.1 18.6 40.3 22.1 68.9 62 75.1 109.4h66c17.7 0 32-14.3 32-32v-32c0-35.3-28.7-64-64-64zm-256 0c61.9 0 112-50.1 112-112S381.9 32 320 32 208 82.1 208 144s50.1 112 112 112zm76.8 32h-8.3c-20.8 10-43.9 16-68.5 16s-47.6-6-68.5-16h-8.3C179.6 288 128 339.6 128 403.2V432c0 26.5 21.5 48 48 48h288c26.5 0 48-21.5 48-48v-28.8c0-63.6-51.6-115.2-115.2-115.2zm-223.7-13.4C161.5 263.1 145.6 256 128 256H64c-35.3 0-64 28.7-64 64v32c0 17.7 14.3 32 32 32h65.9c6.3-47.4 34.9-87.3 75.2-109.4z"/></svg>',
            'roles' => array('admin_manager', 'accountant')
        ),
        'accounts' => array(
            'label' => 'Accounts',
            'svg'   => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M464 128H48c-26.5 0-48 21.5-48 48v240c0 26.5 21.5 48 48 48h416c26.5 0 48-21.5 48-48V176c0-26.5-21.5-48-48-48zm-16 240c0 13.3-10.7 24-24 24H96c-13.3 0-24-10.7-24-24V216c0-13.3 10.7-24 24-24h304c13.3 0 24 10.7 24 24v152zm-88-104c-22.1 0-40 17.9-40 40s17.9 40 40 40 40-17.9 40-40-17.9-40-40-40zM400 64H48c-8.8 0-16 7.2-16 16v16h448V80c0-8.8-7.2-16-16-16z"/></svg>',
            'roles' => array('accountant', 'admin_manager')
        ),
        'reports' => array(
            'label' => 'Reports',
            'svg'   => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><path d="M336 0H48C21.5 0 0 21.5 0 48v416c0 26.5 21.5 48 48 48h288c26.5 0 48-21.5 48-48V48c0-26.5-21.5-48-48-48zM144 432H96v-48h48v48zm0-96H96v-48h48v48zm0-96H96v-48h48v48zm144 192H176v-48h112v48zm0-96H176v-48h112v48zm0-96H176v-48h112v48zm0-112H96V80h192v48z"/></svg>',
            'roles' => array('admin_manager', 'accountant')
        ),
        'settings' => array(
            'label' => 'Settings',
            'svg'   => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M487.4 315.7l-42.6-24.6c2.3-14.2 3.5-28.7 3.5-43.1s-1.2-28.9-3.5-43.1l42.6-24.6c11.5-6.6 15.4-21.3 8.7-32.8L447.5 61.2c-6.6-11.5-21.3-15.4-32.8-8.7L372 77.1c-22.1-14.8-46.7-26.3-72.9-33.8L292.8 12C291.1 5.2 285 0 278.1 0h-44.2c-6.9 0-13 5.2-14.7 12L213 43.3c-26.2 7.5-50.8 19-72.9 33.8l-42.7-24.6c-11.5-6.7-26.2-2.8-32.8 8.7L16.1 147.3c-6.7 11.5-2.8 26.2 8.7 32.8l42.6 24.6c-2.3 14.2-3.5 28.7-3.5 43.1s1.2 28.9 3.5 43.1l-42.6 24.6c-11.5 6.6-15.4 21.3-8.7 32.8l48.6 84.3c6.6 11.5 21.3 15.4 32.8 8.7l42.7-24.6c22.1 14.8 46.7 26.3 72.9 33.8L219.2 500c1.7 6.8 7.8 12 14.7 12h44.2c6.9 0 13-5.2 14.7-12l6.3-31.3c26.2-7.5 50.8-19 72.9-33.8l42.7 24.6c11.5 6.7 26.2 2.8 32.8-8.7l48.6-84.3c6.7-11.5 2.8-26.2-8.7-32.9zM256 336c-44.2 0-80-35.8-80-80s35.8-80 80-80 80 35.8 80 80-35.8 80-80 80z"/></svg>',
            'roles' => array('admin_manager')
        ),
        'logout' => array(
            'label' => 'Log Out',
            'svg'   => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M160 96c17.7 0 32-14.3 32-32s-14.3-32-32-32H96C43 32 0 75 0 128v256c0 53 43 96 96 96h64c17.7 0 32-14.3 32-32s-14.3-32-32-32H96c-17.7 0-32-14.3-32-32V128c0-17.7 14.3-32 32-32h64zm273 135L313 111c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3l123 123H192c-17.7 0-32 14.3-32 32s14.3 32 32 32h198.7L267.7 401.7c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0l120-120c12.5-12.5 12.5-32.8 0-45.3z"/></svg>',
            'roles' => array()
        ),
    );

    $active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'dashboard';
    
    if ( ! array_key_exists( $active_tab, $all_tabs ) ) {
        $active_tab = 'dashboard';
    }

    if ( ! ifs_terp_has_access( $all_tabs[ $active_tab ]['roles'] ) ) {
        echo '<div class="notice notice-error"><p>Access Denied: You do not possess the required privilege level to monitor this operations desk.</p></div>';
        return;
    }

    $is_print_mode = ( isset( $_GET['action'] ) && $_GET['action'] === 'print' );

    // Custom user variables for sidebar rendering
    $current_user_id = get_current_user_id();
    $current_user    = wp_get_current_user();
    
    $display_name  = ! empty( $current_user->display_name ) ? $current_user->display_name : $current_user->user_login;
    $designation   = ! empty( $current_user->roles ) ? ucfirst( reset( $current_user->roles ) ) : 'Travel Agent';
    $custom_avatar = '';

    ?>

    <div id="ifs-terp-wrapper" class="ifs-travel-erp-system <?php echo $is_print_mode ? 'ifs-terp-print' : ''; ?>">
        
        <?php if ( ! $is_print_mode ) : ?>
            <div class="ifs-terp-sidebar-container">
                
                <div class="ifs-terp-author-profile">
                    <div class="profile-avatar">
                        <?php 
                        if ( ! empty( $custom_avatar ) ) {
                            echo '<img src="' . esc_url( $custom_avatar ) . '" alt="' . esc_attr( $display_name ) . '" width="64" height="64" style="border-radius: 50%; object-fit: cover;" />';
                        } else {
                            echo get_avatar( $current_user_id, 64, '', '', array( 'class' => 'avatar-round' ) ); 
                        }
                        ?>
                    </div>
                    <div class="profile-meta">
                        <h4 class="profile-name"><?php echo esc_html( $display_name ); ?></h4>
                        <span class="profile-designation"><?php echo esc_html( str_replace('_', ' ', $designation) ); ?></span>
                    </div>
                </div>

                <ul class="ifs-terp-left-tabs">
                    <?php 
                    foreach ( $all_tabs as $slug => $config ) : 
                        if ( ! ifs_terp_has_access( $config['roles'] ) ) {
                            continue; 
                        }
                        $active_class = ( $active_tab === $slug ) ? 'active' : '';
                        
                        if ( $slug === 'logout' ) {
                            $target_url = wp_logout_url( admin_url( 'admin.php?page=ifs_travel_erp' ) );
                        } else {
                            $target_url = admin_url( 'admin.php?page=ifs_travel_erp&tab=' . $slug );
                        }
                        ?>
                        <li class="<?php echo esc_attr( 'tab-' . $slug ); ?>">
                            <a class="<?php echo esc_attr( $active_class ); ?>" href="<?php echo esc_url( $target_url ); ?>">
                                <?php echo $config['svg']; ?>
                                <span><?php echo esc_html( $config['label'] ); ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="ifs-terp-right-box">
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
                case 'b2b_agents':
                    if ( function_exists( 'ifs_terp_b2b_agents_tab' ) ) { ifs_terp_b2b_agents_tab(); }
                    break;
                case 'accounts':
                    if ( function_exists( 'ifs_terp_accounts_tab' ) ) { ifs_terp_accounts_tab(); }
                    break;
                case 'reports':
                    if ( function_exists( 'ifs_terp_reports_tab' ) ) { ifs_terp_reports_tab(); }
                    break;
                case 'settings':
                    if ( function_exists( 'ifs_terp_settings_tab' ) ) { ifs_terp_settings_tab(); }
                    break;
            }
            ?>
        </div>
    </div>
    <?php
}

/*--------------------------------------------------------------
# 9. Head CSS Layout Injection
--------------------------------------------------------------*/
add_action( 'admin_head', function() {
    $screen = get_current_screen();

    if ( $screen && $screen->id === 'toplevel_page_ifs_travel_erp' ) {
        echo '<style>
            #wpadminbar, 
            #adminmenu, #adminmenuback, #adminmenuwrap, 
            #wpfooter { display: none !important; }
            
            #wpcontent, #wpbody-content { margin-left: 0 !important; padding: 0 !important; width: 100% !important; }
            
            body.wp-admin { background: #f1f1f1; overflow-x: hidden; }

            .ifs-travel-erp-system {
                display: flex;
                position: relative;
                min-height: 100vh;
            }

            .ifs-terp-left-tabs, .ifs-terp-author-profile {
                width: 240px;
                margin: 0;
                list-style: none;
                flex-shrink: 0;
                background: #fff;
                border-right: 1px solid #e2e8f0;
            }

            .ifs-terp-author-profile {
                display: flex;
                align-items: center;
                gap: 15px;
                justify-content: center;
                padding: 20px;
                border-bottom: 1px solid #ddd;
            }
            .ifs-terp-author-profile img {
                width: 60px !important;
                height: 60px;
                border-radius: 50%;
                border: 3px solid #003376;
            }
            .ifs-terp-author-profile .profile-name {
                margin-bottom: 0;
                font-size: 14px;
                font-weight: 700;
            }
            .profile-meta span {
                font-size: 13px;
                font-weight: 500;
                text-transform: capitalize;
            }
            .ifs-terp-left-tabs li a {
                display: flex;
                align-items: center;
                padding: 10px 24px;
                color: #333;
                text-decoration: none;
                font-weight: 600;
                font-size: 15px;
                transition: all 0.2s ease;
            }

            .ifs-terp-left-tabs li a svg {
                width: 18px;
                height: 18px;
                margin-right: 14px;
                fill: #64748b;
                transition: all 0.2s ease;
                flex-shrink: 0;
            }

            .ifs-terp-left-tabs li a:hover {
                background: #f8fafc;
                color: #1e293b;
            }

            .ifs-terp-left-tabs li a:hover svg {
                fill: #003376;
            }

            .ifs-terp-left-tabs li a.active {
                background: #003376;
                color: #fff;
                font-weight: 600;
            }

            .ifs-terp-left-tabs li a.active svg {
                fill: #fff;
            }

            .ifs-terp-right-box {
                flex-grow: 1;
                background: #f8fafc;
                padding: 30px;
            }

            .ifs-terp-print .ifs-terp-left-tabs {
                display: none !important;
            }
        </style>';
    }
});

/**
 * 1. ROOT HOMEPAGE TO WP-ADMIN REDIRECT
 */
function ifs_terp_root_to_admin_redirect() {
    if ( is_admin() || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
        return;
    }

    $home_path   = ifs_terp_upper_safe_path_parse( home_url() );
    $request_uri = ifs_terp_upper_safe_path_parse( $_SERVER['REQUEST_URI'] );

    if ( $request_uri === $home_path ) {
        wp_safe_redirect( admin_url(), 302 );
        exit;
    }
}
add_action( 'init', 'ifs_terp_root_to_admin_redirect', 5 );

function ifs_terp_upper_safe_path_parse( $url ) {
    $path = parse_url( $url, PHP_URL_PATH );
    return trim( $path, '/' );
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
 * 3. LOGOUT ROUTING OVERRIDE
 */
function ifs_terp_custom_logout_routing() {
    wp_safe_redirect( home_url() );
    exit;
}
add_action( 'wp_logout', 'ifs_terp_custom_logout_routing' );


/**
 * 4. CUSTOM WHITE-LABEL STYLES & DUMMY LOGO MASKING
 */
function ifs_terp_custom_login_styles() {
    $custom_logo_url = plugin_dir_url( __FILE__ ) . 'assets/img/logo.png';
    ?>
    <style type="text/css">
        #login h1 a, .login h1 a {
            background-image: url('<?php echo $custom_logo_url; ?>') !important;
            height: 80px !important;
            width: 100% !important;
            background-size: contain !important;
            background-position: center !important;
            margin-bottom: 25px !important;
            border: 1px solid #ddd;
            background-color: #fff;
        }
        .wp-core-ui .button-group.button-large .button, .wp-core-ui .button.button-large {
            background-color: #003376 !important;
        }

        body.login {
            background: #f0f4f8 !important;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
        }

        #login { padding: 6% 0 0 !important; width: 360px !important; }

        .login form {
            background: #ffffff !important;
            border: 1px solid #e1e8ed !important;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05) !important;
            border-radius: 8px !important;
            padding: 30px !important;
        }

        .login label { color: #4a5568 !important; font-weight: 500 !important; }

        .login input[type="text"], .login input[type="password"] {
            border: 1px solid #cbd5e1 !important;
            border-radius: 6px !important;
            padding: 8px 12px !important;
            background: #f8fafc !important;
            box-shadow: none !important;
        }

        .wp-core-ui .button-primary {
            background: #003376 !important;
            border: none !important;
            border-radius: 6px !important;
            box-shadow: none !important;
            font-weight: 600 !important;
            height: 40px !important;
            width: 100% !important;
            margin-top: 15px !important;
        }

        .wp-core-ui .button-primary:hover { background: #002255 !important; }

        .login #backtoblog, .login #nav, .privacy-policy-page-link {
            display: none !important;
        }
        
        .ifs-terp-captcha-container { margin: 15px 0; }
        .ifs-terp-captcha-label { display: block; margin-bottom: 5px; font-weight: bold; }
    </style>
    <?php
}
add_action( 'login_enqueue_scripts', 'ifs_terp_custom_login_styles' );

function ifs_terp_login_logo_url() { return home_url(); }
add_filter( 'login_headerurl', 'ifs_terp_login_logo_url' );

function ifs_terp_login_logo_title() { return get_bloginfo( 'name' ); }
add_filter( 'login_headertext', 'ifs_terp_login_logo_title' );


/**
 * 5. MATHEMATICAL CAPTCHA ENGINE
 */
function ifs_terp_display_login_captcha() {
    $num1 = rand(1, 9);
    $num2 = rand(1, 9);
    $captcha_token = md5( uniqid( rand(), true ) );
    set_transient( 'iterp_captcha_' . $captcha_token, ($num1 + $num2), 300 );
    ?>
    <div class="ifs-terp-captcha-container">
        <label class="ifs-terp-captcha-label" for="iterp_captcha_answer">Security Verification</label>
        <p style="margin: 0 0 8px 0; color: #718096; font-size: 13px;">
            Please solve: <strong><?php echo $num1; ?> + <?php echo $num2; ?> = ?</strong>
        </p>
        <input type="text" name="iterp_captcha_answer" id="iterp_captcha_answer" class="input" value="" size="4" autocomplete="off" required />
        <input type="hidden" name="iterp_captcha_token" value="<?php echo esc_attr( $captcha_token ); ?>" />
    </div>
    <?php
}
add_action( 'login_form', 'ifs_terp_display_login_captcha' );

function ifs_terp_validate_login_captcha( $user, $username, $password ) {
    if ( is_wp_error( $user ) ) { return $user; }

    $user_answer = isset( $_POST['iterp_captcha_answer'] ) ? sanitize_text_field( $_POST['iterp_captcha_answer'] ) : '';
    $token       = isset( $_POST['iterp_captcha_token'] ) ? sanitize_text_field( $_POST['iterp_captcha_token'] ) : '';
    
    $correct_answer = get_transient( 'iterp_captcha_' . $token );
    delete_transient( 'iterp_captcha_' . $token );

    if ( $correct_answer === false || intval( $user_answer ) !== intval( $correct_answer ) ) {
        return new WP_Error( 'authentication_failed', __( '<strong>ERROR</strong>: Incorrect security verification answer.' ) );
    }
    return $user;
}
add_filter( 'authenticate', 'ifs_terp_validate_login_captcha', 25, 3 );