<!DOCTYPE html>
<html lang="en">
<head>

    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Faktura</title>
    <link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin><link href="https://fonts.googleapis.com/css2?family=Fredoka+One&family=Inconsolata&family=Open+Sans:wght@300;400;600;800&family=Quicksand:wght@300;400;700&family=Roboto:wght@100;400;700&family=Varela+Round&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ resource_path('style/Invoice.css') }}" />
</head>
<body>
    <p id='data'>Wystawiono dnia {{Carbon\Carbon::now()->format('d-m-Y')}}, Warszawa</p>
    <div id='fakturaInfo' style="position: absolute; right: 0; transform: translateY(-10px)">
        <h2>Faktura VAT nr {{$nazwa}}</h2>
        <div class='fakturaData'>
            <span class='text' style="width: 150px">Data sprzedaży:</span><span class='value'>{{$dane['data_sprzedazy']}}</span>
        </div>
        <div class='fakturaData'>
            <span class='text' style="width: 150px">Sposób zapłaty:</span><span class='value'>{{$dane['sposob_zaplaty']}}</span>
        </div>
        <div class='fakturaData'>
            <span class='text' style="width: 150px">Termin płatności:</span><span class='value'>{{$dane['termin_platnosci']}}</span>
        </div>
    </div>
    <div id='fakturaKupSprz' style="transform: translateY(-40px)">
        <div id='seller' style="float: left; transform: translateY(10px)">
            <h2>Sprzedawca:</h2>
            <p>NAZWA</p>
            <p>ADRES</p>
            <p>00-000 MIASTO</p>
            <p>NIP 0000000000</p>
            <h2 id='filia'>Filia:</h2>
            <p>NAZWA</p>
            <p>ADRES</p>
            <p>KOD MIASTO</p>
        </div>
        <div id='buyer' style="float: right; transform: translateX(-40px) translateY(10px)">
            <h2>Nabywca:</h2>
            <p>{{$nabywca['nazwa']}}</p>
            <p>{{$nabywca['adres']}}</p>
            <p>{{$nabywca['miasto']}}</p>
            @if($nabywca['nip'] != null)
                <p>NIP {{$nabywca['nip']}}</p>
            @endif
        </div>
    </div>
    <div id='fakturaContent' style="clear: both; transform: translateY(-20px)">
        <h2>POZYCJE FAKTURY</h2>
        <table>
            <tr>
                <th class='lp'>Lp.</th>
                <th class='nazwa'>Nazwa towaru lub usługi</th>
                <th class='ilosc'>Ilość</th>
                <th class='jedn'>Jedn.</th>
                <th class='cjb'>Cena <br/>jedn. brutto</th>
                <th class='wb'>Wartość brutto</th>
                <th class='vat'>Stawka VAT</th>
            </tr>
            @foreach($pozycje as $pozycja)
            <tr>
                <td class='lp'>{{$pozycja['lp']}}</td>
                <td class='nazwa'>{{$pozycja['nazwa']}}</td>
                <td class='ilosc'>{{$pozycja['ilosc']}}</td>
                <td class='jedn'>{{$pozycja['jednostka']}}</td>
                <td class='cjb'>{{number_format($pozycja['kwota'],2,',','')}}</td>
                <td class='wb'>{{number_format(($pozycja['kwota']*$pozycja['ilosc']),2,',','')}}</td>
                <td class='vat'>{{$pozycja['vat']}}%</td>
            </tr>
            @endforeach
        </table>
    </div>
    <div id='fakturaSummary' style="transform: translateY(-20px)">
        <h2>PODSUMOWANIE</h2>
        <table>
            <tr>
                <th class='summaryVert'></th>
                <th class='summaryVATRate'>Stawka VAT</th>
                <th class='summaryNetto'>Wartość netto</th>
                <th class='summaryVAT'>VAT</th>
                <th class='summaryBrutto'>Wartość brutto</th>
            </tr>
            @foreach($vats as $vat)
            <tr>
                <td class='summaryVert'></td>
                <td class='summaryVATRate'>{{$vat}}%</td>
                <td class='summaryNetto'>{{number_format($summary[$vat]['netto'],2,',','')}}</td>
                <td class='summaryVAT'>{{number_format($summary[$vat]['vat'],2,',','')}}</td>
                <td class='summaryBrutto'>{{number_format((double)$summary[$vat]['brutto'],2,',','')}}</td>
            </tr>
            @endforeach
            <tr>
                <th class='summaryVert'>Razem:</th>
                <th class='summaryVATRate'></th>
                <th class='summaryNetto'>{{$fullNetto}}</th>
                <th class='summaryVAT'>{{$fullVat}}</th>
                <th class='summaryBrutto'>{{$fullBrutto}}</th>
            </tr>
            <tr>
                <th class='summaryVert'>Zapłacono:</th>
                <th class='summaryVATRate'></th>
                <th class='summaryNetto'></th>
                <th class='summaryVAT'></th>
                <th class='summaryBrutto'>{{$fullBrutto}}</th>
            </tr>
            <tr>
                <th class='summaryVert'>Pozostało do zapłaty:</th>
                <th class='summaryVATRate'></th>
                <th class='summaryNetto'></th>
                <th class='summaryVAT'></th>
                <th class='summaryBrutto'>0,00</th>
            </tr>
            <tr>
                <th class='summaryVert'>Słownie:</th>
                <th class='summaryVATRate'></th>
                <th class='summaryNetto'></th>
                <th class='summaryVAT'></th>
                <th class='summarySlownie'>{{$slowne}} PLN</th>
            </tr>
            <tr>
                <th class='summaryVert'>Konto bankowe:</th>
                <th class='summaryVATRate'></th>
                <th class='summaryNetto'></th>
                <th class='summaryVAT'></th>
                <td class='summarySlownie'>{{$nrKonta}}</td>
            </tr>
            <tr>
                <th class='summaryVert'>Uwagi:</th>
                <th class='summaryVATRate'></th>
                <th class='summaryNetto'></th>
                <th class='summaryVAT'></th>
                <td class='summarySlownie'>{{$dane['uwagi']}}</td>
            </tr>
        </table>
        <div id='podpis' style="margin-top: 10px">
            <span id='odbPodpis' style="float: left; margin-left: 40px">Faktura bez podpisu odbiorcy</span>
            <span style="float: right; margin-right: 40px" id='upoPodpis'>Osoba upoważniona do wystawienia faktury VAT<br><strong>IMIE NAZWISKO</strong></span>
        </div>
    </div>
</body>
</html>
