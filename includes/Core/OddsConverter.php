<?php
/**
 * Odds Converter Class
 *
 * Converts odds between different formats: decimal, fractional, and American.
 * Implements mathematical conversions with high precision.
 *
 * @package OddsComparison\Core
 * @since 1.0.0
 */

namespace OddsComparison\Core;

/**
 * Class OddsConverter
 *
 * Provides static methods for converting between odds formats.
 */
class OddsConverter {
    
    /**
     * Convert decimal odds to fractional format.
     *
     * @param float $decimal Decimal odds (e.g., 2.5).
     * @return string Fractional odds (e.g., "3/2").
     */
    public static function decimal_to_fractional($decimal) {
        if ($decimal <= 1) {
            return '0/1';
        }
        
        $decimal = $decimal - 1;
        
        // Find greatest common divisor.
        $precision = 100;
        $numerator = round($decimal * $precision);
        $denominator = $precision;
        
        $gcd = self::gcd($numerator, $denominator);
        
        $numerator = $numerator / $gcd;
        $denominator = $denominator / $gcd;
        
        return sprintf('%d/%d', $numerator, $denominator);
    }
    
    /**
     * Convert decimal odds to American format.
     *
     * @param float $decimal Decimal odds (e.g., 2.5).
     * @return string American odds (e.g., "+150" or "-200").
     */
    public static function decimal_to_american($decimal) {
        if ($decimal >= 2) {
            $american = ($decimal - 1) * 100;
            return '+' . round($american);
        } else {
            $american = -100 / ($decimal - 1);
            return round($american);
        }
    }
    
    /**
     * Convert fractional odds to decimal format.
     *
     * @param string $fractional Fractional odds (e.g., "3/2").
     * @return float Decimal odds (e.g., 2.5).
     */
    public static function fractional_to_decimal($fractional) {
        if (!preg_match('/^(\d+)\/(\d+)$/', $fractional, $matches)) {
            return 1.0;
        }
        
        $numerator = (float) $matches[1];
        $denominator = (float) $matches[2];
        
        if ($denominator == 0) {
            return 1.0;
        }
        
        return round(($numerator / $denominator) + 1, 2);
    }
    
    /**
     * Convert fractional odds to American format.
     *
     * @param string $fractional Fractional odds (e.g., "3/2").
     * @return string American odds (e.g., "+150").
     */
    public static function fractional_to_american($fractional) {
        $decimal = self::fractional_to_decimal($fractional);
        return self::decimal_to_american($decimal);
    }
    
    /**
     * Convert American odds to decimal format.
     *
     * @param string|int $american American odds (e.g., "+150" or "-200").
     * @return float Decimal odds (e.g., 2.5).
     */
    public static function american_to_decimal($american) {
        $american = (int) $american;
        
        if ($american > 0) {
            return round(($american / 100) + 1, 2);
        } else {
            return round((100 / abs($american)) + 1, 2);
        }
    }
    
    /**
     * Convert American odds to fractional format.
     *
     * @param string|int $american American odds (e.g., "+150").
     * @return string Fractional odds (e.g., "3/2").
     */
    public static function american_to_fractional($american) {
        $decimal = self::american_to_decimal($american);
        return self::decimal_to_fractional($decimal);
    }
    
    /**
     * Convert odds from one format to another.
     *
     * @param mixed $odds Odds value.
     * @param string $from_format Source format (decimal, fractional, american).
     * @param string $to_format Target format (decimal, fractional, american).
     * @return mixed Converted odds.
     */
    public static function convert($odds, $from_format, $to_format) {
        if ($from_format === $to_format) {
            return $odds;
        }
        
        $method = strtolower($from_format) . '_to_' . strtolower($to_format);
        
        if (method_exists(self::class, $method)) {
            return self::$method($odds);
        }
        
        return $odds;
    }
    
    /**
     * Format odds based on preferred format.
     *
     * @param mixed $odds Odds value (in any format).
     * @param string $format Desired format (decimal, fractional, american).
     * @param string $current_format Current format of odds.
     * @return mixed Formatted odds.
     */
    public static function format($odds, $format, $current_format = 'decimal') {
        return self::convert($odds, $current_format, $format);
    }
    
    /**
     * Calculate implied probability from decimal odds.
     *
     * @param float $decimal Decimal odds.
     * @return float Implied probability as percentage (0-100).
     */
    public static function calculate_implied_probability($decimal) {
        if ($decimal <= 0) {
            return 0;
        }
        
        return round((1 / $decimal) * 100, 2);
    }
    
    /**
     * Calculate potential profit from stake and decimal odds.
     *
     * @param float $stake Stake amount.
     * @param float $decimal Decimal odds.
     * @return float Potential profit.
     */
    public static function calculate_profit($stake, $decimal) {
        return round($stake * ($decimal - 1), 2);
    }
    
    /**
     * Calculate potential return from stake and decimal odds.
     *
     * @param float $stake Stake amount.
     * @param float $decimal Decimal odds.
     * @return float Potential return (stake + profit).
     */
    public static function calculate_return($stake, $decimal) {
        return round($stake * $decimal, 2);
    }
    
    /**
     * Find greatest common divisor (GCD) using Euclidean algorithm.
     *
     * @param int $a First number.
     * @param int $b Second number.
     * @return int Greatest common divisor.
     */
    private static function gcd($a, $b) {
        $a = abs($a);
        $b = abs($b);
        
        while ($b != 0) {
            $temp = $b;
            $b = $a % $b;
            $a = $temp;
        }
        
        return $a;
    }
    
    /**
     * Validate if odds value is valid for given format.
     *
     * @param mixed $odds Odds value.
     * @param string $format Format to validate against.
     * @return bool True if valid, false otherwise.
     */
    public static function is_valid($odds, $format) {
        switch ($format) {
            case 'decimal':
                return is_numeric($odds) && $odds >= 1;
                
            case 'fractional':
                return preg_match('/^\d+\/\d+$/', $odds) === 1;
                
            case 'american':
                $odds = (int) $odds;
                return $odds != 0;
                
            default:
                return false;
        }
    }
}


