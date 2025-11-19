<x-mail::message>
# {{ __('mail.company_profile_canceled_title') }}

{{ __('mail.company_profile_canceled_message') }}

<x-mail::button :url="$url ?? ''">
{{ __('mail.company_profile_canceled_button') }}
</x-mail::button>

{{ __('mail.company_profile_canceled_thanks') }}<br>
{{ config('app.name') }}
</x-mail::message>
