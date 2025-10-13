<x-mail::message>
# Gratulujeme!

Váš profil spoločnosti bol úspešne schválený garantom praxe.

Teraz môžete naplno využívať všetky možnosti systému na prácu so stážistami a správu profilu spoločnosti.

@if(!empty($url))
<x-mail::button :url="$url">
Prejsť do osobného účtu
</x-mail::button>
@endif

Ďakujeme za spoluprácu!<br>
{{ config('app.name') }}
</x-mail::message>
