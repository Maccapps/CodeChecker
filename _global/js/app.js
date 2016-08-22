/*globals $*/
/*jslint eqeq:true plusplus:true*/

var oApp = window.oApp || {};

(function () {

    'use strict';

    oApp.showActiveResultsTab = function () {
        var activeTab = $('.table-counts span.active').data('tab-id');
        $('#table-results tr.issue').addClass('hide');
        $('#table-results tr[data-type="' + activeTab + '"]').removeClass('hide');
    };
    oApp.showActiveResultsTab();

    $('.table-counts').on('click', 'span', function () {
        var el = $(this);

        $('.table-counts span').removeClass('active');
        el.addClass('active');
        oApp.showActiveResultsTab();
    });

    $('.jsToggleWrapper').on('click', '.jsToggleTrigger', function () {
        var el = $(this),
            wrapper = el.closest('.jsToggleWrapper'),
            target = wrapper.find('.jsToggleTarget');

        el.toggleClass('twisted');
        target.toggleClass('hide');
    });

}());
