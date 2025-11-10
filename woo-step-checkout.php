<?php

/**
 * Astra child  Theme functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package Astra child 
 * @since 1.0.0
 */

/**
 * Define Constants
 */
define('CHILD_THEME_ASTRA_CHILD_VERSION', '1.0.0');

/**
 * Enqueue styles
 */
function child_enqueue_styles()
{

    wp_enqueue_style('astra-child-theme-css', get_stylesheet_directory_uri() . '/style.css', array('astra-theme-css'), CHILD_THEME_ASTRA_CHILD_VERSION, 'all');
}

add_action('wp_enqueue_scripts', 'child_enqueue_styles', 15);



//sample checkout page 
// 保存 Material Series
add_filter('woocommerce_add_cart_item_data', function ($cart_item_data, $product_id) {
    if (isset($_POST['series']) && is_array($_POST['series'])) {
        $cart_item_data['series_meta'] = array_map('sanitize_text_field', $_POST['series']);
    } elseif (isset($_POST['series'])) {
        $cart_item_data['series_meta'] = sanitize_text_field($_POST['series']);
    }
    return $cart_item_data;
}, 10, 2);

// 显示 Material Series 在购物车/结账页
add_filter('woocommerce_get_item_data', function ($item_data, $cart_item) {
    if (isset($cart_item['series_meta'])) {
        // 如果是数组，转换为逗号分隔的字符串
        $value = $cart_item['series_meta'];
        if (is_array($value)) {
            $value = implode(', ', $value); // Entry Series, Ultra Series
        }

        $item_data[] = [
            'name'  => 'Material Series',
            'value' => $value
        ];
    }
    return $item_data;
}, 10, 2);


/**處理訂單相關 */
// 保存 Material Series 到訂單項目中
add_action('woocommerce_checkout_create_order_line_item', 'save_series_to_order_item', 10, 4);
function save_series_to_order_item($item, $cart_item_key, $cart_item, $order) {
    // 从购物车数据中获取 Material Series
    if (isset($cart_item['series_meta'])) {
        $series_value = $cart_item['series_meta'];
        // 如果是数组，转换为逗号分隔的字符串（与购物车显示保持一致）
        if (is_array($series_value)) {
            $series_value = implode(', ', $series_value);
        }
        // 添加到订单项目元数据（键名与购物车显示一致，确保显示统一）
        $item->add_meta_data('Material Series', $series_value, true);
    }
}

// 确保元数据在订单页面正确显示（可选，WooCommerce 通常会自动显示非下划线开头的元数据）
add_filter('woocommerce_order_item_display_meta_key', 'display_series_meta_key', 10, 3);
function display_series_meta_key($display_key, $meta, $order_item) {
    if ($meta->key === 'Material Series') {
        return 'Material Series'; // 强制显示的标签名
    }
    return $display_key;
}

//AJAX 接口获取购物车价格明细
// 返回 WooCommerce 购物车价格明细
add_action('wp_ajax_get_cart_totals', 'get_cart_totals');
add_action('wp_ajax_nopriv_get_cart_totals', 'get_cart_totals');

function get_cart_totals()
{
    WC()->cart->calculate_totals();
    $totals = WC()->cart->get_totals();

    wp_send_json([
        'subtotal' => isset($totals['subtotal']) ? wc_price($totals['subtotal']) : '$0.00',
        'shipping' => isset($totals['shipping_total']) ? wc_price($totals['shipping_total']) : 'Calculated at checkout',
        'tax'      => isset($totals['total_tax']) ? wc_price($totals['total_tax']) : 'Calculated at checkout',
        'total'    => isset($totals['total']) ? wc_price($totals['total']) : '$0.00',
    ]);
}

function generate_color_classes()
{
    return [
        'green' => [
            'bg'   => 'rgb(220 252 231)',
            'text' => 'rgb(22 163 74)'
        ],
        'blue' => [
            'bg'   => 'rgb(219 234 254)',
            'text' => 'rgb(37 99 235)'
        ],
        'yellow' => [
            'bg'   => 'rgb(254 249 195)',
            'text' => 'rgb(202 138 4)'
        ],
        'red' => [
            'bg'   => 'rgb(254 226 226)',
            'text' => 'rgb(220 38 38)'
        ],
        'purple' => [
            'bg'   => 'rgb(243 232 255)',
            'text' => 'rgb(147 51 234)'
        ],
    ];
}

/**
 * 输出所有样式的 CSS
 */
function output_color_css()
{
    $colors = generate_color_classes();
    $css = "";

    foreach ($colors as $name => $value) {
        // 处理 background：把 alpha 放在括号内，如果是 rgb(...) 格式
        if (preg_match('/^rgb\(\s*([^\)]+)\s*\)$/i', $value['bg'], $m)) {
            $bg_color = "rgb({$m[1]} / var(--tw-bg-opacity, 1))";
        } else {
            // 回退（直接把 var 加在后面，浏览器能接受的大多数情况）
            $bg_color = $value['bg'] . ' / var(--tw-bg-opacity, 1)';
        }

        // 处理 text color：同上
        if (preg_match('/^rgb\(\s*([^\)]+)\s*\)$/i', $value['text'], $m2)) {
            $text_color = "rgb({$m2[1]} / var(--tw-text-opacity, 1))";
        } else {
            $text_color = $value['text'] . ' / var(--tw-text-opacity, 1)';
        }

        $css .= ".bg-{$name}-100 {\n";
        $css .= "    --tw-bg-opacity: 1;\n";
        $css .= "    background-color: {$bg_color};\n";
        $css .= "}\n\n";

        $css .= ".text-{$name}-600 {\n";
        $css .= "    --tw-text-opacity: 1;\n";
        $css .= "    color: {$text_color};\n";
        $css .= "}\n\n";
    }

    return $css;
}

