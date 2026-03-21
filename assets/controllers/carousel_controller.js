import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['track', 'dot'];
    static values = {
        interval: { type: Number, default: 5000 },
    };

    connect() {
        this.index = 0;
        this.slidesCount = this.hasDotTarget ? this.dotTargets.length : 0;
        this.show(this.index);
        this.startAutoplay();
    }

    disconnect() {
        this.stopAutoplay();
    }

    prev() {
        this.show(this.index - 1);
        this.restartAutoplay();
    }

    next() {
        this.show(this.index + 1);
        this.restartAutoplay();
    }

    goTo(event) {
        const nextIndex = Number(event.currentTarget.dataset.index || 0);
        this.show(nextIndex);
        this.restartAutoplay();
    }

    show(index) {
        if (!this.hasTrackTarget || this.slidesCount < 2) {
            return;
        }

        this.index = (index + this.slidesCount) % this.slidesCount;
        this.trackTarget.style.transform = `translateX(-${this.index * 100}%)`;

        this.dotTargets.forEach((dot, dotIndex) => {
            dot.classList.toggle('is-active', dotIndex === this.index);
        });
    }

    startAutoplay() {
        if (this.slidesCount < 2) {
            return;
        }

        this.autoplayTimer = window.setInterval(() => {
            this.show(this.index + 1);
        }, this.intervalValue);
    }

    stopAutoplay() {
        if (this.autoplayTimer) {
            window.clearInterval(this.autoplayTimer);
            this.autoplayTimer = null;
        }
    }

    restartAutoplay() {
        this.stopAutoplay();
        this.startAutoplay();
    }
}
