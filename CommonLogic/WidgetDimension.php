<?php
namespace exface\Core\CommonLogic;

use exface\Core\Interfaces\WorkbenchInterface;

class WidgetDimension
{
    const MAX = 'max';
    
    private $exface;

    private $value = NULL;

    public function __construct(WorkbenchInterface $exface, string $value = null)
    {
        $this->exface = $exface;
        if ($value !== null) {
            $this->parseDimension($value);
        }
    }

    /**
     * Parses a dimension string.
     * Dimensions may be specified in relative ExFace units (in this case, the value is numeric)
     * or in any unit compatible with the current facade (in this case, the value is alphanumeric because the unit must be
     * specified directltly).
     *
     * How much a relative unit really is, depends on the facade. E.g. a relative height of 1 would mean, that the widget
     * occupies on visual line in the facade (like a simple input), while a relative height of 2 would span the widget over
     * two lines, etc. The same goes for widths.
     *
     * Examples:
     * - "1" - relative height of 1 (e.g. a simple input widget). The facade would need to translate this into a specific height like 300px or similar.
     * - "2" - double relative height (an input with double height).
     * - "0.5" - half relative height (an input with half height)
     * - "300px" - facade specific height defined via the CSS unit "px". This is only compatible with facades, that understand CSS!
     * - "100%" - percentual height. Most facades will support this directly, while others will transform it to relative or facade specific units.
     */
    public function parseDimension($string)
    {
        $this->setValue(trim($string));
    }

    public function toString()
    {
        return $this->value;
    }

    public function getValue()
    {
        return $this->value;
    }

    private function setValue($value)
    {
        $this->value = ($value === '') ? null : $value;
        return $this;
    }

    /**
     * Returns TRUE if the dimension is not defined (null) or FALSE otherwise.
     *
     * @return boolean
     */
    public function isUndefined()
    {
        if (is_null($this->getValue()))
            return true;
        else
            return false;
    }

    /**
     * Returns TRUE if the dimension was specified in relative units and FALSE otherwise.
     *
     * @return boolean
     */
    public function isRelative()
    {
        if (! $this->isUndefined() && (is_numeric($this->getValue()) || $this->isMax()))
            return true;
        else
            return false;
    }

    /**
     * Returns TRUE if the dimension was specified in relative units and equals 'max' and
     * FALSE otherwise.
     *
     * @return boolean
     */
    public function isMax()
    {
        if (! $this->isUndefined() && (strcasecmp($this->getValue(), self::MAX) == 0)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Returns TRUE if the dimension was specified in facade specific units and FALSE otherwise.
     *
     * @return boolean
     */
    public function isFacadeSpecific()
    {
        if (! $this->isUndefined() && ! $this->isPercentual() && ! $this->isRelative())
            return true;
        else
            return false;
    }

    /**
     * Returns TRUE if the dimension was specified in percent and FALSE otherwise.
     *
     * @return boolean
     */
    public function isPercentual()
    {
        if (! $this->isUndefined() && substr($this->getValue(), - 1) == '%')
            return true;
        else
            return false;
    }
    
    /**
     * 
     * @return bool
     */
    public function isAuto() : bool
    {
        return is_string($this->value) && strcasecmp($this->value, 'auto') === 0;
    }
}
?>