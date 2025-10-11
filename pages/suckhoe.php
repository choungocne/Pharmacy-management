    <link rel="stylesheet" href="static/css/health.css">

    <div class="container">

        <!-- Phần Bác sĩ chuyên khoa -->
        <div class="section">
            <div class="section-header">
                <h2 class="section-title"><i class="fas fa-user-md"></i> Bác sĩ Chuyên khoa</h2>
                <a href="#" class="view-all">Xem tất cả <i class="fas fa-chevron-right"></i></a>
            </div>

            <div class="doctors-grid">
                <div class="doctor-card">
                    <div class="doctor-img">
                        <i class="fas fa-user-md"></i>
                    </div>
                    <div class="doctor-title">Bác sĩ Chuyên khoa 1</div>
                    <div class="doctor-name">Lê Thị Giao Thi</div>
                    <div class="doctor-specialty">Y học gia đình</div>
                </div>

                <div class="doctor-card">
                    <div class="doctor-img">
                        <i class="fas fa-user-md"></i>
                    </div>
                    <div class="doctor-name">Bác sĩ Nguyễn Văn My</div>
                    <div class="doctor-specialty">Truyền nhiễm</div>
                </div>

                <div class="doctor-card">
                    <div class="doctor-img">
                        <i class="fas fa-user-md"></i>
                    </div>
                    <div class="doctor-title">Bác sĩ Chuyên khoa 1</div>
                    <div class="doctor-name">Nguyễn Anh Tuấn</div>
                    <div class="doctor-specialty">Chẩn đoán hình ảnh</div>
                </div>
            </div>
        </div>

        <!-- Phần Chuyên mục sức khỏe -->
        <div class="section">
            <div class="section-header">
                <h2 class="section-title"><i class="fas fa-book-medical"></i> Chuyên mục Sức khỏe</h2>
            </div>

            <div class="categories-container">
                <!-- Bệnh lý -->
                <div class="category-group">
                    <h3 class="group-title"><i class="fas fa-stethoscope"></i> Bệnh</h3>
                    <div class="category-grid">
                        <div class="category-item">
                            <div class="category-icon cancer">
                                <i class="fas fa-allergies"></i>
                            </div>
                            <div class="category-name">Ung thư</div>
                            <div class="article-count"><i class="far fa-file-alt"></i> 147 bài viết</div>
                        </div>

                        <div class="category-item">
                            <div class="category-icon heart">
                                <i class="fas fa-heart"></i>
                            </div>
                            <div class="category-name">Tim mạch</div>
                            <div class="article-count"><i class="far fa-file-alt"></i> 119 bài viết</div>
                        </div>

                        <div class="category-item">
                            <div class="category-icon endocrine">
                                <i class="fas fa-tint"></i>
                            </div>
                            <div class="category-name">Nội tiết - chuyển hóa</div>
                            <div class="article-count"><i class="far fa-file-alt"></i> 87 bài viết</div>
                        </div>

                        <div class="category-item">
                            <div class="category-icon bone">
                                <i class="fas fa-bone"></i>
                            </div>
                            <div class="category-name">Cơ - Xương - Khớp</div>
                            <div class="article-count"><i class="far fa-file-alt"></i> 184 bài viết</div>
                        </div>
                    </div>
                </div>

                <!-- Phòng bệnh & sống khỏe -->
                <div class="category-group">
                    <h3 class="group-title"><i class="fas fa-shield-alt"></i> Phòng bệnh & sống khỏe</h3>
                    <div class="category-grid">
                        <div class="category-item">
                            <div class="category-icon vaccine">
                                <i class="fas fa-syringe"></i>
                            </div>
                            <div class="category-name">Tiêm chủng</div>
                            <div class="article-count"><i class="far fa-file-alt"></i> 1.199 bài viết</div>
                        </div>

                        <div class="category-item">
                            <div class="category-icon knowledge">
                                <i class="fas fa-graduation-cap"></i>
                            </div>
                            <div class="category-name">Kiến thức y khoa</div>
                            <div class="article-count"><i class="far fa-file-alt"></i> 34.025 bài viết</div>
                        </div>

                        <div class="category-item">
                            <div class="category-icon family">
                                <i class="fas fa-home"></i>
                            </div>
                            <div class="category-name">Sức khỏe gia đình</div>
                            <div class="article-count"><i class="far fa-file-alt"></i> 14.959 bài viết</div>
                        </div>

                        <div class="category-item">
                            <div class="category-icon psychology">
                                <i class="fas fa-brain"></i>
                            </div>
                            <div class="category-name">Tâm lý - Tâm thần</div>
                            <div class="article-count"><i class="far fa-file-alt"></i> 559 bài viết</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Thống kê -->
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-number">50+</div>
                <div class="stat-label">Bác sĩ chuyên gia</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-file-medical"></i>
                </div>
                <div class="stat-number">50K+</div>
                <div class="stat-label">Bài viết y khoa</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-procedures"></i>
                </div>
                <div class="stat-number">120+</div>
                <div class="stat-label">Chuyên khoa</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="stat-number">15K+</div>
                <div class="stat-label">Lượt tư vấn</div>
            </div>
        </div>


    </div>

    <script>
        // Thêm tương tác cho các card
        document.querySelectorAll('.doctor-card, .category-item').forEach(card => {
            card.addEventListener('click', function() {
                // Logic để điều hướng đến trang chi tiết
                const title = this.querySelector('.doctor-name, .category-name').textContent;
                alert('Đã chọn: ' + title);
            });
        });

        // Hiệu ứng số đếm cho thống kê
        document.addEventListener('DOMContentLoaded', function() {
            const statNumbers = document.querySelectorAll('.stat-number');

            statNumbers.forEach(stat => {
                const target = parseInt(stat.textContent);
                let current = 0;
                const increment = target / 50;

                const updateNumber = () => {
                    if (current < target) {
                        current += increment;
                        stat.textContent = Math.floor(current) + (stat.textContent.includes('K') ? 'K+' : '+');
                        setTimeout(updateNumber, 30);
                    } else {
                        stat.textContent = target + (stat.textContent.includes('K') ? 'K+' : '+');
                    }
                };

                updateNumber();
            });
        });
    </script>


<div id="footer"></div>
