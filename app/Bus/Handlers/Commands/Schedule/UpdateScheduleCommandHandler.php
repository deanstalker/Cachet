<?php

/*
 * This file is part of Cachet.
 *
 * (c) Alt Three Services Limited
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CachetHQ\Cachet\Bus\Handlers\Commands\Schedule;

use CachetHQ\Cachet\Bus\Commands\Schedule\UpdateScheduleCommand;
use CachetHQ\Cachet\Bus\Events\Schedule\ScheduleWasUpdatedEvent;
use CachetHQ\Cachet\Dates\DateFactory;
use CachetHQ\Cachet\Models\Schedule;

/**
 * This is the update schedule command handler.
 *
 * @author James Brooks <james@alt-three.com>
 */
class UpdateScheduleCommandHandler
{
    /**
     * The date factory instance.
     *
     * @var \CachetHQ\Cachet\Dates\DateFactory
     */
    protected $dates;

    /**
     * Create a new update schedule command handler instance.
     *
     * @param \CachetHQ\Cachet\Dates\DateFactory $dates
     *
     * @return void
     */
    public function __construct(DateFactory $dates)
    {
        $this->dates = $dates;
    }

    /**
     * Handle the update schedule command.
     *
     * @param \CachetHQ\Cachet\Bus\Commands\Schedule\UpdateScheduleCommand $command
     *
     * @return \CachetHQ\Cachet\Models\Schedule
     */
    public function handle(UpdateScheduleCommand $command)
    {
        $schedule = $command->schedule;

        $schedule->update($this->filter($command));

        event(new ScheduleWasUpdatedEvent($schedule));

        return $schedule;
    }

    /**
     * Filter the command data.
     *
     * @param \CachetHQ\Cachet\Bus\Commands\Schedule\UpdateScheduleCommand $command
     *
     * @return array
     */
    protected function filter(UpdateScheduleCommand $command)
    {
        $params = [
            'name'         => $command->name,
            'message'      => $command->message,
            'status'       => $command->status,
            'scheduled_at' => $command->scheduledAt,
            'completed_at' => $command->completedAt,
        ];

        $availableParams = array_filter($params, function ($val) {
            return $val !== null;
        });

        if (isset($availableParams['scheduled_at'])) {
            $availableParams['scheduled_at'] = $this->dates->create('U', $command->scheduledAt)->format('Y-m-d H:i:s');
        }

        if (isset($availableParams['completed_at'])) {
            $availableParams['completed_at'] = $this->dates->create('U', $command->completedAt)->format('Y-m-d H:i:s');
        }

        return $availableParams;
    }
}
