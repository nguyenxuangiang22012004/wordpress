<div class="container py-4">
    <div class="card shadow-sm" style="background-color: #FFFFFF; border: none;">
        <div class="card-header text-center" style="background-color: #FFFFFF; border-bottom: 2px solid #d4a373;">
            <h2 class="mb-0" style="color: #6c757d; font-weight: bold;">LỊCH HỌC</h2>
        </div>
        <div class="card-body">
            <?php if (!empty($schedule)): ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover text-center" style="background-color: #f8f9fa; border-color: #d4a373;">
                        <thead style="background-color: #d4a373;">
                            <tr>
                                <th scope="col" style="color: white; width: 25%;">TÊN LỚP</th>
                                <th scope="col" style="color: white; width: 15%;">LOẠI LỚP</th>
                                <th scope="col" style="color: white; width: 30%;">THỜI GIAN</th>
                                <th scope="col" style="color: white; width: 30%;">HUẤN LUYỆN VIÊN</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($schedule as $class): if (!empty($class['name'])): ?>
                                    <tr style="border-bottom: 1px solid #dee2e6;">
                                        <td style="color: #495057; font-weight: 500;">
                                            <?php echo esc_html($class['name']); ?>
                                        </td>
                                        <td>
                                            <span class="badge <?php echo ($class['type'] == 'paid') ? 'bg-success' : 'bg-primary'; ?>" style="font-size: 0.9rem;">
                                                <?php echo ($class['type'] == 'paid') ? 'Trả phí' : 'Miễn phí'; ?>
                                            </span>
                                        </td>
                                        <td style="color: #6c757d;">
                                            <i class="far fa-clock me-2"></i><?php echo esc_html($class['time']); ?>
                                        </td>
                                        <td style="color: #d4a373; font-weight: 500;">
                                            <i class="fas fa-user-tie me-2"></i><?php echo esc_html($class['trainer']); ?>
                                        </td>
                                    </tr>
                            <?php endif;
                            endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info text-center" style="background-color: #f8f9fa; border-color: #d4a373; color: #6c757d;">
                    <i class="fas fa-info-circle me-2"></i>Hiện chưa có lịch tập. Vui lòng quay lại sau!
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
    .table-hover tbody tr:hover {
        background-color: rgba(212, 163, 115, 0.1);
    }

    .badge {
        padding: 0.5em 0.75em;
        border-radius: 0.25rem;
    }
</style>