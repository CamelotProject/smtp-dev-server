window.addEventListener('load', () => {
    const element = document.getElementById('toasts');
    if (!element) {
        console.error('Unable to find element with the ID "toasts"');
        return;
    }

    JSON.parse(element.dataset.toasts).forEach((toast) => {
        toast.position = 'top-right';
        toast.duration = 5000;
        toast.pauseOnHover = true;
        bulmaToast.toast(toast)
    });
});

window.addEventListener('load', () => {
    document.querySelectorAll('.tabs').forEach((tabs) => {
        const textTab = tabs.querySelector('.tabs-header .tab-text');
        const textContent = tabs.querySelector('.tabs-content .tab-content.tab-content-text');
        const htmlTab = tabs.querySelector('.tabs-header .tab-html');
        const htmlContent = tabs.querySelector('.tabs-content .tab-content.tab-content-html');

        if (!textTab.classList.contains('is-disabled')) {
            textTab.addEventListener('click', () => {
                textContent.classList.remove('is-hidden');
                htmlContent.classList.add('is-hidden');
            });
        }
        if (!htmlTab.classList.contains('is-disabled')) {
            htmlTab.addEventListener('click', () => {
                textContent.classList.add('is-hidden');
                htmlContent.classList.remove('is-hidden');
            });
        }
    });
});
