<x-mail::message>
# {{ __('mail.account_created_greeting', ['name' => $user->name]) }}

{{ __('mail.account_created_message') }}

**{{ __('mail.temporary_password', ['password' => $password]) }}**

{{ __('mail.account_created_instruction') }}
</x-mail::message>
