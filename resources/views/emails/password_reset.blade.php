<p>Ahoj {{ $user->first_name ?? '' }},</p>

<p>Klikni na tento odkaz pre obnovenie hesla:</p>

<p><a href="{{ $link }}">{{ $link }}</a></p>

<p>Ak si nepožiadal o obnovenie hesla, ignoruj tento e-mail.</p>
