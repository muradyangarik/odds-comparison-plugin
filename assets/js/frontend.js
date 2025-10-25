/**
 * Frontend JavaScript for Odds Comparison
 *
 * @package OddsComparison
 * @since 1.0.0
 */

(function ($) {
    'use strict';

    /**
     * Odds Comparison frontend functionality.
     */
    const OddsComparison = {
        /**
         * Initialize the frontend functionality.
         */
        init: function () {
            this.bindEvents();
        },

        /**
         * Bind event handlers.
         */
        bindEvents: function () {
            // Handle view odds button clicks
            $(document).on('click', '.view-odds-btn', function (e) {
                e.preventDefault();
                const eventName = $(this).data('event');
                const sport = $(this).data('sport');
                const market = $(this).data('market');
                OddsComparison.showOddsModal(eventName, sport, market);
            });
        },

        /**
         * Show odds modal.
         */
        showOddsModal: function (eventName, sport, marketType) {
            // Create modal if it doesn't exist
            let modal = $('#odds-modal');
            if (modal.length === 0) {
                modal = $('<div id="odds-modal" class="odds-modal">' +
                    '<div class="odds-modal-overlay"></div>' +
                    '<div class="odds-modal-content">' +
                    '<button class="odds-modal-close">&times;</button>' +
                    '<div class="odds-modal-body">' +
                    '<h3 class="modal-event-title">' + eventName + '</h3>' +
                    '<span class="sport-badge">' + sport + '</span>' +
                    '<div class="market-tabs">' +
                    '<button class="market-tab active" data-market="match_winner">Match Winner</button>' +
                    '<button class="market-tab" data-market="over_under">Over/Under</button>' +
                    '<button class="market-tab" data-market="both_teams_score">Both Teams Score</button>' +
                    '<button class="market-tab" data-market="handicap">Handicap</button>' +
                    '</div>' +
                    '<div class="market-content"></div>' +
                    '</div>' +
                    '</div>' +
                    '</div>');
                $('body').append(modal);
           } else {
               // Update existing modal with new event
               modal.find('.modal-event-title').text(eventName);
               modal.find('.sport-badge').text(sport);
           }
           
           // Debug: Log what we're showing
           console.log('Modal title:', eventName);
           console.log('Sport badge:', sport);

            // Show modal
            modal.addClass('active');
            
            // Load bookmakers data immediately
            OddsComparison.loadMarketData(eventName, sport, marketType);

            // Handle tab clicks
            modal.find('.market-tab').off('click').on('click', function () {
                const clickedMarket = $(this).data('market');
                modal.find('.market-tab').removeClass('active');
                $(this).addClass('active');
                OddsComparison.loadMarketData(eventName, sport, clickedMarket);
            });

            // Close modal handlers
            modal.find('.odds-modal-close, .odds-modal-overlay').off('click').on('click', function () {
                modal.removeClass('active');
            });
        },

        /**
         * Load market data from admin bookmakers.
         */
        loadMarketData: function (eventName, sport, marketType) {
            const modal = $('#odds-modal');
            const contentArea = modal.find('.market-content');
            
            contentArea.html('<div class="loading">Loading odds...</div>');
            
            // Load bookmakers from admin panel
            OddsComparison.loadFallbackData(marketType);
        },

        /**
         * Load bookmakers from admin panel.
         */
        loadFallbackData: function (marketType) {
            // Get bookmakers from admin panel via AJAX
            $.ajax({
                url: oddsComparison.ajaxUrl,
                method: 'POST',
                data: {
                    action: 'get_admin_bookmakers',
                    nonce: oddsComparison.nonce
                },
                success: function(response) {
                    // Handle both Array and Object formats
                    const dataLength = Array.isArray(response.data) ? response.data.length : Object.keys(response.data || {}).length;
                    
                    if (response.success && response.data && dataLength > 0) {
                        const html = OddsComparison.generateMarketTableHTML(response.data, marketType);
                        $('#odds-modal .market-content').html(html);
                    } else {
                        // Fallback to default bookmakers
                        OddsComparison.loadDefaultFallbackData(marketType);
                    }
                },
                error: function(xhr, status, error) {
                    // Fallback to default bookmakers
                    OddsComparison.loadDefaultFallbackData(marketType);
                }
            });
        },

        /**
         * Load default fallback data when admin bookmakers fail.
         */
        loadDefaultFallbackData: function (marketType) {
            // This should never be called - show error message
            console.log('CRITICAL ERROR: No bookmakers found anywhere!');
            $('#odds-modal .market-content').html(
                '<div class="error" style="padding: 20px; text-align: center; background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; border-radius: 4px; margin: 20px;">' +
                '<h3>No bookmakers found</h3>' +
                '<p>Please check your admin panel settings:</p>' +
                '<ol style="text-align: left; display: inline-block;">' +
                '<li>Go to <strong>Odds Comparison > Settings</strong> and configure your API key</li>' +
                '<li>Go to <strong>Odds Comparison > Bookmakers</strong> and enable some bookmakers</li>' +
                '<li>Make sure your API connection is working</li>' +
                '</ol>' +
                '</div>'
            );
        },

        /**
         * Generate market table HTML from odds data.
         */
        generateMarketTableHTML: function (oddsData, marketType) {
            let html = '<div class="odds-modal-table-wrapper">';
            html += '<table class="odds-modal-table">';
            html += '<thead><tr>';
            
            if (marketType === 'match_winner' || marketType === 'h2h') {
                html += '<th>Bookmaker</th><th>Home</th><th>Draw</th><th>Away</th><th>Action</th>';
            } else if (marketType === 'over_under' || marketType === 'totals') {
                html += '<th>Bookmaker</th><th>Over 2.5</th><th>Under 2.5</th><th>Action</th>';
            } else if (marketType === 'both_teams_score') {
                html += '<th>Bookmaker</th><th>Yes</th><th>No</th><th>Action</th>';
            } else if (marketType === 'handicap') {
                html += '<th>Bookmaker</th><th>Home</th><th>Away</th><th>Action</th>';
            }
            
            html += '</tr></thead><tbody>';
            
            // Handle both array and object formats
            let bookmakers;
            if (Array.isArray(oddsData)) {
                bookmakers = oddsData;
            } else if (oddsData && oddsData.data && Array.isArray(oddsData.data)) {
                bookmakers = oddsData.data;
            } else {
                bookmakers = Object.values(oddsData);
            }
            
            
            bookmakers.forEach(function(bookmaker) {
                html += '<tr>';
                html += '<td><strong>' + (bookmaker.bookmaker || 'Unknown') + '</strong></td>';
                
                // Safely access odds with fallbacks
                const odds = bookmaker.odds || {};
                
                if (marketType === 'match_winner' || marketType === 'h2h') {
                    html += '<td>' + (odds.home || '-') + '</td>';
                    html += '<td>' + (odds.draw || '-') + '</td>';
                    html += '<td>' + (odds.away || '-') + '</td>';
                } else if (marketType === 'over_under' || marketType === 'totals') {
                    html += '<td>' + (odds.over || '-') + '</td>';
                    html += '<td>' + (odds.under || '-') + '</td>';
                } else if (marketType === 'both_teams_score') {
                    html += '<td>' + (odds.yes || '-') + '</td>';
                    html += '<td>' + (odds.no || '-') + '</td>';
                } else if (marketType === 'handicap') {
                    html += '<td>' + (odds.home || '-') + '</td>';
                    html += '<td>' + (odds.away || '-') + '</td>';
                }
                
                html += '<td><a href="' + (bookmaker.url || '#') + '" target="_blank" class="bet-now-btn">Bet Now</a></td>';
                html += '</tr>';
            });
            
            html += '</tbody></table></div>';
            
            return html;
        }
    };

    /**
     * Initialize on document ready.
     */
    $(document).ready(function () {
        OddsComparison.init();
    });

})(jQuery);