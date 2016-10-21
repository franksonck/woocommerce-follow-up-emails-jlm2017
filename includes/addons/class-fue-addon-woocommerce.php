<?php

/**
 * Class FUE_Addon_Woocommerce
 */
class FUE_Addon_Woocommerce {

    /**
     * @var Follow_Up_Emails
     */
    private $fue;

    /**
     * @var Woocommerce
     */
    public $wc;

    /**
     * @var FUE_Addon_Woocommerce_Cart
     */
    public $wc_cart;

    /**
     * @var FUE_Addon_Woocommerce_Admin
     */
    public $wc_admin;

    /**
     * @var FUE_Addon_Woocommerce_Scheduler
     */
    public $wc_scheduler;

    /**
     * @var FUE_Addon_Woocommerce_Mailer
     */
    public $wc_mailer;

    /**
     * @var FUE_Addon_Woocommerce_Reports
     */
    public $wc_reports;

    /**
     * @var FUE_Sending_Email_Variables
     */
    private $variables;

    /**
     * @var FUE_Sending_Mailer
     */
    private $mailer;

    /**
     * Class constructor. Inject dependencies
     *
     * @param Follow_Up_Emails $fue
     * @param FUE_Sending_Mailer $mailer
     * @param FUE_Sending_Email_Variables $variables
     */
    public function __construct( Follow_Up_Emails $fue, FUE_Sending_Mailer $mailer, FUE_Sending_Email_Variables $variables ) {
        $this->fue          = $fue;
        $this->mailer       = $mailer;
        $this->variables    = $variables;

        $this->wc_cart      = new FUE_Addon_Woocommerce_Cart( $this );
        $this->wc_admin     = new FUE_Addon_Woocommerce_Admin( $this );
        $this->wc_scheduler = new FUE_Addon_Woocommerce_Scheduler( $this );
        $this->wc_mailer    = new FUE_Addon_Woocommerce_Mailer( $this );
        $this->wc_reports   = new FUE_Addon_Woocommerce_Reports( $this ) ;
        $this->wc_conditions= new FUE_Addon_Woocommerce_Conditions( $this );

        $this->register_hooks();

    }

    /**
     * Register action hooks and filters
     */
    public function register_hooks() {
        add_filter( 'body_class', array( $this, 'output_body_class' ) );

        // use WooCommerce as the default email template
        add_action( 'fue_email_loaded', array($this, 'set_email_default_template') );

        // load woocommerce addons
        add_action( 'fue_addons_loaded', array($this, 'load_addons') );

        add_filter( 'fue_email_types', array($this, 'register_email_types') );
        add_filter( 'fue_email_query', array($this, 'email_address_search'), 10, 3 );

        add_filter( 'fue_signup_email_properties', array($this, 'register_signup_conditions') );

        // trigger conditions
        add_filter( 'fue_trigger_conditions', array($this, 'register_conditions'), 10, 2 );

        add_action('delete_post', 'FUE_Addon_WooCommerce_Scheduler::order_deleted' );

        // My Account Email Subscriptions
        // shortcode for the My Subscriptions page
        add_shortcode('woocommerce_followup_optout', array($this, 'my_email_subscriptions'));
        add_shortcode('fue_followup_subscriptions', array($this, 'my_email_subscriptions'));
        add_action('template_redirect', array($this, 'process_unsubscribe_request'));

        add_filter( 'woocommerce_json_search_found_products', array($this, 'display_product_variable_attributes') );

        // update customer data on order status update
        add_action( 'woocommerce_order_status_changed', array($this, 'update_customer_data'), 30, 3 );

        // checkout subscription
        if ( get_option( 'fue_enable_checkout_subscription', 1 ) == 1 ) {
            add_action( 'woocommerce_after_order_notes', array($this, 'display_email_subscription_checkbox') );
            add_action( 'woocommerce_checkout_order_processed', array($this, 'maybe_subscribe_customer'), 10, 2 );
        }

        // my account subscription
        if ( get_option( 'fue_enable_account_subscription', 0 ) == 1 ) {
            add_action( 'woocommerce_before_my_account', array( $this, 'display_subscription_preferences_block' ) );
        }

    }

    /**
     * As of FuE 4.4, the Quantity-based (Reminders) email type will not get
     * registered if there are no existing emails under it.
     */
    public function exclude_quantity_email_type() {
        $emails = fue_get_emails( 'reminder' );

        if ( empty( $emails ) ) {
            return true;
        }

        return false;
    }

    /**
     * Add a notice to display on WC's frontend
     *
     * @since 4.1
     * @param string $message
     */
    public static function add_message( $message ) {
        if ( function_exists('wc_add_notice') ) {
            wc_add_notice( $message );
        } else {
            WC()->add_message( $message );
        }
    }

    /**
     * Add woocommerce and woocommerce-page CSS classes to the body of the unsubscribe page
     *
     * @param array $classes
     * @return array
     */
    public function output_body_class( $classes ) {
        global $wp;

        if ( empty( $wp->query_vars ) || !is_array( $wp->query_vars ) ) {
            return $classes;
        }

        if (
            array_key_exists( 'unsubscribe', $wp->query_vars ) ||
            array_key_exists( 'my-account/email-subscriptions', $wp->query_vars )
        ) {
            $classes[] = 'woocommerce';
            $classes[] = 'woocommerce-page';
        }

        return $classes;
    }

    /**
     * If the email has no template assigned to it, set it to use
     * the WooCommerce email template
     *
     * @param FUE_Email $email
     */
    public function set_email_default_template( $email ) {
        if ( empty( $email->template ) ) {
            $email->template = 'WooCommerce';
        }
    }

    /**
     * Load additional functionality from 3rd-party woocommerce extensions
     */
    public function load_addons() {
        require_once FUE_INC_DIR .'/class-fue-coupons.php';
        require_once FUE_INC_DIR .'/addons/class-fue-addon-subscriptions.php';
        require_once FUE_INC_DIR .'/addons/class-fue-addon-warranty.php';
        require_once FUE_INC_DIR .'/addons/class-fue-addon-points-and-rewards.php';
        require_once FUE_INC_DIR .'/addons/class-fue-addon-wootickets.php';
        require_once FUE_INC_DIR .'/addons/class-fue-addon-event-tickets.php';
        require_once FUE_INC_DIR .'/addons/class-fue-addon-bookings.php';
    }

    /**
     * Checks if WooCommerce is installed and activated by looking at the 'active_plugins' array
     * @return bool True if WooCommerce is installed
     */
    public static function is_installed() {
        return function_exists('WC');
    }

    public static function init_wc_session() {
        if ( !WC()->session ) {
            include_once( WC()->plugin_path() .'/includes/abstracts/abstract-wc-session.php' );
            include_once( WC()->plugin_path() .'/includes/class-wc-session-handler.php' );

            WC()->session = new WC_Session_Handler();
        }
    }

