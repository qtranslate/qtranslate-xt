/* executed for
 /wp-admin/nav-menus.php
*/
'use strict';
import * as hooks from '../core/hooks';

const $ = jQuery;

export default function () {
    const addMenuItemHooks = function (li) {
        hooks.addContentHooksByClass('edit-menu-item-title', li);
        hooks.addContentHooksByClass('edit-menu-item-attr-title', li);
        hooks.addContentHooksByClass('[edit-menu-item-description', li); // must use '[:]' separator style

        hooks.addDisplayHooksByClass('menu-item-title', li);
        hooks.addDisplayHooksByTagInClass('link-to-original', 'A', li);
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

    const onLanguageSwitch = function (lang) {
        if (wpNavMenu) {
            if (typeof wpNavMenu.refreshKeyboardAccessibility == 'function') {
                wpNavMenu.refreshKeyboardAccessibility();
            }
            if (typeof wpNavMenu.refreshAdvancedAccessibility == 'function') {
                wpNavMenu.refreshAdvancedAccessibility();
            }
        }
    };
    onLanguageSwitch();
    wp.hooks.addAction('qtranx.languageSwitch', 'qtranx/pages/nav-menus', onLanguageSwitch);
}
