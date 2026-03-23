
const buttons = document.querySelectorAll('.buyBtn');
buttons.forEach(function(btn) {
    btn.addEventListener('click', function() {
        let product = btn.getAttribute('data-product');
        if (product) {
            localStorage.setItem("produsSelectat", product);
            window.location.href = "contact.html";
        }
    });
});

document.addEventListener("DOMContentLoaded", function() {
    
    let produs = localStorage.getItem("produsSelectat");
    let produsInput = document.getElementById("produs");
    let numeProdus = document.getElementById("numeProdus");

    if (produs && produsInput) {
        produsInput.value = produs;
        numeProdus.textContent = produs.replace(/-/g, ' '); // "buchet-trandafiri" → "buchet trandafiri"
    }

    let form = document.getElementById("orderForm");
    if (!form) return;

    form.addEventListener("submit", function(event) {
        event.preventDefault();

        let nume    = document.querySelector('input[placeholder="nume, prenume"]').value.trim();
        let email   = document.querySelector('input[placeholder="email"]').value.trim();
        let telefon = document.querySelector('input[placeholder="telefon"]').value.trim();
        let message = document.querySelector('textarea[placeholder="message"]').value.trim();
        let produs  = document.getElementById("produs").value;

        if (nume === "" || email === "" || telefon === "" || message === "") {
            alert("Vă rugăm să completați toate câmpurile!");
            return;
        }

        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            alert("Adresa de email nu este validă!");
            return;
        }

        const telefonRegex = /^\+?[0-9]{9,15}$/;
        if (!telefonRegex.test(telefon)) {
            alert("Numărul de telefon nu este valid!");
            return;
        }

        alert(`Comanda trimisă cu succes!\nBuchet: ${produs}\nNume: ${nume}`);
        localStorage.removeItem("produsSelectat");
        this.reset();
        if (numeProdus) numeProdus.textContent = "—";
    });
});