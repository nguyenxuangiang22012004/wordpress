<?php
if (!defined('ABSPATH')) {
    exit;
}

// Import namespaces của PhpSpreadsheet
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Khởi tạo module product management
function glp_product_management_init()
{
    // Đăng ký shortcode để hiển thị sản phẩm
    add_shortcode('gym_products', 'glp_render_products');

    // Đăng ký action để xử lý đặt hàng
    add_action('admin_post_nopriv_glp_order_submit', 'glp_handle_order_submit');
    add_action('admin_post_glp_order_submit', 'glp_handle_order_submit');

    // Đăng ký action để xử lý giỏ hàng
    add_action('wp_ajax_glp_add_to_cart', 'glp_add_to_cart');
    add_action('wp_ajax_nopriv_glp_add_to_cart', 'glp_add_to_cart');
    add_action('wp_ajax_glp_remove_from_cart', 'glp_remove_from_cart');
    add_action('wp_ajax_nopriv_glp_remove_from_cart', 'glp_remove_from_cart');
    add_action('wp_ajax_glp_update_cart_quantity', 'glp_update_cart_quantity');
    add_action('wp_ajax_nopriv_glp_update_cart_quantity', 'glp_update_cart_quantity');

    // Thêm action cho export orders
    add_action('admin_post_glp_export_orders', 'glp_handle_export_orders');
}
add_action('init', 'glp_product_management_init');

// Khởi tạo session để lưu giỏ hàng
add_action('init', 'glp_start_session', 1);
function glp_start_session()
{
    if (!session_id()) {
        session_start();
    }

    // Khởi tạo giỏ hàng nếu chưa có
    if (!isset($_SESSION['glp_cart'])) {
        $_SESSION['glp_cart'] = array();
    }
}

// Xử lý thêm sản phẩm vào giỏ hàng
function glp_add_to_cart()
{
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : -1;
    $products = get_option('glp_products', array());

    if (isset($products[$product_id])) {
        $cart = $_SESSION['glp_cart'];
        if (isset($cart[$product_id])) {
            $cart[$product_id]['quantity'] += 1;
        } else {
            $cart[$product_id] = array(
                'product_id' => $product_id,
                'name' => $products[$product_id]['name'],
                'price' => $products[$product_id]['price'],
                'quantity' => 1
            );
        }
        $_SESSION['glp_cart'] = $cart;
        wp_send_json_success(array('message' => 'Đã thêm sản phẩm vào giỏ hàng!'));
    } else {
        wp_send_json_error(array('message' => 'Sản phẩm không tồn tại.'));
    }
    wp_die();
}

// Xử lý xóa sản phẩm khỏi giỏ hàng
function glp_remove_from_cart()
{
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : -1;
    $cart = $_SESSION['glp_cart'];

    if (isset($cart[$product_id])) {
        unset($cart[$product_id]);
        $_SESSION['glp_cart'] = $cart;
        wp_send_json_success(array('message' => 'Đã xóa sản phẩm khỏi giỏ hàng!'));
    } else {
        wp_send_json_error(array('message' => 'Sản phẩm không tồn tại trong giỏ hàng.'));
    }
    wp_die();
}

// Xử lý cập nhật số lượng sản phẩm trong giỏ hàng
function glp_update_cart_quantity()
{
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'glp_cart_nonce')) {
        wp_send_json_error(array('message' => 'Yêu cầu không hợp lệ.'));
        wp_die();
    }

    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : -1;
    $quantity = isset($_POST['quantity']) ? max(1, intval($_POST['quantity'])) : 1;
    $cart = $_SESSION['glp_cart'];

    if (isset($cart[$product_id])) {
        $cart[$product_id]['quantity'] = $quantity;
        $_SESSION['glp_cart'] = $cart;
        $subtotal = $cart[$product_id]['price'] * $quantity;

        $total_price = 0;
        foreach ($cart as $item) {
            $total_price += $item['price'] * $item['quantity'];
        }

        wp_send_json_success(array(
            'message' => 'Đã cập nhật số lượng!',
            'subtotal' => number_format($subtotal, 0, ',', '.') . ' VNĐ',
            'total_price' => number_format($total_price, 0, ',', '.') . ' VNĐ'
        ));
    } else {
        wp_send_json_error(array('message' => 'Sản phẩm không tồn tại trong giỏ hàng.'));
    }
    wp_die();
}

