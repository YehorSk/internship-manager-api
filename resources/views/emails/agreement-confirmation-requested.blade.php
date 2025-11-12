<x-mail::message>
# {{ __('mail.approval_greeting') }}

@if($options['isReconfirm'])
{{ $options['actionType'] == 'report'
? __('mail.approval_request_reconfirm_report')
: __('mail.approval_request_reconfirm_agreement') }}
@else
{{ $options['actionType'] == 'report'
? __('mail.approval_request_report')
: __('mail.approval_request_agreement') }}
@endif

{{ __('mail.approval_check_details', ['type' => $options['actionType'] == 'report' ? 'správu' : 'zmluvu']) }}

**{{ __('mail.approval_student') }}** {{ $options['printName'] }} <br>
**{{ __('mail.approval_student_email') }}** {{ $practice->student->student_email }} <br>
**{{ __('mail.approval_study_program') }}** {{ $practice->studyProgram->name }} <br>
**{{ __('mail.approval_internship_period') }}** {{ $practice->start_date?->format('d.m.Y') ?? '—' }} - {{ $practice->end_date?->format('d.m.Y') ?? '—' }} <br>
**{{ __('mail.approval_company') }}** {{ $practice->practiceCompany->name }} <br>
**{{ __('mail.approval_practice_id') }}** {{ $practice->id }} <br>

@isset($options['updateLink'])
<x-mail::button :url="$options['updateLink']">
{{__('mail.approval_check_button')}}
</x-mail::button>
@endisset

{{ __('mail.approval_contact_us', ['type' => $options['actionType'] == 'report' ? 'správe' : 'zmluve']) }} {{ config('mail.from.address') ?? '—' }}

{{ __('mail.approval_auto_note') }}

{{ __('mail.approval_thanks') }}
{{ config('app.name') }}
</x-mail::message>
