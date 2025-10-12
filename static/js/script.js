// Countdown Timer
class CountdownTimer {
    constructor() {
        this.updateCountdown();
        setInterval(() => this.updateCountdown(), 1000);
    }

    updateCountdown() {
        // For demo purposes, we'll use a fixed countdown
        // In a real application, you would fetch this from an API
        const now = new Date();
        const endTime = new Date();
        endTime.setHours(23, 59, 59, 999);

        const diff = endTime - now;

        if (diff <= 0) {
            document.getElementById('countdown').textContent = '00:00:00';
            return;
        }

        const hours = Math.floor(diff / (1000 * 60 * 60));
        const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((diff % (1000 * 60)) / 1000);

        const timeString = `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
        document.getElementById('countdown').textContent = timeString;
    }
}

// Cart Manager
class CartManager {
    constructor() {
        this.initEventListeners();
    }

    initEventListeners() {
        // Add to cart buttons
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('add-to-cart')) {
                this.addToCart(e.target);
            }
        });
    }

    addToCart(button) {
        const productId = button.dataset.productId;

        // In a real application, you would make an API call here
        // For demo purposes, we'll just show a notification
        this.showNotification('Sản phẩm đã được thêm vào giỏ hàng!', 'success');
    }

    showNotification(message, type) {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
        notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        notification.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        document.body.appendChild(notification);

        // Auto remove after 3 seconds
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 3000);
    }
}

// Search functionality
class SearchManager {
    constructor() {
        this.initEventListeners();
    }

    initEventListeners() {
        const searchInput = document.querySelector('.search-bar input');
        const searchButton = document.querySelector('.search-bar button');

        if (searchInput && searchButton) {
            searchButton.addEventListener('click', () => this.performSearch(searchInput.value));
            searchInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    this.performSearch(searchInput.value);
                }
            });
        }
    }

    performSearch(query) {
        if (query.trim()) {
            alert(`Tìm kiếm: ${query}`);
            // In a real application, you would redirect to search page
            // window.location.href = `/search?q=${encodeURIComponent(query)}`;
        }
    }
}

// Tab functionality for disease section
class TabManager {
    constructor() {
        this.initTabs();
    }

    initTabs() {
        const tabs = document.querySelectorAll(".tab-btn");
        const contents = document.querySelectorAll(".tab-content");

        tabs.forEach(tab => {
            tab.addEventListener("click", () => {
                // Remove active class from all tabs and contents
                tabs.forEach(btn => btn.classList.remove("active"));
                contents.forEach(c => c.classList.remove("active"));

                // Add active class to clicked tab and corresponding content
                tab.classList.add("active");
                document.getElementById(tab.dataset.tab).classList.add("active");
            });
        });
    }
}

// Mobile Navigation Handler
class MobileNavigation {
    constructor() {
        this.initMobileNav();
    }

    initMobileNav() {
        // Add mobile menu toggle for small screens
        if (window.innerWidth <= 768) {
            const navItems = document.querySelectorAll('.nav-item');

            navItems.forEach(item => {
                const link = item.querySelector('a');
                if (link.nextElementSibling && (link.nextElementSibling.classList.contains('dropdown-menu'))) {
                    link.addEventListener('click', (e) => {
                        e.preventDefault();
                        const dropdown = link.nextElementSibling;

                        // Close all other dropdowns
                        document.querySelectorAll('.dropdown-menu').forEach(menu => {
                            if (menu !== dropdown) menu.style.display = 'none';
                        });

                        // Toggle current dropdown
                        if (dropdown.style.display === 'block') {
                            dropdown.style.display = 'none';
                        } else {
                            dropdown.style.display = 'block';
                        }
                    });
                }
            });
        }
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    new CountdownTimer();
    new CartManager();
    new SearchManager();
    new TabManager();
    new MobileNavigation();

    // Add hover effects to category cards
    const categoryCards = document.querySelectorAll('.category-card');
    categoryCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
        });

        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });

    // Add click effects to product cards
    const productCards = document.querySelectorAll('.product-card');
    productCards.forEach(card => {
        card.addEventListener('click', function(e) {
            if (!e.target.classList.contains('add-to-cart') && !e.target.classList.contains('view-details')) {
                // In a real application, you would redirect to product detail page
                console.log('Navigate to product detail');
            }
        });
    });
});
// Carousel functionality
class HeroCarousel {
    constructor() {
        this.carousel = document.querySelector('.carousel-container');
        this.slides = document.querySelectorAll('.carousel-slide');
        this.indicators = document.querySelectorAll('.indicator');
        this.prevBtn = document.querySelector('.carousel-prev');
        this.nextBtn = document.querySelector('.carousel-next');
        this.currentSlide = 0;
        this.slideInterval = null;
        this.autoPlayDelay = 5000; // 5 seconds

        this.init();
    }

    init() {
        // Event listeners
        this.prevBtn.addEventListener('click', () => this.prevSlide());
        this.nextBtn.addEventListener('click', () => this.nextSlide());

        // Indicator clicks
        this.indicators.forEach((indicator, index) => {
            indicator.addEventListener('click', () => this.goToSlide(index));
        });

        // Auto-play
        this.startAutoPlay();

        // Pause on hover
        this.carousel.addEventListener('mouseenter', () => this.stopAutoPlay());
        this.carousel.addEventListener('mouseleave', () => this.startAutoPlay());

        // Touch events for mobile
        this.setupTouchEvents();
    }

    showSlide(index) {
        // Hide all slides
        this.slides.forEach(slide => slide.classList.remove('active'));
        this.indicators.forEach(indicator => indicator.classList.remove('active'));

        // Show current slide
        this.slides[index].classList.add('active');
        this.indicators[index].classList.add('active');

        this.currentSlide = index;
    }

    nextSlide() {
        const next = (this.currentSlide + 1) % this.slides.length;
        this.showSlide(next);
    }

    prevSlide() {
        const prev = (this.currentSlide - 1 + this.slides.length) % this.slides.length;
        this.showSlide(prev);
    }

    goToSlide(index) {
        this.showSlide(index);
    }

    startAutoPlay() {
        this.stopAutoPlay();
        this.slideInterval = setInterval(() => {
            this.nextSlide();
        }, this.autoPlayDelay);
    }

    stopAutoPlay() {
        if (this.slideInterval) {
            clearInterval(this.slideInterval);
            this.slideInterval = null;
        }
    }

    setupTouchEvents() {
        let startX = 0;
        let endX = 0;

        this.carousel.addEventListener('touchstart', (e) => {
            startX = e.touches[0].clientX;
        });

        this.carousel.addEventListener('touchend', (e) => {
            endX = e.changedTouches[0].clientX;
            this.handleSwipe(startX, endX);
        });
    }

    handleSwipe(startX, endX) {
        const swipeThreshold = 50;
        const diff = startX - endX;

        if (Math.abs(diff) > swipeThreshold) {
            if (diff > 0) {
                this.nextSlide(); // Swipe left
            } else {
                this.prevSlide(); // Swipe right
            }
        }
    }
}

// Initialize carousel when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new HeroCarousel();
});

// Optional: Add keyboard navigation
document.addEventListener('keydown', (e) => {
    const carousel = document.querySelector('.carousel-container');
    if (!carousel) return;

    if (e.key === 'ArrowLeft') {
        document.querySelector('.carousel-prev')?.click();
    } else if (e.key === 'ArrowRight') {
        document.querySelector('.carousel-next')?.click();
    }
});
// Banner Carousel functionality
class BannerCarousel {
    constructor() {
        this.carousel = document.querySelector('.banner-carousel');
        if (!this.carousel) return;

        this.slides = document.querySelectorAll('.banner-slide');
        this.indicators = document.querySelectorAll('.banner-indicator');
        this.prevBtn = document.querySelector('.banner-prev');
        this.nextBtn = document.querySelector('.banner-next');
        this.currentSlide = 0;
        this.slideInterval = null;
        this.autoPlayDelay = 4000; // 4 seconds

        this.init();
    }

    init() {
        // Event listeners
        this.prevBtn.addEventListener('click', () => this.prevSlide());
        this.nextBtn.addEventListener('click', () => this.nextSlide());

        // Indicator clicks
        this.indicators.forEach((indicator, index) => {
            indicator.addEventListener('click', () => this.goToSlide(index));
        });

        // Auto-play
        this.startAutoPlay();

        // Pause on hover
        this.carousel.addEventListener('mouseenter', () => this.stopAutoPlay());
        this.carousel.addEventListener('mouseleave', () => this.startAutoPlay());

        // Touch events for mobile
        this.setupTouchEvents();
    }

    showSlide(index) {
        // Hide all slides
        this.slides.forEach(slide => slide.classList.remove('active'));
        this.indicators.forEach(indicator => indicator.classList.remove('active'));

        // Show current slide
        this.slides[index].classList.add('active');
        this.indicators[index].classList.add('active');

        this.currentSlide = index;
    }

    nextSlide() {
        const next = (this.currentSlide + 1) % this.slides.length;
        this.showSlide(next);
    }

    prevSlide() {
        const prev = (this.currentSlide - 1 + this.slides.length) % this.slides.length;
        this.showSlide(prev);
    }

    goToSlide(index) {
        this.showSlide(index);
    }

    startAutoPlay() {
        this.stopAutoPlay();
        this.slideInterval = setInterval(() => {
            this.nextSlide();
        }, this.autoPlayDelay);
    }

    stopAutoPlay() {
        if (this.slideInterval) {
            clearInterval(this.slideInterval);
            this.slideInterval = null;
        }
    }

    setupTouchEvents() {
        let startX = 0;
        let endX = 0;

        this.carousel.addEventListener('touchstart', (e) => {
            startX = e.touches[0].clientX;
        });

        this.carousel.addEventListener('touchend', (e) => {
            endX = e.changedTouches[0].clientX;
            this.handleSwipe(startX, endX);
        });
    }

    handleSwipe(startX, endX) {
        const swipeThreshold = 50;
        const diff = startX - endX;

        if (Math.abs(diff) > swipeThreshold) {
            if (diff > 0) {
                this.nextSlide(); // Swipe left
            } else {
                this.prevSlide(); // Swipe right
            }
        }
    }
}

// Cập nhật phần khởi tạo trong DOMContentLoaded
document.addEventListener('DOMContentLoaded', function() {
    new CountdownTimer();
    new CartManager();
    new SearchManager();
    new TabManager();
    new MobileNavigation();
    new BannerCarousel(); // Thêm dòng này

    // ... phần code còn lại giữ nguyên
});
// Featured Categories functionality
class FeaturedCategories {
    constructor() {
        this.categories = document.querySelectorAll('.category-item');
        this.init();
    }

    init() {
        this.categories.forEach(category => {
            category.addEventListener('click', () => {
                this.handleCategoryClick(category);
            });

            // Add keyboard navigation
            category.addEventListener('keypress', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    this.handleCategoryClick(category);
                }
            });

            // Add focus styles
            category.setAttribute('tabindex', '0');
        });
    }

    handleCategoryClick(category) {
        const categoryId = category.dataset.category;

        // Show loading state
        this.showLoadingState(category);

        // In real application, you would redirect to category page
        // For demo, we'll show a notification
        setTimeout(() => {
            this.showCategoryNotification(category);
        }, 500);
    }

    showLoadingState(category) {
        const originalContent = category.innerHTML;
        category.innerHTML = `
            <div class="category-loading">
                <i class="fas fa-spinner fa-spin"></i>
                <span>Đang tải...</span>
            </div>
        `;

        setTimeout(() => {
            category.innerHTML = originalContent;
        }, 500);
    }

    showCategoryNotification(category) {
        const categoryName = category.querySelector('.category-name').textContent;
        this.showNotification(`Đang chuyển đến danh mục: ${categoryName}`, 'info');
    }

    showNotification(message, type) {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
        notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        notification.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        document.body.appendChild(notification);

        // Auto remove after 3 seconds
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 3000);
    }
}

// Thêm CSS cho loading state
const style = document.createElement('style');
style.textContent = `
    .category-loading {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 10px;
        color: var(--primary);
    }

    .category-loading i {
        font-size: 24px;
    }

    .category-loading span {
        font-size: 14px;
        font-weight: 500;
    }
`;
document.head.appendChild(style);

// Cập nhật phần khởi tạo trong DOMContentLoaded
document.addEventListener('DOMContentLoaded', function() {
    new CountdownTimer();
    new CartManager();
    new SearchManager();
    new TabManager();
    new MobileNavigation();
    new BannerCarousel();
    new FeaturedCategories(); // Thêm dòng này

    // ... phần code còn lại
});
// Xử lý các nút lọc và sắp xếp
document.addEventListener("DOMContentLoaded", function () {
  const sortButtons = document.querySelectorAll(".sort-options button");
  sortButtons.forEach(btn => {
    btn.addEventListener("click", () => {
      sortButtons.forEach(b => b.classList.remove("active"));
      btn.classList.add("active");
    });
  });

  // Toggle mở rộng các nhóm lọc
  const filterTitles = document.querySelectorAll(".filter-group h4");
  filterTitles.forEach(title => {
    title.addEventListener("click", () => {
      title.nextElementSibling.classList.toggle("collapsed");
    });
  });
});
