<?php
if (!defined('ABSPATH')) {
    exit;
}

// Import namespaces của PhpSpreadsheet và PHPWord
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;

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

    // Thêm action cho xuất hóa đơn Word
    add_action('admin_post_glp_export_invoice', 'glp_handle_export_invoice');

    // Đăng ký action xử lý callback từ VNPay
    add_action('init', 'glp_handle_vnpay_callback');
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

// Hàm tạo URL thanh toán VNPay
function glp_create_vnpay_payment_url($order_id, $total_price)
{
    $vnp_TmnCode = "QFASPMTW"; // Thay bằng mã của bạn
    $vnp_HashSecret = "640PQ2BSF3SVBO1O6FC13R29ZWWNUIQW"; // Thay bằng secret của bạn
    $vnp_Url = "https://sandbox.vnpayment.vn/paymentv2/vpcpay.html"; // URL sandbox, thay bằng URL production nếu cần
    $vnp_Returnurl = home_url('/?vnpay_return=1'); // URL callback sau khi thanh toán

    $vnp_TxnRef = time() . '_' . $order_id; // Mã giao dịch duy nhất
    $vnp_OrderInfo = "Thanh toan don hang #$order_id";
    $vnp_OrderType = 'billpayment';
    $vnp_Amount = $total_price * 100; // Số tiền (VNĐ) nhân 100 theo yêu cầu của VNPay
    $vnp_Locale = 'vn';
    $vnp_IpAddr = $_SERVER['REMOTE_ADDR'];
    $vnp_CreateDate = date('YmdHis');

    $inputData = array(
        "vnp_Version" => "2.1.0",
        "vnp_TmnCode" => $vnp_TmnCode,
        "vnp_Amount" => $vnp_Amount,
        "vnp_Command" => "pay",
        "vnp_CreateDate" => $vnp_CreateDate,
        "vnp_CurrCode" => "VND",
        "vnp_IpAddr" => $vnp_IpAddr,
        "vnp_Locale" => $vnp_Locale,
        "vnp_OrderInfo" => $vnp_OrderInfo,
        "vnp_OrderType" => $vnp_OrderType,
        "vnp_ReturnUrl" => $vnp_Returnurl,
        "vnp_TxnRef" => $vnp_TxnRef,
    );

    ksort($inputData);
    $query = "";
    $i = 0;
    $hashdata = "";
    foreach ($inputData as $key => $value) {
        if ($i == 1) {
            $hashdata .= '&' . urlencode($key) . "=" . urlencode($value);
        } else {
            $hashdata .= urlencode($key) . "=" . urlencode($value);
            $i = 1;
        }
        $query .= urlencode($key) . "=" . urlencode($value) . '&';
    }

    $vnp_Url = $vnp_Url . "?" . $query;
    $vnpSecureHash = hash_hmac("sha512", $hashdata, $vnp_HashSecret);
    $vnp_Url .= 'vnp_SecureHash=' . $vnpSecureHash;

    // Debug URL
    error_log('VNPay URL: ' . $vnp_Url);

    return $vnp_Url;
}
function glp_test_update_order()
{
    if (isset($_GET['test_update_order']) && current_user_can('manage_options')) {
        $order_id = 7;

        // Lấy thông tin kết nối từ wp-config.php
        $db_host = DB_HOST;
        $db_user = DB_USER;
        $db_password = DB_PASSWORD;
        $db_name = DB_NAME;

        // Tạo kết nối trực tiếp
        $conn = new mysqli($db_host, $db_user, $db_password, $db_name);
        if ($conn->connect_error) {
            error_log('Direct connection failed: ' . $conn->connect_error);
            return;
        }
        error_log('Direct connection successful');

        // Chuẩn bị truy vấn
        $table_name = $GLOBALS['wpdb']->prefix . 'glp_orders';
        $stmt = $conn->prepare("UPDATE $table_name SET payment_status = ?, transaction_id = ? WHERE order_id = ?");
        $payment_status = 'completed';
        $transaction_id = '14926811';
        $stmt->bind_param('ssi', $payment_status, $transaction_id, $order_id);

        // Thực thi truy vấn
        if ($stmt->execute()) {
            error_log('Direct mysqli update from admin for Order ID ' . $order_id . ': Success');
        } else {
            error_log('Direct mysqli update from admin for Order ID ' . $order_id . ': Failed - ' . $stmt->error);
        }

        // Đóng kết nối
        $stmt->close();
        $conn->close();

        // Kiểm tra dữ liệu sau khi cập nhật (dùng $wpdb để đọc)
        global $wpdb;
        $updated_order = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE order_id = %d", $order_id), ARRAY_A);
        error_log('Order data after direct mysqli update for ID ' . $order_id . ': ' . print_r($updated_order, true));
    }
}
add_action('init', 'glp_test_update_order');
function glp_handle_vnpay_callback()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'glp_orders';

    error_log('Database prefix: ' . $wpdb->prefix);
    error_log('Table name: ' . $table_name);

    if (isset($_GET['vnpay_return']) && $_GET['vnpay_return'] == '1') {
        $vnp_HashSecret = "640PQ2BSF3SVBO1O6FC13R29ZWWNUIQW";
        $vnp_SecureHash = $_GET['vnp_SecureHash'];
        $inputData = array();
        foreach ($_GET as $key => $value) {
            if (substr($key, 0, 4) == "vnp_") {
                $inputData[$key] = $value;
            }
        }
        unset($inputData['vnp_SecureHash']);
        ksort($inputData);
        $hashData = "";
        $i = 0;
        foreach ($inputData as $key => $value) {
            if ($i == 1) {
                $hashData .= '&' . urlencode($key) . "=" . urlencode($value);
            } else {
                $hashData .= urlencode($key) . "=" . urlencode($value);
                $i = 1;
            }
        }
        $secureHash = hash_hmac("sha512", $hashData, $vnp_HashSecret);

        error_log('VNPay Callback Data: ' . print_r($inputData, true));
        error_log('VNPay Secure Hash: ' . $vnp_SecureHash . ' | Calculated: ' . $secureHash);

        if ($secureHash == $vnp_SecureHash) {
            if ($_GET['vnp_ResponseCode'] == '00') {
                $order_id = explode('_', $_GET['vnp_TxnRef'])[1];
                error_log('Extracted Order ID: ' . $order_id);
                error_log('vnp_TransactionNo: ' . $_GET['vnp_TransactionNo']);

                $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
                error_log('Table exists: ' . ($table_exists ? 'Yes' : 'No'));

                $order = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE order_id = %d", $order_id), ARRAY_A);
                error_log('Order data for ID ' . $order_id . ': ' . print_r($order, true));

                if ($order) {
                    // Sử dụng $wpdb->query() thay vì $wpdb->update()
                    $result = $wpdb->query(
                        $wpdb->prepare(
                            "UPDATE $table_name SET payment_status = %s, transaction_id = %s WHERE order_id = %d",
                            'completed',
                            $_GET['vnp_TransactionNo'],
                            $order_id
                        )
                    );
                    error_log('Direct SQL update result for Order ID ' . $order_id . ': ' . ($result !== false ? 'Success' : 'Failed'));
                    if ($result === false) {
                        error_log('wpdb last error (direct SQL): ' . $wpdb->last_error);
                    }

                    $updated_order = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE order_id = %d", $order_id), ARRAY_A);
                    error_log('Order data after direct SQL update for ID ' . $order_id . ': ' . print_r($updated_order, true));
                } else {
                    error_log('Order not found for ID: ' . $order_id);
                }
                wp_redirect(add_query_arg('order_submitted', '1', home_url()));
                exit;
            } else {
                error_log('Payment failed with ResponseCode: ' . $_GET['vnp_ResponseCode']);
                wp_redirect(add_query_arg('order_error', 'payment_failed', home_url()));
                exit;
            }
        } else {
            error_log('Invalid signature');
            wp_redirect(add_query_arg('order_error', 'invalid_signature', home_url()));
            exit;
        }
    }
}

