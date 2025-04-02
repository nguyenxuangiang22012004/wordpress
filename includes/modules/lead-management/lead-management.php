<?php
if (!defined('ABSPATH')) {
    exit;
}

// Khởi tạo module lead management
function glp_lead_management_init()
{
    add_shortcode('gym_lead_form', 'glp_render_lead_form');
    add_action('admin_post_nopriv_glp_lead_submit', 'glp_handle_lead_submit');
    add_action('admin_post_glp_lead_submit', 'glp_handle_lead_submit');
}
add_action('init', 'glp_lead_management_init');

// Hiển thị form lead frontend
function glp_render_lead_form()
{
    ob_start();
    include GLP_PLUGIN_DIR . 'includes/modules/lead-management/templates/lead-form.php';
    return ob_get_clean();
}

// Xử lý submit form lead
function glp_handle_lead_submit()
{
    if (!isset($_POST['glp_lead_nonce']) || !wp_verify_nonce($_POST['glp_lead_nonce'], 'glp_lead_form_nonce')) {
        wp_die('Invalid request');
    }

    $name = sanitize_text_field($_POST['name']);
    $email = sanitize_email($_POST['email']);
    $phone = sanitize_text_field($_POST['phone'] ?? '');
    $message = sanitize_textarea_field($_POST['message'] ?? '');

    // Validate phía server
    $errors = [];
    if (empty($name) || strlen($name) < 2 || strlen($name) > 50) {
        $errors[] = 'Vui lòng nhập tên hợp lệ (2-50 ký tự).';
    }
    if (empty($email) || !is_email($email)) {
        $errors[] = 'Vui lòng nhập email hợp lệ.';
    }
    if (empty($phone) || !preg_match('/^[0-9]{10,15}$/', $phone)) {
        $errors[] = 'Vui lòng nhập số điện thoại hợp lệ (10-15 số).';
    }
    if (empty($message) || strlen($message) < 10 || strlen($message) > 500) {
        $errors[] = 'Tin nhắn phải từ 10-500 ký tự.';
    }

    // Nếu có lỗi, lưu vào transient và redirect
    if (!empty($errors)) {
        set_transient('glp_lead_form_errors', $errors, 30);
        wp_redirect(add_query_arg('lead_error', '1', wp_get_referer()));
        exit;
    }

    // Lưu lead vào wp_options
    $lead = array(
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'message' => $message,
        'date' => current_time('mysql'),
        'status' => 'new'
    );

    $leads = get_option('glp_leads', array());
    $leads[] = $lead;
    update_option('glp_leads', $leads);

    // Gửi email thông báo
    $to = get_option('admin_email');
    $subject = 'Có lead mới từ website';
    $message_email = "Thông tin lead mới:\n\n";
    $message_email .= "Tên: {$lead['name']}\n";
    $message_email .= "Email: {$lead['email']}\n";
    $message_email .= "Điện thoại: {$lead['phone']}\n";
    $message_email .= "Tin nhắn: {$lead['message']}\n";
    wp_mail($to, $subject, $message_email);

    // Redirect với thông báo thành công
    wp_redirect(add_query_arg('lead_submitted', '1', wp_get_referer()));
    exit;
}

// Hiển thị trang quản lý lead trong admin
function glp_render_leads_admin_page()
{
    // Xử lý xóa lead
    if (isset($_POST['glp_delete_lead']) && check_admin_referer('glp_delete_lead_action')) {
        $leads = get_option('glp_leads', array());
        $index = intval($_POST['lead_index']);
        if (isset($leads[$index])) {
            unset($leads[$index]);
            $leads = array_values($leads);
            update_option('glp_leads', $leads);
            echo '<div class="notice notice-success"><p>Đã xóa lead thành công!</p></div>';
        }
    }

    // Hiển thị danh sách lead
    $leads = get_option('glp_leads', array());
?>
    <div class="wrap">
        <h1 class="wp-heading-inline">Quản lý Leads</h1>

        <?php if (empty($leads)): ?>
            <div class="notice notice-info">
                <p>Chưa có lead nào.</p>
            </div>
        <?php else: ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>STT</th>
                        <th>Tên</th>
                        <th>Email</th>
                        <th>Số điện thoại</th>
                        <th>Tin nhắn</th>
                        <th>Ngày đăng ký</th>
                        <th>Trạng thái</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($leads as $index => $lead): ?>
                        <tr>
                            <td><?php echo $index + 1; ?></td>
                            <td><?php echo esc_html($lead['name']); ?></td>
                            <td><?php echo esc_html($lead['email']); ?></td>
                            <td><?php echo esc_html($lead['phone']); ?></td>
                            <td><?php echo esc_html($lead['message']); ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($lead['date'])); ?></td>
                            <td>
                                <span class="glp-status-badge <?php echo esc_attr($lead['status']); ?>">
                                    <?php
                                    echo $lead['status'] === 'new' ? 'Mới' : ($lead['status'] === 'contacted' ? 'Đã liên hệ' : 'Đã chốt');
                                    ?>
                                </span>
                            </td>
                            <td>
                                <form method="post">
                                    <?php wp_nonce_field('glp_delete_lead_action'); ?>
                                    <input type="hidden" name="lead_index" value="<?php echo $index; ?>">
                                    <button type="submit" name="glp_delete_lead" class="button button-secondary" onclick="return confirm('Bạn chắc chắn muốn xóa lead này?');">
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
