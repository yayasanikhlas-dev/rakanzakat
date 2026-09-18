(function () {
  function mapType(key) {
    if (!key) return "";
    if (key === "lain") return "lain-lain";
    return key;
  }

  document.addEventListener("click", function (e) {
    var btn = e.target.closest("[data-rz-zakat]");
    if (btn) {
      var type = mapType(btn.getAttribute("data-rz-zakat"));
      var sel = document.querySelector(".js-rakanzakat-form [name='zakat_type']");
      if (sel && type) {
        sel.value = type;
        sel.dispatchEvent(new Event("change", { bubbles: true }));
      }
    }

    var card = e.target.closest && e.target.closest(".rzs-cat--s2");
    document.querySelectorAll(".rzs-cat--s2.is-open").forEach(function (openCard) {
      if (openCard !== card) openCard.classList.remove("is-open");
    });
    if (!card || e.target.closest("a")) return;
    if (window.matchMedia && window.matchMedia("(hover: hover)").matches) return;
    card.classList.toggle("is-open");
  });

  document.addEventListener("toggle", function (e) {
    var item = e.target;
    if (!item || !item.classList || !item.classList.contains("rzs-acc") || !item.open) return;
    var wrap = item.parentElement;
    if (!wrap) return;
    wrap.querySelectorAll("details.rzs-acc").forEach(function (other) {
      if (other !== item) other.open = false;
    });
  }, true);

  function moneyParts(n) {
    return Number(n || 0).toLocaleString("en-US", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  function num(el) {
    if (!el) return 0;
    var n = parseFloat(String(el.value).replace(/,/g, ""));
    return isNaN(n) ? 0 : n;
  }

  function updateCalc(root) {
    if (!root) return;
    var rate = parseFloat(root.getAttribute("data-rate") || "2.5") || 2.5;
    var nisabY = parseFloat(root.getAttribute("data-nisab-year") || "0") || 0;
    var nisabM = parseFloat(root.getAttribute("data-nisab-month") || "0") || 0;
    var typeEl = root.querySelector(".js-rz-calc-type");
    var opt = typeEl && typeEl.options[typeEl.selectedIndex];
    var mode = opt ? opt.getAttribute("data-mode") : "income";
    var income = root.querySelector(".js-rz-calc-income");
    var amountWrap = root.querySelector(".js-rz-calc-amount");
    if (income) income.hidden = mode !== "income";
    if (amountWrap) amountWrap.hidden = mode === "income";

    var zakat = 0;
    var wajib = false;
    var formula = "Formula: —";
    var hint = root.querySelector(".js-rz-calc-hint");

    if (mode === "income") {
      var gaji = num(root.querySelector(".js-rz-calc-gaji"));
      var kifayah = num(root.querySelector(".js-rz-calc-kifayah"));
      var bersih = Math.max(0, gaji - kifayah);
      zakat = bersih * 12 * (rate / 100);
      wajib = nisabM > 0 ? bersih >= nisabM : zakat > 0;
      formula = "Formula: (RM" + moneyParts(bersih) + " bersih/bln × 12) × " + rate + "%";
      if (hint) hint.textContent = "Kiraan: Bulanan × 12 bulan";
    } else {
      var nilai = num(root.querySelector(".js-rz-calc-nilai"));
      zakat = nilai * (rate / 100);
      wajib = nisabY > 0 ? nilai >= nisabY : zakat > 0;
      formula = "Formula: (RM" + moneyParts(nilai) + " × " + rate + "%)";
      if (hint) hint.textContent = "Kiraan: Nilai × " + rate + "%";
    }

    if (!wajib) zakat = 0;

    var total = root.querySelector(".js-rz-calc-total");
    var badge = root.querySelector(".js-rz-calc-badge");
    var formulaEl = root.querySelector(".js-rz-calc-formula");
    if (total) total.textContent = moneyParts(zakat);
    if (formulaEl) formulaEl.textContent = formula;
    if (badge) {
      badge.textContent = wajib ? "Wajib Zakat" : "Tidak Wajib";
      badge.classList.toggle("is-yes", wajib);
      badge.classList.toggle("is-no", !wajib);
    }
    root.setAttribute("data-zakat", String(zakat));
  }

  function fillPayForm(root) {
    var typeEl = root.querySelector(".js-rz-calc-type");
    var type = typeEl ? typeEl.value : "pendapatan";
    var amount = parseFloat(root.getAttribute("data-zakat") || "0") || 0;
    var form = document.querySelector(".js-rakanzakat-form form");
    if (!form) return;
    var sel = form.querySelector("[name='zakat_type']");
    var amt = form.querySelector("[name='amount']");
    if (sel && type) {
      sel.value = type;
      sel.dispatchEvent(new Event("change", { bubbles: true }));
    }
    if (amt) {
      amt.value = amount > 0 ? amount.toFixed(2) : "";
      amt.dispatchEvent(new Event("input", { bubbles: true }));
    }
  }

  document.addEventListener("input", function (e) {
    var root = e.target && e.target.closest && e.target.closest(".js-rz-calc");
    if (root) updateCalc(root);
  });
  document.addEventListener("change", function (e) {
    var root = e.target && e.target.closest && e.target.closest(".js-rz-calc");
    if (root) updateCalc(root);
  });
  document.addEventListener("submit", function (e) {
    var root = e.target && e.target.classList && e.target.classList.contains("js-rz-calc") ? e.target : null;
    if (!root) return;
    e.preventDefault();
    updateCalc(root);
    fillPayForm(root);
    var href = root.getAttribute("action") || "#bayar";
    if (href.charAt(0) === "#") {
      var el = document.querySelector(href);
      if (el) el.scrollIntoView({ behavior: "smooth", block: "start" });
      else window.location.hash = href;
    } else {
      window.location.href = href;
    }
  });

  function initCalcs() {
    document.querySelectorAll(".js-rz-calc").forEach(updateCalc);
  }
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initCalcs);
  } else {
    initCalcs();
  }
})();
