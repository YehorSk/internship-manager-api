@php
use App\Enums\RoleEnum;
@endphp

<x-mail::message>

@if($student)
# {{ __('mail.greeting_student', ['name' => $student->first_name]) }}

{{ __('mail.practice_removed_student', ['job_title' => $practice->job_title]) }}
@endif

@if($company)
# {{ __('mail.greeting_student', ['name' => $company->contact_name]) }}

{{ __('mail.practice_removed_company', [
'student_first' => $practice->student->first_name,
'student_last' => $practice->student->last_name,
'job_title' => $practice->job_title
]) }}
@endif

{{ __('mail.regards') }}

</x-mail::message>
