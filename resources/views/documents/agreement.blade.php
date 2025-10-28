<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="utf-8">
    <title>Dohoda o odbornej praxi študenta</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        @page { size: A4; margin: 20mm; }
        html, body { height: 100%; }
        body {
            font-family: "DejaVu Sans", "Segoe UI", Roboto, Arial, sans-serif;
            font-size: 12pt;
            line-height: 1.35;
            color: #000;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .doc {
            max-width: 920px;
            margin: 0 auto;
        }
        .muted { color:#555; font-size:11pt; }
        .title {
            text-align: center;
            font-weight: 700;
            font-size: 16pt;
            margin: 10pt 0 14pt;
        }
        .subtitle { text-align:center; margin: 0 0 12pt; }
        .section-title {
            font-weight: 700;
            margin: 14pt 0 6pt;
            text-transform: none;
        }
        .block { margin: 8pt 0; }
        .indent { margin-left: 18pt; }

        .list { margin: 4pt 0 6pt 18pt; padding-left: 0; }
        .list > li { margin: 1.5pt 0; }

        .list {
            page-break-inside: auto;
            break-inside: auto;
            -webkit-column-break-inside: auto;
        }
        .list > li {
            page-break-inside: auto;
            break-inside: auto;
            -webkit-column-break-inside: auto;
        }
        .indent {
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .section-title, .two-col, .sign-grid { page-break-after: avoid; }
        .two-col {
            width: 100%;
            border-collapse: collapse;
            margin: 6pt 0 10pt;
        }
        .two-col td {
            vertical-align: top;
            width: 50%;
            padding: 2pt 6pt 2pt 0;
        }
        .label { white-space: nowrap; }
        .dots {
            border-bottom: 1px dotted #000;
            min-width: 220px;
            vertical-align: bottom;
        }
        .sign-grid {
            width: 100%;
            margin-top: 30pt;
            border-collapse: collapse;
        }
        .sign-grid td {
            width: 33.333%;
            text-align: center;
            padding: 18pt 8pt 0;
        }
        .sign-line {
            border-top: 1px solid #000;
            height: 0;
            margin: 0 auto 6pt;
            width: 85%;
        }
        .small { font-size: 10.5pt; }
        .contact-line { margin-top: 2pt; }
    </style>
</head>
<body>
<div class="doc">

    <h1 class="title">Dohoda o odbornej praxi študenta</h1>
    <div class="subtitle small">
        uzatvorená v zmysle § 51 Občianskeho zákonníka a Zákona č. 131/2002 Z.z. o vysokých školách
    </div>

    <!-- Univerzita / Fakulta -->
    <div class="block">
        <div><strong>Univerzita Konštantína Filozofa v Nitre</strong></div>
        <div>Fakulta prírodných vied a informatiky</div>
        <div>Trieda A. Hlinku 1, 949 01 Nitra</div>
        <div>
            v zastúpení
            <span class="dots">
        {{ $dekan_name ?? 'prof. RNDr. František Petrovič, PhD.' }}
      </span>
            – dekan fakulty
        </div>
        <div class="contact-line small">
            e-mail:
            <span class="dots">
        {{ $dekan_email ?? 'fpetrovic@ukf.sk' }}
      </span>,
            tel.
            <span class="dots">
        {{ $dekan_tel ?? '037/6408 555' }}
      </span>
        </div>
    </div>

    <!-- Poskytovateľ -->
    <div class="block">
        <div><strong>Poskytovateľ odbornej praxe (organizácia, resp. inštitúcia)</strong></div>
        <table class="two-col">
            <tr>
                <td class="label">Plný názov a adresa</td>
                <td><span class="dots">{{ $practice->practiceCompany->name ?? '' }}, {{ $practice->practiceCompany->address ?? '' }}</span></td>
            </tr>
            <tr>
                <td class="label">v zastúpení (meno, pozícia)</td>
                <td><span class="dots">{{ $practice->practiceCompany->contact_name ?? '' }}</span></td>
            </tr>
        </table>
    </div>

    <!-- Študent -->
    <div class="block">
        <div><strong>Študent:</strong></div>
        <table class="two-col">
            <tr>
                <td class="label">Meno a priezvisko</td>
                <td><span class="dots">{{ $practice->student->user->name ?? ($practice->student->first_name . ' ' . $practice->student->last_name) ?? '' }}</span></td>
            </tr>
            <tr>
                <td class="label">Adresa trvalého bydliska</td>
                <td><span class="dots">{{ $practice->student->address ?? '' }}</span></td>
            </tr>
            <tr>
                <td class="label">Kontakt študenta FPVaI UKF v Nitre (e-mail, tel.)</td>
                <td><span class="dots">{{ $practice->student->student_email ?? '' }}, tel.{{ $practice->student->phone ?? '' }}</span></td>
            </tr>
            <tr>
                <td class="label">Študijný program</td>
                <td><span class="dots">{{ $practice->studyProgram->name ?? '' }}</span></td>
            </tr>
        </table>
    </div>

    <div class="block">
        <em>uzatvárajú túto dohodu o odbornej praxi študenta.</em>
    </div>

    <!-- I. Predmet dohody -->
    <div class="section-title">I. Predmet dohody</div>
    <div class="block">
        Predmetom tejto dohody je vykonanie odbornej praxe študenta v rozsahu
        <span class="dots">{{ $practice_hours ?? '150' }}</span> hodín,
        v termíne od <span class="dots">{{ $practice->start_date?->format('d.m.Y') ?? '—' }}</span>
        do <span class="dots">{{ $practice->end_date?->format('d.m.Y') ?? '—' }}</span> bezodplatne.
    </div>

    <!-- II. Práva a povinnosti -->
    <div class="section-title">II. Práva a povinnosti účastníkov dohody</div>

    <div class="block">
        <strong>1. Fakulta prírodných vied a informatiky UKF v Nitre:</strong>
        <ol class="list">
            <li>
                Poverí svojho zamestnanca:
                <span class="dots">
          {{ $guarantors ?? 'Mgr. Martin Vozár, PhD. (mvozar@ukf.sk) za 1. stupeň, PaedDr. Peter Švec, Ph.D. (psvec@ukf.sk) za 2. stupeň' }}
        </span>
                (ďalej garant odbornej praxe) garanciou odbornej praxe.
            </li>
            <li>
                Prostredníctvom garanta odbornej praxe:
                <ol class="sublist" type="a">
                    <li>poskytne študentovi informácie o organizácii praxe, o podmienkach dojednania dohody o odbornej praxi, o obsahovom zameraní odbornej praxe a o požiadavkách na obsahovú náplň správy z odbornej praxe, ako aj návrh dohody o odbornej praxi študenta,</li>
                    <li>rozhodne o udelení hodnotenia „ABS“ (absolvoval) študentovi na základe dokladu „Výkaz o vykonanej odbornej praxi" a študentom vypracovanej správy o odbornej praxi (vrátane verejnej obhajoby výsledkov),</li>
                    <li>spravuje vyplnenú a účastníkmi podpísanú dohodu o odbornej praxi.</li>
                </ol>
            </li>
        </ol>
    </div>

    <div class="block">
        <strong>2. Poskytovateľ odbornej praxe:</strong>
        <ol class="list">
            <li>
                poverí svojho zamestnanca (tútor – zodpovedný za odbornú prax v organizácii)
                <span class="dots">{{ $practice->practiceCompany->contact_name ?? '' }}</span>,
                ktorý bude dohliadať na dodržiavanie dohody, plnenie obsahovej náplne praxe a bude nápomocný pri získavaní údajov pre správu z praxe,
            </li>
            <li>na začiatku praxe vykoná poučenie o BOZP v zmysle platných predpisov,</li>
            <li>vzniknuté organizačné problémy rieši spolu s garantom odbornej praxe,</li>
            <li>
                po ukončení praxe vydá študentovi „Výkaz o vykonanej odbornej praxi“, ktorý obsahuje popis činností a stručné hodnotenie študenta
                a je jedným z predpokladov úspešného ukončenia predmetu Odborná prax,
            </li>
            <li>umožní garantovi odbornej praxe a garantovi študijného predmetu kontrolu plnených úloh.</li>
        </ol>
    </div>

    <div class="block">
        <strong>3. Študent FPVaI UKF v Nitre:</strong>
        <ol class="list">
            <li>osobne zabezpečí podpísanie tejto dohody o odbornej praxi študenta,</li>
            <li>zodpovedne vykonáva činnosti pridelené tútorom odbornej praxe,</li>
            <li>zabezpečí doručenie dokladu „Výkaz o vykonanej odbornej praxi“ v predpísaných termínoch,</li>
            <li>okamžite informuje garanta odbornej praxe o problémoch brániacich plneniu praxe.</li>
        </ol>
    </div>

    <!-- III. Všeobecné a záverečné ustanovenia -->
    <div class="section-title">III. Všeobecné a záverečné ustanovenia</div>
    <div class="block">
        Dohoda sa uzatvára na dobu určitú. Dohoda nadobúda platnosť a účinnosť dňom podpísania obidvomi zmluvnými stranami.
        Obsah dohody sa môže meniť písomne len po súhlase jej zmluvných strán. Dohoda sa uzatvára v 3 vyhotoveniach,
        každá zmluvná strana obdrží jedno vyhotovenie dohody.
    </div>

    <!-- Miesto a dátum -->
    <table class="two-col">
        <tr>
            <td>V Nitre, dňa <span class="dots">{{ $signed_date_faculty ?? '' }}</span></td>
            <td>V <span class="dots">{{ $place_company ?? '.........' }}</span>, dňa <span class="dots">{{ $signed_date_company ?? '' }}</span></td>
        </tr>
    </table>

    <!-- Podpisy -->
    <table class="sign-grid">
        <tr>
            <td>
                <div class="sign-line"></div>
                <div class="small">
                    {{ $dekan_name ?? 'prof. RNDr. František Petrovič, PhD.' }}<br>
                    dekan FPVaI UKF v Nitre
                </div>
            </td>
            <td>
                <div class="sign-line"></div>
                <div class="small">
                    <span>{{ $practice->practiceCompany->contact_name ?? '' }}</span><br>
                    štatutárny zástupca pracoviska odb. praxe
                </div>
            </td>
        </tr>
        <tr>
            <td></td>
            <td>
                <div class="sign-line"></div>
                <div class="small">
                    <span>{{ $practice->student->user->user->name ?? ($practice->student->first_name . ' ' . $practice->student->last_name) ?? '' }}</span>
                </div>
            </td>
        </tr>
    </table>

</div>
</body>
</html>
