"use strict";
window.isRtl = window.Helpers.isRtl(), window.isDarkStyle = window.Helpers.isDarkStyle();
let menu, animate, isHorizontalLayout = !1;
document.getElementById("layout-menu") && (isHorizontalLayout = document.getElementById("layout-menu").classList.contains("menu-horizontal")), function () {
  function e() {
    var e = document.querySelector(".layout-page");
    e && (0 < window.pageYOffset ? e.classList.add("window-scrolled") : e.classList.remove("window-scrolled"))
  }

  "undefined" != typeof Waves && (Waves.init(), Waves.attach(".btn[class*='btn-']:not(.position-relative):not([class*='btn-outline-']):not([class*='btn-label-'])", ["waves-light"]), Waves.attach("[class*='btn-outline-']:not(.position-relative)"), Waves.attach("[class*='btn-label-']:not(.position-relative)"), Waves.attach(".pagination .page-item .page-link"), Waves.attach(".dropdown-menu .dropdown-item"), Waves.attach(".light-style .list-group .list-group-item-action"), Waves.attach(".dark-style .list-group .list-group-item-action", ["waves-light"]), Waves.attach(".nav-tabs:not(.nav-tabs-widget) .nav-item .nav-link"), Waves.attach(".nav-pills .nav-item .nav-link", ["waves-light"]), Waves.attach(".menu-vertical .menu-item .menu-link.menu-toggle")), setTimeout(() => {
    e()
  }, 200), window.onscroll = function () {
    e()
  }, setTimeout(function () {
    window.Helpers.initCustomOptionCheck()
  }, 1e3), document.querySelectorAll("#layout-menu").forEach(function (e) {
    menu = new Menu(e, {
      orientation: isHorizontalLayout ? "horizontal" : "vertical",
      closeChildren: !!isHorizontalLayout,
      showDropdownOnHover: localStorage.getItem("templateCustomizer-" + templateName + "--ShowDropdownOnHover") ? "true" === localStorage.getItem("templateCustomizer-" + templateName + "--ShowDropdownOnHover") : void 0 === window.templateCustomizer || window.templateCustomizer.settings.defaultShowDropdownOnHover
    }), window.Helpers.scrollToActive(animate = !1), window.Helpers.mainMenu = menu
  }), document.querySelectorAll(".layout-menu-toggle").forEach(e => {
    e.addEventListener("click", e => {
      if (e.preventDefault(), window.Helpers.toggleCollapsed(), config.enableMenuLocalStorage && !window.Helpers.isSmallScreen()) try {
        localStorage.setItem("templateCustomizer-" + templateName + "--LayoutCollapsed", String(window.Helpers.isCollapsed()));
        var t, a = document.querySelector(".template-customizer-layouts-options");
        a && (t = window.Helpers.isCollapsed() ? "collapsed" : "expanded", a.querySelector(`input[value="${t}"]`).click())
      } catch (e) {
      }
    })
  }), window.Helpers.swipeIn(".drag-target", function (e) {
    window.Helpers.setCollapsed(!1)
  }), window.Helpers.swipeOut("#layout-menu", function (e) {
    window.Helpers.isSmallScreen() && window.Helpers.setCollapsed(!0)
  });
  let t = document.getElementsByClassName("menu-inner"), a = document.getElementsByClassName("menu-inner-shadow")[0];
  0 < t.length && a && t[0].addEventListener("ps-scroll-y", function () {
    this.querySelector(".ps__thumb-y").offsetTop ? a.style.display = "block" : a.style.display = "none"
  });
  var n = document.querySelector(".dropdown-style-switcher");
  const o = document.documentElement.getAttribute("data-style");
  var s,
      i = localStorage.getItem("templateCustomizer-" + templateName + "--Style") || (window.templateCustomizer?.settings?.defaultStyle ?? "light"),
      n = (window.templateCustomizer && n && ([].slice.call(n.children[1].querySelectorAll(".dropdown-item")).forEach(function (e) {
        e.classList.remove("active"), e.addEventListener("click", function () {
          var e = this.getAttribute("data-theme");
          "light" === e ? window.templateCustomizer.setStyle("light") : "dark" === e ? window.templateCustomizer.setStyle("dark") : window.templateCustomizer.setStyle("system")
        }), e.getAttribute("data-theme") === o && e.classList.add("active")
      }), n = n.querySelector("i"), "light" === i ? (n.classList.add("ri-sun-line"), new bootstrap.Tooltip(n, {
        title: "Mode Terang",
        fallbackPlacements: ["bottom"]
      })) : "dark" === i ? (n.classList.add("ri-moon-clear-line"), new bootstrap.Tooltip(n, {
        title: "Mode Gelap",
        fallbackPlacements: ["bottom"]
      })) : (n.classList.add("ri-computer-line"), new bootstrap.Tooltip(n, {
        title: "Mode Sistem",
        fallbackPlacements: ["bottom"]
      }))), "system" === (s = i) && (s = window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light"), [].slice.call(document.querySelectorAll("[data-app-" + s + "-img]")).map(function (e) {
        var t = e.getAttribute("data-app-" + s + "-img");
        e.src = assetsPath + "img/" + t
      }), "undefined" != typeof i18next && "undefined" != typeof i18NextHttpBackend && i18next.use(i18NextHttpBackend).init({
        lng: window.templateCustomizer ? window.templateCustomizer.settings.lang : "en",
        debug: !1,
        fallbackLng: "en",
        backend: {loadPath: assetsPath + "json/locales/{{lng}}.json"},
        returnObjects: !0
      }).then(function (e) {
        r()
      }), document.getElementsByClassName("dropdown-language"));
  if (n.length) {
    var l = n[0].querySelectorAll(".dropdown-item");
    for (let e = 0; e < l.length; e++) l[e].addEventListener("click", function () {
      let a = this.getAttribute("data-language"), n = this.getAttribute("data-text-direction");
      for (var e of this.parentNode.children) for (var t = e.parentElement.parentNode.firstChild; t;) 1 === t.nodeType && t !== t.parentElement && t.querySelector(".dropdown-item").classList.remove("active"), t = t.nextSibling;
      this.classList.add("active"), i18next.changeLanguage(a, (e, t) => {
        if (window.templateCustomizer && window.templateCustomizer.setLang(a), "rtl" === n ? "true" !== localStorage.getItem("templateCustomizer-" + templateName + "--Rtl") && window.templateCustomizer && window.templateCustomizer.setRtl(!0) : "true" === localStorage.getItem("templateCustomizer-" + templateName + "--Rtl") && window.templateCustomizer && window.templateCustomizer.setRtl(!1), e) return console.log("something went wrong loading", e);
        r()
      })
    })
  }

  function r() {
    var e = document.querySelectorAll("[data-i18n]"),
        t = document.querySelector('.dropdown-item[data-language="' + i18next.language + '"]');
    t && t.click(), e.forEach(function (e) {
      e.innerHTML = i18next.t(e.dataset.i18n)
    })
  }

  i = document.querySelector(".dropdown-notifications-all");

  function d(e) {
    "show.bs.collapse" == e.type || "show.bs.collapse" == e.type ? (e.target.closest(".accordion-item").classList.add("active"), e.target.closest(".accordion-item").previousElementSibling?.classList.add("previous-active")) : (e.target.closest(".accordion-item").classList.remove("active"), e.target.closest(".accordion-item").previousElementSibling?.classList.remove("previous-active"))
  }

  const c = document.querySelectorAll(".dropdown-notifications-read");
  i && i.addEventListener("click", e => {
    c.forEach(e => {
      e.closest(".dropdown-notifications-item").classList.add("marked-as-read")
    })
  }), c && c.forEach(t => {
    t.addEventListener("click", e => {
      t.closest(".dropdown-notifications-item").classList.toggle("marked-as-read")
    })
  }), document.querySelectorAll(".dropdown-notifications-archive").forEach(t => {
    t.addEventListener("click", e => {
      t.closest(".dropdown-notifications-item").remove()
    })
  }), [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]')).map(function (e) {
    return new bootstrap.Tooltip(e)
  });
  [].slice.call(document.querySelectorAll(".accordion")).map(function (e) {
    e.addEventListener("show.bs.collapse", d), e.addEventListener("hide.bs.collapse", d)
  });
  window.Helpers.setAutoUpdate(!0), window.Helpers.initPasswordToggle(), window.Helpers.initSpeechToText(), window.Helpers.navTabsAnimation(), window.Helpers.initNavbarDropdownScrollbar();
  let u = document.querySelector("[data-template^='horizontal-menu']");
  if (u && (window.innerWidth < window.Helpers.LAYOUT_BREAKPOINT ? window.Helpers.setNavbarFixed("fixed") : window.Helpers.setNavbarFixed("")), window.addEventListener("resize", function (e) {
    window.innerWidth >= window.Helpers.LAYOUT_BREAKPOINT && document.querySelector(".search-input-wrapper") && (document.querySelector(".search-input-wrapper").classList.add("d-none"), document.querySelector(".search-input").value = ""), u && (window.innerWidth < window.Helpers.LAYOUT_BREAKPOINT ? window.Helpers.setNavbarFixed("fixed") : window.Helpers.setNavbarFixed(""), setTimeout(function () {
      window.innerWidth < window.Helpers.LAYOUT_BREAKPOINT ? document.getElementById("layout-menu") && document.getElementById("layout-menu").classList.contains("menu-horizontal") && menu.switchMenu("vertical") : document.getElementById("layout-menu") && document.getElementById("layout-menu").classList.contains("menu-vertical") && menu.switchMenu("horizontal")
    }, 100)), window.Helpers.navTabsAnimation()
  }, !0), !isHorizontalLayout && !window.Helpers.isSmallScreen() && ("undefined" != typeof TemplateCustomizer && (window.templateCustomizer.settings.defaultMenuCollapsed ? window.Helpers.setCollapsed(!0, !1) : window.Helpers.setCollapsed(!1, !1)), "undefined" != typeof config) && config.enableMenuLocalStorage) try {
    null !== localStorage.getItem("templateCustomizer-" + templateName + "--LayoutCollapsed") && window.Helpers.setCollapsed("true" === localStorage.getItem("templateCustomizer-" + templateName + "--LayoutCollapsed"), !1)
  } catch (e) {
  }
}(), "undefined" != typeof $ && $(function () {
  window.Helpers.initSidebarToggle();
});
