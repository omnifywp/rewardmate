(function () {
  "use strict";

  document.addEventListener("DOMContentLoaded", function () {
    var spinData = window.rewardmateSpinData;
    if (!spinData || !Array.isArray(spinData.wheelValues) || spinData.wheelValues.length === 0) {
      return;
    }

    var showSpinWheelButton = document.getElementById("show-spin-wheel");
    var spinButton = document.getElementById("spin-button");
    var spinWheel = document.getElementById("spin-wheel");
    var resultDiv = document.getElementById("spin-result");
    var messageDiv = document.getElementById("spin-message");
    var canvas = document.getElementById("wheel");
    var confettiLayer = document.getElementById("rewardmate-confetti");

    if (!showSpinWheelButton || !spinButton || !spinWheel || !canvas) {
      return;
    }

    var values = spinData.wheelValues.map(function (value) {
      return Number(value) || 0;
    });

    if (values.some(function (value) { return value <= 0; })) {
      return;
    }

    var messages = spinData.messages || {};
    var segments = values.length;
    var segmentAngle = (2 * Math.PI) / segments;
    var isSpinning = false;
    var currentRotation = 0;
    var pointerAngle = -Math.PI / 2;

    function cssVar(name, fallback) {
      var value = window.getComputedStyle(document.documentElement).getPropertyValue(name).trim();
      return value || fallback;
    }

    var themePrimary = cssVar("--rewardmate-primary", "#1f6f68");
    var themeDark = cssVar("--rewardmate-primary-dark", "#17324d");
    var themeAccent = cssVar("--rewardmate-accent", "#c88719");
    var colors = [themeDark, themePrimary, themeAccent, "#2f8f83", "#7f5f16", "#245f86", "#c66a32", "#356e58"];

    function normalizeAngle(angle) {
      var twoPi = 2 * Math.PI;
      return ((angle % twoPi) + twoPi) % twoPi;
    }

    function pointerIndexFromRotation(rotation) {
      var localPointer = normalizeAngle(pointerAngle - rotation);
      return Math.floor(localPointer / segmentAngle) % segments;
    }

    function targetRotationForIndex(index) {
      var targetLocalCenter = (index + 0.5) * segmentAngle;
      return normalizeAngle(pointerAngle - targetLocalCenter);
    }

    function setMessage(message, type) {
      if (!messageDiv) {
        return;
      }

      messageDiv.className = "rewardmate-inline-notice " + (type || "info");
      messageDiv.textContent = message || "";
    }

    function setResult(message, type) {
      if (!resultDiv) {
        return;
      }

      resultDiv.className = "rewardmate-spin-result " + (type || "success");
      resultDiv.textContent = message;
      resultDiv.style.display = "block";
    }

    function hideResult() {
      if (resultDiv) {
        resultDiv.style.display = "none";
      }
    }

    function celebrate() {
      if (!confettiLayer) {
        return;
      }

      var colors = [themePrimary, themeAccent, themeDark, "#22c55e", "#3b82f6", "#f59e0b"];

      for (var i = 0; i < 20; i++) {
        var piece = document.createElement("span");
        piece.className = "rewardmate-confetti-piece";
        piece.style.left = Math.random() * 100 + "%";
        piece.style.backgroundColor = colors[i % colors.length];
        piece.style.animationDelay = Math.random() * 180 + "ms";
        piece.style.transform = "translateY(0) rotate(" + Math.floor(Math.random() * 360) + "deg)";
        confettiLayer.appendChild(piece);

        window.setTimeout(function (node) {
          if (node && node.parentNode) {
            node.parentNode.removeChild(node);
          }
        }, 1500, piece);
      }
    }

    async function postToAjax(payload) {
      var body = new URLSearchParams();
      Object.keys(payload).forEach(function (key) {
        body.append(key, payload[key]);
      });

      var response = await fetch(spinData.ajaxUrl, {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
        },
        body: body.toString(),
      });

      return response.json();
    }

    function drawWheel(spinAngle) {
      var ctx = canvas.getContext("2d");
      var centerX = canvas.width / 2;
      var centerY = canvas.height / 2;
      var radius = Math.min(centerX, centerY) - 32;

      ctx.clearRect(0, 0, canvas.width, canvas.height);
      ctx.save();
      ctx.translate(centerX, centerY);

      ctx.beginPath();
      ctx.arc(0, 0, radius + 14, 0, 2 * Math.PI);
      ctx.fillStyle = "#ffffff";
      ctx.fill();
      ctx.strokeStyle = "rgba(31, 111, 104, 0.16)";
      ctx.lineWidth = 2;
      ctx.stroke();
      ctx.restore();

      for (var i = 0; i < segments; i++) {
        var startAngle = i * segmentAngle - spinAngle;
        var endAngle = (i + 1) * segmentAngle - spinAngle;
        var labelAngle = startAngle + segmentAngle / 2;

        ctx.beginPath();
        ctx.moveTo(centerX, centerY);
        ctx.arc(centerX, centerY, radius, startAngle, endAngle);
        ctx.closePath();
        ctx.fillStyle = colors[i % colors.length];
        ctx.fill();
        ctx.strokeStyle = "rgba(255, 255, 255, 0.88)";
        ctx.lineWidth = 3;
        ctx.stroke();

        ctx.textAlign = "center";
        ctx.textBaseline = "middle";
        ctx.save();
        ctx.translate(centerX, centerY);
        ctx.rotate(labelAngle);
        ctx.fillStyle = "#ffffff";
        ctx.shadowColor = "rgba(23, 33, 43, 0.26)";
        ctx.shadowBlur = 5;
        ctx.font = "900 22px sans-serif";
        ctx.fillText(String(values[i]), radius * 0.64, -7);
        ctx.font = "800 9px sans-serif";
        ctx.letterSpacing = "1px";
        ctx.fillText("PTS", radius * 0.64, 13);
        ctx.restore();
      }

      ctx.save();
      ctx.translate(centerX, centerY);
      for (var dot = 0; dot < Math.max(segments * 2, 12); dot++) {
        ctx.rotate((2 * Math.PI) / Math.max(segments * 2, 12));
        ctx.beginPath();
        ctx.arc(0, -(radius + 7), 3, 0, 2 * Math.PI);
        ctx.fillStyle = dot % 2 === 0 ? "#f7cf70" : "#d7e7e3";
        ctx.fill();
      }
      ctx.restore();

      ctx.beginPath();
      ctx.arc(centerX, centerY, 40, 0, 2 * Math.PI);
      ctx.fillStyle = themeDark;
      ctx.fill();
      ctx.strokeStyle = "#ffffff";
      ctx.lineWidth = 8;
      ctx.stroke();
    }

    showSpinWheelButton.addEventListener("click", function (event) {
      event.preventDefault();

      if (!spinData.userCanSpin) {
        setMessage(spinData.spinMessage || "You need to place an order to spin.", "warning");
        return;
      }

      spinWheel.style.display = "grid";
      spinWheel.classList.add("is-visible");
      hideResult();
      setMessage(messages.showToStart || "Click \"Spin Now\" to start.", "info");

      showSpinWheelButton.textContent = messages.viewWheel || "Show Spin Wheel";
      spinButton.textContent = messages.spinNow || "Spin Now";
      drawWheel(currentRotation);
    });

    spinButton.addEventListener("click", function (event) {
      event.preventDefault();

      if (isSpinning) {
        return;
      }

      if (!spinData.userCanSpin) {
        setMessage(spinData.spinMessage || "You need to place an order to spin.", "warning");
        return;
      }

      isSpinning = true;
      spinButton.disabled = true;
      showSpinWheelButton.disabled = true;
      spinWheel.classList.add("is-spinning");
      hideResult();
      spinButton.textContent = messages.spinning || "Spinning...";
      setMessage(messages.spinning || "Spinning...", "info");

      var selectedSegment = Math.floor(Math.random() * segments);
      var baseRotation = targetRotationForIndex(selectedSegment);
      var extraTurns = 6 + Math.floor(Math.random() * 3);
      var deltaRotation = normalizeAngle(baseRotation - normalizeAngle(currentRotation));
      var totalSpinAngle = currentRotation + extraTurns * 2 * Math.PI + deltaRotation;

      canvas.style.transform = "rotate(" + totalSpinAngle + "rad)";

      window.setTimeout(async function () {
        try {
          var landedIndex = pointerIndexFromRotation(totalSpinAngle);
          var landedPoints = values[landedIndex];

          var updateResponse = await postToAjax({
            action: "update_points",
            points: landedPoints,
            nonce: spinData.spinNonce,
          });

          if (updateResponse && updateResponse.success) {
            setResult((messages.won || "You won %d points!").replace("%d", String(landedPoints)), "success");
            setMessage((messages.balance || "New points balance: %d").replace("%d", String(updateResponse.data.new_points)), "success");
            celebrate();

            spinData.userCanSpin = false;
            showSpinWheelButton.textContent = messages.spinCompleted || "Spin Completed";
          } else {
            var failureMessage = messages.errorUpdating || "Error updating points.";
            if (updateResponse && updateResponse.data && updateResponse.data.message) {
              failureMessage = updateResponse.data.message;
            }
            setResult(failureMessage, "error");
            setMessage(messages.tryAgain || "Please try again.", "error");
          }
        } catch (error) {
          setResult(messages.tryAgain || "Please try again.", "error");
          setMessage(messages.networkError || "Network error while spinning.", "error");
        } finally {
          isSpinning = false;
          spinWheel.classList.remove("is-spinning");
          spinButton.disabled = !spinData.userCanSpin;
          showSpinWheelButton.disabled = !spinData.userCanSpin;
          spinButton.textContent = spinData.userCanSpin ? (messages.spinNow || "Spin Now") : (messages.spinCompleted || "Spin Completed");
          currentRotation = normalizeAngle(totalSpinAngle);
        }
      }, 4050);
    });

    drawWheel(currentRotation);
  });
})();
