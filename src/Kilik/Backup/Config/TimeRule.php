<?php

namespace Kilik\Backup\Config;

use Kilik\Backup\Config\Traits\NameTrait;
use Kilik\Backup\Traits\ConfigTrait;

/**
 * Time Rule configuration
 */
class TimeRule
{
    use NameTrait;
    use ConfigTrait;

    const ALL = '*';

    /**
     * Day of week
     *
     * '*'= all
     * '3' = wednesday
     * '1,7' = monday and sunday
     * @var string
     */
    private $dayOfWeek = '*';

    /**
     * Week of year
     *
     * '*'= all
     * '1' = first week of year
     * '40,42' = 40th and 42th week of year
     *
     * @var string
     */
    private $week = '*';

    /**
     * Day of month
     *
     * '*'= all
     * '1' = first day of month
     * '10,11' = 10th and 11th day of month
     *
     * @var string
     */
    private $day = '*';

    /**
     * Month
     *
     * '*'= all
     * '1' = january
     * '10,11' = october and november
     *
     * @var string
     */
    private $month = '*';

    /**
     * How many time the backup should be kept
     *
     * @var string
     * @see http://php.net/manual/fr/datetime.formats.relative.php
     */
    private $delay = '100 years';

    /**
     * @param array $array
     *
     * @return static
     */
    public function setFromArray($array)
    {
        if (isset($array['day_of_week'])) {
            $this->dayOfWeek = $array['day_of_week'];
        }
        if (isset($array['week'])) {
            $this->week = $array['week'];
        }
        if (isset($array['day'])) {
            $this->day = $array['day'];
        }
        if (isset($array['month'])) {
            $this->month = $array['month'];
        }
        if (isset($array['delay'])) {
            $this->delay = $array['delay'];
        }

        return $this;
    }

    /**
     * Check if a date match this rule
     *
     * @param \DateTime $date
     * @return bool true if date match
     *
     * Controls are done on ISO-8601 conventions
     * @see http://php.net/manual/en/function.date.php
     */
    public function dateMatch(\DateTime $date)
    {
        // if not all days of week
        if ($this->dayOfWeek != '*') {
            $dayOfWeek = explode(',', $this->dayOfWeek);
            if (!in_array($date->format('N'), $dayOfWeek)) {
                return false;
            }
        }

        // if not all weeks
        if ($this->week != '*') {
            $week = explode(',', $this->week);
            if (!in_array($date->format('W'), $week)) {
                return false;
            }
        }

        // if not all days
        if ($this->day != '*') {
            $day = explode(',', $this->day);
            if (!in_array($date->format('j'), $day)) {
                return false;
            }
        }

        // if not all months
        if ($this->month != '*') {
            $month = explode(',', $this->month);
            if (!in_array($date->format('n'), $month)) {
                return false;
            }
        }

        // else, this date match
        return true;
    }

    /**
     * Get a date with corresponding delay
     *
     * @param \DateTime $date
     * @return \DateTime
     */
    public function getDelayDate(\DateTime $date)
    {
        $delay = clone $date;
        $delay->modify('-'.$this->delay);

        return $delay;
    }

}
