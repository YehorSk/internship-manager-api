<x-mail::message>
# Upozornenie

Potvrdenie profilu vašej spoločnosti bolo zrušené garantom praxe.

Momentálne nemôžete naplno využívať všetky možnosti systému. Pre viac informácií kontaktujte, prosím, garanta praxe alebo podporu.

<x-mail::button :url="$url ?? ''">
Prejsť do osobného účtu
</x-mail::button>

Ďakujeme za pochopenie!<br>
{{ config('app.name') }}
</x-mail::message>
