<div class="container py-4">
    <div class="card shadow-sm" style="background-color:#FFFFFF; border: none;">
        <div class="card-header text-white text-center" style="background-color:#FFFFFF;">
            <h2 class="mb-0">Lịch Học</h2>
        </div>
        <div class="card-body" style="color: white;">
            <?php if (!empty($schedule)): ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped text-center" style="background-color: #e9c8a7; border-color: #d4a373;">
                        <thead style="background-color: #d4a373;">
                            <tr>
                                <th scope="col" style="color: white;">Tên Lớp</th>
                                <th scope="col" style="color: white;">Thời Gian</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($schedule as $class): if (!empty($class['name'])): ?>
                                    <tr>
                                        <td style="color: white;"><?php echo esc_html($class['name']); ?></td>
                                        <td style="color: white;"><?php echo esc_html($class['time']); ?></td>
                                    </tr>
                            <?php endif;
                            endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-center" style="color: white;">Chưa có lịch học nào.</p>
            <?php endif; ?>
        </div>
    </div>
</div>