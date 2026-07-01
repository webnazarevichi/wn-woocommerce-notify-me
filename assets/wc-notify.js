document.addEventListener('DOMContentLoaded', function() {
    const btn = document.getElementById('wc-notify-btn');
    const modal = document.getElementById('wc-notify-modal');
    const closeBtn = document.getElementById('wc-notify-close');
    const form = document.getElementById('wc-notify-form');
    const msgBlock = document.getElementById('wc-notify-message');

    if (!btn || !modal) return;

    // Открытие попапа
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        document.getElementById('wc-notify-product-id').value = this.dataset.productId;
        
        // Логика для вариаций: ищем скрытое поле вариации WC
        const variationInput = document.querySelector('input.variation_id');
        if (variationInput && variationInput.value) {
            document.getElementById('wc-notify-variation-id').value = variationInput.value;
        }

        modal.style.display = 'flex';
        msgBlock.innerHTML = '';
    });

    // Закрытие попапа
    closeBtn.addEventListener('click', () => modal.style.display = 'none');
    window.addEventListener('click', (e) => { if (e.target === modal) modal.style.display = 'none'; });

    // Отправка формы (Fetch API)
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        msgBlock.innerHTML = 'Отправка...';
        msgBlock.style.color = '#333';

        const data = {
            email: document.getElementById('wc-notify-email').value,
            product_id: document.getElementById('wc-notify-product-id').value,
            variation_id: document.getElementById('wc-notify-variation-id').value,
            website_url_hp: document.querySelector('input[name="website_url_hp"]').value // Honeypot
        };

        fetch(wcNotifyParams.restUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': wcNotifyParams.nonce
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(res => {
            msgBlock.innerHTML = res.message || res.data?.message || 'Ошибка сервера';
            msgBlock.style.color = (res.success || res.code === 'exists') ? 'green' : 'red';
            if (res.success) form.reset();
        })
        .catch(error => {
            msgBlock.innerHTML = 'Произошла ошибка при отправке.';
            msgBlock.style.color = 'red';
        });
    });
});