/**
 * @file
 * The Lazy-load behavior.
 */

(function (Drupal) {

  'use strict';

  Drupal.behaviors.lazy = {
    attach: function (context, settings) {
      var utils = {
        extend: function (obj, src) {
          Object.keys(src).forEach(function (key) {
            obj[key] = src[key];
          });
          return obj;
        },
        hasClass: function (el, className) {
          return el.classList ? el.classList.contains(className) : new RegExp('\\b' + className + '\\b').test(el.className);
        },
        addClass: function (el, className) {
          if (el.classList) {
            el.classList.add(className);
          }
          else if (!hasClass(el, className)) {
            el.className += ' ' + className;
          }
        },
        removeClass: function (el, className) {
          if (el.classList) {
            el.classList.remove(className);
          }
          else {
            el.className = el.className.replace(new RegExp('\\b' + className + '\\b', 'g'), '');
          }
        },
        once: function (selector, context) {
          return (context || document).querySelector(selector);
        },
        loadScript: function (url) {
          var script = document.createElement('script'),
            scripts = document.getElementsByTagName('script')[0];
          script.src = url;
          script.async = true;
          scripts.parentNode.insertBefore(script, scripts);
        }
      };

      if (utils.once('body', context)) {
        var lazysizes = settings.lazy.lazysizes || {};

        if (('loading' in HTMLImageElement.prototype) && settings.lazy.preferNative) {
          // Lazy-loading is natively supported and preferred.
          var element = context.querySelectorAll('[loading="lazy"]');
          element.forEach(function (el) {
            if (el.hasAttribute(lazysizes.srcAttr)) {
              el.src = el.getAttribute(lazysizes.srcAttr);
              el.removeAttribute(lazysizes.srcAttr);
            }
            if (el.hasAttribute(lazysizes.srcsetAttr)) {
              el.srcset = el.getAttribute(lazysizes.srcsetAttr);
              el.removeAttribute(lazysizes.srcsetAttr);
            }
            if (el.hasAttribute(lazysizes.sizesAttr)) {
              el.sizes = el.getAttribute(lazysizes.sizesAttr);
              el.removeAttribute(lazysizes.sizesAttr);
            }
            utils.removeClass(el, lazysizes.lazyClass);
            utils.addClass(el, lazysizes.loadedClass);

            // If parent element is a <picture> element, then there should be
            // <source> element(s) as well.
            var sources = el.parentNode.querySelectorAll('source');
            sources.forEach(function (source) {
              if (source.hasAttribute(lazysizes.srcsetAttr)) {
                source.srcset = source.getAttribute(lazysizes.srcsetAttr);
                source.removeAttribute(lazysizes.srcsetAttr);
              }
              if (source.hasAttribute(lazysizes.sizesAttr)) {
                source.sizes = source.getAttribute(lazysizes.sizesAttr);
                source.removeAttribute(lazysizes.sizesAttr);
              }
            });
          });
        }
        else {
          // 1. Lazysizes configuration.
          window.lazySizesConfig = window.lazySizesConfig || {};
          window.lazySizesConfig = utils.extend(window.lazySizesConfig, lazysizes);
          // 2. Load all selected lazysizes plugins.
          if (!Object.entries) {
            Object.entries = function (obj) {
              var ownProps = Object.keys(obj),
                i = ownProps.length,
                resArray = new Array(i);
              while (i--) {
                resArray[i] = [ownProps[i], obj[ownProps[i]]];
              }
              return resArray;
            };
          }
          Object.entries(lazysizes.plugins).forEach(function (path) {
            utils.loadScript(settings.path.baseUrl + 'libraries/lazysizes/plugins/' + path[1] + '.min.js');
          });
          // 3. Load the lazysizes library.
          utils.loadScript(settings.path.baseUrl + 'libraries/lazysizes/lazysizes.min.js');
        }
      }
    }
  };

})(Drupal);
