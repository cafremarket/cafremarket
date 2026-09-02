/**
 * Cafremarket Admin — Modern UI Enhancements
 */
(function ($) {
  'use strict';

  $(document).ready(function () {
    initSidebarSearch();
    initTreeviewEnhancements();
    initTopbarDropdowns();
    initTopbarPageSearch();
    initCollapsedSidebar();
    initMobileSidebars();
    initMerchantNavGroups();
    markActiveSidebarItems();
    initAdminCards();
  });

  function initSidebarSearch() {
    var $search = $('#sidebar-menu-search');
    if (!$search.length) return;

    $search.on('input', function () {
      var query = $(this).val().toLowerCase().trim();
      var $menu = $('.sidebar-menu');

      if (!query) {
        $menu.find('li').show();
        $menu.find('.nav-section').show();
        return;
      }

      $menu.find('.nav-section').hide();
      $menu.find('> li').each(function () {
        var $item = $(this);
        if ($item.hasClass('nav-section')) return;

        var text = $item.find('> a span').first().text().toLowerCase();
        var childMatch = false;

        $item.find('.treeview-menu li a').each(function () {
          var childText = $(this).text().toLowerCase();
          var $li = $(this).closest('li');
          if (childText.indexOf(query) !== -1) {
            childMatch = true;
            $li.show();
          } else {
            $li.hide();
          }
        });

        if (text.indexOf(query) !== -1 || childMatch) {
          $item.show();
          if (childMatch || $item.hasClass('treeview')) {
            $item.addClass('menu-open active');
            $item.find('> .treeview-menu').show();
          }
        } else {
          $item.hide();
        }
      });
    });
  }

  function initTreeviewEnhancements() {
    $('.sidebar-menu .treeview').each(function () {
      var $tree = $(this);
      if ($tree.find('.treeview-menu li.active').length) {
        $tree.addClass('menu-open active');
        $tree.find('> .treeview-menu').show();
      }
    });
  }

  function initTopbarDropdowns() {
    $(document).on('click', function (e) {
      if (!$(e.target).closest('.dropdown').length) {
        $('.navbar-custom-menu .dropdown.open').removeClass('open');
      }
    });
  }

  /** Filter visible table rows on the current page */
  function initTopbarPageSearch() {
    var $input = $('#topbar-page-search');
    var $clear = $('#topbar-search-clear');
    var $wrap = $('.topbar-search-wrap');
    if (!$input.length) return;

    function filterRows() {
      var query = $input.val().toLowerCase().trim();
      $wrap.toggleClass('has-value', query.length > 0);

      $('.admin-content__body table tbody tr').each(function () {
        var text = $(this).text().toLowerCase();
        $(this).toggle(text.indexOf(query) !== -1);
      });
    }

    $input.on('input', filterRows);
    $clear.on('click', function () {
      $input.val('').trigger('input').focus();
    });
  }

  function initCollapsedSidebar() {
    var $body = $('body');
    if (!$body.hasClass('sidebar-mini')) return;

    $('.sidebar-menu > li > a').each(function () {
      var label = $(this).find('> a span').first().text().trim() || $(this).find('> span').first().text().trim();
      if (label) $(this).attr('title', label);
    });

    $('.sidebar-toggle').on('click', function () {
      setTimeout(function () {
        if ($body.hasClass('sidebar-collapse')) {
          $('.main-sidebar, .sidebar').css('overflow', 'visible');
        }
      }, 350);
    });
  }

  function initMobileSidebars() {
    var $adminOverlay = $('#admin-sidebar-overlay');
    var $body = $('body');

    function setAdminSidebarOpen(open) {
      $body.toggleClass('sidebar-open', open);
      if ($adminOverlay.length) {
        $adminOverlay.prop('hidden', !open).attr('aria-hidden', open ? 'false' : 'true');
      }
      document.body.style.overflow = open ? 'hidden' : '';
    }

    if ($adminOverlay.length) {
      $('.sidebar-toggle').on('click', function () {
        setTimeout(function () {
          setAdminSidebarOpen($body.hasClass('sidebar-open'));
        }, 0);
      });

      $adminOverlay.on('click', function () {
        setAdminSidebarOpen(false);
      });

      $(document).on('keyup', function (e) {
        if (e.key === 'Escape' && $body.hasClass('sidebar-open')) {
          setAdminSidebarOpen(false);
        }
      });
    }

    var $mpToggle = $('#mp-sidebar-toggle');
    var $mpOverlay = $('#mp-sidebar-overlay');
    var $mpSidebar = $('#mp-sidebar');

    if (!$mpToggle.length || !$mpSidebar.length) return;

    function setMerchantSidebarOpen(open) {
      document.body.classList.toggle('mp-sidebar-open', open);
      $mpToggle.attr('aria-expanded', open ? 'true' : 'false');
      if ($mpOverlay.length) {
        $mpOverlay.prop('hidden', !open).attr('aria-hidden', open ? 'false' : 'true');
      }
      document.body.style.overflow = open ? 'hidden' : '';
    }

    $mpToggle.on('click', function () {
      setMerchantSidebarOpen(!document.body.classList.contains('mp-sidebar-open'));
    });

    if ($mpOverlay.length) {
      $mpOverlay.on('click', function () {
        setMerchantSidebarOpen(false);
      });
    }

    $mpSidebar.on('click', 'a.mp-sidebar__link', function () {
      if (window.matchMedia('(max-width: 991px)').matches) {
        setMerchantSidebarOpen(false);
      }
    });

    $(document).on('keyup', function (e) {
      if (e.key === 'Escape' && document.body.classList.contains('mp-sidebar-open')) {
        setMerchantSidebarOpen(false);
      }
    });
  }

  function initMerchantNavGroups() {
    document.querySelectorAll('.mp-nav-group__toggle').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var group = btn.closest('.mp-nav-group');
        if (group) {
          group.classList.toggle('is-open');
        }
      });
    });
  }

  function markActiveSidebarItems() {
    $('.sidebar-menu li.active > a').attr('aria-current', 'page');
  }

  /** Enhance card collapse buttons */
  function initAdminCards() {
    $(document).on('click', '.admin-card__collapse-btn', function () {
      var $icon = $(this).find('i');
      setTimeout(function () {
        $icon.toggleClass('fa-plus fa-minus');
      }, 150);
    });
  }

})(jQuery);
