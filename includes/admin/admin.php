<?php
if (!defined('ABSPATH')) {
    exit;
}

// Xử lý tab
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'leads';

?>
<div class="wrap">
    <h1>Quản Lý Gym Landing Plugin</h1>
    <h2 class="nav-tab-wrapper">
        <a href="?page=gym-landing-settings&tab=leads" class="nav-tab <?php echo $active_tab == 'leads' ? 'nav-tab-active' : ''; ?>">Leads</a>
        <a href="?page=gym-landing-settings&tab=popup" class="nav-tab <?php echo $active_tab == 'popup' ? 'nav-tab-active' : ''; ?>">Popup</a>
        <a href="?page=gym-landing-settings&tab=schedule" class="nav-tab <?php echo $active_tab == 'schedule' ? 'nav-tab-active' : ''; ?>">Lịch Tập</a>
        <a href="?page=gym-landing-settings&tab=testimonials" class="nav-tab <?php echo $active_tab == 'testimonials' ? 'nav-tab-active' : ''; ?>">Nhận Xét</a>
        <a href="?page=gym-landing-settings&tab=analytics" class="nav-tab <?php echo $active_tab == 'analytics' ? 'nav-tab-active' : ''; ?>">Analytics</a>
    </h2>

    <?php
    if ($active_tab == 'leads') {
        glcp_lead_management_admin();
    } elseif ($active_tab == 'popup') {
        glcp_popup_offers_admin();
    } elseif ($active_tab == 'schedule') {
        glcp_class_schedule_admin();
    } elseif ($active_tab == 'testimonials') {
        glcp_testimonials_admin();
    } elseif ($active_tab == 'analytics') {
        glcp_analytics_tracking_admin();
    }
    ?>
</div>