function glp_handle_order_submit()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'glp_orders';

    ob_start();

    if (!isset($_POST['glp_order_nonce']) || !wp_verify_nonce($_POST['glp_order_nonce'], 'glp_order_form_nonce')) {
        set_transient('glp_order_form_errors', ['Yêu cầu không hợp lệ. Vui lòng thử lại.'], 30);
        wp_redirect(add_query_arg('order_error', '1', wp_get_referer()));
        exit;
    }

    $customer_name = sanitize_text_field($_POST['customer_name']);
    $customer_phone = sanitize_text_field($_POST['customer_phone']);
    $customer_address = sanitize_textarea_field($_POST['customer_address']);
    $payment_method = sanitize_text_field($_POST['payment_method']);

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
    if (!in_array($payment_method, ['cod', 'online'])) {
        $errors[] = 'Phương thức thanh toán không hợp lệ.';
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

    $total_price = 0;
    foreach ($cart as $item) {
        $total_price += $item['price'] * $item['quantity'];
    }

    $next_order_id = get_option('glp_next_order_id', 1);
    $order_id = $next_order_id;
    update_option('glp_next_order_id', $next_order_id + 1);

    $result = $wpdb->insert(
        $table_name,
        array(
            'order_id' => $order_id,
            'products' => maybe_serialize($cart),
            'customer_name' => $customer_name,
            'customer_phone' => $customer_phone,
            'customer_address' => $customer_address,
            'date' => current_time('mysql'),
            'status' => 'new',
            'payment_method' => $payment_method,
            'payment_status' => $payment_method === 'cod' ? 'pending' : 'pending',
            'transaction_id' => null
        ),
        array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
    );
    error_log('Insert order result for ID ' . $order_id . ': ' . ($result !== false ? 'Success' : 'Failed'));

    $to = get_option('admin_email');
    $subject = 'Có đơn hàng mới từ website';
    $message_email = "Thông tin đơn hàng mới:\n\n";
    $message_email .= "Mã đơn hàng: #$order_id\n";
    $message_email .= "Danh sách sản phẩm:\n";
    foreach ($cart as $item) {
        $subtotal = $item['price'] * $item['quantity'];
        $message_email .= "- {$item['name']} (x{$item['quantity']}): " . number_format($subtotal, 0, ',', '.') . " VNĐ\n";
    }
    $message_email .= "Tổng tiền: " . number_format($total_price, 0, ',', '.') . " VNĐ\n\n";
    $message_email .= "Tên khách hàng: $customer_name\n";
    $message_email .= "Số điện thoại: $customer_phone\n";
    $message_email .= "Địa chỉ: $customer_address\n";
    $message_email .= "Phương thức thanh toán: " . ($payment_method === 'cod' ? 'Ship COD' : 'Thanh toán online (VNPay)') . "\n";
    $message_email .= "Ngày đặt: " . current_time('mysql') . "\n";
    wp_mail($to, $subject, $message_email);

    if ($payment_method === 'online') {
        $vnpay_url = glp_create_vnpay_payment_url($order_id, $total_price);
        wp_redirect($vnpay_url);
        exit;
    }

    $_SESSION['glp_cart'] = array();
    wp_redirect(add_query_arg('order_submitted', '1', wp_get_referer()));
    exit;
}

