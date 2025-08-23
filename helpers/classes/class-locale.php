<?php
class OpenSim_Locale {
    const HTTP_ACCEPT_LANGUAGE_HEADER_KEY = 'HTTP_ACCEPT_LANGUAGE';
    private static $detected = array();
    // private static $lang;
    // private static $locale;
  
    public static function detect() {
        if( !empty( self::$detected ) ) {
            return self::$detected;
        }
        $httpAcceptLanguageHeader = static::getHttpAcceptLanguageHeader();
        if ($httpAcceptLanguageHeader == null) {
            return [];
        }
        $locales = static::getWeightedLocales($httpAcceptLanguageHeader);
        $sortedLocales = static::sortLocalesByWeight($locales);
    
        self::$detected = array_map(function ($weightedLocale) {
            return $weightedLocale['locale'];
        }, $sortedLocales);

        if( empty( self::$detected ) ) {
            self::$detected = ['en_US', 'en'];
        }

        return self::$detected;
    }
    
    public static function locale() {
        $detected = self::detect();
        return $detected[0] ?? 'en_US';
    }

    public static function lang() {
        $detected = self::detect();
        return $detected[1] ?? substr( self::locale(), 0, 2 ) ?? 'en';
    }

    private static function getHttpAcceptLanguageHeader() {
        if (isset($_SERVER[static::HTTP_ACCEPT_LANGUAGE_HEADER_KEY])) {
            return trim($_SERVER['HTTP_ACCEPT_LANGUAGE']);
        } else {
            return null;
        }
    }
    
    private static function getWeightedLocales($httpAcceptLanguageHeader) {
        if (strlen($httpAcceptLanguageHeader) == 0) {
            return [];
        }

        $weightedLocales = [];

        // We break up the string 'en-CA,ar-EG;q=0.5' along the commas,
        // and iterate over the resulting array of individual locales. Once
        // we're done, $weightedLocales should look like
        // [['locale' => 'en-CA', 'q' => 1.0], ['locale' => 'ar-EG', 'q' => 0.5]]
        foreach (explode(',', $httpAcceptLanguageHeader) as $locale) {
            // separate the locale key ("ar-EG") from its weight ("q=0.5")
            $localeParts = explode(';', $locale);
            $weightedLocale = ['locale' => $localeParts[0]];
            if (count($localeParts) == 2) {
                // explicit weight e.g. 'q=0.5'
                $weightParts = explode('=', $localeParts[1]);
                // grab the '0.5' bit and parse it to a float
                $weightedLocale['q'] = floatval($weightParts[1]);
            } else {
                // no weight given in string, ie. implicit weight of 'q=1.0'
                $weightedLocale['q'] = 1.0;
            }
            $weightedLocales[] = $weightedLocale;
        }
        return $weightedLocales;
    }
    
    /**
     * Sort by high to low `q` value
     */
    private static function sortLocalesByWeight($locales) {
        usort($locales, function ($a, $b) {
            // usort will cast float values that we return here into integers,
            // which can mess up our sorting. So instead of subtracting the `q`,
            // values and returning the difference, we compare the `q` values and
            // explicitly return integer values.
            if ($a['q'] == $b['q']) {
                return 0;
            }
            if ($a['q'] > $b['q']) {
                return -1;
            }
            return 1;
        });
        
        return $locales;
    }
}
