<x-mail::message>
    # Dobrý deň {{ $user->name }},

    Stav práce študenta **{{ $practice->student->first_name }} {{ $practice->student->last_name }}**
    pre prax **{{ $practice->job_title }}** bol zmenený na **{{ $status }}**.

    S pozdravom,
    Tím Internship Manager
</x-mail::message>