function glp_handle_export_orders()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'glp_orders';

    if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'glp_export_orders_action')) {
        wp_die('Yêu cầu không hợp lệ.');
    }

    if (!current_user_can('manage_options')) {
        wp_die('Bạn không có quyền thực hiện hành động này.');
    }

    $orders = $wpdb->get_results("SELECT * FROM $table_name ORDER BY id DESC", ARRAY_A);

    if (!empty($orders)) {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', 'STT');
        $sheet->setCellValue('B1', 'Mã Đơn Hàng');
        $sheet->setCellValue('C1', 'Sản Phẩm');
        $sheet->setCellValue('D1', 'Tổng Tiền (VNĐ)');
        $sheet->setCellValue('E1', 'Tên Khách Hàng');
        $sheet->setCellValue('F1', 'Số Điện Thoại');
        $sheet->setCellValue('G1', 'Địa Chỉ');
        $sheet->setCellValue('H1', 'Ngày Đặt');
        $sheet->setCellValue('I1', 'Trạng Thái');
        $sheet->setCellValue('J1', 'Phương Thức Thanh Toán');
        $sheet->setCellValue('K1', 'Trạng Thái Thanh Toán');

        $row = 2;
        foreach ($orders as $index => $order) {
            $sheet->setCellValue('A' . $row, $index + 1);
            $sheet->setCellValue('B' . $row, '#' . $order['order_id']);

            $products = maybe_unserialize($order['products']);
            $product_list = [];
            foreach ($products as $item) {
                $product_list[] = "{$item['name']} (x{$item['quantity']})";
            }
            $sheet->setCellValue('C' . $row, implode(', ', $product_list));

            $total_price = 0;
            foreach ($products as $item) {
                $total_price += $item['price'] * $item['quantity'];
            }
            $sheet->setCellValue('D' . $row, number_format($total_price, 0, ',', '.'));

            $sheet->setCellValue('E' . $row, $order['customer_name']);
            $sheet->setCellValue('F' . $row, $order['customer_phone']);
            $sheet->setCellValue('G' . $row, $order['customer_address']);
            $sheet->setCellValue('H' . $row, date('d/m/Y H:i', strtotime($order['date'])));
            $sheet->setCellValue('I' . $row, $order['status'] === 'new' ? 'Mới' : ($order['status'] === 'contacted' ? 'Đã liên hệ' : 'Đã chốt'));
            $sheet->setCellValue('J' . $row, $order['payment_method'] === 'cod' ? 'Ship COD' : 'Thanh toán online (VNPay)');
            $sheet->setCellValue('K' . $row, $order['payment_status'] === 'completed' ? 'Đã thanh toán' : 'Chưa thanh toán');
            $row++;
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'don-hang-' . date('Y-m-d-H-i-s') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Cache-Control: cache, must-revalidate');
        header('Pragma: public');

        $writer->save('php://output');
        exit;
    } else {
        wp_redirect(add_query_arg('export_error', 'no_orders', wp_get_referer()));
        exit;
    }
}

