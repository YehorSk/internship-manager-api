<x-mail::message>
# Dobrý deň,
@if($options['isReconfirm'])
Máme pre Vás žiadosť o opätovné schválenie{{ $options['actionType'] == 'report' ? ' správy' : ' zmluvy' }} týkajúcej sa stáže.
@else
Máme pre Vás žiadosť o schválenie{{ $options['actionType'] == 'report' ? ' správy' : ' zmluvy' }} týkajúcej sa stáže.
@endif

Prosím, skontrolujte detaily nižšie a potvrďte alebo odmietnite {{ $options['actionType'] == 'report' ? 'správu' : 'zmluvu' }}.

**Študent:** {{ $options['printName'] }}

**E-mail študenta:** {{ $practice->student->student_email }}

**Študijný program:** {{ $practice->studyProgram->name }}

**Obdobie stáže:** {{ $practice->start_date?->format('d.m.Y') ?? '—' }} - {{ $practice->end_date?->format('d.m.Y') ?? '—' }}

**Spoločnosť:** {{ $practice->practiceCompany->name }}

**ID praxe:** {{ $practice->id }}

@isset($options['updateLink'])
<x-mail::button :url="$options['updateLink']">
Skontrolovať a potvrdiť
</x-mail::button>
@endisset

Ak máte otázky k {{ $options['actionType'] == 'report' ? 'správe' : 'zmluve' }}, prosím, kontaktujte nás na tomto e-maile: {{ config('mail.from.address') ?? '—' }}.

Poznámka: Toto oznámenie bolo vytvorené automaticky. Ak už bol dokument spracovaný, ignorujte, prosím, tento e-mail.

Ďakujeme za spoluprácu,
{{ config('app.name') }}
</x-mail::message>
