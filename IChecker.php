<?php
/**
 * Another simple input checker
 *
 * License:
 * This is open-sourced software licensed under the [MIT license]
 * (http://opensource.org/licenses/MIT).
 *
 * History:
 * 2020-06-17 v1.0 shangshuai@gmail.com
 *
 */
class IChecker {

    // rules
    const RULE_TYPE = 'type';
    const RULE_REQUIRED = 'required';
    const RULE_NONEMPTY = 'nonempty';
    const RULE_DEFAULT = 'default';
    const RULE_RANGE = 'range';
    const RULE_CONVERT = 'convert';

    // rule handlers
    protected static $RULE_HANDLERS = [
        self::RULE_TYPE => '_checkType',
        self::RULE_REQUIRED => '_checkRequired',
        self::RULE_NONEMPTY => 'checkNonEmpty',
        self::RULE_DEFAULT => '_setDefault',
        self::RULE_RANGE => '_checkRange',
        self::RULE_CONVERT => '_doConvert',
    ];

    // types
    const TYPE_STRING = 'string';
    const TYPE_NUMERIC = 'numeric';
    const TYPE_INTEGER = 'integer';
    const TYPE_VERSION = 'version';
    const TYPE_DATETIME = 'datetime';
    const TYPE_ARRAY = 'array';
    const TYPE_ANY = 'any';

    // type operations
    const OPR_GT = '>';
    const OPR_GTE = '>=';
    const OPR_LT = '<';
    const OPR_LTE = '<=';
    const OPR_EQ = '==';
    const OPR_NEQ = '!=';
    const OPR_REGEX = 'regex';
    const OPR_IN = 'in';

    // type comparers
    protected static $TYPE_COMPARERS = [
        self::TYPE_STRING => '_compareString',
        self::TYPE_VERSION => '_compareVersion',
        self::TYPE_DATETIME => '_compareDatetime',
        self::TYPE_ARRAY => '_compareArray',
        self::TYPE_ANY => '_compareAny',
    ];

    // check options
    const OPT_FILTER = 'filter';

    // special keys
    const KEY_ALL = '...';

    /**
     * @brief check a single key in an array
     * @param $arrInput
     * @param $strKey
     * @param $strType
     * @param $bolRequired
     * @param $bolNotEmpty
     * @param $mixDefault
     * @return mixed
     * @throws Exception
     */
    public static function check($arrInput, $strKey, $strType, $bolRequired = true, $bolNotEmpty = false, $mixDefault = null)
    {
        $arrKeyRule = [
            self::RULE_TYPE => $strType,
            self::RULE_REQUIRED => $bolRequired,
            self::RULE_NONEMPTY => $bolNotEmpty,
            self::RULE_DEFAULT => $mixDefault,
        ];

        return self::checkKeyRule($arrInput, $strKey, $arrKeyRule);
    }

    /**
     * @brief check a single key in an array with a rule
     * @param $arrInput
     * @param $strKey
     * @param $arrKeyRule
     * @return mixed
     * @throws Exception
     */
    public static function checkKeyRule($arrInput, $strKey, $arrKeyRule)
    {
        $arrRule = [
            $strKey => $arrKeyRule,
        ];

        $arrInput = self::checkRule($arrInput, $arrRule);
        return $strKey == self::KEY_ALL ? $arrInput : $arrInput[$strKey];
    }

    /**
     * @brief check multi keys in an array with a rule
     * @param $arrInput
     * @param $arrRule
     * @param $arrOpt
     * @return array
     * @throws Exception
     */
    public static function checkRule($arrInput, $arrRule, $arrOpt = [])
    {
        list ($bolRet, $strMsg, $arrInput) = self::_checkRule($arrInput, $arrRule, $arrOpt);
        if (!$bolRet) {
            throw new Exception("param error: $strMsg");
        }

        return $arrInput;
    }

