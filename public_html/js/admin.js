/******/ (function(modules) { // webpackBootstrap
/******/ 	// The module cache
/******/ 	var installedModules = {};
/******/
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/
/******/ 		// Check if module is in cache
/******/ 		if(installedModules[moduleId]) {
/******/ 			return installedModules[moduleId].exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = installedModules[moduleId] = {
/******/ 			i: moduleId,
/******/ 			l: false,
/******/ 			exports: {}
/******/ 		};
/******/
/******/ 		// Execute the module function
/******/ 		modules[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/
/******/ 		// Flag the module as loaded
/******/ 		module.l = true;
/******/
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/
/******/
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = modules;
/******/
/******/ 	// expose the module cache
/******/ 	__webpack_require__.c = installedModules;
/******/
/******/ 	// define getter function for harmony exports
/******/ 	__webpack_require__.d = function(exports, name, getter) {
/******/ 		if(!__webpack_require__.o(exports, name)) {
/******/ 			Object.defineProperty(exports, name, { enumerable: true, get: getter });
/******/ 		}
/******/ 	};
/******/
/******/ 	// define __esModule on exports
/******/ 	__webpack_require__.r = function(exports) {
/******/ 		if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 			Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 		}
/******/ 		Object.defineProperty(exports, '__esModule', { value: true });
/******/ 	};
/******/
/******/ 	// create a fake namespace object
/******/ 	// mode & 1: value is a module id, require it
/******/ 	// mode & 2: merge all properties of value into the ns
/******/ 	// mode & 4: return value when already ns object
/******/ 	// mode & 8|1: behave like require
/******/ 	__webpack_require__.t = function(value, mode) {
/******/ 		if(mode & 1) value = __webpack_require__(value);
/******/ 		if(mode & 8) return value;
/******/ 		if((mode & 4) && typeof value === 'object' && value && value.__esModule) return value;
/******/ 		var ns = Object.create(null);
/******/ 		__webpack_require__.r(ns);
/******/ 		Object.defineProperty(ns, 'default', { enumerable: true, value: value });
/******/ 		if(mode & 2 && typeof value != 'string') for(var key in value) __webpack_require__.d(ns, key, function(key) { return value[key]; }.bind(null, key));
/******/ 		return ns;
/******/ 	};
/******/
/******/ 	// getDefaultExport function for compatibility with non-harmony modules
/******/ 	__webpack_require__.n = function(module) {
/******/ 		var getter = module && module.__esModule ?
/******/ 			function getDefault() { return module['default']; } :
/******/ 			function getModuleExports() { return module; };
/******/ 		__webpack_require__.d(getter, 'a', getter);
/******/ 		return getter;
/******/ 	};
/******/
/******/ 	// Object.prototype.hasOwnProperty.call
/******/ 	__webpack_require__.o = function(object, property) { return Object.prototype.hasOwnProperty.call(object, property); };
/******/
/******/ 	// __webpack_public_path__
/******/ 	__webpack_require__.p = "/";
/******/
/******/
/******/ 	// Load entry module and return exports
/******/ 	return __webpack_require__(__webpack_require__.s = 1);
/******/ })
/************************************************************************/
/******/ ({

/***/ "./resources/js/admin.js":
/*!*******************************!*\
  !*** ./resources/js/admin.js ***!
  \*******************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

__webpack_require__(/*! ./admin/orders.js */ "./resources/js/admin/orders.js");

__webpack_require__(/*! ./admin/chats.js */ "./resources/js/admin/chats.js");

function loader(element) {
  var loader = document.createElement('div');
  loader.classList.add('loader--warper');
  var loader2 = document.createElement('div');
  loader2.classList.add('loader');
  loader.appendChild(loader2);
  element.appendChild(loader);
}

/***/ }),

/***/ "./resources/js/admin/chats.js":
/*!*************************************!*\
  !*** ./resources/js/admin/chats.js ***!
  \*************************************/
