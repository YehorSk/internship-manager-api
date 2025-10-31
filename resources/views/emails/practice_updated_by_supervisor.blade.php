@php
    use App\Enums\RoleEnum;
@endphp
<x-mail::message>

    @if($student)
    # Dobrý deň {{ $student->first_name }},

    Údaje o vašej praxi {{ $practice->job_title }} boli zmenené.
    @endif
    @if($company)
    # Dobrý deň {{ $company->contact_name }},

    Udaje práce študenta {{ $practice->student->first_name }} {{ $practice->student->last_name }}
    pre prax {{ $practice->job_title }} boli zmenený.
    @endif

    S pozdravom,
    Tím Internship Manager
</x-mail::message>
