import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['input', 'empty', 'count', 'list'];

    connect() {
        this.objectUrls = [];
        this.render();
    }

    disconnect() {
        this.revokeObjectUrls();
    }

    update() {
        this.render();
    }

    render() {
        if (!this.hasInputTarget || !this.hasListTarget) {
            return;
        }

        const files = Array.from(this.inputTarget.files || []).filter((file) => file.type.startsWith('image/'));

        this.revokeObjectUrls();
        this.listTarget.innerHTML = '';

        if (this.hasEmptyTarget) {
            this.emptyTarget.hidden = files.length > 0;
        }

        if (this.hasCountTarget) {
            if (files.length > 0) {
                this.countTarget.hidden = false;
                this.countTarget.textContent = `${files.length} photo${files.length > 1 ? 's' : ''} sélectionnée${files.length > 1 ? 's' : ''}`;
            } else {
                this.countTarget.hidden = true;
                this.countTarget.textContent = '';
            }
        }

        this.listTarget.hidden = files.length === 0;

        files.forEach((file, index) => {
            this.listTarget.appendChild(this.buildCard(file, index));
        });
    }

    buildCard(file, index) {
        const card = document.createElement('article');
        card.className = 'file-preview__card';

        const media = document.createElement('div');
        media.className = 'file-preview__media';

        const image = document.createElement('img');
        const objectUrl = URL.createObjectURL(file);
        this.objectUrls.push(objectUrl);

        image.src = objectUrl;
        image.alt = `Aperçu de la photo ${index + 1}`;

        media.appendChild(image);

        const body = document.createElement('div');
        body.className = 'file-preview__body';

        const name = document.createElement('div');
        name.className = 'file-preview__name';
        name.textContent = file.name;

        const meta = document.createElement('div');
        meta.className = 'file-preview__meta';
        meta.textContent = `${this.formatSize(file.size)} · Photo ${index + 1}`;

        body.appendChild(name);
        body.appendChild(meta);

        card.appendChild(media);
        card.appendChild(body);

        return card;
    }

    revokeObjectUrls() {
        this.objectUrls?.forEach((objectUrl) => URL.revokeObjectURL(objectUrl));
        this.objectUrls = [];
    }

    formatSize(size) {
        if (size < 1024) {
            return `${size} octets`;
        }

        const units = ['Ko', 'Mo', 'Go'];
        let value = size / 1024;
        let unitIndex = 0;

        while (value >= 1024 && unitIndex < units.length - 1) {
            value /= 1024;
            unitIndex += 1;
        }

        return `${new Intl.NumberFormat('fr-FR', {
            maximumFractionDigits: value >= 10 ? 0 : 1,
        }).format(value)} ${units[unitIndex]}`;
    }
}
