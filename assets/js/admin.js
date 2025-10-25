/**
 * Admin JavaScript for Odds Comparison
 *
 * @package OddsComparison
 * @since 1.0.0
 */

(function ($) {
    'use strict';

    const OddsComparisonAdmin = {
        /**
         * Initialize admin functionality.
         */
        init: function () {
            this.initColorPickers();
            this.initTooltips();
            this.initConfirmations();
        },

        /**
         * Initialize color pickers.
         */
        initColorPickers: function () {
            if ($.fn.wpColorPicker) {
                $('.color-picker').wpColorPicker();
            }
        },

        /**
         * Initialize tooltips.
         */
        initTooltips: function () {
            $('[data-tooltip]').each(function () {
                $(this).attr('title', $(this).data('tooltip'));
            });
        },

        /**
         * Initialize confirmation dialogs.
         */
        initConfirmations: function () {
            $('.needs-confirmation').on('click', function (e) {
                const message = $(this).data('confirm-message') || 
                               'Are you sure you want to perform this action?';
                
                if (!confirm(message)) {
                    e.preventDefault();
                    return false;
                }
            });
        },
    };

    /**
     * Initialize on document ready.
     */
    $(document).ready(function () {
        OddsComparisonAdmin.init();
    });

})(jQuery);





