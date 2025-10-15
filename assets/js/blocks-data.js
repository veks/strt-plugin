(function (global, factory) {
  typeof exports === 'object' && typeof module !== 'undefined' ? factory(exports, require('@wordpress/data')) :
  typeof define === 'function' && define.amd ? define(['exports', '@wordpress/data'], factory) :
  (global = typeof globalThis !== 'undefined' ? globalThis : global || self, factory((global.strt = global.strt || {}, global.strt.strtBlocksData = global.strt.strtBlocksData || {}), global.wp.data));
})(this, (function (exports, data) { 'use strict';

  const STORE_KEY = 'strt/store/query-state';

  const getStateForContext = (state, context) => {
    return typeof state[context] === 'undefined' ? null : state[context];
  };

  /**
   * Селектор для получения определённого состояния запроса для заданного контекста.
   *
   * @param {Object} state Текущее состояние.
   * @param {string} context Контекст для извлекаемого состояния запроса.
   * @param {string} queryKey Ключ для определённого элемента состояния запроса.
   * @param {*} defaultValue Значение по умолчанию для ключа состояния запроса, если оно в данный момент
   * отсутствует в состоянии.
   *
   * @return {*} Текущее сохранённое значение или defaultValue, если оно отсутствует.
   */
  const getValueForQueryKey = function (state, context, queryKey) {
    let defaultValue = arguments.length > 3 && arguments[3] !== undefined ? arguments[3] : {};
    let stateContext = getStateForContext(state, context);
    if (stateContext === null) {
      return defaultValue;
    }
    stateContext = JSON.parse(stateContext);
    return typeof stateContext[queryKey] !== 'undefined' ? stateContext[queryKey] : defaultValue;
  };

  /**
   * Селектор для получения состояния запроса для заданного контекста.
   *
   * @param {Object} state Текущее состояние.
   * @param {string} context Контекст для извлекаемого состояния запроса.
   * @param {*} defaultValue Значение по умолчанию, возвращаемое, если для
   * заданного контекста состояние отсутствует.
   *
   * @return {*} Текущее сохранённое состояние запроса для заданного контекста или
   * defaultValue, если отсутствует в состоянии.
   */
  const getValueForQueryContext = function (state, context) {
    let defaultValue = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : {};
    const stateContext = getStateForContext(state, context);
    return stateContext === null ? defaultValue : JSON.parse(stateContext);
  };

  var selectors = /*#__PURE__*/Object.freeze({
    __proto__: null,
    getValueForQueryContext: getValueForQueryContext,
    getValueForQueryKey: getValueForQueryKey
  });

  const ACTION_TYPES = {
    SET_QUERY_KEY_VALUE: 'SET_QUERY_KEY_VALUE',
    SET_QUERY_CONTEXT_VALUE: 'SET_QUERY_CONTEXT_VALUE'
  };

  /**
   * Создатель действия для установки одного значения состояния запроса для заданного контекста.
   *
   * @param {string} context Контекст для сохраняемого состояния запроса.
   * @param {string} queryKey Ключ для элемента запроса.
   * @param {*} value Значение элемента запроса.
   *
   * @return {Object} Объект действия.
   */
  const setQueryValue = (context, queryKey, value) => {
    return {
      type: ACTION_TYPES.SET_QUERY_KEY_VALUE,
      context,
      queryKey,
      value
    };
  };

  /**
   * Создатель действия для установки состояния запроса для заданного контекста.
   *
   * @param {string} context Контекст для сохраняемого состояния запроса.
   * @param {*} value Состояние запроса, сохраняемое для заданного контекста.
   *
   * @return {Object} Объект действия.
   */
  const setValueForQueryContext = (context, value) => {
    return {
      type: ACTION_TYPES.SET_QUERY_CONTEXT_VALUE,
      context,
      value
    };
  };

  var actions = /*#__PURE__*/Object.freeze({
    __proto__: null,
    setQueryValue: setQueryValue,
    setValueForQueryContext: setValueForQueryContext
  });

  /**
   * Редуктор для обработки действий, связанных с хранилищем состояний запроса.
   *
   * @param {Object} state Текущее состояние в хранилище.
   * @param {Object} action Действие, которое обрабатывается.
   */
  const queryStateReducer = function () {
    let state = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
    let action = arguments.length > 1 ? arguments[1] : undefined;
    const {
      type,
      context,
      queryKey,
      value
    } = action;
    const prevState = getStateForContext(state, context);
    let newState;
    switch (type) {
      case ACTION_TYPES.SET_QUERY_KEY_VALUE:
        const prevStateObject = prevState !== null ? JSON.parse(prevState) : {};
        prevStateObject[queryKey] = value;
        newState = JSON.stringify(prevStateObject);
        if (prevState !== newState) {
          state = {
            ...state,
            [context]: newState
          };
        }
        break;
      case ACTION_TYPES.SET_QUERY_CONTEXT_VALUE:
        newState = JSON.stringify(value);
        if (prevState !== newState) {
          state = {
            ...state,
            [context]: newState
          };
        }
        break;
    }
    return state;
  };

  const config = {
    reducer: queryStateReducer,
    actions,
    selectors
  };
  const store = data.createReduxStore(STORE_KEY, config);
  data.register(store);
  const QUERY_STATE_STORE_KEY = STORE_KEY;

  exports.QUERY_STATE_STORE_KEY = QUERY_STATE_STORE_KEY;
  exports.queryStateStore = store;

}));
//# sourceMappingURL=blocks-data.js.map
