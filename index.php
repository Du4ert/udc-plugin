<?php

/**
 * @defgroup plugins_pubIds_udc UDC Pub ID Plugin
 */

/**
 * @file plugins/pubIds/udc/index.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_pubIds_udc
 * @brief Wrapper for UDC plugin.
 *
 */
require_once('UDCPubIdPlugin.inc.php');

return new UDCPubIdPlugin();


