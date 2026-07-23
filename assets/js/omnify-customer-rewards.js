(function () {
  "use strict";

  function getParams() {
    return window.rewardmate_daily_checkin_params || {};
  }

  function refreshWooCommerceTotals() {
    if (window.jQuery && window.jQuery(document.body)) {
      window.jQuery(document.body).trigger("update_checkout");
      window.jQuery(document.body).trigger("wc_update_cart");
      window.jQuery(document.body).trigger("wc_fragment_refresh");
      return;
    }

    window.setTimeout(function () {
      window.location.reload();
    }, 500);
  }

  function initDailyCheckin() {
    var button = document.getElementById("daily-checkin-btn");
    var status = document.getElementById("rewardmate-checkin-status");
    var params = getParams();
    var messages = params && params.messages ? params.messages : {};

    if (!button || !params || !params.ajax_url || !params.nonce) {
      return;
    }

    function setStatus(message, type) {
      if (!status) {
        return;
      }

      status.className = "rewardmate-inline-notice " + (type || "info");
      status.textContent = message;
    }

    function setButtonState(disabled, label) {
      button.disabled = disabled;
      if (label) {
        button.textContent = label;
      }
    }

    async function requestCheckin() {
      var body = new URLSearchParams();
      body.append("action", "rewardmate_daily_checkin");
      body.append("nonce", params.nonce);

      var response = await fetch(params.ajax_url, {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
        },
        body: body.toString(),
      });

      return response.json();
    }

    button.addEventListener("click", async function (event) {
      event.preventDefault();

      setButtonState(true, messages.checkingIn || "Checking in...");
      setStatus(messages.processing || "Processing your check-in...", "info");

      try {
        var result = await requestCheckin();

        if (result && result.success) {
          setStatus(result.data && result.data.message ? result.data.message : (messages.doneFallback || "Check-in completed."), "success");

          var actions = button.closest(".rewardmate-checkin-actions");
          if (actions) {
            actions.remove();
          } else {
            button.remove();
          }
          return;
        }

        var errorMessage = messages.failed || "Unable to complete check-in.";
        if (result && result.data && result.data.message) {
          errorMessage = result.data.message;
        }

        setStatus(errorMessage, "error");
        setButtonState(false, messages.buttonLabel || "Daily Check-In");
      } catch (error) {
        setStatus(messages.tryAgain || "An error occurred. Please try again.", "error");
        setButtonState(false, messages.buttonLabel || "Daily Check-In");
      }
    });
  }

  function clampToStep(value, max, step) {
    var numericValue = Math.max(0, Number(value) || 0);
    var numericMax = Math.max(0, Number(max) || 0);
    var numericStep = Math.max(1, Number(step) || 1);
    var clamped = Math.min(numericValue, numericMax);

    return Math.floor(clamped / numericStep) * numericStep;
  }

  function initRedeemControls() {
    var params = getParams();
    var messages = params && params.messages ? params.messages : {};
    var controls = document.querySelectorAll(".rewardmate-redeem-control");

    if (!controls.length || !params.ajax_url || !params.redeem_nonce) {
      return;
    }

    controls.forEach(function (control) {
      var range = control.querySelector(".rewardmate-redeem-range");
      var number = control.querySelector(".rewardmate-redeem-number");
      var button = control.querySelector(".rewardmate-redeem-apply");
      var status = control.querySelector(".rewardmate-redeem-status");
      var max = Number(control.getAttribute("data-max-points")) || 0;
      var step = Number(control.getAttribute("data-step-points")) || 1;

      if (!range || !number || !button) {
        return;
      }

      function setStatus(message, type) {
        if (!status) {
          return;
        }

        status.className = "rewardmate-redeem-status " + (type || "");
        status.textContent = message;
      }

      function syncValue(source) {
        var nextValue = clampToStep(source.value, max, step);
        range.value = String(nextValue);
        number.value = String(nextValue);
      }

      range.addEventListener("input", function () {
        syncValue(range);
      });

      number.addEventListener("input", function () {
        syncValue(number);
      });

      button.addEventListener("click", async function (event) {
        event.preventDefault();
        syncValue(number);

        var body = new URLSearchParams();
        body.append("action", "rewardmate_set_redeem_points");
        body.append("nonce", params.redeem_nonce);
        body.append("points", number.value);

        button.disabled = true;
        setStatus(messages.applying || "Applying points...", "is-loading");

        try {
          var response = await fetch(params.ajax_url, {
            method: "POST",
            headers: {
              "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
            },
            body: body.toString(),
          });
          var result = await response.json();

          if (result && result.success) {
            setStatus(result.data && result.data.message ? result.data.message : (messages.redeemApplied || "Points updated."), "is-success");
            refreshWooCommerceTotals();
            return;
          }

          setStatus(result && result.data && result.data.message ? result.data.message : (messages.redeemFailed || "Unable to apply points."), "is-error");
        } catch (error) {
          setStatus(messages.tryAgain || "An error occurred. Please try again.", "is-error");
        } finally {
          button.disabled = false;
        }
      });
    });
  }

  document.addEventListener("DOMContentLoaded", function () {
    initDailyCheckin();
    initRedeemControls();
  });
})();
