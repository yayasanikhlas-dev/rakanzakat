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

  function trimRate(n) {
    var s = Number(n || 0).toFixed(2);
    return s.replace(/\.?0+$/, "");
  }

  function parseCfg(opt, root) {
    var cfg = {};
    if (opt && opt.getAttribute("data-cfg")) {
      try {
        cfg = JSON.parse(opt.getAttribute("data-cfg")) || {};
      } catch (err) {
        cfg = {};
      }
    }
    var mode = cfg.mode || (opt && opt.getAttribute("data-mode")) || "income";
    return {
      mode: mode,
      rate: cfg.rate != null ? parseFloat(cfg.rate) : parseFloat(root.getAttribute("data-rate") || "2.5") || 2.5,
      nisab_year: cfg.nisab_year != null ? parseFloat(cfg.nisab_year) : parseFloat(root.getAttribute("data-nisab-year") || "0") || 0,
      nisab_month: cfg.nisab_month != null ? parseFloat(cfg.nisab_month) : parseFloat(root.getAttribute("data-nisab-month") || "0") || 0,
      skip_nisab: !!cfg.skip_nisab,
      field_a_label: cfg.field_a_label || "Gaji Bulanan (RM)",
      field_b_label: cfg.field_b_label || "Tolakan Had Kifayah (RM)",
      field_a_value: cfg.field_a_value,
      field_b_value: cfg.field_b_value,
      amount_label: cfg.amount_label || (mode === "head" ? "Bilangan individu" : "Nilai / Amaun (RM)"),
      amount_value: cfg.amount_value,
      period: cfg.period == null ? "/ setahun" : String(cfg.period),
      hint: cfg.hint || ""
    };
  }

  function setText(el, text) {
    if (el) el.textContent = text || "";
  }

  function applyTypeUi(root, cfg, resetValues) {
    var income = root.querySelector(".js-rz-calc-income");
    var amountWrap = root.querySelector(".js-rz-calc-amount");
    if (income) income.hidden = cfg.mode !== "income";
    if (amountWrap) amountWrap.hidden = cfg.mode === "income";
    setText(root.querySelector(".js-rz-calc-gaji-label"), cfg.field_a_label);
    setText(root.querySelector(".js-rz-calc-kifayah-label"), cfg.field_b_label);
    setText(root.querySelector(".js-rz-calc-nilai-label"), cfg.amount_label);
    var chip = root.querySelector(".js-rz-calc-rate-chip");
    if (chip) {
      if (cfg.mode === "head") chip.textContent = "Kadar RM" + trimRate(cfg.rate) + "/orang";
      else if (cfg.mode === "flat") chip.textContent = "Amaun terus";
      else chip.textContent = "Kadar " + trimRate(cfg.rate) + "%";
    }
    var title = root.querySelector(".js-rz-calc-result-title");
    if (title) {
      title.textContent = (cfg.mode === "head" || cfg.mode === "flat")
        ? "Jumlah Zakat Wajib"
        : "Jumlah Zakat Wajib (" + trimRate(cfg.rate) + "%)";
    }
    var period = root.querySelector(".js-rz-calc-period");
    if (period) {
      period.textContent = cfg.period || "";
      period.hidden = !cfg.period;
    }
    var nisab = root.querySelector(".js-rz-calc-nisab");
    if (nisab) {
      if (cfg.skip_nisab) {
        nisab.textContent = "Tiada syarat nisab";
      } else if (cfg.mode === "income") {
        nisab.innerHTML = "Nisab bulanan: <strong>RM" + moneyParts(cfg.nisab_month) + "</strong>";
      } else {
        nisab.innerHTML = "Nisab tahunan: <strong>RM" + moneyParts(cfg.nisab_year) + "</strong>";
      }
    }
    if (resetValues) {
      var gajiEl = root.querySelector(".js-rz-calc-gaji");
      var kifayahEl = root.querySelector(".js-rz-calc-kifayah");
      var nilaiEl = root.querySelector(".js-rz-calc-nilai");
      if (gajiEl && cfg.field_a_value != null) gajiEl.value = cfg.field_a_value;
      if (kifayahEl && cfg.field_b_value != null) kifayahEl.value = cfg.field_b_value;
      if (nilaiEl && cfg.amount_value != null) nilaiEl.value = cfg.amount_value;
    }
  }

  function updateCalc(root, resetValues) {
    if (!root) return;
    var typeEl = root.querySelector(".js-rz-calc-type");
    var opt = typeEl && typeEl.options[typeEl.selectedIndex];
    var cfg = parseCfg(opt, root);
    applyTypeUi(root, cfg, !!resetValues);

    var zakat = 0;
    var wajib = false;
    var formula = "Formula: —";
    var hint = root.querySelector(".js-rz-calc-hint");
    var hintText = cfg.hint || "";

    if (cfg.mode === "income") {
      var gaji = num(root.querySelector(".js-rz-calc-gaji"));
      var kifayah = num(root.querySelector(".js-rz-calc-kifayah"));
      var bersih = Math.max(0, gaji - kifayah);
      zakat = bersih * 12 * (cfg.rate / 100);
      wajib = cfg.skip_nisab ? zakat > 0 : (cfg.nisab_month > 0 ? bersih >= cfg.nisab_month : zakat > 0);
      formula = "Formula: (RM" + moneyParts(bersih) + " bersih/bln × 12) × " + trimRate(cfg.rate) + "%";
      if (!hintText) hintText = "Kiraan: Bulanan × 12 bulan";
    } else if (cfg.mode === "head") {
      var heads = num(root.querySelector(".js-rz-calc-nilai"));
      zakat = heads * cfg.rate;
      wajib = cfg.skip_nisab ? heads > 0 : (cfg.nisab_year > 0 ? zakat >= cfg.nisab_year : heads > 0);
      formula = "Formula: " + heads + " orang × RM" + trimRate(cfg.rate);
      if (!hintText) hintText = "Kiraan: bilangan × kadar RM/orang";
    } else if (cfg.mode === "flat") {
      zakat = num(root.querySelector(".js-rz-calc-nilai"));
      wajib = zakat > 0;
      formula = "Formula: amaun terus RM" + moneyParts(zakat);
      if (!hintText) hintText = "Kiraan: amaun yang diisi";
    } else {
      var nilai = num(root.querySelector(".js-rz-calc-nilai"));
      zakat = nilai * (cfg.rate / 100);
      wajib = cfg.skip_nisab ? zakat > 0 : (cfg.nisab_year > 0 ? nilai >= cfg.nisab_year : zakat > 0);
      formula = "Formula: (RM" + moneyParts(nilai) + " × " + trimRate(cfg.rate) + "%)";
      if (!hintText) hintText = "Kiraan: Nilai × " + trimRate(cfg.rate) + "%";
    }

    if (!wajib) zakat = 0;
    if (hint) hint.textContent = hintText;

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
    if (!root) return;
    var isType = e.target && e.target.classList && e.target.classList.contains("js-rz-calc-type");
    updateCalc(root, isType);
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
    document.querySelectorAll(".js-rz-calc").forEach(function (root) {
      updateCalc(root);
    });
  }
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initCalcs);
  } else {
    initCalcs();
  }
  window.addEventListener("elementor/frontend/init", function () {
    if (!window.elementorFrontend || !window.elementorFrontend.hooks) return;
    window.elementorFrontend.hooks.addAction("frontend/element_ready/rakanzakat_calc.default", function ($scope) {
      var el = $scope && $scope[0] ? $scope[0].querySelector(".js-rz-calc") : null;
      if (el) updateCalc(el);
    });
  });
})();
