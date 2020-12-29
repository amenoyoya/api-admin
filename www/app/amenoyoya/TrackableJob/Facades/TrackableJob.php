<?php

namespace Amenoyoya\TrackableJob\Facades;

use \Illuminate\Support\Facades\Redis;

class TrackableJob
{
    /**
     * TrackableJob の状態を Redis サーバに保存しておく期間を取得
     * @note TRACKABLE_JOB_LIFETIME 環境変数で設定可能
     *
     * @return int $lifetime (minutes)
     */
    public static function getLifeTime()
    {
        // default: 10 days
        return env('TRACKABLE_JOB_LIFETIME', 60 * 24 * 10);
    }

    /**
     * Job を Queue にスタックする
     * @note Queue にスタックされた Job を実行するには artisan queue:work で QueueWorker を起動する
     *
     * @param Amenoyoya\TrackableJob\Traits\Trackable $job
     * @return string $jobStatusId
     */
    public static function dispatch($job)
    {
        dispatch($job);
        return $job->getJobStatusId();
    }

    /**
     * 指定の JobStatusId に紐付けられた Queue Job の状態を取得
     *
     * @param string $jobStatusId
     * @return object|null {status, name, id, created_at, updated_at}
     */
    public static function getJobStatus($jobStatusId)
    {
        try {
            return json_decode(Redis::get("trackable_queue_job.$jobStatusId"));
        } catch (Exception $err) {
            return null;
        }
    }

    /**
     * Job 状態管理用 ID を新規発行し、Redis データベースに状態保存
     *
     * @return string $jobStatusId
     */
    public static function initializeJobStatus()
    {
        $jobStatusId = bin2hex(random_bytes(8));
        Redis::set("trackable_queue_job.$jobStatusId", json_encode([
            'status' => 'queueing',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]));
        // TRACKABLE_JOB_LIFETIME 分間データを維持する
        Redis::expire("trackable_queue_job.$jobStatusId", self::getLifeTime() * 60);
        return $jobStatusId;
    }

    /**
     * Job 状態を更新
     *
     * @param JobProcessing|JobProcessed|JobFailed|JobExceptionOccurred $event
     * @param string $status
     * @return object|bool
     */
    public static function updateJobStatus($event, $status)
    {
        // JobStatusId 取得
        $payload = $event->job->payload();
        $job = unserialize($payload['data']['command']);
        $jobStatusId = method_exists($job, 'getJobStatusId')? $job->getJobStatusId(): null;
        if ($jobStatusId === null) {
            return false;
        }
        // Redis に保存された JobStatus を更新
        if (null === ($data = json_decode(Redis::get("trackable_queue_job.$jobStatusId")))) {
            return false;
        }
        $data->name = @$payload['displayName'];
        $data->id = @$payload['id'];
        $data->status = $status;
        $data->updated_at = date('Y-m-d H:i:s');
        Redis::set("trackable_queue_job.$jobStatusId", json_encode($data));
        return $data;
    }
}