function converterApp(config) {
  const paymentMethods = Array.isArray(config.paymentMethods) ? config.paymentMethods : [];
  const pairMethodMap = config.pairMethodMap && typeof config.pairMethodMap === "object" ? config.pairMethodMap : {};
  const pairMethodRules = config.pairMethodRules && typeof config.pairMethodRules === "object" ? config.pairMethodRules : {};

  const uniqueByCode = (items) => {
    const map = new Map();
    items.forEach((item) => {
      const code = String(item.code || "");
      if (code === "" || map.has(code)) return;
      map.set(code, item);
    });
    return Array.from(map.values());
  };

  return {
    quoteUrl: config.quoteUrl || "",
    categories: Array.isArray(config.categories) ? config.categories : [],
    pairs: Array.isArray(config.pairs) ? config.pairs : [],
    paymentMethods,
    pairMethodMap,
    pairMethodRules,
    activeCategory: "semua",
    selectedSellCode: "",
    selectedBuyCode: "",
    selectedPairId: "",
    selectedMethodId: "",
    amountSent: "",
    senderAccount: "",
    idempotencyKey: "",
    warning: "",
    debounceTimer: null,
    quoteToken: config.initialToken || "",
    quote: {
      pair_id: null,
      amount_sent: 0,
      amount_received: 0,
      gross: 0,
      fee: 0,
      spread: 0,
      rate: 0,
      min_amount: 0,
      max_amount: 0,
      notes: "",
      from_code: "",
      to_code: "",
    },

    get selectedPair() {
      return this.pairs.find((item) => String(item.id) === String(this.selectedPairId)) || null;
    },

    get selectedMethod() {
      return this.paymentMethods.find((item) => String(item.id) === String(this.selectedMethodId)) || null;
    },

    get selectedMethodRules() {
      const pairId = String(this.selectedPairId || "");
      const methodId = String(this.selectedMethodId || "");
      if (pairId === "" || methodId === "") return {};
      const pairRules = this.pairMethodRules[pairId] || this.pairMethodRules[Number(pairId)] || {};
      return pairRules[methodId] || pairRules[Number(methodId)] || {};
    },

    get effectiveDestinationNumber() {
      const rules = this.selectedMethodRules || {};
      const useAssetDestination = rules.use_asset_destination !== false;
      const overrideNumber = String(rules.destination_number || "").trim();
      const assetNumber = String(this.selectedPair?.to_destination_account_number || "").trim();
      if (!useAssetDestination && overrideNumber !== "") return overrideNumber;
      if (assetNumber !== "") return assetNumber;
      return overrideNumber;
    },

    get effectiveDestinationName() {
      const rules = this.selectedMethodRules || {};
      const useAssetDestination = rules.use_asset_destination !== false;
      const overrideName = String(rules.destination_name || "").trim();
      const assetName = String(this.selectedPair?.to_destination_account_name || "").trim();
      if (!useAssetDestination && overrideName !== "") return overrideName;
      if (assetName !== "") return assetName;
      return overrideName;
    },

    get effectiveDestinationType() {
      const type = String(this.selectedPair?.to_destination_account_type || "").trim();
      if (type !== "") return type;
      return "other";
    },

    get effectiveDestinationLabel() {
      if (this.effectiveDestinationType === "phone_number") return "No. HP Tujuan";
      if (this.effectiveDestinationType === "bank_account") return "No. Rekening Tujuan";
      return "Tujuan Transfer";
    },

    get sellAssets() {
      return uniqueByCode(this.pairs.map((pair) => ({
        code: String(pair.from_code || ""),
        name: String(pair.from_name || pair.from_code || ""),
        categorySlug: String(pair.from_category_slug || "semua"),
      })));
    },

    get filteredSellAssets() {
      if (this.activeCategory === "semua") {
        return this.sellAssets;
      }
      return this.sellAssets.filter((item) => item.categorySlug === this.activeCategory);
    },

    get buyAssets() {
      const sellCode = String(this.selectedSellCode || "");
      const rows = this.pairs.filter((pair) => String(pair.from_code || "") === sellCode);
      return uniqueByCode(rows.map((pair) => ({
        code: String(pair.to_code || ""),
        name: String(pair.to_name || pair.to_code || ""),
      })));
    },

    get methodsForSelectedPair() {
      const pairId = String(this.selectedPairId || "");
      if (pairId === "") return [];

      const allowedIdsRaw = this.pairMethodMap[pairId] || this.pairMethodMap[Number(pairId)] || [];
      const allowedIds = Array.isArray(allowedIdsRaw) ? allowedIdsRaw.map((item) => String(item)) : [];

      let methods = this.paymentMethods.filter((item) => String(item.status || "active") === "active");
      if (allowedIds.length > 0) {
        methods = methods.filter((item) => allowedIds.includes(String(item.id)));
      }
      return methods;
    },

    init() {
      const hasSemua = this.categories.some((item) => String(item.slug || "") === "semua");
      if (!hasSemua) {
        this.categories = [{ id: "semua", slug: "semua", name: "Semua" }, ...this.categories];
      }
      this.activeCategory = "semua";

      const preferredSell = this.sellAssets.find((item) => item.code === "IDR");
      this.selectedSellCode = preferredSell ? preferredSell.code : (this.sellAssets[0]?.code || "");
      this.syncBuySelection();
      this.syncPairAndMethodSelection();
      this.scheduleQuote();
      this.regenerateIdempotencyKey();
    },

    changeCategory(slug) {
      this.activeCategory = String(slug || "semua");
      const exists = this.filteredSellAssets.some((item) => item.code === this.selectedSellCode);
      if (!exists) {
        this.selectedSellCode = this.filteredSellAssets[0]?.code || "";
      }
      this.syncBuySelection();
      this.syncPairAndMethodSelection();
      this.scheduleQuote();
    },

    setSellAsset(code) {
      this.selectedSellCode = String(code || "");
      this.syncBuySelection();
      this.syncPairAndMethodSelection();
      this.scheduleQuote();
      this.regenerateIdempotencyKey();
    },

    setBuyAsset(code) {
      this.selectedBuyCode = String(code || "");
      this.syncPairAndMethodSelection();
      this.scheduleQuote();
      this.regenerateIdempotencyKey();
    },

    swapDirection() {
      const currentSell = String(this.selectedSellCode || "");
      const currentBuy = String(this.selectedBuyCode || "");
      this.selectedSellCode = currentBuy;
      this.selectedBuyCode = currentSell;
      this.syncBuySelection();
      this.syncPairAndMethodSelection();
      this.scheduleQuote();
    },

    syncBuySelection() {
      const available = this.buyAssets;
      if (available.length === 0) {
        this.selectedBuyCode = "";
        return;
      }
      const stillValid = available.some((item) => item.code === this.selectedBuyCode);
      if (!stillValid) {
        const preferredBuy = available.find((item) => item.code === "PAYPAL_USD");
        this.selectedBuyCode = preferredBuy ? preferredBuy.code : available[0].code;
      }
    },

    syncPairAndMethodSelection() {
      const pair = this.pairs.find(
        (item) =>
          String(item.from_code || "") === String(this.selectedSellCode || "") &&
          String(item.to_code || "") === String(this.selectedBuyCode || "")
      );
      this.selectedPairId = pair ? String(pair.id) : "";

      if (!pair) {
        this.selectedMethodId = "";
        this.warning = "Pair tidak tersedia untuk kombinasi Jual/Beli ini.";
        return;
      }

      this.warning = "";
      const methods = this.methodsForSelectedPair;
      const selectedStillValid = methods.some((item) => String(item.id) === String(this.selectedMethodId));
      if (!selectedStillValid) {
        this.selectedMethodId = methods.length > 0 ? String(methods[0].id) : "";
        this.senderAccount = "";
      }
    },

    selectMethod(methodId) {
      this.selectedMethodId = String(methodId || "");
      this.senderAccount = "";
      this.regenerateIdempotencyKey();
    },

    setAmount(value) {
      this.amountSent = String(value);
      this.scheduleQuote();
      this.regenerateIdempotencyKey();
    },

    progressPercent() {
      const min = Number(this.quote.min_amount || 0);
      const max = Number(this.quote.max_amount || 0);
      const amount = Number(this.amountSent || 0);
      if (!max || max <= 0) return 0;
      const normalized = Math.max(0, Math.min(1, (amount - min) / (max - min || 1)));
      return Math.round(normalized * 100);
    },

    scheduleQuote() {
      if (this.debounceTimer !== null) {
        clearTimeout(this.debounceTimer);
      }
      this.debounceTimer = setTimeout(() => {
        this.fetchQuote();
      }, 400);
    },

    async fetchQuote() {
      const pairId = Number(this.selectedPairId);
      const amount = Number(this.amountSent);
      if (!pairId || !amount || amount <= 0) {
        return;
      }

      const payload = new FormData();
      payload.append("pair_id", String(pairId));
      payload.append("amount_sent", String(amount));

      try {
        const response = await fetch(this.quoteUrl, {
          method: "POST",
          headers: {
            "X-Quote-Token": this.quoteToken,
          },
          body: payload,
        });
        const data = await response.json();

        if (typeof data.next_token === "string" && data.next_token.length > 0) {
          this.quoteToken = data.next_token;
        }

        if (!response.ok || data.success !== true) {
          this.warning = data.message || "Gagal menghitung quote.";
          if (data.quote) {
            this.quote = { ...this.quote, ...data.quote };
          }
          return;
        }

        this.warning = "";
        this.quote = { ...this.quote, ...data.quote };
      } catch (error) {
        this.warning = "Terjadi kesalahan saat mengambil quote.";
      }
    },

    formatNumber(value) {
      const numeric = Number(value || 0);
      return new Intl.NumberFormat("id-ID", {
        minimumFractionDigits: 0,
        maximumFractionDigits: 8,
      }).format(numeric);
    },

    async copyText(value) {
      const text = String(value || "").trim();
      if (text === "") return;
      try {
        if (navigator.clipboard && typeof navigator.clipboard.writeText === "function") {
          await navigator.clipboard.writeText(text);
          return;
        }
      } catch (error) {
      }
      const textarea = document.createElement("textarea");
      textarea.value = text;
      textarea.setAttribute("readonly", "");
      textarea.style.position = "absolute";
      textarea.style.left = "-9999px";
      document.body.appendChild(textarea);
      textarea.select();
      document.execCommand("copy");
      document.body.removeChild(textarea);
    },

    regenerateIdempotencyKey() {
      const pair = String(this.selectedPairId || "");
      const method = String(this.selectedMethodId || "");
      const amount = String(this.amountSent || "");
      const ts = Date.now().toString();
      this.idempotencyKey = btoa([pair, method, amount, ts].join("|")).slice(0, 76);
    },
  };
}

