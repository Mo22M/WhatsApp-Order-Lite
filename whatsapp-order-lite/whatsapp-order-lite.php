<?php
/**
 * Plugin Name: WhatsApp Order Lite
 * Plugin URI: https://yourwebsite.com
 * Description: Add a "Order via WhatsApp" button on WooCommerce product pages.
 * Version: 1.1
 * Author: M.Husseiny
 * License: GPL2
 */

// ===========================================
// 1️⃣ WhatsApp Button on Product Page
// ===========================================
add_action('woocommerce_after_add_to_cart_button', 'add_whatsapp_order_button');

function add_whatsapp_order_button() {
    if (!is_product()) return;

    global $product;
    $product_name = $product->get_name();
    $seller_id = get_post_field('post_author', $product->get_id());
    $whatsapp_number = get_user_meta($seller_id, 'whatsapp', true);
    if (!$whatsapp_number) $whatsapp_number = '201234567890'; // Default number

    $message = "Hello, I would like to order the product: $product_name - $product_url";

    echo '<a target="_blank" href="https://wa.me/' . $whatsapp_number . '?text=' . urlencode($message) . '" class="button whatsapp-order-button">Order via WhatsApp</a>';
}

// ===========================================
// 2️⃣ Styles for WhatsApp Button
// ===========================================
add_action('wp_footer', 'whatsapp_button_custom_styles');
function whatsapp_button_custom_styles() {
    echo '<style>
        .single_add_to_cart_button { margin-left: 10px; }
        .whatsapp-order-button {
            display: inline-block;
            vertical-align: middle;
            font-size: 1em;
            padding: 0.618em 1.5em;
            border-radius: 3px;
            border: none;
            cursor: pointer;
            background-color: #25D366 !important;
            color: white !important;
            margin-right: 10px;
        }
        .whatsapp-order-button:hover { background-color: #1ebe5d !important; }
        form.cart {
            display: flex;
            align-items: center;
            gap: 10px;
        }
    </style>';
}

// ===========================================
// 3️⃣ Admin Settings Page
// ===========================================
add_action('admin_menu', 'whatsapp_order_add_admin_menu');
function whatsapp_order_add_admin_menu() {
    add_menu_page(
        'WhatsApp Order Lite',
        'WhatsApp Order',
        'manage_options',
        'whatsapp-order-lite',
        'whatsapp_order_admin_page',
        'dashicons-whatsapp',
        56
    );
}

function whatsapp_order_admin_page() {
    // Handle form submission
    if (
        isset($_POST['vendor_data']) &&
        is_array($_POST['vendor_data']) &&
        current_user_can('manage_options')
    ) {
        foreach ($_POST['vendor_data'] as $user_id => $data) {
         $shop_name = sanitize_text_field($data['shopname']); // نقرأ shopname من الفورم
        $whatsapp_number = sanitize_text_field($data['whatsapp_number']);

         update_user_meta($user_id, 'shopname', $shop_name); // نحفظ في meta key shopname
        update_user_meta($user_id, 'whatsapp_number', $whatsapp_number);
}

        echo '<div class="updated notice is-dismissible"><p>Vendors updated successfully.</p></div>';
    }
    ?>

    <div class="wrap">
        <h2>Vendor WhatsApp Settings</h2>
        <form method="post">
            <table class="widefat fixed striped">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Shop name</th>
                        <th>WhatsApp Number</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Get all users who have either store_name or whatsapp_number meta
                    $users = get_users(array(
                        'meta_query' => array(
                            'relation' => 'OR',
                            array(
                                'key' => 'store_name',
                                'compare' => 'EXISTS',
                            ),
                            array(
                                'key' => 'whatsapp_number',
                                'compare' => 'EXISTS',
                            )
                        )
                    ));

                    foreach ($users as $user) {
                        $user_id = $user->ID;
                        $shop_name = get_user_meta($user_id, 'shopname', true);
                        $whatsapp_number = get_user_meta($user_id, 'whatsapp_number', true);

                        echo '<tr>';
                        echo '<td>' . esc_html($user->user_login) . '</td>';
                        echo '<td><input type="text" name="vendor_data[' . esc_attr($user_id) . '][shopname]" value="' . esc_attr($shop_name) . '" /></td>';
                        echo '<td><input type="text" name="vendor_data[' . esc_attr($user_id) . '][whatsapp_number]" value="' . esc_attr($whatsapp_number) . '" /></td>';
                        echo '</tr>';
                    }
                    ?>
                </tbody>
            </table>
            <?php submit_button('Update Vendors'); ?>
        </form>
    </div>
    <?php
}

// ===========================================
// 4️⃣ Add WhatsApp Field to Registration Form (Vendors Only)
// ===========================================
add_action('woocommerce_register_form', 'add_vendor_custom_fields');
function add_vendor_custom_fields() {
    ?>
    <p class="form-row form-row-wide" id="whatsapp_field" style="display:none;">
        <label for="whatsapp_number">WhatsApp Number</label>
        <input type="text" name="whatsapp_number" id="whatsapp_number" />
    </p>

    <script type="text/javascript">
    document.addEventListener('DOMContentLoaded', function() {
        var whatsappField = document.getElementById('whatsapp_field');

        function checkVendorSelected() {
            var vendorSelected = false;

            // لو فيه select فيه دور المستخدم
            var roleSelect = document.querySelector('select[name="role"]');
            if (roleSelect && (roleSelect.value === 'seller' || roleSelect.value === 'vendor')) {
                vendorSelected = true;
            }

            // لو فيه radio/checkbox بيحدد انه بائع
            var roleInputs = document.querySelectorAll('input[name="role"]');
            roleInputs.forEach(function(input) {
                if (input.checked && (input.value === 'seller' || input.value === 'vendor')) {
                    vendorSelected = true;
                }
            });

            // دعم دكان وأي إضافة مشابهة
            var dokanCheckbox = document.querySelector('input[name="dokan_enable_selling"]');
            if (dokanCheckbox && dokanCheckbox.checked) {
                vendorSelected = true;
            }

            // إظهار أو إخفاء الحقل
            whatsappField.style.display = vendorSelected ? 'block' : 'none';
        }

        document.addEventListener('change', checkVendorSelected);
        checkVendorSelected();
    });
    </script>
    <?php
}

// ===========================================
// 5️⃣ Save Fields on Registration
// ===========================================
add_action('woocommerce_created_customer', 'save_vendor_custom_fields');
function save_vendor_custom_fields($customer_id) {
    if (isset($_POST['shopname'])) { // بدل shop_name بـ shopname
        update_user_meta($customer_id, 'shopname', sanitize_text_field($_POST['shopname']));
    }
    if (isset($_POST['whatsapp_number'])) {
        update_user_meta($customer_id, 'whatsapp_number', sanitize_text_field($_POST['whatsapp_number']));
    }
}

// ===========================================
// 4️⃣ Vendor Profile WhatsApp Field
// ===========================================
add_action('show_user_profile', 'add_whatsapp_field_to_vendor');
add_action('edit_user_profile', 'add_whatsapp_field_to_vendor');
function add_whatsapp_field_to_vendor($user) {
    if (in_array('seller', $user->roles)) {
        ?>
        <h3>Vendor Settings</h3>
        <table class="form-table">
            <tr>
                <th><label for="whatsapp">WhatsApp Number</label></th>
                <td>
                    <input type="text" name="whatsapp" id="whatsapp" value="<?php echo esc_attr(get_user_meta($user->ID, 'whatsapp', true)); ?>" class="regular-text" />
                    <p class="description">Enter your WhatsApp number.</p>
                </td>
            </tr>
        </table>
        <?php
    }
}

add_action('personal_options_update', 'save_vendor_whatsapp_field');
add_action('edit_user_profile_update', 'save_vendor_whatsapp_field');
function save_vendor_whatsapp_field($user_id) {
    if (current_user_can('edit_user', $user_id)) {
        update_user_meta($user_id, 'whatsapp', sanitize_text_field($_POST['whatsapp']));
    }
}

function whatsapp_order_vendor_page() {
    $user_id = get_current_user_id();

    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['vendor_whatsapp'])) {
        update_user_meta($user_id, 'whatsapp', sanitize_text_field($_POST['vendor_whatsapp']));
        echo '<div class="notice notice-success is-dismissible"><p>Your WhatsApp number has been updated.</p></div>';
    }

    $current_number = esc_attr(get_user_meta($user_id, 'whatsapp', true));
    ?>
    <div class="wrap">
        <h1>Vendor WhatsApp Settings</h1>
        <form method="post">
            <table class="form-table">
                <tr>
                    <th><label for="vendor_whatsapp">Your WhatsApp Number</label></th>
                    <td>
                        <input type="text" name="vendor_whatsapp" id="vendor_whatsapp" value="<?php echo $current_number; ?>" class="regular-text" />
                        <p class="description">This number will be used on all your products.</p>
                    </td>
                </tr>
            </table>
            <?php submit_button('Save WhatsApp Number'); ?>
        </form>
    </div>
    <?php
}