function glp_handle_export_invoice()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'glp_orders';

    if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'glp_export_invoice_action')) {
        wp_die('Yêu cầu không hợp lệ.');
    }

    if (!current_user_can('manage_options')) {
        wp_die('Bạn không có quyền thực hiện hành động này.');
    }

    $order_id = isset($_POST['order_index']) ? intval($_POST['order_index']) : -1;
    $order = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE order_id = %d", $order_id), ARRAY_A);

    if (!$order) {
        wp_die('Đơn hàng không tồn tại.');
    }

    $phpWord = new PhpWord();
    $section = $phpWord->addSection();

    $section->addText('HÓA ĐƠN MUA HÀNG', ['bold' => true, 'size' => 16], ['alignment' => 'center']);
    $section->addTextBreak(1);
    $section->addText('Mã đơn hàng: #' . $order['order_id'], ['size' => 12]);
    $section->addText('Ngày đặt: ' . date('d/m/Y H:i', strtotime($order['date'])), ['size' => 12]);
    $section->addText('Khách hàng: ' . $order['customer_name'], ['size' => 12]);
    $section->addText('Số điện thoại: ' . $order['customer_phone'], ['size' => 12]);
    $section->addText('Địa chỉ: ' . $order['customer_address'], ['size' => 12]);
    $section->addText('Phương thức thanh toán: ' . ($order['payment_method'] === 'cod' ? 'Ship COD' : 'Thanh toán online (VNPay)'), ['size' => 12]);
    $section->addText('Trạng thái thanh toán: ' . ($order['payment_status'] === 'completed' ? 'Đã thanh toán' : 'Chưa thanh toán'), ['size' => 12]);
    $section->addTextBreak(1);

    $table = $section->addTable(['borderSize' => 1, 'borderColor' => '000000']);
    $table->addRow();
    $table->addCell(2000)->addText('Sản Phẩm', ['bold' => true]);
    $table->addCell(2000)->addText('Số Lượng', ['bold' => true]);
    $table->addCell(2000)->addText('Giá (VNĐ)', ['bold' => true]);
    $table->addCell(2000)->addText('Thành Tiền (VNĐ)', ['bold' => true]);

    $products = maybe_unserialize($order['products']);
    $total_price = 0;
    foreach ($products as $item) {
        $subtotal = $item['price'] * $item['quantity'];
        $total_price += $subtotal;
        $table->addRow();
        $table->addCell(2000)->addText($item['name']);
        $table->addCell(2000)->addText($item['quantity']);
        $table->addCell(2000)->addText(number_format($item['price'], 0, ',', '.'));
        $table->addCell(2000)->addText(number_format($subtotal, 0, ',', '.'));
    }

    if ($order['payment_status'] === 'completed') {
        $total_price = 0;
    }

    $section->addTextBreak(1);
    $section->addText('Tổng tiền: ' . number_format($total_price, 0, ',', '.') . ' VNĐ', ['bold' => true]);

    $filename = 'hoa-don-' . $order['order_id'] . '-' . date('Y-m-d-H-i-s') . '.docx';
    header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    $writer = IOFactory::createWriter($phpWord, 'Word2007');
    $writer->save('php://output');
    exit;
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

