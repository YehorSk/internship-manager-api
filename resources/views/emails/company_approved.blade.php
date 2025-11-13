<x-mail::message>
# {{ __('emails.company_approved_title') }}

{{ __('emails.company_approved_message') }}

{{ __('emails.company_approved_description') }}

@if(!empty($url))
<x-mail::button :url="$url">
{{ __('emails.company_approved_button') }}
</x-mail::button>
@endif

{{ __('emails.company_approved_thanks') }}<br>
{{ config('app.name') }}
</x-mail::message>
