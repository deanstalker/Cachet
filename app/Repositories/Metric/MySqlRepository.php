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

class MySqlRepository implements MetricInterface
{
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
            $queryType = 'SUM(mp.`value` * mp.`counter`) AS `value`';
        } elseif ($metric->calc_type == Metric::CALC_AVG) {
            $queryType = 'AVG(mp.`value` * mp.`counter`) AS `value`';
        }

        $sql = <<<SQL_FRAGMENT
        SELECT DATE_FORMAT(mp.`created_at`, '%H:%i') AS pointKey, {$queryType} 
        FROM metrics m
            INNER JOIN metric_points mp ON m.id = mp.metric_id
        WHERE m.id = :metricId 
            AND DATE_SUB(:milestone, INTERVAL 61 MINUTE) <= mp.`created_at`
        GROUP BY HOUR(mp.`created_at`), MINUTE(mp.`created_at`)
        LIMIT 1, 61
SQL_FRAGMENT;

        return DB::select($sql,
            [
                'metricId' => $metric->id,
                'milestone' => $milestone->format('Y-m-d H:i')
            ]
        );


    }

    /**
     * {@inheritdoc}
     */
    public function getPointsForLastXHours(Metric $metric, \DateTime $milestone, $hours = 0)
    {
        if (!isset($metric->calc_type) || $metric->calc_type == Metric::CALC_SUM) {
            $queryType = 'SUM(mp.`value` * mp.`counter`) AS `value`';
        } elseif ($metric->calc_type == Metric::CALC_AVG) {
            $queryType = 'AVG(mp.`value` * mp.`counter`) AS `value`';
        }

        $sql = <<<SQL_FRAGMENT
        SELECT DATE_FORMAT(mp.`created_at`, '%H:00') AS pointKey, {$queryType} FROM metrics m
            INNER JOIN metric_points mp ON m.id = mp.metric_id
            WHERE m.id = :metricId
                AND DATE_SUB(:milestone, INTERVAL :hours HOUR) <= mp.`created_at`
        GROUP BY HOUR(mp.`created_at`)
        LIMIT 1, :hoursLimit           
SQL_FRAGMENT;

        $hours++;

        return DB::select(
            $sql,
            [
                'metricId'   => $metric->id,
                'milestone' => $milestone->format('Y-m-d H:i:s'),
                'hours'      => $hours,
                'hoursLimit' => $hours
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getPointsForLastXDays(Metric $metric, \DateTime $milestone, $days = 0)
    {
        if (!isset($metric->calc_type) || $metric->calc_type == Metric::CALC_SUM) {
            $queryType = 'SUM(mp.`value` * mp.`counter`) AS `value`';
        } elseif ($metric->calc_type == Metric::CALC_AVG) {
            $queryType = 'AVG(mp.`value` * mp.`counter`) AS `value`';
        }

        $sql = <<<SQL_FRAGMENT
SELECT DATE_FORMAT(mp.`created_at`, '%a %D %b') AS pointKey, AVG(mp.`value` * mp.`counter`) AS `value` FROM metrics m
  INNER JOIN metric_points mp ON m.id = mp.metric_id
WHERE m.id = :metricId
      AND DATE_SUB(:milestone, INTERVAL :days DAY) <= mp.`created_at`
GROUP BY DATE_FORMAT(mp.`created_at`, '%a %D %b')
LIMIT 1, :daysLimit
SQL_FRAGMENT;

        $days++;

        return DB::select(
            $sql,
            [
                'metricId'  => $metric->id,
                'milestone' => $milestone,
                'days'      => $days,
                'daysLimit' => $days
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
        $hourInterval = $dateTime->format('YmdH');

        if (!isset($metric->calc_type) || $metric->calc_type == Metric::CALC_SUM) {
            $queryType = 'SUM(mp.`value` * mp.`counter`) AS `value`';
        } elseif ($metric->calc_type == Metric::CALC_AVG) {
            $queryType = 'AVG(mp.`value` * mp.`counter`) AS `value`';
        }

        $value = 0;

        $points = DB::select("SELECT {$queryType} FROM metrics m INNER JOIN metric_points mp ON m.id = mp.metric_id WHERE m.id = :metricId AND DATE_FORMAT(mp.`created_at`, '%Y%m%d%H') = :hourInterval GROUP BY HOUR(mp.`created_at`)", [
            'metricId'     => $metric->id,
            'hourInterval' => $hourInterval,
        ]);

        if (isset($points[0]) && !($value = $points[0]->value)) {
            $value = 0;
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

        if (!isset($metric->calc_type) || $metric->calc_type == Metric::CALC_SUM) {
            $queryType = 'SUM(mp.`value` * mp.`counter`) AS `value`';
        } elseif ($metric->calc_type == Metric::CALC_AVG) {
            $queryType = 'AVG(mp.`value` * mp.`counter`) AS `value`';
        }

        $value = 0;

        $points = DB::select("SELECT {$queryType} FROM metrics m INNER JOIN metric_points mp ON m.id = mp.metric_id WHERE m.id = :metricId AND mp.`created_at` BETWEEN DATE_SUB(mp.`created_at`, INTERVAL 1 WEEK) AND DATE_ADD(NOW(), INTERVAL 1 DAY) AND DATE_FORMAT(mp.`created_at`, '%Y%m%d') = :timeInterval GROUP BY DATE_FORMAT(mp.`created_at`, '%Y%m%d')", [
            'metricId'     => $metric->id,
            'timeInterval' => $dateTime->format('Ymd'),
        ]);

        if (isset($points[0]) && !($value = $points[0]->value)) {
            $value = 0;
        }

        if ($value === 0 && $metric->default_value != $value) {
            return $metric->default_value;
        }

        return round($value, $metric->places);
    }
}
