<?php

/**
 * Internal module state, stored in DB options.
 * A module is blocked when incompatible plugins are active.
 */
const QTX_MODULE_STATE_UNDEFINED = 0;
const QTX_MODULE_STATE_ACTIVE    = 1;
const QTX_MODULE_STATE_INACTIVE  = 2;
const QTX_MODULE_STATE_BLOCKED   = 3;
