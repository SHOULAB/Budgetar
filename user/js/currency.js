// currency.js — Global currency utility for all pages

const currencySymbols = {
    'EUR': '€',
    'USD': '$',
    'GBP': '£',
    'JPY': '¥',
    'CAD': '$',
    'AUD': '$',
    'CHF': 'CHF',
    'CNY': '¥',
    'INR': '₹',
    'MXN': '$'
};

const currencyNames = {
    'EUR': 'Eiro',
    'USD': 'ASV Dolārs',
    'GBP': 'Sterliņu mārciņa',
    'JPY': 'Japānas Jena',
    'CAD': 'Kanādas Dolārs',
    'AUD': 'Austrālijas Dolārs',
    'CHF': 'Šveices Franks',
    'CNY': 'Ķīnas Juaņ',
    'INR': 'Indijas Rupija',
    'MXN': 'Meksikas Peso'
};

// Track currency change listeners
const currencyListeners = [];

/**
 * Register a callback to be called whenever the currency changes
 * @param {Function} callback - Function to call with (newCurrency, symbol)
 */
function onCurrencyChange(callback) {
    currencyListeners.push(callback);
}

/**
 * Get the current currency preference from localStorage
 * Falls back to EUR if not set
 */
function getCurrentCurrency() {
    return localStorage.getItem('budgetiva_currency') || 'EUR';
}

/**
 * Get the currency symbol for a given currency code
 * @param {string} code - Currency code (e.g., 'EUR', 'USD')
 * @returns {string} - Currency symbol
 */
function getCurrencySymbol(code) {
    return currencySymbols[code] || code;
}

/**
 * Format an amount with the current currency
 * @param {number} amount - The amount to format
 * @param {string} code - Optional currency code; uses current if not provided
 * @returns {string} - Formatted amount (e.g., "€12.50")
 */
function formatCurrency(amount, code = null) {
    const currency = code || getCurrentCurrency();
    const symbol = getCurrencySymbol(currency);
    const formatted = parseFloat(amount).toFixed(2);
    return `${symbol}${formatted}`;
}

/**
 * Get formatted currency for display in a specific way
 * @param {number} amount - The amount to format
 * @param {string} prefix - Optional prefix (default: symbol first)
 * @returns {string} - Formatted amount
 */
function formatCurrencyAlt(amount, prefix = true) {
    const currency = getCurrentCurrency();
    const symbol = getCurrencySymbol(currency);
    const formatted = parseFloat(amount).toFixed(2);
    return prefix ? `${symbol}${formatted}` : `${formatted} ${symbol}`;
}

/**
 * Notify all listeners of a currency change
 * @param {string} newCurrency - The new currency code
 */
function notifyCurrencyChange(newCurrency) {
    const symbol = getCurrencySymbol(newCurrency);
    currencyListeners.forEach(callback => {
        try {
            callback(newCurrency, symbol);
        } catch (e) {
            console.error('Error in currency change listener:', e);
        }
    });
}
