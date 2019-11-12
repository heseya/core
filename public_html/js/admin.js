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

__webpack_require__(/*! ./admin/gallery.js */ "./resources/js/admin/gallery.js");

__webpack_require__(/*! ./admin/status.js */ "./resources/js/admin/status.js");

__webpack_require__(/*! ./admin/dark.js */ "./resources/js/admin/dark.js");

window.toBottom = function () {
  window.scrollTo(0, document.body.scrollHeight);
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

/***/ "./resources/js/admin/gallery.js":
/*!***************************************!*\
  !*** ./resources/js/admin/gallery.js ***!
  \***************************************/
/*! no static exports found */
/***/ (function(module, exports) {

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

var Tabs =
/*#__PURE__*/
function () {
  function Tabs(body) {
    _classCallCheck(this, Tabs);

    this.tabs = [];
    this.currentTab = null;
    if (!body) this.dom = document.createElement('div');else if (typeof body == 'string') this.dom = document.querySelector(body);else this.dom = body;
    this.dom.classList.add('tabs');
    this.initTabs();
  }

  _createClass(Tabs, [{
    key: "initTabs",
    value: function initTabs() {
      var _this = this;

      this.updateTabs();

      this.dom.ondragover = function (ev) {
        ev.preventDefault();
        ev.dataTransfer.dropEffect = 'move';
      };

      this.dom.ondragenter = function () {
        _this.dom.classList.add('dragover');
      };

      this.dom.ondragleave = function () {
        _this.dom.classList.remove('dragover');
      };

      this.dom.ondrop = function (ev) {
        ev.preventDefault();
        if (!isNaN(ev.dataTransfer.getData('text/plain'))) _this.moveTab(ev.dataTransfer.getData('text/plain'), _this.tabs.length - 1);

        _this.dom.classList.remove('dragover');
      };
    }
  }, {
    key: "addTab",
    value: function addTab(tab) {
      var _this2 = this;

      this.tabs.push(tab);

      tab.dom.ondragstart = function (ev) {
        ev.dataTransfer.setData('text/plain', _this2.tabs.indexOf(tab).toString());
      };

      tab.dom.ondragover = function (ev) {
        ev.preventDefault();
        ev.dataTransfer.dropEffect = "move";
      };

      tab.dom.ondragenter = function (ev) {
        ev.stopPropagation();
        tab.dom.classList.add('dragover');
      };

      tab.dom.ondragleave = function (ev) {
        ev.stopPropagation();
        tab.dom.classList.remove('dragover');
      };

      tab.dom.ondrop = function (ev) {
        ev.preventDefault();
        ev.stopPropagation();

        _this2.moveTab(ev.dataTransfer.getData('text/plain'), _this2.tabs.indexOf(tab));

        tab.dom.classList.remove('dragover');
      };

      tab.dom.onmousedown = function () {
        _this2.selectTab(tab);
      };

      if (!this.currentTab) this.selectTab(tab);
      this.updateTabs();
    }
  }, {
    key: "delTab",
    value: function delTab(tab) {
      this.tabs.splice(this.tabs.indexOf(tab), 1);
      this.updateTabs();
    }
  }, {
    key: "rotateTab",
    value: function rotateTab(tab) {
      tab.rotate();
    }
  }, {
    key: "moveTab",
    value: function moveTab(from, to) {
      var tab = this.tabs.splice(from, 1)[0];
      this.tabs.splice(to, 0, tab);
      this.updateTabs();
    }
  }, {
    key: "selectTab",
    value: function selectTab(tab) {
      if (this.currentTab) this.currentTab.unselect();
      tab.select();
      this.currentTab = tab;
    }
  }, {
    key: "updateTabs",
    value: function updateTabs() {
      while (this.dom.firstChild) {
        this.dom.removeChild(this.dom.firstChild);
      }

      var _iteratorNormalCompletion = true;
      var _didIteratorError = false;
      var _iteratorError = undefined;

      try {
        for (var _iterator = this.tabs[Symbol.iterator](), _step; !(_iteratorNormalCompletion = (_step = _iterator.next()).done); _iteratorNormalCompletion = true) {
          var tab = _step.value;
          this.dom.appendChild(tab.dom);
        }
      } catch (err) {
        _didIteratorError = true;
        _iteratorError = err;
      } finally {
        try {
          if (!_iteratorNormalCompletion && _iterator["return"] != null) {
            _iterator["return"]();
          }
        } finally {
          if (_didIteratorError) {
            throw _iteratorError;
          }
        }
      }
    }
  }]);

  return Tabs;
}();

var tabs = new Tabs('#tabs');
window.oldpictures = [];
var _iteratorNormalCompletion2 = true;
var _didIteratorError2 = false;
var _iteratorError2 = undefined;

try {
  var _loop = function _loop() {
    var id = _step2.value;
    var preview = document.createElement('div');
    preview.className = 'gallery__img';
    preview.innerHTML = '<img src="/img/icons/camera.svg" />';
    preview.children[0].src = "/img/thumbnails/".concat(id, ".jpeg?") + Math.random();
    var input = document.createElement('input');
    input.name = 'photos[]';
    input.type = 'hidden';
    input.value = id;
    preview.appendChild(input);
    var tab = new Tab(preview, id);
    var del = document.createElement('div');
    del.className = 'remove';

    del.onclick = function () {
      tabs.delTab(tab);
    };

    var rotate = document.createElement('div');
    rotate.className = 'rotate';

    rotate.onclick = function () {
      tabs.rotateTab(tab);
    };

    preview.appendChild(rotate);
    preview.appendChild(del);
    tabs.addTab(tab);
  };

  for (var _iterator2 = window.oldpictures[Symbol.iterator](), _step2; !(_iteratorNormalCompletion2 = (_step2 = _iterator2.next()).done); _iteratorNormalCompletion2 = true) {
    _loop();
  }
} catch (err) {
  _didIteratorError2 = true;
  _iteratorError2 = err;
} finally {
  try {
    if (!_iteratorNormalCompletion2 && _iterator2["return"] != null) {
      _iterator2["return"]();
    }
  } finally {
    if (_didIteratorError2) {
      throw _iteratorError2;
    }
  }
}

window.addPicture = function (container) {
  var preview = document.createElement('div');
  preview.className = 'gallery__img add';
  preview.innerHTML = '<img src="/img/icons/camera.svg" />';
  var input = document.createElement('input');
  input.type = 'file';
  input.accept = 'image/x-png,image/jpeg';
  var id = document.createElement('input');
  id.name = 'photos[]';
  id.type = 'hidden';
  preview.appendChild(id);
  container.appendChild(preview);

  preview.onclick = function () {
    return input.click();
  };

  preview.ondragover = function (ev) {
    ev.preventDefault();
    ev.dataTransfer.dropEffect = 'copy';
  };

  preview.ondrop = function (ev) {
    ev.preventDefault();
    ev.stopPropagation();
    if (ev.dataTransfer.files.length == 1) input.files = ev.dataTransfer.files;
  };

  input.onchange = function () {
    preview.classList.remove('add');
    var reader = new FileReader();
    reader.readAsDataURL(input.files[0]);
    preview.children[0].src = 'data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==';

    reader.onload = function (event) {
      preview.classList.add('loading');
      preview.children[0].src = event.target.result;
    };

    var data = new FormData();
    data.append('photo', input.files[0]);
    var tab = new Tab(preview, 0);
    fetch('/api/admin/upload', {
      method: 'POST',
      body: data,
      credentials: 'same-origin'
    }).then(function (response) {
      return response.text();
    }).then(function (text) {
      console.log(text);
      tab.id = text;
      id.value = text;
      preview.classList.remove('loading');
    });
    var del = document.createElement('div');
    del.className = 'remove';

    del.onclick = function () {
      tabs.delTab(tab);
    };

    var rotate = document.createElement('div');
    rotate.className = 'rotate';

    rotate.onclick = function () {
      tabs.rotateTab(tab);
    };

    preview.onclick = null;
    preview.ondrop = null;
    preview.appendChild(del);
    preview.appendChild(rotate);
    tabs.addTab(tab);
    addPicture(container);
  };
};

addPicture(document.getElementById('tabs'));

var Tab =
/*#__PURE__*/
function () {
  function Tab(content, id, callback) {
    _classCallCheck(this, Tab);

    this.id = id;
    this.dom = content;
    this.dom.classList.add('tab');
    this.dom.draggable = true;
    this.callback = callback;
    this.rotateState = 0;
  }

  _createClass(Tab, [{
    key: "select",
    value: function select() {
      this.dom.classList.add('active');
      if (this.callback) this.callback();
    }
  }, {
    key: "unselect",
    value: function unselect() {
      this.dom.classList.remove('active');
    }
  }, {
    key: "rotate",
    value: function rotate() {
      this.dom.classList.remove('rotated-' + this.rotateState);
      this.rotateState = this.rotateState == 3 ? 0 : this.rotateState + 1;
      this.dom.classList.add('rotated-' + this.rotateState); // console.log(this.id)

      var data = new FormData();
      data.append('id', this.id);
      data.append('pos', this.rotateState);
      fetch('/panel/save/photo-pos.php', {
        method: 'POST',
        body: data,
        credentials: 'same-origin'
      }).then(function (response) {
        return response.text();
      }).then(function (text) {
        console.log(text);
      });
    }
  }]);

  return Tab;
}();

/***/ }),

/***/ "./resources/js/admin/modal.js":
/*!*************************************!*\
  !*** ./resources/js/admin/modal.js ***!
  \*************************************/
/*! no static exports found */
/***/ (function(module, exports) {

window.closeModal = function () {
  document.getElementById('modal').classList.add('modal--hidden');
};

window.confirmModal = function (title) {
  var modal = document.getElementById('modal');
  document.getElementById('modal__title').innerText = title;
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
    fetch('/api/admin/status', {
      method: 'POST',
      // or 'PUT'
      body: JSON.stringify({
        order_id: window.order_id,
        type: 'payment',
        status: event.target.value
      }),
      // data can be `string` or {object}!
      headers: {
        'Content-Type': 'application/json'
      }
    });
  }
});
document.getElementById('shop_status').addEventListener('change', function (event) {
  if (confirm('Czy na pewno chcesz zmienić status zanówienia?')) {
    fetch('/api/admin/status', {
      method: 'POST',
      // or 'PUT'
      body: JSON.stringify({
        order_id: window.order_id,
        type: 'shop',
        status: event.target.value
      }),
      // data can be `string` or {object}!
      headers: {
        'Content-Type': 'application/json'
      }
    });
  }
});
document.getElementById('delivery_status').addEventListener('change', function (event) {
  if (confirm('Czy na pewno chcesz zmienić status dostawy?')) {
    fetch('/api/admin/status', {
      method: 'POST',
      // or 'PUT'
      body: JSON.stringify({
        order_id: window.order_id,
        type: 'delivery',
        status: event.target.value
      }),
      // data can be `string` or {object}!
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