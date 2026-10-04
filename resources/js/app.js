import './bootstrap';

document.addEventListener('alpine:init', () => {
    // Two-row opposite-direction category marquee (homepage + shop strip)
    Alpine.data('categoryMarquee', (direction) => ({
        direction: direction,
        paused: false,
        dragging: false,
        moved: false,
        startX: 0,
        startScroll: 0,
        halfWidth: 1,
        speed: 0.6,
        track: null,
        alive: true,

        init() {
            this.track = this.$refs.track;
            this.measure();
            this._resizeHandler = () => this.measure();
            window.addEventListener('resize', this._resizeHandler);
            this.loop();
        },

        destroy() {
            this.alive = false;
            window.removeEventListener('resize', this._resizeHandler);
        },

        measure() {
            this.halfWidth = (this.track.scrollWidth / 2) || 1;
            if (this.direction === 'right') {
                this.track.scrollLeft = this.halfWidth;
            }
        },

        loop() {
            if (!this.alive) return;
            if (!this.paused && !this.dragging && this.track) {
                if (this.direction === 'left') {
                    this.track.scrollLeft += this.speed;
                    if (this.track.scrollLeft >= this.halfWidth) {
                        this.track.scrollLeft -= this.halfWidth;
                    }
                } else {
                    this.track.scrollLeft -= this.speed;
                    if (this.track.scrollLeft <= 0) {
                        this.track.scrollLeft += this.halfWidth;
                    }
                }
            }
            requestAnimationFrame(() => this.loop());
        },

        startDrag(e) {
            this.dragging = true;
            this.moved = false;
            this.startX = e.clientX;
            this.startScroll = this.track.scrollLeft;
        },

        onDrag(e) {
            if (!this.dragging) return;
            const delta = e.clientX - this.startX;
            if (Math.abs(delta) > 5) {
                this.moved = true;
                e.preventDefault();
            }
            this.track.scrollLeft = this.startScroll - delta;
        },

        endDrag() {
            this.dragging = false;
        },

        onClick(e) {
            if (this.moved) {
                e.preventDefault();
                e.stopPropagation();
                this.moved = false;
            }
        },
    }));
});

