<x-mail::message>
    # Dobrý deň {{ $practice->student->first_name }},

    Stav Vašej praxe {{ $practice->job_title }} bol zmenený na {{ $status }}.

    S pozdravom,
    {{ $user->name }}
</x-mail::message>
