<?php
namespace exface\Core\Facades\AbstractAjaxFacade\Elements;

use exface\Core\Exceptions\Widgets\WidgetConfigurationError;

/**
 *
 * @method \exface\Core\Widgets\Filter getWidget()
 *        
 * @author Andrej Kabachnik
 *        
 */
trait JqueryFilterTrait {

    public function buildJsConditionGetter($valueJs = null)
    {
        $widget = $this->getWidget();
        if ($widget->hasCustomConditionGroup() === true) {
            return '';
        }
        if ($widget->isDisplayOnly() === true) {
            return '';
        }
        $value = is_null($valueJs) ? $this->buildJsValueGetter() : $valueJs;
        if ($widget->getAttributeAlias() === '' || $widget->getAttributeAlias() === null) {
            throw new WidgetConfigurationError($widget, 'Invalid filter configuration for filter "' . $widget->getCaption() . '": missing expression (e.g. attribute_alias)!');
        }
        return '{expression: "' . $widget->getAttributeAlias() . '", comparator: ' . $this->buildJsComparatorGetter() . ', value: ' . $value . ', object_alias: "' . $widget->getMetaObject()->getAliasWithNamespace() . '"}';
    }
    
    public function buildJsCustomConditionGroup($valueJs = null) : string
    {
        $widget = $this->getWidget();
        if ($widget->hasCustomConditionGroup() === false) {
            return '';
        }
        
        $jsonWithValuePlaceholder = $widget->getCustomConditionGroup()->exportUxonObject()->toJson(false);
        return str_replace('"[#value#]"', $valueJs ?? $this->buildJsValueGetter(), $jsonWithValuePlaceholder);
    }
    
    public function buildJsComparatorGetter()
    {
        return '"' . $this->getWidget()->getComparator() . '"';
    }

    public function buildJsValueGetter()
    {
        return $this->getInputElement()->buildJsValueGetter();
    }

    public function buildJsValueGetterMethod()
    {
        return $this->getInputElement()->buildJsValueGetterMethod();
    }

    public function buildJsValueSetter($value)
    {
        return $this->getInputElement()->buildJsValueSetter($value);
    }

    public function buildJsValueSetterMethod($value)
    {
        return $this->getInputElement()->buildJsValueSetterMethod($value);
    }

    public function buildJsInitOptions()
    {
        return $this->getInputElement()->buildJsInitOptions();
    }

    public function getInputElement()
    {
        return $this->getFacade()->getElement($this->getWidget()->getInputWidget());
    }
    
    public function addOnChangeScript($string)
    {
        $this->getInputElement()->addOnChangeScript($string);
        return $this;
    }

    /**
     * Magic method to forward all calls to methods, not explicitly defined in the filter to ist value widget.
     * Thus, the filter is a simple proxy from the point of view of the facade. However, it can be easily
     * enhanced with additional methods, that will override the ones of the value widget.
     * TODO this did not really work so far. Don't know why. As a work around, added some explicit proxy methods
     * -> __call wird aufgerufen, wenn eine un!zugreifbare Methode in einem Objekt aufgerufen wird
     * (werden die ueberschriebenen Proxymethoden entfernt, existieren sie ja aber auch noch EuiInput)
     *
     * @param string $name            
     * @param array $arguments            
     */
    public function __call($name, $arguments)
    {
        return call_user_method_array($name, $this->getInputElement(), $arguments);
    }
    
    /**
     * 
     * {@inheritdoc}
     * @see AbstractJqueryElement::buildJsValidator()
     */
    public function buildJsValidator(?string $valJs = null) : string
    {
        return $this->getInputElement()->buildJsValidator();
    }
    
    /**
     *
     * {@inheritdoc}
     * @see AbstractJqueryElement::buildJsValidationError()
     */
    public function buildJsValidationError()
    {
        return $this->getInputElement()->buildJsValidationError();
    }
    
    /**
     * 
     * {@inheritdoc}
     * @see AbstractJqueryElement::buildJsResetter()
     */
    public function buildJsResetter() : string
    {
        return $this->getInputElement()->buildJsResetter();
    }
}