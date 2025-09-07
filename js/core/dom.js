/**
 * DOM manipulation
 */
'use strict';

/**
 * Create an HTML element in the DOM with optional props, parentNode, beforeNode.
 *
 * @param {string} tagName
 * @param {Object=} props
 * @param {HTMLElement=} parentNode
 * @param {HTMLElement=} beforeNode
 * @returns {HTMLElement}
 */
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
