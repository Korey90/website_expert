<?php

return [
    'tab_failed'           => 'Failed Jobs',
    'tab_pending'          => 'Pending Jobs',
    'tab_batches'          => 'Job Batches',

    'queue'                => 'Queue',
    'all_queues'           => 'All queues',
    'search'               => 'Search',
    'search_placeholder'   => 'Search by class, UUID, exception…',
    'job_class'            => 'Job class',
    'failed_at'            => 'Failed at',
    'created_at'           => 'Created at',
    'attempts'             => 'Attempts',
    'status'               => 'Status',
    'actions'              => 'Actions',

    'batch_name'           => 'Batch name',
    'progress'             => 'Progress',
    'total'                => 'Total',
    'pending'              => 'Pending',
    'failed_count'         => 'Failed',

    'running'              => 'Running',
    'waiting'              => 'Waiting',
    'cancelled'            => 'Cancelled',
    'finished'             => 'Finished',

    'retry'                => 'Retry',
    'retry_all'            => 'Retry all',
    'flush_all'            => 'Flush all',
    'delete'               => 'Delete',
    'cancel'               => 'Cancel',
    'view_payload'         => 'View payload',
    'view_exception'       => 'View exception',
    'exception'            => 'Exception',

    'page'                 => 'Page',
    'records'              => 'records',
    'prev'                 => 'Prev',
    'next'                 => 'Next',

    'no_failed'            => 'No failed jobs.',
    'no_pending'           => 'No pending jobs in the queue.',
    'no_batches'           => 'No job batches found.',

    'retry_success'        => 'Job queued for retry.',
    'retry_all_success'    => 'All failed jobs queued for retry.',
    'delete_success'       => 'Job deleted.',
    'flush_success'        => 'All failed jobs have been deleted.',
    'batch_cancelled'      => 'Batch cancelled.',
    'batch_not_found'      => 'Batch not found.',

    'confirm_retry'        => 'Retry this job?',
    'confirm_retry_all'    => 'Retry all failed jobs? This will re-queue every failed job.',
    'confirm_delete'       => 'Delete this entry? This action cannot be undone.',
    'confirm_cancel_batch' => 'Cancel this batch? Running jobs will finish, but no new jobs will be dispatched.',

    'flush_confirm_title'  => 'Delete all failed jobs',
    'flush_confirm_body'   => 'This will permanently delete every entry in the failed jobs table. This action cannot be undone.',

    'restart_workers'      => 'Restart workers',
    'restart_success'      => 'Restart signal sent.',
    'restart_body'         => 'Running workers will finish their current job and restart.',
    'restart_confirm_body' => 'Sends a queue:restart signal. Workers will gracefully finish the current job before restarting. No work in progress will be lost.',

    'worker'               => 'Worker',
    'worker_running'       => 'Active',
    'worker_idle'          => 'Inactive',
    'queues_breakdown'     => 'Breakdown by queue',
];
