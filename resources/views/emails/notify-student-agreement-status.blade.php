<x-mail::message>
# {{ __('mail.greeting_student', ['name' => $practice->student->first_name]) }}

{{ __('mail.practice_status_student', [
'job_title' => $practice->job_title,
'status' => $status
]) }}

{{ __('mail.regards_sender', ['name' => $user->name]) }}
</x-mail::message>
