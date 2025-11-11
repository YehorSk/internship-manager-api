<?php

namespace App\Enums;

enum ReportTypeEnum: string
{
    case PRACTICES_LIST = 'practices_list';
    case PRACTICES_STATUS_SUMMARY = 'practices_status_summary';
    case COMPANIES_WITHOUT_ACTIVATION = 'companies_without_activation';
    case COMPANIES_WITHOUT_PRACTICES = 'companies_without_practices';
}
