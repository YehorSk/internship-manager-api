@php
    use App\Enums\RoleEnum;
@endphp
<x-mail::message>

@if($student)
# {{ __('mail.greeting_student', ['name' => $student->first_name]) }}

{{ __('mail.practice_updated_student', ['job_title' => $practice->job_title]) }}
@endif
@if($company)
# {{ __('mail.greeting_student', ['name' => $practice->student->first_name]) }}

{{ __('mail.practice_updated_company', [
'student_first' => $practice->student->first_name,
'student_last' => $practice->student->last_name,
'job_title' => $practice->job_title
]) }}
@endif


{{ __('mail.regards') }}

</x-mail::message>