    /**
     * Register additional email types
     *
     * @param array $types
     * @return array
     */
    public function register_email_types( $types ) {
        $status_triggers    = array();

        foreach ( $this->get_order_statuses() as $i => $status ) {
            $status_triggers[ $status ] = sprintf( __('after Order Status: %s', 'follow_up_emails'), $status );
        }

        $storewide_triggers = apply_filters( 'fue_wc_storewide_triggers', array_unique( array_merge(
            array(
                'first_purchase'                => __('after first purchase', 'follow_up_emails'),
                'cart'                          => __('after added to cart', 'follow_up_emails'),
                'product_purchase_above_one'    => __('after customer purchased more than one time', 'follow_up_emails'),
                'downloadable_file_added'       => __('after downloadable file added', 'follow_up_emails'),
                'downloaded'                    => __('after file downloaded', 'follow_up_emails'),
                'not_downloaded'                => __('file not yet downloaded', 'follow_up_emails'),
                'coupon'                        => __('after Coupon used', 'follow_up_emails')
            ),
            $status_triggers
        ) ) );

        $customer_triggers = apply_filters( 'fue_wc_customer_triggers', array(
            'after_last_purchase'   => __('after last purchase', 'follow_up_emails'),
            'order_total_above'     => __('after order total is above', 'follow_up_emails'),
            'order_total_below'     => __('after order total is below', 'follow_up_emails'),
            'purchase_above_one'    => __('after customer purchased more than one time', 'follow_up_emails'),
            'total_orders'          => __('after total orders by customer', 'follow_up_emails'),
            'total_purchases'       => __('after total purchase amount by customer', 'follow_up_emails')
        ) );

        if ( WC_FUE_Compatibility::is_wc_version_gte_2_2() ) {
            // add support for refunds
            $storewide_triggers['refund_manual']      = __('after refunded manually', 'follow_up_emails');
            $storewide_triggers['refund_successful']  = __('after refunded successfully', 'follow_up_emails');
            $storewide_triggers['refund_failed']      = __('after refund failed', 'follow_up_emails');
        }

        $props = array(
            'priority'              => 9,
            'label'                 => __('Storewide Emails', 'follow_up_emails'),
            'singular_label'        => __('Storewide Email', 'follow_up_emails'),
            'triggers'              => $storewide_triggers,
            'supports'              => array('conditions'),
            'durations'             => Follow_Up_Emails::$durations,
            'long_description'      => __('Create your emails for all, or specific, products or categories as well as abandoned carts.', 'follow_up_emails'),
            'short_description'     => __('Create your emails for all, or specific, products or categories as well as abandoned carts.', 'follow_up_emails'),
            'list_template'         => FUE_TEMPLATES_DIR .'/email-list/storewide-list.php'
        );
        $types[] = new FUE_Email_Type( 'storewide', $props );

        $props = array(
            'label'                 => __('Customer Emails', 'follow_up_emails'),
            'singular_label'        => __('Customer Email', 'follow_up_emails'),
            'triggers'              => $customer_triggers,
            'durations'             => Follow_Up_Emails::$durations,
            'long_description'      => __('Engage your customers by following up with emails specifically related to customer lifetime value metrics.', 'follow_up_emails'),
            'short_description'     => __('Engage your customers by following up with emails specifically related to customer lifetime value metrics.', 'follow_up_emails'),
            'list_template'         => FUE_TEMPLATES_DIR .'/email-list/storewide-list.php'
        );
        $types[] = new FUE_Email_Type( 'customer', $props );

        if ( !$this->exclude_quantity_email_type() ) {
            $props   = array(
                'label'             => __( 'Quantity Based Emails', 'wc_followup_emails' ),
                'singular_label'    => __( 'Quantity Based Email', 'wc_followup_emails' ),
                'triggers'          => array(
                    'processing' => __( 'after Order Status: processing', 'follow_up_emails' ),
                    'completed'  => __( 'after Order Status: completed', 'follow_up_emails' )
                ),
                'durations'         => Follow_Up_Emails::$durations,
                'long_description'  => __( 'Quantity emails are deprecated. Please switch to Storewide.', 'follow_up_emails' ),
                'short_description' => __( 'Quantity emails are deprecated. Please switch to Storewide.', 'follow_up_emails' ),
                'list_template'     => FUE_TEMPLATES_DIR . '/email-list/storewide-list.php'
            );
            $types[] = new FUE_Email_Type( 'reminder', $props );
        }

        return $types;

    }

    /**
     * Make signup emails support the 'conditions' feature
     * @param array $props
     * @return array
     */
    public function register_signup_conditions( $props ) {
        if ( !isset( $props['supports'] ) ) {
            $props['supports'] = array();
        }

        if ( !in_array( 'conditions', $props['supports'] ) ) {
            $props['supports'][] = 'conditions';
        }

        return $props;
    }

    /**
     * Register order and customer-related conditions
     *
     * @param array $conditions
     * @param FUE_Email $email
     * @return array
     */
    public function register_conditions( $conditions, $email ) {
        if ( in_array( $email->type, array( 'storewide', 'customer' ) ) ) {
            $conditions = $conditions + $this->wc_conditions->get_store_conditions();
        } elseif ( $email->type == 'signup' ) {
            $conditions = $conditions + $this->wc_conditions->get_signup_conditions();
        }

        return $conditions;
    }

    /**
     * Backwards-compatible way of getting the available order statuses
     *
     * @return array
     */
    public function get_order_statuses() {
        $order_statuses = array();

        if ( WC_FUE_Compatibility::is_wc_version_gte_2_2() ) {
            $statuses = wc_get_order_statuses();

            foreach ( $statuses as $key => $status ) {
                $order_statuses[] = str_replace( 'wc-', '', $key );
            }

        } else {
            $terms = get_terms( 'shop_order_status', array('hide_empty' => 0, 'orderby' => 'id') );

            if (! isset($terms->errors) ) {
                foreach ( $terms as $status ) {
                    $order_statuses[] = $status->slug;
                }
            }

        }

        return $order_statuses;
    }