function glp_render_orders_admin_page()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'glp_orders';

    if (isset($_POST['glp_delete_order']) && check_admin_referer('glp_delete_order_action')) {
        $order_id = intval($_POST['order_index']);
        $result = $wpdb->delete(
            $table_name,
            array('order_id' => $order_id),
            array('%d')
        );
        error_log('Delete order result for ID ' . $order_id . ': ' . ($result !== false ? 'Success' : 'Failed'));
        if ($result !== false) {
            echo '<div class="notice notice-success"><p>Đã xóa đơn hàng thành công!</p></div>';
        }
    }

    if (isset($_GET['export_error']) && $_GET['export_error'] === 'no_orders') {
        echo '<div class="notice notice-error"><p>Không có đơn hàng nào để export!</p></div>';
    }

    $orders = $wpdb->get_results("SELECT * FROM $table_name ORDER BY id DESC", ARRAY_A);
?>
    <div class="wrap">
        <h1 class="wp-heading-inline">Quản lý Đơn Hàng</h1>

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
                        <th>Mã Đơn Hàng</th>
                        <th>Sản Phẩm</th>
                        <th>Tổng Tiền (VNĐ)</th>
                        <th>Tên Khách Hàng</th>
                        <th>Số Điện Thoại</th>
                        <th>Địa Chỉ</th>
                        <th>Ngày Đặt</th>
                        <th>Trạng Thái Thanh Toán</th>
                        <th>Xuất Hóa Đơn</th>
                        <th>Hành Động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $index => $order): ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>
                            <td>#<?php echo esc_html($order['order_id']); ?></td>
                            <td>
                                <?php
                                $products = maybe_unserialize($order['products']);
                                $product_list = [];
                                foreach ($products as $item) {
                                    $product_list[] = "{$item['name']} (x{$item['quantity']})";
                                }
                                echo esc_html(implode(', ', $product_list));
                                ?>
                            </td>
                            <td>
                                <?php
                                $total_price = 0;
                                foreach ($products as $item) {
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
                                <?php echo $order['payment_method'] === 'cod' ? 'Ship COD' : ($order['payment_status'] === 'completed' ? 'Đã thanh toán online' : 'Chưa thanh toán online'); ?>
                            </td>
                            <td>
                                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display: inline;">
                                    <input type="hidden" name="action" value="glp_export_invoice">
                                    <?php wp_nonce_field('glp_export_invoice_action'); ?>
                                    <input type="hidden" name="order_index" value="<?php echo $order['order_id']; ?>">
                                    <button type="submit" class="button button-primary">Xuất Hóa Đơn</button>
                                </form>
                            </td>
                            <td>
                                <form method="post">
                                    <?php wp_nonce_field('glp_delete_order_action'); ?>
                                    <input type="hidden" name="order_index" value="<?php echo $order['order_id']; ?>">
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
