import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['item'];

    connect() {
        this.timeouts = this.itemTargets.map((item, index) => window.setTimeout(() => {
            this.closeItem(item);
        }, 3200 + (index * 180)));
    }

    disconnect() {
        this.timeouts?.forEach((timeout) => window.clearTimeout(timeout));
        this.timeouts = [];
    }

    close(event) {
        const item = event.currentTarget.closest('[data-flash-toast-target="item"]');
        this.closeItem(item);
    }

    closeItem(item) {
        if (!item || item.dataset.closing === 'true') {
            return;
        }

        item.dataset.closing = 'true';
        item.classList.add('is-leaving');

        window.setTimeout(() => {
            item.remove();

            if (this.itemTargets.length === 0) {
                this.element.remove();
            }
        }, 220);
    }
}
