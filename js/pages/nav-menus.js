/* executed for
 /wp-admin/nav-menus.php
*/
'use strict';
const $ = jQuery;

export default function () {
    const qtx = qTranx.hooks;

    const addMenuItemHooks = function (li) {
        qtx.addContentHooksByClass('edit-menu-item-title', li);
        qtx.addContentHooksByClass('edit-menu-item-attr-title', li);
        qtx.addContentHooksByClass('[edit-menu-item-description', li); // must use '[:]' separator style

        qtx.addDisplayHooksByClass('menu-item-title', li);
        qtx.addDisplayHooksByTagInClass('link-to-original', 'A', li);
    };

    const onAddMenuItem = function (menuMarkup) {
        const rx = /id="menu-item-(\d+)"/gi;
        let matches;
        while ((matches = rx.exec(menuMarkup))) {
            const id = 'menu-item-' + matches[1];
            const li = document.getElementById(id);
            if (li) addMenuItemHooks(li);
        }
    };

    if (wpNavMenu) {
        const wp_addMenuItemToBottom = wpNavMenu.addMenuItemToBottom;
        if (typeof wp_addMenuItemToBottom == 'function') {
            wpNavMenu.addMenuItemToBottom = function (menuMarkup, req) {
                wp_addMenuItemToBottom(menuMarkup, req);
                onAddMenuItem(menuMarkup);
            };
        }
        if (typeof wp_addMenuItemToTop == 'function') {
            wpNavMenu.addMenuItemToTop = function (menuMarkup) {
                wp_addMenuItemToTop(menuMarkup);
                onAddMenuItem(menuMarkup);
            };
        }
    }

    const onLanguageSwitchAfter = function (lang) {
        if (wpNavMenu) {
            if (typeof wpNavMenu.refreshKeyboardAccessibility == 'function') {
                wpNavMenu.refreshKeyboardAccessibility();
            }
            if (typeof wpNavMenu.refreshAdvancedAccessibility == 'function') {
                wpNavMenu.refreshAdvancedAccessibility();
            }
        }
    };
    onLanguageSwitchAfter();

    qtx.addLanguageSwitchAfterListener(onLanguageSwitchAfter);
}
