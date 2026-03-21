import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['value'];
    static values = {
        duration: { type: Number, default: 1800 },
    };

    connect() {
        this.activeAnimations = [];

        if (!this.hasValueTarget) {
            return;
        }

        this.valueTargets.forEach((value) => {
            value.dataset.countupFinal = value.textContent.trim();
            value.textContent = this.formatValue(0, value.dataset.countupSuffix || '');
        });

        this.observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) {
                    return;
                }

                this.start();
                this.observer?.disconnect();
            });
        }, {
            threshold: 0.35,
        });

        this.observer.observe(this.element);
    }

    disconnect() {
        this.observer?.disconnect();
        this.activeAnimations.forEach((frame) => window.cancelAnimationFrame(frame));
        this.activeAnimations = [];
    }

    start() {
        this.valueTargets.forEach((value, index) => {
            this.animateValue(value, index * 90);
        });
    }

    animateValue(element, delay = 0) {
        const endValue = Number(element.dataset.countupEnd || 0);
        const suffix = element.dataset.countupSuffix || '';
        const duration = this.durationValue;
        const startAt = performance.now() + delay;

        element.classList.add('is-counting');

        const step = (timestamp) => {
            if (timestamp < startAt) {
                const frame = window.requestAnimationFrame(step);
                this.activeAnimations.push(frame);
                return;
            }

            const progress = Math.min((timestamp - startAt) / duration, 1);
            const easedProgress = 1 - Math.pow(1 - progress, 3);
            const currentValue = Math.round(endValue * easedProgress);

            element.textContent = this.formatValue(currentValue, suffix);

            if (progress < 1) {
                const frame = window.requestAnimationFrame(step);
                this.activeAnimations.push(frame);
                return;
            }

            element.textContent = element.dataset.countupFinal || this.formatValue(endValue, suffix);
            element.classList.remove('is-counting');
        };

        const frame = window.requestAnimationFrame(step);
        this.activeAnimations.push(frame);
    }

    formatValue(value, suffix) {
        return `${new Intl.NumberFormat('fr-FR').format(value)}${suffix}`;
    }
}
