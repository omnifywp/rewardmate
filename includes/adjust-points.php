<?php
/**
 * Save the user points adjustment and gift points
 *
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

function rewardmate_save_user_points($user_id) {
    if (function_exists('rewardmate_save_user_points_metabox')) {
        return rewardmate_save_user_points_metabox($user_id);
    }
    return false;
}
