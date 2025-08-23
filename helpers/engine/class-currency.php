<?php
/**
 * Helpers Currency Class
 * 
 * @package     magicoli/opensim-helpers
 * @author      Gudule Lapointe <gudule@speculoos.world>
 * @link            https://github.com/magicoli/opensim-helpers
 * @license     AGPLv3
 */

class OpenSim_Currency {
    private static $enabled;
    private static $db;
    private static $db_creds;
    private static $provider;
    private static $module;
    private static $url;
    private static $xmlrpc_server = null;
    private static $callback;

    public function __construct() {
        $this->init();
    }

    private static function disable() {
        self::$enabled = false;
        self::$db = false; // Reset database connection
        self::$url = false; // Reset URL
    }

    private static function init() {
        self::$provider = Engine_Settings::get('engine.Economy.Provider');
        if( empty(self::$provider) ) {
            error_log('[DEBUG] ' . __METHOD__ . ' No Economy provider');
            // No provider set, disable the currency helper
            self::disable();
            return;
        }
        error_log('[DEBUG] ' . __METHOD__ . ' Economy provider: ' . self::$provider);
        
        $base_url = Engine_Settings::get('robust.GridInfoService.economy', Helpers::url());
        if( empty($base_url) ) {
            error_log('[ERROR] ' . __METHOD__ . ' Could not determine currency or helpers URL');
            self::disable();
            return;
        }
        self::$url = rtrim($base_url, '/') . '/currency.php';

        error_log('[DEBUG] ' . __METHOD__ . ' Economy provider: ' . self::$provider);

        switch (self::$provider) {
            case '':
                // No provider set, disable the currency helper
                self::disable();
                return false;
            case 'free':
                self::$module = 'BetaGridLikeMoneyModule';
                // TODO: Not sure we need db for free transactions, double check
                self::$db_creds = Engine_Settings::get(
                    'robust.DatabaseService.ConnectionString', 
                    false
                );
                break;
            case 'gloebit':
                self::$module = 'Gloebit';
                // Use Gloebit DB if set, fallback to Robust DB
                self::$db_creds = Engine_Settings::get(
                    'opensim.Gloebit.GLBSpecificConnectionString',
                    Engine_Settings::get(
                        'robust.DatabaseService.ConnectionString', 
                        false
                    )
                );
                break;
            case 'podex':
            case 'moneyserver':
                self::$module = 'DTLNSLMoneyModule';
                self::$db_creds = Engine_Settings::get(
                    'moneyserver.MySql',
                    Engine_Settings::get(
                        'robust.DatabaseService.ConnectionString', 
                        false
                    )
                );
            default:
                // Not implemented, disabled by default
                self::disable();
                return false;
        }


        self::db(); // Initialize the search database connection
        if ( ! self::$db ) {
            error_log( '[ERROR] Failed to connect to the search database.' );
            self::disable();
            return;
        }

        // If nothing above has set Enabled to false, we are good to set it to true.
        if( self::$enabled !== false ) {
            self::$enabled = true;
        }
    }

    public static function url() {
        if( self::$url === null ) {
            self::init();
        }
        return self::$url;
    }

    public static function enabled() {
        if( self::$enabled === null ) {
            self::init();
        }
        return self::$enabled;
    }

    public static function db() {
        if( self::$db ) {
            return self::$db;
        }
        if( self::$db === false ) {
            // Don't check again if already failed
            return false;
        }

        self::$db = false; // Reset to false to avoid multiple checks

        if(! self::$db_creds ) {
            self::disable();
            return false;
        }
        
        if (self::$db_creds) {
            self::$db = new OpenSim_Database(self::$db_creds);
        } else {
            self::$db = OpenSim_Robust::db(); // Fallback to Robust database if SearchDB not configured
        }

        if (self::$db) {
            return self::$db;
        }

        error_log('[ERROR] ' . __METHOD__ . ' Database connection failed');
        self::$db = false; // Set to false if connection fails

        return self::$db;
    }

