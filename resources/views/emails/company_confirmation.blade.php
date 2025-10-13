<x-mail::message>
# Dobrý deň,

Vaša spoločnosť **{{ $company->name }}** bola zaregistrovaná v systéme. Pre aktiváciu účtu kliknite na nasledujúci odkaz:

<x-mail::button :url="$activationUrl">
Aktivovať účet
</x-mail::button>

Ak tlačidlo nefunguje, skopírujte a vložte nasledujúci odkaz do adresného riadku prehliadača:
{{ $activationUrl }}

Ak ste sa neregistrovali, ignorujte tento email.

S pozdravom,<br>
Internship Manager Team
</x-mail::message>