// ===========================================
// 7️⃣ Dokan Alert if No WhatsApp Number
// ===========================================
add_action('dokan_product_edit_after_main', 'alert_vendor_if_no_whatsapp');
function alert_vendor_if_no_whatsapp() {
    $user_id = get_current_user_id();
    $whatsapp = get_user_meta($user_id, 'whatsapp', true);
    if (empty($whatsapp)) {
        echo '<div class="dokan-alert dokan-alert-warning">⚠️ You haven\'t added your WhatsApp number. The button will use a default number. <a href="/my-account/edit-account">Click here to update it.</a></div>';
    }
}

// ===========================================
// 8️⃣ User Roles WhatsApp Field (Admin / Vendor)
// ===========================================
add_action('show_user_profile', 'add_whatsapp_field_to_user');
add_action('edit_user_profile', 'add_whatsapp_field_to_user');
function add_whatsapp_field_to_user($user) {
    if (array_intersect(['vendor', 'seller', 'shop_manager', 'administrator'], $user->roles)) {
        ?>
        <h3>WhatsApp Settings</h3>
        <table class="form-table">
            <tr>
                <th><label for="whatsapp">WhatsApp Number</label></th>
                <td>
                    <input type="text" name="whatsapp" id="whatsapp" value="<?php echo esc_attr(get_user_meta($user->ID, 'whatsapp', true)); ?>" class="regular-text" />
                    <p class="description">Enter the WhatsApp number that will appear on your products.</p>
                </td>
            </tr>
        </table>
        <?php
    }
}

add_action('personal_options_update', 'save_whatsapp_field_to_user');
add_action('edit_user_profile_update', 'save_whatsapp_field_to_user');
function save_whatsapp_field_to_user($user_id) {
    if (current_user_can('edit_user', $user_id) && isset($_POST['whatsapp'])) {
        update_user_meta($user_id, 'whatsapp', sanitize_text_field($_POST['whatsapp']));
    }
}

// ===========================================
// 9️⃣ Validate Number Format (example)
// ===========================================
if (!preg_match('/^(\+?2|00)?[0-9]{10,}$/', $whatsapp_number)) {
  // check the number alert
}