    private static function response( $response, $error_code = null ) {
        if(!is_array($response)) {
            $success = $error_code === null ?? isSuccess($error_code);
            $message = is_string($response) ? $response : 'Bad response format';
            $response = osResponseArray($success, $response, null, $error_code);
        }
        error_log('[DEBUG] ' . __METHOD__ . ' Response: ' . print_r($response, true));
        if( self::$callback && is_callable( self::$callback ) ) {
            return call_user_func( self::$callback, $response, $error_code );
        } else {
            return $response;
        }
    }

    public static function register( $callback = null ) {
        if( $callback && is_callable( $callback ) ) {
            error_log('[DEBUG] ' . __METHOD__ . ' Callback: ' . print_r($callback, true));
            self::$callback = $callback;
        }
        
        if (!self::enabled()) {
            return osError('Currency helper is not enabled', 503);
            // return false;
        }

        if(self::$xmlrpc_server) {
            // Already registered, no need to register again
            return self::$xmlrpc_server;
        }

        // The XMLRPC server object
        self::$xmlrpc_server = xmlrpc_server_create();

        if(! self::$xmlrpc_server ) {
            return osError('Failed to create XML-RPC server', 500);
        }

        // Register XML-RPC methods
        xmlrpc_server_register_method( self::$xmlrpc_server, 'getCurrencyQuote', 'currency_xmlrpc_quote' );
        xmlrpc_server_register_method( self::$xmlrpc_server, 'buyCurrency', 'currency_xmlrpc_buy' );
        xmlrpc_server_register_method( self::$xmlrpc_server, 'simulatorUserBalanceRequest', 'currency_xmlrpc_balance' );
        xmlrpc_server_register_method( self::$xmlrpc_server, 'regionMoveMoney', 'currency_xmlrpc_regionMoveMoney' );
        xmlrpc_server_register_method( self::$xmlrpc_server, 'simulatorClaimUserRequest', 'currency_xmlrpc_claimUserRequest' );

        //
        // Process the request
        //
        $request_xml = file_get_contents( 'php://input' );
        // error_log(__FILE__ . ' '. $request_xml);

        xmlrpc_server_call_method( $xmlrpc_server, $request_xml, '' );
        xmlrpc_server_destroy( $xmlrpc_server );
        // die();
    }

    public static function process() {
        if( ! self::enabled() ) {
            return array(
                'success' => false,
                'errorMessage' => 'Currency helper is not enabled',
                'errorURI' => self::$url,
                'error_code' => 503
            );
        }
            
        $summary = [];
        return osSuccess('Currency request processed', $summary);
    }

    function currency_xmlrpc_quote( $method_name, $params, $app_data ) {
        $req       = $params[0];
        $agentid   = $req['agentId'];
        $sessionid = $req['secureSessionId'];
        $amount    = $req['currencyBuy'];
        $ipAddress = $_SERVER['REMOTE_ADDR'];

        $ret = opensim_check_secure_session( $agentid, null, $sessionid );

        if ( $ret ) {
            $confirmvalue = currency_get_confirm_value( $ipAddress );
            switch ( CURRENCY_PROVIDER ) {
                case 'gloebit':
                    $cost             = 1; // default cost if no table;
                    $conversion_table = GLOEBIT_CONVERSION_TABLE;
                    foreach ( $conversion_table as $key => $value ) {
                        $cost = $value;
                        if ( GLOEBIT_CONVERSION_THRESHOLD > 0 ) {
                            $threshold = GLOEBIT_CONVERSION_THRESHOLD;
                        } else {
                            $threshold = 1;
                        }
                        if ( $key >= $amount / $threshold ) {
                            break;
                        }
                    }
                    break;

                default:
                    $cost       = currency_virtual_to_real( $amount );
                    $realamount = $amount;
            }

            $currency     = array(
                'estimatedCost' => $cost,
                'currencyBuy'   => $realamount,
            );
            $response_xml = xmlrpc_encode(
                array(
                    'success'  => true,
                    'currency' => $currency,
                    'confirm'  => $confirmvalue,
                )
            );
        } else {
            $response_xml = xmlrpc_encode(
                array(
                    'success'      => false,
                    'errorMessage' => "Unable to Authenticate\n\nClick URL for more info.",
                    'errorURI'     => '' . CURRENCY_HELPER_URL . '',
                )
            );
        }

        header( 'Content-type: text/xml' );
        echo $response_xml;

        return '';
    }

