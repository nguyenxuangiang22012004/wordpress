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
        echo '<div class="alert alert-success">Đã lưu lịch tập!</div>';
    }

    // Xử lý xóa lớp
    if (isset($_POST['glcp_delete_class']) && check_admin_referer('glcp_delete_class_action', 'glcp_delete_class_nonce')) {
        $index = intval($_POST['delete_index']);
        $schedule = maybe_unserialize(get_option('glcp_schedule', []));
        if (isset($schedule[$index])) {
            unset($schedule[$index]);
            $schedule = array_values($schedule);
            update_option('glcp_schedule', maybe_serialize($schedule));
            echo '<div class="alert alert-success">Đã xóa lớp!</div>';
        }
    }

    $schedule = maybe_unserialize(get_option('glcp_schedule', []));
?>
    <h2>Lịch Tập</h2>
    <form method="post" id="glcp-schedule-form">
        <?php wp_nonce_field('glcp_save_schedule_action', 'glcp_schedule_nonce'); ?>
        <div class="table-responsive">
            <table class="table table-striped table-bordered glcp-schedule-table" id="glcp-schedule-table">
                <thead class="table-light">
                    <tr>
                        <th>Tên Lớp</th>
                        <th>Thời Gian</th>
                        <th>Hành Động</th>
                    </tr>
                </thead>
                <tbody id="glcp-schedule-rows">
                    <?php if (!empty($schedule)): ?>
                        <?php foreach ($schedule as $index => $class): ?>
                            <tr class="glcp-schedule-row">
                                <td><input type="text" class="form-control" name="schedule[<?php echo $index; ?>][name]" value="<?php echo esc_attr($class['name'] ?? ''); ?>" placeholder="Tên lớp"></td>
                                <td><input type="text" class="form-control" name="schedule[<?php echo $index; ?>][time]" value="<?php echo esc_attr($class['time'] ?? ''); ?>" placeholder="Thời gian"></td>
                                <td>
                                    <form method="post" style="display:inline;">
                                        <?php wp_nonce_field('glcp_delete_class_action', 'glcp_delete_class_nonce'); ?>
                                        <input type="hidden" name="delete_index" value="<?php echo $index; ?>">
                                        <button type="submit" name="glcp_delete_class" class="btn btn-danger btn-sm" onclick="return confirm('Bạn chắc chắn muốn xóa lớp này?');">Xóa</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr class="glcp-schedule-row">
                            <td><input type="text" class="form-control" name="schedule[0][name]" value="" placeholder="Tên lớp"></td>
                            <td><input type="text" class="form-control" name="schedule[0][time]" value="" placeholder="Thời gian"></td>
                            <td></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <p class="mt-3">
            <button type="button" id="glcp-add-class" class="btn btn-secondary me-2">Thêm Lớp</button>
            <input type="submit" name="glcp_save_schedule" value="Lưu" class="btn btn-primary">
        </p>
    </form>

    <script>
        jQuery(document).ready(function($) {
            var rowCount = <?php echo !empty($schedule) ? count($schedule) : 1; ?>;

            $('#glcp-add-class').on('click', function() {
                var newRow = '<tr class="glcp-schedule-row">' +
                    '<td><input type="text" class="form-control" name="schedule[' + rowCount + '][name]" value="" placeholder="Tên lớp"></td>' +
                    '<td><input type="text" class="form-control" name="schedule[' + rowCount + '][time]" value="" placeholder="Thời gian"></td>' +
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
