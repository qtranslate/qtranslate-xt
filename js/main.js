/**
 * Common API and core functionalities for qTranslate-XT
 */
import './pages';
import * as core from './core';

// TODO: remove legacy support in next major release
// Do not use the `qtx` object neither - it will also be removed! Use `qTranx.hooks` instead.
window.qTranslateConfig.qtx = core.hooks;

export * from './core';
