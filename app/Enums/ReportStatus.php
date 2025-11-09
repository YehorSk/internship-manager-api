<?php

namespace App\Enums;

enum ReportStatus: string
{
    case PENDING = 'PENDING';
    case RUNNING = 'RUNNING';
    case SUCCESS = 'SUCCESS';
    case FAILED = 'FAILED';
}