    /**
     * @brief check a key's type
     * @param $arrInput
     * @param $strKey
     * @param $arrKeyRule
     * @param $arrOtp
     * @return array
     */
    protected static function _checkType($arrInput, $strKey, $arrKeyRule, $arrOtp)
    {
        $bolRet = true;
        $strMsg = '';

        if (!array_key_exists($strKey, $arrInput)) {
            return [$bolRet, $strMsg, $arrInput];
        }

        $mixType = $arrKeyRule[self::RULE_TYPE];

        if (is_array($mixType)) {
            // the key is an array which match a sub rule
            if (is_array($arrInput[$strKey])) {
                list($bolRet, $strMsg, $arrInput[$strKey]) = self::_checkRule($arrInput[$strKey], $mixType, $arrOtp);
            } else {
                $bolRet = false;
                $strMsg = "should be " . self::TYPE_ARRAY;
            }
            return [$bolRet, $strMsg, $arrInput];
        }

        switch ($mixType) {
            case self::TYPE_STRING;
                $bolRet = !is_array($arrInput[$strKey]);
                break;
            case self::TYPE_NUMERIC;
                $bolRet = is_numeric($arrInput[$strKey]);
                break;
            case self::TYPE_INTEGER;
                $bolRet = is_numeric($arrInput[$strKey]) && intval($arrInput[$strKey]) == $arrInput[$strKey];
                break;
            case self::TYPE_VERSION;
                $bolRet = (bool)preg_match('/\d+(\.\d+){0,3}/', $arrInput[$strKey]);
                break;
            case self::TYPE_DATETIME;
                $bolRet = strtotime($arrInput[$strKey]) !== false;
                break;
            case self::TYPE_ARRAY;
                $bolRet = is_array($arrInput[$strKey]);
                break;
            default:;
        }
        if (!$bolRet) {
            $strMsg = "should be {$arrKeyRule[self::RULE_TYPE]}";
        }
        return [$bolRet, $strMsg, $arrInput];
    }

    /**
     * @brief check a key's existence
     * @param $arrInput
     * @param $strKey
     * @param $arrKeyRule
     * @return array
     */
    protected static function _checkRequired($arrInput, $strKey, $arrKeyRule)
    {
        $bolRet = true;
        $strMsg = '';

        if ($arrKeyRule[self::RULE_REQUIRED] && !array_key_exists($strKey, $arrInput)) {
            $bolRet = false;
            $strMsg = "is required";
        }

        return [$bolRet, $strMsg, $arrInput];
    }

    /**
     * @brief check is a key is not empty
     * @param $arrInput
     * @param $strKey
     * @param $arrKeyRule
     * @return array
     */
    protected static function checkNonEmpty($arrInput, $strKey, $arrKeyRule)
    {
        $bolRet = true;
        $strMsg = '';

        if ($arrKeyRule[self::RULE_NONEMPTY] && array_key_exists($strKey, $arrInput) && empty($arrInput[$strKey])) {
            $bolRet = false;
            $strMsg = "should not be empty";
        }

        return [$bolRet, $strMsg, $arrInput];
    }

    /**
     * @brief set default value when a key does not exist
     * @param $arrInput
     * @param $strKey
     * @param $arrKeyRule
     * @return array
     */
    protected static function _setDefault($arrInput, $strKey, $arrKeyRule)
    {
        $bolRet = true;
        $strMsg = '';

        $mixDefault = $arrKeyRule[self::RULE_DEFAULT];
        if (!array_key_exists($strKey, $arrInput) && !is_null($mixDefault)) {
            $arrInput[$strKey] = $mixDefault;
        }

        return [$bolRet, $strMsg, $arrInput];
    }