    /**
     * Hook into FUE_AJAX::search_for_email() to add possible matches against billing emails
     *
     * Return format:
     *  key     - [id]|[email]|[name]
     *  value   - [name] <[email]>
     *
     * @param array     $results    The result from FUE_AJAX::search_for_email()
     * @param string    $term       The term being searched for
     * @param array     $all_emails All matched emails
     *
     * @return mixed
     */
    public function email_address_search( $results, $term, $all_emails = array() ) {
        $wpdb = Follow_Up_Emails::instance()->wpdb;

        // Guest customers (billing email)
        $billing_results = $wpdb->get_results("SELECT `post_id`, `meta_value` FROM {$wpdb->prefix}postmeta WHERE meta_key = '_billing_email' AND meta_value LIKE '{$term}%'");

        if ( $billing_results ) {
            foreach ( $billing_results as $result ) {
                if ( in_array($result->meta_value, $all_emails) ) continue;

                $all_emails[] = $result->meta_value;

                // get the name
                $first_name = get_post_meta( $result->post_id, '_billing_first_name', true );
                $last_name = get_post_meta( $result->post_id, '_billing_last_name', true );

                $key = '0|'. $result->meta_value .'|'. $first_name .' '. $last_name;

                $results[$key] = $first_name .' '. $last_name .' &lt;'. $result->meta_value .'&gt;';
            }
        }

        return $results;
    }

    /**
     * Return TRUE if the given product is a 'product variation' and has other products under it
     *
     * @param int $product_id
     * @return bool
     */
    public static function product_has_children( $product_id ) {
        global $wpdb;

        if ( 0 == $wpdb->get_var( $wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}posts WHERE post_parent = %d AND post_type = 'product_variation'", $product_id) ) ) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Get the downloadable files for the given product
     * @param int $product_id
     * @return array
     */
    public static function get_product_downloadables( $product_id ) {
        $product = WC_FUE_Compatibility::wc_get_product( $product_id );
        $downloadables = ($product) ? $product->get_files() : array();
        $files = array();

        if ( !empty( $downloadables ) ) {
            foreach ( $downloadables as $key => $file ) {
                $files[ $key ] = $file['name'] .' ('. basename($file['file']) .')';
            }
        }

        return $files;
    }

    /**
     * Look for a Subscription Variation product and modify the displayed
     * title to include all of its attributes in a CSV format
     *
     * @param array $products
     * @return mixed
     */
    public function display_product_variable_attributes( $products ) {
        foreach ( $products as $id => $title ) {
            $product = WC_FUE_Compatibility::wc_get_product( $id );

            if ( is_a($product, 'WC_Product_Subscription_Variation') ) {
                $identifier = '#' . $id;
                $attributes = $product->get_variation_attributes();
                $extra_data = ' &ndash; ' . implode( ', ', $attributes ) . ' &ndash; ' . woocommerce_price( $product->get_price() );

                $products[$id] = sprintf( __( '%s &ndash; %s%s', 'woocommerce' ), $identifier, $product->get_title(), $extra_data );
            }

        }

        return $products;
    }

    /**
     * Update the customer data on order status update. This only affects orders that
     * have been previously recorded
     *
     * @param int $order_id
     * @param string $old_status
     * @param string $new_status
     */
    public function update_customer_data( $order_id, $old_status, $new_status ) {
        $wpdb = Follow_Up_Emails::instance()->wpdb;
        $recorded = get_post_meta( $order_id, '_fue_recorded', true );

        if ( $recorded != 1 ) {
            return;
        }

        $customer   = fue_get_customer_from_order( $order_id );
        $order      = WC_FUE_Compatibility::wc_get_order( $order_id );

        if ( !$customer ) {
            return;
        }

        // if the order's status is failed, cancelled or refunded - deduct the order total from the customer data
        if ( in_array( $new_status, array( 'failed', 'cancelled', 'refunded' ) ) ) {
            $customer->total_orders -= 1;
            $customer->total_purchase_price -= $order->get_total();

            $wpdb->query( $wpdb->prepare(
                "DELETE FROM {$wpdb->prefix}followup_customer_orders WHERE order_id = %d",
                $order_id
            ) );

            $wpdb->query( $wpdb->prepare(
                "DELETE FROM {$wpdb->prefix}followup_order_categories WHERE order_id = %d",
                $order_id
            ) );

            $wpdb->query( $wpdb->prepare(
                "DELETE FROM {$wpdb->prefix}followup_order_items WHERE order_id = %d",
                $order_id
            ) );

            $wpdb->update(
                $wpdb->prefix .'followup_customers',
                array(
                    'total_purchase_price' => $customer->total_purchase_price,
                    'total_orders' => $customer->total_orders
                ),
                array(
                    'id' => $customer->id
                )
            );

            delete_post_meta( $order_id, '_fue_recorded' );

        } elseif (
            in_array( $old_status, array( 'failed', 'cancelled', 'refunded', 'pending' ) ) &&
            in_array( $new_status, array( 'on-hold', 'processing', 'completed' ) )
        ) {
            self::record_order( $order, true );
        }

    }

    /**
     * Display a checkbox in the checkout form to allow customers to subscribe to the email newsletter
     */
    public function display_email_subscription_checkbox() {
        fue_get_template( '/checkout/email-subscribe.php', array(), 'follow-up-emails', FUE_TEMPLATES_DIR );
    }

    /**
     * If the customer checked the checkbox displayed by self::display_email_subscription_checkbox(),
     * then add his billing email to the subscribers table
     *
     * @param int $order_id
     * @param array $posted
     */
    public function maybe_subscribe_customer( $order_id, $posted ) {
        if ( !empty( $_POST['fue_subscribe'] ) && $_POST['fue_subscribe'] == 'yes' ) {
            $list = get_option( 'fue_checkout_subscription_list', '' );
            fue_add_subscriber( $posted['billing_email'], $list );

            do_action( 'fue_checkout_added_subscriber', $posted['billing_email'], $list );
        }
    }

    /**
     * Display form in the account page to let users configure their email subscription lists
     */
    public function display_subscription_preferences_block() {
        $lists = Follow_Up_Emails::instance()->newsletter->get_public_lists();
        fue_get_template( '/myaccount/account-subscriptions.php', array('lists' => $lists), 'follow-up-emails', FUE_TEMPLATES_DIR );
    }

    /**
     * Get the correct name of a product
     * @param int|WC_Product $product
     * @return string
     */
    public static function get_product_name( $product ) {
        if ( is_numeric( $product ) ) {
            $product = WC_FUE_Compatibility::wc_get_product( $product );
        }

        if ( $product )
            return $product->get_title();

        return '';

    }

    /**
     * Extract data from an order and mark it as 'recorded' so we don't make
     * the mistake of processing it again and giving us duplicate and unreliable data
     *
     * @param WC_Order $order
     * @param bool $force Set to TRUE to disregard the _fue_recorded flag
     */
    public static function record_order( $order, $force = false ) {
        $wpdb = Follow_Up_Emails::instance()->wpdb;

        $order_categories   = array();
        $wc2                = WC_FUE_Compatibility::is_wc_version_gt('2.0');
        $order_id           = $order->id;
        $user_id            = WC_FUE_Compatibility::get_order_user_id( $order );

        $recorded = get_post_meta( $order_id, '_fue_recorded', true );

        if ( $recorded == 1 && !$force ) {
            return;
        }

        $order_total        = 0;
        $order_increment    = 0;

        if ( $order && in_array( $order->get_status(), array( 'processing', 'completed', 'on-hold' ) ) ) {
            $order_total = $order->get_total();
            $order_increment = 1;
        }

        if ( $user_id > 0 ) {
            $user       = new WP_User( $user_id );
            $email      = $user->user_email;

            $customer   = $wpdb->get_row( $wpdb->prepare("SELECT * FROM {$wpdb->prefix}followup_customers WHERE user_id = %d", $user_id) );
        } else {
            $user_id    = 0;
            $email      = $order->billing_email;
            $customer   = $wpdb->get_row( $wpdb->prepare("SELECT * FROM {$wpdb->prefix}followup_customers WHERE email_address = %s", $email) );
        }

        if (! $customer ) {
            $insert = array(
                'user_id'               => $user_id,
                'email_address'         => $email,
                'total_purchase_price'  => $order_total,
                'total_orders'          => 1
            );

            $wpdb->insert( $wpdb->prefix .'followup_customers', $insert );
            $customer_id = $wpdb->insert_id;
            $customer = $wpdb->get_row( $wpdb->prepare("SELECT * FROM {$wpdb->prefix}followup_customers WHERE id = %d", $customer_id) );
        }

        // record order
        $order_recorded = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}followup_customer_orders WHERE order_id = {$order_id}");

        if ( 0 == $order_recorded ) {
            $wpdb->insert( $wpdb->prefix .'followup_customer_orders', array('followup_customer_id' => $customer->id, 'order_id' => $order_id, 'price' => $order->order_total) );
        }

        // update order totals
        $total_orders = $wpdb->get_var(
            "SELECT COUNT(*)
            FROM {$wpdb->prefix}followup_customer_orders
            WHERE followup_customer_id = {$customer->id}"
        );
        $total_purchases = $customer->total_purchase_price + $order_total;

        $wpdb->update(
            $wpdb->prefix .'followup_customers',
            array('total_purchase_price' => $total_purchases, 'total_orders' => $total_orders),
            array('id' => $customer->id)
        );

        // if items and categories have been previously recorded,
        // there's no need to record them again
        if ( $recorded ) {
            return;
        }

        if ( $wc2 ) {
            $order_item_ids = $wpdb->get_results("SELECT order_item_id FROM {$wpdb->prefix}woocommerce_order_items WHERE order_id = {$order_id}");

            foreach ( $order_item_ids as $order_item ) {
                $product_id     = $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}woocommerce_order_itemmeta WHERE order_item_id = {$order_item->order_item_id} AND meta_key = '_product_id'");
                $variation_id   = $wpdb->get_var("SELECT meta_value FROM {$wpdb->prefix}woocommerce_order_itemmeta WHERE order_item_id = {$order_item->order_item_id} AND meta_key = '_variation_id'");

                if ( is_null( $variation_id ) ) {
                    $variation_id = 0;
                }

                if ( $product_id ) {
                    $insert = array(
                        'order_id'      => $order_id,
                        'product_id'    => $product_id,
                        'variation_id'  => $variation_id
                    );
                    $wpdb->insert( $wpdb->prefix .'followup_order_items', $insert );

                    // get the categories
                    $cat_ids = wp_get_post_terms( $product_id, 'product_cat', array('fields' => 'ids') );

                    if ( $cat_ids ) {
                        foreach ( $cat_ids as $cat_id ) {
                            $order_categories[] = $cat_id;
                        }
                    }
                }
            }
        } else {
            $order_items = get_post_meta( $order_id, '_order_items', true );

            foreach ( $order_items as $item ) {
                $insert = array(
                    'order_id'      => $order_id,
                    'product_id'    => $item['id']
                );
                $wpdb->insert( $wpdb->prefix .'followup_order_items', $insert );

                // get the categories
                $cat_ids = wp_get_post_terms( $item['id'], 'product_cat', array('fields' => 'ids') );

                if ( $cat_ids ) {
                    foreach ( $cat_ids as $cat_id ) {
                        $order_categories[] = $cat_id;
                    }
                }
            }
        }

        $order_categories = array_unique($order_categories);

        foreach ( $order_categories as $category_id ) {
            $insert = array(
                'order_id'      => $order_id,
                'category_id'   => $category_id
            );
            $wpdb->insert( $wpdb->prefix .'followup_order_categories', $insert );
        }

        update_post_meta( $order_id, '_fue_recorded', true );
    }

