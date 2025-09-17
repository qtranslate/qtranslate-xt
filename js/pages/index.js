'use strict';
import {isPageActive} from "../core/loader";
import loadEditTags from './edit-tags';
import loadtNavMenus from './nav-menus';
import loadPost from './post';
import loadWidgets from './widgets';

if (isPageActive('edit-tags'))
    wp.hooks.addAction('qtranx.load', 'qtranx/pages/edit-tags', loadEditTags);

if (isPageActive('nav-menus'))
    wp.hooks.addAction('qtranx.load', 'qtranx/pages/nav-menus', loadtNavMenus);

if (isPageActive('post'))
    wp.hooks.addAction('qtranx.load', 'qtranx/pages/post', loadPost);

if (isPageActive('widgets'))
    wp.hooks.addAction('qtranx.load', 'qtranx/pages/widgets', loadWidgets);