function converterAppFromDataset(element) {
  const readJson = (value, fallback) => {
    if (typeof value !== "string" || value.trim() === "") {
      return fallback;
    }
    try {
      return JSON.parse(value);
    } catch (error) {
      return fallback;
    }
  };

  return converterApp({
    quoteUrl: element?.dataset?.quoteUrl || "",
    categories: readJson(element?.dataset?.categories, []),
    pairs: readJson(element?.dataset?.pairs, []),
    paymentMethods: readJson(element?.dataset?.paymentMethods, []),
    pairMethodMap: readJson(element?.dataset?.pairMethodMap, {}),
    pairMethodRules: readJson(element?.dataset?.pairMethodRules, {}),
    initialToken: element?.dataset?.initialToken || "",
  });
}

function registerConverterPublic() {
  if (!window.Alpine || typeof window.Alpine.data !== "function") {
    return false;
  }

  window.Alpine.data("converterPublic", () => {
    const root = document.getElementById("converter-root");
    return converterAppFromDataset(root);
  });

  return true;
}

if (!registerConverterPublic()) {
  document.addEventListener("alpine:init", registerConverterPublic, { once: true });
}

document.addEventListener("DOMContentLoaded", () => {
  document.querySelectorAll("form[data-once-submit]").forEach((form) => {
    form.addEventListener("submit", () => {
      const buttons = form.querySelectorAll('button[type="submit"], input[type="submit"]');
      buttons.forEach((button) => {
        button.disabled = true;
      });
      setTimeout(() => {
        buttons.forEach((button) => {
          button.disabled = false;
        });
      }, 5000);
    });
  });
});
