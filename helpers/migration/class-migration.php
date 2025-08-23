<?php
/**
 * Migration class. Should be used only once.
 * 
 * This class is used to migrate the configuration from the old constants-based config.php to the new array-based settings.
 * 
 * First it defines an map of the old constants to the new settings names.
 */

class OSHelpers_Migration {
    private $map = array(
        'Robust.HG.ini' => array(
        'OPENSIM_GRID_NAME'   => 'Const.BaseURL',
        'OPENSIM_LOGIN_URI'   => array ( 'Const.BaseURL', 'Const.PublicPort' ),
        'OPENSIM_MAIL_SENDER' => array( $this, 'mail_sender' ),
        'ROBUST_DB'           => 'OpenSim.ini::DatabaseService.ConnectionString',
        'OPENSIM_DB'          => 'DatabaseService.ConnectionString',
        // 'OPENSIM_DB_HOST'     => $robust_db['host'],
        // 'OPENSIM_DB_PORT'     => $robust_db['port'] ?? null,
        // 'OPENSIM_DB_NAME'     => $robust_db['name'],
        // 'OPENSIM_DB_USER'     => $robust_db['user'],
        // 'OPENSIM_DB_PASS'     => $robust_db['pass'],
        'SEARCH_REGISTRARS'   => array( $this, 'search_registrars' ),
        // 'ROBUST_CONSOLE'     => $console,
        'CURRENCY_HELPER_URL' => 'GridInfoService.economy',
        'CURRENCY_DB' => '',
        'CURRENCY_DB_HOST' => 'MoneyServer.ini::MySql.hostname',
        'CURRENCY_DB_NAME' => 'MoneyServer.ini::MySql.database',
        'CURRENCY_DB_USER' => 'MoneyServer.ini::MySql.username',
        'CURRENCY_DB_PASS' => 'MoneyServer.ini::MySql.password',
        'CURRENCY_DB_PORT' => 'MoneyServer.ini::MySql.port',
        // 'CURRENCY_HELPER_PATH' => '',
        'CURRENCY_MONEY_TBL' => '',
        'CURRENCY_PROVIDER' => '',
        'CURRENCY_RATE' => '',
        'CURRENCY_RATE_PER' => '',
        'CURRENCY_SCRIPT_KEY' => '',
        'CURRENCY_TRANSACTION_TBL' => '',
        'CURRENCY_USE_MONEYSERVER' => '',
        'HYPEVENTS_URL' => '',
        'MUTE_DB_HOST' => '',
        'MUTE_DB_NAME' => '',
        'MUTE_DB_PASS' => '',
        'MUTE_DB_USER' => '',
        'MUTE_LIST_TBL' => '',
        'OFFLINE_DB' => '',
        'OFFLINE_DB_HOST' => '',
        'OFFLINE_DB_NAME' => '',
        'OFFLINE_DB_PASS' => '',
        'OFFLINE_DB_PORT' => '',
        'OFFLINE_DB_USER' => '',
        'OFFLINE_MESSAGE_TBL' => '',
        'OPENSIM_GRID_LOGO_URL' => '',
        'OPENSIM_USE_UTC_TIME' => '',
        'SEARCH_DB' => '',
        'SEARCH_DB_HOST' => '',
        'SEARCH_DB_NAME' => '',
        'SEARCH_DB_PASS' => '',
        'SEARCH_DB_PORT' => '',
        'SEARCH_DB_USER' => '',
        'SEARCH_TABLE_EVENTS' => '',
    );
}
