@echo off
rem ============================================================================
rem  devices:door - obvertka dlya planirovshchika Windows.
rem
rem  Vse argumenty peredayutsya zadache devices:door, vyvod dopisyvaetsya
rem  v door_task.log ryadom s etim faylom, kod vozvrata peredaetsya dalshe.
rem
rem  Primery:
rem    devices_door_run.bat --command=lockdoor --group=2
rem    devices_door_run.bat --command=unlockdoor --group=2
rem    devices_door_run.bat --command=opendoor --id_dev=483,484
rem    devices_door_run.bat --command=lockdoor --group=2 --test=1
rem
rem  Proverka pered postanovkoy v raspisanie:
rem    devices_door_run.bat --list=1
rem ============================================================================

setlocal

set "PHP=c:\xampp\php\php.exe"
set "MINION=c:\xampp\htdocs\city\modules\minion\minion"
set "LOG=%~dp0door_task.log"

"%PHP%" "%MINION%" --task=devices:door %* --encoding=cp1251 >> "%LOG%" 2>&1

exit /b %ERRORLEVEL%
