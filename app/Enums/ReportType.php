<?php

namespace App\Enums;

enum ReportType: string
{
        case PRACTICES_STATUS_SUMMARY = 'practices_status_summary'; // Summary of practice statuses
    case PRACTICES_FUNNEL = 'practices_funnel'; // Funnel of practices through different stages
    case MISSING_DOCUMENTS = 'missing_documents'; // List of practices with missing documents
    case STUDENTS_WITHOUT_PRACTICE = 'students_without_practice'; // Students who have not yet started a practice
    case PRACTICES_BY_COMPANY = 'practices_by_company'; // Practices grouped by company
}
