(function () {
  if (typeof RakanZakatPay === "undefined") return;

  function ids() {
    try {
      return {
        visitor_id: localStorage.getItem("rz_vid") || "",
        session_id: sessionStorage.getItem("rz_sid") || "",
        landing_page: sessionStorage.getItem("rz_land") || location.href,
        referrer: document.referrer || "",
      };
    } catch (e) {
      return { visitor_id: "", session_id: "", landing_page: location.href, referrer: document.referrer || "" };
    }
  }

  function utm() {
    var p = new URLSearchParams(location.search);
    return {
      utm_source: p.get("utm_source") || sessionStorage.getItem("rz_utm_source") || "",
      utm_medium: p.get("utm_medium") || sessionStorage.getItem("rz_utm_medium") || "",
      utm_campaign: p.get("utm_campaign") || sessionStorage.getItem("rz_utm_campaign") || "",
      utm_content: p.get("utm_content") || sessionStorage.getItem("rz_utm_content") || "",
      utm_term: p.get("utm_term") || sessionStorage.getItem("rz_utm_term") || "",
    };
  }

  function money(value) {
    var n = parseFloat(value);
    if (isNaN(n) || n <= 0) return "RM0.00";
    return "RM" + n.toLocaleString("en-US", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  function updateNiat(form) {
    var amount = form.querySelector('[name="amount"]');
    var label = form.querySelector(".js-rz-niat-amount");
    if (amount && label) label.textContent = money(amount.value);
    var current = parseFloat(amount && amount.value);
    form.querySelectorAll(".chip-btn").forEach(function (btn) {
      btn.classList.toggle("active", parseFloat(btn.getAttribute("data-amount")) === current);
    });
  }

  function val(form, name) {
    var el = form.elements[name];
    if (!el) return "";
    if (el.type === "checkbox") return el.checked ? "1" : "";
    return el.value;
  }

  document.addEventListener("input", function (e) {
    var form = e.target.closest && e.target.closest(".js-rakanzakat-form form");
    if (!form) return;
    if (e.target.name === "mobile" || e.target.name === "postcode") {
      e.target.value = e.target.value.replace(/[^0-9]/g, "");
      if (e.target.name === "postcode") e.target.value = e.target.value.slice(0, 5);
    }
    updateNiat(form);
  });

  document.addEventListener("click", function (e) {
    var btn = e.target.closest(".chip-btn");
    if (!btn) return;
    var form = btn.closest(".js-rakanzakat-form form");
    if (!form) return;
    e.preventDefault();
    form.querySelectorAll(".chip-btn").forEach(function (b) {
      b.classList.remove("active");
    });
    btn.classList.add("active");
    form.querySelector('[name="amount"]').value = btn.getAttribute("data-amount");
    updateNiat(form);
  });

  document.addEventListener("submit", function (e) {
    var form = e.target;
    if (!form || !form.closest || !form.closest(".js-rakanzakat-form")) return;
    e.preventDefault();

    var errorEl = form.querySelector(".rz-error");
    var submit = form.querySelector(".rzf-submit");
    if (errorEl) errorEl.hidden = true;
    if (submit) {
      submit.disabled = true;
      submit.dataset.rzOriginal = submit.dataset.rzOriginal || submit.textContent;
      submit.textContent = "Sedang buka Billplz…";
    }

    var payload = Object.assign(
      {
        name: val(form, "name"),
        email: val(form, "email"),
        mobile: val(form, "mobile"),
        id_type: val(form, "id_type"),
        address_1: val(form, "address_1"),
        address_2: val(form, "address_2"),
        city: val(form, "city"),
        state: val(form, "state"),
        postcode: val(form, "postcode"),
        zakat_type: val(form, "zakat_type"),
        haul_year: val(form, "haul_year"),
        niat: val(form, "niat"),
        amount: val(form, "amount"),
      },
      ids(),
      utm()
    );

    fetch(RakanZakatPay.endpoint, {
      method: "POST",
      credentials: "same-origin",
      headers: {
        "Content-Type": "application/json",
        "X-WP-Nonce": RakanZakatPay.nonce,
      },
      body: JSON.stringify(payload),
    })
      .then(function (res) {
        return res.json().then(function (body) {
          if (!res.ok) {
            throw new Error(body.message || "Gagal mencipta bil.");
          }
          return body;
        });
      })
      .then(function (body) {
        if (!body.url) throw new Error("URL pembayaran tidak diterima.");
        window.location.href = body.url;
      })
      .catch(function (err) {
        if (errorEl) {
          errorEl.hidden = false;
          errorEl.textContent = err.message || "Ralat pembayaran.";
        }
        if (submit) {
          submit.disabled = false;
          submit.textContent = submit.dataset.rzOriginal || "Bayar Sekarang";
        }
      });
  });
})();