    //
    // Viewer buys currency
    //
    function currency_xmlrpc_buy( $method_name, $params, $app_data ) {
        $req       = $params[0];
        $agentid   = $req['agentId'];
        $sessionid = $req['secureSessionId'];
        $amount    = $req['currencyBuy'];
        $confim    = $req['confirm'];
        $ipAddress = $_SERVER['REMOTE_ADDR'];

        if ( $confim != currency_get_confirm_value( $ipAddress ) ) {
            $response_xml = xmlrpc_encode(
                array(
                    'success'      => false,
                    'errorMessage' => "\n\nMissmatch Confirm Value!!",
                    'errorURI'     => '' . CURRENCY_HELPER_URL . '',
                )
            );
            header( 'Content-type: text/xml' );
            echo $response_xml;
            return '';
        }

        $checkSecure = opensim_check_secure_session( $agentid, null, $sessionid );
        if ( ! $checkSecure ) {
            $response_xml = xmlrpc_encode(
                array(
                    'success'      => false,
                    'errorMessage' => "\n\nMissmatch Secure Session ID!!",
                    'errorURI'     => '' . CURRENCY_HELPER_URL . '',
                )
            );
            header( 'Content-type: text/xml' );
            echo $response_xml;
            return '';
        }

        $ret               = false;
        $cost              = currency_virtual_to_real( $amount );
        $transactionPermit = currency_process_transaction( $agentid, $cost, $ipAddress );

        if ( $transactionPermit ) {
            $res = currency_add_money( $agentid, $amount, $sessionid );
            if ( $res['success'] ) {
                $ret = true;
            }
        }

        if ( $ret ) {
            $response_xml = xmlrpc_encode( array( 'success' => true ) );
        } else {
            switch ( CURRENCY_PROVIDER ) {
                case 'podex':
                    $errorURI     = null; // opensim_format_tp(PODEX_REDIRECT_URL, TPLINK_HOP);
                    $errorMessage = PODEX_ERROR_MESSAGE . ' ' . PODEX_REDIRECT_URL;
                    break;

                case 'gloebit':
                    if ( defined( GLOEBIT_SANDBOX ) && GLOEBIT_SANDBOX ) {
                        $baseurl = 'https://sandbox.gloebit.com/purchase';
                    } else {
                        $baseurl = 'https://www.gloebit.com/purchase';
                    }
                    $server_info = opensim_get_server_info( $agentid );
                    $serverip    = $server_info['serverIP'];
                    $httpport    = $server_info['serverHttpPort'];

                    $informurl    = "http://${serverip}:${httpport}/gloebit/buy_complete?agentId=${agentid}";
                    $errorURI     = "${baseurl}?reset&r=&inform=$informurl";
                    $errorMessage = 'Click OK to finish the transaction on Gloebit website.';
                    break;

                default:
                    $errorMessage = 'Unable to process the transaction. The gateway denied your charge. Open help page?';
                    // TODO: return website help page URL
                    $errorURI     = CURRENCY_HELPER_URL ?? null;
            }
            $response_xml = xmlrpc_encode(
                array(
                    'success'      => false,
                    'errorMessage' => $errorMessage,
                    'errorURI'     => $errorURI,
                )
            );
        }

        header( 'Content-type: text/xml' );
        echo $response_xml;

        return '';
    }

    //
    // Region requests account balance
    //
    function currency_xmlrpc_balance( $method_name, $params, $app_data ) {
        $req       = $params[0];
        $agentid   = $req['agentId'];
        $sessionid = $req['secureSessionId'];

        $balance = currency_get_balance( $agentid, $sessionid );

        if ( $balance >= 0 ) {
            $response_xml = xmlrpc_encode(
                array(
                    'success' => true,
                    'agentId' => $agentid,
                    'funds'   => $balance,
                )
            );
        } else {
            $response_xml = xmlrpc_encode(
                array(
                    'success'      => false,
                    'errorMessage' => 'Could not authenticate your avatar. Money operations may be unavailable',
                    'errorURI'     => ' ',
                )
            );
        }

        header( 'Content-type: text/xml' );
        echo $response_xml;

        return '';
    }

