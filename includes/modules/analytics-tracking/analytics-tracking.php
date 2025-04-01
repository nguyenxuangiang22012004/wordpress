<?php
if (!defined('ABSPATH')) {
    exit;
}

function glcp_analytics_tracking_init()
{
    add_action('wp_head', 'glcp_analytics_tracking_display');
}

function glcp_analytics_tracking_display()
{
    if (is_page('gym-landing')) {
        echo get_option('glcp_analytics_code', '');
    }
}

function glcp_analytics_tracking_admin()
{
    if (isset($_POST['glcp_save_analytics'])) {
        update_option('glcp_analytics_code', wp_kses_post($_POST['analytics_code']));
        echo '<div class="updated"><p>Đã lưu mã analytics!</p></div>';
    }
    $analytics_code = get_option('glcp_analytics_code', '');
?>
    <h2>Analytics</h2>
    <form method="post">
        <p><label>Mã Analytics:</label><br><textarea name="analytics_code" style="width: 400px; height: 100px;"><?php echo esc_textarea($analytics_code); ?></textarea></p>
        <p><input type="submit" name="glcp_save_analytics" value="Lưu" class="button button-primary"></p>
    </form>
<?php
}

glcp_analytics_tracking_init();
