::����砥� curpath:
@FOR /f %%i IN ("%0") DO SET curpath=%~dp0
::������ �᭮��� ��६���� ���㦥���
@CALL "%curpath%/set_path.bat"


::@del app.exe
@CLS

@echo === build =====================================================================
::go build -o app.exe

@echo ==== start ====================================================================
::app.exe
:: >> app.exe.log 2>&1

::h:\Program\Otchets\gis\in\46\������ ������ ��-10.0.2.1__���_4�__�������.xlsx


::  import_lc_from_elc     ���������� ���� ������ �� �� 䠩�� �����頥���� ��⥬�� '����饭�� � ���*'
::  import_lc_from_result  ���������� ���� ������ �� �� ��襣� ��ࠡ�⠭���� 䠩�� �ᯮ�� '������ ������ ��*'
::  export_flats           ���������� ����묨 䠩�� '������ ������ ᢥ����� � ���-��-10.0.2.1.xlsx'
::  export_lc              ���������� ����묨 䠩�� '������ ������ ��-10.0.2.1.xlsx'



::����㦠�� ����� �� ������
::@php.exe script.php [fcomp] 2 [type] "import_lc_from_result" [from] "h:\Program\Otchets\gis\in\2\������ ������ ��-10.0.2.1_�������.xlsx"
::@php.exe script.php [fcomp] 2 [type] "import_lc_from_elc" [from] "h:\Program\Otchets\gis\in\2\����饭�� � ��� �� 19.11.2016 14-44_�������.xlsx"

::����㦠�� ����� �� �⫠���
::@php.exe script.php [fcomp] 46 [type] "import_lc_from_result" [from] "h:\Program\Otchets\gis\in\46\������ ������ ��-10.0.2.1_���_�������.xlsx"
::@php.exe script.php [fcomp] 46 [type] "import_lc_from_elc" [from] "h:\Program\Otchets\gis\in\46\����饭�� � ��� �� 18.11.2016 15-23_�������.xlsx"

::����㦠�� ����� �� ��ன����
::@php.exe script.php [fcomp] 57 [type] "import_lc_from_result" [from] "h:\Program\Otchets\gis\in\57\������ ������ ��-10.0.2.1_�������.xlsx"
::@php.exe script.php [fcomp] 57 [type] "import_lc_from_elc" [from] "h:\Program\Otchets\gis\in\57\����饭�� � ��� �� 18.11.2016 15-23_�������.xlsx"

::����㦠�� ����� �� �孨��
::@php.exe script.php [fcomp] 45 [type] "import_lc_from_result" [from] "h:\Program\Otchets\gis\in\ready\45\������ ������ ��-10.0.2.1_�������.xlsx"
::@php.exe script.php [fcomp] 45 [type] "import_lc_from_elc" [from] "h:\Program\Otchets\gis\in\46\����饭�� � ��� �� 18.11.2016 15-23_�������.xlsx"




::@PAUSE
::EXIT 0

@SET wpath=h:\Program\Otchets\gis\

@SET opt_uk=[fcomp] 58 [fperiod] 2016.10

@echo �ᯮ�� ������ �� �����,���ꥧ���,��:
@SET fname=������ ������ ᢥ����� � ���-��-10.0.2.1.xlsx
@SET opts=[type] "export_flats" [from] "%wpath%\in\%fname%" [to] "%wpath%\out\%fname%" %opt_uk%
@php.exe script.php %opts%


@echo �ᯮ�� ������ �� ��:
@SET fname=������ ������ ��-10.0.2.1.xlsx
@SET opts=[type] "export_lc"    [from] "%wpath%\in\%fname%" [to] "%wpath%\out\%fname%" %opt_uk%
@php.exe script.php %opts%


@echo ==== end ======================================================================
@PAUSE
