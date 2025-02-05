<?php
namespace exface\Core\Widgets;

use exface\Core\Interfaces\Widgets\iContainOtherWidgets;
use exface\Core\Interfaces\Widgets\iFillEntireContainer;
use exface\Core\Interfaces\WidgetInterface;
use exface\Core\Factories\WidgetFactory;
use exface\Core\CommonLogic\UxonObject;
use exface\Core\Interfaces\DataSheets\DataSheetInterface;
use exface\Core\Factories\DataPointerFactory;
use exface\Core\Events\Widget\OnPrefillChangePropertyEvent;

/**
 * Configurable form to collect structured data and save it in singe attribute
 * 
 * @author Andrej Kabachnik
 *
 */
class InputForm extends Input implements iFillEntireContainer
{
    private $formConfigAttributeAlias = null;
    
    private $formWidgets = [];
    
    /**
     * 
     * @return string
     */
    public function getFormConfigAttributeAlias() : string
    {
        return $this->formConfigAttributeAlias;
    }
    
    public function isFormConfigBoundToAttribute() : bool
    {
        return $this->formConfigAttributeAlias !== null;
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\Core\Widgets\Value::prepareDataSheetToRead()
     */
    public function prepareDataSheetToRead(DataSheetInterface $data_sheet = null)
    {
        $data_sheet = parent::prepareDataSheetToRead($data_sheet);
        if ($this->isFormConfigBoundToAttribute() === true) {
            $formConfigPrefillExpr = $this->getPrefillExpression($data_sheet, $this->getMetaObject(), $this->getFormConfigAttributeAlias());
            if ($formConfigPrefillExpr!== null) {
                $data_sheet->getColumns()->addFromExpression($formConfigPrefillExpr);
            }
        }
        return $data_sheet;
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\Core\Widgets\Value::doPrefill()
     */
    protected function doPrefill(DataSheetInterface $data_sheet)
    {
        parent::doPrefill($data_sheet);
        if ($this->isFormConfigBoundToAttribute() === true) {
            $formConfigPrefillExpression = $this->getPrefillExpression($data_sheet, $this->getMetaObject(), $this->getFormConfigAttributeAlias());
            if ($formConfigPrefillExpression !== null && $col = $data_sheet->getColumns()->getByExpression($formConfigPrefillExpression)) {
                if (count($col->getValues(false)) > 1 && $this->getAggregator()) {
                    // TODO #OnPrefillChangeProperty
                    $valuePointer = DataPointerFactory::createFromColumn($col);
                    $value = $col->aggregate($this->getAggregator());
                } else {
                    $valuePointer = DataPointerFactory::createFromColumn($col, 0);
                    $value = $valuePointer->getValue();
                }
                // Ignore empty values because if value is a live-references as the ref would get overwritten
                // even without a meaningfull prefill value
                if ($this->isBoundByReference() === false || ($value !== null && $value != '')) {
                    $this->setValue($value, false);
                    $this->dispatchEvent(new OnPrefillChangePropertyEvent($this, 'form_config', $valuePointer));
                }
            }
        }
        return;
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Widgets\iShowDataColumn::getDataColumnName()
     */
    public function getFormConfigDataColumnName()
    {
        return $this->isFormConfigBoundToAttribute() ? DataColumn::sanitizeColumnName($this->getFormConfigAttributeAlias()) : $this->getDataColumnName();
    }
    
    /**
     * Alias of the attribute containing the configuration for the form to be rendered
     * 
     * @uxon-property form_config_attribute_alias
     * @uxon-type metamodel:attribute
     * @uxon-required true
     * 
     * @param string $value
     * @return InputForm
     */
    public function setFormConfigAttributeAlias(string $value) : InputForm
    {
        $this->formConfigAttributeAlias = $value;
        return $this;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Widgets\iFillEntireContainer::getAlternativeContainerForOrphanedSiblings()
     */
    public function getAlternativeContainerForOrphanedSiblings(): ?iContainOtherWidgets
    {
        return null;
    }
    
    /**
     * 
     * @return WidgetInterface[]
     */
    public function getFormWidgets() : array
    {
        return $this->formWidgets;
    }
    
    /**
     * 
     * @param WidgetInterface $widget
     * @return InputForm
     */
    protected function addFormWidget(WidgetInterface $widget) : InputForm
    {
        $this->formWidgets = $widget;
        return $this;
    }
    
    /**
     * Custom widgets like InputSelect or InputComboTable, that should be available as reusable form elements
     * 
     * @uxon-property widgets
     * @uxon-type \exface\Core\Widgets\AbstractWidget[]
     * @uxon-template [{"widget_type": ""}]
     *
     * @param WidgetInterface[]|UxonObject $widgetOrUxonArray
     * @return InputForm
     */
    public function setFormWidgets($widgetOrUxonArray) : InputForm
    {
        foreach ($widgetOrUxonArray as $w) {
            if ($w instanceof WidgetInterface) {
                $this->addFormWidget($w);
            } else {
                $widget = WidgetFactory::createFromUxonInParent($this, $w);
                $this->addFormWidget($widget);
            }
        }
        return $this;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Widgets\AbstractWidget::getChildren()
     */
    public function getChildren() : \Iterator
    {
        foreach ($this->getFormWidgets() as $child) {
            yield $child;
        }
    }
}