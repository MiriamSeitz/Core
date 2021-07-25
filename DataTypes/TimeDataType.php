<?php
namespace exface\Core\DataTypes;

use exface\Core\CommonLogic\DataTypes\AbstractDataType;
use exface\Core\Exceptions\DataTypes\DataTypeCastingError;
use exface\Core\Exceptions\DataTypes\DataTypeValidationError;

class TimeDataType extends AbstractDataType
{    
    private $showSeconds = false;
    
    private $amPm = false;
    
    private $format = null;
    
    const TIME_FORMAT_INTERNAL = 'H:i:s';
    
    const TIME_ICU_FORMAT_INTERNAL = 'HH:mm:ss';
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\CommonLogic\DataTypes\AbstractDataType::cast()
     */
    public static function cast($string)
    {
        $string = trim($string);
        
        if ($string === '' || $string === null) {
            return $string;
        }
        
        try {
            $date = new \DateTime($string);
        } catch (\Exception $e) {
            // FIXME add message code with descriptions of valid formats
            throw new DataTypeCastingError('Cannot convert "' . $string . '" to a time!', '74BKHZL', $e);
        }
        return static::formatTimeNormalized($date);
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\CommonLogic\DataTypes\AbstractDataType::parse()
     */
    public function parse($value)
    {
        try {
            return parent::parse($value);
        } catch (DataTypeValidationError | DataTypeCastingError $e) {
            $parsed =  $this->getIntlDateFormatter()->parse($value);
            if ($parsed === false) {
                throw $e;
            }
            return $parsed;
        }
    }
    
    /**
     * 
     * @param string $locale
     * @param string $format
     * @return \IntlDateFormatter
     */
    protected static function createIntlDateFormatter(string $locale, string $format) : \IntlDateFormatter
    {
        return new \IntlDateFormatter($locale, NULL, NULL, NULL, NULL, $format);
    }
    
    /**
     * 
     * @return \IntlDateFormatter
     */
    protected function getIntlDateFormatter() : \IntlDateFormatter
    {
        return self::createIntlDateFormatter($this->getLocale(), $this->getFormat());
    }
    
    /**
     * 
     * @return string
     */
    public function getLocale() : string
    {
        return $this->getWorkbench()->getContext()->getScopeSession()->getSessionLocale();
    }
    
    /**
     * 
     * @param \DateTime $date
     * @return string
     */
    public static function formatTimeNormalized(\DateTime $date)
    {    
        return $date->format(self::TIME_FORMAT_INTERNAL);
    }
    
    /**
     * 
     * @param \DateTime $date
     * @return string
     */
    public function formatTime(\DateTime $date) : string
    {
        return $this->getIntlDateFormatter()->format($date);
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\Core\CommonLogic\DataTypes\AbstractDataType::getDefaultSortingDirection()
     */
    public function getDefaultSortingDirection()
    {
        return SortingDirectionsDataType::DESC($this->getWorkbench());
    }
    
    /**
     * 
     * @return string
     */
    public function getFormatToParseTo() : string
    {
        return self::TIME_ICU_FORMAT_INTERNAL;
    }
    
    /**
     * 
     * @return string
     */
    public function getFormat() : string
    {
        if ($this->format !== null) {
            return $this->format;
        }
        
        $format = $this->getAmPm() ? 'hh:mm' : 'HH:mm';
        if ($this->getShowSeconds() === true) {
            $format .= ':ss';
        }
        if ($this->getAmPm() === true) {
            $format .= ' a';
        }
        return $format;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\CommonLogic\DataTypes\AbstractDataType::getInputFormatHint()
     */
    public function getInputFormatHint() : string
    {
        return $this->getApp()->getTranslator()->translate('LOCALIZATION.DATE.TIME_FORMAT_HINT');
    }
    
    /**
     *
     * @return bool
     */
    public function getShowSeconds() : bool
    {
        return $this->showSeconds;
    }
    
    /**
     * Set to TRUE to show the seconds (has no effect when custom `format` specified!).
     * 
     * @uxon-property show_seconds
     * @uxon-type boolean
     * @uxon-default false
     * 
     * @param bool $value
     * @return TimeDataType
     */
    public function setShowSeconds(bool $value) : TimeDataType
    {
        $this->showSeconds = $value;
        return $this;
    }
    
    /**
     * 
     * @return bool
     */
    public function getAmPm() : bool
    {
        return $this->amPm;
    }
    
    /**
     * Set to TRUE to use the 12-h format with AM/PM (has no effect when custom `format` specified!).
     * 
     * @uxon-property am_pm
     * @uxon-type boolean
     * @uxon-default false
     * 
     * @param bool $value
     * @return TimeDataType
     */
    public function setAmPm(bool $value) : TimeDataType
    {
        $this->amPm = $value;
        return $this;
    }
    
    /**
     * Display format for the time - see ICU formatting: http://userguide.icu-project.org/formatparse/datetime
     *
     * Typical formats are:
     *
     * - `HH:mm` -> 21:00
     * - `HH:mm:ss` -> 21:00:00
     * - `hh:mm a` -> 9:00 pm
     *
     * For most numerical fields, the number of characters specifies the field width. For example, if
     * h is the hour, 'h' might produce '5', but 'hh' produces '05'. For some characters, the count
     * specifies whether an abbreviated or full form should be used, but may have other choices, as
     * given below.
     *
     * Text within single quotes is not interpreted in any way (except for two adjacent single quotes).
     * Otherwise all ASCII letter from a to z and A to Z are reserved as syntax characters, and require
     * quoting if they are to represent literal characters. Two single quotes represents a literal
     * single quote, either inside or outside single quotes.
     *
     * Any characters in the pattern that are not in the ranges of [`a`..`z`] and [`A`..`Z`] will be treated as
     * quoted text. For instance, characters like `:`, `.`, ` `, `#` and `@` will appear in the resulting time text
     * even they are not enclosed within single quotes.The single quote is used to 'escape' letters. Two single quotes
     * in a row, whether inside or outside a quoted sequence, represent a 'real' single quote.
     *
     * ## Available placeholders
     *
     *  | Symbol |    Meaning             |    Example            |    Result         |
     *  | ------ | ----------------- | ----------       | ---------         |
     *  |    a   |    am/pm marker     |    a    |    pm    |
     *  |    h   |    hour in am/pm (1~12)    |    h    |    7    |
     *  |        |        |    hh    |    7    |
     *  |    H   |    hour in day (0~23)    |    H    |    0    |
     *  |        |        |    HH    |    0    |
     *  |    k   |    hour in day (1~24)    |    k    |    24    |
     *  |        |        |    kk    |    24    |
     *  |    K   |    hour in am/pm (0~11)    |    K    |    0    |
     *  |        |        |    KK    |    0    |
     *  |    m   |    minute in hour    |    m    |    4    |
     *  |        |        |    mm    |    4    |
     *  |    s   |    second in minute    |    s    |    5    |
     *  |        |        |    ss    |    5    |
     *  |    S   |    fractional second - truncates (like other time fields)    |    S    |    2    |
     *  |        |    to the count of letters when formatting. Appends    |    SS    |    23    |
     *  |        |    zeros if more than 3 letters specified. Truncates at    |    SSS    |    235    |
     *  |        |    three significant digits when parsing.     |    SSSS    |    2350    |
     *  |    A   |    milliseconds in day    |    A    |    61201235    |
     *  |    '   |    escape for text    |    '    |    (nothing)    |
     *  |    ''  |    two single quotes produce one    |    ' '    |    '    |
     *
     * @uxon-property format
     * @uxon-type string
     * @uxon-template HH:mm:ss
     *
     * @param string $format
     * @return DateDataType
     */
    public function setFormat(string $value) : TimeDataType
    {
        $this->format = $value;
        return $this;
    }
}