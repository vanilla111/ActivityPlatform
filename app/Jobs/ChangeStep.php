<?php

namespace App\Jobs;

use App\Models\ApplyData;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class ChangeStep implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels, Queueable;

    private $enroll_id;

    private $act_key;

    private $step;

    private $add;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($enroll_id, $act_key, $step, $add)
    {
        $this->enroll_id = $enroll_id;
        $this->act_key = $act_key;
        $this->step = $step;
        $this->add = $add;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $condition = [
            'enroll_id' => $this->enroll_id,
            'activity_key' => $this->act_key,
            'current_step' => $this->step
        ];
        ApplyData::where($condition)->update(['was_send_sms' => 0]);
        ApplyData::where($condition)->increment('current_step', $this->add);
    }
}
