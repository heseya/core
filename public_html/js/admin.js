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
/******/ 	return __webpack_require__(__webpack_require__.s = 0);
/******/ })
/************************************************************************/
/******/ ({

/***/ "./resources/js/admin.js":
/*!*******************************!*\
  !*** ./resources/js/admin.js ***!
  \*******************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

__webpack_require__(/*! ./admin/modal.js */ "./resources/js/admin/modal.js");

__webpack_require__(/*! ./admin/cart.js */ "./resources/js/admin/cart.js");

__webpack_require__(/*! ./admin/status.js */ "./resources/js/admin/status.js");

__webpack_require__(/*! ./admin/dark.js */ "./resources/js/admin/dark.js");

/***/ }),

/***/ "./resources/js/admin/cart.js":
/*!************************************!*\
  !*** ./resources/js/admin/cart.js ***!
  \************************************/
/*! no static exports found */
/***/ (function(module, exports) {

window.cartItems = 0;

window.addItem = function ($id) {
  var depth = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 0;
  var parent = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : 'items';
  window.cartItems++;
  div = document.createElement('div');
  div.classList.add('depth-' + depth);
  div.id = 'item' + window.cartItems;
  div.innerHTML = '<div class="columns">' + '<div class="column is-2">' + '<div class="field">' + '<label class="label">Symbol</label>' + '<div class="control">' + '<input name="' + parent + '[' + window.cartItems + '][symbol]" class="input">' + '</div>' + '</div>' + '</div>' + '<div class="column is-6">' + '<div class="field">' + '<label class="label">Nazwa</label>' + '<div class="control">' + '<input name="' + parent + '[' + window.cartItems + '][name]" class="input" required>' + '</div>' + '</div>' + '</div>' + '<div class="column is-1">' + '<div class="field">' + '<label class="label">Ilość</label>' + '<div class="control">' + '<input name="' + parent + '[' + window.cartItems + '][qty]" class="input" required>' + '</div>' + '</div>' + '</div>' + '<div class="column is-2">' + '<div class="field">' + '<label class="label">Cena</label>' + '<div class="control">' + '<input name="' + parent + '[' + window.cartItems + '][price]" class="input" required>' + '</div>' + '</div>' + '</div>' + '<div class="column is-1">' + '<div class="buttons">' + '<button class="button is-small" type="button" onclick="window.addItem(`subItem' + window.cartItems + '`, ' + (depth + 1) + ', `' + parent + '[' + window.cartItems + '][children]`)">' + '<span class="icon is-small">' + '<img src="/img/icons/plus.svg">' + '</span>' + '</button>' + '<button class="button is-small" type="button" onclick="window.removeItem(`item' + window.cartItems + '`)">' + '<span class="icon is-small">' + '<img src="/img/icons/trash.svg">' + '</span>' + '</button>' + '</div>' + '</div>' + '</div>' + '<div class="subItem" id="subItem' + window.cartItems + '"></div>';
  document.getElementById($id).appendChild(div);
};

window.removeItem = function ($id) {
  document.getElementById($id).remove();
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

/***/ "./resources/js/admin/modal.js":
/*!*************************************!*\
  !*** ./resources/js/admin/modal.js ***!
  \*************************************/
/*! no static exports found */
/***/ (function(module, exports) {

window.closeModal = function () {
  document.querySelector('.modal').classList.add('modal--hidden');
};

window.confirmModal = function (title, url) {
  var modal = document.getElementById('modal-confirm');
  document.getElementById('modal-title').innerText = title;
  document.getElementById('modal-form').action = url;
  modal.classList.remove('modal--hidden');
};

/***/ }),

/***/ "./resources/js/admin/status.js":
/*!**************************************!*\
  !*** ./resources/js/admin/status.js ***!
  \**************************************/
/*! no static exports found */
/***/ (function(module, exports) {

document.getElementById('payment_status').addEventListener('change', function (event) {
  if (confirm('Czy na pewno chcesz zmienić status płatności?')) {
    fetch('/admin/orders/' + window.order_id + '/status', {
      method: 'POST',
      body: JSON.stringify({
        type: 'payment',
        status: event.target.value
      }),
      headers: {
        'Content-Type': 'application/json'
      }
    });
  }
});
document.getElementById('shop_status').addEventListener('change', function (event) {
  if (confirm('Czy na pewno chcesz zmienić status zanówienia?')) {
    fetch('/admin/orders/' + window.order_id + '/status', {
      method: 'POST',
      body: JSON.stringify({
        type: 'shop',
        status: event.target.value
      }),
      headers: {
        'Content-Type': 'application/json'
      }
    });
  }
});
document.getElementById('delivery_status').addEventListener('change', function (event) {
  if (confirm('Czy na pewno chcesz zmienić status dostawy?')) {
    fetch('/admin/orders/' + window.order_id + '/status', {
      method: 'POST',
      body: JSON.stringify({
        type: 'delivery',
        status: event.target.value
      }),
      headers: {
        'Content-Type': 'application/json'
      }
    });
  }
});

/***/ }),

/***/ "./resources/sass/admin.scss":
/*!***********************************!*\
  !*** ./resources/sass/admin.scss ***!
  \***********************************/
/*! no static exports found */
/***/ (function(module, exports) {

// removed by extract-text-webpack-plugin

/***/ }),

/***/ 0:
/*!*****************************************************************!*\
  !*** multi ./resources/js/admin.js ./resources/sass/admin.scss ***!
  \*****************************************************************/
/*! no static exports found */
/***/ (function(module, exports, __webpack_require__) {

__webpack_require__(/*! /Users/jedrzej/Projekty/depth/resources/js/admin.js */"./resources/js/admin.js");
module.exports = __webpack_require__(/*! /Users/jedrzej/Projekty/depth/resources/sass/admin.scss */"./resources/sass/admin.scss");


/***/ })

/******/ });