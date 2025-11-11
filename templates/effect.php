<?php
/**
 * Shared glassmorphism background + floating pills animation.
 * Usage:
 *   require_once __DIR__ . '/effect.php';
 *   render_pills_effect_assets(); // call once before </head> or prior to output that needs it
 */
if (!function_exists('render_pills_effect_assets')) {
    function render_pills_effect_assets(): void
    {
        static $rendered = false;
        if ($rendered) {
            return;
        }
        $rendered = true;
        ?>
        <style>
            .effect-pills-container {
                position: relative;
                border-radius: 22px;
                padding: 2px;
                overflow: hidden;
                isolation: isolate;
                box-shadow: 0 25px 45px rgba(2, 132, 199, 0.25);
                border: 1px solid rgba(255, 255, 255, 0.55);
                background: transparent;
            }

            .effect-pills-container::before {
                content: '';
                position: absolute;
                inset: -20%;
                background:
                        radial-gradient(circle at 20% 20%, rgba(255, 255, 255, 0.8), transparent 55%),
                        radial-gradient(circle at 80% 10%, rgba(186, 230, 253, 0.75), transparent 55%),
                        linear-gradient(180deg, #e0f7fa 0%, #b3e5fc 100%);
                z-index: 0;
                filter: blur(10px);
                transform: scale(1.1);
            }

            .effect-pills-container .effect-pills-content {
                position: relative;
                z-index: 2;
                border-radius: 18px;
                background: rgba(255, 255, 255, 0.78);
                backdrop-filter: blur(16px);
                -webkit-backdrop-filter: blur(16px);
                overflow: hidden;
            }

            .effect-pills-canvas {
                position: absolute;
                inset: 0;
                width: 100%;
                height: 100%;
                z-index: 1;
                pointer-events: none;
                opacity: 0.9;
            }
        </style>

        <script>
            (function () {
                if (window.PillsEffect) {
                    return;
                }

                class Pill {
                    constructor(effect, initial = false) {
                        this.effect = effect;
                        this.reset(initial);
                    }

                    reset(initial = false) {
                        const { canvas } = this.effect;
                        this.size = Math.random() * 6 + 5;
                        this.x = Math.random() * (canvas.width || 1);
                        this.y = initial
                            ? Math.random() * (canvas.height || 1)
                            : (canvas.height || 0) + this.size * 4;
                        this.speedY = Math.random() * 0.6 + 0.25;
                        this.color = this.effect.colors[Math.floor(Math.random() * this.effect.colors.length)];
                        this.opacity = Math.random() * 0.4 + 0.25;
                        this.density = Math.random() * 4 + 1;
                        this.angle = Math.random() * Math.PI * 2;
                        this.rotationSpeed = (Math.random() - 0.5) * 0.02;
                    }

                    update(mouse) {
                        const canvas = this.effect.canvas;
                        if (!canvas.width || !canvas.height) {
                            return;
                        }

                        if (mouse.x !== null && mouse.y !== null) {
                            const dx = mouse.x - this.x;
                            const dy = mouse.y - this.y;
                            const distance = Math.sqrt(dx * dx + dy * dy) || 0.001;

                            if (distance < mouse.radius) {
                                const force = (mouse.radius - distance) / mouse.radius;
                                this.x -= (dx / distance) * force * this.density;
                                this.y -= (dy / distance) * force * this.density;
                            }
                        }

                        this.y -= this.speedY;
                        this.angle += this.rotationSpeed;

                        if (this.y < -this.size * 4) {
                            this.reset();
                        }
                    }

                    draw(ctx) {
                        ctx.save();
                        ctx.translate(this.x, this.y);
                        ctx.rotate(this.angle);
                        ctx.globalAlpha = this.opacity;
                        ctx.shadowBlur = 12;
                        ctx.shadowColor = this.color;

                        const capsuleHeight = this.size;
                        const capsuleWidth = this.size * 2;

                        ctx.fillStyle = this.color;
                        ctx.beginPath();
                        ctx.arc(capsuleWidth / 4, 0, capsuleHeight / 2, -Math.PI / 2, Math.PI / 2, false);
                        ctx.arc(-capsuleWidth / 4, 0, capsuleHeight / 2, Math.PI / 2, -Math.PI / 2, false);
                        ctx.closePath();
                        ctx.fill();
                        ctx.restore();
                    }
                }

                class PillsEffect {
                    constructor(container) {
                        this.container = container;
                        this.canvas = document.createElement('canvas');
                        this.canvas.className = 'effect-pills-canvas';
                        container.insertBefore(this.canvas, container.firstChild);
                        this.ctx = this.canvas.getContext('2d');
                        this.colors = ['#ffffff', '#bae6fd', '#f0f9ff', '#0284c7'];
                        this.mouse = { x: null, y: null, radius: 90 };
                        this.pills = [];
                        this.animationFrame = null;
                        this.boundMouseMove = this.handleMouseMove.bind(this);
                        this.boundMouseLeave = this.handleMouseLeave.bind(this);
                        this.boundResize = this.resize.bind(this);
                        this.initListeners();
                        this.resize();
                        this.initPills();
                        this.animate();
                    }

                    initListeners() {
                        this.container.addEventListener('mousemove', this.boundMouseMove);
                        this.container.addEventListener('mouseleave', this.boundMouseLeave);

                        if ('ResizeObserver' in window) {
                            this.resizeObserver = new ResizeObserver(this.boundResize);
                            this.resizeObserver.observe(this.container);
                        } else {
                            window.addEventListener('resize', this.boundResize);
                        }
                    }

                    handleMouseMove(event) {
                        const rect = this.container.getBoundingClientRect();
                        this.mouse.x = event.clientX - rect.left;
                        this.mouse.y = event.clientY - rect.top;
                    }

                    handleMouseLeave() {
                        this.mouse.x = null;
                        this.mouse.y = null;
                    }

                    resize() {
                        const fallbackWidth = parseInt(this.container.getAttribute('data-effect-fallback-width'), 10) || 360;
                        const fallbackHeight = parseInt(this.container.getAttribute('data-effect-fallback-height'), 10) || 320;
                        const width = Math.round(this.container.clientWidth || fallbackWidth);
                        const height = Math.round(this.container.clientHeight || fallbackHeight);

                        if (!width || !height) {
                            return;
                        }

                        if (this.canvas.width === width && this.canvas.height === height) {
                            return;
                        }

                        this.canvas.width = width;
                        this.canvas.height = height;
                        this.initPills();
                    }

                    initPills() {
                        const area = (this.canvas.width || 1) * (this.canvas.height || 1);
                        const count = Math.min(80, Math.max(20, Math.floor(area / 4500)));
                        this.pills = Array.from({ length: count }, () => new Pill(this, true));
                    }

                    animate() {
                        this.animationFrame = requestAnimationFrame(() => this.animate());
                        this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
                        this.pills.forEach((pill) => {
                            pill.update(this.mouse);
                            pill.draw(this.ctx);
                        });
                    }
                }

                window.PillsEffect = function initPillsEffect(selector = '.effect-pills-container') {
                    document.querySelectorAll(selector).forEach((container) => {
                        if (container.dataset.effectInit === 'true') {
                            return;
                        }
                        container.dataset.effectInit = 'true';
                        new PillsEffect(container);
                    });
                };

                const startEffect = () => window.PillsEffect();

                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', startEffect);
                } else {
                    startEffect();
                }
            })();
        </script>
        <?php
    }
}
