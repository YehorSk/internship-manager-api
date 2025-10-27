<x-mail::message>
# Dobrý deň,

@if($options['isReconfirm'])
Máme pre Vás žiadosť o opätovné schválenie zmluvy týkajúcej sa stáže.
@else
Máme pre Vás žiadosť o schválenie zmluvy týkajúcej sa novej stáže.
@endif

Prosím, skontrolujte detaily nižšie a potvrďte alebo odmietnite zmluvu.

**Študent:** {{ $options['printName'] }}

**E-mail študenta:** {{ $practice->student->student_email }}

**Študijný program:** {{ $practice->studyProgram->name }}

**Obdobie stáže:** {{ $practice->start_date }} — {{ $practice->end_date }}

**Spoločnosť:** {{ $practice->practiceCompany->name }}

**ID praxe:** {{ $practice->id }}

@isset($options['confirmLink'])
<x-mail::button :url="$options['confirmLink']">
Potvrdiť zmluvu
</x-mail::button>
@endisset

@isset($options['rejectLink'])
<x-mail::button :url="$options['rejectLink']" color="secondary">
Odmietnuť / Požiadať o zmeny
</x-mail::button>
@endisset

@if(empty($options['confirmLink']) && empty($options['rejectLink']))
Ak máte otázky k dohode, prosím, kontaktujte nás na tomto e-maile: {{ config('mail.from.address') ?? '—' }}.
@endif

Poznámka: Toto oznámenie bolo vytvorené automaticky. Ak už bol dokument spracovaný, ignorujte, prosím, tento e-mail.

Ďakujeme za spoluprácu,
{{ config('app.name') }}
</x-mail::message>
