<?php
if (!defined('ABSPATH')) {
    exit;
}

function glcp_class_schedule_init()
{
    add_shortcode('gym_schedule', 'glcp_schedule_shortcode');
}

function glcp_schedule_shortcode()
{
    $schedule = maybe_unserialize(get_option('glcp_schedule', []));
    ob_start();
    include GLP_PLUGIN_DIR . 'includes/modules/class-schedule/templates/schedule.php';
    return ob_get_clean();
}

function glcp_class_schedule_admin()
{
    // Xử lý lưu dữ liệu
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
        }
    }

    $schedule = maybe_unserialize(get_option('glcp_schedule', []));
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
<?php
}

glcp_class_schedule_init();
