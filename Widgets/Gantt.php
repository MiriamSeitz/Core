<?php
namespace exface\Core\Widgets;

use exface\Core\CommonLogic\UxonObject;
use exface\Core\Widgets\Parts\DataTimeline;
use exface\Core\Widgets\Parts\DataCalendarItem;

/**
 * 
 * 
 * @author Andrej Kabachnik
 *
 */
class Gantt extends DataTree
{
    private $timelinePart = null;
    
    private $taskPart = null;
    
    private $schedulerResourcePart = null;
    
    private $startDate = null;
    
    /**
     *
     * @return DataTimeline
     */
    public function getTimelineConfig() : DataTimeline
    {
        if ($this->timelinePart === null) {
            $this->timelinePart = new DataTimeline($this);
        }
        return $this->timelinePart;
    }
    
    /**
     * Defines the options for the time scale.
     *
     * @uxon-property timeline
     * @uxon-type \exface\Core\Widgets\Parts\DataTimeline
     * @uxon-template {"granularity": ""}
     *
     * @param UxonObject $uxon
     * @return Gantt
     */
    public function setTimeline(UxonObject $uxon) : Gantt
    {
        $this->timelinePart = new DataTimeline($this, $uxon);
        return $this;
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\Core\Widgets\Data::exportUxonObject()
     */
    public function exportUxonObject()
    {
        $uxon = parent::exportUxonObject();
        $uxon->setProperty('timeline', $this->getTimelineConfig()->exportUxonObject());
        $uxon->setProperty('tasks', $this->getTaskConfig()->exportUxonObject());
        return $uxon;
    }
    
    /**
     *
     * @return DataCalendarItem
     */
    public function getTasksConfig() : DataCalendarItem
    {
        if ($this->taskPart === null) {
            $this->taskPart = new DataCalendarItem($this);
        }
        return $this->taskPart;
    }
    
    /**
     * Defines, what data the calendar tasks should show.
     *
     * @uxon-property tasks
     * @uxon-type \exface\Core\Widgets\Parts\DataCalendarItem
     * @uxon-template {"start_time": ""}
     *
     * @param DataCalendarItem $uxon
     * @return Gantt
     */
    public function setTasks(UxonObject $uxon) : Gantt
    {
        $this->taskPart = new DataCalendarItem($this, $uxon);
        return $this;
    }
    
    /**
     * Same as setTasks() - just for better compatibility with Scheduler widget.
     * @param UxonObject $uxon
     * @return Gantt
     */
    protected function setItems(UxonObject $uxon) : Gantt
    {
        return $this->setTasks($uxon);
    }
    
    public function getStartDate() : ?string
    {
        return $this->startDate;
    }
    
    /**
     * The left-most date in the scheduler: can be a real date or a relative date - e.g. `-2w`.
     *
     * If not set, the date of the first task will be used.
     *
     * @uxon-property start_date
     * @uxon-type string
     *
     * @param string $value
     * @return Gantt
     */
    public function setStartDate(string $value) : Gantt
    {
        $this->startDate = $value;
        return $this;
    }
}