// Hiển thị danh sách sản phẩm frontend
function glp_render_products()
{
    $products = get_option('glp_products', array());
    $order_submitted = isset($_GET['order_submitted']);
    $order_error = isset($_GET['order_error']);
    $errors = get_transient('glp_order_form_errors');
    if ($errors) {
        delete_transient('glp_order_form_errors');
    }

    $search_query = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
    if (!empty($search_query)) {
        $filtered_products = array();
        foreach ($products as $index => $product) {
            if (
                stripos($product['name'], $search_query) !== false ||
                stripos($product['description'], $search_query) !== false
            ) {
                $filtered_products[$index] = $product;
            }
        }
        $products = $filtered_products;
    }

    ob_start();
    include GLP_PLUGIN_DIR . 'includes/modules/product-management/templates/products.php';
    return ob_get_clean();
}

// Xử lý đặt hàng từ giỏ hàng
function glp_handle_order_submit()
{
    ob_start();

    if (!isset($_POST['glp_order_nonce']) || !wp_verify_nonce($_POST['glp_order_nonce'], 'glp_order_form_nonce')) {
        set_transient('glp_order_form_errors', ['Yêu cầu không hợp lệ. Vui lòng thử lại.'], 30);
        wp_redirect(add_query_arg('order_error', '1', wp_get_referer()));
        exit;
    }

    $customer_name = sanitize_text_field($_POST['customer_name']);
    $customer_phone = sanitize_text_field($_POST['customer_phone']);
    $customer_address = sanitize_textarea_field($_POST['customer_address']);

    $errors = [];
    if (empty($customer_name) || strlen($customer_name) < 2 || strlen($customer_name) > 50) {
        $errors[] = 'Vui lòng nhập tên hợp lệ (2-50 ký tự).';
    }
    if (empty($customer_phone) || !preg_match('/^[0-9]{10,15}$/', $customer_phone)) {
        $errors[] = 'Vui lòng nhập số điện thoại hợp lệ (10-15 số).';
    }
    if (empty($customer_address) || strlen($customer_address) < 10 || strlen($customer_address) > 500) {
        $errors[] = 'Địa chỉ phải từ 10-500 ký tự.';
    }

    $cart = $_SESSION['glp_cart'];
    if (empty($cart)) {
        $errors[] = 'Giỏ hàng của bạn đang trống. Vui lòng thêm sản phẩm trước khi đặt hàng.';
    }

    if (!empty($errors)) {
        set_transient('glp_order_form_errors', $errors, 30);
        wp_redirect(add_query_arg('order_error', '1', wp_get_referer()));
        exit;
    }

    $order = array(
        'products' => $cart,
        'customer_name' => $customer_name,
        'customer_phone' => $customer_phone,
        'customer_address' => $customer_address,
        'date' => current_time('mysql'),
        'status' => 'new'
    );

    $orders = get_option('glp_orders', array());
    $orders[] = $order;
    update_option('glp_orders', $orders);

    $to = get_option('admin_email');
    $subject = 'Có đơn hàng mới từ website';
    $message_email = "Thông tin đơn hàng mới:\n\n";
    $message_email .= "Danh sách sản phẩm:\n";
    $total_price = 0;
    foreach ($cart as $item) {
        $subtotal = $item['price'] * $item['quantity'];
        $total_price += $subtotal;
        $message_email .= "- {$item['name']} (x{$item['quantity']}): " . number_format($subtotal, 0, ',', '.') . " VNĐ\n";
    }
    $message_email .= "Tổng tiền: " . number_format($total_price, 0, ',', '.') . " VNĐ\n\n";
    $message_email .= "Tên khách hàng: {$order['customer_name']}\n";
    $message_email .= "Số điện thoại: {$order['customer_phone']}\n";
    $message_email .= "Địa chỉ: {$order['customer_address']}\n";
    $message_email .= "Ngày đặt: {$order['date']}\n";
    wp_mail($to, $subject, $message_email);

    $_SESSION['glp_cart'] = array();

    wp_redirect(add_query_arg('order_submitted', '1', wp_get_referer()));
    exit;
}

