<?php
if (!defined('ABSPATH')) {
    exit;
}

function glcp_lead_management_init()
{
    add_shortcode('gym_lead_form', 'glcp_lead_form_shortcode');
    add_action('init', 'glcp_save_lead');
}

function glcp_lead_form_shortcode()
{
    ob_start();
    include GLP_PLUGIN_DIR . 'includes/modules/lead-management/templates/lead-form.php';
    return ob_get_clean();
}

function glcp_save_lead()
{
    if (isset($_POST['glcp_form_submit'])) {
        $lead = array(
            'name' => sanitize_text_field($_POST['name']),
            'email' => sanitize_email($_POST['email']),
            'phone' => sanitize_text_field($_POST['phone']),
            'date' => current_time('mysql')
        );
        $leads = get_option('glcp_leads', []);
        $leads[] = $lead;
        update_option('glcp_leads', $leads);
    }
}

function glcp_lead_management_admin()
{
    if (isset($_POST['glcp_delete_lead'])) {
        $leads = get_option('glcp_leads', []);
        $index = intval($_POST['lead_index']);
        unset($leads[$index]);
        $leads = array_values($leads); // Sắp xếp lại mảng
        update_option('glcp_leads', $leads);
        echo '<div class="updated"><p>Đã xóa lead!</p></div>';
    }

    $leads = get_option('glcp_leads', []);
    if (!empty($leads)) {
        echo '<h2>Danh Sách Lead</h2>';
        echo '<table class="wp-list-table widefat striped">';
        echo '<thead><tr><th>Tên</th><th>Email</th><th>Số điện thoại</th><th>Ngày</th><th>Hành động</th></tr></thead><tbody>';
        foreach ($leads as $index => $lead) {
            echo '<tr>';
            echo '<td>' . esc_html($lead['name']) . '</td>';
            echo '<td>' . esc_html($lead['email']) . '</td>';
            echo '<td>' . esc_html($lead['phone']) . '</td>';
            echo '<td>' . esc_html($lead['date']) . '</td>';
            echo '<td><form method="post"><input type="hidden" name="lead_index" value="' . $index . '"><input type="submit" name="glcp_delete_lead" value="Xóa" class="button button-secondary" onclick="return confirm(\'Bạn chắc chắn muốn xóa?\');"></form></td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
    } else {
        echo '<p>Chưa có lead nào.</p>';
    }
}

glcp_lead_management_init();
