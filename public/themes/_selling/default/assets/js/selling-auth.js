;(function ($, window, document) {
    'use strict';

    var authApi = window.sfSellingAuth;
    if (!authApi) {
        return;
    }

    function showAlert($box, message, type) {
        $box.html('<div class="sf-sell-alert sf-sell-alert--danger" role="alert"><strong>Error!</strong> ' + message + '</div>');
    }

    function clearFieldErrors($form) {
        $form.find('.sf-sell-form-group--invalid').removeClass('sf-sell-form-group--invalid');
        $form.find('.is-invalid').removeClass('is-invalid').attr('aria-invalid', 'false');
        $form.find('.sf-sell-field-error--ajax').remove();
    }

    function fieldSelector(field) {
        var escaped = field.replace(/([!"#$%&'()*+,./:;<=>?@[\\\]^`{|}~])/g, '\\$1');
        return '[name="' + escaped + '"], #' + escaped.replace(/\./g, '\\.');
    }

    function showFieldErrors($form, errors) {
        clearFieldErrors($form);

        var $alert = $('#sfSellAuthAlert');
        var hasFieldErrors = false;

        $.each(errors, function (field, messages) {
            if (!messages || !messages.length) {
                return;
            }

            hasFieldErrors = true;
            var message = messages[0];
            var $input = $form.find(fieldSelector(field)).first();
            var $group = $input.closest('.sf-sell-form-group');

            if (!$group.length) {
                $group = $input.parent();
            }

            $group.addClass('sf-sell-form-group--invalid');
            $input.addClass('is-invalid').attr('aria-invalid', 'true');

            if (!$group.find('.sf-sell-field-error--ajax').length) {
                $group.append(
                    '<p class="sf-sell-field-error sf-sell-field-error--ajax" role="alert">' +
                    '<i class="fa fa-exclamation-circle" aria-hidden="true"></i> ' +
                    $('<span/>').text(message).html() +
                    '</p>'
                );
            }
        });

        if (hasFieldErrors) {
            showAlert($alert, authApi.fixFieldsMsg || 'Please fix the highlighted fields below.', 'danger');
            scrollToFirstInvalid($form);
        }
    }

    function scrollToFirstInvalid($scope) {
        var $first = ($scope || $(document)).find('.sf-sell-form-group--invalid').first();
        if ($first.length) {
            $('html, body').animate({
                scrollTop: Math.max($first.offset().top - 120, 0)
            }, 300);
            $first.find('input, select, textarea').filter(':visible').first().trigger('focus');
        }
    }

    function bindFieldClearOnInput() {
        $(document).on('input change', '.sf-sell-auth__form input, .sf-sell-auth__form select, .sf-sell-auth__form textarea', function () {
            var $input = $(this);
            var $group = $input.closest('.sf-sell-form-group');

            $input.removeClass('is-invalid').attr('aria-invalid', 'false');
            $group.removeClass('sf-sell-form-group--invalid');
            $group.find('.sf-sell-field-error--ajax').remove();
        });
    }

    function loadRegisterPlans() {
        var $select = $('#plans');
        if (!$select.length || !authApi.plansUrl) {
            return;
        }

        var existingOptions = $select.find('option').length;

        $.getJSON(authApi.plansUrl).done(function (response) {
            if (!response.enabled || !response.data || !response.data.length) {
                return;
            }

            var selected = authApi.selectedPlan || $select.val();
            $select.empty();

            response.data.forEach(function (plan) {
                var label = plan.name;
                if (plan.cost > 0) {
                    label += ' — ' + plan.cost_formatted + authApi.perMonthLabel;
                } else {
                    label += ' — ' + authApi.freeLabel;
                }

                $select.append($('<option>', {
                    value: plan.plan_id,
                    text: label,
                    selected: selected && selected === plan.plan_id
                }));
            });

            if (!$select.val() && response.data.length) {
                $select.val(response.data[0].plan_id);
            }
        }).fail(function () {
            if (!existingOptions) {
                showAlert($('#sfSellAuthAlert'), authApi.loadError, 'danger');
            }
        });
    }

    function bindSellerLogin() {
        var $form = $('#seller-login-form');
        if (!$form.length) {
            return;
        }

        $form.on('submit', function (event) {
            event.preventDefault();

            var $btn = $form.find('button[type=submit], input[type=submit]');
            var $alert = $('#sfSellAuthAlert');
            clearFieldErrors($form);
            $alert.empty();
            $btn.prop('disabled', true);

            $.ajax({
                url: $form.attr('action'),
                method: 'POST',
                data: $form.serialize(),
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            }).done(function (response) {
                window.location.href = response.redirect || authApi.dashboardUrl;
            }).fail(function (xhr) {
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    showFieldErrors($form, xhr.responseJSON.errors);
                    return;
                }

                var message = authApi.loginError;
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
                showAlert($alert, message, 'danger');
            }).always(function () {
                $btn.prop('disabled', false);
            });
        });
    }

    $(document).ready(function () {
        loadRegisterPlans();
        bindSellerLogin();
        bindFieldClearOnInput();

        if ($('.sf-sell-form-group--invalid').length) {
            scrollToFirstInvalid($('.sf-sell-auth__form'));
        }
    });
}(window.jQuery, window, document));
