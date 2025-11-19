<x-mail::message>
# {{ __('mail.company_registered_greeting') }}

{{ __('mail.company_registered_body', ['company_name' => $company->name]) }}

<x-mail::button :url="$activationUrl">
{{ __('mail.company_register_button') }}
</x-mail::button>

{{ __('mail.company_register_link_info') }}
{{ $activationUrl }}

{{ __('mail.company_register_ignore') }}

{{ __('mail.regards') }}<br>
{{ config('app.name') }}
</x-mail::message>
