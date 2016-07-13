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

use CachetHQ\Cachet\Models\Metric;

interface MetricInterface
{
    /**
     * Returns metrics for the last hour.
     *
     * @param \CachetHQ\Cachet\Models\Metric $metric
     * @paran \DateTime $milestone
     *
     * @return array
     */
    public function getPointsLastHour(Metric $metric, \DateTime $milestone);

    /**
     * Returns metrics for the last (x) hours
     *
     * @param Metric    $metric
     * @param \DateTime $milestone
     * @param int       $hours
     *
     * @return mixed
     */
    public function getPointsForLastXHours(Metric $metric, \DateTime $milestone, $hours = 0);

    /**
     * Returns metrics for the last (x) days
     *
     * @param Metric    $metric
     * @param \DateTime $milestone
     * @param int       $days
     *
     * @return mixed
     */
    public function getPointsForLastXDays(Metric $metric, \DateTime $milestone, $days = 0);

    /**
     * Returns metrics for a given hour.
     *
     * @param \CachetHQ\Cachet\Models\Metric $metric
     * @param int                            $hour
     *
     * @return int
     */
    public function getPointsByHour(Metric $metric, $hour);

    /**
     * Returns metrics for the week.
     *
     * @param \CachetHQ\Cachet\Models\Metric $metric
     * @param int                            $day
     *
     * @return int
     */
    public function getPointsForDayInWeek(Metric $metric, $day);
}
