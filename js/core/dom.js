/**
 * DOM manipulation
 */
'use strict';

export const domCreateElement = function (tagName, props, parentNode, beforeNode) {
    const elem = document.createElement(tagName);
    if (props) {
        for (const prop in props) {
            elem[prop] = props[prop];
        }
    }
    if (parentNode) {
        // Retro-compatibility with old API.
        if (typeof beforeNode === "boolean" && beforeNode) {
            console.warn("qTranslate: deprecated boolean type in domCreateElement, will be removed in future major release.");
            beforeNode = parentNode.firstChild;
        }
        parentNode.insertBefore(elem, beforeNode);  // As AppendChild if beforeNode is null.
    }
    return elem;
};
