/**
 * Package to handle the loaders depending on the active page.
 */
'use strict';
import {config} from "../config";
import loadEditTags from './edit-tags';
import loadtNavMenus from './nav-menus';
import loadPost from './post';
import loadWidgets from './widgets';

if (config.isPageActive('edit-tags'))
    wp.hooks.addAction('qtranx.load', 'qtranx/pages/edit-tags', loadEditTags);

if (config.isPageActive('nav-menus'))
    wp.hooks.addAction('qtranx.load', 'qtranx/pages/nav-menus', loadtNavMenus);

if (config.isPageActive('post'))
    wp.hooks.addAction('qtranx.load', 'qtranx/pages/post', loadPost);

if (config.isPageActive('widgets'))
    wp.hooks.addAction('qtranx.load', 'qtranx/pages/widgets', loadWidgets);
