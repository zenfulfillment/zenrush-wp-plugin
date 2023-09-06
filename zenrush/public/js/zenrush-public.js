(function ($) {
    'use strict';

    /**
     * Zenrush styling for checkout pages
     */

    if (!$) {
        return;
    }

    const IMG_SRC = 'https://public.zenfulfillment.com/zenrush/zenrush-logo.svg';

    function isCheckoutPage() {
        return $('form[name="checkout"]').length > 0;
    }

    function addHeadStyles() {
        if ($("style[id='zenrush-checkout']").length < 1) {
            const fontSize = parseInt($("body").css("font-size"), 10) || 16;
            const logoMaxHeight = fontSize - 2;
            $('head').append(`<style type="text/css" id="zenrush-checkout">#zenrush_wrapper{font-size: clamp(12px, 1em, 16px)} #zenrush_wrapper .zr-logo{max-height: ${logoMaxHeight}px;padding-right: 10px;float: left;width: auto;}</style>`);
        }
    }

    function updateZenrushLabel() {
        const zenrushLabel = $('label[for*="zenrush_premiumversand"]');

        if (zenrushLabel) {
            const $img = $('<img>', {class:'zr-logo', src: IMG_SRC, alt: "Zenrush"});
            $(zenrushLabel)
                .wrap( "<div id='zenrush_wrapper'></div>")
                .css({
                    "color": "#00b67a",
                    "font-weight": "500",
                    "margin-bottom": "0",
                    "padding-left": "25px"
                });

            const updatedLabelText = $(zenrushLabel).text().replace('Zenrush', '');
            $(zenrushLabel).text(updatedLabelText);
            $(zenrushLabel).prepend($img);
        }
    }

    $(document).ready(function() {
        const onCheckout = isCheckoutPage();
        if (onCheckout) {
            addHeadStyles();
            updateZenrushLabel();
            $(document.body).on('updated_checkout', updateZenrushLabel);
        }
    })
})(jQuery);
