<x-mail::message>
#{{ __('emails.company_registered_greeting') }}

{{ __('emails.company_registered_body', ['company_name' => $company->name]) }}

<x-mail::button :url="$activationUrl">
{{ __('emails.company_register_button') }}
</x-mail::button>

{{ __('emails.company_register_link_info') }}
{{ $activationUrl }}

{{ __('emails.company_register_ignore') }}

{{ __('emails.regards') }}<br>
{{ config('app.name') }}
</x-mail::message>
