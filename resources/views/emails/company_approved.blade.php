<x-mail::message>
# {{ __('mail.company_approved_title') }}

{{ __('mail.company_approved_message') }}

{{ __('mail.company_approved_description') }}

@if(!empty($url))
<x-mail::button :url="$url">
{{ __('mail.company_approved_button') }}
</x-mail::button>
@endif

{{ __('mail.company_approved_thanks') }}<br>
{{ config('app.name') }}
</x-mail::message>
