(function (global, factory) {
  typeof exports === 'object' && typeof module !== 'undefined' ? factory(exports) :
  typeof define === 'function' && define.amd ? define(['exports'], factory) :
  (global = typeof globalThis !== 'undefined' ? globalThis : global || self, factory((global.strt = global.strt || {}, global.strt.strtSettings = global.strt.strtSettings || {})));
})(this, (function (exports) { 'use strict';

  /**
   * Internal dependencies
   */

  /**
   * Returns true if the two objects are shallow equal, or false otherwise.
   *
   * @param a First object to compare.
   * @param b Second object to compare.
   *
   * @return Whether the two objects are shallow equal.
   */
  function isShallowEqualObjects(a, b) {
    if (a === b) {
      return true;
    }
    const aKeys = Object.keys(a);
    const bKeys = Object.keys(b);
    if (aKeys.length !== bKeys.length) {
      return false;
    }
    let i = 0;
    while (i < aKeys.length) {
      const key = aKeys[i];
      const aValue = a[key];
      if (
      // In iterating only the keys of the first object after verifying
      // equal lengths, account for the case that an explicit `undefined`
      // value in the first is implicitly undefined in the second.
      //
      // Example: isShallowEqualObjects( { a: undefined }, { b: 5 } )
      aValue === undefined && !b.hasOwnProperty(key) || aValue !== b[key]) {
        return false;
      }
      i++;
    }
    return true;
  }

  /**
   * Returns true if the two arrays are shallow equal, or false otherwise.
   *
   * @param a First array to compare.
   * @param b Second array to compare.
   *
   * @return Whether the two arrays are shallow equal.
   */
  function isShallowEqualArrays(a, b) {
    if (a === b) {
      return true;
    }
    if (a.length !== b.length) {
      return false;
    }
    for (let i = 0, len = a.length; i < len; i++) {
      if (a[i] !== b[i]) {
        return false;
      }
    }
    return true;
  }

  /**
   * Проверяет, является ли значение массивом.
   *
   * В отличие от стандартного `Array.isArray`, дополнительно исключает `null` и `undefined`.
   *
   * @param {*} term — значение для проверки
   * @returns {boolean} — `true`, если значение является массивом
   *
   * @example
   * isArray([])            // true
   * isArray([1, 2, 3])     // true
   * isArray(null)          // false
   * isArray(undefined)     // false
   * isArray('string')      // false
   */
  const isArray = term => {
    return term !== null && typeof term !== 'undefined' && Array.isArray(term);
  };

  /**
   * Проверяет, является ли значение null.
   *
   * @param {*} term - Проверяемое значение.
   * @returns {boolean} true, если term === null, иначе false.
   */
  const isNull = term => {
    return term === null;
  };

  /**
   * Проверяет, является ли значение объектом (не массивом и не null).
   *
   * @param {*} term - Проверяемое значение
   * @returns {boolean} true, если term — объект, иначе false
   */
  const isObject = term => {
    return !isNull(term) && typeof term === 'object' && term.constructor === Object && !isArray(term);
  };

  /**
   * Выполняет поверхностное (shallow) сравнение двух значений.
   *
   * - Если оба значения являются простыми объектами → используется `isShallowEqualObjects`
   * - Если оба значения являются массивами → используется `isShallowEqualArrays`
   * - В остальных случаях используется строгое сравнение (`===`)
   *
   * @param {unknown} a - Первое значение для сравнения.
   * @param {unknown} b - Второе значение для сравнения.
   * @returns {boolean} Возвращает true, если значения поверхностно равны, иначе false.
   */
  const shallowEqual = (a, b) => {
    if (a && b) {
      if (isObject(a) && isObject(b)) {
        return isShallowEqualObjects(a, b);
      } else if (isArray(a) && isArray(b)) {
        return isShallowEqualArrays(a, b);
      }
    }
    return a === b;
  };

  /**
   * --------------------------------------------------------------------------
   * Isvek (v1.0.0): utils.js
   * Licensed under MIT[](https://isvek.ru/main/LICENSE.md)
   * --------------------------------------------------------------------------
   *
   * Модуль для управления глобальными настройками приложения в JavaScript.
   * Используется для чтения, записи и подписки на изменения настроек в контексте
   * WordPress и WooCommerce. Настройки хранятся в объекте window.strtSettings,
   * а изменения синхронизируются через события `strt:settings` и `strt:settings:<key>`.
   *
   * @module utils
   */


  /**
   * Пространство имен для настроек приложения.
   * Используется для формирования ключей настроек и событий (например, `strtSettings`).
   * @type {string}
   */
  const NAMESPACE = 'strt';

  /**
   * Ссылка на глобальный объект window или пустой объект для серверного окружения.
   * Используется для безопасного доступа к глобальным настройкам и событиям в браузере.
   * @type {Window|Object}
   */
  const windowRef = typeof window !== 'undefined' ? window : {};

  /**
   * Объект настроек, содержащий данные приложения (например, favoriteProductCount, cartContentsCount).
   * Инициализируется из window.strtSettings, если он существует, иначе создается пустой объект.
   * @type {Object.<string, any>}
   */
  const settings = typeof windowRef[`${NAMESPACE}Settings`] === 'object' ? windowRef[`${NAMESPACE}Settings`] : {};

  /**
   * Название события, вызываемого при любом изменении настроек.
   * Формат: strt:settings. Передает объект { key, value } или массив [{ key, value }] в свойстве detail.
   * @type {string}
   */
  const ROOT_EVENT = `${NAMESPACE}:settings`;

  /**
   * Генерирует имя события для конкретного ключа настройки.
   * Формат: strt:settings:<key>. Передает значение настройки в свойстве detail.
   * @param {string} key - Ключ настройки (например, 'favoriteProductCount').
   * @returns {string} Имя события для указанного ключа.
   */
  const KEY_EVENT = key => `${NAMESPACE}:settings:${key}`;

  /**
   * Проверяет, доступен ли метод dispatchEvent в windowRef (для браузерного окружения).
   * @type {boolean}
   */
  const canDispatch = typeof windowRef.dispatchEvent === 'function';

  /**
   * Проверяет, доступен ли метод addEventListener в windowRef (для браузерного окружения).
   * @type {boolean}
   */
  const canListen = typeof windowRef.addEventListener === 'function';

  /**
   * Получает значение настройки по ключу с возвратом запасного значения, если ключ не найден.
   *
   * @param {string} name - Ключ настройки (например, 'favoriteProductCount' или 'cartContentsCount').
   * @param {any} [fallback=undefined] - Запасное значение, возвращаемое, если ключ отсутствует.
   * @param {function(any, any): any} [filter] - Функция-фильтр для нормализации значения (val, fb).
   * @returns {any} Значение настройки или запасное значение.
   * @throws {Error} Если параметр name не является строкой.
   * @example
   * const count = getSetting('favoriteProductCount', 0); // Вернет 0, если ключ отсутствует
   */
  const getSetting = function (name) {
    let fallback = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : undefined;
    let filter = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : (val, fb) => typeof val !== 'undefined' ? val : fb;
    if (typeof name !== 'string') {
      throw new Error('getSetting: Параметр "name" должен быть строкой');
    }
    const value = name in settings ? settings[name] : fallback;
    return filter(value, fallback);
  };

  /**
   * Возвращает копию объекта настроек приложения.
   * Используется для безопасного доступа ко всем настройкам без риска их изменения.
   *
   * @returns {Object.<string, any>} Копия объекта настроек.
   * @example
   * const allSettings = getSettings(); // { favoriteProductCount: 3, cartContentsCount: 2, ... }
   */
  const getSettings = () => ({
    ...settings
  });

  /**
   * Устанавливает значение настройки по ключу и вызывает события при изменении.
   * Обновляет window.strtSettings и генерирует события strt:settings и strt:settings:<key>,
   * если значение изменилось. События не вызываются, если значение осталось прежним или API событий недоступно.
   *
   * @param {string} name - Ключ настройки (например, 'cartContentsCount').
   * @param {any} value - Новое значение настройки.
   * @throws {Error} Если параметр name не является строкой.
   * @example
   * setSetting('cartContentsCount', 5); // Устанавливает значение и вызывает события
   */
  const setSetting = (name, value) => {
    if (typeof name !== 'string') {
      throw new Error('setSetting: Параметр "name" должен быть строкой');
    }
    const previousValue = settings[name];
    settings[name] = value;
    const changed = !shallowEqual(previousValue, value);
    if (canDispatch && changed) {
      try {
        // Инициализация пространства имен в window
        windowRef[NAMESPACE] = windowRef[NAMESPACE] || {};
        windowRef[`${NAMESPACE}Settings`] = settings;

        // Вызов общего события для всех изменений настроек
        windowRef.dispatchEvent(new CustomEvent(ROOT_EVENT, {
          detail: {
            key: name,
            value
          }
        }));

        // Вызов события для конкретного ключа
        windowRef.dispatchEvent(new CustomEvent(KEY_EVENT(name), {
          detail: value
        }));
      } catch (error) {
        console.error(`Ошибка при вызове событий для настройки "${name}":`, error);
      }
    }
  };

  /**
   * Устанавливает сразу несколько настроек и вызывает события для измененных ключей.
   * Обновляет window.strtSettings и генерирует события strt:settings:<key> для каждого измененного ключа
   * и одно групповое событие strt:settings с массивом [{ key, value }].
   *
   * @param {Object.<string, any>} updates - Объект с парами ключ-значение для обновления.
   * @throws {Error} Если updates не является объектом или содержит нестроковые ключи.
   * @example
   * setSettingsBatch({
   *   favoriteProductCount: 3,
   *   cartContentsCount: 5
   * }); // Обновляет оба ключа и вызывает события
   */
  const setSettingsBatch = updates => {
    if (typeof updates !== 'object' || updates === null) {
      throw new Error('setSettingsBatch: Параметр "updates" должен быть объектом');
    }
    const changedSettings = [];

    // Обновляем настройки и собираем измененные пары
    Object.entries(updates).forEach(_ref => {
      let [key, value] = _ref;
      if (typeof key !== 'string') {
        throw new Error(`setSettingsBatch: Ключ "${key}" должен быть строкой`);
      }
      const prev = settings[key];
      const changed = !shallowEqual(prev, value);
      settings[key] = value;
      if (changed) {
        changedSettings.push({
          key,
          value
        });
      }
    });
    if (canDispatch && changedSettings.length > 0) {
      try {
        // Инициализация пространства имен в window
        windowRef[NAMESPACE] = windowRef[NAMESPACE] || {};
        windowRef[`${NAMESPACE}Settings`] = settings;

        // Вызов событий для каждого измененного ключа
        changedSettings.forEach(_ref2 => {
          let {
            key,
            value
          } = _ref2;
          windowRef.dispatchEvent(new CustomEvent(KEY_EVENT(key), {
            detail: value
          }));
        });

        // Вызов группового события с массивом изменений
        windowRef.dispatchEvent(new CustomEvent(ROOT_EVENT, {
          detail: changedSettings
        }));
      } catch (error) {
        console.error('Ошибка при вызове событий в setSettingsBatch:', error);
      }
    }
  };

  /**
   * Подписывает обработчик на изменение конкретной настройки.
   * Возвращает функцию для отписки от события. Если API событий недоступно, возвращает пустую функцию.
   *
   * @param {string} name - Ключ настройки для отслеживания (например, 'favoriteProductCount').
   * @param {function(any): void} handler - Функция-обработчик, вызываемая при изменении настройки.
   * @returns {function(): void} Функция для отписки от события.
   * @throws {Error} Если name не строка или handler не функция.
   * @example
   * const unsubscribe = onSetting('favoriteProductCount', (count) => {
   *   console.log(`Избранное обновлено: ${count}`);
   * });
   * unsubscribe(); // Отписка от события
   */
  const onSetting = (name, handler) => {
    if (typeof name !== 'string') {
      throw new Error('onSetting: Параметр "name" должен быть строкой');
    }
    if (typeof handler !== 'function') {
      throw new Error('onSetting: Параметр "handler" должен быть функцией');
    }
    if (!canListen) {
      return () => {};
    }
    const listener = event => handler(event.detail);
    windowRef.addEventListener(KEY_EVENT(name), listener);
    return () => {
      try {
        windowRef.removeEventListener(KEY_EVENT(name), listener);
      } catch (error) {
        console.error(`Ошибка при отписке от события "${KEY_EVENT(name)}":`, error);
      }
    };
  };

  /**
   * Подписывает обработчик на изменение любой настройки.
   * Обработчик получает объект { key, value } для одиночных изменений или массив [{ key, value }] для массовых.
   * Возвращает функцию для отписки. Если API событий недоступно, возвращает пустую функцию.
   *
   * @param {function({key: string, value: any}|Array.<{key: string, value: any}>): void} handler - Функция-обработчик.
   * @returns {function(): void} Функция для отписки от события.
   * @throws {Error} Если handler не является функцией.
   * @example
   * const unsubscribe = onAnySetting((update) => {
   *   console.log('Настройки изменены:', update);
   * });
   * unsubscribe(); // Отписка от события
   */
  const onAnySetting = handler => {
    if (typeof handler !== 'function') {
      throw new Error('onAnySetting: Параметр "handler" должен быть функцией');
    }
    if (!canListen) {
      return () => {};
    }
    const listener = event => handler(event.detail);
    windowRef.addEventListener(ROOT_EVENT, listener);
    return () => {
      try {
        windowRef.removeEventListener(ROOT_EVENT, listener);
      } catch (error) {
        console.error(`Ошибка при отписке от события "${ROOT_EVENT}":`, error);
      }
    };
  };

  /**
   * Получает значение настройки по имени с приведением типа и значением по умолчанию.
   *
   * @param {string} [name=''] - Имя настройки, которую нужно получить.
   * @param {*} fallback - Значение по умолчанию, возвращаемое, если настройка не найдена или не проходит проверку типа.
   * @param {Function} typeguard - Функция, проверяющая тип значения настройки.
   *                              Должна возвращать `true`, если значение валидно, иначе `false`.
   * @returns {*} Значение настройки, если оно существует и проходит проверку типа, иначе значение по умолчанию.
   * @example
   * const settings = { theme: 'dark' };
   * const getTheme = getSettingWithCoercion('theme', 'light', (val) => typeof val === 'string');
   * console.log(getTheme); // 'dark'
   */
  const getSettingWithCoercion = function () {
    let name = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : '';
    let fallback = arguments.length > 1 ? arguments[1] : undefined;
    let typeguard = arguments.length > 2 ? arguments[2] : undefined;
    const value = name in settings ? settings[name] : fallback;
    return typeguard(value, fallback) ? value : fallback;
  };

  /**
   * --------------------------------------------------------------------------
   * Isvek (v1.0.0): index.js
   * Licensed under MIT[](https://isvek.ru/main/LICENSE.md)
   * --------------------------------------------------------------------------
   *
   * Основной модуль для экспорта утилит и глобальных настроек приложения.
   * Предоставляет доступ к настройкам WordPress и WooCommerce, а также утилитам
   * для работы с настройками через @strt/settings. Настройки инициализируются из
   * window.strtSettings и доступны через объект `api`.
   *
   * @module index
   */


  /**
   * URL админ-панели WordPress.
   * @type {string}
   */
  const ADMIN_URL = getSetting('adminUrl', '');

  /**
   * URL главной страницы сайта.
   * @type {string}
   */
  const HOME_URL = getSetting('homeUrl', '');

  /**
   * Флаг, указывающий, авторизован ли пользователь.
   * @type {boolean}
   */
  const IS_USER_LOGGED_IN = getSetting('isUserLoggedIn', false);

  /**
   * Данные текущего пользователя или false, если пользователь не авторизован.
   * @type {Object|boolean}
   */
  const CURRENT_USER = getSetting('currentUser', false);

  /**
   * Флаг, указывающий, является ли текущий пользователь администратором.
   * @type {boolean}
   */
  const CURRENT_USER_IS_ADMIN = getSetting('currentUserIsAdmin', false);

  /**
   * Nonce для выполнения сброса WordPress (например, для AJAX-запросов).
   * @type {string}
   */
  const WP_RESET_NONCE = getSetting('wpResetNonce', '');

  /**
   * Данные о валюте магазина WooCommerce (например, код, символ, формат).
   * @type {Object}
   */
  const CURRENCY = getSetting('currency', {});

  /**
   * Единица измерения веса в WooCommerce (например, 'kg', 'g').
   * @type {string}
   */
  const WEIGHT_UNIT = getSetting('weightUnit', '');

  /**
   * Список страниц магазина WooCommerce (например, myaccount, cart, checkout).
   * @type {Object.<string, {id: number, title: string, permalink: string}>}
   */
  const STORE_PAGES = getSetting('storePages', {});

  /**
   * Данные изображения-заглушки для товаров WooCommerce.
   * @type {Object}
   */
  const PLACEHOLDER_IMG = getSetting('placeholderImg', {});

  /**
   * Пункты меню учетной записи пользователя WooCommerce.
   * @type {Object.<string, {title: string, permalink: string}>}
   */
  const ACCOUNT_MENU = getSetting('accountMenu', {});

  /**
   * Nonce для WooCommerce Store API.
   * @type {string}
   */
  const WC_STORE_API_NONCE = getSetting('wcStoreApiNonce', '');

  /**
   * Данные об интеграциях API (например, сторонние сервисы).
   * @type {Array}
   */
  const API = getSetting('apiIntegration', []);

  exports.ACCOUNT_MENU = ACCOUNT_MENU;
  exports.ADMIN_URL = ADMIN_URL;
  exports.API = API;
  exports.CURRENCY = CURRENCY;
  exports.CURRENT_USER = CURRENT_USER;
  exports.CURRENT_USER_IS_ADMIN = CURRENT_USER_IS_ADMIN;
  exports.HOME_URL = HOME_URL;
  exports.IS_USER_LOGGED_IN = IS_USER_LOGGED_IN;
  exports.NAMESPACE = NAMESPACE;
  exports.PLACEHOLDER_IMG = PLACEHOLDER_IMG;
  exports.STORE_PAGES = STORE_PAGES;
  exports.WC_STORE_API_NONCE = WC_STORE_API_NONCE;
  exports.WEIGHT_UNIT = WEIGHT_UNIT;
  exports.WP_RESET_NONCE = WP_RESET_NONCE;
  exports.getSetting = getSetting;
  exports.getSettingWithCoercion = getSettingWithCoercion;
  exports.getSettings = getSettings;
  exports.onAnySetting = onAnySetting;
  exports.onSetting = onSetting;
  exports.setSetting = setSetting;
  exports.setSettingsBatch = setSettingsBatch;

}));
//# sourceMappingURL=settings.js.map
