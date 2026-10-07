@echo off
rem ============================================================================
rem  devices:door - upravlenie tochkami prohoda iz komandnoy stroki
rem
rem  Komandy:  opendoor | lockdoor | opendooralways | unlockdoor
rem  Dobavte --test=1, chtoby posmotret, chto budet zatronuto (komandy ne
rem  otpravlyayutsya).
rem
rem  Raspakovka / encoding: vyvod perekodiruetsya v kodirovku konsoli
rem  avtomaticheski (sm. --encoding).
rem ============================================================================

rem --- 1. Spisok grupp i kolichestvo tochek prohoda (tolko chtenie) ---
c:\xampp\php\php.exe c:\xampp\htdocs\city\modules\minion\minion --task=devices:door --list=1

rem --- 2. Otkryt 1 raz dlya dvuh tochek prohoda po ID_DEV ---
rem     Vazhno: spisok bez probelov (ili v kavychkah), inache cmd razbivaet ego
rem     na dva argumenta.
rem c:\xampp\php\php.exe c:\xampp\htdocs\city\modules\minion\minion --task=devices:door --command=opendoor --id_dev=483,484

rem --- 3. Razblokirovat vse tochki prohoda gruppy 2 ---
rem c:\xampp\php\php.exe c:\xampp\htdocs\city\modules\minion\minion --task=devices:door --command=unlockdoor --group=2

rem --- 4. Proverka bez otpravki komand: spisok tochek prohoda + svyaz s TS ---
rem c:\xampp\php\php.exe c:\xampp\htdocs\city\modules\minion\minion --task=devices:door --command=lockdoor --group=2 --test=1

pause
