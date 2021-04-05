/**
 * DOM manipulation
 */
export var qtranxj_ce = function (tagName, props, pNode, isFirst) {
    var el = document.createElement(tagName);
    if (props) {
        for (var prop in props) {
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
