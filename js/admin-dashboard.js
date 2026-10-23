/* StudentHub – admin dashboard bar chart (Practical 14 advanced), drawn on <canvas> without any library */
(function () {
    "use strict";

    var canvas = document.getElementById("reg-chart");
    if (!canvas) { return; }

    var data;
    try { data = JSON.parse(canvas.dataset.chart); } catch (e) { return; }
    if (!Array.isArray(data) || !data.length) { return; }

    function colours() {
        var dark = document.body.classList.contains("dark-mode");
        return {
            text: dark ? "#e5e7eb" : "#333333",
            grid: dark ? "#3a3a3a" : "#e5e7eb",
            bar: dark ? "#60a5fa" : "#2563eb"
        };
    }

    function draw() {
        var ratio = window.devicePixelRatio || 1;
        var width = canvas.parentElement.clientWidth - 2;
        var height = 260;
        canvas.width = width * ratio;
        canvas.height = height * ratio;
        canvas.style.width = width + "px";
        canvas.style.height = height + "px";

        var ctx = canvas.getContext("2d");
        ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
        ctx.clearRect(0, 0, width, height);

        var c = colours();
        var pad = { top: 20, right: 15, bottom: 40, left: 40 };
        var plotW = width - pad.left - pad.right;
        var plotH = height - pad.top - pad.bottom;
        var max = Math.max.apply(null, data.map(function (d) { return d.value; }));
        var step = Math.max(1, Math.ceil(max / 5));
        var top = Math.ceil(max / step) * step;

        ctx.font = "12px Poppins, sans-serif";
        ctx.textAlign = "right";
        ctx.textBaseline = "middle";

        // horizontal grid + y labels
        for (var v = 0; v <= top; v += step) {
            var y = pad.top + plotH - (v / top) * plotH;
            ctx.strokeStyle = c.grid;
            ctx.beginPath(); ctx.moveTo(pad.left, y); ctx.lineTo(width - pad.right, y); ctx.stroke();
            ctx.fillStyle = c.text;
            ctx.fillText(String(v), pad.left - 6, y);
        }

        // bars + labels
        var slot = plotW / data.length;
        var barW = Math.min(60, slot * 0.6);
        ctx.textAlign = "center";
        data.forEach(function (d, i) {
            var h = (d.value / top) * plotH;
            var x = pad.left + slot * i + (slot - barW) / 2;
            var y = pad.top + plotH - h;
            ctx.fillStyle = c.bar;
            ctx.fillRect(x, y, barW, h);
            ctx.fillStyle = c.text;
            ctx.textBaseline = "bottom";
            ctx.fillText(String(d.value), x + barW / 2, y - 3);
            ctx.textBaseline = "top";
            ctx.fillText(d.label, x + barW / 2, pad.top + plotH + 8);
        });
    }

    draw();
    window.addEventListener("resize", draw);
    document.addEventListener("themechange", draw);
})();