    /**
     * Shortcode content for managing a customer's email subscriptions
     * @return string
     */
    public function my_email_subscriptions() {
        global $wpdb, $woocommerce;

        $user   = wp_get_current_user();
        $emails = $wpdb->get_results( $wpdb->prepare("SELECT COUNT(*) AS num, user_email, order_id FROM {$wpdb->prefix}followup_email_orders WHERE (user_id = %d OR user_email = %s) AND is_sent = 0 AND order_id > 0 GROUP BY order_id", $user->ID, $user->user_email) );

        if ( $user->ID == 0 )
            return;

        $args = array(
            'user'          => $user,
            'emails'        => $emails,
            'unsubscribed'  => ( isset($_GET['fue_order_unsubscribed']) ) ? true : false
        );

        ob_start();

        wc_get_template( 'my-account-emails.php', $args, 'follow-up-emails', trailingslashit( FUE_TEMPLATES_DIR ) );
        return ob_get_clean();
    }

    /**
     * Unsubscribe a customer from an email
     */
    public function process_unsubscribe_request() {
        global $wpdb;

        if (isset($_GET['fue_action']) && $_GET['fue_action'] == 'order_unsubscribe') {

            if (! wp_verify_nonce( $_GET['_wpnonce'], 'fue_unsubscribe' ) ) {
                die( 'Request error. Please try again.' );
            }

            $order_id   = $_GET['order_id'];
            $email      = str_replace( ' ', '+', $_GET['email'] );
            $back       = (isset( $_GET['ref'] ) ) ? $_GET['ref'] : fue_get_email_subscriptions_url();

            $wpdb->query( $wpdb->prepare("DELETE FROM {$wpdb->prefix}followup_email_orders WHERE user_email = %s AND order_id = %d AND is_sent = 0", $email, $order_id) );

            wp_redirect( add_query_arg( 'fue_order_unsubscribed', 1, $back ) );
            exit;
        }
    }

