/**
 * Common API and core functionalities for qTranslate-XT
 */
import './pages';  // Load WP hooks for QTX actions.
import * as hooks from './hooks';

// TODO: remove legacy support in next major release
// Do not use the `qtx` object neither - it will also be removed! Use `qTranx.hooks` instead.
window.qTranslateConfig.qtx = hooks;

/**
 * Legacy support for plugin integration.
 *
 * @deprecated Use `qTranx.hooks` from new API.
 * @since 3.4
 */
// TODO: remove in next major release
qTranslateConfig.js.get_qtx = function () {
    wp.deprecated('qTranslateConfig.js.get_qtx', {
        since: '3.16.0',
        version: '4.0.0',
        plugin: 'qTranslate-XT',
        alternative: 'qTranx.hooks',
        hint: 'See release notes to use new API.'
    });
    return qTranx.hooks;
};

export * from './config';
export * as ml from './multi-lang';
export {hooks};