    /**
     * @brief check a key's value range
     * @param $arrInput
     * @param $strKey
     * @param $arrKeyRule
     * @return array
     */
    protected static function _checkRange($arrInput, $strKey, $arrKeyRule)
    {
        $bolRet = true;
        $strMsg = '';

        $arrRange = $arrKeyRule[self::RULE_RANGE];
        if (!array_key_exists($strKey, $arrInput) || !is_array($arrRange) || empty($arrRange)) {
            return [$bolRet, $strMsg, $arrInput];
        }

        $strType = $arrKeyRule[self::RULE_TYPE] ?: gettype($arrInput[$strKey]);
        $strComparer = self::$TYPE_COMPARERS[$strType] ?: self::$TYPE_COMPARERS[self::TYPE_ANY];

        $arrRanges = isset($arrRange[0]) ? $arrRange : [$arrRange];
        foreach ($arrRanges as $arrRange) {
            $bolRet = true;
            foreach ($arrRange as $strOpr => $mixValue) {
                if (!call_user_func([__CLASS__, $strComparer], $arrInput[$strKey], $mixValue, $strOpr)) {
                    $bolRet = false;
                    break;
                }
            }
            if ($bolRet) {
                break;
            }
        }

        if (!$bolRet) {
            $strMsg = "as $strType, should be " . implode(', or ', array_map(function($arrRange) {
                    return implode(' and ', array_map(function($strOpr, $mixValue){
                        return "$strOpr " . (is_array($mixValue) ? json_encode($mixValue) : $mixValue);
                    }, array_keys($arrRange), array_values($arrRange)));
                }, $arrRanges));
        }
        return [$bolRet, $strMsg, $arrInput];
    }

    /**
     * @brief convert a key's value
     * @param $arrInput
     * @param $strKey
     * @param $arrKeyRule
     * @return array
     */
    protected static function _doConvert($arrInput, $strKey, $arrKeyRule)
    {
        $bolRet = true;
        $strMsg = '';

        $strConverter = $arrKeyRule[self::RULE_CONVERT];

        if (array_key_exists($strKey, $arrInput) && is_callable($strConverter)) {
            $arrInput[$strKey] = call_user_func($strConverter, $arrInput[$strKey]);
        }

        return [$bolRet, $strMsg, $arrInput];
    }

    /**
     * @brief string comparer
     * @param $x
     * @param $y
     * @param $strOpr
     * @return bool
     */
    protected static function _compareString($x, $y, $strOpr)
    {
        switch ($strOpr) {
            case self::OPR_REGEX:
                return (bool)preg_match($y, $x);
            default:;
        }
        return self::_compareAny($x, $y, $strOpr);
    }

    /**
     * @brief version comparer
     * @param $x
     * @param $y
     * @param $strOpr
     * @return bool
     */
    protected static function _compareVersion($x, $y, $strOpr)
    {
        switch ($strOpr) {
            case self::OPR_GT:
            case self::OPR_GTE:
            case self::OPR_LT:
            case self::OPR_LTE:
            case self::OPR_EQ:
            case self::OPR_NEQ:
                return version_compare($x, $y, $strOpr);
            default:;
        }
        return self::_compareString($x, $y, $strOpr);
    }

    /**
     * @brief version comparer
     * @param $x
     * @param $y
     * @param $strOpr
     * @return bool
     */
    protected static function _compareDatetime($x, $y, $strOpr)
    {
        $x = strtotime($x);
        $y = strtotime($y);

        return self::_compareAny($x, $y, $strOpr);
    }

    /**
     * @brief array comparer
     * @param $x
     * @param $y
     * @param $strOpr
     * @return bool
     */
    protected static function _compareArray($x, $y, $strOpr)
    {
        switch ($strOpr) {
            case self::OPR_EQ:
                return $x == $y;
            case self::OPR_NEQ:
                return $x != $y;
            default:;
        }
        return false;
    }