    /**
     * Get the orders that match the conditions of the given $email
     * @param FUE_Email $email
     * @return array
     */
    public function get_orders_for_email( $email ) {

        $orders = array();

        if ( $email->type == 'storewide' ) {
            $orders = $this->get_orders_for_storewide_email( $email );
        } elseif ( $email->type == 'customer' ) {
            $orders = $this->get_orders_for_customer_email( $email );
        }

        $orders = apply_filters( 'fue_wc_get_orders_for_email', $orders, $email );

        if ( !empty( $orders ) ) {
            // save the data for 10 minutes
            set_transient( 'fue_orders_for_email_'. $email->id, $orders, 1800 );
        }

        return $orders;
    }

    /**
     * Return only the number of orders that match the conditions of $email
     * @param FUE_Email $email
     *
     * @return int
     */
    public function count_orders_for_email( $email ) {
        return count( $this->get_orders_for_email( $email ) );
    }

    /**
     * Import orders that match the given $email
     * @param FUE_Email $email
     * @param int       $limit The maximum number of orders to import per run
     * @return array
     */
    public function import_orders_for_email( $email, $limit = 100 ) {
        $orders     = get_transient( 'fue_orders_for_email_'. $email->id );
        //$orders     = array_slice( $this->get_orders_for_email( $email ), 0, $limit );
        $imported   = array();

        $processed = 0;
        foreach ( $orders as $idx => $order_id ) {
            $processed++;

            // break out of the loop if the $limit has been hit
            if ( $processed > $limit ) {
                break;
            }

            // remove from $orders so this doesn't get processed again in the next run
            unset( $orders[ $idx ] );

            if ( !$order_id ) {
                $imported[] = array(
                    'id'        => $order_id,
                    'status'    => 'failed',
                    'reason'    => sprintf( __('Importing failed. Invalid order (#%d)', 'follow_up_emails'), $order_id )
                );
                continue;
            }

            $order      = WC_FUE_Compatibility::wc_get_order( $order_id );
            $start_date = null;

            if ( $email->trigger == 'first_purchase' ) {
                $start_date = $order->post->post_date;
            }

            $insert = apply_filters( 'fue_wc_import_insert', array(
                'send_on'       => $email->get_send_timestamp( $start_date ),
                'email_id'      => $email->id,
                'order_id'      => $order_id,
                'user_id'       => $order->customer_user,
                'user_email'    => $order->billing_email
            ), $email );

            if ( $insert ) {
                $item_id = FUE_Sending_Scheduler::queue_email( $insert, $email );

                if ( is_wp_error( $item_id ) ) {
                    $imported[] = array(
                        'id'        => $order_id,
                        'status'    => 'failed',
                        'reason'    => sprintf( __('Importing failed. %s', 'follow_up_emails'), $item_id->get_error_message() )
                    );
                    continue;
                }

                $_order = WC_FUE_Compatibility::wc_get_order( $order_id );

                $email_trigger  = apply_filters( 'fue_interval_str', $email->get_trigger_string(), $email );
                $send_date      = date( get_option('date_format') .' '. get_option('time_format'), $email->get_send_timestamp() );

                if ( $_order ) {
                    $note = sprintf(
                        __('Email queued: %s scheduled on %s<br/>Trigger: %s', 'follow_up_emails'),
                        $email->name,
                        $send_date,
                        $email_trigger
                    );

                    $_order->add_order_note( $note );

                    $imported[] = array(
                        'id'        => $order_id,
                        'status'    => 'success'
                    );
                } else {
                    $imported[] = array(
                        'id'        => $order_id,
                        'status'    => 'failed',
                        'reason'    => sprintf( __('Importing failed. Could not load order (#%d)', 'follow_up_emails'), $order_id )
                    );
                }
            }

        }

        if ( is_array( $orders ) && count( $orders ) > 0 ) {
            // store the new $orders array
            set_transient( 'fue_orders_for_email_'. $email->id, $orders, 600 );
        } else {
            // completed. delete the transient
            delete_transient( 'fue_orders_for_email_'. $email->id );
            $orders = array();
        }

        return array(
            'imported'          => $imported,
            'remaining_orders'  => count( $orders ),
            'status'            => ( count( $orders ) > 0 ) ? 'running' : 'completed'
        );
    }

