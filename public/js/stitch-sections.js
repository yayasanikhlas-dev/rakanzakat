(function () {
  function mapType(key) {
    if (!key) return "";
    if (key === "lain") return "lain-lain";
    return key;
  }

  document.addEventListener("click", function (e) {
    var btn = e.target.closest("[data-rz-zakat]");
    if (!btn) return;
    var type = mapType(btn.getAttribute("data-rz-zakat"));
    var sel = document.querySelector(".js-rakanzakat-form [name='zakat_type']");
    if (sel && type) {
      sel.value = type;
      sel.dispatchEvent(new Event("change", { bubbles: true }));
    }
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
})();
