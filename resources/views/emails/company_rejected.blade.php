<x-mail::message>
# {{ __('translations.company_profile_canceled_title') }}

{{ __('translations.company_profile_canceled_message') }}

<x-mail::button :url="$url ?? ''">
{{ __('translations.company_profile_canceled_button') }}
</x-mail::button>

{{ __('translations.company_profile_canceled_thanks') }}<br>
{{ config('app.name') }}
</x-mail::message>
