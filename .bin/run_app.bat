::����砥� curpath:
@FOR /f %%i IN ("%0") DO SET curpath=%~dp0
::������ �᭮��� ��६���� ���㦥���
@CALL "%curpath%/set_path.bat"


@del app.exe
@CLS

@echo === build =====================================================================
go build -o app.exe

@echo ==== start ====================================================================
::app.exe
:: >> app.exe.log 2>&1

SET wpath=h:\Program\Otchets\gis\
SET fname=������ ������ ᢥ����� � ���-��-10.0.2.1.xlsx

SET opts=--type "export_flats" --from "%wpath%\in\%fname%" --to "%wpath%\out\%fname%" --fcomp 57 --fperiod 2016.10
app.exe %opts%

@echo ==== end ======================================================================
@PAUSE
