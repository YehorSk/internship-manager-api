<?php

namespace App\Enums;

enum PracticeStatusEnum: string
{
    case CREATED = 'created';
    case AGREEMENT_CONFIRM_REQUESTED = 'agreement_confirm_requested';
    case AGREEMENT_CONFIRMED_BY_COMPANY = 'agreement_confirmed_by_company';
    case AGREEMENT_CONFIRMED_BY_SUPERVISOR = 'agreement_confirmed_by_supervisor';
    case AGREEMENT_REJECTED_BY_COMPANY = 'agreement_rejected_by_company';
    case AGREEMENT_REJECTED_BY_SUPERVISOR = 'agreement_rejected_by_supervisor';
    case REPORT_CONFIRM_REQUESTED = 'report_confirm_requested';
    case REPORT_CONFIRMED_BY_COMPANY = 'report_confirmed_by_company';
    case REPORT_CONFIRMED_BY_SUPERVISOR = 'report_confirmed_by_supervisor';
    case REPORT_REJECTED_BY_COMPANY = 'report_rejected_by_company';
    case REPORT_REJECTED_BY_SUPERVISOR = 'report_rejected_by_supervisor';
    case CANCELED = 'canceled';
}