function get_random_style_class($count = 1)
{
    $colors = array_keys(generate_color_classes());

    // 限制最大数量不超过现有颜色数
    $count = min($count, count($colors));

    // 打乱顺序
    shuffle($colors);

    // 取指定数量
    $selected = array_slice($colors, 0, $count);

    // 返回对应的类名
    $result = [];
    foreach ($selected as $color) {
        $result[] = [
            'bg'   => "bg-{$color}-100",
            'text' => "text-{$color}-600"
        ];
    }

    // 如果只需要一个，直接返回第一个
    if ($count === 1) {
        return $result[0];
    }

    return $result;
}

function _render_step1_choose_pack($product_id, $data)
{
    $addons = $data['addons'] ?? [];
    $variations = $data['variations'] ?? [];
    $pack_options = $data['pack_options'] ?? [];
    ob_start();
    ?>
    <style>
        <?php echo output_color_css(); ?>.choose-pack-page .section-title {
            font-size: 30px;
            line-height: 36px;
            margin-bottom: 32px;
            font-weight: 700px;
        }

        .pack-card {
            border: 2px solid #ccc;
            padding: 24px;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: #fff;
            position: relative;
        }

        .pack-card .icon-txt {
            border-radius: 0.5rem;
            justify-content: center;
            align-items: center;
            width: 4rem;
            height: 4rem;
            display: flex;
            margin-bottom: 1rem;
            margin-left: auto;
            margin-right: auto;
        }

        .pack-card .icon-txt span {
            font-weight: 700;
            font-size: 1.125rem;
            line-height: 1.75rem;
        }

        .pack-card .card-title {
            font-size: 1.25rem;
            line-height: 1.75rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .pack-card .sub-desction {
            font-size: 0.875rem !important;
            line-height: 1.25rem !important;
            margin-bottom: 1rem !important;
            text-align: center !important;
        }

        .pack-card .desction {
            color: #4b5563;
            line-height: 16px;
            font-size: 12px;
            text-align: left;
            width: 90%;
            margin-left: auto;
            margin-right: auto;
        }

        .pack-card:hover {
            transform: translateY(-3px);
            border: 2px solid #e67e22 !important;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }

        .pack-card.selected {
            box-shadow: 0 6px 20px rgba(0, 115, 170, 0.2);
            transform: translateY(-3px);
            border: 2px solid #e67e22 !important;
            background: linear-gradient(135deg, rgba(230, 126, 34, 0.1) 0%, rgba(236, 240, 241, 0.9) 100%);
        }

        .samples label {
            display: flex;
            align-items: center;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .samples label:hover {
            background: #f9f9f9;
        }

        .samples input:checked+span {
            font-weight: bold;
            color: #0073aa;
        }

        .right-side {
            border: 1px solid #ccc;
            padding: 15px;
            border-radius: 10px;
            height: fit-content;
        }
    </style>
    <form id="choose-pack-form" class="choose-pack-form-grid">
        <div id="choose-pack-page" style="display:flex; gap:40px; max-width:1200px; margin:auto; flex-wrap:wrap;">
            <div class="left-side" style="flex:1; min-width:500px;">
                <h2 class="section-title">Choose Your Sample Pack</h2>
                <div class="packs" style="display:grid; grid-template-columns:repeat(auto-fill,minmax(250px,1fr)); gap:20px; margin-bottom:20px;">
                    <?php
                    // 获取多个随机样式（不重复）
                    $styles = get_random_style_class(count($variations));
                    foreach ($variations as $var):
                        $pack = $var['attributes']['attribute_pa_pack-type'] ?? '';
                        $description = $var['variation_description'] ?? '';
                        if (!$pack) continue;

                        $pack_name = $pack_options[$pack]['name'] ?? "";
                        $subDescription = $pack_options[$pack]['description'] ?? '';
                        $styleColor = array_shift($styles);

                    ?>
                        <div class="pack-card" data-pack="<?php echo esc_attr($pack); ?>" data-price="<?php echo esc_attr($var['display_price']); ?>" data-variation_id="<?php echo esc_attr($var['variation_id']); ?>">
                            <div style="text-align:center;">
                                <div class="icon-txt <?php echo $styleColor['bg']; ?>">
                                    <span class="<?php echo $styleColor['text']; ?>"><?php echo strtoupper(mb_substr($pack_name, 0, 1)); ?></span>
                                </div>
                                <h3 class="card-title"><?php echo esc_html($pack_name); ?></h3>
                                <div class="desction sub-desction"><?php echo $subDescription; ?></div>
                                <div style="font-size:1.5rem; font-weight:bold; color:#ff6600;"><?php echo wc_price($var['display_price']); ?></div>
                                <div class="desction"><?php echo $description; ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if (!empty($addons)) {
                    foreach ($addons as $id => $addon):
                        $inputName = "series[]";
                        $addonOpts = is_array($addon['options']) ? $addon['options'] : [];
                ?>
                        <h2 class="section-title"><?php echo $addon['name']; ?></h2>
                        <div class="samples" style="display:grid; grid-template-columns:repeat(2,1fr); gap:10px; margin-bottom:20px;">
                            <?php foreach ($addonOpts as $s) {
                                echo '<label><input type="checkbox" required name="' . $inputName . '" value="' . esc_attr($s['label']) . '" data-price="' . esc_attr($s['price']) . '"><span style="margin-left:6px;">' . esc_html($s['label']) . '</span></label>';
                            } ?>
                        </div>
                <?php
                    endforeach;
                } ?>
                <input type="hidden" required name="product_id" value="" />
            </div>
        </div>
    </form>
<?php
    return ob_get_clean();
}
function _render_step3_review_comfirm()
{
    ob_start(); ?>
    <style>
        .review-step-section {
            background-color: white;
            padding: 24px;
            border-radius: 10px;
            margin: 40px 0px;
            border: 1px solid #e5e7eb;
        }

        .review-step-section ul {
            list-style: none !important;
            padding: 0 !important;
            margin: 0 !important;
        }
    </style>
    <div id="review-step" style="display:none; margin-top:30px;">
        <h2>Review & Confirm Order</h2>
        <div class="review-step-section">
            <h3>Order Summary</h3>
            <div id="order-summary"></div>
        </div>
        <div class="review-step-section">
            <h3>Order Summary</h3>
            <div id="shipping-info"></div>
        </div>
        <div class="review-step-section">
            <h3>Payment Method</h3>
            <form id="confirm-order-form">
                <div id="payment-method"></div>
            </form>
        </div>
    </div>
<?php
    return ob_get_clean();
}

function _render_step2_shipping_info()
{
    // 先检查WooCommerce是否加载
    if (!class_exists('WC_Countries') || !WC()->countries) {
        return '<p>Shipping information requires WooCommerce to be active.</p>';
    }
    // 获取当前用户信息（登录用户自动填充）
    $customer = WC()->customer;
    $billing_first_name = $customer ? $customer->get_billing_first_name() : '';
    $billing_email = $customer ? $customer->get_billing_email() : '';
    $billing_company = $customer ? $customer->get_billing_company() : '';
    $billing_phone = $customer ? $customer->get_billing_phone() : '';
    $billing_country = $customer ? $customer->get_billing_country() : 'GB'; // 默认国家
    $billing_address_1 = $customer ? $customer->get_billing_address_1() : '';
    $billing_city = $customer ? $customer->get_billing_city() : '';
    $billing_state = $customer ? $customer->get_billing_state() : '';
    $billing_postcode = $customer ? $customer->get_billing_postcode() : '';
    $order_comments = $customer ? $customer->get_meta('order_comments') : '';

    ob_start(); ?>
    <!-- Step 2: Shipping Info -->
    <div id="shipping-info-step" style="display:none; margin-top:30px;">
        <h2>Shipping Information</h2>
        <form id="shipping-form" class="shipping-grid">

            <!-- 第一行：姓名和邮箱 -->
            <div class="row two-col">
                <div class="col">
                    <label>Full Name *</label>
                    <input type="text" name="billing_first_name" required
                        placeholder="Enter your full name"
                        value="<?php echo esc_attr($billing_first_name); ?>">
                </div>
                <div class="col">
                    <label>Email Address *</label>
                    <input type="email" name="billing_email" required
                        placeholder="your@email.com"
                        value="<?php echo esc_attr($billing_email); ?>">
                </div>
            </div>

            <!-- 第二行：公司和电话 -->
            <div class="row two-col">
                <div class="col">
                    <label>Company</label>
                    <input type="text" name="billing_company"
                        placeholder="Your company name (optional)"
                        value="<?php echo esc_attr($billing_company); ?>">
                </div>
                <div class="col">
                    <label>Phone</label>
                    <input type="tel" name="billing_phone"
                        placeholder="Your phone number"
                        value="<?php echo esc_attr($billing_phone); ?>">
                </div>
            </div>

            <!-- 第三行：国家（联动州/省） -->
            <div class="row full">
                <label>Country *</label>
                <select name="billing_country" id="billing_country" required
                    onchange="updateStateField(this.value)">
                    <?php foreach (WC()->countries->get_allowed_countries() as $code => $name) : ?>
                        <option value="<?php echo esc_attr($code); ?>"
                            <?php selected($code, $billing_country); ?>>
                            <?php echo esc_html($name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- 第四行：地址 -->
            <div class="row full">
                <label>Address *</label>
                <input type="text" name="billing_address_1" required
                    placeholder="Street address, P.O. box, etc."
                    value="<?php echo esc_attr($billing_address_1); ?>">
            </div>

            <!-- 第五行：城市、州/省、邮编 -->
            <div class="row three-col">
                <div class="col">
                    <label>City *</label>
                    <input type="text" name="billing_city" required
                        placeholder="City"
                        value="<?php echo esc_attr($billing_city); ?>">
                </div>
                <div class="col">
                    <label>State/Province <?php $states = WC()->countries->get_states($billing_country);
                                            echo (!empty($states) ? '*' : ''); ?></label>
                    <!-- 州/省动态容器 -->
                    <div id="billing_state_field">
                        <?php
                        // 初始加载时根据默认国家生成州/省字段
                        $states = WC()->countries->get_states($billing_country);
                        if (!empty($states)) {
                            // 有州/省列表，显示下拉框
                            $states = WC()->countries->get_states($billing_country);
                            echo '<select name="billing_state" required>';
                            echo '<option value="">Select a state</option>';
                            foreach ($states as $state_code => $state_name) {
                                echo '<option value="' . esc_attr($state_code) . '" ' . selected($state_code, $billing_state, false) . '>' . esc_html($state_name) . '</option>';
                            }
                            echo '</select>';
                        } else {
                            // 无州/省列表，显示文本框
                            echo '<input type="text" name="billing_state" placeholder="State/Province" value="' . esc_attr($billing_state) . '">';
                        }
                        ?>
                    </div>
                </div>
                <div class="col">
                    <label>Postal Code *</label>
                    <input type="text" name="billing_postcode" required
                        placeholder="ZIP/Postal code"
                        value="<?php echo esc_attr($billing_postcode); ?>">
                </div>
            </div>

            <!-- 第六行：备注 -->
            <div class="row full">
                <label>Notes (Optional)</label>
                <textarea name="order_comments" rows="3"
                    placeholder="Any special requirements or questions?"><?php echo esc_textarea($order_comments); ?></textarea>
            </div>
        </form>
    </div>

    <style>
        /* 保持原样式，新增州/省字段错误提示 */
        .input-error {
            border-color: #dc3232 !important;
            box-shadow: 0 0 0 3px rgba(220, 50, 50, 0.1) !important;
        }
    </style>
    <style>
        /* Shipping Info 样式 */
        .shipping-grid {
            max-width: 900px;
            margin: auto;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .shipping-grid .row {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }

        .shipping-grid .row.full {
            flex-direction: column;
        }

        .shipping-grid .two-col .col {
            flex: 1 1 calc(50% - 10px);
        }

        .shipping-grid .three-col .col {
            flex: 1 1 calc(33.33% - 10px);
        }

        .shipping-grid label {
            font-weight: 600;
            display: block;
            margin-bottom: 6px;
            color: #333;
        }

        .shipping-grid input,
        .shipping-grid select,
        .shipping-grid textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ccc;
            border-radius: 8px;
            font-size: 15px;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .shipping-grid input:focus,
        .shipping-grid select:focus,
        .shipping-grid textarea:focus {
            border-color: #0073aa;
            box-shadow: 0 0 0 3px rgba(0, 115, 170, 0.15);
            outline: none;
        }

        .shipping-grid textarea {
            resize: vertical;
            min-height: 80px;
        }

        .btn-primary {
            background: #0073aa;
            color: #fff;
            border: none;
            padding: 12px 28px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s, transform 0.2s;
        }

        .btn-primary:hover {
            background: #005f8c;
            transform: translateY(-1px);
        }
    </style>

    <script>
        // 动态更新州/省字段（根据国家选择）
        function updateStateField(countryCode) {
            const stateFieldContainer = document.getElementById('billing_state_field');
            // 加载中提示
            stateFieldContainer.innerHTML = '<div>Loading...</div>';

            // 调用WooCommerce的州/省数据（前端直接处理，无需AJAX）
            <?php
            // 预加载所有国家的州/省数据到JS变量（减少AJAX请求）
            $all_states = WC()->countries->get_states();
            echo "const allStates = " . json_encode($all_states) . ";";
            ?>

            // 生成州/省字段HTML
            if (allStates[countryCode] && Object.keys(allStates[countryCode]).length > 0) {
                // 有州/省列表，显示下拉框
                let html = '<select name="billing_state" required>';
                html += '<option value="">Select a state</option>';
                Object.entries(allStates[countryCode]).forEach(([code, name]) => {
                    html += `<option value="${code}">${name}</option>`;
                });
                html += '</select>';
                stateFieldContainer.innerHTML = html;
            } else {
                // 无州/省列表，显示文本框
                stateFieldContainer.innerHTML = '<input type="text" name="billing_state" placeholder="State/Province">';
            }
        }
    </script>
<?php
    return ob_get_clean();
}
add_shortcode('sample_pack_price_breakdown', function ($atts) {
    ob_start();
?>
    <!-- Right-side price breakdown -->
    <div class="price-breakdown-wrapper">
        <h3>Price Breakdown</h3>
        <p class="justify-between">Sample Pack: <span id="price-sample">$0.00</span></p>
        <p class="justify-between">Shipping: <span id="price-shipping">Calculated at checkout</span></p>
        <p class="justify-between">Tax: <span id="price-tax">Calculated at checkout</span></p>
        <hr class="border-gray-300">
        <p class="total"><span class="text-deep-charcoal">Total:</span> <span class="text-accent-orange" id="price-total">$0.00</span></strong></p>
    </div>
    <style>
        .site-header {
            z-index: 999 !important;
        }

        .border-gray-300 {
            --tw-border-opacity: 1;
            border-color: rgb(209 213 219 / var(--tw-border-opacity, 1));
        }

        .justify-between {
            display: flex;
            flex-direction: row;
            justify-content: space-between;
        }

        #price-sample,
        #price-shipping,
        #price-tax {
            font-weight: 500;
            color: rgb(26, 26, 26);
        }

        .price-breakdown-wrapper {
            padding: 1.5rem;
            border-radius: 0.75rem;
            margin-bottom: 1.5rem;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border: 1px solid #dee2e6;
            padding: 24px;
            font-size: 14px;
            line-height: 20px;
        }

        .price-breakdown-wrapper h3 {
            font-size: 18px;
            font-weight: 600;
            line-height: 28px;
        }

        .price-breakdown-wrapper .total {
            --tw-space-y-reverse: 0;
            margin-top: calc(0.75rem * calc(1 - var(--tw-space-y-reverse)));
            margin-bottom: calc(0.75rem * var(--tw-space-y-reverse));
            font-weight: 600;
            font-size: 1.125rem;
            line-height: 1.75rem;
            justify-content: space-between;
            display: flex;
        }

        .price-breakdown-wrapper .text-deep-charcoal {
            --tw-text-opacity: 1;
            color: rgb(26 26 26 / var(--tw-text-opacity, 1));
        }

        .price-breakdown-wrapper .text-accent-orange {
            --tw-text-opacity: 1;
            color: rgb(230 126 34 / var(--tw-text-opacity, 1));
        }
    </style>
<?php
    return ob_get_clean();
});
add_shortcode('sample_pack_checkout_process', function ($atts) {
    ob_start();
?>
    <style>
        .checkout-steps .step {
            transition: all 0.3s;
            font-weight: 500;
            border-bottom: 5px solid #ccc;
            padding: 10px;
            text-align: center;
            flex: 1;
        }

        .checkout-steps .step.active {
            border-bottom: 5px solid rgb(230 126 34 / var(--tw-bg-opacity, 1)) !important;
            color: rgb(230 126 34 / var(--tw-bg-opacity, 1));
            font-weight: 600;
        }
    </style>
    <!-- Checkout Steps -->
    <div class="checkout-steps" style="display:flex; gap:20px; margin-bottom:30px; justify-content:center;">
        <div class="step active" data-step="1">1. Choose Pack</div>
        <div class="step" data-step="2">2. Shipping Info</div>
        <div class="step" data-step="3">3. Review & Confirm</div>
    </div>
<?php
    return ob_get_clean();
});
/**
 * 指定产品ID的详情页自动跳转到 samples-get 页面
 */
add_action('template_redirect', 'redirect_specific_product_to_sample_page');
function redirect_specific_product_to_sample_page() {
    // 1. 填写需要跳转的产品ID（多个ID用逗号分隔，例如 [123, 456, 789]）
    $target_product_ids = [3178]; // 替换为你的目标产品ID！
    
    // 2. 验证当前页面是否为产品详情页，且产品ID在指定列表中
    if (is_product() && in_array(get_the_ID(), $target_product_ids)) {
        // 3. 跳转到目标页面（302临时跳转，如需永久跳转可改为 301）
        wp_redirect('https://antnewmaterials.com/samples-get/', 302);
        exit; // 必须加 exit 确保跳转生效
    }
}
// Sample Pack Checkout Shortcode
add_shortcode('sample_pack_checkout_choose_pack', function ($atts) {
    if (!class_exists('WC_Product_Variable')) return 'WooCommerce is required.';

    $atts = shortcode_atts([
        'parent_id' => 0,
    ], $atts);

    $parent_id = intval($atts['parent_id']);
    if (!$parent_id) return 'Please set a valid parent product ID.';

    $parent = wc_get_product($parent_id);
    if (!$parent || !$parent->is_type('variable')) return 'Parent product is not variable.';

    // 获取所有变体
    $variations = $parent->get_available_variations();
    if (!$variations) return 'No variations found.';

    // 自动获取 Material Series Add-on
    $addons = [];
    $raw_addons = get_post_meta($parent_id, '_product_addons', true);
    if (!empty($raw_addons) && is_array($raw_addons)) {
        foreach ($raw_addons as $index => $addon) {
            if (!empty($addon['name']) && stripos($addon['name'], 'Material Series') !== false) {
                if (!empty($addon['options']) && is_array($addon['options'])) {
                    $addons[$addon['id']]['name'] = $addon['name'];
                    $addons[$addon['id']]['index'] = $index;
                    foreach ($addon['options'] as $opt) {
                        $addons[$addon['id']]['options'][] = [
                            'label' => $opt['label'],
                            'price' => !empty($opt['price']) ? floatval($opt['price']) : 0
                        ];
                    }
                }
                break;
            }
        }
    }
    $productIds = [];
    $packPrices = [];

    foreach ($variations as $var) {
        $vid = $var['variation_id'];
        $pack = $var['attributes']['attribute_pa_pack-type'] ?? '';
        if (!$pack) continue;
        $productIds[$pack] = $vid;
        $packPrices[$pack] = $var['display_price'];
    }
    // 获取人类可读名称
    $terms = wc_get_product_terms($parent_id, 'pa_pack-type', ['fields' => 'all']);
    $pack_options = [];
    foreach ($terms as $term) {
        $pack_options[$term->slug] = [
            'name' => $term->name,
            'description' => $term->description
        ];
    }

    ob_start();
?>

    <!-- Step 1: Choose Pack -->
    <?php echo _render_step1_choose_pack($parent_id, ['addons' => $addons, 'variations' => $variations, 'pack_options' => $pack_options]); ?>
    <!-- Step 2: Shipping Info -->
    <?php echo _render_step2_shipping_info(); ?>
    <!-- Step 3: Review & Confirm -->
    <?php echo _render_step3_review_comfirm(); ?>

    <div class="step-btn-group" style="display: flex;flex-direction:row;justify-content:space-between;align-items:center;padding:24px 0px;">
        <button id="preview-btn" style="display:none;padding:12px 32px; background:#d1d5db;font-weight:600; color:#374151; border:none; cursor:pointer; border-radius:6px;">Back</button>
        <button id="continue-btn" style="padding:12px 32px; color:white; border:none;font-weight:600; cursor:pointer; border-radius:6px;">Continue</button>
    </div>
    <script>
        jQuery(function($) {
            const parentProductId = <?php echo $parent_id; ?>;
            const productIds = <?php echo json_encode($productIds); ?>;
            go_step(1);

            function go_step(step_num) {
                // 总步骤数
                const total_steps = 3;

                // 1️⃣ 更新步骤导航状态
                $('.checkout-steps .step').each(function() {
                    const step = parseInt($(this).data('step'));
                    if (step <= step_num) {
                        $(this).addClass('active');
                    } else {
                        $(this).removeClass('active');
                    }
                });

                // 2️⃣ 隐藏所有步骤内容块
                $('#choose-pack-page, #shipping-info-step, #review-step').hide();

                // 3️⃣ 显示对应步骤内容
                switch (step_num) {
                    case 1:
                        $('#choose-pack-page').show();
                        $('#preview-btn').hide().attr('data-step', 0);
                        $('#continue-btn').show().text("Continue to Shipping").attr('data-step', 2);
                        break;
                    case 2:
                        $('#shipping-info-step').show();
                        $('#preview-btn').show().text("Back to Selection").attr('data-step', 1);
                        $('#continue-btn').show().text("Review Order").attr('data-step', 3);
                        break;
                    case 3:
                        $('#review-step').show();
                        $('#preview-btn').show().text("Back to Shipping").attr('data-step', 2);
                        $('#continue-btn').hide().attr('data-step', 0);
                        break;
                    default:
                        console.warn('Unknown step number:', step_num);
                }

                // 4️⃣ 更新进度条
                const percent = (step_num / total_steps) * 100;
                $('.checkout-progress').css('width', percent + '%');
            }

            function updatePrice() {
                const selectedPack = $('.pack-card.selected').data('pack');
                if (!selectedPack) {
                    $('#price-sample').text('$0.00');
                    $('#price-total').text('$0.00');
                    return;
                }
                const packPrice = parseFloat($('.pack-card.selected').data('price'));
                let addonTotal = 0;
                $('input[name="series[]"]:checked').each(function() {
                    addonTotal += parseFloat($(this).data('price')) || 0;
                    addonTotal += packPrice;
                });
                const subtotal = addonTotal;
                $('#price-sample').html('$' + subtotal.toFixed(2));
                $('#price-shipping').text('Calculated at checkout');
                $('#price-tax').text('Calculated at checkout');
                $('#price-total').html('$' + subtotal.toFixed(2));
            }

            $('.pack-card').on('click', function() {
                var pack = $(this).data('pack');
                var price = $(this).data('price');
                var variation_id = $(this).data('variation_id'); // 需要在 PHP 输出 data-variation_id

                // 设置隐藏字段
                // $('input[name="variation[attribute_pa_pack-type]"]').val(pack);
                $('input[name="product_id"]').val(variation_id);

                $('.pack-card').removeClass('selected');
                $(this).addClass('selected');
                updatePrice();
            });

            function get_submit_product_data() {
                const selectedPack = $('.pack-card.selected').data('pack');
                if (!selectedPack) {
                    alert('Please select a Pack');
                    return false;
                }
                const selectedSeries = $('input[name="series[]"]:checked').map(function() {
                    return this.value;
                }).get();
                if (selectedSeries.length === 0) {
                    alert('Please select at least one Material Series');
                    return false;
                }
                const variationId = productIds[selectedPack];
                return {
                    product_id: parentProductId,
                    variation_id: variationId,
                    quantity: selectedSeries.length,
                    variation: {
                        'pa_pack-type': selectedPack
                    },
                    series: selectedSeries.join(',')
                };
            }

            $('input[name="series[]"]').on('change', updatePrice);

            // Step1 → Step2
            $('#continue-btn').on('click', function() {
                const $btn = $(this);
                const step = $btn.attr('data-step');
                const oldTxt = $btn.text();
                if (parseInt(step) == 0) {
                    return;
                }
                switch (parseInt(step)) {
                    case 1:
                        $btn.text(oldTxt).prop('disabled', false);
                        return;
                        break;
                    case 2:
                        // ✅ 激活第 2 步
                        $('#choose-pack-form').submit();
                        break;
                    case 3:
                        $btn.text(oldTxt).prop('disabled', false);
                        $('#shipping-form').submit();
                        break;
                }
            });
            $('#preview-btn').on('click', function() {
                const step = $(this).attr('data-step');
                if (parseInt(step) == 0) {
                    return;
                }
                switch (parseInt(step)) {
                    case 1:
                        go_step(1);
                        break;
                    case 2:
                        go_step(2);
                        break;
                    case 3:
                        return;
                        break;
                }

            });

            function check_required(formObj) {
                let isValid = true;
                const error_names = [];
                const checked_names = {}; // 记录已经检查过的同名元素

                $(formObj).find('[required]').each(function() {
                    const $el = $(this);
                    const name = $el.attr('name');

                    // 已经检查过同名 checkbox/radio，跳过
                    if (checked_names[name]) return;
                    checked_names[name] = true;

                    if ($el.is(':checkbox')) {
                        // 同名 checkbox 至少选中一个
                        if (!$(`input[name="${name}"]:checked`).length) {
                            isValid = false;
                            error_names.push(name);
                        }
                    } else if ($el.is(':radio')) {
                        // 同名 radio 至少选中一个
                        if (!$(`input[name="${name}"]:checked`).length) {
                            isValid = false;
                            error_names.push(name);
                        }
                    } else if ($el.is('select')) {
                        // select 选中有效值
                        if (!$el.val() || $el.val() === '') {
                            isValid = false;
                            error_names.push(name);
                        }
                    } else {
                        // 普通文本框、textarea
                        if (!$el.val() || $el.val().trim() === '') {
                            isValid = false;
                            error_names.push(name);
                        }
                    }
                });

                return {
                    'is_valid': isValid,
                    'error_names': error_names
                };
            }


            $('#choose-pack-form').on('submit', function(e) {
                $btn = $('#continue-btn');
                const oldTxt = $btn.text();
                $btn.text('Loading...').prop('disabled', true);
                e.preventDefault();
                // 1️⃣ 校验必填项
                const check = check_required($(this));
                if (!check.is_valid) {
                    $btn.text(oldTxt).prop('disabled', false);
                    if (check.error_names.includes('product_id')) {
                        alert('Please Choose Your Sample Pack');
                    } else if (check.error_names.includes('series[]')) {
                        alert('Please Choose Material Series');
                    }
                    $btn.text(oldTxt).prop('disabled', false);
                    return false;
                }
                const formData = $(this).serializeArray();
                $.post('/?wc-ajax=add_to_cart', formData, function(res) {
                    updatePrice();
                    go_step(2);
                    $btn.text(oldTxt).prop('disabled', false);
                });
                console.log(formData);
            });
            // Step2 → Step3
            $('#shipping-form').on('submit', function(e) {
                $btn = $('#continue-btn');
                const oldTxt = $btn.text();
                $btn.text('Loading...').prop('disabled', true);
                e.preventDefault();
                // 1️⃣ 校验必填项
                let isValid = true;
                let firstInvalid = null;
                $(this).find('[required]').each(function() {
                    const value = $(this).val().trim();
                    if (!value) {
                        isValid = false;
                        $(this).addClass('input-error'); // 添加红色边框样式
                        if (!firstInvalid) firstInvalid = this;
                    } else {
                        $(this).removeClass('input-error');
                    }
                });
                if (!isValid) {
                    if (firstInvalid) firstInvalid.focus();
                    $btn.text(oldTxt).prop('disabled', false);
                    return false;
                }
                const formData = $(this).serializeArray();
                const data = {};
                formData.forEach(item => {
                    data[item.name] = item.value;
                });
                const cart_data = get_submit_product_data();
                if (cart_data == false) {
                    return;
                }
                const billData = {
                    action: 'update_shipping_info'
                };
                formData.forEach(item => {
                    billData[item.name] = item.value;
                });
                const all_data = {
                    ...billData,
                    ...cart_data
                };
                $.post('/wp-admin/admin-ajax.php', all_data, function(res) {
                    if (!res.success) {
                        $btn.text(oldTxt).prop('disabled', false);
                        return;
                    }

                    function decodeHtmlEntities(str) {
                        const txt = document.createElement('textarea');
                        txt.innerHTML = str;
                        return txt.value;
                    }
                    const currency = decodeHtmlEntities(res.data?.currency);
                    $('#price-sample').text(currency + res.data?.subtotal.toFixed(2));
                    $('#price-shipping').text(currency + res.data?.shipping.toFixed(2));
                    $('#price-tax').text(currency + res.data?.tax.toFixed(2));
                    $('#price-total').text(currency + res.data?.total.toFixed(2));

                    // 调用后端接口
                    $.ajax({
                        url: '<?php echo admin_url('admin-ajax.php'); ?>',
                        method: 'POST',
                        data: {
                            action: 'get_checkout_preview',
                            form_data: all_data
                        },
                        beforeSend: function() {
                            $('#to-step-3').text('Loading...').prop('disabled', true);
                        },
                        success: function(res) {
                            $('#to-step-3').text('Continue to Review & Confirm').prop('disabled', false);
                            if (res.success) {
                                // 隐藏 Step 2
                                go_step(3);

                                // 注入返回的HTML
                                $('#shipping-info').html(res.data.shippingInfo);
                                $('#order-summary').html(res.data.orderSummary);
                                $('#payment-method').html(res.data.paymentMethod);
                                $btn.text(oldTxt).prop('disabled', false);
                            } else {
                                alert(res.data.message || 'Failed to load order summary.');
                                $btn.text(oldTxt).prop('disabled', false);
                            }
                        },
                        error: function(xhr, status, error) {
                            $('#to-step-3').text('Continue to Review & Confirm').prop('disabled', false);
                            alert('Error loading checkout preview: ' + error);
                            console.error(xhr.responseText); // 查看具体错误信息
                            $btn.text(oldTxt).prop('disabled', false);
                        }
                    });
                    go_step(3);
                });

            });

            $(document).on('click', '#place_order', function(e) {
                e.preventDefault(); // ✅ 阻止表单默认提交
                e.stopPropagation(); // ✅ 防止事件冒泡（有时必要）

                const $btn = $(this);
                const oldTxt = $btn.text();
                $btn.text('Processing...').prop('disabled', true);

                // ✅ 收集账单信息
                const billData = {
                    billing_first_name: $('input[name="billing_first_name"]').val() || 'N/A',
                    billing_last_name: $('input[name="billing_last_name"]').val() || 'N/A',
                    billing_email: $('input[name="billing_email"]').val() || 'no-reply@example.com',
                    billing_company: $('input[name="billing_company"]').val() || '',
                    billing_phone: $('input[name="billing_phone"]').val() || '',
                    billing_country: $('select[name="billing_country"]').val() || 'CN',
                    billing_address_1: $('input[name="billing_address_1"]').val() || '',
                    billing_address_2: $('input[name="billing_address_2"]').val() || '',
                    billing_city: $('input[name="billing_city"]').val() || '',
                    billing_state: $('select[name="billing_state"]').val() || 'Guangdong', // 必须是 WooCommerce 支持的州/省
                    billing_postcode: $('input[name="billing_postcode"]').val() || '',
                    order_comments: $('textarea[name="order_comments"]').val() || '',
                    terms: 1,
                    'woocommerce-process-checkout-nonce': $('input[name="woocommerce-process-checkout-nonce"]').val(),
                    _wp_http_referer: $('input[name="_wp_http_referer"]').val()
                };

                // ✅ 序列化其他表单字段（如 product_id, variation_id, addon 等）
                const formData = $("#confirm-order-form").serializeArray();

                // 合并数据，避免重复字段覆盖
                const allData = {};
                formData.forEach(field => {
                    allData[field.name] = field.value;
                });
                const postData = {
                    ...allData,
                    ...billData
                };
                console.log("formdata", allData, "billData", billData, "postData2", postData);
                $.post('/?wc-ajax=checkout', postData, function(res) {
                    if (res.result === 'success') {
                        window.location.href = res.redirect;
                    } else {
                        $btn.text(oldTxt).prop('disabled', false);
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(res.messages, 'text/html');
                        const errors = [...doc.querySelectorAll('li')].map(li => li.textContent.trim());
                        alert(errors.join('\n'));
                    }
                }).fail(function() {
                    $btn.text(oldTxt).prop('disabled', false);
                    alert('Checkout failed, please try again.');
                });

                return false; // ✅ 额外保险
            });


            updatePrice();
        });
    </script>
<?php
    return ob_get_clean();
});

add_action('wp_ajax_update_shipping_info', 'update_shipping_info');
add_action('wp_ajax_nopriv_update_shipping_info', 'update_shipping_info');
function update_shipping_info()
{
    nocache_headers();

    // 确保 WC cart 已可用
    if (! function_exists('wc_load_cart')) {
        wp_send_json_error('WooCommerce not loaded', 500);
    }
    if (! WC()->cart) {
        wc_load_cart();
    }
    // 必要字段
    $required_fields = ['product_id', 'variation_id', 'variation', 'series'];
    foreach ($required_fields as $f) {
        if (empty($_POST[$f])) {
            wp_send_json_error("Missing field: {$f}", 400);
        }
    }

    // 解析并清理输入
    $product_id    = intval($_POST['product_id']);          // variable product ID
    $variation_id = intval($_POST['variation_id']);       // specific variation id
    // variation 需为关联数组，前端传 JSON 字符串时需要 json_decode
    $variation_raw = $_POST['variation'];
    if (is_string($variation_raw)) {
        $variation = json_decode(stripslashes($variation_raw), true);
    } else {
        $variation = (array) $variation_raw;
    }

    // series 可以是数组或逗号分隔字符串
    $series_raw = $_POST['series'];
    if (is_string($series_raw)) {
        $series = array_filter(array_map('trim', explode(',', $series_raw)));
    } elseif (is_array($series_raw)) {
        $series = array_filter(array_map('sanitize_text_field', $series_raw));
    } else {
        $series = [];
    }

    if (empty($series)) {
        wp_send_json_error('Please select at least one Material Series', 400);
    }

    // 清空购物车（确保只有当前 pack 被计入）
    WC()->cart->empty_cart();

    // quantity = series 数量（你的需求）
    $quantity = count($series);

    // 准备 cart item data（保存 series 信息）
    $cart_item_data = [
        'series_meta' => implode(',', $series),
    ];

    // 将变体加入购物车（父商品 ID, 数量, variation_id, variation attributes, cart_item_data）
    $added = WC()->cart->add_to_cart($product_id, $quantity, $variation_id, $variation, $cart_item_data);

    if (! $added) {
        wp_send_json_error('Failed to add product to cart', 500);
    }

    // 更新 billing 信息（可选字段）
    $fields = ['billing_first_name', 'billing_address_1', 'billing_city', 'billing_state', 'billing_postcode', 'billing_country', 'billing_email', 'billing_phone'];
    foreach ($fields as $field) {
        if (!empty($_POST[$field])) {
            $value = sanitize_text_field($_POST[$field]);
            // 使用 WC_Customer 的 set_billing_* 方法 则方法名为 set_billing_first_name 等
            if (method_exists(WC()->customer, "set_{$field}")) {
                WC()->customer->{"set_{$field}"}($value);
            } else {
                // 回退：保存到 session
                WC()->session->set($field, $value);
            }
        }
    }
    $shipping_fields = ['first_name', 'last_name', 'address_1', 'address_2', 'city', 'state', 'postcode', 'country'];
    foreach ($shipping_fields as $field) {
        $billing_field = "billing_" . $field;
        if (!empty($_POST[$billing_field])) {
            $value = sanitize_text_field($_POST[$billing_field]);
            if (method_exists(WC()->customer, "set_shipping_{$field}")) {
                WC()->customer->{"set_shipping_{$field}"}($value);
            } else {
                // 如果 WC_Customer 不存在对应方法，可以存 session
                WC()->session->set("shipping_{$field}", $value);
            }
        }
    }

    WC()->customer->save(); // 保存到数据库
    // 重新计算 totals 并返回数字
    WC()->cart->calculate_totals();
    $totals = WC()->cart->get_totals();

    wp_send_json_success([
        'subtotal' => floatval($totals['subtotal']),
        'shipping' => floatval($totals['shipping_total']),
        'tax'      => floatval($totals['total_tax']),
        'total'    => floatval($totals['total']),
        'currency' => get_woocommerce_currency_symbol(),
    ]);
}

// AJAX：获取结账预览 HTML
add_action('wp_ajax_get_checkout_preview', 'get_checkout_preview');
add_action('wp_ajax_nopriv_get_checkout_preview', 'get_checkout_preview');

function get_checkout_preview()
{
    if (!WC()->cart || WC()->cart->is_empty()) {
        wp_send_json_error(['message' => 'Cart is empty']);
    }
    // 初始化 Checkout 对象
    if (! isset(WC()->checkout)) {
        WC()->checkout();
    }
    $customer = WC()->customer;
    if (! $customer) {
        wp_send_json_error(['message' => 'Customer not found.']);
    }

    // 获取地址数组
    $shipping_address_array = array(
        'first_name' => $customer->get_shipping_first_name(),
        'last_name'  => $customer->get_shipping_last_name(),
        'company'    => $customer->get_shipping_company(),
        'address_1'  => $customer->get_shipping_address_1(),
        'address_2'  => $customer->get_shipping_address_2(),
        'city'       => $customer->get_shipping_city(),
        'state'      => $customer->get_shipping_state(),
        'postcode'   => $customer->get_shipping_postcode(),
        'country'    => $customer->get_shipping_country(),
    );

    // 获取 WooCommerce 默认格式 HTML
    $formatted_shipping = WC()->countries->get_formatted_address($shipping_address_array);

    if (! $formatted_shipping) {
        $formatted_shipping = __('No shipping address set.', 'woocommerce');
    }
    ob_start();
    woocommerce_order_review();
    $order_summary = ob_get_clean();
    ob_start();
    woocommerce_checkout_payment();
    $payment_method = ob_get_clean();
    wp_send_json_success([
        'shippingInfo' => $formatted_shipping,
        'orderSummary' => $order_summary,
        'paymentMethod' => $payment_method,
    ]);
}
