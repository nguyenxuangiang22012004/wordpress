<?php
if (!defined('ABSPATH')) {
    exit;
}

function glcp_class_schedule_init()
{
    add_shortcode('gym_schedule', 'glcp_schedule_shortcode');

    // Đăng ký action xử lý đăng ký lớp học
    add_action('admin_post_nopriv_glcp_register_class', 'glcp_handle_register_class');
    add_action('admin_post_glcp_register_class', 'glcp_handle_register_class');
}

function glcp_schedule_shortcode()
{
    $schedule = maybe_unserialize(get_option('glcp_schedule', []));
    ob_start();
    include GLP_PLUGIN_DIR . 'includes/modules/class-schedule/templates/schedule.php';
    return ob_get_clean();
}

// Xử lý đăng ký lớp học
function glcp_handle_register_class()
{
    ob_start(); // Bắt đầu output buffering để tránh lỗi headers

    // Kiểm tra nonce
    if (!isset($_POST['glcp_register_nonce']) || !wp_verify_nonce($_POST['glcp_register_nonce'], 'glcp_register_nonce_action')) {
        set_transient('glcp_register_errors', ['Yêu cầu không hợp lệ. Vui lòng thử lại.'], 30);
        wp_redirect(add_query_arg('register_error', '1', wp_get_referer()));
        exit;
    }

    // Lấy thông tin đăng ký
    $class_index = isset($_POST['class_index']) ? intval($_POST['class_index']) : -1;
    $name = sanitize_text_field($_POST['register_name']);
    $phone = sanitize_text_field($_POST['register_phone']);
    $email = sanitize_email($_POST['register_email']);

    // Validate thông tin
    $errors = [];
    if (empty($name) || strlen($name) < 2 || strlen($name) > 50) {
        $errors[] = 'Vui lòng nhập tên hợp lệ (2-50 ký tự).';
    }
    if (empty($phone) || !preg_match('/^[0-9]{10,15}$/', $phone)) {
        $errors[] = 'Vui lòng nhập số điện thoại hợp lệ (10-15 số).';
    }
    if (empty($email) || !is_email($email)) {
        $errors[] = 'Vui lòng nhập email hợp lệ.';
    }

    // Kiểm tra lớp học có tồn tại không
    $schedule = maybe_unserialize(get_option('glcp_schedule', []));
    if (!isset($schedule[$class_index])) {
        $errors[] = 'Lớp học không tồn tại.';
    }

    // Nếu có lỗi, redirect với thông báo lỗi
    if (!empty($errors)) {
        set_transient('glcp_register_errors', $errors, 30);
        wp_redirect(add_query_arg('register_error', '1', wp_get_referer()));
        exit;
    }

    // Lưu thông tin đăng ký
    $registrations = maybe_unserialize(get_option('glcp_registrations', []));
    $registrations[] = [
        'class_index' => $class_index,
        'class_name' => $schedule[$class_index]['name'],
        'name' => $name,
        'phone' => $phone,
        'email' => $email,
        'date' => current_time('mysql'),
        'status' => 'new'
    ];
    update_option('glcp_registrations', maybe_serialize($registrations));

    // Gửi email thông báo cho admin
    $to = get_option('admin_email');
    $subject = 'Có đăng ký lớp học mới từ website';
    $message = "Thông tin đăng ký mới:\n\n";
    $message .= "Lớp học: {$schedule[$class_index]['name']}\n";
    $message .= "Tên khách hàng: {$name}\n";
    $message .= "Số điện thoại: {$phone}\n";
    $message .= "Email: {$email}\n";
    $message .= "Ngày đăng ký: " . date('d/m/Y H:i', strtotime(current_time('mysql'))) . "\n";
    wp_mail($to, $subject, $message);

    // Redirect với thông báo thành công
    wp_redirect(add_query_arg('register_success', '1', wp_get_referer()));
    exit;
}

