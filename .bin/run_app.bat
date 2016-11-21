::получаем curpath:
@FOR /f %%i IN ("%0") DO SET curpath=%~dp0
::задаем основные переменные окружения
@CALL "%curpath%/set_path.bat"


::@del app.exe
@CLS

@echo === build =====================================================================
::go build -o app.exe

@echo ==== start ====================================================================
::app.exe
:: >> app.exe.log 2>&1

::h:\Program\Otchets\gis\in\46\Шаблон импорта ЛС-10.0.2.1__мира_4а__Результат.xlsx


::  import_lc_from_elc     обновление наших данных лс из файла возвращаемого системой 'Помещения и ЕЛС*'
::  import_lc_from_result  обновление наших данных лс из нашего обработанного файла экспорта 'Шаблон импорта ЛС*'
::  export_flats           заполнение данными файла 'Шаблон импорт сведений о МКД-УО-10.0.2.1.xlsx'
::  export_lc              заполнение данными файла 'Шаблон импорта ЛС-10.0.2.1.xlsx'



::загружаем данные по звизде
::@php.exe script.php [fcomp] 2 [type] "import_lc_from_result" [from] "h:\Program\Otchets\gis\in\2\Шаблон импорта ЛС-10.0.2.1_Результат.xlsx"
::@php.exe script.php [fcomp] 2 [type] "import_lc_from_elc" [from] "h:\Program\Otchets\gis\in\2\Помещения и ЕЛС от 19.11.2016 14-44_Результат.xlsx"

::загружаем данные по атланту
::@php.exe script.php [fcomp] 46 [type] "import_lc_from_result" [from] "h:\Program\Otchets\gis\in\46\Шаблон импорта ЛС-10.0.2.1_ВСЕ_Результат.xlsx"
::@php.exe script.php [fcomp] 46 [type] "import_lc_from_elc" [from] "h:\Program\Otchets\gis\in\46\Помещения и ЕЛС от 18.11.2016 15-23_Результат.xlsx"

::загружаем данные по стройкому
::@php.exe script.php [fcomp] 57 [type] "import_lc_from_result" [from] "h:\Program\Otchets\gis\in\57\Шаблон импорта ЛС-10.0.2.1_Результат.xlsx"
::@php.exe script.php [fcomp] 57 [type] "import_lc_from_elc" [from] "h:\Program\Otchets\gis\in\57\Помещения и ЕЛС от 18.11.2016 15-23_Результат.xlsx"

::загружаем данные по технику
::@php.exe script.php [fcomp] 45 [type] "import_lc_from_result" [from] "h:\Program\Otchets\gis\in\ready\45\Шаблон импорта ЛС-10.0.2.1_Результат.xlsx"
::@php.exe script.php [fcomp] 45 [type] "import_lc_from_elc" [from] "h:\Program\Otchets\gis\in\46\Помещения и ЕЛС от 18.11.2016 15-23_Результат.xlsx"




::@PAUSE
::EXIT 0

@SET wpath=h:\Program\Otchets\gis\

@SET opt_uk=[fcomp] 58 [fperiod] 2016.10

@echo экспорт данных по домам,подъездам,кв:
@SET fname=Шаблон импорт сведений о МКД-УО-10.0.2.1.xlsx
@SET opts=[type] "export_flats" [from] "%wpath%\in\%fname%" [to] "%wpath%\out\%fname%" %opt_uk%
@php.exe script.php %opts%


@echo экспорт данных по лс:
@SET fname=Шаблон импорта ЛС-10.0.2.1.xlsx
@SET opts=[type] "export_lc"    [from] "%wpath%\in\%fname%" [to] "%wpath%\out\%fname%" %opt_uk%
@php.exe script.php %opts%


@echo ==== end ======================================================================
@PAUSE
