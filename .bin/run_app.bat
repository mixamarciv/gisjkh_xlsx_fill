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

SET wpath=d:\program\go\projects\gisjkh_xlsx_fill\test
SET opts=--type "export_flats" --from "%wpath%\from.log" --to "%wpath%\to.log" --fcomp 1 --fperiod 1
app.exe %opts%

@echo ==== end ======================================================================
@PAUSE
