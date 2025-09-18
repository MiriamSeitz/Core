<?php
namespace exface\Core\Widgets\Parts\Maps;

use exface\Core\CommonLogic\UxonObject;
use exface\Core\Events\Facades\OnFacadeWidgetRendererExtendedEvent;
use exface\Core\Facades\AbstractAjaxFacade\Elements\LeafletTrait;
use exface\Core\Widgets\Map;
use exface\Core\Widgets\Parts\Maps\BaseMaps\GenericUrlTiles;

/**
 * Allows to insert images onto the base Map as a new Layer.
 * 
 * @author Miriam Seitz
 *
 */
class ImageOverlayLayer extends AbstractMapLayer
{
    private $imagePath;

    private $imageBounds;

    /**
     *
     * @param Map $widget
     * @param UxonObject $uxon
     */
    public function __construct(Map $widget, UxonObject $uxon = null)
    {
        parent::__construct($widget, $uxon);
        $widget->getWorkbench()->eventManager()->addListener(OnFacadeWidgetRendererExtendedEvent::getEventName(), [$this, 'onLeafletRendererRegister']);
    }

    /**
     * @return mixed
     */
    public function getImagePath()
    {
        return $this->imagePath;
    }

    /**
     * Path to image to lay onto map.
     *
     * @uxon-property image_path
     * @uxon-type image_path
     *
     * @param mixed $imagePath
     */
    public function setImagePath($imagePath): ImageOverlayLayer
    {
        $this->imagePath = $imagePath;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getImageBounds()
    {
        return $this->imageBounds;
    }

    /**
     * Set bound for image to be overlayed in.
     *
     * @uxon-property image_bounds
     * @uxon-type image_bounds
     * @uxon-tempalte []
     *
     * @param mixed $imageBounds
     */
    public function setImageBounds($imageBounds): ImageOverlayLayer
    {
        $this->imageBounds = $imageBounds;
        return $this;
    }
}