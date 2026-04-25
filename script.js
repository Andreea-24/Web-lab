const buttons = document.querySelectorAll('.buyBtn');
buttons.forEach(function (btn) {
    btn.addEventListener('click', function () {
        const product = btn.getAttribute('data-product');
        if (product) {
            localStorage.setItem('produsSelectat', product);
            window.location.href = '/contact';
        }
    });
});

document.addEventListener('DOMContentLoaded', function () {
    const produs = localStorage.getItem('produsSelectat');
    const produsInput = document.getElementById('produs');
    const numeProdus = document.getElementById('numeProdus');

    if (produs && produsInput) {
        produsInput.value = produs;
        if (numeProdus) numeProdus.textContent = produs.replace(/-/g, ' ');
    }

    const form = document.getElementById('orderForm');
    if (!form) return;

    form.addEventListener('submit', function (event) {
        const nume    = form.querySelector('input[name="nume"]').value.trim();
        const email   = form.querySelector('input[name="email"]').value.trim();
        const telefon = form.querySelector('input[name="telefon"]').value.trim();
        const message = form.querySelector('textarea[name="message"]').value.trim();

        if (!nume || !email || !telefon || !message) {
            event.preventDefault();
            alert('Vă rugăm să completați toate câmpurile!');
            return;
        }
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            event.preventDefault();
            alert('Adresa de email nu este validă!');
            return;
        }
        if (!/^\+?[0-9]{9,15}$/.test(telefon)) {
            event.preventDefault();
            alert('Numărul de telefon nu este valid!');
            return;
        }
        localStorage.removeItem('produsSelectat');
    });
});
