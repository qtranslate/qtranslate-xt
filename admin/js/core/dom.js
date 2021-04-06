/**
 * DOM manipulation
 */
export const qtranxj_ce = function (tagName, props, pNode, isFirst) {
    const el = document.createElement(tagName);
    if (props) {
        for (const prop in props) {
            el[prop] = props[prop];
        }
    }
    if (pNode) {
        if (isFirst && pNode.firstChild) {
            pNode.insertBefore(el, pNode.firstChild);
        } else {
            pNode.appendChild(el);
        }
    }
    return el;
};