// Xử lý export đơn hàng sang Excel
function glp_handle_export_orders()
{
    // Kiểm tra nonce
    if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'glp_export_orders_action')) {
        wp_die('Yêu cầu không hợp lệ.');
    }

    // Kiểm tra quyền truy cập
    if (!current_user_can('manage_options')) {
        wp_die('Bạn không có quyền thực hiện hành động này.');
    }

    $orders = get_option('glp_orders', array());

    if (!empty($orders)) {
        // Tạo spreadsheet mới
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Đặt tiêu đề cột
        $sheet->setCellValue('A1', 'STT');
        $sheet->setCellValue('B1', 'Sản Phẩm');
        $sheet->setCellValue('C1', 'Tổng Tiền (VNĐ)');
        $sheet->setCellValue('D1', 'Tên Khách Hàng');
        $sheet->setCellValue('E1', 'Số Điện Thoại');
        $sheet->setCellValue('F1', 'Địa Chỉ');
        $sheet->setCellValue('G1', 'Ngày Đặt');
        $sheet->setCellValue('H1', 'Trạng Thái');

        // Điền dữ liệu
        $row = 2;
        foreach ($orders as $index => $order) {
            $sheet->setCellValue('A' . $row, $index + 1);

            // Danh sách sản phẩm
            $product_list = [];
            foreach ($order['products'] as $item) {
                $product_list[] = "{$item['name']} (x{$item['quantity']})";
            }
            $sheet->setCellValue('B' . $row, implode(', ', $product_list));

            // Tổng tiền
            $total_price = 0;
            foreach ($order['products'] as $item) {
                $total_price += $item['price'] * $item['quantity'];
            }
            $sheet->setCellValue('C' . $row, number_format($total_price, 0, ',', '.'));

            $sheet->setCellValue('D' . $row, $order['customer_name']);
            $sheet->setCellValue('E' . $row, $order['customer_phone']);
            $sheet->setCellValue('F' . $row, $order['customer_address']);
            $sheet->setCellValue('G' . $row, date('d/m/Y H:i', strtotime($order['date'])));
            $sheet->setCellValue('H' . $row, $order['status'] === 'new' ? 'Mới' : ($order['status'] === 'contacted' ? 'Đã liên hệ' : 'Đã chốt'));
            $row++;
        }

        // Tạo file Excel và gửi về trình duyệt
        $writer = new Xlsx($spreadsheet);
        $filename = 'don-hang-' . date('Y-m-d-H-i-s') . '.xlsx';

        // Đặt header để tải file
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Cache-Control: cache, must-revalidate');
        header('Pragma: public');

        // Ghi file và thoát
        $writer->save('php://output');
        exit;
    } else {
        // Nếu không có đơn hàng, redirect về trang trước với thông báo
        wp_redirect(add_query_arg('export_error', 'no_orders', wp_get_referer()));
        exit;
    }
}