    /**
     * Get the orders matching the conditions of the storewide email
     * @param FUE_Email $email
     * @return array
     */
    private function get_orders_for_storewide_email( $email ) {
        $wpdb       = Follow_Up_Emails::instance()->wpdb;
        $trigger    = $email->trigger;
        $orders     = array();

        if ( $trigger == 'cart' ) {
            // cart is an unsupported trigger
            return $orders;
        }

        if ( in_array( $trigger, $this->get_order_statuses() ) ) {
            // count the number of orders matching the email's order status trigger
            // and exclude those Order IDs that are in the email queue, sent or unsent
            if ( WC_FUE_Compatibility::is_wc_version_gte_2_2() ) {
                $status = 'wc-'. $email->trigger;
                $orders = $wpdb->get_col( $wpdb->prepare(
                    "SELECT ID
                        FROM {$wpdb->posts} p
                        WHERE p.post_status = %s
                        AND (
                            SELECT COUNT(id)
                            FROM {$wpdb->prefix}followup_email_orders
                            WHERE order_id = p.ID
                            AND email_id = %d
                        ) = 0",
                    $status,
                    $email->id
                ) );
            } else {
                $excluded_ids = $wpdb->get_col( $wpdb->prepare(
                    "SELECT DISTINCT order_id
                    FROM {$wpdb->prefix}followup_email_orders
                    WHERE order_id > 0
                    AND email_id = %d",
                    $email->id
                ) );
                $orders = get_posts( array(
                    'post_status'   => 'publish',
                    'tax_query'     => array(
                        array(
                            'taxonomy' => 'shop_order_status',
                            'field'    => 'slug',
                            'terms'    => $email->trigger
                        )
                    ),
                    'post__not_in'  => $excluded_ids,
                    'fields'        => 'ids',
                    'posts_per_page'=> -1
                ) );
            }

            // filter out the orders that don't match the email's product or category filter
            if ( $email->product_id ) {
                $order_ids = implode(',', $orders);
                $orders = $wpdb->get_col( $wpdb->prepare(
                    "SELECT order_id
                    FROM {$wpdb->prefix}followup_order_items
                WHERE order_id IN ($order_ids)
                    AND (
                        product_id = %d
                        OR
                        variation_id = %d
                    )", $email->product_id, $email->product_id
                ) );
            }

            if ( $email->category_id ) {
                $order_ids = implode(',', $orders);
                $orders = $wpdb->get_col( $wpdb->prepare(
                    "SELECT order_id
                    FROM {$wpdb->prefix}followup_order_categories
                    WHERE order_id IN ($order_ids)
                    AND category_id = %d", $email->category_id
                ) );
            }

            // remove orders with products/categories that are in the email exclusions list
            if ( !empty( $email->meta['excluded_categories'] ) ) {
                $excludes = $email->meta['excluded_categories'];

                if ( !is_array( $excludes ) ) {
                    $excludes = array();
                }

                foreach ( $orders as $idx => $order_id ) {
                    if ( in_array( $order_id, $excludes ) ) {
                        unset( $orders[ $idx ] );
                    }
                }

                // reset the indices
                $orders = array_merge( $orders, array() );
            }

            if ( !empty( $email->meta['excluded_customers_products'] ) ) {
                // exclude orders from customers who have purchased any one of these products
                $product_ids = implode( ',', $email->meta['excluded_customers_products'] );

                foreach ( $orders as $idx => $order_id ) {
                    $order      = WC_FUE_Compatibility::wc_get_order( $order_id );
                    $user_id    = WC_FUE_Compatibility::get_order_user_id( $order );

                    if ( $user_id ) {
                        $customer = fue_get_customer( $user_id );
                    } else {
                        $customer = fue_get_customer( 0, $order->billing_email );
                    }

                    if ( ! $customer ) {
                        continue;
                    }

                    $sql = "SELECT COUNT(*)
                                FROM {$wpdb->prefix}followup_order_items i
                                WHERE o.followup_customer_id = %d
                                AND o.order_id = i.order_id
                                AND (
                                    i.product_id IN ( $product_ids )
                                    OR
                                    i.variation_id IN ( $product_ids )
                                )";
                    $found = $wpdb->get_var( $wpdb->prepare( $sql, $customer->id ) );

                    if ( $found > 0 ) {
                        unset( $orders[ $idx ] );
                    }

                }

            }

            if ( !empty( $email->meta['excluded_customers_categories'] ) ) {
                $product_ids = implode( ',', $email->meta['excluded_customers_products'] );

                foreach ( $orders as $idx => $order_id ) {
                    $order      = WC_FUE_Compatibility::wc_get_order( $order_id );
                    $user_id    = WC_FUE_Compatibility::get_order_user_id( $order );

                    if ( $user_id ) {
                        $customer = fue_get_customer( $user_id );
                    } else {
                        $customer = fue_get_customer( 0, $order->billing_email );
                    }

                    if ( ! $customer ) {
                        continue;
                    }

                    $sql = "SELECT COUNT(*)
                            FROM {$wpdb->prefix}followup_order_items i, {$wpdb->prefix}followup_customer_orders o
                            WHERE o.followup_customer_id = %d
                            AND o.order_id = i.order_id
                            AND (
                                i.product_id IN ( $product_ids )
                                OR
                                i.variation_id IN ( $product_ids )
                            )";
                    $found = $wpdb->get_var( $wpdb->prepare( $sql, $customer->id ) );

                    if ( $found > 0 ) {
                        unset( $orders[ $idx ] );
                    }
                }

            }
        } elseif ( $trigger == 'first_purchase' ) {
            // get the order IDs of customers with only 1 order
            $customer_orders = $wpdb->get_col(
                "SELECT order_id
                    FROM {$wpdb->prefix}followup_customer_orders
                    GROUP BY followup_customer_id
                    HAVING COUNT(followup_customer_id) = 1"
            );

            if ( count( $customer_orders ) > 0 ) {
                $queue_orders = $wpdb->get_col( $wpdb->prepare(
                    "SELECT order_id
                    FROM {$wpdb->prefix}followup_email_orders
                    WHERE order_id IN (". implode(',', $customer_orders) .")
                    AND email_id = %d",
                    $email->id
                ) );

                // exclude orders that are already in the queue
                foreach ( $customer_orders as $customer_order ) {
                    if ( !in_array( $customer_order, $queue_orders ) ) {
                        $orders[] = $customer_order;
                    }
                }
            }

        } elseif ( $trigger == 'product_purchase_above_one' ) {
            // Get the orders of customers with more than 1 order
            $customers = $wpdb->get_col(
                "SELECT followup_customer_id
                    FROM {$wpdb->prefix}followup_customer_orders
                    GROUP BY followup_customer_id
                    HAVING COUNT(followup_customer_id) > 1"
            );

            foreach ( $customers as $customer_id ) {
                $customer_orders = $wpdb->get_col( $wpdb->prepare(
                    "SELECT order_id
                        FROM {$wpdb->prefix}followup_customer_orders
                        WHERE followup_customer_id = %d
                        ORDER BY order_id ASC",
                    $customer_id
                ) );

                if ( count( $customer_orders ) > 0 ) {
                    // drop the customer's first order
                    $customer_orders = array_slice( $customer_orders, 1 );

                    $queue_orders = $wpdb->get_col( $wpdb->prepare(
                        "SELECT order_id
                        FROM {$wpdb->prefix}followup_email_orders
                        WHERE order_id IN (". implode(',', $customer_orders) .")
                        AND email_id = %d",
                        $email->id
                    ) );

                    // exclude orders that are already in the queue
                    foreach ( $customer_orders as $customer_order ) {
                        if ( !in_array( $customer_order, $queue_orders ) ) {
                            $orders[] = $customer_order;
                        }
                    }
                }
            }

        }

        // Remove the orders that match the email's send_once property
        $email_meta     = $email->meta;
        $adjust_date    = false;


        if ( !empty($email_meta) ) {

            if ( isset($email_meta['adjust_date']) && $email_meta['adjust_date'] == 'yes' ) {
                $adjust_date = true;
            }

            foreach ( $orders as $idx => $order_id ) {
                $order = WC_FUE_Compatibility::wc_get_order( $order_id );

                // send email only once per customer
                if ( isset($email_meta['one_time']) && $email_meta['one_time'] == 'yes' ) {
                    $count_sent = $wpdb->get_var( $wpdb->prepare(
                        "SELECT COUNT(*)
                    FROM {$wpdb->prefix}followup_email_orders
                    WHERE `user_email` = %s
                    AND `email_id` = %d",
                        $order->billing_email,
                        $email->id
                    ) );

                    if ( $count_sent > 0 ) {
                        // do not send more of the same emails to this user
                        unset( $orders[ $idx ] );
                        continue;
                    }
                }

                // adjust date only applies to non-guest orders
                if ( $adjust_date && $order->customer_user > 0 ) {
                    // check for similar existing and unsent email orders
                    // and adjust the date to send instead of inserting a duplicate row
                    $similar_emails = $this->fue->scheduler->get_items( array(
                        'email_id'      => $email->id,
                        'user_id'       => $order->customer_user,
                        'product_id'    => $email->product_id,
                        'is_cart'       => 0,
                        'is_sent'       => 0
                    ) );

                    if ( count( $similar_emails ) > 0 ) {
                        unset( $orders[ $idx ] );
                        continue;
                    }
                }

            }

            // reset the indices
            $orders = array_values( $orders );

        }

        return $orders;
    }

