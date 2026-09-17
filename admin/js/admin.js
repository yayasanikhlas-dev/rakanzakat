(function () {
  function drawLine(canvas) {
    if (!canvas) return;
    var raw = canvas.getAttribute("data-points");
    if (!raw) return;
    var points = [];
    try {
      points = JSON.parse(raw);
    } catch (e) {
      return;
    }

    var ctx = canvas.getContext("2d");
    var width = canvas.parentElement.clientWidth - 8;
    var height = canvas.height;
    canvas.width = width;

    ctx.clearRect(0, 0, width, height);
    ctx.fillStyle = "#f7faf8";
    ctx.fillRect(0, 0, width, height);

    if (!points.length) {
      ctx.fillStyle = "#6b8179";
      ctx.font = "13px sans-serif";
      ctx.fillText("Tiada data untuk carta ini.", 16, height / 2);
      return;
    }

    var pad = 28;
    var max = Math.max.apply(
      null,
      points.map(function (p) {
        return Number(p.value) || 0;
      })
    );
    if (max <= 0) max = 1;

    ctx.strokeStyle = "#d7e3dc";
    ctx.beginPath();
    ctx.moveTo(pad, height - pad);
    ctx.lineTo(width - 10, height - pad);
    ctx.stroke();

    ctx.beginPath();
    ctx.strokeStyle = "#146c43";
    ctx.lineWidth = 2;
    points.forEach(function (p, i) {
      var x = pad + (i * (width - pad - 16)) / Math.max(points.length - 1, 1);
      var y = height - pad - (Number(p.value) / max) * (height - pad * 2);
      if (i === 0) ctx.moveTo(x, y);
      else ctx.lineTo(x, y);
    });
    ctx.stroke();

    ctx.fillStyle = "#146c43";
    points.forEach(function (p, i) {
      var x = pad + (i * (width - pad - 16)) / Math.max(points.length - 1, 1);
      var y = height - pad - (Number(p.value) / max) * (height - pad * 2);
      ctx.beginPath();
      ctx.arc(x, y, 3, 0, Math.PI * 2);
      ctx.fill();
    });
  }

  drawLine(document.getElementById("rz-chart-kutipan"));
  drawLine(document.getElementById("rz-chart-pelawat"));
})();
