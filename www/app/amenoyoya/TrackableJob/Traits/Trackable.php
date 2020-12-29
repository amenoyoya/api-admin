<?php

namespace Amenoyoya\TrackableJob\Traits;

use Amenoyoya\TrackableJob\Facades\TrackableJob;

/**
 * トラッキング可能な Queue Job にするための trait
 * @declare Illuminate\Contracts\Queue\ShouldQueue を継承し Trackable を use する
 * @prepare __construct() で prepareJobStatus() メソッドを実行
 * @usage Job を dispatch 後、getJobStatusId() で ID を取得 => TrackableJob::getJobStatus($id) で状態を確認
 */
trait Trackable
{
    protected $jobStatusId;

    protected function prepareJobStatus()
    {
        $this->jobStatusId = TrackableJob::initializeJobStatus();
    }

    public function getJobStatusId()
    {
        return $this->jobStatusId;
    }
}
