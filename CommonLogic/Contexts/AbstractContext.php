<?php
namespace exface\Core\CommonLogic\Contexts;

use exface\Core\CommonLogic\UxonObject;
use exface\Core\Interfaces\Contexts\ContextInterface;
use exface\Core\Interfaces\Contexts\ContextScopeInterface;
use exface\Core\Exceptions\Contexts\ContextRuntimeError;
use exface\Core\Widgets\Container;
use exface\Core\CommonLogic\Constants\Colors;
use exface\Core\Interfaces\Selectors\ContextSelectorInterface;
use exface\Core\CommonLogic\Traits\AliasTrait;
use exface\Core\Interfaces\Tasks\ResultInterface;
use exface\Core\Interfaces\Tasks\TaskInterface;
use exface\Core\Factories\TaskFactory;
use exface\Core\Factories\ResultFactory;

/**
 * This is a basic implementation of common context methods intended to be used
 * as the base for "real" contexts.
 * 
 * @author Andrej Kabachnik
 *
 */
abstract class AbstractContext implements ContextInterface
{
    use AliasTrait;

    private $selector = null;
    
    private $workbench = null;

    private $scope = null;

    private $alias = null;
    
    private $indicator = null;
    
    private $indicator_color = null;
    
    private $icon = null;
    
    private $name = null;
    
    private $context_bar_visibility = null;
    
    /**
     * @deprecated use ContextFactory instead
     */
    public function __construct(ContextSelectorInterface $selector)
    {
        $this->selector = $selector;
        $this->workbench = $selector->getWorkbench();
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Contexts\ContextInterface::getSelector()
     */
    public function getSelector() : ContextSelectorInterface
    {
        return $this->selector;
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Contexts\ContextInterface::getScope()
     */
    public function getScope()
    {
        return $this->scope;
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Contexts\ContextInterface::setScope()
     */
    public function setScope(ContextScopeInterface $context_scope)
    {
        $this->scope = $context_scope;
        return $this;
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Contexts\ContextInterface::getDefaultScope()
     */
    public function getDefaultScope()
    {
        return $this->getWorkbench()->getContext()->getScopeWindow();
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\WorkbenchDependantInterface::getWorkbench()
     */
    public function getWorkbench()
    {
        return $this->workbench;
    }

    /**
     * Returns a serializable UXON object, that represents the current contxt, 
     * thus preparing it to be saved in a session, cookie, database or whatever 
     * is used by a context scope.
     * 
     * What exactly ist to be saved, strongly depends on the context type: an 
     * action context needs an acton alias and, perhaps, a data backup, a filter 
     * context needs to save it's filters conditions, etc. In any case, the 
     * serialized version should contain enough data to restore the context 
     * completely afterwards, but also not to much data in order not to consume 
     * too much space in whatever stores the respective context scope.
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\iCanBeConvertedToUxon::exportUxonObject()
     */
    public function exportUxonObject()
    {
        return new UxonObject();
    }

    /**
     * Restores a context from it's UXON representation.
     * 
     * The input is whatever export_uxon_object() produces for this context type.
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\iCanBeConvertedToUxon::importUxonObject()
     * @return ContextInterface
     */
    public function importUxonObject(UxonObject $uxon)
    {
        return $this;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Contexts\ContextInterface::getIndicator()
     */
    public function getIndicator()
    {
        return $this->indicator;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Contexts\ContextInterface::setIndicator()
     */
    public function setIndicator($indicator)
    {
        $this->indicator = $indicator;
        return $this;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Contexts\ContextInterface::getVisibility()
     */
    public function getVisibility()
    {
        if (is_null($this->context_bar_visibility)){
            $this->setVisibility(ContextInterface::CONTEXT_BAR_SHOW_IF_NOT_EMPTY);
        }
        return $this->context_bar_visibility;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Contexts\ContextInterface::setVisibility()
     */
    public function setVisibility($value)
    {
        if (! defined('static::CONTEXT_BAR_' . mb_strtoupper($value))) {
            throw new ContextRuntimeError($this, 'Invalid context_bar_visibility value "' . $value . '" for context "' . $this->getAliasWithNamespace() . '"!');
        }
        $this->context_bar_visibility = constant('static::CONTEXT_BAR_' . mb_strtoupper($value));
        return $this;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Contexts\ContextInterface::isEmpty()
     */
    public function isEmpty()
    {
        return $this->active;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Contexts\ContextInterface::getContextBarPopup()
     */
    public function getContextBarPopup(Container $container)
    {
        return $container;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Contexts\ContextInterface::getIcon()
     */
    public function getIcon()
    {
        return $this->icon;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Contexts\ContextInterface::setIcon()
     */
    public function setIcon($icon)
    {
        $this->icon = $icon;
        return $this;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Contexts\ContextInterface::getName()
     */
    public function getName()
    {
        return $this->name;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Contexts\ContextInterface::setName()
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Contexts\ContextInterface::getColor()
     */
    public function getColor()
    {
        if (is_null($this->indicator_color)){
            return Colors::DEFAULT_COLOR;
        }
        return $this->indicator_color;
    }

    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Contexts\ContextInterface::setColor()
     */
    public function setColor($indicator_color)
    {
        $this->indicator_color = $indicator_color;
        return $this;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Contexts\ContextInterface::getApp()
     */
    public function getApp(){
        return $this->getWorkbench()->getApp($this->selector->getAppSelector());
    }
    
    /**
     *
     * {@inheritdoc}
     * @see \exface\Core\Interfaces\iCanBeConvertedToUxon::getUxonSchemaClass()
     */
    public static function getUxonSchemaClass() : ?string
    {
        null;
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\TaskHandlerInterface::handle()
     */
    public function handle(TaskInterface $task, string $operation = null): ResultInterface
    {
        if ($operation === null || ! method_exists($this, $operation)){
            throw new ContextRuntimeError($this, 'Invalid operation "' . $operation . '" for context "' . $this->getAlias() . '": method not found!');
        }
        $return_value = call_user_func([$this, $operation]);
        if (is_string($return_value)){
            $result = ResultFactory::createMessageResult($task, $return_value);
        } elseif ($return_value instanceof ContextInterface) {
            $operation_name = ucfirst(strtolower(preg_replace('/(?<!^)[A-Z]/', ' $0', $operation)));
            $result = ResultFactory::createMessageResult($task, $this->getWorkbench()->getCoreApp()->getTranslator()->translate('CONTEXT.API.RESULT', ['%operation_name%' => $operation_name]));
        } else {
            $result = ResultFactory::createTextContentResult($task, $return_value);
        }
        $result->setContextModified(true);
        return $result;
    }
}