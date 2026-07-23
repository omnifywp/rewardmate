(function () {
  "use strict";

  function parseJsonScript(id) {
    var node = document.getElementById(id);
    if (!node) {
      return null;
    }

    try {
      return JSON.parse(node.textContent || "{}");
    } catch (error) {
      return null;
    }
  }

  function formatNumber(value) {
    return new Intl.NumberFormat().format(Number(value) || 0);
  }

  function compactLabel(label, length) {
    label = String(label || "");
    return label.length > length ? label.slice(0, length - 3) + "..." : label;
  }

  function cssVar(name, fallback) {
    var value = window.getComputedStyle(document.documentElement).getPropertyValue(name).trim();
    return value || fallback;
  }

  var rewardmateSettingsSelectorData = null;

  function getSettingsSelectorData() {
    if (rewardmateSettingsSelectorData !== null) {
      return rewardmateSettingsSelectorData;
    }

    var data = parseJsonScript("rewardmate-settings-selector-data") || {};

    rewardmateSettingsSelectorData = {
      categories: Array.isArray(data.categories) ? data.categories : [],
      products: Array.isArray(data.products) ? data.products : [],
    };

    return rewardmateSettingsSelectorData;
  }

  function prepareCanvas(canvas, fallbackHeight) {
    var parent = canvas.parentElement;
    var cssWidth = Math.max(280, Math.floor((parent && parent.clientWidth ? parent.clientWidth : canvas.width) - 2));
    var cssHeight = Number(canvas.getAttribute("height")) || fallbackHeight || 320;
    var ratio = window.devicePixelRatio || 1;
    var ctx = canvas.getContext("2d");

    canvas.style.width = "100%";
    canvas.style.height = cssHeight + "px";
    canvas.width = Math.floor(cssWidth * ratio);
    canvas.height = Math.floor(cssHeight * ratio);
    ctx.setTransform(ratio, 0, 0, ratio, 0, 0);

    return {
      ctx: ctx,
      width: cssWidth,
      height: cssHeight,
    };
  }

  function drawNoData(ctx, width, height, message) {
    ctx.clearRect(0, 0, width, height);
    ctx.fillStyle = "#ffffff";
    ctx.fillRect(0, 0, width, height);
    ctx.fillStyle = "#61707f";
    ctx.font = "700 14px sans-serif";
    ctx.textAlign = "center";
    ctx.textBaseline = "middle";
    ctx.fillText(message || "No chart data yet", width / 2, height / 2);
    ctx.textBaseline = "alphabetic";
  }

  function roundedRect(ctx, x, y, width, height, radius) {
    var r = Math.min(radius, width / 2, height / 2);

    ctx.beginPath();
    ctx.moveTo(x + r, y);
    ctx.lineTo(x + width - r, y);
    ctx.quadraticCurveTo(x + width, y, x + width, y + r);
    ctx.lineTo(x + width, y + height);
    ctx.lineTo(x, y + height);
    ctx.lineTo(x, y + r);
    ctx.quadraticCurveTo(x, y, x + r, y);
    ctx.closePath();
  }

  function drawGrid(ctx, padding, chartWidth, chartHeight, maxValue) {
    ctx.strokeStyle = "#e5edf2";
    ctx.lineWidth = 1;
    ctx.fillStyle = "#61707f";
    ctx.font = "12px sans-serif";
    ctx.textAlign = "right";

    for (var i = 0; i <= 3; i++) {
      var value = Math.round(maxValue - (maxValue / 3) * i);
      var y = padding.top + (chartHeight / 3) * i;

      ctx.beginPath();
      ctx.moveTo(padding.left, y);
      ctx.lineTo(padding.left + chartWidth, y);
      ctx.stroke();
      ctx.fillText(formatNumber(value), padding.left - 10, y + 4);
    }
  }

  function drawBarChart(canvas, labels, values, options) {
    var prepared = prepareCanvas(canvas, 320);
    var ctx = prepared.ctx;
    var width = prepared.width;
    var height = prepared.height;
    var padding = { top: 28, right: 22, bottom: 64, left: 54 };
    var chartWidth = width - padding.left - padding.right;
    var chartHeight = height - padding.top - padding.bottom;
    var maxValue = Math.max.apply(null, values.concat([1]));
    var barCount = Math.max(1, values.length);
    var slotWidth = chartWidth / barCount;
    var barWidth = Math.min(84, Math.max(28, slotWidth * 0.54));

    ctx.clearRect(0, 0, width, height);
    ctx.fillStyle = "#ffffff";
    ctx.fillRect(0, 0, width, height);

    if (!values.length) {
      drawNoData(ctx, width, height, "No product data yet");
      return;
    }

    drawGrid(ctx, padding, chartWidth, chartHeight, maxValue);

    values.forEach(function (value, index) {
      var slotX = padding.left + index * slotWidth;
      var x = slotX + (slotWidth - barWidth) / 2;
      var barHeight = Math.round((Number(value) / maxValue) * chartHeight);
      var y = padding.top + chartHeight - barHeight;

      ctx.fillStyle = options.color || "#1f6f68";
      roundedRect(ctx, x, y, barWidth, Math.max(2, barHeight), 8);
      ctx.fill();
      ctx.fillStyle = "#1d2327";
      ctx.textAlign = "center";
      ctx.font = "700 11px sans-serif";
      ctx.fillText(formatNumber(value), x + barWidth / 2, Math.max(16, y - 8));

      ctx.fillStyle = "#646970";
      ctx.font = "11px sans-serif";
      ctx.textAlign = "center";
      ctx.fillText(compactLabel(labels[index], labels.length === 1 ? 28 : 16), x + barWidth / 2, padding.top + chartHeight + 24);
    });
  }

  function drawLineChart(canvas, labels, series) {
    var prepared = prepareCanvas(canvas, 320);
    var ctx = prepared.ctx;
    var width = prepared.width;
    var height = prepared.height;
    var padding = { top: 28, right: 28, bottom: 58, left: 62 };
    var chartWidth = width - padding.left - padding.right;
    var chartHeight = height - padding.top - padding.bottom;
    var allValues = [];

    series.forEach(function (item) {
      allValues = allValues.concat(item.values || []);
    });

    var maxValue = Math.max.apply(null, allValues.concat([1]));
    var xStep = chartWidth / Math.max(1, labels.length - 1);

    ctx.clearRect(0, 0, width, height);
    ctx.fillStyle = "#ffffff";
    ctx.fillRect(0, 0, width, height);

    if (!labels.length || !allValues.some(function (value) { return Number(value) > 0; })) {
      drawNoData(ctx, width, height, "No points movement yet");
      return;
    }

    drawGrid(ctx, padding, chartWidth, chartHeight, maxValue);

    function xForIndex(index) {
      if (labels.length <= 1) {
        return padding.left + chartWidth / 2;
      }

      return padding.left + index * xStep;
    }

    series.forEach(function (item) {
      var values = item.values || [];
      ctx.strokeStyle = item.color || "#2271b1";
      ctx.lineWidth = 3;
      ctx.beginPath();

      values.forEach(function (value, index) {
        var x = xForIndex(index);
        var y = padding.top + chartHeight - ((Number(value) || 0) / maxValue) * chartHeight;
        if (index === 0) {
          ctx.moveTo(x, y);
        } else {
          ctx.lineTo(x, y);
        }
      });

      ctx.stroke();

      values.forEach(function (value, index) {
        var x = xForIndex(index);
        var y = padding.top + chartHeight - ((Number(value) || 0) / maxValue) * chartHeight;
        ctx.fillStyle = item.color || "#2271b1";
        ctx.beginPath();
        ctx.arc(x, y, 4.5, 0, Math.PI * 2);
        ctx.fill();

        if (Number(value) > 0) {
          ctx.fillStyle = "#1d2327";
          ctx.font = "700 11px sans-serif";
          ctx.textAlign = "center";
          ctx.fillText(formatNumber(value), x, Math.max(14, y - 10));
        }
      });
    });

    labels.forEach(function (label, index) {
      if (labels.length > 8 && index % Math.ceil(labels.length / 8) !== 0) {
        return;
      }

      var x = xForIndex(index);
      ctx.fillStyle = "#646970";
      ctx.font = "11px sans-serif";
      ctx.textAlign = "center";
      ctx.fillText(compactLabel(label, 16), x, padding.top + chartHeight + 26);
    });
  }

  function renderCharts() {
    var data = parseJsonScript("rewardmate-analytics-data");
    if (!data) {
      return;
    }

    document.querySelectorAll("[data-rewardmate-chart]").forEach(function (canvas) {
      var type = canvas.getAttribute("data-rewardmate-chart");
      var primary = cssVar("--rm-admin-primary", "#1f6f68");
      var accent = cssVar("--rm-admin-accent", "#c88719");
      var good = cssVar("--rm-admin-good", "#178a4c");
      var bad = cssVar("--rm-admin-bad", "#b73535");

      if (type === "trend") {
        drawLineChart(canvas, data.trend_labels || [], [
          { values: data.issued_trend || [], color: primary },
          { values: data.redeemed_trend || [], color: bad },
          { values: data.refunded_trend || [], color: accent },
        ]);
      }

      if (type === "products") {
        drawBarChart(canvas, data.top_product_labels || [], data.top_product_values || [], { color: good });
      }
    });
  }

  function debounce(callback, delay) {
    var timer = null;

    return function () {
      clearTimeout(timer);
      timer = setTimeout(callback, delay);
    };
  }

  function getDecimalPlaces(value) {
    value = String(value || "");
    if (value.indexOf(".") === -1) {
      return 0;
    }

    return value.split(".")[1].length;
  }

  function dispatchFieldChange(field) {
    field.dispatchEvent(new Event("input", { bubbles: true }));
    field.dispatchEvent(new Event("change", { bubbles: true }));
  }

  function enhanceNumberInputs(panel) {
    panel.querySelectorAll('input[type="number"]').forEach(function (input) {
      if (input.closest(".rewardmate-number-control")) {
        return;
      }

      var wrapper = document.createElement("div");
      var decrease = document.createElement("button");
      var increase = document.createElement("button");

      wrapper.className = "rewardmate-number-control";
      decrease.type = "button";
      increase.type = "button";
      decrease.className = "rewardmate-number-step";
      increase.className = "rewardmate-number-step";
      decrease.textContent = "-";
      increase.textContent = "+";

      input.parentNode.insertBefore(wrapper, input);
      wrapper.appendChild(decrease);
      wrapper.appendChild(input);
      wrapper.appendChild(increase);

      function stepValue(direction) {
        var step = parseFloat(input.getAttribute("step")) || 1;
        var min = input.getAttribute("min") !== null ? parseFloat(input.getAttribute("min")) : null;
        var max = input.getAttribute("max") !== null ? parseFloat(input.getAttribute("max")) : null;
        var current = parseFloat(input.value);
        var decimals = Math.max(getDecimalPlaces(step), getDecimalPlaces(input.value));

        if (Number.isNaN(current)) {
          current = min !== null && !Number.isNaN(min) ? min : 0;
        }

        current += direction * step;

        if (min !== null && !Number.isNaN(min)) {
          current = Math.max(min, current);
        }

        if (max !== null && !Number.isNaN(max)) {
          current = Math.min(max, current);
        }

        input.value = decimals > 0 ? current.toFixed(decimals) : String(Math.round(current));
        dispatchFieldChange(input);
      }

      decrease.addEventListener("click", function () {
        stepValue(-1);
      });

      increase.addEventListener("click", function () {
        stepValue(1);
      });
    });
  }

  function enhanceSelects(panel) {
    panel.querySelectorAll("select").forEach(function (select) {
      if (
        select.classList.contains("rewardmate-enhanced-select") ||
        select.classList.contains("rewardmate-entity-select") ||
        select.multiple ||
        select.options.length !== 2
      ) {
        return;
      }

      var control = document.createElement("div");
      control.className = "rewardmate-choice-control";
      select.classList.add("rewardmate-enhanced-select");

      Array.prototype.forEach.call(select.options, function (option) {
        var button = document.createElement("button");
        button.type = "button";
        button.textContent = option.textContent;
        button.setAttribute("data-value", option.value);

        button.addEventListener("click", function () {
          select.value = option.value;
          dispatchFieldChange(select);
          updateActive();
        });

        control.appendChild(button);
      });

      function updateActive() {
        control.querySelectorAll("button").forEach(function (button) {
          button.classList.toggle("is-active", button.getAttribute("data-value") === select.value);
        });
      }

      select.parentNode.insertBefore(control, select.nextSibling);
      select.addEventListener("change", updateActive);
      updateActive();
    });
  }

  function enhanceTextareas(panel) {
    panel.querySelectorAll("textarea").forEach(function (textarea) {
      if (
        textarea.id === "rewardmate_campaign_rules" ||
        textarea.id === "rewardmate_tier_rules" ||
        textarea.id === "rewardmate_category_multiplier_rules" ||
        textarea.id === "rewardmate_product_multiplier_rules"
      ) {
        return;
      }

      if (textarea.nextElementSibling && textarea.nextElementSibling.classList.contains("rewardmate-textarea-meta")) {
        return;
      }

      var meta = document.createElement("div");
      meta.className = "rewardmate-textarea-meta";
      textarea.parentNode.insertBefore(meta, textarea.nextSibling);

      function updateMeta() {
        meta.textContent = formatNumber((textarea.value || "").length) + " characters";
      }

      textarea.addEventListener("input", updateMeta);
      updateMeta();
    });
  }

  function addSettingPlaceholders(panel) {
    var placeholders = {
      rewardmate_category_multiplier_rules: "15:2\n27:1.5\n42:0",
      rewardmate_product_multiplier_rules: "1001:5\n1002:2\n1003:0",
      rewardmate_excluded_category_ids: "15, 27, 42",
      rewardmate_excluded_product_ids: "1001, 1002, 1003",
      rewardmate_campaign_rules: "Weekend Boost|2026-05-01 09:00|2026-05-03 23:59|2|15,27|1001,1002",
      rewardmate_tier_rules: "Silver|500|1.10\nGold|2000|1.25\nPlatinum|5000|1.50",
    };

    Object.keys(placeholders).forEach(function (fieldId) {
      var field = panel.querySelector("#" + fieldId);
      if (!field || field.getAttribute("placeholder")) {
        return;
      }

      field.setAttribute("placeholder", placeholders[fieldId]);
    });
  }

  function parseCsvIds(value) {
    var seen = {};

    return String(value || "")
      .split(/[,\r\n]+/)
      .map(function (item) {
        return item.trim().replace(/[^\d]/g, "");
      })
      .filter(function (item) {
        if (!item || seen[item]) {
          return false;
        }

        seen[item] = true;
        return true;
      });
  }

  function selectedOptionValues(select) {
    return Array.prototype.filter.call(select.options || [], function (option) {
      return option.selected && option.value;
    }).map(function (option) {
      return option.value;
    });
  }

  function getEntityItems(entityType) {
    var data = getSettingsSelectorData();
    return Array.isArray(data[entityType]) ? data[entityType] : [];
  }

  function createEntitySelect(entityType, selectedIds, multiple, emptyLabel) {
    var select = document.createElement("select");
    var items = getEntityItems(entityType);
    var selectedMap = {};
    var knownMap = {};

    selectedIds = Array.isArray(selectedIds) ? selectedIds.map(String) : [];
    selectedIds.forEach(function (id) {
      if (id) {
        selectedMap[id] = true;
      }
    });

    select.className = "rewardmate-entity-select";
    select.setAttribute("data-entity-type", entityType);

    if (multiple) {
      select.multiple = true;
      select.size = Math.min(7, Math.max(4, items.length || selectedIds.length || 4));
    } else {
      var blank = document.createElement("option");
      blank.value = "";
      blank.textContent = emptyLabel || "Select item";
      select.appendChild(blank);
    }

    items.forEach(function (item) {
      var id = String(item.id || "");
      if (!id) {
        return;
      }

      knownMap[id] = true;

      var option = document.createElement("option");
      option.value = id;
      option.textContent = String(item.name || "Item") + " (#" + id + ")";
      option.selected = !!selectedMap[id];
      select.appendChild(option);
    });

    selectedIds.forEach(function (id) {
      if (!knownMap[id]) {
        var option = document.createElement("option");
        option.value = id;
        option.textContent = "#" + id;
        option.selected = true;
        select.appendChild(option);
      }
    });

    if (!select.options.length || (!multiple && select.options.length === 1 && items.length === 0)) {
      var empty = document.createElement("option");
      empty.value = "";
      empty.textContent = entityType === "categories" ? "No categories found" : "No products found";
      empty.disabled = true;
      select.appendChild(empty);
    }

    return select;
  }

  function createBuilderField(label, input) {
    var wrapper = document.createElement("label");
    var text = document.createElement("span");

    text.textContent = label;
    wrapper.appendChild(text);
    wrapper.appendChild(input);

    return wrapper;
  }

  function createBuilderNumber(value, placeholder, step) {
    var input = document.createElement("input");
    input.type = "number";
    input.min = "0";
    input.step = step || "0.01";
    input.value = value || "";
    input.placeholder = placeholder || "";

    return input;
  }

  function cleanMultiplierValue(value, fallback) {
    var number = parseFloat(value);

    if (Number.isNaN(number) || number < 0) {
      number = fallback;
    }

    return formatTierNumber(number);
  }

  function parseMultiplierRule(line) {
    var parts = String(line || "").split(":");

    return {
      id: parseCsvIds(parts[0] || "")[0] || "",
      multiplier: parts[1] || "1",
    };
  }

  function enhanceMultiplierBuilder(panel, sourceId, entityType, singularLabel, addLabel) {
    var source = panel.querySelector("#" + sourceId);
    if (!source || source.dataset.rewardmateMultiplierBuilder === "ready") {
      return;
    }

    var builder = document.createElement("div");
    var header = document.createElement("div");
    var title = document.createElement("strong");
    var intro = document.createElement("p");
    var rows = document.createElement("div");
    var actions = document.createElement("div");
    var addButton = document.createElement("button");
    var rawToggle = document.createElement("button");
    var note = document.createElement("p");

    source.dataset.rewardmateMultiplierBuilder = "ready";
    source.classList.add("rewardmate-multiplier-source");
    source.readOnly = true;

    builder.className = "rewardmate-multiplier-builder";
    header.className = "rewardmate-multiplier-builder-head";
    rows.className = "rewardmate-multiplier-rows";
    actions.className = "rewardmate-multiplier-actions";
    note.className = "rewardmate-multiplier-note";

    title.textContent = singularLabel + " multiplier builder";
    intro.textContent = "Choose " + singularLabel.toLowerCase() + " records from your store and assign point multipliers without typing IDs.";
    addButton.type = "button";
    addButton.className = "button rewardmate-multiplier-add";
    addButton.textContent = addLabel;
    rawToggle.type = "button";
    rawToggle.className = "button rewardmate-multiplier-raw-toggle";
    rawToggle.textContent = "View raw rules";
    note.textContent = "Tip: use 2 for double points, 1 for normal earning, and 0 to disable earning for a selected item.";

    header.appendChild(title);
    header.appendChild(intro);
    actions.appendChild(addButton);
    actions.appendChild(rawToggle);
    builder.appendChild(header);
    builder.appendChild(rows);
    builder.appendChild(actions);
    builder.appendChild(note);
    source.parentNode.insertBefore(builder, source);

    function serializeRows() {
      var lines = [];
      var seen = {};

      rows.querySelectorAll(".rewardmate-multiplier-row").forEach(function (row) {
        var select = row.querySelector("[data-multiplier-field='entity']");
        var multiplier = row.querySelector("[data-multiplier-field='multiplier']");
        var id = select ? select.value : "";

        if (!id || seen[id]) {
          return;
        }

        seen[id] = true;
        lines.push(id + ":" + cleanMultiplierValue(multiplier ? multiplier.value : "", 1));
      });

      source.value = lines.join("\n");
      dispatchFieldChange(source);
    }

    function addRow(data) {
      var row = document.createElement("div");
      var rowHead = document.createElement("div");
      var rowTitle = document.createElement("strong");
      var rowMeta = document.createElement("div");
      var status = document.createElement("span");
      var removeButton = document.createElement("button");
      var fields = document.createElement("div");
      var entity = createEntitySelect(entityType, data.id ? [data.id] : [], false, "Select " + singularLabel.toLowerCase());
      var multiplier = createBuilderNumber(data.multiplier || "1", "2", "0.01");

      row.className = "rewardmate-multiplier-row";
      rowHead.className = "rewardmate-multiplier-row-head";
      rowMeta.className = "rewardmate-multiplier-row-meta";
      status.className = "rewardmate-multiplier-status";
      fields.className = "rewardmate-multiplier-fields";
      rowTitle.textContent = singularLabel + " rule";
      removeButton.type = "button";
      removeButton.className = "button-link-delete rewardmate-multiplier-remove";
      removeButton.textContent = "Remove";

      entity.setAttribute("data-multiplier-field", "entity");
      multiplier.setAttribute("data-multiplier-field", "multiplier");

      fields.appendChild(createBuilderField(singularLabel, entity));
      fields.appendChild(createBuilderField("Multiplier", multiplier));
      rowHead.appendChild(rowTitle);
      rowMeta.appendChild(status);
      rowMeta.appendChild(removeButton);
      rowHead.appendChild(rowMeta);
      row.appendChild(rowHead);
      row.appendChild(fields);
      rows.appendChild(row);

      function updateRowMeta() {
        var selected = entity.options[entity.selectedIndex];
        var multiplierValue = cleanMultiplierValue(multiplier.value, 1);

        rowTitle.textContent = selected && selected.value ? selected.textContent : singularLabel + " rule";
        status.textContent = multiplierValue + "x";
        status.className = "rewardmate-multiplier-status";
        status.classList.add(Number(multiplierValue) > 1 ? "is-success" : "is-info");
      }

      fields.querySelectorAll("input, select").forEach(function (input) {
        input.addEventListener("input", function () {
          updateRowMeta();
          serializeRows();
        });
        input.addEventListener("change", function () {
          updateRowMeta();
          serializeRows();
        });
      });

      removeButton.addEventListener("click", function () {
        row.remove();
        serializeRows();
      });

      updateRowMeta();
      serializeRows();
    }

    String(source.value || "")
      .split(/\r\n|\r|\n/)
      .map(function (line) {
        return line.trim();
      })
      .filter(Boolean)
      .forEach(function (line) {
        addRow(parseMultiplierRule(line));
      });

    if (!rows.children.length) {
      addRow({ id: "", multiplier: "1" });
      source.value = "";
    }

    addButton.addEventListener("click", function () {
      addRow({ id: "", multiplier: "1" });
    });

    rawToggle.addEventListener("click", function () {
      var showing = source.classList.toggle("is-visible");
      rawToggle.textContent = showing ? "Hide raw rules" : "View raw rules";
    });

    var form = source.closest("form");
    if (form) {
      form.addEventListener("submit", serializeRows);
    }
  }

  function enhanceEntityIdField(panel, fieldId, entityType, label) {
    var source = panel.querySelector("#" + fieldId);
    if (!source || source.dataset.rewardmateEntityIdBuilder === "ready") {
      return;
    }

    var selector = createEntitySelect(entityType, parseCsvIds(source.value), true, "");
    var wrapper = document.createElement("div");
    var title = document.createElement("strong");
    var note = document.createElement("p");

    source.dataset.rewardmateEntityIdBuilder = "ready";
    source.classList.add("rewardmate-id-source");
    source.readOnly = true;

    wrapper.className = "rewardmate-id-selector";
    title.textContent = label;
    note.textContent = "Select one or more items. Omnify Customer Rewards stores the matching IDs automatically.";
    wrapper.appendChild(title);
    wrapper.appendChild(selector);
    wrapper.appendChild(note);
    source.parentNode.insertBefore(wrapper, source);

    function serialize() {
      source.value = selectedOptionValues(selector).join(", ");
      dispatchFieldChange(source);
    }

    selector.addEventListener("change", serialize);
    serialize();

    var form = source.closest("form");
    if (form) {
      form.addEventListener("submit", serialize);
    }
  }

  function cleanCampaignText(value) {
    return String(value || "").replace(/[|\r\n]+/g, " ").trim();
  }

  function cleanCampaignIds(value) {
    return String(value || "")
      .split(",")
      .map(function (item) {
        return item.trim().replace(/[^\d]/g, "");
      })
      .filter(Boolean)
      .join(",");
  }

  function toDateTimeLocal(value) {
    value = String(value || "").trim();
    if (!value) {
      return "";
    }

    return value.replace(" ", "T").slice(0, 16);
  }

  function fromDateTimeLocal(value) {
    value = String(value || "").trim();
    if (!value) {
      return "";
    }

    return value.replace("T", " ").slice(0, 16);
  }

  function parseCampaignRule(line) {
    var parts = String(line || "").split("|");

    return {
      name: parts[0] || "",
      start: toDateTimeLocal(parts[1] || ""),
      end: toDateTimeLocal(parts[2] || ""),
      multiplier: parts[3] || "2",
      categories: parts[4] || "",
      products: parts[5] || "",
    };
  }

  function createCampaignField(label, input) {
    var wrapper = document.createElement("label");
    var text = document.createElement("span");

    text.textContent = label;
    wrapper.appendChild(text);
    wrapper.appendChild(input);

    return wrapper;
  }

  function createCampaignInput(type, value, placeholder) {
    var input = document.createElement("input");
    input.type = type;
    input.value = value || "";
    input.placeholder = placeholder || "";

    if (type === "number") {
      input.min = "0";
      input.step = "0.01";
    }

    return input;
  }

  function enhanceCampaignBuilder(panel) {
    var source = panel.querySelector("#rewardmate_campaign_rules");
    if (!source || source.dataset.rewardmateCampaignBuilder === "ready") {
      return;
    }

    var builder = document.createElement("div");
    var header = document.createElement("div");
    var title = document.createElement("strong");
    var intro = document.createElement("p");
    var rows = document.createElement("div");
    var actions = document.createElement("div");
    var addButton = document.createElement("button");
    var rawToggle = document.createElement("button");
    var rawNote = document.createElement("p");

    source.dataset.rewardmateCampaignBuilder = "ready";
    source.classList.add("rewardmate-campaign-source");
    source.readOnly = true;

    builder.className = "rewardmate-campaign-builder";
    header.className = "rewardmate-campaign-builder-head";
    rows.className = "rewardmate-campaign-rows";
    actions.className = "rewardmate-campaign-actions";
    rawNote.className = "rewardmate-campaign-note";

    title.textContent = "Visual campaign builder";
    intro.textContent = "Create scheduled point boosts without writing pipe-separated rules. Times use your store timezone; category and product targeting is optional.";
    addButton.type = "button";
    addButton.className = "button rewardmate-campaign-add";
    addButton.textContent = "+ Add campaign";
    rawToggle.type = "button";
    rawToggle.className = "button rewardmate-campaign-raw-toggle";
    rawToggle.textContent = "View raw rules";
    rawNote.textContent = "Tip: leave category and product targeting empty to run the campaign across the whole store.";

    header.appendChild(title);
    header.appendChild(intro);
    actions.appendChild(addButton);
    actions.appendChild(rawToggle);
    builder.appendChild(header);
    builder.appendChild(rows);
    builder.appendChild(actions);
    builder.appendChild(rawNote);
    source.parentNode.insertBefore(builder, source);

    function serializeRows() {
      var lines = [];

      rows.querySelectorAll(".rewardmate-campaign-row").forEach(function (row) {
        var name = cleanCampaignText(row.querySelector('[data-campaign-field="name"]').value);
        var start = fromDateTimeLocal(row.querySelector('[data-campaign-field="start"]').value);
        var end = fromDateTimeLocal(row.querySelector('[data-campaign-field="end"]').value);
        var multiplier = parseFloat(row.querySelector('[data-campaign-field="multiplier"]').value);
        var categories = selectedOptionValues(row.querySelector('[data-campaign-field="categories"]')).join(",");
        var products = selectedOptionValues(row.querySelector('[data-campaign-field="products"]')).join(",");

        if (!name && !start && !end && !categories && !products) {
          return;
        }

        if (!name) {
          name = "Reward boost";
        }

        if (Number.isNaN(multiplier) || multiplier < 0) {
          multiplier = 1;
        }

        lines.push([name, start, end, String(multiplier), categories, products].join("|"));
      });

      source.value = lines.join("\n");
      dispatchFieldChange(source);
    }

    function addRow(data) {
      var row = document.createElement("div");
      var rowHead = document.createElement("div");
      var rowTitle = document.createElement("strong");
      var rowMeta = document.createElement("div");
      var status = document.createElement("span");
      var removeButton = document.createElement("button");
      var fields = document.createElement("div");
      var name = createCampaignInput("text", data.name, "Weekend Boost");
      var start = createCampaignInput("datetime-local", data.start, "");
      var end = createCampaignInput("datetime-local", data.end, "");
      var multiplier = createCampaignInput("number", data.multiplier || "2", "2");
      var categories = createEntitySelect("categories", parseCsvIds(data.categories), true, "");
      var products = createEntitySelect("products", parseCsvIds(data.products), true, "");

      row.className = "rewardmate-campaign-row";
      rowHead.className = "rewardmate-campaign-row-head";
      rowMeta.className = "rewardmate-campaign-row-meta";
      status.className = "rewardmate-campaign-status";
      fields.className = "rewardmate-campaign-fields";
      rowTitle.textContent = "Campaign";
      removeButton.type = "button";
      removeButton.className = "button-link-delete rewardmate-campaign-remove";
      removeButton.textContent = "Remove";

      name.setAttribute("data-campaign-field", "name");
      start.setAttribute("data-campaign-field", "start");
      end.setAttribute("data-campaign-field", "end");
      multiplier.setAttribute("data-campaign-field", "multiplier");
      categories.setAttribute("data-campaign-field", "categories");
      products.setAttribute("data-campaign-field", "products");

      fields.appendChild(createCampaignField("Campaign name", name));
      fields.appendChild(createCampaignField("Starts", start));
      fields.appendChild(createCampaignField("Ends", end));
      fields.appendChild(createCampaignField("Multiplier", multiplier));
      fields.appendChild(createCampaignField("Categories", categories));
      fields.appendChild(createCampaignField("Products", products));
      rowHead.appendChild(rowTitle);
      rowMeta.appendChild(status);
      rowMeta.appendChild(removeButton);
      rowHead.appendChild(rowMeta);
      row.appendChild(rowHead);
      row.appendChild(fields);
      rows.appendChild(row);

      function updateRowMeta() {
        var label = cleanCampaignText(name.value);
        var startsAt = start.value ? new Date(start.value) : null;
        var endsAt = end.value ? new Date(end.value) : null;
        var now = new Date();

        rowTitle.textContent = label || "Campaign";
        status.className = "rewardmate-campaign-status";

        if (!start.value || !end.value) {
          status.textContent = "Needs schedule";
          status.classList.add("is-warning");
          return;
        }

        if (startsAt && endsAt && startsAt > endsAt) {
          status.textContent = "Check dates";
          status.classList.add("is-error");
          return;
        }

        if (endsAt && now > endsAt) {
          status.textContent = "Ended";
          status.classList.add("is-muted");
          return;
        }

        if (startsAt && now < startsAt) {
          status.textContent = "Scheduled";
          status.classList.add("is-info");
          return;
        }

        status.textContent = "Active";
        status.classList.add("is-success");
      }

      fields.querySelectorAll("input, select").forEach(function (input) {
        input.addEventListener("input", function () {
          updateRowMeta();
          serializeRows();
        });
        input.addEventListener("change", function () {
          updateRowMeta();
          serializeRows();
        });
      });

      removeButton.addEventListener("click", function () {
        row.remove();
        serializeRows();
      });

      updateRowMeta();
      serializeRows();
    }

    String(source.value || "")
      .split(/\r\n|\r|\n/)
      .map(function (line) {
        return line.trim();
      })
      .filter(Boolean)
      .forEach(function (line) {
        addRow(parseCampaignRule(line));
      });

    if (!rows.children.length) {
      addRow({
        name: "",
        start: "",
        end: "",
        multiplier: "2",
        categories: "",
        products: "",
      });
      source.value = "";
    }

    addButton.addEventListener("click", function () {
      addRow({
        name: "",
        start: "",
        end: "",
        multiplier: "2",
        categories: "",
        products: "",
      });
    });

    rawToggle.addEventListener("click", function () {
      var showing = source.classList.toggle("is-visible");
      rawToggle.textContent = showing ? "Hide raw rules" : "View raw rules";
    });

    var form = source.closest("form");
    if (form) {
      form.addEventListener("submit", serializeRows);
    }
  }

  function cleanTierText(value) {
    return String(value || "").replace(/[|\r\n]+/g, " ").trim();
  }

  function parseTierNumber(value, fallback) {
    var number = parseFloat(value);

    if (Number.isNaN(number) || number < 0) {
      return fallback;
    }

    return number;
  }

  function formatTierNumber(value) {
    var number = parseTierNumber(value, 0);

    return String(Math.round(number * 100) / 100);
  }

  function parseTierRule(line) {
    var parts = String(line || "").split("|");

    return {
      name: parts[0] || "",
      threshold: parts[1] || "",
      multiplier: parts[2] || "",
    };
  }

  function createTierField(label, input) {
    var wrapper = document.createElement("label");
    var text = document.createElement("span");

    text.textContent = label;
    wrapper.appendChild(text);
    wrapper.appendChild(input);

    return wrapper;
  }

  function createTierInput(type, value, placeholder, step) {
    var input = document.createElement("input");
    input.type = type;
    input.value = value || "";
    input.placeholder = placeholder || "";

    if (type === "number") {
      input.min = "0";
      input.step = step || "0.01";
    }

    return input;
  }

  function enhanceTierBuilder(panel) {
    var source = panel.querySelector("#rewardmate_tier_rules");
    if (!source || source.dataset.rewardmateTierBuilder === "ready") {
      return;
    }

    var builder = document.createElement("div");
    var header = document.createElement("div");
    var title = document.createElement("strong");
    var intro = document.createElement("p");
    var rows = document.createElement("div");
    var actions = document.createElement("div");
    var addButton = document.createElement("button");
    var rawToggle = document.createElement("button");
    var note = document.createElement("p");

    source.dataset.rewardmateTierBuilder = "ready";
    source.classList.add("rewardmate-tier-source");
    source.readOnly = true;

    builder.className = "rewardmate-tier-builder";
    header.className = "rewardmate-tier-builder-head";
    rows.className = "rewardmate-tier-rows";
    actions.className = "rewardmate-tier-actions";
    note.className = "rewardmate-tier-note";

    title.textContent = "Dynamic tier builder";
    intro.textContent = "Add as many loyalty tiers as you need. Customers are assigned to the highest spend threshold they reach.";
    addButton.type = "button";
    addButton.className = "button rewardmate-tier-add";
    addButton.textContent = "+ Add tier";
    rawToggle.type = "button";
    rawToggle.className = "button rewardmate-tier-raw-toggle";
    rawToggle.textContent = "View raw rules";
    note.textContent = "Tip: use multiplier 1 for normal earning, 1.25 for 25% bonus points, and 0 to disable earning for a tier.";

    header.appendChild(title);
    header.appendChild(intro);
    actions.appendChild(addButton);
    actions.appendChild(rawToggle);
    builder.appendChild(header);
    builder.appendChild(rows);
    builder.appendChild(actions);
    builder.appendChild(note);
    source.parentNode.insertBefore(builder, source);

    function serializeRows() {
      var lines = [];

      rows.querySelectorAll(".rewardmate-tier-row").forEach(function (row) {
        var name = cleanTierText(row.querySelector('[data-tier-field="name"]').value);
        var threshold = parseTierNumber(row.querySelector('[data-tier-field="threshold"]').value, 0);
        var multiplier = parseTierNumber(row.querySelector('[data-tier-field="multiplier"]').value, 1);

        if (!name) {
          return;
        }

        lines.push([name, formatTierNumber(threshold), formatTierNumber(multiplier)].join("|"));
      });

      source.value = lines.join("\n");
      dispatchFieldChange(source);
    }

    function addRow(data) {
      var row = document.createElement("div");
      var rowHead = document.createElement("div");
      var rowTitle = document.createElement("strong");
      var rowMeta = document.createElement("div");
      var status = document.createElement("span");
      var removeButton = document.createElement("button");
      var fields = document.createElement("div");
      var name = createTierInput("text", data.name, "Gold");
      var threshold = createTierInput("number", data.threshold, "2000", "0.01");
      var multiplier = createTierInput("number", data.multiplier || "1", "1.25", "0.01");

      row.className = "rewardmate-tier-row";
      rowHead.className = "rewardmate-tier-row-head";
      rowMeta.className = "rewardmate-tier-row-meta";
      status.className = "rewardmate-tier-status";
      fields.className = "rewardmate-tier-fields";
      rowTitle.textContent = data.name || "Reward tier";
      removeButton.type = "button";
      removeButton.className = "button-link-delete rewardmate-tier-remove";
      removeButton.textContent = "Remove";

      name.setAttribute("data-tier-field", "name");
      threshold.setAttribute("data-tier-field", "threshold");
      multiplier.setAttribute("data-tier-field", "multiplier");

      fields.appendChild(createTierField("Tier name", name));
      fields.appendChild(createTierField("Spend threshold", threshold));
      fields.appendChild(createTierField("Earn multiplier", multiplier));
      rowHead.appendChild(rowTitle);
      rowMeta.appendChild(status);
      rowMeta.appendChild(removeButton);
      rowHead.appendChild(rowMeta);
      row.appendChild(rowHead);
      row.appendChild(fields);
      rows.appendChild(row);

      function updateRowMeta() {
        var label = cleanTierText(name.value);
        var thresholdValue = parseTierNumber(threshold.value, 0);
        var multiplierValue = parseTierNumber(multiplier.value, 1);

        rowTitle.textContent = label || "Reward tier";
        status.className = "rewardmate-tier-status";

        if (!label) {
          status.textContent = "Needs name";
          status.classList.add("is-warning");
          return;
        }

        status.textContent = formatTierNumber(thresholdValue) + " spend / " + formatTierNumber(multiplierValue) + "x";
        status.classList.add(multiplierValue > 1 ? "is-success" : "is-info");
      }

      fields.querySelectorAll("input").forEach(function (input) {
        input.addEventListener("input", function () {
          updateRowMeta();
          serializeRows();
        });
        input.addEventListener("change", function () {
          updateRowMeta();
          serializeRows();
        });
      });

      removeButton.addEventListener("click", function () {
        row.remove();
        serializeRows();
      });

      updateRowMeta();
      serializeRows();
    }

    String(source.value || "")
      .split(/\r\n|\r|\n/)
      .map(function (line) {
        return line.trim();
      })
      .filter(Boolean)
      .forEach(function (line) {
        addRow(parseTierRule(line));
      });

    if (!rows.children.length) {
      [
        { name: "Silver", threshold: "500", multiplier: "1.10" },
        { name: "Gold", threshold: "2000", multiplier: "1.25" },
        { name: "Platinum", threshold: "5000", multiplier: "1.50" },
      ].forEach(addRow);
    }

    addButton.addEventListener("click", function () {
      addRow({
        name: "",
        threshold: "",
        multiplier: "1",
      });
    });

    rawToggle.addEventListener("click", function () {
      var showing = source.classList.toggle("is-visible");
      rawToggle.textContent = showing ? "Hide raw rules" : "View raw rules";
    });

    var form = source.closest("form");
    if (form) {
      form.addEventListener("submit", serializeRows);
    }
  }

  function enhanceSettingsControls() {
    var panel = document.querySelector(".rewardmate-settings-panel");
    if (!panel) {
      return;
    }

    addSettingPlaceholders(panel);
    enhanceMultiplierBuilder(panel, "rewardmate_category_multiplier_rules", "categories", "Category", "+ Add category rule");
    enhanceMultiplierBuilder(panel, "rewardmate_product_multiplier_rules", "products", "Product", "+ Add product rule");
    enhanceEntityIdField(panel, "rewardmate_excluded_category_ids", "categories", "Excluded categories");
    enhanceEntityIdField(panel, "rewardmate_excluded_product_ids", "products", "Excluded products");
    enhanceCampaignBuilder(panel);
    enhanceTierBuilder(panel);
    enhanceNumberInputs(panel);
    enhanceSelects(panel);
    enhanceTextareas(panel);
  }

  function initCopyButtons() {
    document.querySelectorAll("[data-rewardmate-copy]").forEach(function (button) {
      button.addEventListener("click", function () {
        var selector = button.getAttribute("data-rewardmate-copy");
        var target = selector ? document.querySelector(selector) : null;
        var text = target ? String(target.textContent || "").trim() : "";

        if (!text || text === "No key generated yet") {
          return;
        }

        function markCopied() {
          var original = button.textContent;
          button.textContent = "Copied";
          window.setTimeout(function () {
            button.textContent = original;
          }, 1400);
        }

        if (navigator.clipboard && navigator.clipboard.writeText) {
          navigator.clipboard.writeText(text).then(markCopied).catch(function () {});
          return;
        }

        var textarea = document.createElement("textarea");
        textarea.value = text;
        textarea.setAttribute("readonly", "readonly");
        textarea.style.position = "fixed";
        textarea.style.left = "-9999px";
        document.body.appendChild(textarea);
        textarea.select();
        try {
          document.execCommand("copy");
          markCopied();
        } catch (error) {
          // No fallback UI needed; the visible code remains selectable.
        }
        textarea.remove();
      });
    });
  }

  function initFilterToggles() {
    document.querySelectorAll("[data-rewardmate-toggle-filters]").forEach(function (button) {
      var selector = button.getAttribute("data-rewardmate-toggle-filters");
      var target = selector ? document.querySelector(selector) : null;

      if (!target) {
        return;
      }

      function syncLabel() {
        var isHidden = target.classList.contains("is-hidden");
        var showLabel = button.getAttribute("data-show-label") || "More Filters";
        var hideLabel = button.getAttribute("data-hide-label") || "Hide Filters";

        button.textContent = isHidden ? showLabel : hideLabel;
        button.setAttribute("aria-expanded", isHidden ? "false" : "true");
      }

      button.addEventListener("click", function () {
        target.classList.toggle("is-hidden");
        syncLabel();

        if (!target.classList.contains("is-hidden")) {
          var firstField = target.querySelector("input, select, textarea, button");
          if (firstField && typeof firstField.focus === "function") {
            firstField.focus({ preventScroll: true });
          }
        }
      });

      syncLabel();
    });
  }

  function initColorPickers() {
    if (!window.jQuery || !window.jQuery.fn || !window.jQuery.fn.wpColorPicker) {
      return;
    }

    window.jQuery(".rewardmate-color-picker").wpColorPicker({
      change: function () {
        var field = this;
        window.setTimeout(function () {
          dispatchFieldChange(field);
        }, 0);
      },
      clear: function () {
        dispatchFieldChange(this);
      },
    });
  }

  document.addEventListener("DOMContentLoaded", function () {
    renderCharts();
    enhanceSettingsControls();
    initColorPickers();
    initCopyButtons();
    initFilterToggles();
    window.addEventListener("resize", debounce(renderCharts, 160));
  });
})();
