<?php

namespace App\Enums;

enum ReportStatusEnum: string
{
    case PENDING = 'pending';
    case RUNNING = 'running';
    case SUCCESS = 'success';
    case FAILED = 'failed';
}
