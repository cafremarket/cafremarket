@if (get_popup_data()['type'] != 'none')
    @include('DynamicPopup::styles') <!-- css -->
    <!-- New Modal-->
    <div id="zcart-popup-modal" class="modal fade">
        <div id="newsletter-popup" class="modal-dialog">
            <div id="newsletter-popup-content" class="modal-content p-0 m-0">
                <button type="button" class="close-btn" data-dismiss="modal" aria-hidden="false"> <span>&times;</span></button>
                @if (get_popup_data()['type'] == 'newsletter')
                <div id="newsletter-popup-body" class="modal-body row">
                    <div id="newsletter-popup-left-container" class="col-12 col-sm-5">
                        <img class="img-cover" src="{{ get_popup_data()['background_img'] }}" width="100%" height="100%" alt="example of a banner" />
                    </div>
                    <div  id="newsletter-popup-right-container" class="col-12 col-sm-7">
                        <div class="text-container">
                            <h4>
                                {{ trans('DynamicPopup::lang.newsletter_header1') }}<br>
                                {{ trans('DynamicPopup::lang.newsletter_header2') }}
                            </h4>
                            <p class="subtext">{{ trans('DynamicPopup::lang.newsletter_description') }}</p>
                        </div>
                        <div id="subscribe_form">
                                <input id="email" type="email" placeholder="Email" />
                                <button id="subscribe_btn">Subscribe</button>
                        </div>
                        <div id="newsletter-popup-hide" class="form-check">
                            <input class="form-check-input icheckbox_minimal-blue" type="checkbox" value="hide" id="js-hide-newsletter-check">
                            <label class="form-check-label" for="js-hide-newsletter-check">
                                {{ trans('theme.dont_show') }}
                            </label>
                        </div>
                    </div>
                </div>
                @elseif(get_popup_data()['type'] == 'banner')
                <div id="newsletter-popup-banner">
                    <img src="{{ get_popup_data()['background_img'] }}" alt="Popup Banner">
                </div>
                <div id="newsletter-popup-banner-hide" class="form-check">
                    <input class="form-check-input icheckbox_minimal-blue" type="checkbox" value="hide" id="js-hide-newsletter-check">
                    <label class="form-check-label mt-0" for="js-hide-newsletter-check">
                        {{ trans('theme.dont_show') }}
                    </label>
                </div>
                @endif
            </div>
        </div>
    </div> <!--/. Modal End-->
@endif
