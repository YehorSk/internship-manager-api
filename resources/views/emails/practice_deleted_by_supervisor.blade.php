@php
use App\Enums\RoleEnum;
@endphp

<x-mail::message>

@if($student)
# {{ __('emails.greeting_student', ['name' => $student->first_name]) }}

{{ __('emails.practice_removed_student', ['job_title' => $practice->job_title]) }}
@endif

@if($company)
# {{ __('emails.greeting_student', ['name' => $company->contact_name]) }}

{{ __('emails.practice_removed_company', [
'student_first' => $practice->student->first_name,
'student_last' => $practice->student->last_name,
'job_title' => $practice->job_title
]) }}
@endif

{{ __('emails.regards') }}

</x-mail::message>