    /**
     * @brief comparer for any type
     * @param $x
     * @param $y
     * @param $strOpr
     * @return bool
     */
    protected static function _compareAny($x, $y, $strOpr)
    {
        switch ($strOpr) {
            case self::OPR_GT:
                return $x > $y;
            case self::OPR_GTE:
                return $x >= $y;
            case self::OPR_LT:
                return $x < $y;
            case self::OPR_LTE:
                return $x <= $y;
            case self::OPR_EQ:
                return $x == $y;
            case self::OPR_NEQ:
                return $x != $y;
            case self::OPR_IN:
                return in_array($x, (array)$y);
            default:;
        }

        if (is_callable($strOpr)) {
            return call_user_func($strOpr, $x, $y);
        }

        return false;
    }

    /**
     * @brief check a key with the key's rule
     * @param $arrInput
     * @param $strKey
     * @param $arrKeyRule
     * @param $arrOtp
     * @return array
     */
    protected static function _checkKeyRule($arrInput, $strKey, $arrKeyRule, $arrOtp)
    {
        $bolRet = true;
        $strMsg = '';

        if (empty($arrKeyRule)) {
            return [$bolRet, $strMsg, $arrInput];
        }

        $arrKeyRules = isset($arrKeyRule[0]) ? $arrKeyRule : [$arrKeyRule];
        $arrMsg = [];
        foreach ($arrKeyRules as $arrKeyRule) {
            $bolRet = true;
            foreach (self::$RULE_HANDLERS as $strRule => $strHandler) {
                if (!isset($arrKeyRule[$strRule])) {
                    continue;
                }
                list ($bolRet, $strMsg, $arrInput) = call_user_func([__CLASS__, $strHandler],
                    $arrInput, $strKey, $arrKeyRule, $arrOtp);
                if (!$bolRet) {
                    $arrMsg[] = $strMsg;
                    break;
                }
            }
            if ($bolRet) {
                break;
            }
        }

        if (!$bolRet) {
            if (count($arrMsg) > 1) {
                $strMsg = implode('; ', array_map(function($k, $strMsg) {
                    return "with key rule $k, $strMsg";
                }, array_keys($arrMsg), array_values($arrMsg)));
            }
            $strMsg = "key $strKey ($strMsg)";
        }
        return [$bolRet, $strMsg, $arrInput];
    }

    /**
     * @brief check the input with a rule
     * @param $arrInput
     * @param $arrRule
     * @param $arrOtp
     * @return array
     */
    protected static function _checkRule($arrInput, $arrRule, $arrOtp)
    {
        $bolRet = true;
        $strMsg = '';

        if (empty($arrRule)) {
            return [$bolRet, $strMsg, $arrInput];
        }

        $arrRules = isset($arrRule[0]) ? $arrRule : [$arrRule];
        $arrMsg = [];
        foreach ($arrRules as $arrRule) {
            $bolRet = true;
            if (isset($arrRule[self::KEY_ALL])) {
                // all key with a same key rule
                foreach (array_keys($arrInput) as $strKey) {
                    list ($bolRet, $strMsg, $arrInput) =  self::_checkKeyRule($arrInput, $strKey, $arrRule[self::KEY_ALL], $arrOtp);
                    if (!$bolRet) {
                        $arrMsg[] = $strMsg;
                        break;
                    }
                }
            } else {
                foreach ($arrRule as $strKey => $arrKeyRule) {
                    list ($bolRet, $strMsg, $arrInput) =  self::_checkKeyRule($arrInput, $strKey, $arrKeyRule, $arrOtp);
                    if (!$bolRet) {
                        $arrMsg[] = $strMsg;
                        break;
                    }
                }
                if ($bolRet && $arrOtp[self::OPT_FILTER]) {
                    $arrInput = array_intersect_key($arrInput, $arrRule);
                }
            }
            if ($bolRet) {
                break;
            }
        }

        if (!$bolRet && count($arrMsg) > 1) {
            $strMsg = implode('; ', array_map(function($k, $strMsg) {
                return "with rule $k, $strMsg";
            }, array_keys($arrMsg), array_values($arrMsg)));
        }
        return [$bolRet, $strMsg, $arrInput];
    }
}
