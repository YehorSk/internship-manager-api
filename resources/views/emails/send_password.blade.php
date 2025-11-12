<x-mail::message>
    # {{ __('emails.account_created_greeting', ['name' => $user->name]) }}

    {{ __('emails.account_created_message') }}

    **{{ __('emails.temporary_password', ['password' => $password]) }}**

    {{ __('emails.account_created_instruction') }}
</x-mail::message>
