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

__webpack_require__(/*! ./admin/loader.js */ "./resources/js/admin/loader.js");

__webpack_require__(/*! ./admin/orders.js */ "./resources/js/admin/orders.js");

__webpack_require__(/*! ./admin/products.js */ "./resources/js/admin/products.js");

__webpack_require__(/*! ./admin/chats.js */ "./resources/js/admin/chats.js");

__webpack_require__(/*! ./admin/dark.js */ "./resources/js/admin/dark.js");

window.toBottom = function () {
  window.scrollTo(0, document.body.scrollHeight);
};

/***/ }),

/***/ "./resources/js/admin/chats.js":
/*!*************************************!*\
  !*** ./resources/js/admin/chats.js ***!
  \*************************************/
/*! no static exports found */
/***/ (function(module, exports) {

window.updateChats = function () {
  var chats = document.getElementById('chats');
  window.loader.start(chats);
  fetch('/api/admin/chats', {
    credentials: 'same-origin'
  }).then(function (response) {
    return response.json();
  }).then(function (data) {
    if (data.error) return;
    var chatsTemp = chats.innerHTML;
    data.forEach(function (row) {
      chatsTemp += "\n      <a href=\"/admin/chat/".concat(row.id, "\">\n        <li class=\"clickable\">\n          <div class=\"avatar\">\n            <img src=\"").concat(row.avatar, "\">\n          </div>\n          <div>\n            <div class=\"").concat(row.unread ? "unread" : "", "\">").concat(row.client.name, "</div>\n            <small>").concat(row.snippet, "</small>\n          </div>\n        </li>\n      </a>");
    });
    chats.innerHTML = chatsTemp;
  });
  window.loader.stop();
};

/***/ }),

/***/ "./resources/js/admin/dark.js":
/*!************************************!*\
  !*** ./resources/js/admin/dark.js ***!
  \************************************/
/*! no static exports found */
/***/ (function(module, exports) {

window.darkMode = function () {
  if (localStorage.dark == undefined || localStorage.dark == 0) {
    localStorage.setItem('dark', 1);
    document.body.classList.add('dark');
  } else {
    localStorage.setItem('dark', 0);
    document.body.classList.remove('dark');
  }
};

if (localStorage.dark == 1) {
  document.body.classList.add('dark');
}

/***/ }),

/***/ "./resources/js/admin/loader.js":
/*!**************************************!*\
  !*** ./resources/js/admin/loader.js ***!
  \**************************************/
/*! no static exports found */
/***/ (function(module, exports) {

window.loader = {
  start: function start(element) {
    var loader = document.createElement('div');
    loader.classList.add('loader--warper');
    loader.id = 'loader';
    var loader2 = document.createElement('div');
    loader2.classList.add('loader');
    loader.appendChild(loader2);
    element.appendChild(loader);
  },
  stop: function stop() {
    document.getElementById('loader').remove();
  }
};

/***/ }),

/***/ "./resources/js/admin/orders.js":
/*!**************************************!*\
  !*** ./resources/js/admin/orders.js ***!
  \**************************************/
/*! no static exports found */
/***/ (function(module, exports) {

window.updateOrders = function () {
  var rtf = new Intl.RelativeTimeFormat('pl', {
    numeric: 'auto'
  });
  var orders = document.getElementById('orders');
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
      e.classList.add('clickable');
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
};

/***/ }),

/***/ "./resources/js/admin/products.js":
/*!****************************************!*\
  !*** ./resources/js/admin/products.js ***!
  \****************************************/
/*! no static exports found */
/***/ (function(module, exports) {

var products = document.getElementById('products');
var formatter = new Intl.NumberFormat('pl-PL', {
  style: 'currency',
  currency: 'PLN'
});

window.updateProducts = function () {
  window.loader.start(products);
  fetch('/api/admin/products', {
    credentials: 'same-origin'
  }).then(function (response) {
    return response.json();
  }).then(function (data) {
    if (data.error) return;
    var temp = products.innerHTML;
    data.forEach(function (row) {
      temp += "\n        <a href=\"/admin/products/".concat(row.id, "\" class=\"product\">\n          <div class=\"product__img\">\n            <img src=\"").concat(row.img, "\">\n          </div>\n          <div class=\"flex\">\n            <div class=\"name\">\n              ").concat(row.name, "<br/>\n              <small>").concat(formatter.format(row.price), "</small>  \n            </div>\n          </div>\n        </a>");
    });
    products.innerHTML = temp;
  });
  window.loader.stop();
};

/***/ }),

/***/ 1:
/*!*************************************!*\
  !*** multi ./resources/js/admin.js ***!
  \*************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

module.exports = __webpack_require__(/*! /Users/jedrzej/Projekty/depth/resources/js/admin.js */"./resources/js/admin.js");


/***/ })

/******/ });