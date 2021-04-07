/**
 * DOM manipulation
 */
export const qtranxj_ce = function (tagName, props, pNode, isFirst) {
    const elem = document.createElement(tagName);
    if (props) {
        for (const prop in props) {
            elem[prop] = props[prop];
        }
    }
    if (pNode) {
        if (isFirst && pNode.firstChild) {
            pNode.insertBefore(elem, pNode.firstChild);
        } else {
            pNode.appendChild(elem);
        }
    }
    return elem;
};