// Hiển thị trang quản lý sản phẩm trong admin
function glp_render_products_admin_page()
{
    if (isset($_POST['glp_add_product']) && check_admin_referer('glp_add_product_action')) {
        $name = sanitize_text_field($_POST['product_name']);
        $price = floatval($_POST['product_price']);
        $description = sanitize_textarea_field($_POST['product_description']);
        $image = esc_url_raw($_POST['product_image']);
        $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : -1;

        $products = get_option('glp_products', array());

        if ($product_id >= 0 && isset($products[$product_id])) {
            $products[$product_id] = array(
                'name' => $name,
                'price' => $price,
                'description' => $description,
                'image' => $image
            );
            update_option('glp_products', $products);
            echo '<div class="notice notice-success"><p>Đã cập nhật sản phẩm thành công!</p></div>';
        } else {
            $products[] = array(
                'name' => $name,
                'price' => $price,
                'description' => $description,
                'image' => $image
            );
            update_option('glp_products', $products);
            echo '<div class="notice notice-success"><p>Đã thêm sản phẩm thành công!</p></div>';
        }
    }

    if (isset($_POST['glp_delete_product']) && check_admin_referer('glp_delete_product_action')) {
        $products = get_option('glp_products', array());
        $index = intval($_POST['product_index']);
        if (isset($products[$index])) {
            unset($products[$index]);
            $products = array_values($products);
            update_option('glp_products', $products);
            echo '<div class="notice notice-success"><p>Đã xóa sản phẩm thành công!</p></div>';
        }
    }

    $products = get_option('glp_products', array());
    $edit_product = null;
    if (isset($_GET['edit_product'])) {
        $edit_index = intval($_GET['edit_product']);
        if (isset($products[$edit_index])) {
            $edit_product = $products[$edit_index];
            $edit_product['id'] = $edit_index;
        }
    }

    wp_enqueue_media();
?>
    <div class="wrap">
        <h1 class="wp-heading-inline">Quản lý Sản Phẩm</h1>

        <form method="post" style="margin-bottom: 20px;">
            <?php wp_nonce_field('glp_add_product_action'); ?>
            <table class="form-table">
                <tr>
                    <th><label for="product_name">Tên Sản Phẩm</label></th>
                    <td><input type="text" name="product_name" id="product_name" value="<?php echo isset($edit_product) ? esc_attr($edit_product['name']) : ''; ?>" required></td>
                </tr>
                <tr>
                    <th><label for="product_price">Giá (VNĐ)</label></th>
                    <td><input type="number" name="product_price" id="product_price" value="<?php echo isset($edit_product) ? esc_attr($edit_product['price']) : ''; ?>" required step="1000"></td>
                </tr>
                <tr>
                    <th><label for="product_description">Mô Tả</label></th>
                    <td><textarea name="product_description" id="product_description" rows="5" required><?php echo isset($edit_product) ? esc_textarea($edit_product['description']) : ''; ?></textarea></td>
                </tr>
                <tr>
                    <th><label for="product_image">Hình Ảnh Sản Phẩm</label></th>
                    <td>
                        <input type="text" name="product_image" id="product_image" value="<?php echo isset($edit_product) ? esc_attr($edit_product['image']) : ''; ?>" class="regular-text">
                        <input type="button" name="upload-btn" id="upload-btn" class="button-secondary" value="Chọn Hình Ảnh">
                        <p class="description">Chọn hình ảnh từ thư viện media hoặc nhập URL hình ảnh.</p>
                        <?php if (isset($edit_product) && !empty($edit_product['image'])): ?>
                            <img src="<?php echo esc_url($edit_product['image']); ?>" style="max-width: 200px; height: auto; margin-top: 10px;">
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
            <?php if (isset($edit_product)): ?>
                <input type="hidden" name="product_id" value="<?php echo $edit_product['id']; ?>">
                <button type="submit" name="glp_add_product" class="button button-primary">Cập Nhật Sản Phẩm</button>
                <a href="?page=gym-landing-settings&tab=products" class="button">Hủy</a>
            <?php else: ?>
                <button type="submit" name="glp_add_product" class="button button-primary">Thêm Sản Phẩm</button>
            <?php endif; ?>
        </form>

        <?php if (empty($products)): ?>
            <div class="notice notice-info">
                <p>Chưa có sản phẩm nào.</p>
            </div>
        <?php else: ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>STT</th>
                        <th>Hình Ảnh</th>
                        <th>Tên Sản Phẩm</th>
                        <th>Giá (VNĐ)</th>
                        <th>Mô Tả</th>
                        <th>Hành Động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $index => $product): ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>
                            <td>
                                <?php if (!empty($product['image'])): ?>
                                    <img src="<?php echo esc_url($product['image']); ?>" style="max-width: 50px; height: auto;">
                                <?php else: ?>
                                    Chưa có hình ảnh
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html($product['name']); ?></td>
                            <td><?php echo number_format($product['price'], 0, ',', '.'); ?></td>
                            <td><?php echo esc_html($product['description']); ?></td>
                            <td>
                                <a href="?page=gym-landing-settings&tab=products&edit_product=<?php echo $index; ?>" class="button">Sửa</a>
                                <form method="post" style="display: inline;">
                                    <?php wp_nonce_field('glp_delete_product_action'); ?>
                                    <input type="hidden" name="product_index" value="<?php echo $index; ?>">
                                    <button type="submit" name="glp_delete_product" class="button button-secondary" onclick="return confirm('Bạn chắc chắn muốn xóa sản phẩm này?');">Xóa</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#upload-btn').click(function(e) {
                e.preventDefault();
                var image = wp.media({
                        title: 'Chọn Hình Ảnh Sản Phẩm',
                        multiple: false
                    }).open()
                    .on('select', function() {
                        var uploaded_image = image.state().get('selection').first();
                        var image_url = uploaded_image.toJSON().url;
                        $('#product_image').val(image_url);
                    });
            });
        });
    </script>
<?php
}

