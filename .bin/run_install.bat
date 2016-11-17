::получаем curpath:
@FOR /f %%i IN ("%0") DO SET curpath=%~dp0
::задаем основные переменные окружения
@CALL "%curpath%/set_path.bat"


@del app.exe
@CLS

@echo === install ===================================================================
go get "github.com/nakagami/firebirdsql"
go get "github.com/mixamarciv/gofncstd3000"
go get "github.com/jessevdk/go-flags"
go get "github.com/fatih/structs"
go get "github.com/tealeg/xlsx"


go install

@echo ==== end ======================================================================
@PAUSE
