/**
 * @file
 * Provides an event handler for hidden elements in dropdown menus.
 */

(function ($) { 
    'use strict';

    function setActiveMenu() {
        const path = window.location.pathname.split('/');
        const search = window.location.search.split('/');
        const activeMenu = jQuery(`#${path.pop()}`)
        
        activeMenu.addClass('category-filter--active');
        activeMenu.closest('ul').css('display', 'block');
        activeMenu.closest('ul').parent().find('span').addClass('active');
        activeMenu.closest('ul').parent().find('i').removeClass('fa-caret-right').addClass('fa-caret-down active');
    }

    function copyEmailToUserName() {
        $('#edit-mail').on('input', function() {
            $('#edit-name').val(this.value); 
        });
    }

    $("document").ready(function() {

        // $('#block-filtro-categorias-menu li span').click(function(){
            // $('#block-filtro-categorias-menu li span').not(this).removeClass('active').parent().next().slideUp();
            // $('#block-filtro-categorias-menu li i').not(this).removeClass('fa-caret-down active').addClass('fa-caret-right');
            // $(this).toggleClass('active').parent().next().slideToggle();
            // $(this).siblings().removeClass('fa-caret-right').addClass('fa-caret-down active')
        // });
		
		$('#block-filtro-categorias-menu li div').click(function(){
			let currentSpan = $(this).find('span');
			let currentI = $(this).find('i');
            $('#block-filtro-categorias-menu li span').not(currentSpan).removeClass('active').parent().next().slideUp();
            $('#block-filtro-categorias-menu li i').not(currentI).removeClass('fa-caret-down active').addClass('fa-caret-right');
            currentSpan.toggleClass('active').parent().next().slideToggle();
            currentSpan.siblings().removeClass('fa-caret-right').addClass('fa-caret-down active')
        });

        setActiveMenu();
        copyEmailToUserName();
        // fa-caret-right
        // fa-caret-down
    });
})(jQuery);