// Hiển thị trang quản lý đơn hàng trong admin
function glp_render_orders_admin_page()
{
    // Xử lý xóa đơn hàng
    if (isset($_POST['glp_delete_order']) && check_admin_referer('glp_delete_order_action')) {
        $orders = get_option('glp_orders', array());
        $index = intval($_POST['order_index']);
        if (isset($orders[$index])) {
            unset($orders[$index]);
            $orders = array_values($orders);
            update_option('glp_orders', $orders);
            echo '<div class="notice notice-success"><p>Đã xóa đơn hàng thành công!</p></div>';
        }
    }

    // Hiển thị thông báo lỗi nếu không có đơn hàng để export
    if (isset($_GET['export_error']) && $_GET['export_error'] === 'no_orders') {
        echo '<div class="notice notice-error"><p>Không có đơn hàng nào để export!</p></div>';
    }

    $orders = get_option('glp_orders', array());
?>
    <div class="wrap">
        <h1 class="wp-heading-inline">Quản lý Đơn Hàng</h1>

        <!-- Nút Export to Excel -->
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin-bottom: 20px;">
            <input type="hidden" name="action" value="glp_export_orders">
            <?php wp_nonce_field('glp_export_orders_action'); ?>
            <button type="submit" class="button button-primary">Export to Excel</button>
        </form>

        <?php if (empty($orders)): ?>
            <div class="notice notice-info">
                <p>Chưa có đơn hàng nào.</p>
            </div>
        <?php else: ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>STT</th>
                        <th>Sản Phẩm</th>
                        <th>Tổng Tiền (VNĐ)</th>
                        <th>Tên Khách Hàng</th>
                        <th>Số Điện Thoại</th>
                        <th>Địa Chỉ</th>
                        <th>Ngày Đặt</th>
                        <th>Trạng Thái</th>
                        <th>Hành Động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $index => $order): ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>
                            <td>
                                <?php
                                $product_list = [];
                                foreach ($order['products'] as $item) {
                                    $product_list[] = "{$item['name']} (x{$item['quantity']})";
                                }
                                echo esc_html(implode(', ', $product_list));
                                ?>
                            </td>
                            <td>
                                <?php
                                $total_price = 0;
                                foreach ($order['products'] as $item) {
                                    $total_price += $item['price'] * $item['quantity'];
                                }
                                echo number_format($total_price, 0, ',', '.');
                                ?>
                            </td>
                            <td><?php echo esc_html($order['customer_name']); ?></td>
                            <td><?php echo esc_html($order['customer_phone']); ?></td>
                            <td><?php echo esc_html($order['customer_address']); ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($order['date'])); ?></td>
                            <td>
                                <span class="glp-status-badge <?php echo esc_attr($order['status']); ?>">
                                    <?php
                                    echo $order['status'] === 'new' ? 'Mới' : ($order['status'] === 'contacted' ? 'Đã liên hệ' : 'Đã chốt');
                                    ?>
                                </span>
                            </td>
                            <td>
                                <form method="post">
                                    <?php wp_nonce_field('glp_delete_order_action'); ?>
                                    <input type="hidden" name="order_index" value="<?php echo $index; ?>">
                                    <button type="submit" name="glp_delete_order" class="button button-secondary" onclick="return confirm('Bạn chắc chắn muốn xóa đơn hàng này?');">
                                        Xóa
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
<?php
}

// Load Bootstrap và script cho frontend
add_action('wp_enqueue_scripts', 'glp_product_management_enqueue_scripts');
function glp_product_management_enqueue_scripts()
{
    if (has_shortcode(get_post()->post_content, 'gym_products')) {
        wp_enqueue_style('bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css', array(), '5.0.2');
        wp_enqueue_script('bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js', array('jquery'), '5.0.2', true);

        // Log để kiểm tra đường dẫn
        error_log('Cart.js URL: ' . plugin_dir_url(__FILE__) . 'cart.js');

        wp_enqueue_script('glp-cart', plugin_dir_url(__FILE__) . 'cart.js', array('jquery'), '1.0', true);
        wp_localize_script('glp-cart', 'glp_cart_params', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('glp_cart_nonce')
        ));
    }
}

// Load script cho admin
add_action('admin_enqueue_scripts', 'glp_product_management_admin_scripts');
function glp_product_management_admin_scripts($hook)
{
    if ($hook === 'toplevel_page_gym-landing-settings') {
        wp_enqueue_script('jquery');
    }
}