/*! no static exports found */
/***/ (function(module, exports) {

var rtf = new Intl.RelativeTimeFormat('pl', {
  numeric: 'auto'
});
var chats = document.getElementById('chats');

function updateChats() {
  loader(orders);
  fetch('/api/admin/orders', {
    credentials: 'same-origin'
  }).then(function (response) {
    return response.json();
  }).then(function (data) {
    if (data.error) return;
    var days;
    data.forEach(function (row) {
      var created = new Date(row.created_at);
      var sec = created.getTime() - new Date().getTime();
      created = Math.ceil(sec / (1000 * 60 * 60 * 24));

      if (days != created) {
        var _e = document.createElement('li');

        _e.classList.add('separator');

        _e.innerText = rtf.format(created, 'day');
        orders.appendChild(_e);
        days = created;
      }

      var e = document.createElement('li');
      var left = document.createElement('div');
      var top = document.createElement('div');
      top.innerText = row.code;
      left.appendChild(top);
      var bottom = document.createElement('small');
      bottom.innerText = row.email;
      left.appendChild(bottom);
      e.appendChild(left);
      var sum = document.createElement('div');
      sum.innerText = row.sum;
      sum.classList.add('sum');
      e.appendChild(sum);
      var status = document.createElement('div');
      status.classList.add('status');
      e.appendChild(status);
      row.status.forEach(function (color) {
        if (color == null) return;
        var x = document.createElement('div');
        x.classList.add('status-circle');
        x.classList.add('status-circle__' + color);
        status.appendChild(x);
      });
      var a = document.createElement('a');
      a.href = '/admin/orders/' + row.id;
      a.appendChild(e);
      orders.appendChild(a);
    });
    loader.remove();
  });
}

if (chats !== null) updateOrders();

/***/ }),

/***/ "./resources/js/admin/orders.js":
/*!**************************************!*\
  !*** ./resources/js/admin/orders.js ***!
  \**************************************/
/*! no static exports found */
/***/ (function(module, exports) {

var rtf = new Intl.RelativeTimeFormat('pl', {
  numeric: 'auto'
});
var orders = document.getElementById('orders');

function updateOrders() {
  var loader = document.createElement('div');
  loader.classList.add('loader--warper');
  var loader2 = document.createElement('div');
  loader2.classList.add('loader');
  loader.appendChild(loader2);
  orders.appendChild(loader);
  fetch('/api/admin/orders', {
    credentials: 'same-origin'
  }).then(function (response) {
    return response.json();
  }).then(function (data) {
    if (data.error) return;
    var days;
    data.forEach(function (row) {
      var created = new Date(row.created_at);
      var sec = created.getTime() - new Date().getTime();
      created = Math.ceil(sec / (1000 * 60 * 60 * 24));

      if (days != created) {
        var _e = document.createElement('li');

        _e.classList.add('separator');

        _e.innerText = rtf.format(created, 'day');
        orders.appendChild(_e);
        days = created;
      }

      var e = document.createElement('li');
      var left = document.createElement('div');
      var top = document.createElement('div');
      top.innerText = row.code;
      left.appendChild(top);
      var bottom = document.createElement('small');
      bottom.innerText = row.email;
      left.appendChild(bottom);
      e.appendChild(left);
      var sum = document.createElement('div');
      sum.innerText = row.sum;
      sum.classList.add('sum');
      e.appendChild(sum);
      var status = document.createElement('div');
      status.classList.add('status');
      e.appendChild(status);
      row.status.forEach(function (color) {
        if (color == null) return;
        var x = document.createElement('div');
        x.classList.add('status-circle');
        x.classList.add('status-circle__' + color);
        status.appendChild(x);
      });
      var a = document.createElement('a');
      a.href = '/admin/orders/' + row.id;
      a.appendChild(e);
      orders.appendChild(a);
    });
    loader.remove();
  });
}

if (orders !== null) updateOrders();

/***/ }),

/***/ 1:
/*!*************************************!*\
  !*** multi ./resources/js/admin.js ***!
  \*************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

module.exports = __webpack_require__(/*! /Users/jedrzej/Projekty/depth-2/resources/js/admin.js */"./resources/js/admin.js");


/***/ })

/******/ });