    //
    // Region initiates money transfer (Direct DB Operation for security)
    //
    function currency_xmlrpc_regionMoveMoney( $method_name, $params, $app_data ) {
        $req                    = $params[0];
        $agentid                = $req['agentId'];
        $destid                 = $req['destId'];
        $sessionid              = $req['secureSessionId'];
        $regionid               = $req['regionId'];
        $secret                 = $req['secret'];
        $currencySecret         = $req['currencySecret'];
        $cash                   = $req['cash'];
        $aggregatePermInventory = $req['aggregatePermInventory'];
        $aggregatePermNextOwner = $req['aggregatePermNextOwner'];
        $flags                  = $req['flags'];
        $transactiontype        = $req['transactionType'];
        $description            = $req['description'];
        $ipAddress              = $_SERVER['REMOTE_ADDR'];

        $ret = opensim_check_region_secret( $regionid, $secret );

        if ( $ret ) {
            $ret = opensim_check_secure_session( $agentid, $regionid, $sessionid );

            if ( $ret ) {
                $balance = currency_get_balance( $agentid, $sessionid );
                if ( $balance >= $cash ) {
                    currency_move_money(
                        $agentid,
                        $destid,
                        $cash,
                        $transactiontype,
                        $flags,
                        $description,
                        $aggregatePermInventory,
                        $aggregatePermNextOwner,
                        $ipAddress
                    );
                    $sbalance = currency_get_balance( $agentid, $sessionid );
                    $dbalance = currency_get_balance( $destid );

                    $response_xml = xmlrpc_encode(
                        array(
                            'success'        => true,
                            'agentId'        => $agentid,
                            'funds'          => $balance,
                            'funds2'         => $balance,
                            'currencySecret' => ' ',
                        )
                    );

                    currency_update_simulator_balance( $agentid, $sbalance, $sessionid );
                    currency_update_simulator_balance( $destid, $dbalance );
                } else {
                    $response_xml = xmlrpc_encode(
                        array(
                            'success'      => false,
                            'errorMessage' => 'You do not have sufficient funds for this purchase',
                            'errorURI'     => ' ',
                        )
                    );
                }
            } else {
                $response_xml = xmlrpc_encode(
                    array(
                        'success'      => false,
                        'errorMessage' => 'Unable to authenticate avatar. Money operations may be unavailable',
                        'errorURI'     => ' ',
                    )
                );
            }
        } else {
            $response_xml = xmlrpc_encode(
                array(
                    'success'      => false,
                    'errorMessage' => 'This region is not authorized to manage your money.',
                    'errorURI'     => ' ',
                )
            );
        }

        header( 'Content-type: text/xml' );
        echo $response_xml;

        return '';
    }

    //
    // Region claims user
    //
    function currency_xmlrpc_claimUserRequest( $method_name, $params, $app_data ) {
        $req       = $params[0];
        $agentid   = $req['agentId'];
        $sessionid = $req['secureSessionId'];
        $regionid  = $req['regionId'];
        $secret    = $req['secret'];

        $ret = opensim_check_region_secret( $regionid, $secret );

        if ( $ret ) {
            $ret = opensim_check_secure_session( $agentid, null, $sessionid );

            if ( $ret ) {
                $ret = opensim_set_current_region( $agentid, $regionid );

                if ( $ret ) {
                    $balance      = currency_get_balance( $agentid, $sessionid );
                    $response_xml = xmlrpc_encode(
                        array(
                            'success'        => true,
                            'agentId'        => $agentid,
                            'funds'          => $balance,
                            'currencySecret' => ' ',
                        )
                    );
                } else {
                    $response_xml = xmlrpc_encode(
                        array(
                            'success'      => false,
                            'errorMessage' => 'Error occurred, when DB was updated.',
                            'errorURI'     => ' ',
                        )
                    );
                }
            } else {
                $response_xml = xmlrpc_encode(
                    array(
                        'success'      => false,
                        'errorMessage' => 'Unable to authenticate avatar. Money operations may be unavailable.',
                        'errorURI'     => ' ',
                    )
                );
            }
        } else {
            $response_xml = xmlrpc_encode(
                array(
                    'success'      => false,
                    'errorMessage' => 'This region is not authorized to manage your money.',
                    'errorURI'     => ' ',
                )
            );
        }

        header( 'Content-type: text/xml' );
        echo $response_xml;

        return '';
    }
}