    /**
     * Get the number of purchases a customer has made.
     *
     * Specify either a `product_id` or a `category_id` to count
     * the number of times the customer had purchased the product
     * or products from the category. Leaving the `product_id` and
     * `category_id` empty will return the number of times the customer
     * had purchased from the store.
     *
     * @param int $fue_customer_id
     * @param int $product_id
     * @param int $category_id
     * @return int
     */
    public function count_customer_purchases( $fue_customer_id, $product_id = 0, $category_id = 0 ) {

        $wpdb = Follow_Up_Emails::instance()->wpdb;

        if ( !empty( $product_id ) ) {
            // number of time this customer have purchased the current item
            $num_purchases = $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*)
                    FROM {$wpdb->prefix}followup_order_items oi, {$wpdb->prefix}followup_customer_orders co
                    WHERE co.followup_customer_id = %d
                    AND co.order_id = oi.order_id
                    AND oi.product_id = %d"
                , $fue_customer_id, $product_id
            ) );
        } elseif ( !empty( $category_id ) ) {
            $num_purchases = $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*)
                    FROM {$wpdb->prefix}followup_order_categories oc, {$wpdb->prefix}followup_customer_orders co
                    WHERE co.followup_customer_id = %d
                    AND co.order_id = oc.order_id
                    AND oc.category_id = %d",
                $fue_customer_id,
                $category_id
            ) );
        } else {
            $num_purchases = $wpdb->get_var( $wpdb->prepare(
                "SELECT total_orders
                FROM {$wpdb->prefix}followup_customers
                WHERE id = %d",
                $fue_customer_id
            ) );
        }

        return $num_purchases;

    }

    /**
     * Checks whether or not a customer had purchased the given product
     *
     * @param Object    $customer
     * @param int       $product_id
     * @return bool
     */
    public function customer_purchased_product( $customer, $product_id ) {
        $wpdb = Follow_Up_Emails::instance()->wpdb;

        $sql = "SELECT COUNT(*)
                    FROM {$wpdb->prefix}followup_order_items i, {$wpdb->prefix}followup_customer_orders o
                    WHERE o.followup_customer_id = %d
                    AND o.order_id = i.order_id
                    AND (
                        i.product_id = %d
                        OR
                        i.variation_id = %d
                    )";
        $found = $wpdb->get_var( $wpdb->prepare( $sql, $customer->id, $product_id, $product_id ) );

        if ( $found == 0 ) {
            return false;
        }

        return true;

    }

    public static function format_price( $price, $args = array() ) {
        if ( version_compare( WOOCOMMERCE_VERSION, '2.3', '>=' ) ) {
            $args = apply_filters( 'wc_price_args', wp_parse_args( $args, array(
                'ex_tax_label'       => false,
                'currency'           => '',
                'decimal_separator'  => wc_get_price_decimal_separator(),
                'thousand_separator' => wc_get_price_thousand_separator(),
                'decimals'           => wc_get_price_decimals(),
                'price_format'       => get_woocommerce_price_format()
            ) ) );
        } else {
            $args = apply_filters( 'wc_price_args', wp_parse_args( $args, array(
                'ex_tax_label'       => false,
                'currency'           => '',
                'decimal_separator'  => '.',
                'thousand_separator' => stripslashes( get_option( 'woocommerce_price_thousand_sep' ) ),
                'decimals'           => absint( get_option( 'woocommerce_price_num_decimals', 2 ) ),
                'price_format'       => get_woocommerce_price_format()
            ) ) );
        }

        $negative        = $price < 0;
        $price           = apply_filters( 'raw_woocommerce_price', floatval( $negative ? $price * -1 : $price ) );
        $price           = apply_filters( 'formatted_woocommerce_price', number_format( $price, $args['decimals'], $args['decimal_separator'], $args['thousand_separator'] ), $price, $args['decimals'], $args['decimal_separator'], $args['thousand_separator'] );

        if ( apply_filters( 'woocommerce_price_trim_zeros', false ) && $args['decimals'] > 0 ) {
            $price = wc_trim_zeros( $price );
        }

        $formatted_price = ( $negative ? '-' : '' ) . sprintf( $args['price_format'], get_woocommerce_currency_symbol( $args['currency'] ), $price );

        return $formatted_price;
    }

    /**
     * Returns a list of order items with the categories included
     *
     * @param WC_Order $order
     * @return array
     */
    public static function list_order_items( $order ) {
        $used_cats  = array();
        $lists      = array(
            'items'         => array(),
            'categories'    => array(),
        );

        $items      = $order->get_items();
        $items_array= array();

        foreach ( $items as $item ) {
            $item_id            = (isset($item['product_id'])) ? $item['product_id'] : $item['id'];
            $product            = WC_FUE_Compatibility::wc_get_product( $item_id );
            $item_name          = FUE_Addon_Woocommerce::get_product_name( $item_id );
            $items_array[]      = $item_name;
            $item_categories    = array();
            $cats               = get_the_terms($item_id, 'product_cat');

            if ( is_array( $cats ) && !empty( $cats ) ) {
                foreach ( $cats as $cat ) {
                    if ( !in_array( $cat->term_id, $used_cats ) ) {
                        $lists['categories'][] = $cat->name;
                    }

                    $item_categories[] = $cat->name;
                    $used_cats[] = $cat->term_id;
                }
            }

            $lists['items'][] = array(
                'id'            => $item_id,
                'product'       => $product,
                'sku'           => $product->get_sku(),
                'link'          => get_permalink( $item_id ),
                'name'          => $item_name,
                'price'         => $item['line_total'],
                'qty'           => $item['qty'],
                'categories'    => $item_categories,
                'data'          => $item
            );
        }

        return $lists;
    }

    /**
     * Checks whether or not a customer had purchased any product under the given category
     *
     * @param Object    $customer
     * @param int       $category_id
     * @return bool
     */
    public function customer_purchased_from_category( $customer, $category_id ) {
        $wpdb = Follow_Up_Emails::instance()->wpdb;

        $sql = "SELECT COUNT(*)
                    FROM {$wpdb->prefix}followup_order_categories c, {$wpdb->prefix}followup_customer_orders o
                    WHERE o.followup_customer_id = %d
                    AND o.order_id = c.order_id
                    AND c.category_id = %d";
        $found = $wpdb->get_var( $wpdb->prepare( $sql, $customer->id, $category_id ) );

        if ( $found == 0 ) {
            return false;
        }

        return true;
    }

    /**
     * Get orders matching the conditions of the customer email
     * @param FUE_Email $email
     * @return array
     */
    private function get_orders_for_customer_email( $email ) {
        $wpdb       = Follow_Up_Emails::instance()->wpdb;
        $orders     = array();

        if ( $email->type != 'customer' ) {
            return $orders;
        }

        if ( $email->trigger == 'after_last_purchase' ) {
            $customer_ids = $wpdb->get_col(
                "SELECT DISTINCT followup_customer_id
                FROM {$wpdb->prefix}followup_customer_orders"
            );

            foreach ( $customer_ids as $customer_id ) {
                // get the last order of each customer
                $order_id = $wpdb->get_var( $wpdb->prepare(
                    "SELECT order_id
                    FROM  {$wpdb->prefix}followup_customer_orders co
                    WHERE followup_customer_id = %d
                    AND (
                        SELECT COUNT(*)
                        FROM {$wpdb->prefix}followup_email_orders
                        WHERE order_id = co.order_id
                        AND email_id = %d
                        ) = 0
                    AND (
                        SELECT meta_value
                        FROM {$wpdb->postmeta}
                        WHERE post_id = order_id
                        AND meta_key = '_fue_recorded'
                        ) != 1
                    ORDER BY order_id DESC
                    LIMIT 1",
                    $customer_id,
                    $email->id
                ) );

                if ( $order_id ) {
                    $orders[] = $order_id;
                }

            }
        }

        if ( $email->trigger == 'order_total_above' ) {
            // get customer orders where the order total exceeds the value specified in the email
            $order_total_baseline = empty($email->meta['order_total_above']) ? 0 : $email->meta['order_total_above'];
            $orders = $wpdb->get_col( $wpdb->prepare(
                "SELECT order_id
                FROM {$wpdb->prefix}followup_customer_orders co
                WHERE price > %d
                AND (
                    SELECT COUNT(*)
                    FROM {$wpdb->prefix}followup_email_orders
                    WHERE order_id = co.order_id
                    AND email_id = %d
                ) = 0
                AND (
                    SELECT meta_value
                    FROM {$wpdb->postmeta}
                    WHERE post_id = order_id
                    AND meta_key = '_fue_recorded'
                    ) != 1",
                $order_total_baseline,
                $email->id
            ) );
        }

        if ( $email->trigger == 'order_total_below' ) {
            // get customer orders where the order total is below the value specified in the email
            $order_total_baseline = empty($email->meta['order_total_below']) ? 0 : $email->meta['order_total_below'];
            $orders = $wpdb->get_col( $wpdb->prepare(
                "SELECT order_id
                FROM {$wpdb->prefix}followup_customer_orders co
                WHERE price < %d
                AND (
                    SELECT COUNT(*)
                    FROM {$wpdb->prefix}followup_email_orders
                    WHERE order_id = co.order_id
                    AND email_id = %d
                ) = 0
                AND (
                    SELECT meta_value
                    FROM {$wpdb->postmeta}
                    WHERE post_id = order_id
                    AND meta_key = '_fue_recorded'
                    ) != 1",
                $order_total_baseline,
                $email->id
            ) );
        }

        if ( $email->trigger == 'purchase_above_one' ) {
            // Get the orders of customers with more than 1 order
            $customers = $wpdb->get_col(
                "SELECT followup_customer_id
                FROM {$wpdb->prefix}followup_customer_orders
                GROUP BY followup_customer_id
                HAVING COUNT(followup_customer_id) > 1"
            );

            foreach ( $customers as $customer_id ) {
                $customer_orders = $wpdb->get_col( $wpdb->prepare(
                    "SELECT order_id
                    FROM {$wpdb->prefix}followup_customer_orders
                    WHERE followup_customer_id = %d
                    ORDER BY order_id ASC",
                    $customer_id
                ) );

                if ( count( $customer_orders ) > 0 ) {
                    // drop the customer's first order
                    $customer_orders = array_slice( $customer_orders, 1 );

                    $queue_orders = $wpdb->get_col( $wpdb->prepare(
                        "SELECT order_id
                        FROM {$wpdb->prefix}followup_email_orders
                        WHERE order_id IN (". implode(',', $customer_orders) .")
                        AND email_id = %d",
                        $email->id
                    ) );

                    // exclude orders that are already in the queue
                    foreach ( $customer_orders as $customer_order ) {
                        if ( !in_array( $customer_order, $queue_orders ) ) {
                            $orders[] = $customer_order;
                        }
                    }
                }
            }
        }

        if ( $email->trigger == 'total_orders' ) {
            $total_orders_baseline = $email->meta['total_orders'];
            $operator = ($email->meta['total_orders_mode'] == 'equal to') ? '=' : '>';

            $customers = $wpdb->get_col( $wpdb->prepare(
                "SELECT followup_customer_id
                FROM {$wpdb->prefix}followup_customer_orders
                GROUP BY followup_customer_id
                HAVING COUNT(followup_customer_id) $operator %d",
                $total_orders_baseline
            ) );

            foreach ( $customers as $customer_id ) {
                $order_id = $wpdb->get_var( $wpdb->prepare(
                    "SELECT order_id
                    FROM {$wpdb->prefix}followup_customer_orders
                    WHERE followup_customer_id = %d
                    ORDER BY order_id DESC
                    LIMIT 1",
                    $customer_id
                ) );

                if ( $order_id ) {
                    $in_queue = $wpdb->get_var( $wpdb->prepare(
                        "SELECT COUNT(*)
                        FROM {$wpdb->prefix}followup_email_orders
                        WHERE order_id = %d
                        AND email_id = %d",
                        $order_id,
                        $email->id
                    ) );

                    if ( $in_queue ) {
                        continue;
                    }

                    $orders[] = $order_id;
                }
            }

        }

        if ( $email->trigger == 'total_purchases' ) {
            $total_purchases_baseline = $email->meta['total_purchases'];
            $operator = ($email->meta['total_purchases_mode'] == 'equal to') ? '=' : '>';

            $customers = $wpdb->get_col( $wpdb->prepare(
                "SELECT followup_customer_id
                FROM {$wpdb->prefix}followup_customer_orders
                GROUP BY followup_customer_id
                HAVING SUM(price) $operator %d",
                $total_purchases_baseline
            ) );

            foreach ( $customers as $customer_id ) {
                // get the customer's last order_id
                $order_id = $wpdb->get_var( $wpdb->prepare(
                    "SELECT order_id
                    FROM {$wpdb->prefix}followup_customer_orders
                    WHERE followup_customer_id = %d
                    ORDER BY order_id DESC
                    LIMIT 1",
                    $customer_id
                ) );

                if ( $order_id ) {
                    $in_queue = $wpdb->get_var( $wpdb->prepare(
                        "SELECT COUNT(*)
                        FROM {$wpdb->prefix}followup_email_orders
                        WHERE order_id = %d
                        AND email_id = %d",
                        $order_id,
                        $email->id
                    ) );

                    if ( $in_queue ) {
                        continue;
                    }

                    $orders[] = $order_id;
                }
            }
        }

        return $orders;
    }

}