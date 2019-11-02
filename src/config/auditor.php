<?php

/**
 * demafelix/auditor
 * A simplified audit trail recorder for Laravel.
 *
 * @author Liam Demafelix <ldemafelix@protonmail.ch>
 * @license MIT Open Source License
 */

return [

    /**
     * Specify the models to watch by providing their
     * fully-qualified class names below.
     *
     * @var array
     */

    'models' => [
        'App\User',
    ],

    /**
     * Specify fields to discard.
     * The fields specified in this configuration are discarded for all models.
     * To make model-specific discards, use the $discarded declaration on your model.
     *
     * @var array
     */

    'global_discards' => [
        'password', 'remember_token', 'created_at', 'updated_at', 'deleted_at', 'banned_at'
    ]

];
