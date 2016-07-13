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
use DateInterval;
use Illuminate\Support\Facades\DB;
use Jenssegers\Date\Date;

class SqliteRepository implements MetricInterface
{
    public function getPointsForLastXDays(Metric $metric, \DateTime $milestone, $days = 0)
    {
        
    }

    /**
     * Returns metrics for the last hour.
     *
     * @param \CachetHQ\Cachet\Models\Metric $metric
     * @param \DateTime $milestone
     *
     * @return int
     */
    public function getPointsLastHour(Metric $metric, \DateTime $milestone)
    {
        if (!isset($metric->calc_type) || $metric->calc_type == Metric::CALC_SUM) {
            $queryType = 'SUM(metric_points.`value` * metric_points.`counter`) AS `value`';
        } elseif ($metric->calc_type == Metric::CALC_AVG) {
            $queryType = 'AVG(metric_points.`value` * metric_points.`counter`) AS `value`';
        }

        $sql = <<<SQL_FRAGMENT
        SELECT strftime('%H:%M', metric_points.created_at) AS pointKey, {$queryType} 
        FROM metrics
            INNER JOIN metric_points ON metrics.id = metric_points.metric_id
        WHERE metrics.id = :metricId 
            AND datetime(:milestone, '-61 minute') <= metric_points.created_at            
        GROUP BY strftime('%H', metric_points.created_at), strftime('%M', metric_points.created_at)
        LIMIT 61 OFFSET 1
SQL_FRAGMENT;

        return DB::select($sql,
            [
                'metricId' => $metric->id,
                'milestone' => $milestone->format('Y-m-d H:i')
            ]
        );

    }

    public function getPointsForLastXHours(Metric $metric, \DateTime $milestone, $hours = 0)
    {
        if (!isset($metric->calc_type) || $metric->calc_type == Metric::CALC_SUM) {
            $queryType = 'SUM(metric_points.value * metric_points.counter) AS `value`';
        } elseif ($metric->calc_type == Metric::CALC_AVG) {
            $queryType = 'AVG(metric_points.value * metric_points.counter) AS value';
        }

        $sql = <<<SQL_FRAGMENT
        SELECT strftime('%H:00', metric_points.created_at) AS pointKey, {$queryType} 
        FROM metrics 
            INNER JOIN metric_points ON metrics.id = metric_points.metric_id
            WHERE metrics.id = :metricId
                AND datetime(:milestone, '-:hours HOUR') <= metric_points.`created_at`
        GROUP BY strftime('%H', metric_points.created_at)
        LIMIT :hoursLimit OFFSET 1           
SQL_FRAGMENT;

        $hours++;

        return DB::select(
            $sql,
            [
                'metricId'   => $metric->id,
                'milestone'  => $milestone->format('Y-m-d H:i:s'),
                'hours'      => $hours,
                'hoursLimit' => $hours
            ]);
    }

    /**
     * Returns metrics for a given hour.
     *
     * @param \CachetHQ\Cachet\Models\Metric $metric
     * @param int                            $hour
     *
     * @return int
     */
    public function getPointsByHour(Metric $metric, $hour)
    {
        $dateTime = (new Date())->sub(new DateInterval('PT'.$hour.'H'));

        // Default metrics calculations.
        if (!isset($metric->calc_type) || $metric->calc_type == Metric::CALC_SUM) {
            $queryType = 'sum(metric_points.value * metric_points.counter)';
        } elseif ($metric->calc_type == Metric::CALC_AVG) {
            $queryType = 'avg(metric_points.value * metric_points.counter)';
        } else {
            $queryType = 'sum(metric_points.value * metric_points.counter)';
        }

        $value = 0;
        $query = DB::select("select {$queryType} as value FROM metrics JOIN metric_points ON metric_points.metric_id = metrics.id WHERE metrics.id = :metricId AND strftime('%Y%m%d%H', metric_points.created_at) = :timeInterval GROUP BY strftime('%H', metric_points.created_at)", [
            'metricId'     => $metric->id,
            'timeInterval' => $dateTime->format('YmdH'),
        ]);

        if (isset($query[0])) {
            $value = $query[0]->value;
        }

        if ($value === 0 && $metric->default_value != $value) {
            return $metric->default_value;
        }

        return round($value, $metric->places);
    }

    /**
     * Returns metrics for the week.
     *
     * @param \CachetHQ\Cachet\Models\Metric $metric
     *
     * @return int
     */
    public function getPointsForDayInWeek(Metric $metric, $day)
    {
        $dateTime = (new Date())->sub(new DateInterval('P'.$day.'D'));

        // Default metrics calculations.
        if (!isset($metric->calc_type) || $metric->calc_type == Metric::CALC_SUM) {
            $queryType = 'sum(metric_points.value * metric_points.counter)';
        } elseif ($metric->calc_type == Metric::CALC_AVG) {
            $queryType = 'avg(metric_points.value * metric_points.counter)';
        } else {
            $queryType = 'sum(metric_points.value * metric_points.counter)';
        }

        $value = 0;
        $query = DB::select("select {$queryType} as value FROM metrics JOIN metric_points ON metric_points.metric_id = metrics.id WHERE metrics.id = :metricId AND metric_points.created_at > date('now', '-7 day') AND strftime('%Y%m%d', metric_points.created_at) = :timeInterval GROUP BY strftime('%Y%m%d', metric_points.created_at)", [
            'metricId'     => $metric->id,
            'timeInterval' => $dateTime->format('Ymd'),
        ]);

        if (isset($query[0])) {
            $value = $query[0]->value;
        }

        if ($value === 0 && $metric->default_value != $value) {
            return $metric->default_value;
        }

        return round($value, $metric->places);
    }
}
