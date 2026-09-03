// cafremarket Selling page JavaScript
;(function ($, window, document) {
    "use strict";

    var headerOffset = 120;

    $(document).ready(function () {
        var csrf = $('meta[name="csrf-token"]').attr('content');
        if (csrf) {
            $.ajaxSetup({
                cache: false,
                headers: {
                    'X-CSRF-TOKEN': csrf
                }
            });
        }

        var $recaptcha = document.querySelector('#g-recaptcha-response');
        if ($recaptcha) {
            $recaptcha.setAttribute('required', 'required');
        }

        initSmoothScroll();
        initSectionNav();
        initFaqAccordion();
        initMobileNav();
        initHeaderScroll();
        openHashSection();
    });

    function initSmoothScroll() {
        $(document).on('click', '.sf-sell-scroll-link', function (event) {
            var target = $(this).attr('href');

            if (!target || target.charAt(0) !== '#') {
                return;
            }

            var $target = $(target);
            if (!$target.length) {
                return;
            }

            event.preventDefault();

            $('html, body').animate({
                scrollTop: $target.offset().top - headerOffset + 1
            }, 500);

            if (history.replaceState) {
                history.replaceState(null, null, target);
            } else {
                window.location.hash = target;
            }

            setActiveSectionLink(target);
            closeMobileNav();
        });
    }

    function initSectionNav() {
        var $links = $('.sf-sell-section-nav__link, .sf-sell-header__nav .sf-sell-scroll-link');
        var sections = [];

        $links.each(function () {
            var href = $(this).attr('href');
            if (href && href.charAt(0) === '#') {
                var $section = $(href);
                if ($section.length) {
                    sections.push({ id: href, $el: $section });
                }
            }
        });

        if (!sections.length) {
            return;
        }

        $(window).on('scroll', function () {
            var scrollPos = $(window).scrollTop() + headerOffset + 20;
            var current = sections[0].id;

            sections.forEach(function (section) {
                if (section.$el.offset().top <= scrollPos) {
                    current = section.id;
                }
            });

            setActiveSectionLink(current);
        });
    }

    function setActiveSectionLink(hash) {
        $('.sf-sell-section-nav__link, .sf-sell-header__nav .sf-sell-scroll-link').removeClass('is-active');
        $('.sf-sell-section-nav__link[href="' + hash + '"], .sf-sell-header__nav .sf-sell-scroll-link[href="' + hash + '"]').addClass('is-active');
    }

    function openHashSection() {
        var hash = window.location.hash;
        if (hash && $(hash).length) {
            setTimeout(function () {
                $('html, body').scrollTop($(hash).offset().top - headerOffset + 1);
                setActiveSectionLink(hash);
            }, 100);
        }
    }

    function initFaqAccordion() {
        $(document).on('click', '.sf-sell-faq-item__question', function () {
            var $item = $(this).closest('.sf-sell-faq-item');
            var isOpen = $item.hasClass('is-open');

            $item.siblings('.sf-sell-faq-item').removeClass('is-open');
            $item.toggleClass('is-open', !isOpen);
            $(this).attr('aria-expanded', !isOpen);
        });
    }

    function initMobileNav() {
        $('#sfSellNavToggle').on('click', function () {
            $('#sfSellMobileNav').toggleClass('is-open');
        });

        $('#sfSellMobileNav').on('click', function (event) {
            if (event.target === this) {
                closeMobileNav();
            }
        });
    }

    function closeMobileNav() {
        $('#sfSellMobileNav').removeClass('is-open');
    }

    function initHeaderScroll() {
        var $header = $('#sfSellHeader');

        $(window).on('scroll', function () {
            $header.toggleClass('is-scrolled', $(window).scrollTop() > 12);
        });
    }

    if ($.fn.jqBootstrapValidation) {
        $("#contactForm input, #contactForm textarea").jqBootstrapValidation({
            preventSubmit: true,
            submitSuccess: function ($form, event) {
                event.preventDefault();

                var form = $("form#contactForm");
                var formData = new FormData(form[0]);
                var submitBtn = form.find("button[type=submit]");
                submitBtn.prop("disabled", true);

                $.ajax({
                    url: form.attr("action"),
                    type: "POST",
                    data: formData,
                    cache: false,
                    processData: false,
                    contentType: false,
                    success: function (response) {
                        $('#success').html("<div class='alert alert-success'><strong>" + response + "</strong></div>");
                    },
                    error: function (response, textStatus, xhr) {
                        if (xhr.status === 422 && response.responseJSON && response.responseJSON.errors) {
                            var errors = response.responseJSON.errors;
                            var errorsHtml = '<ul>';
                            $.each(errors, function (key, value) {
                                errorsHtml += '<li>' + value[0] + '</li>';
                            });
                            errorsHtml += '</ul>';
                            $('#success').html("<div class='alert alert-danger'>" + errorsHtml + "</div>");
                        } else {
                            $('#success').html("<div class='alert alert-danger'><strong>" + (response.responseText || 'Something went wrong.') + "</strong></div>");
                        }
                    },
                    complete: function () {
                        form.trigger("reset");
                        setTimeout(function () {
                            submitBtn.prop("disabled", false);
                        }, 1000);
                    }
                });
            },
            filter: function () {
                return $(this).is(":visible");
            }
        });
    }

    $('#name').on('focus', function () {
        $('#success').html('');
    });

}(window.jQuery, window, document));
