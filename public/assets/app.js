(function () {
    document.querySelectorAll("[data-auto-dismiss]").forEach(function (alert) {
        window.setTimeout(function () {
            alert.remove();
        }, 10000);
    });

    document.querySelectorAll("[data-toggle-radio]").forEach(function (radio) {
        let wasCheckedOnPointerDown = false;

        radio.addEventListener("pointerdown", function () {
            wasCheckedOnPointerDown = radio.checked;
        });

        radio.addEventListener("click", function () {
            if (wasCheckedOnPointerDown && radio.checked) {
                radio.checked = false;
                radio.dispatchEvent(new Event("change", { bubbles: true }));
            }
            wasCheckedOnPointerDown = false;
        });
    });

    const toggle = document.querySelector("[data-menu]");
    const sidebar = document.querySelector(".sidebar");
    if (toggle && sidebar) {
        toggle.addEventListener("click", function () {
            const open = sidebar.classList.toggle("open");
            toggle.setAttribute("aria-expanded", open ? "true" : "false");
        });
    }

    const cart = document.querySelector("[data-cart]");
    if (cart) {
        const discount = cart.querySelector("[name=discount]");
        const tax = cart.querySelector("[name=tax]");
        const subtotal = Number(cart.getAttribute("data-subtotal") || "0");
        const grand = cart.querySelector("[data-grand]");
        const taxOut = cart.querySelector("[data-tax-amount]");
        const refresh = function () {
            const discountValue = Number(discount && discount.value ? discount.value : "0");
            const taxPercent = Number(tax && tax.value ? tax.value : "0");
            const taxAmount = subtotal * (Number.isFinite(taxPercent) ? taxPercent : 0) / 100;
            const total = subtotal - (Number.isFinite(discountValue) ? discountValue : 0) + taxAmount;
            if (taxOut) taxOut.textContent = taxAmount.toFixed(2);
            if (grand) grand.textContent = total.toFixed(2);
        };
        discount && discount.addEventListener("input", refresh);
        tax && tax.addEventListener("input", refresh);
    }

    const reason = document.querySelector("[data-reason]");
    const worker = document.querySelector("[data-worker]");
    if (reason && worker) {
        const sync = function () {
            worker.hidden = reason.value !== "Worker Salary";
        };
        reason.addEventListener("change", sync);
        sync();
    }

})();
