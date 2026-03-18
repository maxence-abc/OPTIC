import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['burgerButton', 'accountContainer', 'accountButton', 'accountPanel'];

    toggleNav(event) {
        event.preventDefault();

        const isOpen = !this.element.classList.contains('is-open');
        this.element.classList.toggle('is-open', isOpen);

        if (isOpen) {
            this.closeAccount();
        }

        if (this.hasBurgerButtonTarget) {
            this.burgerButtonTarget.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        }
    }

    toggleAccount(event) {
        event.preventDefault();

        if (!this.hasAccountPanelTarget) {
            return;
        }

        if (this.accountPanelTarget.hidden) {
            this.openAccount();
            return;
        }

        this.closeAccount();
    }

    closeAll() {
        this.closeNav();
        this.closeAccount();
    }

    closeOnOutside(event) {
        if (this.hasAccountContainerTarget && !this.accountContainerTarget.contains(event.target)) {
            this.closeAccount();
        }

        if (!this.element.contains(event.target)) {
            this.closeNav();
        }
    }

    openAccount() {
        this.accountPanelTarget.hidden = false;
        this.accountButtonTarget.setAttribute('aria-expanded', 'true');
        this.accountContainerTarget.classList.add('is-open');
    }

    closeAccount() {
        if (!this.hasAccountPanelTarget) {
            return;
        }

        this.accountPanelTarget.hidden = true;
        this.accountContainerTarget.classList.remove('is-open');

        if (this.hasAccountButtonTarget) {
            this.accountButtonTarget.setAttribute('aria-expanded', 'false');
        }
    }

    closeNav() {
        this.element.classList.remove('is-open');

        if (this.hasBurgerButtonTarget) {
            this.burgerButtonTarget.setAttribute('aria-expanded', 'false');
        }
    }
}