function glcp_class_schedule_admin()
{
    // Xử lý lưu dữ liệu lịch học
    if (isset($_POST['glcp_save_schedule']) && check_admin_referer('glcp_save_schedule_action', 'glcp_schedule_nonce')) {
        $schedule = isset($_POST['schedule']) ? $_POST['schedule'] : [];
        update_option('glcp_schedule', maybe_serialize($schedule));
        echo '<div class="notice notice-success"><p>Đã lưu lịch tập!</p></div>';
    }

    // Xử lý xóa lớp
    if (isset($_POST['glcp_delete_class']) && check_admin_referer('glcp_delete_class_action', 'glcp_delete_class_nonce')) {
        $index = intval($_POST['delete_index']);
        $schedule = maybe_unserialize(get_option('glcp_schedule', []));
        if (isset($schedule[$index])) {
            unset($schedule[$index]);
            $schedule = array_values($schedule);
            update_option('glcp_schedule', maybe_serialize($schedule));
            echo '<div class="notice notice-success"><p>Đã xóa lớp!</p></div>';

            // Xóa các đăng ký liên quan đến lớp này
            $registrations = maybe_unserialize(get_option('glcp_registrations', []));
            foreach ($registrations as $reg_index => $registration) {
                if ($registration['class_index'] == $index) {
                    unset($registrations[$reg_index]);
                } else if ($registration['class_index'] > $index) {
                    $registrations[$reg_index]['class_index']--;
                }
            }
            $registrations = array_values($registrations);
            update_option('glcp_registrations', maybe_serialize($registrations));
        }
    }

    // Xử lý xóa đăng ký
    if (isset($_POST['glcp_delete_registration']) && check_admin_referer('glcp_delete_registration_action', 'glcp_delete_registration_nonce')) {
        $index = intval($_POST['delete_reg_index']);
        $registrations = maybe_unserialize(get_option('glcp_registrations', []));
        if (isset($registrations[$index])) {
            unset($registrations[$index]);
            $registrations = array_values($registrations);
            update_option('glcp_registrations', maybe_serialize($registrations));
            echo '<div class="notice notice-success"><p>Đã xóa đăng ký!</p></div>';
        }
    }

    $schedule = maybe_unserialize(get_option('glcp_schedule', []));
    $registrations = maybe_unserialize(get_option('glcp_registrations', []));
?>
    <div class="wrap">
        <h2>Quản lý Lịch Tập Gym</h2>
        <form method="post" id="glcp-schedule-form">
            <?php wp_nonce_field('glcp_save_schedule_action', 'glcp_schedule_nonce'); ?>
            <div class="table-responsive">
                <table class="widefat fixed" id="glcp-schedule-table">
                    <thead>
                        <tr>
                            <th width="15%">Tên Lớp</th>
                            <th width="10%">Loại Lớp</th>
                            <th width="15%">Thời Gian</th>
                            <th width="15%">Huấn Luyện Viên</th>
                            <th width="30%">Kinh Nghiệm HLV</th>
                            <th width="15%">Hành Động</th>
                        </tr>
                    </thead>
                    <tbody id="glcp-schedule-rows">
                        <?php if (!empty($schedule)): ?>
                            <?php foreach ($schedule as $index => $class): ?>
                                <tr class="glcp-schedule-row">
                                    <td><input type="text" class="regular-text" name="schedule[<?php echo $index; ?>][name]" value="<?php echo esc_attr($class['name'] ?? ''); ?>" placeholder="Tên lớp" required></td>
                                    <td>
                                        <select name="schedule[<?php echo $index; ?>][type]" class="regular-text">
                                            <option value="free" <?php selected($class['type'] ?? '', 'free'); ?>>Miễn phí</option>
                                            <option value="paid" <?php selected($class['type'] ?? '', 'paid'); ?>>Trả phí</option>
                                        </select>
                                    </td>
                                    <td><input type="text" class="regular-text" name="schedule[<?php echo $index; ?>][time]" value="<?php echo esc_attr($class['time'] ?? ''); ?>" placeholder="VD: Thứ 2,4,6 - 18:00-19:00" required></td>
                                    <td><input type="text" class="regular-text" name="schedule[<?php echo $index; ?>][trainer]" value="<?php echo esc_attr($class['trainer'] ?? ''); ?>" placeholder="Tên HLV" required></td>
                                    <td>
                                        <textarea name="schedule[<?php echo $index; ?>][experience]" class="regular-text" rows="2" placeholder="Mô tả kinh nghiệm HLV"><?php echo esc_textarea($class['experience'] ?? ''); ?></textarea>
                                    </td>
                                    <td>
                                        <form method="post" style="display:inline;">
                                            <?php wp_nonce_field('glcp_delete_class_action', 'glcp_delete_class_nonce'); ?>
                                            <input type="hidden" name="delete_index" value="<?php echo $index; ?>">
                                            <button type="submit" name="glcp_delete_class" class="button button-danger" onclick="return confirm('Bạn chắc chắn muốn xóa lớp này?');">Xóa</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr class="glcp-schedule-row">
                                <td><input type="text" class="regular-text" name="schedule[0][name]" value="" placeholder="Tên lớp" required></td>
                                <td>
                                    <select name="schedule[0][type]" class="regular-text">
                                        <option value="free">Miễn phí</option>
                                        <option value="paid">Trả phí</option>
                                    </select>
                                </td>
                                <td><input type="text" class="regular-text" name="schedule[0][time]" value="" placeholder="VD: Thứ 2,4,6 - 18:00-19:00" required></td>
                                <td><input type="text" class="regular-text" name="schedule[0][trainer]" value="" placeholder="Tên HLV" required></td>
                                <td>
                                    <textarea name="schedule[0][experience]" class="regular-text" rows="2" placeholder="Mô tả kinh nghiệm HLV"></textarea>
                                </td>
                                <td></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <p class="submit">
                <button type="button" id="glcp-add-class" class="button button-secondary">+ Thêm Lớp</button>
                <input type="submit" name="glcp_save_schedule" value="Lưu Thay Đổi" class="button button-primary">
            </p>
        </form>

        <h2 class="mt-5">Danh Sách Đăng Ký Lớp Học</h2>
        <?php if (empty($registrations)): ?>
            <div class="notice notice-info">
                <p>Chưa có đăng ký nào.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="widefat fixed striped">
                    <thead>
                        <tr>
                            <th width="5%">STT</th>
                            <th width="15%">Lớp Học</th>
                            <th width="15%">Tên Khách Hàng</th>
                            <th width="15%">Số Điện Thoại</th>
                            <th width="15%">Email</th>
                            <th width="15%">Ngày Đăng Ký</th>
                            <th width="10%">Trạng Thái</th>
                            <th width="10%">Hành Động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($registrations as $index => $reg): ?>
                            <tr>
                                <td><?php echo $index + 1; ?></td>
                                <td><?php echo esc_html($reg['class_name']); ?></td>
                                <td><?php echo esc_html($reg['name']); ?></td>
                                <td><?php echo esc_html($reg['phone']); ?></td>
                                <td><?php echo esc_html($reg['email']); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($reg['date'])); ?></td>
                                <td>
                                    <span class="glcp-status-badge <?php echo esc_attr($reg['status']); ?>">
                                        <?php echo $reg['status'] === 'new' ? 'Mới' : ($reg['status'] === 'contacted' ? 'Đã Liên Hệ' : 'Đã Chốt'); ?>
                                    </span>
                                </td>
                                <td>
                                    <form method="post" style="display:inline;">
                                        <?php wp_nonce_field('glcp_delete_registration_action', 'glcp_delete_registration_nonce'); ?>
                                        <input type="hidden" name="delete_reg_index" value="<?php echo $index; ?>">
                                        <button type="submit" name="glcp_delete_registration" class="button button-danger" onclick="return confirm('Bạn chắc chắn muốn xóa đăng ký này?');">Xóa</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <script>
        jQuery(document).ready(function($) {
            var rowCount = <?php echo !empty($schedule) ? count($schedule) : 1; ?>;

            $('#glcp-add-class').on('click', function() {
                var newRow = '<tr class="glcp-schedule-row">' +
                    '<td><input type="text" class="regular-text" name="schedule[' + rowCount + '][name]" value="" placeholder="Tên lớp" required></td>' +
                    '<td><select name="schedule[' + rowCount + '][type]" class="regular-text">' +
                    '<option value="free">Miễn phí</option>' +
                    '<option value="paid">Trả phí</option>' +
                    '</select></td>' +
                    '<td><input type="text" class="regular-text" name="schedule[' + rowCount + '][time]" value="" placeholder="VD: Thứ 2,4,6 - 18:00-19:00" required></td>' +
                    '<td><input type="text" class="regular-text" name="schedule[' + rowCount + '][trainer]" value="" placeholder="Tên HLV" required></td>' +
                    '<td><textarea name="schedule[' + rowCount + '][experience]" class="regular-text" rows="2" placeholder="Mô tả kinh nghiệm HLV"></textarea></td>' +
                    '<td></td>' +
                    '</tr>';
                $('#glcp-schedule-rows').append(newRow);
                rowCount++;
            });
        });
    </script>

    <style>
        .glcp-status-badge.new {
            background-color: #ffcc00;
            padding: 2px 5px;
            border-radius: 3px;
        }

        .glcp-status-badge.contacted {
            background-color: #0073aa;
            color: white;
            padding: 2px 5px;
            border-radius: 3px;
        }

        .glcp-status-badge.done {
            background-color: #28a745;
            color: white;
            padding: 2px 5px;
            border-radius: 3px;
        }
    </style>
<?php
}

glcp_class_schedule_init();
