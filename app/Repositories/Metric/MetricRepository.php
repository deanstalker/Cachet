<?php

/*
 * This file is part of Cachet.
 *
 * (c) Alt Three Services Limited
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CachetHQ\Cachet\Repositories\Metric;

use CachetHQ\Cachet\Dates\DateFactory;
use CachetHQ\Cachet\Models\Metric;
use DateInterval;

class MetricRepository
{
    /**
     * Metric repository.
     *
     * @var \CachetHQ\Cachet\Repositories\Metric\MetricInterface
     */
    protected $repository;

    /**
     * The date factory instance.
     *
     * @var \CachetHQ\Cachet\Dates\DateFactory
     */
    protected $dates;

    /**
     * Create a new metric repository class.
     *
     * @param \CachetHQ\Cachet\Repositories\Metric\MetricInterface $repository
     * @param \CachetHQ\Cachet\Dates\DateFactory                   $dates
     *
     * @return void
     */
    public function __construct(MetricInterface $repository, DateFactory $dates)
    {
        $this->repository = $repository;
        $this->dates = $dates;
    }

    /**
     * Returns all points as an array, for the last hour.
     *
     * @param \CachetHQ\Cachet\Models\Metric $metric
     *
     * @return array
     */
    public function listPointsLastHour(Metric $metric)
    {
        $dateTime = $this->dates->make();
        $milestone = $this->dates->make();

        $dateTime = new \DateTime('2016-06-07 09:00');
        $milestone = new \DateTime('2016-06-07 09:00');


        $defaultPoints = [
            $dateTime->format('H:i') => round($metric->default_value, $metric->places)
        ];

        for ($i = 0; $i <= 59; $i++) {
            $pointKey = $dateTime->sub(new DateInterval('PT1M'))->format('H:i');
            $defaultPoints[$pointKey] = round($metric->default_value, $metric->places);
        }

        $points = $this->repository->getPointsLastHour($metric, $milestone);
        if (count($points) > 0) {
            foreach ($points as $point) {
                if (isset($defaultPoints[$point->pointKey])) {
                    $defaultPoints[$point->pointKey] = round($point->value, $metric->places);
                }
            }
        }

        return array_reverse($defaultPoints);
    }

    /**
     * Returns all points as an array, by x hours.
     *
     * @param \CachetHQ\Cachet\Models\Metric $metric
     * @param int                            $hours
     *
     * @return array
     */
    public function listPointsToday(Metric $metric, $hours = 12)
    {
        $dateTime = $this->dates->make();
        $milestone = $this->dates->make();
        
        $defaultPoints = [
          $dateTime->format('H:00') => round($metric->default_value, $metric->places)
        ];

        for ($i = 0; $i <= $hours; $i++) {
            $pointKey = $dateTime->sub(new DateInterval('PT1H'))->format('H:00');
            $defaultPoints[$pointKey] = round($metric->default_value, $metric->places);
        }

        $points = $this->repository->getPointsForLastXHours($metric, $milestone, $hours);
        if (count($points) > 0) {
            foreach ($points as $point) {
                if (isset($defaultPoints[$point->pointKey])) {
                    $defaultPoints[$point->pointKey] = round($point->value, $metric->places);
                }
            }
        }

        return array_reverse($defaultPoints);
    }

    /**
     * Returns all points as an array, in the last week.
     *
     * @param \CachetHQ\Cachet\Models\Metric $metric
     *
     * @return array
     */
    public function listPointsForWeek(Metric $metric)
    {
        $dateTime = $this->dates->make();
        $milestone = $this->dates->make();

        $defaultPoints = [
            $dateTime->format('D jS M') => round($metric->default_value, $metric->places)
        ];

        for ($i = 0; $i <= 7; $i++) {
            $pointKey = $dateTime->sub(new DateInterval('P1D'))->format('D jS M');
            $defaultPoints[$pointKey] = round($metric->default_value, $metric->places);
        }

        $points = $this->repository->getPointsForLastXDays($metric, $milestone, 7);
        if (count($points) > 0) {
            foreach ($points as $point) {
                if (isset($defaultPoints[$point->pointKey])) {
                    $defaultPoints[$point->pointKey] = round($point->value, $metric->places);
                }
            }
        }

        return array_reverse($defaultPoints);
    }

    /**
     * Returns all points as an array, in the last month.
     *
     * @param \CachetHQ\Cachet\Models\Metric $metric
     *
     * @return array
     */
    public function listPointsForMonth(Metric $metric)
    {
        $dateTime = $this->dates->make();
        $milestone = $this->dates->make();

        $daysInMonth = $dateTime->format('t');

        $defaultPoints = [
            $dateTime->format('D jS M') => round($metric->default_value, $metric->places)
        ];

        for ($i = 0; $i <= $daysInMonth; $i++) {
            $pointKey = $dateTime->sub(new DateInterval('P1D'))->format('D jS M');
            $defaultPoints[$pointKey] = round($metric->default_value, $metric->places);
        }

        $points = $this->repository->getPointsForLastXDays($metric, $milestone, $daysInMonth);
        if (count($points) > 0) {
            foreach ($points as $point) {
                if (isset($defaultPoints[$point->pointKey])) {
                    $defaultPoints[$point->pointKey] = round($point->value, $metric->places);
                }
            }
        }

        return array_reverse($defaultPoints);
    }
}
