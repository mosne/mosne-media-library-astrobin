/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "@wordpress/i18n":
/*!******************************!*\
  !*** external ["wp","i18n"] ***!
  \******************************/
/***/ ((module) => {

module.exports = window["wp"]["i18n"];

/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/compat get default export */
/******/ 	(() => {
/******/ 		// getDefaultExport function for compatibility with non-harmony modules
/******/ 		__webpack_require__.n = (module) => {
/******/ 			var getter = module && module.__esModule ?
/******/ 				() => (module['default']) :
/******/ 				() => (module);
/******/ 			__webpack_require__.d(getter, { a: getter });
/******/ 			return getter;
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/define property getters */
/******/ 	(() => {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = (exports, definition) => {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	(() => {
/******/ 		__webpack_require__.o = (obj, prop) => (Object.prototype.hasOwnProperty.call(obj, prop))
/******/ 	})();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/************************************************************************/
var __webpack_exports__ = {};
// This entry needs to be wrapped in an IIFE because it needs to be isolated against other modules in the chunk.
(() => {
/*!***********************!*\
  !*** ./src/editor.js ***!
  \***********************/
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @wordpress/i18n */ "@wordpress/i18n");
/* harmony import */ var _wordpress_i18n__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__);

if (typeof wp !== "undefined") {
  // Helper functions to reduce duplication
  const createAstrobinCaption = (result, formattedDate = null) => {
    const baseCaption = `<a href="https://app.astrobin.com/i/${result.hash}" target="_blank" rel="nofollow noreferrer">${result.title}</a> ${(0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)("by", "mosne-astrobin")} <a href="https://app.astrobin.com/u/${result.user}" target="_blank" rel="nofollow noreferrer">${result.user}</a> &copy; <a href="${result.license_url}" target="_blank" rel="nofollow noreferrer">${result.license_name}</a>
    `;
    if (formattedDate) {
      return `${baseCaption}<br/><em>${(0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)("Image of the Day:", "mosne-astrobin")} ${formattedDate}</em>`;
    }
    return baseCaption;
  };
  const formatDate = dateString => {
    if (!dateString) return null;
    try {
      const dateObj = new Date(dateString);
      return dateObj.toLocaleDateString(undefined, {
        year: "numeric",
        month: "long",
        day: "numeric"
      });
    } catch (e) {
      return dateString;
    }
  };
  const mapToMediaItem = result => ({
    sourceId: result.id,
    id: undefined,
    title: result.title || "",
    hash: result.hash || "",
    caption: createAstrobinCaption(result, result.date ? formatDate(result.date) : null),
    previewUrl: result.url_regular,
    url: result.url_real || result.url_hd
  });

  // Shared fetch function to reduce code duplication
  const fetchAstrobinImages = async (query = {}, type, searchParam = "term") => {
    try {
      const defaultArgs = {
        limit: 30,
        type: type
      };
      const finalQuery = {
        ...query,
        ...defaultArgs
      };
      const mapFromInserterMediaRequest = {
        per_page: "page_size",
        search: searchParam,
        page: "page"
      };
      const url = new URL(`${mosneAstroBin.apiUrl}/search`);
      Object.entries(finalQuery).forEach(([key, value]) => {
        const queryKey = mapFromInserterMediaRequest[key] || key;
        if (value !== undefined && value !== null) {
          url.searchParams.set(queryKey, value);
        }
      });
      const logPrefix = type === "imageoftheday" ? "IOTD" : type;
      const response = await window.fetch(url, {
        credentials: "same-origin",
        headers: {
          "X-WP-Nonce": mosneAstroBin.nonce
        }
      });
      if (!response.ok) {
        console.error(`${logPrefix} API error:`, response.status, response.statusText);
        const errorText = await response.text();
        console.error("Error details:", errorText);
        return [];
      }
      const jsonResponse = await response.json();
      const results = jsonResponse.objects || [];
      if (results.length === 0) {
        console.error(`No ${logPrefix} results found`);
        return [];
      }
      return results.map(mapToMediaItem);
    } catch (error) {
      console.error(`Error fetching ${type}:`, error);
      return [];
    }
  };

  // Register media categories with less duplicate code
  const registerAstrobinCategory = (name, displayName, searchHint, fetchOptions = {}) => {
    const {
      type,
      searchParam
    } = {
      type: name.replace("astrobin-", ""),
      searchParam: "term",
      ...fetchOptions
    };
    wp.data.dispatch("core/block-editor").registerInserterMediaCategory({
      name,
      labels: {
        name: displayName,
        search_items: searchHint
      },
      mediaType: "image",
      async fetch(query = {}) {
        return fetchAstrobinImages(query, type, searchParam);
      },
      getReportUrl: ({
        sourceId
      }) => `https://www.astrobin.com/api/v1/image/${sourceId}/`,
      isExternalResource: true
    });
  };

  // Register all AstroBin media categories
  registerAstrobinCategory("astrobin-my-pictures", (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)("AstroBin | My Pictures", "mosne-astrobin"), (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)("Search by title", "mosne-astrobin"), {
    type: "my_pictures"
  });
  registerAstrobinCategory("astrobin-public-pictures", (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)("AstroBin | Public Pictures", "mosne-astrobin"), (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)("Search by title", "mosne-astrobin"), {
    type: "public_pictures"
  });
  registerAstrobinCategory("astrobin-other-user-gallery", (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)("AstroBin | By Username", "mosne-astrobin"), (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)("Search by username", "mosne-astrobin"), {
    type: "user_gallery",
    searchParam: "username"
  });
  registerAstrobinCategory("astrobin-by-subject", (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)("AstroBin | By Subject", "mosne-astrobin"), (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)("Search by subject", "mosne-astrobin"), {
    type: "by_subject"
  });
  registerAstrobinCategory("astrobin-by-hash", (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)("AstroBin | By Image Hash", "mosne-astrobin"), (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)("Search by hash (ID)", "mosne-astrobin"), {
    type: "by_hash"
  });
  registerAstrobinCategory("astrobin-image-of-the-day", (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)("AstroBin | Image of the Day", "mosne-astrobin"), (0,_wordpress_i18n__WEBPACK_IMPORTED_MODULE_0__.__)("Browse past images", "mosne-astrobin"), {
    type: "imageoftheday"
  });
}
})();

/******/ })()
;
//# sourceMappingURL=editor.js.map