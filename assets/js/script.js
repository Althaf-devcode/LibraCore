document.addEventListener('DOMContentLoaded', () => {

    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const openBtn = document.getElementById('sidebarToggle');
    const closeBtn = document.getElementById('sidebarClose');

    const openSidebar = () => {
        sidebar && sidebar.classList.remove('-translate-x-full');
        overlay && overlay.classList.remove('hidden');
        document.body.classList.add('overflow-hidden', 'lg:overflow-auto');
    };
    const closeSidebar = () => {
        sidebar && sidebar.classList.add('-translate-x-full');
        overlay && overlay.classList.add('hidden');
        document.body.classList.remove('overflow-hidden', 'lg:overflow-auto');
    };

    if (openBtn) openBtn.addEventListener('click', openSidebar);
    if (closeBtn) closeBtn.addEventListener('click', closeSidebar);
    if (overlay) {
        overlay.addEventListener('click', closeSidebar);
        sidebar && sidebar.querySelectorAll('a').forEach((link) => {
            link.addEventListener('click', () => {
                if (window.innerWidth < 1024) closeSidebar();
            });
        });
    }

    const dismissToast = (toast) => {
        toast.style.transition = 'opacity .2s ease, transform .2s ease';
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(-8px)';
        setTimeout(() => toast.remove(), 220);
    };

    document.querySelectorAll('[data-toast]').forEach((toast) => {
        const timer = setTimeout(() => dismissToast(toast), 4500);
        const closeX = toast.querySelector('[data-toast-close]');
        if (closeX) {
            closeX.addEventListener('click', () => {
                clearTimeout(timer);
                dismissToast(toast);
            });
        }
    });

    document.addEventListener('submit', (event) => {
        const target = event.target.closest('[data-confirm]');
        if (target && !window.confirm(target.getAttribute('data-confirm'))) {
            event.preventDefault();
        }
    }, true);

    document.querySelectorAll('[data-confirm]').forEach((el) => {
        el.addEventListener('click', (event) => {
            if (el.tagName === 'A' && !window.confirm(el.getAttribute('data-confirm'))) {
                event.preventDefault();
            }
        });
    });

    document.querySelectorAll('[data-auto-submit]').forEach((el) => {
        el.addEventListener('change', () => {
            if (el.form) el.form.submit();
        });
    });

    const passwordToggles = document.querySelectorAll('[data-toggle-password]');
    passwordToggles.forEach((btn) => {
        btn.addEventListener('click', () => {
            const input = document.getElementById(btn.getAttribute('data-toggle-password'));
            if (!input) return;
            const isHidden = input.type === 'password';
            input.type = isHidden ? 'text' : 'password';
            btn.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
        });
    });

    const bookSelect = document.getElementById('book_id');
    if (bookSelect) {
        const panel = document.getElementById('book-info-panel');
        const setField = (id, value) => {
            const el = document.getElementById(id);
            if (el) el.textContent = value || '-';
        };

        const updateBookInfo = () => {
            const option = bookSelect.selectedOptions[0];
            if (!option || !option.dataset.title) {
                panel && panel.classList.add('hidden');
                return;
            }
            setField('bi-title', option.dataset.title);
            setField('bi-author', option.dataset.author);
            setField('bi-available', option.dataset.available + ' available');
            setField('bi-category', option.dataset.category);
            setField('bi-shelf', option.dataset.shelf ? 'Shelf: ' + option.dataset.shelf : '');
            panel && panel.classList.remove('hidden');
        };

        bookSelect.addEventListener('change', updateBookInfo);
        updateBookInfo();
    }

    const borrowDateInput = document.getElementById('borrow_date');
    const dueDateInput = document.getElementById('due_date');
    if (borrowDateInput && dueDateInput) {
        const syncMinDue = () => { dueDateInput.min = borrowDateInput.value || ''; };
        borrowDateInput.addEventListener('change', syncMinDue);
        syncMinDue();
    }

    document.querySelectorAll('form[data-validate]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!form.checkValidity()) {
                event.preventDefault();
                form.reportValidity();
                const firstInvalid = form.querySelector(':invalid');
                if (firstInvalid) {
                    firstInvalid.classList.add('border-red-400', 'ring-2', 'ring-red-100');
                    firstInvalid.focus();
                    firstInvalid.addEventListener('input', () => {
                        firstInvalid.classList.remove('border-red-400', 'ring-2', 'ring-red-100');
                    }, { once: true });
                }
            }
        });
    });

    const printButton = document.querySelector('[data-print]');
    if (printButton) {
        printButton.addEventListener('click', () => window.print());
    }
});
