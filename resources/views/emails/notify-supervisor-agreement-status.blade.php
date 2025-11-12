<x-mail::message>
# {{ __('mail.greeting', ['name' => $user->name]) }}

{{ __('mail.practice_status', [
'student_first' => $practice->student->first_name,
'student_last' => $practice->student->last_name,
'job_title' => $practice->job_title,
'status' => $status
]) }}

{{ __('mail.regards') }}
</x-mail::message>
