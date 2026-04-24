/**
 * Lightweight i18n engine for BullWatt
 * Reads JSON translation files from /lang/{locale}.json
 * Persists the user choice in localStorage under "bullwatt-lang"
 */
(function () {
  'use strict';

  const SUPPORTED_LANGS = ['en', 'fr'];
  const STORAGE_KEY = 'bullwatt-lang';
  let _translations = {};
  let _currentLang = 'en';

  /**
   * Detect the initial language:
   * 1. localStorage preference
   * 2. Browser language
   * 3. Fallback to 'en'
   */
  function detectLanguage() {
    const stored = localStorage.getItem(STORAGE_KEY);
    if (stored && SUPPORTED_LANGS.includes(stored)) return stored;

    const browserLang = (navigator.language || navigator.userLanguage || 'en').slice(0, 2).toLowerCase();
    if (SUPPORTED_LANGS.includes(browserLang)) return browserLang;

    return 'en';
  }

  /**
   * Apply translations to all elements with [data-i18n] attribute
   */
  function applyTranslations() {
    document.querySelectorAll('[data-i18n]').forEach(function (el) {
      const key = el.getAttribute('data-i18n');
      const value = _translations[key];
      if (value === undefined) return;

      // Keys ending with .html use innerHTML (for links, lists, etc.)
      if (key.endsWith('.html')) {
        el.innerHTML = value;
      } else {
        el.textContent = value;
      }
    });

    // Update <html lang="..."> attribute
    document.documentElement.lang = _currentLang;

    // Update language switcher display
    var langDisplay = document.getElementById('currentLangDisplay');
    if (langDisplay) {
      langDisplay.textContent = _currentLang.toUpperCase();
    }
  }

  /**
   * Load a language JSON file and apply translations
   * @param {string} lang - Language code ('en' or 'fr')
   * @returns {Promise}
   */
  function loadLanguage(lang) {
    if (!SUPPORTED_LANGS.includes(lang)) lang = 'en';
    _currentLang = lang;

    // Determine the base path: handle pages in subdirectories (e.g. /articles/)
    var basePath = './';
    if (window.location.pathname.includes('/articles/')) {
      basePath = '../';
    }

    return fetch(basePath + 'lang/' + lang + '.json')
      .then(function (res) { return res.json(); })
      .then(function (data) {
        _translations = data;
        applyTranslations();
      })
      .catch(function (err) {
        console.error('[i18n] Failed to load language file:', err);
      });
  }

  /**
   * Switch language and persist preference
   * @param {string} lang - Language code
   */
  function setLanguage(lang) {
    localStorage.setItem(STORAGE_KEY, lang);
    loadLanguage(lang);
  }

  /**
   * Get a translated string by key (for use in JS template strings)
   * @param {string} key - Translation key
   * @returns {string} Translated string or the key itself as fallback
   */
  function t(key) {
    return _translations[key] || key;
  }

  /**
   * Get the current language code
   * @returns {string}
   */
  function getCurrentLang() {
    return _currentLang;
  }

  // Expose global API
  window.i18n = {
    setLanguage: setLanguage,
    t: t,
    getCurrentLang: getCurrentLang
  };

  // Auto-initialize on DOM ready
  document.addEventListener('DOMContentLoaded', function () {
    var lang = detectLanguage();
    loadLanguage(lang);
  });
})();
