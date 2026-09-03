;(function (window, document) {
    'use strict';

    var api = window.sfSellingApi;
    if (!api) {
        return;
    }

    function fetchJson(url) {
        return fetch(url, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        }).then(function (response) {
            if (!response.ok) {
                throw new Error('Request failed');
            }
            return response.json();
        });
    }

    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text == null ? '' : String(text);
        return div.innerHTML;
    }

    function renderPricing(plans) {
        var root = document.getElementById('sfSellPricingRoot');
        var section = document.querySelector('[data-api-section="pricing"]');
        if (!root) {
            return;
        }

        if (!plans || !plans.length) {
            if (section) {
                section.style.display = 'none';
            }
            document.querySelectorAll('[data-api-nav="pricing"]').forEach(function (el) {
                el.style.display = 'none';
            });
            return;
        }

        if (section) {
            section.style.display = '';
        }

        root.innerHTML = plans.map(function (plan) {
            var priceHtml = plan.cost === 0
                ? escapeHtml(api.labels.free)
                : escapeHtml(plan.cost_formatted) + ' <small>' + escapeHtml(api.labels.perMonth) + '</small>';

            var featuresHtml = (plan.features || []).map(function (feature) {
                return '<li><i class="fa fa-check"></i> ' + escapeHtml(feature) + '</li>';
            }).join('');

            return (
                '<article class="sf-sell-plan' + (plan.featured ? ' sf-sell-plan--featured' : '') + '">' +
                    '<h3 class="sf-sell-plan__name">' + escapeHtml(plan.name) + '</h3>' +
                    '<div class="sf-sell-plan__price">' + priceHtml + '</div>' +
                    '<p class="sf-sell-plan__for">' + escapeHtml(plan.best_for || api.labels.planTagline) + '</p>' +
                    '<ul class="sf-sell-plan__features">' + featuresHtml + '</ul>' +
                    '<a href="' + escapeHtml(plan.register_url) + '" class="sf-sell-btn sf-sell-btn--primary sf-sell-plan__action">' +
                        escapeHtml(api.labels.choosePlan) +
                    '</a>' +
                '</article>'
            );
        }).join('');
    }

    function renderFaqs(topics) {
        var root = document.getElementById('sfSellFaqsRoot');
        if (!root) {
            return;
        }

        if (!topics || !topics.length) {
            root.innerHTML = '<p class="sf-sell-section__subtitle text-center">' + escapeHtml(api.labels.noFaqs) + '</p>';
            return;
        }

        root.innerHTML = topics.map(function (topic) {
            var faqsHtml = (topic.faqs || []).map(function (faq) {
                return (
                    '<div class="sf-sell-faq-item">' +
                        '<button type="button" class="sf-sell-faq-item__question" aria-expanded="false">' +
                            '<span>' + faq.question + '</span>' +
                            '<i class="fa fa-chevron-down"></i>' +
                        '</button>' +
                        '<div class="sf-sell-faq-item__answer">' + faq.answer + '</div>' +
                    '</div>'
                );
            }).join('');

            return (
                '<div class="sf-sell-faq-topic">' +
                    '<h3 class="sf-sell-faq-topic__title">' + escapeHtml(topic.name) + '</h3>' +
                    faqsHtml +
                '</div>'
            );
        }).join('');
    }

    function updateHeroNote(config, plansPayload) {
        var note = document.getElementById('sfSellHeroNote');
        if (!note) {
            return;
        }

        if (config && config.subscription_enabled && plansPayload && plansPayload.enabled && plansPayload.data && plansPayload.data.length) {
            var minCost = config.min_plan_cost_formatted || '';
            note.innerHTML = api.labels.heroPricingNote.replace(':price', '<strong>' + escapeHtml(minCost) + '</strong>');
            return;
        }

        note.textContent = api.labels.heroFreeNote;
    }

    function showError(rootId) {
        var root = document.getElementById(rootId);
        if (root) {
            root.innerHTML = '<p class="sf-sell-api-error">' + escapeHtml(api.labels.loadError) + '</p>';
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        Promise.all([
            fetchJson(api.config),
            fetchJson(api.faqs),
            fetchJson(api.plans)
        ]).then(function (results) {
            var configPayload = results[0].data || {};
            var faqsPayload = results[1].data || [];
            var plansPayload = results[2];

            updateHeroNote(configPayload, plansPayload);
            renderPricing(plansPayload.enabled ? plansPayload.data : []);
            renderFaqs(faqsPayload);
        }).catch(function () {
            showError('sfSellPricingRoot');
            showError('sfSellFaqsRoot');
        });
    });
}(window, document));
