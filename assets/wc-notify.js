document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('wc-notify-modal');
    const closeBtn = document.getElementById('wc-notify-close');
    const form = document.getElementById('wc-notify-form');
    const msgBlock = document.getElementById('wc-notify-message');

    if (!modal) return;

    // Открытие попапа (делегирование событий, т.к. кнопок может быть несколько в каталоге)
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.open-notify-form');
        if (btn) {
            e.preventDefault();
            document.getElementById('wc-notify-product-id').value = btn.dataset.productId;
            
            // Логика для вариаций: ищем скрытое поле вариации WC
            const variationInput = document.querySelector('input.variation_id');
            if (variationInput && variationInput.value) {
                document.getElementById('wc-notify-variation-id').value = variationInput.value;
            } else {
                document.getElementById('wc-notify-variation-id').value = 0;
            }

            modal.style.display = 'flex';
            msgBlock.innerHTML = '';
        }
    });

    // Интеграция с вариативными товарами WooCommerce через jQuery события
    if (typeof jQuery !== 'undefined') {
        jQuery(document).on('show_variation', '.variations_form', function(event, variation) {
            const notifyBtn = document.getElementById('wc-notify-btn');
            if (variation.is_in_stock) {
                if (notifyBtn) notifyBtn.style.display = 'none';
            } else {
                if (notifyBtn) {
                    notifyBtn.style.display = 'inline-block';
                    document.getElementById('wc-notify-variation-id').value = variation.variation_id;
                }
            }
        });
        
        jQuery(document).on('hide_variation', '.variations_form', function(event) {
            const notifyBtn = document.getElementById('wc-notify-btn');
            if (notifyBtn) notifyBtn.style.display = 'none';
        });
    }

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